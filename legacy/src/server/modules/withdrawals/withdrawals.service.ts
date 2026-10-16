import crypto from 'crypto';
import Decimal from 'decimal.js';
import { encryptionService } from '../encryption/encryption.service.ts';
import { ledgerService } from '../ledger/ledger.service.ts';
import { walletService } from '../wallet/wallet.service.ts';
import { auditService } from '../audit/audit.service.ts';
import { SYSTEM_DEFAULTS } from '../../../../packages/shared/src/constants.ts';

export interface WithdrawalRequestRecord {
  id: string;
  walletId: string;
  marketerProfileId: string;
  amount: Decimal;
  currency: string;
  status: 'REQUESTED' | 'UNDER_REVIEW' | 'APPROVED' | 'PROCESSING' | 'PAID' | 'FAILED' | 'REJECTED' | 'CANCELLED';
  maskedIban: string;
  beneficiarySnapshotEncrypted: string;
  beneficiarySnapshotIv: string;
  beneficiarySnapshotTag: string;
  ibanSnapshotEncrypted: string;
  ibanSnapshotIv: string;
  ibanSnapshotTag: string;
  payoutReference?: string;
  requestedAt: Date;
  processedAt?: Date;
}

export class WithdrawalsService {
  private requests: WithdrawalRequestRecord[] = [];

  requestWithdrawal(params: {
    marketerProfileId: string;
    amount: string | number | Decimal;
    currency?: string;
    rawIban: string;
    rawBeneficiaryName: string;
    isKycVerified: boolean;
  }): WithdrawalRequestRecord {
    const amount = new Decimal(params.amount);
    const currency = params.currency || 'SAR';
    const minAmountValue = SYSTEM_DEFAULTS.MIN_WITHDRAWAL_AMOUNTS[currency] || 150;
    const minAmount = new Decimal(minAmountValue);

    // 1. Min withdrawal threshold check
    if (amount.lessThan(minAmount)) {
      throw new Error(`MIN_WITHDRAWAL_ERROR: Minimum withdrawal amount is ${minAmount.toFixed(2)} ${currency}.`);
    }

    // 2. KYC Enforcement
    if (!params.isKycVerified) {
      throw new Error('KYC_REQUIRED: Identity verification (KYC) must be approved before requesting payouts.');
    }

    // 3. Balance Check
    const wallet = walletService.getOrCreateWallet(params.marketerProfileId, 'MARKETER');
    if (wallet.availableBalance.lessThan(amount)) {
      throw new Error(`INSUFFICIENT_AVAILABLE_FUNDS: Available balance (${wallet.availableBalance.toFixed(2)} SAR) is insufficient.`);
    }

    // 4. Encrypt sensitive payout snapshots
    const encIban = encryptionService.encrypt(params.rawIban);
    const encBeneficiary = encryptionService.encrypt(params.rawBeneficiaryName);
    const maskedIban = encryptionService.maskString(params.rawIban);

    // Lock available funds
    wallet.availableBalance = wallet.availableBalance.minus(amount);

    const request: WithdrawalRequestRecord = {
      id: crypto.randomUUID(),
      walletId: wallet.ownerId,
      marketerProfileId: params.marketerProfileId,
      amount,
      currency: 'SAR',
      status: 'REQUESTED',
      maskedIban,
      ibanSnapshotEncrypted: encIban.ciphertext,
      ibanSnapshotIv: encIban.iv,
      ibanSnapshotTag: encIban.tag,
      beneficiarySnapshotEncrypted: encBeneficiary.ciphertext,
      beneficiarySnapshotIv: encBeneficiary.iv,
      beneficiarySnapshotTag: encBeneficiary.tag,
      requestedAt: new Date(),
    };

    this.requests.push(request);

    auditService.recordEvent({
      action: 'WITHDRAWAL_REQUESTED',
      entityType: 'WithdrawalRequest',
      entityId: request.id,
      payloadDiff: { amount: amount.toFixed(2), maskedIban },
    });

    return request;
  }

  /**
   * Admin approves and settles withdrawal after bank transfer
   */
  settleWithdrawal(params: {
    requestId: string;
    payoutReference: string;
  }): WithdrawalRequestRecord {
    const req = this.requests.find(r => r.id === params.requestId);
    if (!req || req.status === 'PAID') {
      throw new Error('INVALID_WITHDRAWAL_STATE: Request not found or already paid.');
    }

    // Post Double-Entry Ledger Transaction:
    // DR 2030_MARKETER_PAYABLE, CR 1010_ESCROW_BANK
    ledgerService.postBalancedTransaction({
      type: 'WITHDRAWAL_SETTLED',
      description: `تنفيذ سحب أرباح للمسوق حوالة #${params.payoutReference}`,
      referenceId: req.id,
      entries: [
        {
          accountCode: '2030_MARKETER_PAYABLE',
          debitAmount: req.amount,
          creditAmount: 0,
        },
        {
          accountCode: '1010_ESCROW_BANK',
          debitAmount: 0,
          creditAmount: req.amount,
        },
      ],
    });

    const wallet = walletService.getOrCreateWallet(req.marketerProfileId, 'MARKETER');
    wallet.withdrawnTotal = wallet.withdrawnTotal.plus(req.amount);

    req.status = 'PAID';
    req.payoutReference = params.payoutReference;
    req.processedAt = new Date();

    auditService.recordEvent({
      action: 'WITHDRAWAL_SETTLED',
      entityType: 'WithdrawalRequest',
      entityId: req.id,
      payloadDiff: { amount: req.amount.toFixed(2), payoutReference: params.payoutReference },
    });

    return req;
  }

  /**
   * Handle Failed Bank Transfer Reversal
   */
  failWithdrawal(params: {
    requestId: string;
    failureReason: string;
  }): WithdrawalRequestRecord {
    const req = this.requests.find(r => r.id === params.requestId);
    if (!req || req.status === 'PAID' || req.status === 'FAILED') {
      throw new Error('INVALID_WITHDRAWAL_STATE: Cannot fail this request.');
    }

    // Restore available balance in wallet
    const wallet = walletService.getOrCreateWallet(req.marketerProfileId, 'MARKETER');
    wallet.availableBalance = wallet.availableBalance.plus(req.amount);

    req.status = 'FAILED';
    req.processedAt = new Date();

    auditService.recordEvent({
      action: 'WITHDRAWAL_FAILED_REVERSED',
      entityType: 'WithdrawalRequest',
      entityId: req.id,
      payloadDiff: { reason: params.failureReason },
    });

    return req;
  }

  getRequestsByMarketer(marketerProfileId: string): WithdrawalRequestRecord[] {
    return this.requests.filter(r => r.marketerProfileId === marketerProfileId);
  }

  getAllRequests(): WithdrawalRequestRecord[] {
    return this.requests;
  }
}

export const withdrawalsService = new WithdrawalsService();
