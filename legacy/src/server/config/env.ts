import dotenv from 'dotenv';

dotenv.config();

export interface AppEnvConfig {
  NODE_ENV: 'development' | 'production' | 'test';
  PORT: number;
  DATABASE_URL: string;
  REDIS_URL: string;
  JWT_ACCESS_SECRET: string;
  JWT_REFRESH_SECRET: string;
  ENCRYPTION_MASTER_KEY: string;
  ENCRYPTION_KEY_VERSION: string;
  ADMIN_EMAIL: string;
  ADMIN_INITIAL_PASSWORD?: string;
  APP_URL: string;
  API_URL: string;
  DEFAULT_CURRENCY: string;
  MIN_WITHDRAWAL_AMOUNT: number;
}

/**
 * Validates production and runtime environment variables.
 * Never prints secret values to console or logs.
 */
export function validateEnvironment(targetEnv?: string): {
  isValid: boolean;
  errors: string[];
  config: AppEnvConfig;
} {
  const nodeEnv = (targetEnv || process.env.NODE_ENV || 'development') as 'development' | 'production' | 'test';
  const errors: string[] = [];

  const databaseUrl = process.env.DATABASE_URL || '';
  const redisUrl = process.env.REDIS_URL || 'redis://localhost:6379';
  const jwtAccess = process.env.JWT_ACCESS_SECRET || '';
  const jwtRefresh = process.env.JWT_REFRESH_SECRET || '';
  const encKey = process.env.ENCRYPTION_MASTER_KEY || '';
  const encKeyVer = process.env.ENCRYPTION_KEY_VERSION || 'v1';
  const adminEmail = process.env.ADMIN_EMAIL || 'ahmedgamal264@outlook.com';
  const adminPassword = process.env.ADMIN_INITIAL_PASSWORD;
  const appUrl = process.env.APP_URL || 'http://localhost:3000';
  const apiUrl = process.env.API_URL || 'http://localhost:3000/api/v1';

  // 1. Database URL validation
  if (!databaseUrl) {
    errors.push('DATABASE_URL is not set. A valid PostgreSQL connection string is required.');
  } else if (!databaseUrl.startsWith('postgresql://') && !databaseUrl.startsWith('postgres://')) {
    errors.push('DATABASE_URL must be a valid PostgreSQL connection URI (postgresql://...).');
  }

  // 2. Production strict security checks
  if (nodeEnv === 'production') {
    if (!jwtAccess || jwtAccess.length < 32 || jwtAccess.includes('replace-with')) {
      errors.push('JWT_ACCESS_SECRET must be set to a secure random string of at least 32 characters in production.');
    }
    if (!jwtRefresh || jwtRefresh.length < 32 || jwtRefresh.includes('replace-with')) {
      errors.push('JWT_REFRESH_SECRET must be set to a secure random string of at least 32 characters in production.');
    }
    if (!encKey || encKey.length !== 64 || /^[0]+$/.test(encKey)) {
      errors.push('ENCRYPTION_MASTER_KEY must be a 64-character hex string (32 bytes) in production.');
    }
    if (!appUrl.startsWith('https://')) {
      errors.push('APP_URL must use HTTPS protocol in production.');
    }
  }

  // 3. Admin email format
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(adminEmail)) {
    errors.push(`ADMIN_EMAIL is invalid: '${adminEmail}'.`);
  }

  const config: AppEnvConfig = {
    NODE_ENV: nodeEnv,
    PORT: Number(process.env.PORT) || 3000,
    DATABASE_URL: databaseUrl,
    REDIS_URL: redisUrl,
    JWT_ACCESS_SECRET: jwtAccess || 'nomowly-dev-access-secret-32-chars-long-minimum',
    JWT_REFRESH_SECRET: jwtRefresh || 'nomowly-dev-refresh-secret-32-chars-long-minimum',
    ENCRYPTION_MASTER_KEY: encKey || '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
    ENCRYPTION_KEY_VERSION: encKeyVer,
    ADMIN_EMAIL: adminEmail,
    ADMIN_INITIAL_PASSWORD: adminPassword,
    APP_URL: appUrl,
    API_URL: apiUrl,
    DEFAULT_CURRENCY: process.env.DEFAULT_CURRENCY || 'SAR',
    MIN_WITHDRAWAL_AMOUNT: Number(process.env.MIN_WITHDRAWAL_AMOUNT) || 150,
  };

  return {
    isValid: errors.length === 0,
    errors,
    config,
  };
}

/**
 * Asserts production configuration at application bootstrap.
 * Exits or throws securely without dumping secrets.
 */
export function assertProductionConfig(): AppEnvConfig {
  const result = validateEnvironment();
  if (result.config.NODE_ENV === 'production' && !result.isValid) {
    console.error('FATAL CONFIGURATION ERROR: Production environment is misconfigured:');
    for (const err of result.errors) {
      console.error(` - ${err}`);
    }
    throw new Error(`PRODUCTION_CONFIG_ERROR: ${result.errors.join('; ')}`);
  }
  return result.config;
}
