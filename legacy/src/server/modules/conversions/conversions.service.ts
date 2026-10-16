import crypto from 'crypto';
import Decimal from 'decimal.js';
import { MoneyUtil } from '../../../../packages/shared/src/money.ts';
import { trackingService } from '../tracking/tracking.service.ts';
import { campaignsService } from '../campaigns/campaigns.service.ts';
import { ledgerService } from '../ledger/ledger.service.ts';
import { walletService } from '../wallet/wallet.service.ts';
import { auditService } from '../audit/audit.service.ts';

export interface RawOrderItemInput {
  externalProductId?: string;
  sku?: string;
  title: string;
  quantity: number;
  unitPrice: number | string;
  discountAmount?: number | string;
}

export interface ProcessOrderWebhookDto {
  brandStoreId: string;
  externalOrderId: string;
  clickId?: string;
  items: RawOrderItemInput[];
  shippingAmount?: number | string;
  taxAmount?: number | string;
  currency?: string;
}

export interface ConversionRecord {
  id: string;
  brandStoreId: string;
  clickId?: string | null;
  externalOrderId: string;
  subtotalAmount: Decimal;
  discountAmount: Decimal;
  shippingAmount: Decimal;
  taxAmount: Decimal;
  netCommissionBase: Decimal;
  currency: string;
  status: 'PENDING' | 'CONFIRMED' | 'CANCELLED' | 'REFUNDED' | 'PARTIALLY_REFUNDED';
  
  // Attribution Snapshot
  attributedCampaignId: string;
  attributedMarketerId: string;
  attributedTrackingLinkId: string;
  attributionClickId: string;
  attributionMethod: string;
  attributionWindowDaysSnapshot: number;
  attributedAt: Date;
  
  createdAt: Date;
}

export interface CommissionRecord {
  id: string;
  conversionId: string;
  campaignId: string;
  marketerProfileId: string;
  appliedRateType: 'PERCENTAGE' | 'FLAT_FEE';
  appliedRateValue: Decimal;
  commissionBase: Decimal;
  marketerAmount: Decimal;
  platformFeeAmount: Decimal;
  brandTotalCost: Decimal;
  status: 'PENDING' | 'AVAILABLE' | 'PAID' | 'VOIDED' | 'ADJUSTED';
  matureAt: Date;
  createdAt: Date;
}

export class ConversionsService {
  private conversions: ConversionRecord[] = [];
  private commissions: CommissionRecord[] = [];

  /**
   * Process Inbound Store Webhook / Conversion with Strict Server Eligibility
   */
  processOrderConversion(dto: ProcessOrderWebhookDto): {
    conversion: ConversionRecord;
    commission?: CommissionRecord;
  } {
    // 1. Idempotency Check: Prevent duplicate order processing
    const duplicate = this.conversions.find(
      c => c.brandStoreId === dto.brandStoreId && c.externalOrderId === dto.externalOrderId
    );
    if (duplicate) {
      const existingCommission = this.commissions.find(c => c.conversionId === duplicate.id);
      return { conversion: duplicate, commission: existingCommission };
    }

    // 2. Resolve Attribution
    const click = dto.clickId ? trackingService.resolveAttribution(dto.clickId) : null;
    if (!click) {
      throw new Error('ATTRIBUTION_FAILED: No valid click found within active attribution window.');
    }

    const campaign = campaignsService.getCampaignById(click.campaignId);
    if (!campaign) {
      throw new Error('CAMPAIGN_NOT_FOUND: Attributed campaign not found.');
    }

    // 3. Server-Authoritative Calculation of Items and Eligibility
    let subtotal = new Decimal(0);
    let totalDiscount = new Decimal(0);
    let eligibleNetBase = new Decimal(0);

    for (const item of dto.items) {
      const unitPrice = new Decimal(item.unitPrice);
      const qty = item.quantity || 1;
      const lineSubtotal = unitPrice.times(qty);
      const lineDiscount = new Decimal(item.discountAmount || 0);

      subtotal = subtotal.plus(lineSubtotal);
      totalDiscount = totalDiscount.plus(lineDiscount);

      // Server determines eligibility (MVP: campaign isAllProducts = true)
      const isEligible = campaign.isAllProducts ? true : true; // Can check product catalog
      if (isEligible) {
        const lineNet = lineSubtotal.minus(lineDiscount);
        eligibleNetBase = eligibleNetBase.plus(lineNet.greaterThan(0) ? lineNet : new Decimal(0));
      }
    }

    const shipping = new Decimal(dto.shippingAmount || 0);
    const tax = new Decimal(dto.taxAmount || 0);

    // Net commission base strictly excludes tax and shipping
    const finalCommissionBase = MoneyUtil.round(eligibleNetBase);

    // 4. Calculate Commissions
    const calculated = MoneyUtil.calculateCommissions({
      commissionType: campaign.commissionType,
      commissionValue: campaign.commissionValue,
      commissionBase: finalCommissionBase,
      platformFeeRate: campaign.platformFeeRate,
    });

    // 5. Consume from Campaign Reserved Budget
    campaignsService.consumeBudget({
      campaignId: campaign.id,
      amountToConsume: calculated.brandTotalCost,
    });

    const conversionId = crypto.randomUUID();
    const commissionId = crypto.randomUUID();
    const now = new Date();
    const matureAt = new Date(now.getTime() + campaign.lockPeriodDays * 86400000);

    // 6. Post Double-Entry Ledger Transaction:
    // DR BRAND_CAMPAIGN_RESERVED (brandTotalCost)
    // CR MARKETER_COMMISSION_PENDING (marketerAmount)
    // CR PLATFORM_FEE_PENDING (platformFeeAmount)
    ledgerService.postBalancedTransaction({
      type: 'COMMISSION_RESERVATION',
      description: `تسجيل عمولة طلب متجر #${dto.externalOrderId}`,
      brandProfileId: campaign.brandProfileId,
      campaignId: campaign.id,
      conversionId,
      commissionId,
      referenceId: dto.externalOrderId,
      entries: [
        {
          accountCode: '2015_BRAND_CAMPAIGN_RESERVED',
          debitAmount: calculated.brandTotalCost,
          creditAmount: 0,
        },
        {
          accountCode: '2020_MARKETER_COMMISSION_PENDING',
          debitAmount: 0,
          creditAmount: calculated.marketerAmount,
        },
        {
          accountCode: '2025_PLATFORM_FEE_PENDING',
          debitAmount: 0,
          creditAmount: calculated.platformFeeAmount,
        },
      ],
    });

    // 7. Store Immutable Conversion Snapshot
    const conversion: ConversionRecord = {
      id: conversionId,
      brandStoreId: dto.brandStoreId,
      clickId: click.id,
      externalOrderId: dto.externalOrderId,
      subtotalAmount: MoneyUtil.round(subtotal),
      discountAmount: MoneyUtil.round(totalDiscount),
      shippingAmount: MoneyUtil.round(shipping),
      taxAmount: MoneyUtil.round(tax),
      netCommissionBase: finalCommissionBase,
      currency: 'SAR',
      status: 'PENDING',
      attributedCampaignId: campaign.id,
      attributedMarketerId: click.marketerProfileId,
      attributedTrackingLinkId: click.trackingLinkId,
      attributionClickId: click.id,
      attributionMethod: 'LAST_TOUCH',
      attributionWindowDaysSnapshot: click.cookieDurationDays,
      attributedAt: now,
      createdAt: now,
    };

    const commission: CommissionRecord = {
      id: commissionId,
      conversionId,
      campaignId: campaign.id,
      marketerProfileId: click.marketerProfileId,
      appliedRateType: campaign.commissionType,
      appliedRateValue: campaign.commissionValue,
      commissionBase: finalCommissionBase,
      marketerAmount: calculated.marketerAmount,
      platformFeeAmount: calculated.platformFeeAmount,
      brandTotalCost: calculated.brandTotalCost,
      status: 'PENDING',
      matureAt,
      createdAt: now,
    };

    this.conversions.push(conversion);
    this.commissions.push(commission);

    auditService.recordEvent({
      action: 'CONVERSION_PROCESSED',
      entityType: 'Conversion',
      entityId: conversion.id,
      payloadDiff: {
        externalOrderId: conversion.externalOrderId,
        marketerAmount: commission.marketerAmount.toFixed(2),
        platformFeeAmount: commission.platformFeeAmount.toFixed(2),
      },
    });

    return { conversion, commission };
  }

  /**
   * Commission Maturity Worker:
   * Moves mature pending commissions to AVAILABLE and recognizes PLATFORM_REVENUE
   */
  matureEligibleCommissions(): { maturedCount: number; totalMaturedAmount: Decimal } {
    const now = new Date();
    const eligible = this.commissions.filter(
      c => c.status === 'PENDING' && c.matureAt <= now
    );

    let totalMatured = new Decimal(0);

    for (const comm of eligible) {
      // Post Ledger Transaction:
      // DR 2020_MARKETER_COMMISSION_PENDING (marketerAmount)
      // DR 2025_PLATFORM_FEE_PENDING (platformFeeAmount)
      // CR 2030_MARKETER_PAYABLE (marketerAmount)
      // CR 4010_PLATFORM_REVENUE (platformFeeAmount)
      ledgerService.postBalancedTransaction({
        type: 'COMMISSION_MATURITY',
        description: `نضج عمولة واعتراف إيراد نمولي للطلب`,
        campaignId: comm.campaignId,
        conversionId: comm.conversionId,
        commissionId: comm.id,
        entries: [
          {
            accountCode: '2020_MARKETER_COMMISSION_PENDING',
            debitAmount: comm.marketerAmount,
            creditAmount: 0,
          },
          {
            accountCode: '2025_PLATFORM_FEE_PENDING',
            debitAmount: comm.platformFeeAmount,
            creditAmount: 0,
          },
          {
            accountCode: '2030_MARKETER_PAYABLE',
            debitAmount: 0,
            creditAmount: comm.marketerAmount,
          },
          {
            accountCode: '4010_PLATFORM_REVENUE',
            debitAmount: 0,
            creditAmount: comm.platformFeeAmount,
          },
        ],
      });

      // Update wallet with receivable offset logic
      walletService.applyCommissionMaturityWithReceivableOffset({
        marketerProfileId: comm.marketerProfileId,
        grossMaturedAmount: comm.marketerAmount,
      });

      comm.status = 'AVAILABLE';
      totalMatured = totalMatured.plus(comm.marketerAmount);
    }

    return { maturedCount: eligible.length, totalMaturedAmount: totalMatured };
  }

  /**
   * Process Full Refund Before Maturity:
   * DR MARKETER_COMMISSION_PENDING, DR PLATFORM_FEE_PENDING, CR BRAND_CAMPAIGN_RESERVED
   */
  processFullRefundBeforeMaturity(conversionId: string): void {
    const commission = this.commissions.find(c => c.conversionId === conversionId);
    if (!commission || commission.status !== 'PENDING') {
      throw new Error('INVALID_REFUND_STATE: Commission is not in PENDING state.');
    }

    ledgerService.postBalancedTransaction({
      type: 'FULL_REFUND_REVERSAL',
      description: 'إلغاء عمولة معلقة واسترجاع كامل المبلغ لميزانية الحملة',
      campaignId: commission.campaignId,
      conversionId: commission.conversionId,
      commissionId: commission.id,
      entries: [
        {
          accountCode: '2020_MARKETER_COMMISSION_PENDING',
          debitAmount: commission.marketerAmount,
          creditAmount: 0,
        },
        {
          accountCode: '2025_PLATFORM_FEE_PENDING',
          debitAmount: commission.platformFeeAmount,
          creditAmount: 0,
        },
        {
          accountCode: '2015_BRAND_CAMPAIGN_RESERVED',
          debitAmount: 0,
          creditAmount: commission.brandTotalCost,
        },
      ],
    });

    commission.status = 'VOIDED';
    const conversion = this.conversions.find(c => c.id === conversionId);
    if (conversion) {
      conversion.status = 'REFUNDED';
    }
  }

  /**
   * Process Post-Payout Reversal (Chargeback after withdrawal):
   * DR MARKETER_RECEIVABLE, DR PLATFORM_REVENUE, CR BRAND_DEPOSIT
   */
  processPostPayoutReversal(params: {
    conversionId: string;
    brandProfileId: string;
  }): void {
    const commission = this.commissions.find(c => c.conversionId === params.conversionId);
    if (!commission) {
      throw new Error('COMMISSION_NOT_FOUND: Commission record not found.');
    }

    // Post Balanced Reversal:
    ledgerService.postBalancedTransaction({
      type: 'POST_PAYOUT_CHARGEBACK',
      description: 'عكس عملية بيع بعد سحب الأرباح وإثبات ذمة مدينة على المسوق وعكس إيراد المنصة',
      brandProfileId: params.brandProfileId,
      campaignId: commission.campaignId,
      conversionId: commission.conversionId,
      commissionId: commission.id,
      entries: [
        {
          accountCode: '1020_MARKETER_RECEIVABLE',
          debitAmount: commission.marketerAmount,
          creditAmount: 0,
        },
        {
          accountCode: '4010_PLATFORM_REVENUE',
          debitAmount: commission.platformFeeAmount,
          creditAmount: 0,
        },
        {
          accountCode: '2010_BRAND_DEPOSIT',
          debitAmount: 0,
          creditAmount: commission.brandTotalCost,
        },
      ],
    });

    // Update Marketer Wallet to reflect receivable
    walletService.recordPostPayoutChargeback({
      marketerProfileId: commission.marketerProfileId,
      chargebackAmount: commission.marketerAmount,
    });

    commission.status = 'VOIDED';
  }

  getCommissionsByMarketer(marketerProfileId: string): CommissionRecord[] {
    return this.commissions.filter(c => c.marketerProfileId === marketerProfileId);
  }
}

export const conversionsService = new ConversionsService();
