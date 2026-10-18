<?php

namespace App\Enums;

enum AuditAction: string
{
    // Authentication & Identity
    case LOGIN_SUCCESS = 'LOGIN_SUCCESS';
    case LOGIN_FAILED = 'LOGIN_FAILED';
    case LOGOUT = 'LOGOUT';
    case REGISTER_BRAND = 'REGISTER_BRAND';
    case REGISTER_MARKETER = 'REGISTER_MARKETER';
    case PASSWORD_CHANGE = 'PASSWORD_CHANGE';
    case PASSWORD_RESET_REQUEST = 'PASSWORD_RESET_REQUEST';
    case PASSWORD_RESET_COMPLETE = 'PASSWORD_RESET_COMPLETE';
    case EMAIL_VERIFIED = 'EMAIL_VERIFIED';
    case SUPER_ADMIN_BOOTSTRAP = 'SUPER_ADMIN_BOOTSTRAP';
    case USER_PROFILE_UPDATED = 'USER_PROFILE_UPDATED';
    case USER_STATUS_CHANGED = 'USER_STATUS_CHANGED';

    // Security & Authorization
    case UNAUTHORIZED_ACCESS_ATTEMPT = 'UNAUTHORIZED_ACCESS_ATTEMPT';
    case ADMIN_SETTINGS_CHANGED = 'ADMIN_SETTINGS_CHANGED';
    case ROLE_CREATED = 'ROLE_CREATED';
    case ROLE_UPDATED = 'ROLE_UPDATED';
    case ROLE_DELETED = 'ROLE_DELETED';
    case PERMISSIONS_ASSIGNED = 'PERMISSIONS_ASSIGNED';
    case USER_ROLES_ASSIGNED = 'USER_ROLES_ASSIGNED';

    // Campaigns & Applications
    case CAMPAIGN_CREATED = 'CAMPAIGN_CREATED';
    case CAMPAIGN_UPDATED = 'CAMPAIGN_UPDATED';
    case CAMPAIGN_STATUS_CHANGED = 'CAMPAIGN_STATUS_CHANGED';
    case CAMPAIGN_APPLICATION_SUBMITTED = 'CAMPAIGN_APPLICATION_SUBMITTED';
    case CAMPAIGN_APPLICATION_APPROVED = 'CAMPAIGN_APPLICATION_APPROVED';
    case CAMPAIGN_APPLICATION_REJECTED = 'CAMPAIGN_APPLICATION_REJECTED';

    // Phase 7: Campaign Terms Lock, Versioning & Change Requests
    case CAMPAIGN_TERMS_VERSION_CREATED = 'CAMPAIGN_TERMS_VERSION_CREATED';
    case CAMPAIGN_TERMS_LOCKED = 'CAMPAIGN_TERMS_LOCKED';
    case CAMPAIGN_TERMS_CHANGE_REQUESTED = 'CAMPAIGN_TERMS_CHANGE_REQUESTED';
    case CAMPAIGN_TERMS_CHANGE_APPROVED = 'CAMPAIGN_TERMS_CHANGE_APPROVED';
    case CAMPAIGN_TERMS_CHANGE_REJECTED = 'CAMPAIGN_TERMS_CHANGE_REJECTED';
    case CAMPAIGN_TERMS_CHANGE_CANCELLED = 'CAMPAIGN_TERMS_CHANGE_CANCELLED';
    case CAMPAIGN_TERMS_ACCEPTED_BY_MARKETER = 'CAMPAIGN_TERMS_ACCEPTED_BY_MARKETER';

    // Phase 8: Premium Campaigns, Subscription Plans & Entitlements
    case SUBSCRIPTION_PLAN_CREATED = 'SUBSCRIPTION_PLAN_CREATED';
    case SUBSCRIPTION_PLAN_UPDATED = 'SUBSCRIPTION_PLAN_UPDATED';
    case SUBSCRIPTION_PLAN_STATUS_CHANGED = 'SUBSCRIPTION_PLAN_STATUS_CHANGED';
    case SUBSCRIPTION_INITIATED = 'SUBSCRIPTION_INITIATED';
    case SUBSCRIPTION_ACTIVATED = 'SUBSCRIPTION_ACTIVATED';
    case SUBSCRIPTION_EXPIRED = 'SUBSCRIPTION_EXPIRED';
    case SUBSCRIPTION_CANCELLED = 'SUBSCRIPTION_CANCELLED';
    case SUBSCRIPTION_SUSPENDED = 'SUBSCRIPTION_SUSPENDED';
    case CAMPAIGN_PREMIUM_STATUS_UPDATED = 'CAMPAIGN_PREMIUM_STATUS_UPDATED';

    // Attribution & Conversions
    case ATTRIBUTION_CLICK_RECORDED = 'ATTRIBUTION_CLICK_RECORDED';
    case CONVERSION_RECEIVED = 'CONVERSION_RECEIVED';
    case CONVERSION_ATTRIBUTED = 'CONVERSION_ATTRIBUTED';
    case CONVERSION_APPROVED = 'CONVERSION_APPROVED';
    case CONVERSION_REJECTED = 'CONVERSION_REJECTED';
    case CONVERSION_CANCELLED = 'CONVERSION_CANCELLED';
    case COMMISSION_CALCULATED = 'COMMISSION_CALCULATED';

    // Phase 9: Fraud Detection & Integrity
    case CONVERSION_FLAGGED_SUSPICIOUS = 'CONVERSION_FLAGGED_SUSPICIOUS';
    case CONVERSION_INVESTIGATED = 'CONVERSION_INVESTIGATED';
    case FRAUD_RULE_TRIGGERED = 'FRAUD_RULE_TRIGGERED';
    case FRAUD_RULE_UPDATED = 'FRAUD_RULE_UPDATED';

    // Phase 4: Wallets & Double-Entry Ledger
    case WALLET_CREATED = 'WALLET_CREATED';
    case WALLET_STATUS_CHANGED = 'WALLET_STATUS_CHANGED';
    case LEDGER_TRANSACTION_POSTED = 'LEDGER_TRANSACTION_POSTED';
    case LEDGER_TRANSACTION_REVERSED = 'LEDGER_TRANSACTION_REVERSED';
    case BUDGET_RESERVED = 'BUDGET_RESERVED';
    case BUDGET_RELEASED = 'BUDGET_RELEASED';
    case FINANCIAL_ADJUSTMENT = 'FINANCIAL_ADJUSTMENT';

    // Phase 5A: Commission Settlement & Payout Requests
    case COMMISSION_SETTLED = 'COMMISSION_SETTLED';
    case COMMISSION_SETTLEMENT_REVERSED = 'COMMISSION_SETTLEMENT_REVERSED';
    case PAYOUT_REQUESTED = 'PAYOUT_REQUESTED';
    case PAYOUT_APPROVED = 'PAYOUT_APPROVED';
    case PAYOUT_REJECTED = 'PAYOUT_REJECTED';
    case PAYOUT_PAID = 'PAYOUT_PAID';

    // Phase 6: Payment Gateway, Deposits, Webhooks & Refunds
    case PAYMENT_DEPOSIT_INITIATED = 'PAYMENT_DEPOSIT_INITIATED';
    case PAYMENT_DEPOSIT_SUCCEEDED = 'PAYMENT_DEPOSIT_SUCCEEDED';
    case PAYMENT_DEPOSIT_FAILED = 'PAYMENT_DEPOSIT_FAILED';
    case PAYMENT_REFUND_REQUESTED = 'PAYMENT_REFUND_REQUESTED';
    case PAYMENT_REFUND_SUCCEEDED = 'PAYMENT_REFUND_SUCCEEDED';
    case PAYMENT_REFUND_FAILED = 'PAYMENT_REFUND_FAILED';
    case PAYMENT_WEBHOOK_PROCESSED = 'PAYMENT_WEBHOOK_PROCESSED';
    case PAYMENT_RECONCILIATION_RUN = 'PAYMENT_RECONCILIATION_RUN';

    /**
     * Human-readable Arabic label for the audit action.
     */
    public function label(): string
    {
        return match ($this) {
            self::LOGIN_SUCCESS => 'تسجيل دخول ناجح',
            self::LOGIN_FAILED => 'محاولة دخول فاشلة',
            self::LOGOUT => 'تسجيل خروج',
            self::REGISTER_BRAND => 'تسجيل علامة تجارية جديدة',
            self::REGISTER_MARKETER => 'تسجيل مسوق / صانع محتوى',
            self::PASSWORD_CHANGE => 'تغيير كلمة المرور',
            self::PASSWORD_RESET_REQUEST => 'طلب استعادة كلمة المرور',
            self::PASSWORD_RESET_COMPLETE => 'إتمام إعادة تعيين كلمة المرور',
            self::EMAIL_VERIFIED => 'تأكيد البريد الإلكتروني',
            self::SUPER_ADMIN_BOOTSTRAP => 'تهيئة حساب المدير العام',
            self::USER_PROFILE_UPDATED => 'تحديث بيانات المستخدم',
            self::USER_STATUS_CHANGED => 'تغيير حالة حساب المستخدم',

            self::UNAUTHORIZED_ACCESS_ATTEMPT => 'محاولة وصول غير مصرح بها',
            self::ADMIN_SETTINGS_CHANGED => 'تعديل إعدادات النظام الإدارية',
            self::ROLE_CREATED => 'إنشاء دور إداري جديد',
            self::ROLE_UPDATED => 'تعديل بيانات الدور الإداري',
            self::ROLE_DELETED => 'حذف دور إداري',
            self::PERMISSIONS_ASSIGNED => 'تحديث صلاحيات الدور الإداري',
            self::USER_ROLES_ASSIGNED => 'إسناد وتعديل أدوار المستخدم',

            self::CAMPAIGN_CREATED => 'إنشاء حملة تسويقية جديدة',
            self::CAMPAIGN_UPDATED => 'تحديث بيانات حملة تسويقية',
            self::CAMPAIGN_STATUS_CHANGED => 'تغيير حالة الحملة التسويقية',
            self::CAMPAIGN_APPLICATION_SUBMITTED => 'تقديم طلب مشاركة بحملة',
            self::CAMPAIGN_APPLICATION_APPROVED => 'اعتماد مسوق في الحملة',
            self::CAMPAIGN_APPLICATION_REJECTED => 'رفض طلب مسوق في الحملة',

            self::CAMPAIGN_TERMS_VERSION_CREATED => 'إنشاء إصدار جديد لشروط الحملة',
            self::CAMPAIGN_TERMS_LOCKED => 'تثبيت الشروط التعاقدية للحملة تلقائياً',
            self::CAMPAIGN_TERMS_CHANGE_REQUESTED => 'تقديم طلب تعديل شروط الحملة',
            self::CAMPAIGN_TERMS_CHANGE_APPROVED => 'اعتماد طلب تعديل شروط الحملة وترقية الإصدار',
            self::CAMPAIGN_TERMS_CHANGE_REJECTED => 'رفض طلب تعديل شروط الحملة',
            self::CAMPAIGN_TERMS_CHANGE_CANCELLED => 'إلغاء طلب تعديل شروط الحملة',
            self::CAMPAIGN_TERMS_ACCEPTED_BY_MARKETER => 'موافقة المسوق على الإصدار الجديد للشروط',

            self::SUBSCRIPTION_PLAN_CREATED => 'إنشاء خطة اشتراك جديدة للمسوقين',
            self::SUBSCRIPTION_PLAN_UPDATED => 'تحديث بيانات خطة الاشتراك',
            self::SUBSCRIPTION_PLAN_STATUS_CHANGED => 'تغيير حالة خطة الاشتراك',
            self::SUBSCRIPTION_INITIATED => 'بدء طلب اشتراك في خطة للمسوق',
            self::SUBSCRIPTION_ACTIVATED => 'تفعيل اشتراك مسوق بعد السداد',
            self::SUBSCRIPTION_EXPIRED => 'انتهاء صلاحية اشتراك مسوق',
            self::SUBSCRIPTION_CANCELLED => 'إلغاء اشتراك مسوق',
            self::SUBSCRIPTION_SUSPENDED => 'تعليق اشتراك مسوق إدارياً',
            self::CAMPAIGN_PREMIUM_STATUS_UPDATED => 'تحديث إعدادات تمييز وأهلية الحملة',

            self::ATTRIBUTION_CLICK_RECORDED => 'تسجيل نقرة إحالة ترويجية',
            self::CONVERSION_RECEIVED => 'استقبال تحويل مبيعات (طلب خارجي)',
            self::CONVERSION_ATTRIBUTED => 'إسناد تحويل مبيعات لمسوق معتمد',
            self::CONVERSION_APPROVED => 'اعتماد تحويل مبيعات وعمولة',
            self::CONVERSION_REJECTED => 'رفض تحويل مبيعات',
            self::CONVERSION_CANCELLED => 'إلغاء تحويل مبيعات معتمد مسبقاً',
            self::COMMISSION_CALCULATED => 'احتساب قيمة العمولة التسويقية',
            self::CONVERSION_FLAGGED_SUSPICIOUS => 'اشتباه أمني في تحويل مبيعات',
            self::CONVERSION_INVESTIGATED => 'تدقيق وتحقيق إداري في تحويل',
            self::FRAUD_RULE_TRIGGERED => 'تفعيل مؤشر كشف احتيال',
            self::FRAUD_RULE_UPDATED => 'تحديث إعدادات قواعد كشف الاحتيال',

            self::WALLET_CREATED => 'إنشاء محفظة مالية جديدة',
            self::WALLET_STATUS_CHANGED => 'تغيير حالة المحفظة المالية',
            self::LEDGER_TRANSACTION_POSTED => 'ترحيل معاملة دفتر أستاذ محاسبي',
            self::LEDGER_TRANSACTION_REVERSED => 'عكس معاملة دفتر أستاذ (قيد تسوية)',
            self::BUDGET_RESERVED => 'حجز ميزانية حملة في المحفظة',
            self::BUDGET_RELEASED => 'تحرير ميزانية محجوزة للحملة',
            self::FINANCIAL_ADJUSTMENT => 'تسوية مالية إدارية مسجلة',

            self::COMMISSION_SETTLED => 'تسوية وصرف عمولة تحويل للمسوق',
            self::COMMISSION_SETTLEMENT_REVERSED => 'عكس تسوية عمولة تحويل ملغي',
            self::PAYOUT_REQUESTED => 'تقديم طلب سحب أرباح للمسوق',
            self::PAYOUT_APPROVED => 'اعتماد طلب سحب أرباح المسوق',
            self::PAYOUT_REJECTED => 'رفض طلب سحب أرباح وإعادة الرصيد',
            self::PAYOUT_PAID => 'تأكيد تحويل وصرف أرباح المسوق',

            // Payment & Deposits
            self::PAYMENT_DEPOSIT_INITIATED => 'بدء عملية إيداع وشحن محفظة',
            self::PAYMENT_DEPOSIT_SUCCEEDED => 'نجاح عملية إيداع وشحن محفظة',
            self::PAYMENT_DEPOSIT_FAILED => 'فشل عملية إيداع وشحن محفظة',
            self::PAYMENT_REFUND_REQUESTED => 'طلب استرداد دفعة مالية',
            self::PAYMENT_REFUND_SUCCEEDED => 'نجاح استرداد دفعة مالية',
            self::PAYMENT_REFUND_FAILED => 'فشل استرداد دفعة مالية',
            self::PAYMENT_WEBHOOK_PROCESSED => 'معالجة إشعار دفع خارجي (Webhook)',
            self::PAYMENT_RECONCILIATION_RUN => 'تشغيل تسوية ومطابقة دفع',

            default => $this->value,
        };
    }

    /**
     * Category grouping for filtering in the audit UI.
     */
    public function category(): string
    {
        return match ($this) {
            self::LOGIN_SUCCESS, self::LOGIN_FAILED, self::LOGOUT,
            self::REGISTER_BRAND, self::REGISTER_MARKETER,
            self::PASSWORD_CHANGE, self::PASSWORD_RESET_REQUEST,
            self::PASSWORD_RESET_COMPLETE, self::EMAIL_VERIFIED,
            self::SUPER_ADMIN_BOOTSTRAP, self::USER_PROFILE_UPDATED,
            self::USER_STATUS_CHANGED => 'الهوية والمستخدمين',

            self::UNAUTHORIZED_ACCESS_ATTEMPT, self::ADMIN_SETTINGS_CHANGED,
            self::ROLE_CREATED, self::ROLE_UPDATED, self::ROLE_DELETED,
            self::PERMISSIONS_ASSIGNED, self::USER_ROLES_ASSIGNED => 'الأدوار والصلاحيات والأمان',

            self::CAMPAIGN_CREATED, self::CAMPAIGN_UPDATED,
            self::CAMPAIGN_STATUS_CHANGED, self::CAMPAIGN_APPLICATION_SUBMITTED,
            self::CAMPAIGN_APPLICATION_APPROVED,
            self::CAMPAIGN_APPLICATION_REJECTED,
            self::CAMPAIGN_TERMS_VERSION_CREATED,
            self::CAMPAIGN_TERMS_LOCKED,
            self::CAMPAIGN_TERMS_CHANGE_REQUESTED,
            self::CAMPAIGN_TERMS_CHANGE_APPROVED,
            self::CAMPAIGN_TERMS_CHANGE_REJECTED,
            self::CAMPAIGN_TERMS_CHANGE_CANCELLED,
            self::CAMPAIGN_TERMS_ACCEPTED_BY_MARKETER,
            self::CAMPAIGN_PREMIUM_STATUS_UPDATED => 'الحملات والشروط التعاقدية',

            self::SUBSCRIPTION_PLAN_CREATED, self::SUBSCRIPTION_PLAN_UPDATED,
            self::SUBSCRIPTION_PLAN_STATUS_CHANGED, self::SUBSCRIPTION_INITIATED,
            self::SUBSCRIPTION_ACTIVATED, self::SUBSCRIPTION_EXPIRED,
            self::SUBSCRIPTION_CANCELLED, self::SUBSCRIPTION_SUSPENDED => 'الاشتراكات والخطط المميزة',

            self::ATTRIBUTION_CLICK_RECORDED, self::CONVERSION_RECEIVED,
            self::CONVERSION_ATTRIBUTED, self::CONVERSION_APPROVED,
            self::CONVERSION_REJECTED, self::CONVERSION_CANCELLED,
            self::COMMISSION_CALCULATED,
            self::CONVERSION_FLAGGED_SUSPICIOUS, self::CONVERSION_INVESTIGATED,
            self::FRAUD_RULE_TRIGGERED, self::FRAUD_RULE_UPDATED => 'التتبع ومكافحة الاحتيال والتحويلات',

            self::WALLET_CREATED, self::WALLET_STATUS_CHANGED,
            self::LEDGER_TRANSACTION_POSTED, self::LEDGER_TRANSACTION_REVERSED,
            self::BUDGET_RESERVED, self::BUDGET_RELEASED,
            self::FINANCIAL_ADJUSTMENT, self::COMMISSION_SETTLED,
            self::COMMISSION_SETTLEMENT_REVERSED, self::PAYOUT_REQUESTED,
            self::PAYOUT_APPROVED, self::PAYOUT_REJECTED,
            self::PAYOUT_PAID => 'المالية ودفتر القيود المحاسبية',

            self::PAYMENT_DEPOSIT_INITIATED, self::PAYMENT_DEPOSIT_SUCCEEDED,
            self::PAYMENT_DEPOSIT_FAILED, self::PAYMENT_REFUND_REQUESTED,
            self::PAYMENT_REFUND_SUCCEEDED, self::PAYMENT_REFUND_FAILED,
            self::PAYMENT_WEBHOOK_PROCESSED, self::PAYMENT_RECONCILIATION_RUN => 'المدفوعات والإيداعات',

            default => 'عمليات أخرى',
        };
    }

    /**
     * Badge CSS class for the audit action.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::LOGIN_SUCCESS, self::CAMPAIGN_APPLICATION_APPROVED,
            self::CONVERSION_APPROVED, self::LEDGER_TRANSACTION_POSTED,
            self::WALLET_CREATED, self::COMMISSION_SETTLED,
            self::CAMPAIGN_TERMS_CHANGE_APPROVED,
            self::CAMPAIGN_TERMS_ACCEPTED_BY_MARKETER,
            self::SUBSCRIPTION_ACTIVATED,
            self::PAYOUT_PAID => 'bg-emerald-50 text-emerald-700 border-emerald-200',

            self::LOGIN_FAILED, self::CAMPAIGN_APPLICATION_REJECTED,
            self::CONVERSION_REJECTED, self::PAYOUT_REJECTED,
            self::CAMPAIGN_TERMS_CHANGE_REJECTED,
            self::SUBSCRIPTION_CANCELLED, self::SUBSCRIPTION_SUSPENDED,
            self::UNAUTHORIZED_ACCESS_ATTEMPT => 'bg-rose-50 text-rose-700 border-rose-200',

            self::ROLE_CREATED, self::ROLE_UPDATED, self::PERMISSIONS_ASSIGNED, self::USER_ROLES_ASSIGNED,
            self::CAMPAIGN_CREATED, self::REGISTER_BRAND,
            self::REGISTER_MARKETER, self::BUDGET_RELEASED,
            self::CAMPAIGN_TERMS_VERSION_CREATED,
            self::SUBSCRIPTION_PLAN_CREATED,
            self::PAYOUT_APPROVED => 'bg-blue-50 text-blue-700 border-blue-200',

            self::CAMPAIGN_UPDATED, self::USER_PROFILE_UPDATED,
            self::CAMPAIGN_STATUS_CHANGED, self::BUDGET_RESERVED,
            self::CAMPAIGN_TERMS_LOCKED,
            self::CAMPAIGN_TERMS_CHANGE_REQUESTED,
            self::SUBSCRIPTION_INITIATED, self::SUBSCRIPTION_PLAN_UPDATED,
            self::CAMPAIGN_PREMIUM_STATUS_UPDATED,
            self::CONVERSION_FLAGGED_SUSPICIOUS, self::FRAUD_RULE_TRIGGERED,
            self::PASSWORD_CHANGE, self::PAYOUT_REQUESTED => 'bg-amber-50 text-amber-700 border-amber-200',

            self::CONVERSION_CANCELLED, self::WALLET_STATUS_CHANGED,
            self::CAMPAIGN_TERMS_CHANGE_CANCELLED,
            self::SUBSCRIPTION_EXPIRED, self::SUBSCRIPTION_PLAN_STATUS_CHANGED,
            self::FINANCIAL_ADJUSTMENT => 'bg-slate-100 text-slate-700 border-slate-300',

            self::LEDGER_TRANSACTION_REVERSED,
            self::COMMISSION_SETTLEMENT_REVERSED => 'bg-purple-50 text-purple-700 border-purple-200',

            default => 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
}
