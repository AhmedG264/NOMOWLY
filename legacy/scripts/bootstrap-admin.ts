#!/usr/bin/env tsx
import dotenv from 'dotenv';
import { bootstrapInitialSuperAdmin } from '../src/server/modules/auth/admin-bootstrap.service.ts';

dotenv.config();

async function main() {
  console.log('===============================================================');
  console.log('🚀 NOMOWLY — INITIAL SUPER ADMINISTRATOR BOOTSTRAP UTILITY');
  console.log('===============================================================');

  const adminEmail = process.env.ADMIN_EMAIL || 'ahmedgamal264@outlook.com';
  console.log(`Target Administrator Email: ${adminEmail}`);
  console.log('Password Hashing Algorithm: Argon2id (OWASP recommended)');
  console.log('Database Engine: PostgreSQL (Prisma Client)');
  console.log('Checking database connection and credentials...\n');

  try {
    const result = await bootstrapInitialSuperAdmin();

    console.log('---------------------------------------------------------------');
    if (result.action === 'CREATED') {
      console.log('✅ STATUS: SUPER ADMINISTRATOR CREATED SUCCESSFULLY');
      console.log(`User ID: ${result.user?.id}`);
      console.log(`Email: ${result.user?.email}`);
      console.log(`Role: ${result.user?.role}`);
      console.log(`Must Change Password: ${result.user?.mustChangePassword ? 'YES (Required on first login)' : 'NO'}`);
      console.log(`Audit Record: Logged to Audit Trail`);
      console.log('---------------------------------------------------------------');
      console.log('Security Note: Administrator must change the temporary password on first sign-in.');
    } else if (result.action === 'ALREADY_EXISTS') {
      console.log('ℹ️ STATUS: ADMINISTRATOR ALREADY EXISTS (IDEMPOTENT)');
      console.log(`User ID: ${result.user?.id}`);
      console.log(`Email: ${result.user?.email}`);
      console.log(`Role: ${result.user?.role}`);
      console.log('No modifications were made to existing credentials.');
      console.log('---------------------------------------------------------------');
    }
    process.exit(0);
  } catch (err: any) {
    console.error('---------------------------------------------------------------');
    console.error('❌ BOOTSTRAP FAILED');
    console.error(`Error: ${err.message}`);
    console.error('---------------------------------------------------------------');
    console.error('Please verify:');
    console.error(' 1. DATABASE_URL is set and pointing to an accessible PostgreSQL database.');
    console.error(' 2. ADMIN_INITIAL_PASSWORD is provided and meets complexity requirements (min 12 chars).');
    console.error(' 3. Run migrations first: npx prisma migrate deploy');
    process.exit(1);
  }
}

main();
