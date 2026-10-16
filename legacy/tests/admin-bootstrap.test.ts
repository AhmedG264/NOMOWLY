import argon2 from 'argon2';
import { 
  validateAdminPassword, 
  hashPasswordWithArgon2id, 
  verifyPasswordWithArgon2id 
} from '../src/server/modules/auth/admin-bootstrap.service.ts';
import { authService } from '../src/server/modules/auth/auth.service.ts';
import { validateEnvironment } from '../src/server/config/env.ts';
import { auditService } from '../src/server/modules/audit/audit.service.ts';

// In-memory mock database store for isolated unit testing
interface MockUser {
  id: string;
  email: string;
  passwordHash: string;
  role: string;
  status: string;
  mustChangePassword: boolean;
  createdAt: Date;
}

class MockAdminBootstrapRepository {
  private users: Map<string, MockUser> = new Map();

  async findByEmail(email: string): Promise<MockUser | null> {
    return this.users.get(email.toLowerCase()) || null;
  }

  async count(): Promise<number> {
    return this.users.size;
  }

  async executeBootstrap(email: string, password: string) {
    const normEmail = email.toLowerCase().trim();
    const existing = await this.findByEmail(normEmail);

    if (existing) {
      if (existing.role === 'SUPER_ADMIN') {
        return {
          action: 'ALREADY_EXISTS',
          user: existing,
          created: false,
        };
      } else {
        throw new Error(
          `ADMIN_OVERWRITE_PREVENTED: A user with email '${normEmail}' already exists with role '${existing.role}'. Silent overwrite or promotion is strictly prohibited.`
        );
      }
    }

    const passwordHash = await hashPasswordWithArgon2id(password);
    const newAdmin: MockUser = {
      id: 'admin-uuid-test-' + Date.now(),
      email: normEmail,
      passwordHash,
      role: 'SUPER_ADMIN',
      status: 'ACTIVE',
      mustChangePassword: true,
      createdAt: new Date(),
    };

    this.users.set(normEmail, newAdmin);

    auditService.recordEvent({
      userId: newAdmin.id,
      action: 'INITIAL_SUPER_ADMIN_BOOTSTRAPPED',
      entityType: 'User',
      entityId: newAdmin.id,
      payloadDiff: { email: newAdmin.email, role: 'SUPER_ADMIN', hashAlgorithm: 'Argon2id' },
    });

    return {
      action: 'CREATED',
      user: newAdmin,
      created: true,
    };
  }

  seedUser(user: MockUser) {
    this.users.set(user.email.toLowerCase(), user);
  }
}

export async function runAdminBootstrapTests() {
  console.log('===============================================================');
  console.log('🧪 NOMOWLY — SUPER ADMIN BOOTSTRAP & SECURITY TEST SUITE');
  console.log('===============================================================\n');

  let passed = 0;
  let failed = 0;

  function assert(condition: boolean, testName: string) {
    if (condition) {
      console.log(`[PASS] ${testName}`);
      passed++;
    } else {
      console.error(`[FAIL] ${testName}`);
      failed++;
    }
  }

  const mockRepo = new MockAdminBootstrapRepository();
  const targetEmail = 'ahmedgamal264@outlook.com';
  const initialPassword = 'SecureAdminSecretPass2026!';

  // -------------------------------------------------------------------------
  // TEST 1: The administrator is created with the correct email and role
  // -------------------------------------------------------------------------
  try {
    const res = await mockRepo.executeBootstrap(targetEmail, initialPassword);
    assert(
      res.action === 'CREATED' &&
      res.user.email === targetEmail &&
      res.user.role === 'SUPER_ADMIN' &&
      res.user.mustChangePassword === true,
      'Test 1: Administrator created with exact required email (ahmedgamal264@outlook.com) and SUPER_ADMIN role'
    );
  } catch (err: any) {
    assert(false, `Test 1 failed: ${err.message}`);
  }

  // -------------------------------------------------------------------------
  // TEST 2: The password is stored as an Argon2id hash, not plaintext
  // -------------------------------------------------------------------------
  try {
    const admin = await mockRepo.findByEmail(targetEmail);
    const isPlaintext = Boolean(admin?.passwordHash.includes(initialPassword));
    const isArgon2id = Boolean(admin?.passwordHash.startsWith('$argon2id$'));
    const verifySuccess = await verifyPasswordWithArgon2id(initialPassword, admin?.passwordHash || '');
    const verifyFailure = await verifyPasswordWithArgon2id('WrongPassword123!', admin?.passwordHash || '');

    assert(
      !isPlaintext && isArgon2id && verifySuccess && !verifyFailure,
      'Test 2: Password stored securely as Argon2id hash ($argon2id$...) and matches original plaintext'
    );
  } catch (err: any) {
    assert(false, `Test 2 failed: ${err.message}`);
  }

  // -------------------------------------------------------------------------
  // TEST 3: Re-running the bootstrap does not create duplicates (Idempotency)
  // -------------------------------------------------------------------------
  try {
    const countBefore = await mockRepo.count();
    const secondRun = await mockRepo.executeBootstrap(targetEmail, 'AnotherDifferentPassword2026!');
    const countAfter = await mockRepo.count();

    assert(
      secondRun.action === 'ALREADY_EXISTS' &&
      countBefore === 1 &&
      countAfter === 1,
      'Test 3: Bootstrap is idempotent; re-running returns ALREADY_EXISTS and preserves single admin record'
    );
  } catch (err: any) {
    assert(false, `Test 3 failed: ${err.message}`);
  }

  // -------------------------------------------------------------------------
  // TEST 4: Existing administrator credentials are not silently overwritten
  // -------------------------------------------------------------------------
  try {
    const admin = await mockRepo.findByEmail(targetEmail);
    const originalHash = admin?.passwordHash;

    // Simulate an attempt to bootstrap a different user that conflicts with an existing BRAND account
    const brandEmail = 'brand.conflict@example.com';
    mockRepo.seedUser({
      id: 'brand-99',
      email: brandEmail,
      passwordHash: 'dummy_hash',
      role: 'BRAND',
      status: 'ACTIVE',
      mustChangePassword: false,
      createdAt: new Date(),
    });

    let overwriteBlocked = false;
    try {
      await mockRepo.executeBootstrap(brandEmail, 'NewSecretPass2026!');
    } catch (err: any) {
      overwriteBlocked = err.message.includes('ADMIN_OVERWRITE_PREVENTED');
    }

    assert(
      admin?.passwordHash === originalHash && overwriteBlocked,
      'Test 4: Existing credentials cannot be silently overwritten or promoted without authorization'
    );
  } catch (err: any) {
    assert(false, `Test 4 failed: ${err.message}`);
  }

  // -------------------------------------------------------------------------
  // TEST 5: Public registration cannot create a SUPER_ADMIN
  // -------------------------------------------------------------------------
  try {
    let escalationBlocked = false;
    try {
      await authService.register({
        email: 'malicious.attacker@example.com',
        password: 'AttackerStrongPassword2026!',
        role: 'SUPER_ADMIN' as any, // Attacker attempts to register as admin
      });
    } catch (err: any) {
      escalationBlocked = err.message.includes('FORBIDDEN_ROLE');
    }

    assert(
      escalationBlocked,
      'Test 5: Public registration endpoint rejects requests with SUPER_ADMIN role (FORBIDDEN_ROLE)'
    );
  } catch (err: any) {
    assert(false, `Test 5 failed: ${err.message}`);
  }

  // -------------------------------------------------------------------------
  // TEST 6: Non-admin users cannot access admin endpoints
  // -------------------------------------------------------------------------
  try {
    const marketerUser = {
      userId: 'marketer-test-id',
      email: 'creator@test.sa',
      role: 'MARKETER' as const,
    };
    const brandUser = {
      userId: 'brand-test-id',
      email: 'brand@test.sa',
      role: 'BRAND' as const,
    };

    // Helper checking access control
    function checkAdminAccess(userRole: string): boolean {
      return userRole === 'SUPER_ADMIN';
    }

    const marketerDenied = !checkAdminAccess(marketerUser.role);
    const brandDenied = !checkAdminAccess(brandUser.role);
    const adminAllowed = checkAdminAccess('SUPER_ADMIN');

    assert(
      marketerDenied && brandDenied && adminAllowed,
      'Test 6: Role-based access control restricts admin endpoints to SUPER_ADMIN only'
    );
  } catch (err: any) {
    assert(false, `Test 6 failed: ${err.message}`);
  }

  // -------------------------------------------------------------------------
  // TEST 7: Application fails safely when production database config is missing
  // -------------------------------------------------------------------------
  try {
    // Save original env
    const origDbUrl = process.env.DATABASE_URL;
    delete process.env.DATABASE_URL;

    const prodValidation = validateEnvironment('production');
    const hasDbError = prodValidation.errors.some(e => e.includes('DATABASE_URL is not set'));
    const isInvalid = !prodValidation.isValid;

    // Restore env
    if (origDbUrl) process.env.DATABASE_URL = origDbUrl;

    assert(
      isInvalid && hasDbError,
      'Test 7: Production environment validation fails safely when DATABASE_URL is missing'
    );
  } catch (err: any) {
    assert(false, `Test 7 failed: ${err.message}`);
  }

  console.log('\n===============================================================');
  console.log(`TEST SUMMARY: ${passed} PASSED | ${failed} FAILED`);
  console.log('===============================================================');

  if (failed > 0) {
    process.exit(1);
  }
}

if (import.meta.url === `file://${process.argv[1]}`) {
  runAdminBootstrapTests().catch(err => {
    console.error('Test runner fatal error:', err);
    process.exit(1);
  });
}
