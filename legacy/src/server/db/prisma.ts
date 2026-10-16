import { PrismaClient } from '@prisma/client';

// Global singleton pattern for PrismaClient
const globalForPrisma = globalThis as unknown as {
  prisma: PrismaClient | undefined;
};

export const prisma =
  globalForPrisma.prisma ??
  new PrismaClient({
    log: process.env.NODE_ENV === 'development' ? ['warn', 'error'] : ['error'],
  });

if (process.env.NODE_ENV !== 'production') {
  globalForPrisma.prisma = prisma;
}

let lastCheckTime = 0;
let lastKnownState: { connected: boolean; error?: string } = { connected: false };

/**
 * Checks connection to the real PostgreSQL database.
 * Returns clear, sanitized error messages without exposing credentials.
 */
export async function checkDatabaseConnection(forceRefresh = false): Promise<{ connected: boolean; error?: string }> {
  const dbUrl = process.env.DATABASE_URL;
  if (!dbUrl) {
    return {
      connected: false,
      error: 'DATABASE_URL_NOT_CONFIGURED: No PostgreSQL connection string provided.',
    };
  }

  // Throttle database check to avoid flooding logs if down
  const now = Date.now();
  if (!forceRefresh && now - lastCheckTime < 5000 && lastKnownState.connected === false) {
    return lastKnownState;
  }

  try {
    // Execute a simple heartbeat query on PostgreSQL
    await prisma.$queryRaw`SELECT 1 as heartbeat`;
    lastKnownState = { connected: true };
    lastCheckTime = now;
    return lastKnownState;
  } catch (err: any) {
    // Sanitize any database connection string with password from error message
    const rawMsg = err.message || 'Unknown database error';
    const sanitizedMsg = rawMsg.replace(/:\/\/[^:]+:[^@]+@/, '://***:***@');
    lastKnownState = {
      connected: false,
      error: `DATABASE_CONNECTION_FAILED: ${sanitizedMsg}`,
    };
    lastCheckTime = now;
    return lastKnownState;
  }
}

export async function isDatabaseOnline(): Promise<boolean> {
  const status = await checkDatabaseConnection();
  return status.connected;
}
