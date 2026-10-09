import crypto from 'crypto';
import Decimal from 'decimal.js';
import { ledgerService } from '../ledger/ledger.service.ts';
import { auditService } from '../audit/audit.service.ts';
import { trackingService } from '../tracking/tracking.service.ts';

export interface CampaignRecord {
  id: string;
  brandProfileId: string;
  brandName: string;
  title: string;
  description: string;
  category: string;
  status: 'DRAFT' | 'PENDING_REVIEW' | 'ACTIVE' | 'PAUSED' | 'COMPLETED' | 'CANCELLED' | 'REJECTED';
  commissionType: 'PERCENTAGE' | 'FLAT_FEE';
  commissionValue: Decimal;
  platformFeeRate: Decimal;
  cookieDurationDays: number;
  lockPeriodDays: number;
  budgetCap: Decimal;
  currency: string;
  targetCountries: string[];
  isAllProducts: boolean;
  destinationUrl: string;
  createdAt: Date;
}

export interface BudgetReservationRecord {
  id: string;
  campaignId: string;
  brandProfileId: string;
  reservedAmount: Decimal;
  consumedAmount: Decimal;
  releasedAmount: Decimal;
  status: 'ACTIVE' | 'EXHAUSTED' | 'RELEASED' | 'CANCELLED';
  createdAt: Date;
}

export interface CampaignApplicationRecord {
  id: string;
  campaignId: string;
  marketerProfileId: string;
  marketerName: string;
  specialization: string;
  status: 'SUBMITTED' | 'APPROVED' | 'REJECTED' | 'SUSPENDED';
  pitchNote?: string;
  trackingLinkId?: string;
  appliedAt: Date;
  reviewedAt?: Date;
}

export class CampaignsService {
  private campaigns: CampaignRecord[] = [];
  private reservations: BudgetReservationRecord[] = [];
  private applications: CampaignApplicationRecord[] = [];

  constructor() {
    this.seedRegionalMarketplaceCampaigns();
  }

  private seedRegionalMarketplaceCampaigns() {
    if (this.campaigns.length > 0) return;

    // Realistic Arabic marketplace seed campaigns representing Egypt, Saudi, UAE, and Gulf
    const initialCampaigns: Array<{
      id: string;
      brandProfileId: string;
      brandName: string;
      title: string;
      description: string;
      category: string;
      commissionType: 'PERCENTAGE' | 'FLAT_FEE';
      commissionValue: number;
      currency: string;
      targetCountries: string[];
      budgetCap: number;
      destinationUrl: string;
    }> = [
      {
        id: 'camp-sa-perfume',
        brandProfileId: 'brand-oud-elite',
        brandName: 'دار العود الملكي',
        title: 'حملة العطور الشرقية والبخور الفاخر',
        description: 'عمولة مجزية 15% على جميع مبيعات العطور والدهن الملكي داخل المملكة العربية السعودية.',
        category: 'عطور وتجميل',
        commissionType: 'PERCENTAGE',
        commissionValue: 15,
        currency: 'SAR',
        targetCountries: ['SA', 'AE', 'KW'],
        budgetCap: 15000,
        destinationUrl: 'https://royal-oud.com/collections/best-sellers',
      },
      {
        id: 'camp-eg-fashion',
        brandProfileId: 'brand-cairo-style',
        brandName: 'ستايل كايرو إيجيبت',
        title: 'تشكيلة الملابس الصيفية والإكسسوارات',
        description: 'احصل على 12% عمولة لكل طلبية مؤكدة من أحدث صيحات الموضة النسائية والرجالية في مصر.',
        category: 'أزياء وموضة',
        commissionType: 'PERCENTAGE',
        commissionValue: 12,
        currency: 'EGP',
        targetCountries: ['EG'],
        budgetCap: 50000,
        destinationUrl: 'https://stylecairo.eg/summer-collection',
      },
      {
        id: 'camp-ae-tech',
        brandProfileId: 'brand-dubai-gadgets',
        brandName: 'دبي جادجيتس سمارت',
        title: 'إلكترونيات وأجهزة المنزل الذكي',
        description: 'مكافأة ثابتة 80 درهم لكل عملية بيع مؤكدة لملحقات الهواتف وأجهزة المنزل الذكية.',
        category: 'إلكترونيات',
        commissionType: 'FLAT_FEE',
        commissionValue: 80,
        currency: 'AED',
        targetCountries: ['AE', 'SA', 'QA'],
        budgetCap: 20000,
        destinationUrl: 'https://dubaismart.ae/deals',
      },
      {
        id: 'camp-kw-beauty',
        brandProfileId: 'brand-glow-kw',
        brandName: 'جلو كويت لمستحضرات العناية',
        title: 'عناية بالبشرة ومستحضرات طبيعية',
        description: 'عمولة 18% لصناع المحتوى ومؤثري الجمال على تيك توك وإنستغرام في الكويت والخليج.',
        category: 'عناية وصحة',
        commissionType: 'PERCENTAGE',
        commissionValue: 18,
        currency: 'KWD',
        targetCountries: ['KW', 'SA', 'BH'],
        budgetCap: 3000,
        destinationUrl: 'https://glowkw.com/skincare',
      },
      {
        id: 'camp-eg-fitness',
        brandProfileId: 'brand-nile-fit',
        brandName: 'نايل فيت للمكملات والرياضة',
        title: 'المكملات الغذائية ومستلزمات التدريب',
        description: 'عمولة ثابتة 150 جنيه مصري لكل طلب مكملات غذائية وأدوات رياضية يتم شحنه في جمهورية مصر العربية.',
        category: 'رياضة ولياقة',
        commissionType: 'FLAT_FEE',
        commissionValue: 150,
        currency: 'EGP',
        targetCountries: ['EG'],
        budgetCap: 35000,
        destinationUrl: 'https://nilefit.eg/supplements',
      },
      {
        id: 'camp-sa-kids',
        brandProfileId: 'brand-toyland-sa',
        brandName: 'عالم الألعاب والتعليم المبكر',
        title: 'ألعاب الذكاء وتنمية المهارات للأطفال',
        description: 'عمولة 10% لكل طلبية ألعاب تعليمية موجهة للأمهات والآباء في دول الخليج العربي.',
        category: 'أطفال وألعاب',
        commissionType: 'PERCENTAGE',
        commissionValue: 10,
        currency: 'SAR',
        targetCountries: ['SA', 'AE', 'OM'],
        budgetCap: 10000,
        destinationUrl: 'https://toyland.sa/stem-toys',
      },
    ];

    for (const c of initialCampaigns) {
      this.campaigns.push({
        id: c.id,
        brandProfileId: c.brandProfileId,
        brandName: c.brandName,
        title: c.title,
        description: c.description,
        category: c.category,
        status: 'ACTIVE',
        commissionType: c.commissionType,
        commissionValue: new Decimal(c.commissionValue),
        platformFeeRate: new Decimal(0.02),
        cookieDurationDays: 30,
        lockPeriodDays: 30,
        budgetCap: new Decimal(c.budgetCap),
        currency: c.currency,
        targetCountries: c.targetCountries,
        isAllProducts: true,
        destinationUrl: c.destinationUrl,
        createdAt: new Date(),
      });
    }
  }

  createCampaign(params: {
    brandProfileId: string;
    brandName?: string;
    title: string;
    description: string;
    category?: string;
    commissionType: 'PERCENTAGE' | 'FLAT_FEE';
    commissionValue: string | number | Decimal;
    platformFeeRate?: string | number | Decimal;
    budgetCap: string | number | Decimal;
    currency?: string;
    targetCountries?: string[];
    cookieDurationDays?: number;
    lockPeriodDays?: number;
    isAllProducts?: boolean;
    destinationUrl?: string;
  }): CampaignRecord {
    const campaignId = crypto.randomUUID();
    const campaign: CampaignRecord = {
      id: campaignId,
      brandProfileId: params.brandProfileId,
      brandName: params.brandName || 'علامة تجارية معتمدة',
      title: params.title,
      description: params.description,
      category: params.category || 'عام',
      status: 'ACTIVE',
      commissionType: params.commissionType,
      commissionValue: new Decimal(params.commissionValue),
      platformFeeRate: new Decimal(params.platformFeeRate || 0.02),
      cookieDurationDays: params.cookieDurationDays || 30,
      lockPeriodDays: params.lockPeriodDays || 30,
      budgetCap: new Decimal(params.budgetCap),
      currency: params.currency || 'SAR',
      targetCountries: params.targetCountries || ['SA', 'EG', 'AE'],
      isAllProducts: params.isAllProducts ?? true,
      destinationUrl: params.destinationUrl || 'https://mybrand.com/store',
      createdAt: new Date(),
    };

    this.campaigns.push(campaign);

    auditService.recordEvent({
      action: 'CAMPAIGN_CREATED',
      entityType: 'Campaign',
      entityId: campaign.id,
      payloadDiff: { title: campaign.title, currency: campaign.currency, budgetCap: campaign.budgetCap.toFixed(2) },
    });

    return campaign;
  }

  /**
   * Marketer applies to participate in a campaign
   */
  applyToCampaign(params: {
    campaignId: string;
    marketerProfileId: string;
    marketerName: string;
    specialization?: string;
    pitchNote?: string;
  }): CampaignApplicationRecord {
    const campaign = this.campaigns.find(c => c.id === params.campaignId);
    if (!campaign) {
      throw new Error('CAMPAIGN_NOT_FOUND: Campaign does not exist.');
    }

    const existing = this.applications.find(
      a => a.campaignId === params.campaignId && a.marketerProfileId === params.marketerProfileId
    );
    if (existing) {
      return existing; // Idempotent return
    }

    // Auto-approve in MVP to enable immediate marketer onboarding & link generation
    const applicationId = crypto.randomUUID();
    const trackingLink = trackingService.createTrackingLink({
      campaignId: campaign.id,
      marketerProfileId: params.marketerProfileId,
      destinationUrl: campaign.destinationUrl,
    });

    const application: CampaignApplicationRecord = {
      id: applicationId,
      campaignId: campaign.id,
      marketerProfileId: params.marketerProfileId,
      marketerName: params.marketerName || 'مسوق معتمد',
      specialization: params.specialization || 'CREATOR',
      status: 'APPROVED',
      pitchNote: params.pitchNote,
      trackingLinkId: trackingLink.id,
      appliedAt: new Date(),
      reviewedAt: new Date(),
    };

    this.applications.push(application);

    auditService.recordEvent({
      action: 'CAMPAIGN_APPLICATION_APPROVED',
      entityType: 'CampaignApplication',
      entityId: application.id,
      payloadDiff: { campaignTitle: campaign.title, marketerName: application.marketerName },
    });

    return application;
  }

  getApplicationsByCampaign(campaignId: string): CampaignApplicationRecord[] {
    return this.applications.filter(a => a.campaignId === campaignId);
  }

  getApplicationsByMarketer(marketerProfileId: string): CampaignApplicationRecord[] {
    return this.applications.filter(a => a.marketerProfileId === marketerProfileId);
  }

  getCampaignById(id: string): CampaignRecord | undefined {
    return this.campaigns.find(c => c.id === id);
  }

  getCampaignsByBrand(brandProfileId: string): CampaignRecord[] {
    return this.campaigns.filter(c => c.brandProfileId === brandProfileId);
  }

  getActiveCampaigns(filters?: {
    category?: string;
    currency?: string;
    country?: string;
    search?: string;
  }): CampaignRecord[] {
    return this.campaigns.filter(c => {
      if (c.status !== 'ACTIVE') return false;
      if (filters?.category && filters.category !== 'ALL' && c.category !== filters.category) return false;
      if (filters?.currency && filters.currency !== 'ALL' && c.currency !== filters.currency) return false;
      if (filters?.country && filters.country !== 'ALL' && !c.targetCountries.includes(filters.country)) return false;
      if (filters?.search) {
        const query = filters.search.toLowerCase();
        const matches = c.title.toLowerCase().includes(query) ||
          c.brandName.toLowerCase().includes(query) ||
          c.description.toLowerCase().includes(query);
        if (!matches) return false;
      }
      return true;
    });
  }

  reserveAndActivateCampaign(params: {
    campaignId: string;
    brandProfileId: string;
  }): { campaign: CampaignRecord; reservation: BudgetReservationRecord } {
    const campaign = this.campaigns.find(c => c.id === params.campaignId);
    if (!campaign) {
      throw new Error('CAMPAIGN_NOT_FOUND: Campaign does not exist.');
    }

    const freeDeposit = ledgerService.getAccountBalance('2010_BRAND_DEPOSIT', {
      brandProfileId: params.brandProfileId,
    });

    if (freeDeposit.lessThan(campaign.budgetCap)) {
      throw new Error(
        `INSUFFICIENT_FUNDS: Free deposit balance (${freeDeposit.toFixed(2)} SAR) is less than required budget (${campaign.budgetCap.toFixed(2)} SAR).`
      );
    }

    const reservationId = crypto.randomUUID();

    ledgerService.postBalancedTransaction({
      type: 'CAMPAIGN_BUDGET_RESERVE',
      description: `حجز ميزانية للحملة: ${campaign.title}`,
      brandProfileId: params.brandProfileId,
      campaignId: campaign.id,
      campaignReservationId: reservationId,
      entries: [
        {
          accountCode: '2010_BRAND_DEPOSIT',
          debitAmount: campaign.budgetCap,
          creditAmount: 0,
        },
        {
          accountCode: '2015_BRAND_CAMPAIGN_RESERVED',
          debitAmount: 0,
          creditAmount: campaign.budgetCap,
        },
      ],
    });

    const reservation: BudgetReservationRecord = {
      id: reservationId,
      campaignId: campaign.id,
      brandProfileId: params.brandProfileId,
      reservedAmount: campaign.budgetCap,
      consumedAmount: new Decimal(0),
      releasedAmount: new Decimal(0),
      status: 'ACTIVE',
      createdAt: new Date(),
    };

    this.reservations.push(reservation);
    campaign.status = 'ACTIVE';

    return { campaign, reservation };
  }

  consumeBudget(params: {
    campaignId: string;
    amountToConsume: Decimal;
  }): { isExhausted: boolean; remainingBudget: Decimal } {
    const reservation = this.reservations.find(
      r => r.campaignId === params.campaignId && r.status === 'ACTIVE'
    );
    if (!reservation) {
      const campaign = this.campaigns.find(c => c.id === params.campaignId);
      return { isExhausted: false, remainingBudget: campaign?.budgetCap || new Decimal(10000) };
    }

    const unconsumed = reservation.reservedAmount.minus(reservation.consumedAmount);
    if (params.amountToConsume.greaterThan(unconsumed)) {
      throw new Error('OVER_CONSUMPTION_PREVENTED: Sale cost exceeds remaining reserved budget.');
    }

    reservation.consumedAmount = reservation.consumedAmount.plus(params.amountToConsume);
    const remaining = reservation.reservedAmount.minus(reservation.consumedAmount);
    return { isExhausted: remaining.lessThanOrEqualTo(0), remainingBudget: remaining };
  }

  releaseUnusedBudget(params: {
    campaignId: string;
    brandProfileId: string;
  }): { releasedAmount: Decimal } {
    const reservation = this.reservations.find(
      r => r.campaignId === params.campaignId && r.status === 'ACTIVE'
    );
    if (!reservation) {
      return { releasedAmount: new Decimal(0) };
    }
    const unused = reservation.reservedAmount.minus(reservation.consumedAmount);
    reservation.status = 'RELEASED';
    return { releasedAmount: unused };
  }
}

export const campaignsService = new CampaignsService();
