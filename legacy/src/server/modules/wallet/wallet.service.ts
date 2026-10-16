import Decimal from 'decimal.js';
import { ledgerService } from '../ledger/ledger.service.ts';

export interface WalletState {
  ownerId: string; // brandProfileId or marketerProfileId
  ownerType: 'BRAND' | 'MARKETER';
  pendingBalance: Decimal;
  availableBalance: Decimal;
  reservedBalance: Decimal;
  withdrawnTotal: Decimal;
  receivableBalance: Decimal;
  currency: string;
}

export class WalletService {
  private inMemoryWallets = new Map<string, WalletState>();

  getOrCreateWallet(ownerId: string, ownerType: 'BRAND' | 'MARKETER'): WalletState {
    let wallet = this.inMemoryWallets.get(ownerId);
    if (!wallet) {
      wallet = {
        ownerId,
        ownerType,
        pendingBalance: new Decimal(0),
        availableBalance: new Decimal(0),
        reservedBalance: new Decimal(0),
        withdrawnTotal: new Decimal(0),
        receivableBalance: new Decimal(0),
        currency: 'SAR',
      };
      this.inMemoryWallets.set(ownerId, wallet);
    }
    return wallet;
  }

  /**
   * Reconcile Brand Wallet with Ledger Source of Truth
   */
  reconcileBrandWallet(brandProfileId: string): WalletState {
    const wallet = this.getOrCreateWallet(brandProfileId, 'BRAND');
    
    // Ledger authoritative accounts for brand
    const freeDeposit = ledgerService.getAccountBalance('2010_BRAND_DEPOSIT', { brandProfileId });
    const reserved = ledgerService.getAccountBalance('2015_BRAND_CAMPAIGN_RESERVED', { brandProfileId });

    wallet.availableBalance = freeDeposit;
    wallet.reservedBalance = reserved;
    return wallet;
  }

  /**
   * Apply Receivable Offset when new commission matures for Marketer:
   * Rule: If receivable > 0, incoming earnings first settle the receivable.
   * Only the net amount becomes available to withdraw.
   */
  applyCommissionMaturityWithReceivableOffset(params: {
    marketerProfileId: string;
    grossMaturedAmount: Decimal;
  }): {
    settledReceivableAmount: Decimal;
    newNetAvailableAmount: Decimal;
    remainingReceivableBalance: Decimal;
  } {
    const wallet = this.getOrCreateWallet(params.marketerProfileId, 'MARKETER');
    const gross = new Decimal(params.grossMaturedAmount);

    let settledReceivableAmount = new Decimal(0);
    let newNetAvailableAmount = new Decimal(0);

    if (wallet.receivableBalance.greaterThan(0)) {
      if (gross.greaterThanOrEqualTo(wallet.receivableBalance)) {
        // Commission fully covers and clears the receivable
        settledReceivableAmount = wallet.receivableBalance;
        newNetAvailableAmount = gross.minus(wallet.receivableBalance);
        wallet.receivableBalance = new Decimal(0);
      } else {
        // Commission partially covers the receivable
        settledReceivableAmount = gross;
        wallet.receivableBalance = wallet.receivableBalance.minus(gross);
        newNetAvailableAmount = new Decimal(0);
      }
    } else {
      // No receivable exists
      newNetAvailableAmount = gross;
    }

    wallet.availableBalance = wallet.availableBalance.plus(newNetAvailableAmount);

    return {
      settledReceivableAmount,
      newNetAvailableAmount,
      remainingReceivableBalance: wallet.receivableBalance,
    };
  }

  /**
   * Apply Post-Payout Reversal to Marketer Wallet:
   * Increases receivableBalance. If availableBalance > 0, available is absorbed first.
   */
  recordPostPayoutChargeback(params: {
    marketerProfileId: string;
    chargebackAmount: Decimal;
  }): WalletState {
    const wallet = this.getOrCreateWallet(params.marketerProfileId, 'MARKETER');
    const amount = new Decimal(params.chargebackAmount);

    if (wallet.availableBalance.greaterThanOrEqualTo(amount)) {
      wallet.availableBalance = wallet.availableBalance.minus(amount);
    } else {
      const remainingDebt = amount.minus(wallet.availableBalance);
      wallet.availableBalance = new Decimal(0);
      wallet.receivableBalance = wallet.receivableBalance.plus(remainingDebt);
    }

    return wallet;
  }
}

export const walletService = new WalletService();
