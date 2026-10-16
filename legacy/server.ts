import express, { Request, Response, NextFunction } from 'express';
import crypto from 'crypto';
import path from 'path';
import { fileURLToPath } from 'url';
import dotenv from 'dotenv';
import { apiRouter } from './src/server/routes/api.routes.ts';
import { trackingService } from './src/server/modules/tracking/tracking.service.ts';
import { auditService } from './src/server/modules/audit/audit.service.ts';
import { validateEnvironment } from './src/server/config/env.ts';
import { checkDatabaseConnection } from './src/server/db/prisma.ts';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Validate runtime environment variables
const envCheck = validateEnvironment();
if (process.env.NODE_ENV === 'production' && !envCheck.isValid) {
  console.error('FATAL: Production environment validation failed:');
  for (const err of envCheck.errors) {
    console.error(`  - ${err}`);
  }
  process.exit(1);
}

const app = express();
const PORT = Number(process.env.PORT) || 3000;

// Correlation ID & Security Headers
app.use((req: Request, res: Response, next: NextFunction) => {
  const correlationId = (req.headers['x-correlation-id'] as string) || crypto.randomUUID();
  res.setHeader('X-Correlation-Id', correlationId);
  (req as any).correlationId = correlationId;
  next();
});

app.use(express.json());

// Request logging with IP privacy
app.use((req: Request, res: Response, next: NextFunction) => {
  const start = Date.now();
  res.on('finish', () => {
    const duration = Date.now() - start;
    if (req.path.startsWith('/api') || req.path.startsWith('/c/')) {
      const ipHash = auditService.hashIpAddress(req.ip);
      console.log(`[${new Date().toISOString()}] ${req.method} ${req.path} ${res.statusCode} - ${duration}ms [ipHash: ${ipHash?.substring(0, 8)}...]`);
    }
  });
  next();
});

// High-Throughput Click Redirect Route: /c/:shortCode
app.get('/c/:shortCode', (req: Request, res: Response) => {
  const { shortCode } = req.params;
  try {
    const result = trackingService.recordClick({
      shortCode,
      rawIp: req.ip,
      userAgent: req.headers['user-agent'],
      sub1: req.query.sub1 as string,
      sub2: req.query.sub2 as string,
      sub3: req.query.sub3 as string,
    });

    if (result.clickId) {
      // Set first-party tracking cookie with lookback duration
      res.cookie('nmw_click_id', result.clickId, {
        maxAge: 30 * 86400 * 1000,
        sameSite: 'lax',
        secure: process.env.NODE_ENV === 'production',
      });
    }

    res.redirect(302, result.redirectUrl);
  } catch (err: any) {
    res.status(404).send(`<h3>خطأ في رابط التتبع: ${err.message}</h3>`);
  }
});

// Mount Authoritative API Gateway
app.use('/api/v1', apiRouter);

// Health check endpoint with real database status
app.get('/api/health', async (req: Request, res: Response) => {
  const dbStatus = await checkDatabaseConnection();
  res.json({
    status: 'healthy',
    platform: 'Nomowly Performance Marketing Marketplace',
    version: '1.0.0',
    currency: 'SAR',
    database: {
      engine: 'PostgreSQL',
      connected: dbStatus.connected,
      error: dbStatus.error,
    },
    timestamp: new Date().toISOString(),
  });
});

async function startServer() {
  // Check and report database connection status securely at startup
  const dbStatus = await checkDatabaseConnection();
  if (dbStatus.connected) {
    console.log('✅ PostgreSQL Database connected successfully.');
  } else {
    console.warn(`⚠️ PostgreSQL Database status: ${dbStatus.error}`);
  }

  if (process.env.NODE_ENV !== 'production') {
    // In dev mode, attach Vite dev server middleware
    const { createServer: createViteServer } = await import('vite');
    const vite = await createViteServer({
      server: { middlewareMode: true },
      appType: 'spa',
    });
    app.use(vite.middlewares);
  } else {
    // In production, serve static files from dist
    app.use(express.static(path.resolve(__dirname, 'dist')));
    app.get('*', (req: Request, res: Response) => {
      res.sendFile(path.resolve(__dirname, 'dist', 'index.html'));
    });
  }

  app.listen(PORT, '0.0.0.0', () => {
    console.log(`====================================================`);
    console.log(`🚀 NOMOWLY CORE BACKEND & WEB RUNNING ON PORT ${PORT}`);
    console.log(`🔗 API Base URL: http://0.0.0.0:${PORT}/api/v1`);
    console.log(`📖 API Documentation: http://0.0.0.0:${PORT}/api/v1/docs`);
    console.log(`====================================================`);
  });
}

startServer().catch(err => {
  console.error('Failed to start server:', err);
  process.exit(1);
});
