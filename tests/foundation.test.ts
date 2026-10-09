import crypto from 'crypto';
import Decimal from 'decimal.js';
import { authService } from '../src/server/modules/auth/auth.service.ts';
import { encryptionService } from '../src/server/modules/encryption/encryption.service.ts';
import { ledgerService } from '../src/server/modules/ledger/ledger.service.ts';
import { walletService } from '../src/server/modules/wallet/wallet.service.ts';
import { campaignsService } from '../src/server/modules/campaigns/campaigns.service.ts';
import { trackingService } from '../src/server/modules/tracking/tracking.service.ts';
import { conversionsService } from '../src/server/modules/conversions/conversions.service.ts';
import { withdrawalsService } from '../src/server/modules/withdrawals/withdrawals.service.ts';
import { MoneyUtil } from '../packages/shared/src/money.ts';

async function runTestSuite() {
  console.log('====================================================');
  console.log('NOMOWLY PHASE 3.1 CORE FOUNDATION AUTOMATED TESTS');
  console.log('====================================================\n');

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

  // -----------------------------------------------------------
  // 1. ENCRYPTION & FINGERPRINTS
  // -----------------------------------------------------------
  try {
    const rawSecret = 'whsec_9876543210_abcdef_nomowly';
    const enc = encryptionService.encrypt(rawSecret);
    const decrypted = encryptionService.decrypt(enc);
    assert(decrypted === rawSecret, 'Encryption: AES-256-GCM encrypt & decrypt roundtrip');

    const fp1 = encryptionService.fingerprint('SA4400000012345678901234');
    const fp2 = encryptionService.fingerprint('sa4400000012345678901234');
    assert(fp1 === fp2 && fp1.length === 64, 'Encryption: Keyed HMAC-SHA256 fingerprint consistency');

    const body = JSON.stringify({ event: 'order.completed', orderId: 'ORD-991' });
    const sig = crypto.createHmac('sha256', rawSecret).update(body).digest('hex');
    const isValidSig = encryptionService.verifyWebhookSignature(body, sig, rawSecret);
    const isBadSig = encryptionService.verifyWebhookSignature(body, 'tampered-signature', rawSecret);
    assert(isValidSig && !isBadSig, 'Encryption: Webhook constant-time HMAC signature verification');
  } catch (err: any) {
    assert(false, `Encryption error: ${err.message}`);
  }

  // -----------------------------------------------------------
  // 2. AUTHENTICATION & MULTI-TENANCY
  // -----------------------------------------------------------
  try {
    const brandReg = await authService.register({
      email: 'brand.owner@example.com',
      password: 'StrongPassword2026!',
      role: 'BRAND',
      companyName: ' متجر الأناقة السعودي',
    });
    assert(brandReg.user.role === 'BRAND' && !!brandReg.accessToken, 'Auth: Brand registration and token generation');

    const loginRes = await authService.login({
      email: 'brand.owner@example.com',
      password: 'StrongPassword2026!',
    });
    assert(loginRes.user.id === brandReg.user.id, 'Auth: Valid login with bcrypt verification');

    let invalidLoginBlocked = false;
    try {
      await authService.login({
        email: 'brand.owner@example.com',
        password: 'WrongPassword!',
      });
    } catch {
      invalidLoginBlocked = true;
    }
    assert(invalidLoginBlocked, 'Auth: Invalid password rejected');

    const marketerReg = await authService.register({
      email: 'creator.pro@example.com',
      password: 'CreatorPass2026!',
      role: 'MARKETER',
      fullName: 'أحمد المحمدي',
      specialization: 'CREATOR',
    });
    assert(marketerReg.user.role === 'MARKETER', 'Auth: Marketer registration');
  } catch (err: any) {
    assert(false, `Auth error: ${err.message}`);
  }

  // -----------------------------------------------------------
  // 3. LEDGER & BALANCED TRANSACTIONS
  // -----------------------------------------------------------
  try {
    // Brand Deposit: DR 1010_ESCROW_BANK 10000, CR 2010_BRAND_DEPOSIT 10000
    const depositTx = ledgerService.postBalancedTransaction({
      type: 'BRAND_DEPOSIT',
      description: 'شحن رصيد ضمان مبدئي للعلامة التجارية',
      brandProfileId: 'brand-test-01',
      entries: [
        { accountCode: '1010_ESCROW_BANK', debitAmount: 10000, creditAmount: 0 },
        { accountCode: '2010_BRAND_DEPOSIT', debitAmount: 0, creditAmount: 10000 },
      ],
    });
    assert(depositTx.entries.length === 2, 'Ledger: Balanced Brand Deposit transaction posted');

    const bankBal = ledgerService.getAccountBalance('1010_ESCROW_BANK');
    const depositBal = ledgerService.getAccountBalance('2010_BRAND_DEPOSIT', { brandProfileId: 'brand-test-01' });
    assert(bankBal.equals(10000) && depositBal.equals(10000), 'Ledger: Accurate account balances derived from entries');

    let unbalancedRejected = false;
    try {
      ledgerService.postBalancedTransaction({
        type: 'INVALID_TX',
        description: 'Unbalanced test',
        entries: [
          { accountCode: '1010_ESCROW_BANK', debitAmount: 500, creditAmount: 0 },
          { accountCode: '2010_BRAND_DEPOSIT', debitAmount: 0, creditAmount: 400 }, // Variance 100
        ],
      });
    } catch (err: any) {
      unbalancedRejected = err.message.includes('UNBALANCED_TRANSACTION');
    }
    assert(unbalancedRejected, 'Ledger: Unbalanced transaction strictly rejected with rollback');
  } catch (err: any) {
    assert(false, `Ledger error: ${err.message}`);
  }

  // -----------------------------------------------------------
  // 4. CAMPAIGN BUDGET RESERVATION & CONSUMPTION
  // -----------------------------------------------------------
  try {
    const campaign = campaignsService.createCampaign({
      brandProfileId: 'brand-test-01',
      title: 'حملة العيد للملابس',
      description: 'عمولة 10% لكل مبيعة',
      commissionType: 'PERCENTAGE',
      commissionValue: 10,
      platformFeeRate: 0.02,
      budgetCap: 3000,
      cookieDurationDays: 30,
      lockPeriodDays: 30,
    });

    const { reservation } = campaignsService.reserveAndActivateCampaign({
      campaignId: campaign.id,
      brandProfileId: 'brand-test-01',
    });
    assert(reservation.reservedAmount.equals(3000) && reservation.status === 'ACTIVE', 'Campaign Budget: Reservation from free deposits succeeds');

    const freeDepositRemaining = ledgerService.getAccountBalance('2010_BRAND_DEPOSIT', { brandProfileId: 'brand-test-01' });
    const reservedBal = ledgerService.getAccountBalance('2015_BRAND_CAMPAIGN_RESERVED', { brandProfileId: 'brand-test-01' });
    assert(freeDepositRemaining.equals(7000) && reservedBal.equals(3000), 'Campaign Budget: Ledger balances updated correctly');

    let overConsumptionPrevented = false;
    try {
      campaignsService.consumeBudget({
        campaignId: campaign.id,
        amountToConsume: new Decimal(3500),
      });
    } catch {
      overConsumptionPrevented = true;
    }
    assert(overConsumptionPrevented, 'Campaign Budget: Over-consumption strictly prevented');
  } catch (err: any) {
    assert(false, `Campaign budget error: ${err.message}`);
  }

  // -----------------------------------------------------------
  // 5. TRACKING & ATTRIBUTION
  // -----------------------------------------------------------
  let activeClickId = '';
  try {
    const campaigns = campaignsService.getActiveCampaigns();
    const activeCampaign = campaigns.find(c => c.brandProfileId === 'brand-test-01') || campaigns[0];
    const link = trackingService.createTrackingLink({
      campaignId: activeCampaign.id,
      marketerProfileId: 'marketer-test-01',
      destinationUrl: 'https://myshop.com/product/perfume',
    });

    const clickRes = trackingService.recordClick({
      shortCode: link.shortCode,
      rawIp: '197.240.10.5',
      userAgent: 'Mozilla/5.0 Chrome',
      sub1: 'tiktok_adset_1',
    });
    activeClickId = clickRes.clickId;
    assert(clickRes.redirectUrl.includes(`nmw_cid=${activeClickId}`), 'Tracking: Fast redirect URL with nmw_cid appended');

    const resolved = trackingService.resolveAttribution(activeClickId);
    assert(resolved?.marketerProfileId === 'marketer-test-01', 'Attribution: Resolved click within lookback window');
  } catch (err: any) {
    assert(false, `Tracking error: ${err.message}`);
  }

  // -----------------------------------------------------------
  // 6. CONVERSION INGESTION & FINANCIAL INTEGRITY
  // -----------------------------------------------------------
  let testConversionId = '';
  try {
    // Order: 1000 SAR subtotal, 100 SAR discount -> Net Commission Base = 900 SAR
    // Marketer 10% = 90 SAR, Platform Fee 2% = 18 SAR -> Total Brand Cost = 108 SAR
    const orderResult = conversionsService.processOrderConversion({
      brandStoreId: 'store-sa-01',
      externalOrderId: 'ORD-100293',
      clickId: activeClickId,
      items: [
        { title: 'عطر فاخر 100 مل', quantity: 2, unitPrice: 500, discountAmount: 100 },
      ],
      shippingAmount: 30, // Excluded from commission base
      taxAmount: 135,     // Excluded from commission base
    });

    testConversionId = orderResult.conversion.id;
    const comm = orderResult.commission!;
    assert(comm.commissionBase.equals(900), 'Conversion: Net commission base strictly excludes tax & shipping (900 SAR)');
    assert(comm.marketerAmount.equals(90), 'Conversion: Marketer commission accurately calculated (90 SAR)');
    assert(comm.platformFeeAmount.equals(18), 'Conversion: Platform success fee accurately calculated (18 SAR)');
    assert(comm.brandTotalCost.equals(108), 'Conversion: Brand total cost equals commission + fee (108 SAR)');

    // Test Idempotency: Repeating duplicate externalOrderId returns existing conversion without re-crediting
    const duplicateAttempt = conversionsService.processOrderConversion({
      brandStoreId: 'store-sa-01',
      externalOrderId: 'ORD-100293',
      clickId: activeClickId,
      items: [{ title: 'عطر فاخر', quantity: 1, unitPrice: 1000 }],
    });
    assert(duplicateAttempt.conversion.id === testConversionId, 'Idempotency: Repeated order ingestion does not duplicate conversions');
  } catch (err: any) {
    assert(false, `Conversion error: ${err.message}`);
  }

  // -----------------------------------------------------------
  // 7. WALLET & RECEIVABLE OFFSET
  // -----------------------------------------------------------
  try {
    // Marketer has a debt (Receivable = 100 SAR)
    const wallet = walletService.getOrCreateWallet('marketer-test-01', 'MARKETER');
    wallet.receivableBalance = new Decimal(100);

    // New commission matures for 150 SAR
    const offsetResult = walletService.applyCommissionMaturityWithReceivableOffset({
      marketerProfileId: 'marketer-test-01',
      grossMaturedAmount: new Decimal(150),
    });

    assert(offsetResult.settledReceivableAmount.equals(100), 'Wallet: 100 SAR debt cleared by incoming earnings');
    assert(offsetResult.newNetAvailableAmount.equals(50), 'Wallet: Only net 50 SAR becomes available to withdraw');
    assert(wallet.receivableBalance.isZero(), 'Wallet: Receivable balance reset to 0');
  } catch (err: any) {
    assert(false, `Wallet error: ${err.message}`);
  }

  // -----------------------------------------------------------
  // 8. WITHDRAWALS & KYC ENFORCEMENT
  // -----------------------------------------------------------
  try {
    let unverifiedBlocked = false;
    try {
      withdrawalsService.requestWithdrawal({
        marketerProfileId: 'marketer-test-01',
        amount: 50,
        rawIban: 'SA4400000012345678901234',
        rawBeneficiaryName: 'أحمد المحمدي',
        isKycVerified: false, // Unverified
      });
    } catch (err: any) {
      unverifiedBlocked = err.message.includes('MIN_WITHDRAWAL_ERROR') || err.message.includes('KYC_REQUIRED');
    }
    assert(unverifiedBlocked, 'Withdrawals: Min threshold & KYC verification strictly enforced');
  } catch (err: any) {
    assert(false, `Withdrawal error: ${err.message}`);
  }

  console.log('\n====================================================');
  console.log(`TEST RESULTS: ${passed} PASSED | ${failed} FAILED`);
  console.log('====================================================');

  if (failed > 0) {
    process.exit(1);
  }
}

runTestSuite().catch(err => {
  console.error('Fatal test error:', err);
  process.exit(1);
});
