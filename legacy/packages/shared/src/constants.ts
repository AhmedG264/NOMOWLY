export const SUPPORTED_CURRENCIES = [
  { code: 'SAR', name: 'ريال سعودي', symbol: 'ر.س', country: 'السعودية', flag: '🇸🇦' },
  { code: 'EGP', name: 'جنيه مصري', symbol: 'ج.م', country: 'مصر', flag: '🇪🇬' },
  { code: 'AED', name: 'درهم إماراتي', symbol: 'د.إ', country: 'الإمارات', flag: '🇦🇪' },
  { code: 'KWD', name: 'دينار كويتي', symbol: 'د.ك', country: 'الكويت', flag: '🇰🇼' },
  { code: 'QAR', name: 'ريال قطري', symbol: 'ر.ق', country: 'قطر', flag: '🇶🇦' },
  { code: 'BHD', name: 'دينار بحريني', symbol: 'د.ب', country: 'البحرين', flag: '🇧🇭' },
  { code: 'OMR', name: 'ريال عماني', symbol: 'ر.ع', country: 'عُمان', flag: '🇴🇲' },
] as const;

export type SupportedCurrencyCode = typeof SUPPORTED_CURRENCIES[number]['code'];

export const SYSTEM_DEFAULTS = {
  DEFAULT_CURRENCY: 'SAR',
  MIN_WITHDRAWAL_AMOUNTS: {
    SAR: 150,
    EGP: 1000,
    AED: 150,
    KWD: 15,
    QAR: 150,
    BHD: 15,
    OMR: 15,
  } as Record<string, number>,
  DEFAULT_COOKIE_DURATION_DAYS: 30,
  DEFAULT_LOCK_PERIOD_DAYS: 30,
  DEFAULT_PLATFORM_FEE_RATE: 0.02, // 2%
  SECRET_ROTATION_GRACE_PERIOD_HOURS: 24,
  MAX_ATTRIBUTION_WINDOW_DAYS: 90,
} as const;

export interface SeedLedgerAccount {
  code: string;
  name: string;
  type: 'ASSET' | 'LIABILITY' | 'EQUITY' | 'REVENUE' | 'EXPENSE';
  description: string;
}

export const CHART_OF_ACCOUNTS: SeedLedgerAccount[] = [
  {
    code: '1010_ESCROW_BANK',
    name: 'حساب بنك الضمان المالي',
    type: 'ASSET',
    description: 'أموال المنصة الفعلية المودعة في البنك أو بوابات الدفع لحساب الضمان',
  },
  {
    code: '1020_MARKETER_RECEIVABLE',
    name: 'ذمم مدينة على المسوقين (أرصدة سالبة)',
    type: 'ASSET',
    description: 'مطالبات مالية مستحقة على المسوق عند حدوث استرجاع بعد صرف أرباحه',
  },
  {
    code: '2010_BRAND_DEPOSIT',
    name: 'أمانات العلامات التجارية (الرصيد الحر)',
    type: 'LIABILITY',
    description: 'رصيد التاجر الحر القابل للاستخدام في تمويل الحملات التسويقية',
  },
  {
    code: '2015_BRAND_CAMPAIGN_RESERVED',
    name: 'ميزانيات الحملات المحجوزة',
    type: 'LIABILITY',
    description: 'المبالغ المخصصة والمحجوزة للحملات النشطة لتغطية العمولات المحتملة',
  },
  {
    code: '2020_MARKETER_COMMISSION_PENDING',
    name: 'عمولات المسوقين المعلقة (قيد الفحص)',
    type: 'LIABILITY',
    description: 'عمولات المبيعات المسجلة الجارية ضمن فترة فحص المرتجعات',
  },
  {
    code: '2025_PLATFORM_FEE_PENDING',
    name: 'رسوم نمولي المعلقة (غير المكتسبة)',
    type: 'LIABILITY',
    description: 'رسوم نجاح المنصة المرتبطة بمبيعات لا تزال خاضعة لفترة المرتجعات',
  },
  {
    code: '2030_MARKETER_PAYABLE',
    name: 'مستحقات المسوقين الجاهزة للسحب',
    type: 'LIABILITY',
    description: 'عمولات مؤكدة ومتاحة للسحب الفوري في محفظة المسوق',
  },
  {
    code: '4010_PLATFORM_REVENUE',
    name: 'إيرادات رسوم نجاح نمولي',
    type: 'REVENUE',
    description: 'صافي أرباح المنصة المكتسبة نهائياً بعد نضج المبيعات دون استرجاع',
  },
  {
    code: '5010_BANK_ADJUSTMENT_VARIANCE',
    name: 'فروقات وتسويات بنكية ونظامية',
    type: 'EXPENSE',
    description: 'تسويات العجز أو الفروقات البنكية المعتمدة إدارياً',
  },
];

export const ERROR_CODES = {
  UNAUTHORIZED: 'UNAUTHORIZED',
  FORBIDDEN: 'FORBIDDEN',
  NOT_FOUND: 'NOT_FOUND',
  BAD_REQUEST: 'BAD_REQUEST',
  VALIDATION_ERROR: 'VALIDATION_ERROR',
  INSUFFICIENT_FUNDS: 'INSUFFICIENT_FUNDS',
  UNBALANCED_TRANSACTION: 'UNBALANCED_TRANSACTION',
  IDEMPOTENCY_CONFLICT: 'IDEMPOTENCY_CONFLICT',
  RESERVATION_EXHAUSTED: 'RESERVATION_EXHAUSTED',
  KYC_REQUIRED: 'KYC_REQUIRED',
  INVALID_SIGNATURE: 'INVALID_SIGNATURE',
  INTERNAL_ERROR: 'INTERNAL_ERROR',
  CURRENCY_MISMATCH: 'CURRENCY_MISMATCH',
} as const;
