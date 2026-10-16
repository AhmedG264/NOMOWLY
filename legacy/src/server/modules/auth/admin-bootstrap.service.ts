import argon2 from 'argon2';
import crypto from 'crypto';
import { prisma, checkDatabaseConnection } from '../../db/prisma.ts';
import { auditService } from '../audit/audit.service.ts';

export interface BootstrapAdminParams {
  email?: string;
  password?: string;
}

export interface BootstrapAdminResult {
  success: boolean;
  action: 'CREATED' | 'ALREADY_EXISTS' | 'FAILED';
  user?: {
    id: string;
    email: string;
    role: string;
    mustChangePassword: boolean;
    createdAt?: Date;
  };
  message: string;
  hashAlgorithm?: string;
}

/**
 * Validates password strength for administrative accounts.
 * Must be at least 12 characters with uppercase, lowercase, numbers, and symbols.
 */
export function validateAdminPassword(password: string): { isValid: boolean; error?: string } {
  if (!password || typeof password !== 'string') {
    return { isValid: false, error: 'Password is required.' };
  }
  if (password.length < 12) {
    return { isValid: false, error: 'Admin password must be at least 12 characters long.' };
  }
  if (password.includes('replace-with') || password === 'ADMIN_INITIAL_PASSWORD') {
    return { isValid: false, error: 'Cannot use default placeholder as initial admin password.' };
  }
  const hasUpper = /[A-Z]/.test(password);
  const hasLower = /[a-z]/.test(password);
  const hasDigit = /[0-9]/.test(password);
  const hasSpecial = /[^A-Za-z0-9]/.test(password);

  if (!hasUpper || !hasLower || !hasDigit || !hasSpecial) {
    return {
      isValid: false,
      error: 'Admin password must contain uppercase, lowercase, numbers, and special characters.',
    };
  }
  return { isValid: true };
}

/**
 * Hashes password using Argon2id algorithm according to OWASP guidelines.
 */
export async function hashPasswordWithArgon2id(password: string): Promise<string> {
  return argon2.hash(password, {
    type: argon2.argon2id,
    memoryCost: 65536, // 64 MB
    timeCost: 3,       // 3 iterations
    parallelism: 4,    // 4 threads
  });
}

/**
 * Verifies password against Argon2id hash.
 */
export async function verifyPasswordWithArgon2id(password: string, hash: string): Promise<boolean> {
  try {
    return await argon2.verify(hash, password);
  } catch {
    return false;
  }
}

/**
 * Executes idempotent Initial Super Administrator Bootstrap into PostgreSQL.
 * NEVER prints or stores passwords in plaintext.
 */
export async function bootstrapInitialSuperAdmin(
  params?: BootstrapAdminParams
): Promise<BootstrapAdminResult> {
  const email = (params?.email || process.env.ADMIN_EMAIL || 'ahmedgamal264@outlook.com').trim().toLowerCase();
  const password = params?.password || process.env.ADMIN_INITIAL_PASSWORD;

  // 1. Email validation
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    throw new Error(`INVALID_ADMIN_EMAIL: Email '${email}' is not a valid email address.`);
  }

  // 2. Password validation
  if (!password) {
    throw new Error(
      'MISSING_ADMIN_INITIAL_PASSWORD: Provide ADMIN_INITIAL_PASSWORD environment variable or argument.'
    );
  }
  const passwordCheck = validateAdminPassword(password);
  if (!passwordCheck.isValid) {
    throw new Error(`WEAK_ADMIN_PASSWORD: ${passwordCheck.error}`);
  }

  // 3. Database connection check
  const dbCheck = await checkDatabaseConnection();
  if (!dbCheck.connected) {
    throw new Error(`DATABASE_UNAVAILABLE: ${dbCheck.error}`);
  }

  // 4. Check existing user in PostgreSQL
  const existingUser = await prisma.user.findUnique({
    where: { email },
  });

  if (existingUser) {
    if (existingUser.role === 'SUPER_ADMIN') {
      return {
        success: true,
        action: 'ALREADY_EXISTS',
        user: {
          id: existingUser.id,
          email: existingUser.email,
          role: existingUser.role,
          mustChangePassword: existingUser.mustChangePassword,
          createdAt: existingUser.createdAt,
        },
        message: `Admin '${email}' already exists in the database. Idempotency enforced, credentials untouched.`,
        hashAlgorithm: 'Argon2id',
      };
    } else {
      // Security rule: Never silently overwrite or promote an existing account
      throw new Error(
        `ADMIN_OVERWRITE_PREVENTED: A user with email '${email}' already exists with role '${existingUser.role}'. Silent overwrite or promotion is strictly prohibited.`
      );
    }
  }

  // 5. Hash password with Argon2id
  const passwordHash = await hashPasswordWithArgon2id(password);

  // 6. Create administrator in PostgreSQL
  const newAdmin = await prisma.user.create({
    data: {
      email,
      passwordHash,
      role: 'SUPER_ADMIN',
      status: 'ACTIVE',
      mustChangePassword: true,
    },
  });

  // 7. Record in audit log
  auditService.recordEvent({
    userId: newAdmin.id,
    action: 'INITIAL_SUPER_ADMIN_BOOTSTRAPPED',
    entityType: 'User',
    entityId: newAdmin.id,
    payloadDiff: {
      email: newAdmin.email,
      role: newAdmin.role,
      mustChangePassword: true,
      hashAlgorithm: 'Argon2id',
    },
  });

  return {
    success: true,
    action: 'CREATED',
    user: {
      id: newAdmin.id,
      email: newAdmin.email,
      role: newAdmin.role,
      mustChangePassword: true,
      createdAt: newAdmin.createdAt,
    },
    message: `Initial super administrator '${email}' created successfully in PostgreSQL. Password change required on first login.`,
    hashAlgorithm: 'Argon2id',
  };
}
