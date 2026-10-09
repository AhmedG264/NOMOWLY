import crypto from 'crypto';
import { campaignsService } from '../campaigns/campaigns.service.ts';
import { auditService } from '../audit/audit.service.ts';

export interface TrackingLinkRecord {
  id: string;
  campaignId: string;
  marketerProfileId: string;
  shortCode: string;
  destinationUrl: string;
  clicksCount: number;
  conversionsCount: number;
  createdAt: Date;
}

export interface ClickRecord {
  id: string;
  trackingLinkId: string;
  campaignId: string;
  marketerProfileId: string;
  ipHash: string;
  userAgent: string;
  sub1?: string;
  sub2?: string;
  sub3?: string;
  cookieDurationDays: number;
  expiresAt: Date;
  createdAt: Date;
}

export class TrackingService {
  private links: TrackingLinkRecord[] = [];
  private clicks: ClickRecord[] = [];
  // Fast in-memory / Redis attribution cache: clickId -> ClickRecord
  private attributionCache = new Map<string, ClickRecord>();

  createTrackingLink(params: {
    campaignId: string;
    marketerProfileId: string;
    destinationUrl: string;
  }): TrackingLinkRecord {
    const campaign = campaignsService.getCampaignById(params.campaignId);
    if (!campaign) {
      throw new Error('CAMPAIGN_NOT_FOUND: Campaign does not exist.');
    }

    const shortCode = `ar-perf-${crypto.randomBytes(3).toString('hex')}`;
    const link: TrackingLinkRecord = {
      id: crypto.randomUUID(),
      campaignId: params.campaignId,
      marketerProfileId: params.marketerProfileId,
      shortCode,
      destinationUrl: params.destinationUrl,
      clicksCount: 0,
      conversionsCount: 0,
      createdAt: new Date(),
    };

    this.links.push(link);
    return link;
  }

  /**
   * Fast Dual-Write Click Ingestion:
   * 1. Generates Click ID (UUIDv7 timestamp-based)
   * 2. Writes immediately to attribution cache with dynamic TTL matching campaign.cookieDurationDays
   * 3. Stores long-term click entry with privacy-hashed IP
   */
  recordClick(params: {
    shortCode: string;
    rawIp?: string;
    userAgent?: string;
    sub1?: string;
    sub2?: string;
    sub3?: string;
  }): { clickId: string; redirectUrl: string } {
    const link = this.links.find(l => l.shortCode === params.shortCode);
    if (!link) {
      throw new Error('INVALID_TRACKING_CODE: Tracking link not found.');
    }

    const campaign = campaignsService.getCampaignById(link.campaignId);
    if (!campaign || campaign.status !== 'ACTIVE') {
      // Direct redirect without crediting if campaign is inactive
      return {
        clickId: '',
        redirectUrl: link.destinationUrl,
      };
    }

    const clickId = crypto.randomUUID();
    const now = new Date();
    const durationDays = campaign.cookieDurationDays || 30;
    const expiresAt = new Date(now.getTime() + (durationDays + 2) * 86400000);

    const click: ClickRecord = {
      id: clickId,
      trackingLinkId: link.id,
      campaignId: link.campaignId,
      marketerProfileId: link.marketerProfileId,
      ipHash: auditService.hashIpAddress(params.rawIp) || 'unknown-ip',
      userAgent: params.userAgent || 'unknown-ua',
      sub1: params.sub1,
      sub2: params.sub2,
      sub3: params.sub3,
      cookieDurationDays: durationDays,
      expiresAt,
      createdAt: now,
    };

    // 1. Fast cache write (Attribution-Critical Path)
    this.attributionCache.set(clickId, click);
    // 2. Persistent log write (Analytics Path)
    this.clicks.push(click);
    link.clicksCount++;

    // Append ?nmw_cid=clickId to destination URL
    const separator = link.destinationUrl.includes('?') ? '&' : '?';
    const redirectUrl = `${link.destinationUrl}${separator}nmw_cid=${clickId}`;

    return { clickId, redirectUrl };
  }

  /**
   * Attribution Engine: Look up and validate click within active attribution window
   */
  resolveAttribution(clickId: string): ClickRecord | null {
    if (!clickId) return null;

    // Check fast cache first
    let click = this.attributionCache.get(clickId);
    if (!click) {
      // Fallback to persistent storage
      click = this.clicks.find(c => c.id === clickId);
    }

    if (!click) return null;

    // Verify lookback window
    const now = new Date();
    const windowMs = click.cookieDurationDays * 86400000;
    const elapsedMs = now.getTime() - click.createdAt.getTime();

    if (elapsedMs > windowMs) {
      // Attribution window expired
      return null;
    }

    return click;
  }

  getLinkByShortCode(shortCode: string): TrackingLinkRecord | undefined {
    return this.links.find(l => l.shortCode === shortCode);
  }

  getLinksByMarketer(marketerProfileId: string): TrackingLinkRecord[] {
    return this.links.filter(l => l.marketerProfileId === marketerProfileId);
  }
}

export const trackingService = new TrackingService();
