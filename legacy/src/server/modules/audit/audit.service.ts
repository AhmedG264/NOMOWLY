import crypto from 'crypto';

export interface AuditLogEntry {
  id: string;
  userId?: string | null;
  action: string;
  entityType: string;
  entityId: string;
  payloadDiff?: Record<string, any> | null;
  ipHash?: string | null;
  createdAt: Date;
}

export class AuditService {
  private inMemoryLogs: AuditLogEntry[] = [];
  private ipSalt: string;

  constructor() {
    this.ipSalt = process.env.AUDIT_IP_SALT || 'nomowly-ip-privacy-salt-rotate-180d';
  }

  /**
   * Hash IP address with privacy salt to comply with GDPR/Saudi Data Privacy standards
   */
  hashIpAddress(ip?: string): string | null {
    if (!ip) return null;
    return crypto.createHmac('sha256', this.ipSalt).update(ip.trim()).digest('hex');
  }

  /**
   * Sanitize object so sensitive fields are never written to audit logs
   */
  sanitizePayload(payload?: Record<string, any>): Record<string, any> | null {
    if (!payload) return null;
    const sanitized = { ...payload };
    const sensitiveKeys = [
      'password',
      'passwordHash',
      'secret',
      'webhookSecret',
      'nationalId',
      'iban',
      'token',
      'accessToken',
      'refreshToken',
    ];

    for (const key of Object.keys(sanitized)) {
      if (sensitiveKeys.some(s => key.toLowerCase().includes(s.toLowerCase()))) {
        sanitized[key] = '[REDACTED_SENSITIVE]';
      }
    }
    return sanitized;
  }

  /**
   * Record security/financial event
   */
  recordEvent(params: {
    userId?: string | null;
    action: string;
    entityType: string;
    entityId: string;
    payloadDiff?: Record<string, any>;
    rawIp?: string;
  }): AuditLogEntry {
    const entry: AuditLogEntry = {
      id: crypto.randomUUID(),
      userId: params.userId || null,
      action: params.action,
      entityType: params.entityType,
      entityId: params.entityId,
      payloadDiff: this.sanitizePayload(params.payloadDiff),
      ipHash: this.hashIpAddress(params.rawIp),
      createdAt: new Date(),
    };

    this.inMemoryLogs.unshift(entry);
    // Keep max 2000 in memory buffer for quick inspection
    if (this.inMemoryLogs.length > 2000) {
      this.inMemoryLogs.pop();
    }
    return entry;
  }

  getLogs(entityType?: string, entityId?: string): AuditLogEntry[] {
    return this.inMemoryLogs.filter(log => {
      if (entityType && log.entityType !== entityType) return false;
      if (entityId && log.entityId !== entityId) return false;
      return true;
    });
  }
}

export const auditService = new AuditService();
