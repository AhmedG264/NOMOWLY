import bcrypt from 'bcryptjs';
import crypto from 'crypto';
import jwt from 'jsonwebtoken';
import { UserRoleType, UserSessionPayload } from '../../../../packages/shared/src/types.ts';
import { auditService } from '../audit/audit.service.ts';

export interface UserRecord {
  id: string;
  email: string;
  passwordHash: string;
  role: UserRoleType;
  status: 'ACTIVE' | 'SUSPENDED' | 'PENDING_VERIFICATION';
  brandProfileId?: string;
  marketerProfileId?: string;
  createdAt: Date;
}

export class AuthService {
  private users: UserRecord[] = [];
  private accessSecret: string;
  private refreshSecret: string;

  constructor() {
    this.accessSecret = process.env.JWT_ACCESS_SECRET || 'nomowly-jwt-access-secret-32-chars-long';
    this.refreshSecret = process.env.JWT_REFRESH_SECRET || 'nomowly-jwt-refresh-secret-32-chars-long';
    this.seedSuperAdmin();
  }

  private seedSuperAdmin() {
    const adminEmail = 'admin@nomowly.com';
    const exists = this.users.find(u => u.email === adminEmail);
    if (!exists) {
      const salt = bcrypt.genSaltSync(10);
      const hash = bcrypt.hashSync('Admin@Nomowly2026', salt);
      this.users.push({
        id: 'admin-seed-id-01',
        email: adminEmail,
        passwordHash: hash,
        role: 'SUPER_ADMIN',
        status: 'ACTIVE',
        createdAt: new Date(),
      });
    }
  }

  /**
   * Register a new Brand or Marketer User
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
    const existing = this.users.find(u => u.email === normalizedEmail);
    if (existing) {
      throw new Error('EMAIL_ALREADY_REGISTERED: This email is already registered.');
    }

    if (params.password.length < 8) {
      throw new Error('PASSWORD_TOO_SHORT: Password must be at least 8 characters.');
    }

    const salt = await bcrypt.genSalt(10);
    const passwordHash = await bcrypt.hash(params.password, salt);
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
      brandProfileId,
      marketerProfileId,
      createdAt: new Date(),
    };

    this.users.push(newUser);

    auditService.recordEvent({
      userId: newUser.id,
      action: 'USER_REGISTERED',
      entityType: 'User',
      entityId: newUser.id,
      payloadDiff: { role: newUser.role, email: newUser.email },
    });

    const tokens = this.generateTokens(newUser);
    const { passwordHash: _, ...safeUser } = newUser;

    return {
      user: safeUser,
      ...tokens,
    };
  }

  /**
   * Authenticate and return JWT tokens
   */
  async login(params: {
    email: string;
    password: string;
  }): Promise<{ user: Omit<UserRecord, 'passwordHash'>; accessToken: string; refreshToken: string }> {
    const normalizedEmail = params.email.trim().toLowerCase();
    const user = this.users.find(u => u.email === normalizedEmail);

    if (!user) {
      throw new Error('INVALID_CREDENTIALS: Email or password is incorrect.');
    }

    if (user.status === 'SUSPENDED') {
      throw new Error('ACCOUNT_SUSPENDED: Your account has been suspended by administration.');
    }

    const isValid = await bcrypt.compare(params.password, user.passwordHash);
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
    });

    const tokens = this.generateTokens(user);
    const { passwordHash: _, ...safeUser } = user;

    return {
      user: safeUser,
      ...tokens,
    };
  }

  /**
   * Verify Access Token
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

  getUserById(id: string): UserRecord | undefined {
    return this.users.find(u => u.id === id);
  }

  getAllUsers(): Omit<UserRecord, 'passwordHash'>[] {
    return this.users.map(({ passwordHash: _, ...u }) => u);
  }
}

export const authService = new AuthService();
