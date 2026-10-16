import crypto from 'crypto';
import Decimal from 'decimal.js';
import { CHART_OF_ACCOUNTS, ERROR_CODES } from '../../../../packages/shared/src/constants.ts';
import { auditService } from '../audit/audit.service.ts';

export interface LedgerEntryRecord {
  id: string;
  transactionId: string;
  accountCode: string;
  debitAmount: Decimal;
  creditAmount: Decimal;
  createdAt: Date;
}

export interface LedgerTransactionRecord {
  id: string;
  type: string;
  description: string;
  currency: string;
  brandProfileId?: string | null;
  campaignId?: string | null;
  campaignReservationId?: string | null;
  conversionId?: string | null;
  commissionId?: string | null;
  referenceId?: string | null;
  idempotencyKey?: string | null;
  createdAt: Date;
  entries: LedgerEntryRecord[];
}

export interface PostEntryDto {
  accountCode: string;
  debitAmount: string | number | Decimal;
  creditAmount: string | number | Decimal;
}

export class LedgerService {
  private transactions: LedgerTransactionRecord[] = [];
  private entries: LedgerEntryRecord[] = [];
  private idempotencyStore = new Map<string, LedgerTransactionRecord>();

  constructor() {
    this.seedChartOfAccounts();
  }

  private seedChartOfAccounts() {
    // In-memory verification of chart of accounts integrity
    if (CHART_OF_ACCOUNTS.length !== 9) {
      throw new Error('Chart of accounts must contain exactly 9 verified accounts');
    }
  }

  /**
   * The Authoritative Posting Engine
   * Validates Double-Entry balance: SUM(Debits) == SUM(Credits)
   * Enforces Atomicity and Idempotency
   */
  postBalancedTransaction(params: {
    type: string;
    description: string;
    currency?: string;
    brandProfileId?: string | null;
    campaignId?: string | null;
    campaignReservationId?: string | null;
    conversionId?: string | null;
    commissionId?: string | null;
    referenceId?: string | null;
    idempotencyKey?: string;
    entries: PostEntryDto[];
  }): LedgerTransactionRecord {
    // 1. Idempotency Check
    if (params.idempotencyKey) {
      const existing = this.idempotencyStore.get(params.idempotencyKey);
      if (existing) {
        return existing;
      }
    }

    if (!params.entries || params.entries.length < 2) {
      throw new Error('A double-entry transaction must have at least 2 entries (one debit, one credit).');
    }

    // 2. Validate and Calculate Debits & Credits
    let totalDebit = new Decimal(0);
    let totalCredit = new Decimal(0);

    const validatedEntries: Array<{
      accountCode: string;
      debit: Decimal;
      credit: Decimal;
    }> = [];

    for (const entry of params.entries) {
      const accountExists = CHART_OF_ACCOUNTS.some(a => a.code === entry.accountCode);
      if (!accountExists) {
        throw new Error(`Invalid Ledger Account Code: ${entry.accountCode}`);
      }

      const debit = new Decimal(entry.debitAmount || 0);
      const credit = new Decimal(entry.creditAmount || 0);

      if (debit.lessThan(0) || credit.lessThan(0)) {
        throw new Error('Ledger amounts cannot be negative. Create an offsetting entry instead.');
      }
      if (debit.isZero() && credit.isZero()) {
        throw new Error(`Entry for account ${entry.accountCode} has zero value.`);
      }
      if (debit.greaterThan(0) && credit.greaterThan(0)) {
        throw new Error(`Entry for account ${entry.accountCode} cannot have both debit and credit amounts.`);
      }

      totalDebit = totalDebit.plus(debit);
      totalCredit = totalCredit.plus(credit);

      validatedEntries.push({
        accountCode: entry.accountCode,
        debit,
        credit,
      });
    }

    // 3. Strict Double-Entry Check
    if (!totalDebit.equals(totalCredit)) {
      throw new Error(
        `UNBALANCED_TRANSACTION: Total Debits (${totalDebit.toFixed(2)}) must equal Total Credits (${totalCredit.toFixed(2)}). Variance: ${totalDebit.minus(totalCredit).toFixed(2)}`
      );
    }

    // 4. Atomic Commit
    const transactionId = crypto.randomUUID();
    const now = new Date();

    const createdEntries: LedgerEntryRecord[] = validatedEntries.map(e => ({
      id: crypto.randomUUID(),
      transactionId,
      accountCode: e.accountCode,
      debitAmount: e.debit,
      creditAmount: e.credit,
      createdAt: now,
    }));

    const txRecord: LedgerTransactionRecord = {
      id: transactionId,
      type: params.type,
      description: params.description,
      currency: params.currency || 'SAR',
      brandProfileId: params.brandProfileId || null,
      campaignId: params.campaignId || null,
      campaignReservationId: params.campaignReservationId || null,
      conversionId: params.conversionId || null,
      commissionId: params.commissionId || null,
      referenceId: params.referenceId || null,
      idempotencyKey: params.idempotencyKey || null,
      createdAt: now,
      entries: createdEntries,
    };

    // Store append-only records
    this.transactions.push(txRecord);
    this.entries.push(...createdEntries);

    if (params.idempotencyKey) {
      this.idempotencyStore.set(params.idempotencyKey, txRecord);
    }

    auditService.recordEvent({
      action: 'LEDGER_TRANSACTION_POSTED',
      entityType: 'LedgerTransaction',
      entityId: txRecord.id,
      payloadDiff: {
        type: txRecord.type,
        amount: totalDebit.toFixed(2),
        entriesCount: createdEntries.length,
      },
    });

    return txRecord;
  }

  /**
   * Get dynamic balance of any ledger account code
   * ASSET: Debits - Credits
   * LIABILITY & REVENUE: Credits - Debits
   */
  getAccountBalance(accountCode: string, filter?: { brandProfileId?: string }): Decimal {
    const account = CHART_OF_ACCOUNTS.find(a => a.code === accountCode);
    if (!account) {
      throw new Error(`Unknown account code: ${accountCode}`);
    }

    let balance = new Decimal(0);

    for (const tx of this.transactions) {
      if (filter?.brandProfileId && tx.brandProfileId !== filter.brandProfileId) {
        continue;
      }
      for (const entry of tx.entries) {
        if (entry.accountCode === accountCode) {
          if (account.type === 'ASSET' || account.type === 'EXPENSE') {
            balance = balance.plus(entry.debitAmount).minus(entry.creditAmount);
          } else {
            // LIABILITY or REVENUE
            balance = balance.plus(entry.creditAmount).minus(entry.debitAmount);
          }
        }
      }
    }

    return balance;
  }

  getAllTransactions(filter?: {
    brandProfileId?: string;
    campaignId?: string;
    conversionId?: string;
  }): LedgerTransactionRecord[] {
    return this.transactions.filter(tx => {
      if (filter?.brandProfileId && tx.brandProfileId !== filter.brandProfileId) return false;
      if (filter?.campaignId && tx.campaignId !== filter.campaignId) return false;
      if (filter?.conversionId && tx.conversionId !== filter.conversionId) return false;
      return true;
    });
  }

  // Enforce Immutability (Forbidden methods)
  updateTransaction() {
    throw new Error('IMMUTABLE_RECORD: Modifying posted ledger transactions is strictly forbidden.');
  }

  deleteTransaction() {
    throw new Error('IMMUTABLE_RECORD: Deleting posted ledger transactions is strictly forbidden.');
  }
}

export const ledgerService = new LedgerService();
