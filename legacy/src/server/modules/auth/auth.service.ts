import argon2 from 'argon2';
import crypto from 'crypto';
import jwt from 'jsonwebtoken';
import { UserRoleType, UserSessionPayload } from '../../../../packages/shared/src/types.ts';
import { auditService } from '../audit/audit.service.ts';
import { prisma, isDatabaseOnline } from '../../db/prisma.ts';

export interface UserRecord {
  id: string;
  email: string;
  passwordHash: string;
  role: UserRoleType;
  status: 'ACTIVE' | 'SUSPENDED' | 'PENDING_VERIFICATION';
  mustChangePassword?: boolean;
  brandProfileId?: string;
  marketerProfileId?: string;
  createdAt: Date;
}

export class AuthService {
  private inMemoryUsers: UserRecord[] = [];
  private accessSecret: string;
  private refreshSecret: string;

  constructor() {
    this.accessSecret = process.env.JWT_ACCESS_SECRET || 'nomowly-jwt-access-secret-32-chars-long';
    this.refreshSecret = process.env.JWT_REFRESH_SECRET || 'nomowly-jwt-refresh-secret-32-chars-long';
    // NOTE: Hardcoded admin seeding is removed. Super admin bootstrap must be executed
    // explicitly via scripts/bootstrap-admin.ts using Argon2id and ADMIN_INITIAL_PASSWORD.
  }

  /**
   * Hashes password with Argon2id.
   */
  async hashPassword(password: string): Promise<string> {
    return argon2.hash(password, {
      type: argon2.argon2id,
      memoryCost: 65536,
      timeCost: 3,
      parallelism: 4,
    });
  }

  /**
   * Verifies password against Argon2id hash.
   */
  async verifyPassword(password: string, hash: string): Promise<boolean> {
    try {
      if (hash.startsWith('$argon2')) {
        return await argon2.verify(hash, password);
      }
      return false;
    } catch {
      return false;
    }
  }

  /**
   * Register a new Brand or Marketer User.
   * STRICT SECURITY RULE: Public registration can NEVER grant SUPER_ADMIN.
   */
  async register(params: {
    email: string;
    password: string;
    role: UserRoleType;
    companyName?: string;
    fullName?: string;
    specialization?: 'CREATOR' | 'AFFILIATE' | 'MEDIA_BUYER';
  }): Promise<{ user: Omit<UserRecord, 'passwordHash'>; accessToken: string; refreshToken: string }> {
    const normalizedEmail = params.email.trim().toLowerCase();

    // 1. Strict Role Authorization Check
    if (params.role === 'SUPER_ADMIN' || !['BRAND', 'MARKETER'].includes(params.role)) {
      auditService.recordEvent({
        action: 'PRIVILEGE_ESCALATION_BLOCKED',
        entityType: 'User',
        entityId: normalizedEmail,
        payloadDiff: {
          attemptedRole: params.role,
          email: normalizedEmail,
          reason: 'Public registration attempted to request unauthorized role',
        },
      });
      throw new Error('FORBIDDEN_ROLE: Public registration cannot create SUPER_ADMIN accounts.');
    }

    // 2. Email format check
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(normalizedEmail)) {
      throw new Error('INVALID_EMAIL_FORMAT: Please provide a valid email address.');
    }

    // 3. Password length check
    if (!params.password || params.password.length < 8) {
      throw new Error('PASSWORD_TOO_SHORT: Password must be at least 8 characters long.');
    }

    // 4. Duplicate email check
    const existing = await this.findUserByEmail(normalizedEmail);
    if (existing) {
      throw new Error('EMAIL_ALREADY_REGISTERED: This email is already registered.');
    }

    // 5. Hash password with Argon2id
    const passwordHash = await this.hashPassword(params.password);
    const userId = crypto.randomUUID();

    let brandProfileId: string | undefined;
    let marketerProfileId: string | undefined;

    if (params.role === 'BRAND') {
      brandProfileId = crypto.randomUUID();
    } else if (params.role === 'MARKETER') {
      marketerProfileId = crypto.randomUUID();
    }

    const newUser: UserRecord = {
      id: userId,
      email: normalizedEmail,
      passwordHash,
      role: params.role,
      status: 'ACTIVE',
      mustChangePassword: false,
      brandProfileId,
      marketerProfileId,
      createdAt: new Date(),
    };

    await this.saveUser(newUser);

    auditService.recordEvent({
      userId: newUser.id,
      action: 'USER_REGISTERED',
      entityType: 'User',
      entityId: newUser.id,
      payloadDiff: { role: newUser.role, email: newUser.email, hashingAlgorithm: 'Argon2id' },
    });

    const tokens = this.generateTokens(newUser);
    const { passwordHash: _, ...safeUser } = newUser;

    return {
      user: safeUser,
      ...tokens,
    };
  }

  /**
   * Authenticate user with Argon2id and return JWT tokens.
   */
  async login(params: {
    email: string;
    password: string;
  }): Promise<{ user: Omit<UserRecord, 'passwordHash'>; accessToken: string; refreshToken: string }> {
    const normalizedEmail = params.email.trim().toLowerCase();
    const user = await this.findUserByEmail(normalizedEmail);

    if (!user) {
      throw new Error('INVALID_CREDENTIALS: Email or password is incorrect.');
    }

    if (user.status === 'SUSPENDED') {
      throw new Error('ACCOUNT_SUSPENDED: Your account has been suspended by administration.');
    }

    const isValid = await this.verifyPassword(params.password, user.passwordHash);
    if (!isValid) {
      auditService.recordEvent({
        userId: user.id,
        action: 'FAILED_LOGIN_ATTEMPT',
        entityType: 'User',
        entityId: user.id,
      });
      throw new Error('INVALID_CREDENTIALS: Email or password is incorrect.');
    }

    auditService.recordEvent({
      userId: user.id,
      action: 'USER_LOGGED_IN',
      entityType: 'User',
      entityId: user.id,
      payloadDiff: {
        mustChangePassword: !!user.mustChangePassword,
      },
    });

    const tokens = this.generateTokens(user);
    const { passwordHash: _, ...safeUser } = user;

    return {
      user: safeUser,
      ...tokens,
    };
  }

  /**
   * Change password (required for initial super admin and security resets).
   */
  async changePassword(params: {
    userId: string;
    currentPassword: string;
    newPassword: string;
  }): Promise<{ success: boolean; message: string }> {
    const user = await this.findUserById(params.userId);
    if (!user) {
      throw new Error('USER_NOT_FOUND: User does not exist.');
    }

    const isCurrentValid = await this.verifyPassword(params.currentPassword, user.passwordHash);
    if (!isCurrentValid) {
      throw new Error('INVALID_CURRENT_PASSWORD: Current password is incorrect.');
    }

    if (params.newPassword.length < 12) {
      throw new Error('PASSWORD_TOO_WEAK: New password must be at least 12 characters.');
    }

    const newHash = await this.hashPassword(params.newPassword);
    user.passwordHash = newHash;
    user.mustChangePassword = false;

    // Update in database or memory
    try {
      await prisma.user.update({
        where: { id: user.id },
        data: {
          passwordHash: newHash,
          mustChangePassword: false,
        },
      });
    } catch {
      // In-memory fallback if DB not connected in test
      const memUser = this.inMemoryUsers.find(u => u.id === user.id);
      if (memUser) {
        memUser.passwordHash = newHash;
        memUser.mustChangePassword = false;
      }
    }

    auditService.recordEvent({
      userId: user.id,
      action: 'PASSWORD_CHANGED',
      entityType: 'User',
      entityId: user.id,
      payloadDiff: {
        mustChangePasswordCleared: true,
        hashAlgorithm: 'Argon2id',
      },
    });

    return {
      success: true,
      message: 'Password changed successfully. You may now continue using the platform.',
    };
  }

  /**
   * Verify Access Token.
   */
  verifyAccessToken(token: string): UserSessionPayload {
    try {
      const decoded = jwt.verify(token, this.accessSecret) as UserSessionPayload;
      return decoded;
    } catch {
      throw new Error('INVALID_TOKEN: Token is invalid or expired.');
    }
  }

  private generateTokens(user: UserRecord): { accessToken: string; refreshToken: string } {
    const payload: UserSessionPayload = {
      userId: user.id,
      email: user.email,
      role: user.role,
      brandProfileId: user.brandProfileId,
      marketerProfileId: user.marketerProfileId,
    };

    const accessToken = jwt.sign(payload, this.accessSecret, { expiresIn: '1h' });
    const refreshToken = jwt.sign({ userId: user.id }, this.refreshSecret, { expiresIn: '7d' });

    return { accessToken, refreshToken };
  }

  // Data persistence helpers (PostgreSQL with in-memory fallback for offline test)
  async findUserByEmail(email: string): Promise<UserRecord | undefined> {
    if (await isDatabaseOnline()) {
      try {
        const dbUser = await prisma.user.findUnique({
          where: { email },
          include: { brandProfile: true, marketerProfile: true },
        });
        if (dbUser) {
          return {
            id: dbUser.id,
            email: dbUser.email,
            passwordHash: dbUser.passwordHash,
            role: dbUser.role as UserRoleType,
            status: dbUser.status as any,
            mustChangePassword: dbUser.mustChangePassword,
            brandProfileId: dbUser.brandProfile?.id,
            marketerProfileId: dbUser.marketerProfile?.id,
            createdAt: dbUser.createdAt,
          };
        }
      } catch {
        // Fallback to in-memory
      }
    }
    return this.inMemoryUsers.find(u => u.email === email);
  }

  async findUserById(id: string): Promise<UserRecord | undefined> {
    if (await isDatabaseOnline()) {
      try {
        const dbUser = await prisma.user.findUnique({
          where: { id },
          include: { brandProfile: true, marketerProfile: true },
        });
        if (dbUser) {
          return {
            id: dbUser.id,
            email: dbUser.email,
            passwordHash: dbUser.passwordHash,
            role: dbUser.role as UserRoleType,
            status: dbUser.status as any,
            mustChangePassword: dbUser.mustChangePassword,
            brandProfileId: dbUser.brandProfile?.id,
            marketerProfileId: dbUser.marketerProfile?.id,
            createdAt: dbUser.createdAt,
          };
        }
      } catch {
        // Fallback to in-memory
      }
    }
    return this.inMemoryUsers.find(u => u.id === id);
  }

  private async saveUser(user: UserRecord): Promise<void> {
    if (await isDatabaseOnline()) {
      try {
        await prisma.user.create({
          data: {
            id: user.id,
            email: user.email,
            passwordHash: user.passwordHash,
            role: user.role as any,
            status: user.status as any,
            mustChangePassword: !!user.mustChangePassword,
          },
        });
      } catch {
        // Fallback to in-memory
      }
    }
    this.inMemoryUsers.push(user);
  }

  getAllUsers(): Omit<UserRecord, 'passwordHash'>[] {
    return this.inMemoryUsers.map(({ passwordHash: _, ...u }) => u);
  }
}

export const authService = new AuthService();
