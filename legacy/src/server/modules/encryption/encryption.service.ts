import crypto from 'crypto';

export interface EncryptedResult {
  ciphertext: string;
  iv: string;
  tag: string;
  keyVersion: string;
}

export class EncryptionService {
  private masterKey: Buffer;
  private keyVersion: string;
  private hmacFingerprintSecret: string;

  constructor() {
    // 32-byte hex or raw key
    const rawMasterKey = process.env.ENCRYPTION_MASTER_KEY || '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
    this.masterKey = Buffer.from(rawMasterKey, 'hex');
    if (this.masterKey.length !== 32) {
      // Fallback hash to ensure exactly 32 bytes
      this.masterKey = crypto.createHash('sha256').update(rawMasterKey).digest();
    }
    this.keyVersion = process.env.ENCRYPTION_KEY_VERSION || 'v1';
    this.hmacFingerprintSecret = process.env.HMAC_FINGERPRINT_SECRET || 'nomowly-hmac-fingerprint-salt-32-bytes-secure';
  }

  /**
   * Encrypt plaintext string using authenticated AES-256-GCM
   */
  encrypt(plaintext: string): EncryptedResult {
    if (!plaintext) {
      throw new Error('Encryption input cannot be empty');
    }
    const iv = crypto.randomBytes(12); // Standard 96-bit IV for GCM
    const cipher = crypto.createCipheriv('aes-256-gcm', this.masterKey, iv);
    
    let encrypted = cipher.update(plaintext, 'utf8', 'hex');
    encrypted += cipher.final('hex');
    const authTag = cipher.getAuthTag();

    return {
      ciphertext: encrypted,
      iv: iv.toString('hex'),
      tag: authTag.toString('hex'),
      keyVersion: this.keyVersion,
    };
  }

  /**
   * Decrypt AES-256-GCM ciphertext
   */
  decrypt(payload: { ciphertext: string; iv: string; tag: string }): string {
    if (!payload.ciphertext || !payload.iv || !payload.tag) {
      throw new Error('Missing ciphertext, iv, or tag for decryption');
    }

    const decipher = crypto.createDecipheriv(
      'aes-256-gcm',
      this.masterKey,
      Buffer.from(payload.iv, 'hex')
    );
    decipher.setAuthTag(Buffer.from(payload.tag, 'hex'));

    let decrypted = decipher.update(payload.ciphertext, 'hex', 'utf8');
    decrypted += decipher.final('utf8');
    return decrypted;
  }

  /**
   * Generate secure one-way keyed HMAC-SHA256 fingerprint for deduplication/indexing
   * Original value cannot be recovered from the fingerprint.
   */
  fingerprint(value: string): string {
    if (!value) return '';
    return crypto
      .createHmac('sha256', this.hmacFingerprintSecret)
      .update(value.trim().toLowerCase())
      .digest('hex');
  }

  /**
   * Constant-time timingSafeEqual HMAC verification for webhooks
   */
  verifyWebhookSignature(payloadBody: string, signatureHeader: string, secretKey: string): boolean {
    if (!payloadBody || !signatureHeader || !secretKey) {
      return false;
    }
    try {
      const expectedSignature = crypto
        .createHmac('sha256', secretKey)
        .update(payloadBody)
        .digest('hex');

      const expectedBuf = Buffer.from(expectedSignature, 'utf8');
      const receivedBuf = Buffer.from(signatureHeader.trim(), 'utf8');

      if (expectedBuf.length !== receivedBuf.length) {
        return false;
      }
      return crypto.timingSafeEqual(expectedBuf, receivedBuf);
    } catch {
      return false;
    }
  }

  /**
   * Mask sensitive string for UI display (e.g., SA44************1234)
   */
  maskString(value: string, prefixLen = 4, suffixLen = 4): string {
    if (!value || value.length <= prefixLen + suffixLen) {
      return '****';
    }
    const prefix = value.substring(0, prefixLen);
    const suffix = value.substring(value.length - suffixLen);
    const maskedLength = Math.max(4, value.length - prefixLen - suffixLen);
    return `${prefix}${'*'.repeat(maskedLength)}${suffix}`;
  }
}

export const encryptionService = new EncryptionService();
