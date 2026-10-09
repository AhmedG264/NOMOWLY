import express, { Request, Response } from 'express';
import { authService } from '../modules/auth/auth.service.ts';
import { campaignsService } from '../modules/campaigns/campaigns.service.ts';
import { trackingService } from '../modules/tracking/tracking.service.ts';
import { conversionsService } from '../modules/conversions/conversions.service.ts';
import { ledgerService } from '../modules/ledger/ledger.service.ts';
import { walletService } from '../modules/wallet/wallet.service.ts';
import { withdrawalsService } from '../modules/withdrawals/withdrawals.service.ts';
import { auditService } from '../modules/audit/audit.service.ts';
import { CHART_OF_ACCOUNTS, SUPPORTED_CURRENCIES } from '../../../packages/shared/src/constants.ts';

export const apiRouter = express.Router();

// Helper middleware for JWT authentication
function requireAuth(req: Request, res: Response, next: express.NextFunction) {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    res.status(401).json({ success: false, error: 'UNAUTHORIZED: Missing or invalid token' });
    return;
  }
  try {
    const token = authHeader.split(' ')[1];
    const user = authService.verifyAccessToken(token);
    (req as any).user = user;
    next();
  } catch {
    res.status(401).json({ success: false, error: 'UNAUTHORIZED: Token expired or invalid' });
  }
}

// -------------------------------------------------------------
// 1. REGIONAL CURRENCIES & LOCALIZATION
// -------------------------------------------------------------

apiRouter.get('/currencies', (req: Request, res: Response) => {
  res.json({ success: true, data: SUPPORTED_CURRENCIES });
});

// -------------------------------------------------------------
// 2. AUTHENTICATION & IDENTITY
// -------------------------------------------------------------

apiRouter.post('/auth/register', async (req: Request, res: Response) => {
  try {
    const result = await authService.register(req.body);
    res.status(201).json({ success: true, data: result });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

apiRouter.post('/auth/login', async (req: Request, res: Response) => {
  try {
    const result = await authService.login(req.body);
    res.json({ success: true, data: result });
  } catch (err: any) {
    res.status(401).json({ success: false, error: err.message });
  }
});

apiRouter.get('/auth/me', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  res.json({ success: true, data: user });
});

// -------------------------------------------------------------
// 3. CAMPAIGN MARKETPLACE & DISCOVERY
// -------------------------------------------------------------

apiRouter.get('/marketplace/campaigns', (req: Request, res: Response) => {
  const { category, currency, country, search } = req.query;
  const campaigns = campaignsService.getActiveCampaigns({
    category: category as string,
    currency: currency as string,
    country: country as string,
    search: search as string,
  });
  res.json({ success: true, data: campaigns });
});

apiRouter.get('/campaigns/:id', (req: Request, res: Response) => {
  const campaign = campaignsService.getCampaignById(req.params.id);
  if (!campaign) {
    res.status(404).json({ success: false, error: 'CAMPAIGN_NOT_FOUND' });
    return;
  }
  res.json({ success: true, data: campaign });
});

apiRouter.post('/campaigns/:id/apply', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'MARKETER') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Only marketers can apply to campaigns' });
    return;
  }
  try {
    const application = campaignsService.applyToCampaign({
      campaignId: req.params.id,
      marketerProfileId: user.marketerProfileId!,
      marketerName: req.body.marketerName || user.email,
      specialization: req.body.specialization || 'CREATOR',
      pitchNote: req.body.pitchNote,
    });
    res.status(201).json({ success: true, data: application });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

apiRouter.get('/marketers/applications', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'MARKETER') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Marketer access required' });
    return;
  }
  const apps = campaignsService.getApplicationsByMarketer(user.marketerProfileId!);
  res.json({ success: true, data: apps });
});

apiRouter.get('/brands/applications', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'BRAND') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Brand access required' });
    return;
  }
  const myCampaigns = campaignsService.getCampaignsByBrand(user.brandProfileId!);
  const allApps: any[] = [];
  for (const c of myCampaigns) {
    const apps = campaignsService.getApplicationsByCampaign(c.id);
    allApps.push(...apps);
  }
  res.json({ success: true, data: allApps });
});

// -------------------------------------------------------------
// 4. BRAND CAMPAIGN MANAGEMENT & DEPOSITS
// -------------------------------------------------------------

apiRouter.post('/campaigns', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'BRAND') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Only brands can create campaigns' });
    return;
  }
  try {
    const campaign = campaignsService.createCampaign({
      brandProfileId: user.brandProfileId!,
      ...req.body,
    });
    res.status(201).json({ success: true, data: campaign });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

apiRouter.get('/brands/campaigns', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'BRAND') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Brand access required' });
    return;
  }
  const myCampaigns = campaignsService.getCampaignsByBrand(user.brandProfileId!);
  res.json({ success: true, data: myCampaigns });
});

apiRouter.post('/brands/deposit', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'BRAND' && user.role !== 'SUPER_ADMIN') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Brand role required' });
    return;
  }
  const { amount, currency, idempotencyKey } = req.body;
  const brandProfileId = user.brandProfileId || req.body.brandProfileId;

  try {
    const tx = ledgerService.postBalancedTransaction({
      type: 'BRAND_DEPOSIT',
      description: `إيداع رصيد ضمان للعلامة التجارية بمبلغ ${amount} ${currency || 'SAR'}`,
      currency: currency || 'SAR',
      brandProfileId,
      idempotencyKey,
      entries: [
        { accountCode: '1010_ESCROW_BANK', debitAmount: amount, creditAmount: 0 },
        { accountCode: '2010_BRAND_DEPOSIT', debitAmount: 0, creditAmount: amount },
      ],
    });
    const wallet = walletService.reconcileBrandWallet(brandProfileId);
    res.json({ success: true, data: { transaction: tx, wallet } });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

// -------------------------------------------------------------
// 5. TRACKING LINKS & CONVERSIONS
// -------------------------------------------------------------

apiRouter.post('/tracking/links', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'MARKETER') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Only marketers can generate links' });
    return;
  }
  try {
    const link = trackingService.createTrackingLink({
      campaignId: req.body.campaignId,
      marketerProfileId: user.marketerProfileId!,
      destinationUrl: req.body.destinationUrl,
    });
    res.status(201).json({ success: true, data: link });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

apiRouter.post('/conversions/webhook', (req: Request, res: Response) => {
  try {
    const result = conversionsService.processOrderConversion(req.body);
    res.status(200).json({
      success: true,
      message: 'Conversion successfully attributed and recorded in ledger',
      data: result,
    });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

// -------------------------------------------------------------
// 6. WALLET & WITHDRAWALS
// -------------------------------------------------------------

apiRouter.get('/wallet', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  const ownerId = user.brandProfileId || user.marketerProfileId || user.userId;
  const ownerType = user.role === 'BRAND' ? 'BRAND' : 'MARKETER';
  const wallet = walletService.getOrCreateWallet(ownerId, ownerType);
  res.json({ success: true, data: wallet });
});

apiRouter.post('/withdrawals/request', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'MARKETER') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Only marketers can withdraw' });
    return;
  }
  try {
    const reqRecord = withdrawalsService.requestWithdrawal({
      marketerProfileId: user.marketerProfileId!,
      amount: req.body.amount,
      rawIban: req.body.iban,
      rawBeneficiaryName: req.body.beneficiaryName,
      isKycVerified: req.body.isKycVerified ?? true,
    });
    res.status(201).json({ success: true, data: reqRecord });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

apiRouter.get('/marketers/withdrawals', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'MARKETER') {
    res.status(403).json({ success: false, error: 'FORBIDDEN' });
    return;
  }
  const history = withdrawalsService.getRequestsByMarketer(user.marketerProfileId!);
  res.json({ success: true, data: history });
});

// -------------------------------------------------------------
// 7. ADMIN OPERATIONS & AUDIT
// -------------------------------------------------------------

apiRouter.get('/admin/withdrawals', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'SUPER_ADMIN') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Admin access required' });
    return;
  }
  const requests = withdrawalsService.getAllRequests();
  res.json({ success: true, data: requests });
});

apiRouter.post('/admin/withdrawals/:id/settle', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'SUPER_ADMIN') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Only admin can settle withdrawals' });
    return;
  }
  try {
    const settled = withdrawalsService.settleWithdrawal({
      requestId: req.params.id,
      payoutReference: req.body.payoutReference || `BANK-TRX-${Date.now()}`,
    });
    res.json({ success: true, data: settled });
  } catch (err: any) {
    res.status(400).json({ success: false, error: err.message });
  }
});

apiRouter.get('/admin/users', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'SUPER_ADMIN') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Admin access required' });
    return;
  }
  const users = authService.getAllUsers();
  res.json({ success: true, data: users });
});

apiRouter.get('/audit/logs', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  if (user.role !== 'SUPER_ADMIN') {
    res.status(403).json({ success: false, error: 'FORBIDDEN: Admin access required' });
    return;
  }
  const logs = auditService.getLogs();
  res.json({ success: true, data: logs });
});

apiRouter.get('/ledger/transactions', requireAuth, (req: Request, res: Response) => {
  const user = (req as any).user;
  const filter = user.role === 'BRAND' ? { brandProfileId: user.brandProfileId } : undefined;
  const txs = ledgerService.getAllTransactions(filter);
  res.json({ success: true, data: txs });
});

apiRouter.get('/ledger/chart-of-accounts', (req: Request, res: Response) => {
  res.json({ success: true, data: CHART_OF_ACCOUNTS });
});

apiRouter.get('/docs', (req: Request, res: Response) => {
  res.json({
    openapi: '3.0.0',
    info: {
      title: 'Nomowly Performance Marketing Marketplace API',
      version: '1.0.0',
      description: 'API موثقة لمنصة نمولي للتسويق بالأداء واحتساب العمولات والدفتر المالي المزدوج في مصر والخليج العربي',
    },
    paths: {
      '/api/v1/marketplace/campaigns': { get: { summary: 'استكشاف الحملات التسويقية مع فلاتر الدولة والعملة والتصنيف' } },
      '/api/v1/campaigns/{id}/apply': { post: { summary: 'تقديم المسوق للانضمام إلى الحملة وتوليد رابط التتبع' } },
      '/api/v1/auth/register': { post: { summary: 'تسجيل مستخدم جديد (علامة تجارية أو مسوق)' } },
      '/api/v1/auth/login': { post: { summary: 'تسجيل الدخول واستخراج توكن JWT' } },
      '/api/v1/brands/deposit': { post: { summary: 'شحن رصيد الضمان للتاجر وإثباته في دفتر الأستاذ' } },
      '/c/{shortCode}': { get: { summary: 'نقطة التحويل السريع 302 مع إلحاق nmw_cid' } },
      '/api/v1/conversions/webhook': { post: { summary: 'استقبال إشعار الشراء واحتساب العمولات بالخادم' } },
      '/api/v1/withdrawals/request': { post: { summary: 'طلب سحب أرباح مع فحص KYC والحد الأدنى' } },
    },
  });
});
