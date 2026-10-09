-- CreateSchema
CREATE SCHEMA IF NOT EXISTS "public";

-- CreateEnum
CREATE TYPE "UserRole" AS ENUM ('SUPER_ADMIN', 'BRAND', 'MARKETER');

-- CreateEnum
CREATE TYPE "UserStatus" AS ENUM ('ACTIVE', 'SUSPENDED', 'PENDING_VERIFICATION');

-- CreateEnum
CREATE TYPE "SpecializationType" AS ENUM ('CREATOR', 'AFFILIATE', 'MEDIA_BUYER');

-- CreateEnum
CREATE TYPE "KycStatus" AS ENUM ('NOT_STARTED', 'PENDING', 'VERIFIED', 'REJECTED');

-- CreateEnum
CREATE TYPE "StorePlatform" AS ENUM ('SALLA', 'ZID', 'SHOPIFY', 'CUSTOM_WEBHOOK');

-- CreateEnum
CREATE TYPE "CampaignStatus" AS ENUM ('DRAFT', 'PENDING_REVIEW', 'ACTIVE', 'PAUSED', 'COMPLETED', 'CANCELLED', 'REJECTED');

-- CreateEnum
CREATE TYPE "CommissionType" AS ENUM ('PERCENTAGE', 'FLAT_FEE');

-- CreateEnum
CREATE TYPE "ApplicationMode" AS ENUM ('AUTO_APPROVE', 'MANUAL_REVIEW');

-- CreateEnum
CREATE TYPE "ApplicationStatus" AS ENUM ('SUBMITTED', 'APPROVED', 'REJECTED', 'WITHDRAWN', 'SUSPENDED');

-- CreateEnum
CREATE TYPE "ConversionStatus" AS ENUM ('PENDING', 'CONFIRMED', 'CANCELLED', 'REFUNDED', 'PARTIALLY_REFUNDED');

-- CreateEnum
CREATE TYPE "CommissionStatus" AS ENUM ('PENDING', 'AVAILABLE', 'PAID', 'VOIDED', 'ADJUSTED');

-- CreateEnum
CREATE TYPE "FlatFeeRefundPolicy" AS ENUM ('PROPORTIONAL', 'FULL_VOID', 'THRESHOLD_BASED');

-- CreateEnum
CREATE TYPE "BudgetReservationStatus" AS ENUM ('ACTIVE', 'EXHAUSTED', 'RELEASED', 'CANCELLED');

-- CreateEnum
CREATE TYPE "BrandPaymentStatus" AS ENUM ('INITIATED', 'PENDING', 'SUCCEEDED', 'FAILED', 'REFUNDED');

-- CreateEnum
CREATE TYPE "WithdrawalStatus" AS ENUM ('REQUESTED', 'UNDER_REVIEW', 'APPROVED', 'PROCESSING', 'PAID', 'FAILED', 'REJECTED', 'CANCELLED');

-- CreateEnum
CREATE TYPE "LedgerAccountType" AS ENUM ('ASSET', 'LIABILITY', 'EQUITY', 'REVENUE', 'EXPENSE');

-- CreateEnum
CREATE TYPE "LedgerTransactionType" AS ENUM ('BRAND_DEPOSIT', 'CAMPAIGN_BUDGET_RESERVE', 'CAMPAIGN_BUDGET_RELEASE', 'COMMISSION_RESERVATION', 'COMMISSION_MATURITY', 'PLATFORM_FEE_RECOGNITION', 'FULL_REFUND_REVERSAL', 'PARTIAL_REFUND_ADJUSTMENT', 'POST_PAYOUT_CHARGEBACK', 'WITHDRAWAL_INITIATED', 'WITHDRAWAL_SETTLED', 'WITHDRAWAL_FAILED_REVERSAL', 'MANUAL_ADJUSTMENT');

-- CreateTable
CREATE TABLE "users" (
    "id" TEXT NOT NULL,
    "email" TEXT NOT NULL,
    "passwordHash" TEXT NOT NULL,
    "role" "UserRole" NOT NULL,
    "status" "UserStatus" NOT NULL DEFAULT 'ACTIVE',
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "users_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "brand_profiles" (
    "id" TEXT NOT NULL,
    "userId" TEXT NOT NULL,
    "companyName" TEXT NOT NULL,
    "legalName" TEXT,
    "commercialRegNo" TEXT,
    "websiteUrl" TEXT NOT NULL,
    "logoUrl" TEXT,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "brand_profiles_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "marketer_profiles" (
    "id" TEXT NOT NULL,
    "userId" TEXT NOT NULL,
    "fullName" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "marketer_profiles_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "marketer_specializations" (
    "id" TEXT NOT NULL,
    "marketerProfileId" TEXT NOT NULL,
    "specialization" "SpecializationType" NOT NULL,
    "isPrimary" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "marketer_specializations_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "kyc_profiles" (
    "id" TEXT NOT NULL,
    "marketerProfileId" TEXT NOT NULL,
    "status" "KycStatus" NOT NULL DEFAULT 'NOT_STARTED',
    "legalFullName" TEXT,
    "nationalIdEncrypted" TEXT,
    "nationalIdIv" TEXT,
    "nationalIdTag" TEXT,
    "nationalIdFingerprint" TEXT,
    "documentType" TEXT,
    "documentStoragePath" TEXT,
    "rejectionReason" TEXT,
    "verifiedAt" TIMESTAMP(3),
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "kyc_profiles_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "payout_methods" (
    "id" TEXT NOT NULL,
    "marketerProfileId" TEXT NOT NULL,
    "beneficiaryNameEncrypted" TEXT NOT NULL,
    "beneficiaryNameIv" TEXT NOT NULL,
    "beneficiaryNameTag" TEXT NOT NULL,
    "bankName" TEXT NOT NULL,
    "ibanEncrypted" TEXT NOT NULL,
    "ibanIv" TEXT NOT NULL,
    "ibanTag" TEXT NOT NULL,
    "ibanFingerprint" TEXT NOT NULL,
    "maskedIban" TEXT NOT NULL,
    "isDefault" BOOLEAN NOT NULL DEFAULT false,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "payout_methods_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "social_accounts" (
    "id" TEXT NOT NULL,
    "marketerProfileId" TEXT NOT NULL,
    "platform" TEXT NOT NULL,
    "handle" TEXT NOT NULL,
    "followerCount" INTEGER NOT NULL DEFAULT 0,
    "profileUrl" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "social_accounts_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "brand_stores" (
    "id" TEXT NOT NULL,
    "brandProfileId" TEXT NOT NULL,
    "platform" "StorePlatform" NOT NULL DEFAULT 'CUSTOM_WEBHOOK',
    "storeDomain" TEXT NOT NULL,
    "encryptedWebhookSecret" TEXT NOT NULL,
    "webhookSecretIv" TEXT NOT NULL,
    "webhookSecretTag" TEXT NOT NULL,
    "webhookSecretFingerprint" TEXT NOT NULL,
    "previousEncryptedSecret" TEXT,
    "previousSecretExpiresAt" TIMESTAMP(3),
    "secretKeyRotatedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "brand_stores_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "products" (
    "id" TEXT NOT NULL,
    "brandProfileId" TEXT NOT NULL,
    "title" TEXT NOT NULL,
    "sku" TEXT,
    "externalId" TEXT,
    "price" DECIMAL(12,2) NOT NULL,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "productUrl" TEXT NOT NULL,
    "imageUrl" TEXT,
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "products_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "campaigns" (
    "id" TEXT NOT NULL,
    "brandProfileId" TEXT NOT NULL,
    "title" TEXT NOT NULL,
    "description" TEXT NOT NULL,
    "status" "CampaignStatus" NOT NULL DEFAULT 'DRAFT',
    "commissionType" "CommissionType" NOT NULL DEFAULT 'PERCENTAGE',
    "commissionValue" DECIMAL(10,2) NOT NULL,
    "platformFeeRate" DECIMAL(5,4) NOT NULL DEFAULT 0.02,
    "flatFeeRefundPolicy" "FlatFeeRefundPolicy" NOT NULL DEFAULT 'PROPORTIONAL',
    "flatFeeRefundThreshold" DECIMAL(10,2),
    "cookieDurationDays" INTEGER NOT NULL DEFAULT 30,
    "lockPeriodDays" INTEGER NOT NULL DEFAULT 30,
    "budgetCap" DECIMAL(14,2) NOT NULL,
    "isAllProducts" BOOLEAN NOT NULL DEFAULT true,
    "applicationMode" "ApplicationMode" NOT NULL DEFAULT 'MANUAL_REVIEW',
    "trafficRestrictions" TEXT,
    "geoRestrictions" TEXT NOT NULL DEFAULT 'SA',
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "startsAt" TIMESTAMP(3),
    "endsAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "campaigns_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "campaign_budget_reservations" (
    "id" TEXT NOT NULL,
    "campaignId" TEXT NOT NULL,
    "brandProfileId" TEXT NOT NULL,
    "reservedAmount" DECIMAL(14,2) NOT NULL,
    "consumedAmount" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "releasedAmount" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "status" "BudgetReservationStatus" NOT NULL DEFAULT 'ACTIVE',
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "expiresAt" TIMESTAMP(3),
    "releasedAt" TIMESTAMP(3),
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "campaign_budget_reservations_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "campaign_products" (
    "id" TEXT NOT NULL,
    "campaignId" TEXT NOT NULL,
    "productId" TEXT NOT NULL,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "campaign_products_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "campaign_applications" (
    "id" TEXT NOT NULL,
    "campaignId" TEXT NOT NULL,
    "marketerProfileId" TEXT NOT NULL,
    "status" "ApplicationStatus" NOT NULL DEFAULT 'SUBMITTED',
    "pitchNote" TEXT,
    "reviewNote" TEXT,
    "appliedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "reviewedAt" TIMESTAMP(3),

    CONSTRAINT "campaign_applications_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "tracking_links" (
    "id" TEXT NOT NULL,
    "campaignId" TEXT NOT NULL,
    "marketerProfileId" TEXT NOT NULL,
    "campaignApplicationId" TEXT NOT NULL,
    "shortCode" TEXT NOT NULL,
    "destinationUrl" TEXT NOT NULL,
    "clicksCount" INTEGER NOT NULL DEFAULT 0,
    "conversionsCount" INTEGER NOT NULL DEFAULT 0,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "tracking_links_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "clicks" (
    "id" TEXT NOT NULL,
    "trackingLinkId" TEXT NOT NULL,
    "ipHash" TEXT NOT NULL,
    "userAgent" TEXT NOT NULL,
    "referer" TEXT,
    "geoCountry" TEXT DEFAULT 'SA',
    "sub1" TEXT,
    "sub2" TEXT,
    "sub3" TEXT,
    "externalClickId" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "clicks_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "conversions" (
    "id" TEXT NOT NULL,
    "brandStoreId" TEXT NOT NULL,
    "clickId" TEXT,
    "externalOrderId" TEXT NOT NULL,
    "subtotalAmount" DECIMAL(12,2) NOT NULL,
    "discountAmount" DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    "shippingAmount" DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    "taxAmount" DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    "netCommissionBase" DECIMAL(12,2) NOT NULL,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "status" "ConversionStatus" NOT NULL DEFAULT 'PENDING',
    "attributedCampaignId" TEXT NOT NULL,
    "attributedMarketerId" TEXT NOT NULL,
    "attributedTrackingLinkId" TEXT NOT NULL,
    "attributionClickId" TEXT NOT NULL,
    "attributionMethod" TEXT NOT NULL DEFAULT 'LAST_TOUCH',
    "attributionWindowDaysSnapshot" INTEGER NOT NULL DEFAULT 30,
    "attributedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "conversions_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "conversion_items" (
    "id" TEXT NOT NULL,
    "conversionId" TEXT NOT NULL,
    "productId" TEXT,
    "externalProductId" TEXT,
    "title" TEXT NOT NULL,
    "quantity" INTEGER NOT NULL DEFAULT 1,
    "unitPrice" DECIMAL(12,2) NOT NULL,
    "discountAmount" DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    "eligibleAmount" DECIMAL(12,2) NOT NULL,
    "isEligible" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "conversion_items_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "commissions" (
    "id" TEXT NOT NULL,
    "conversionId" TEXT NOT NULL,
    "campaignId" TEXT NOT NULL,
    "marketerProfileId" TEXT NOT NULL,
    "appliedRateType" "CommissionType" NOT NULL,
    "appliedRateValue" DECIMAL(10,2) NOT NULL,
    "commissionBase" DECIMAL(12,2) NOT NULL,
    "marketerAmount" DECIMAL(12,2) NOT NULL,
    "platformFeeAmount" DECIMAL(12,2) NOT NULL,
    "brandTotalCost" DECIMAL(12,2) NOT NULL,
    "status" "CommissionStatus" NOT NULL DEFAULT 'PENDING',
    "matureAt" TIMESTAMP(3) NOT NULL,
    "voidReason" TEXT,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "commissions_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "brand_payments" (
    "id" TEXT NOT NULL,
    "brandProfileId" TEXT NOT NULL,
    "ledgerTransactionId" TEXT,
    "amount" DECIMAL(14,2) NOT NULL,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "status" "BrandPaymentStatus" NOT NULL DEFAULT 'INITIATED',
    "paymentProvider" TEXT NOT NULL,
    "gatewayReference" TEXT,
    "idempotencyKey" TEXT NOT NULL,
    "failureReason" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "settledAt" TIMESTAMP(3),

    CONSTRAINT "brand_payments_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "wallets" (
    "id" TEXT NOT NULL,
    "brandProfileId" TEXT,
    "marketerProfileId" TEXT,
    "cachedPending" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "cachedAvailable" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "cachedReserved" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "cachedWithdrawn" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "cachedReceivable" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "updatedAt" TIMESTAMP(3) NOT NULL,

    CONSTRAINT "wallets_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "ledger_accounts" (
    "id" TEXT NOT NULL,
    "code" TEXT NOT NULL,
    "name" TEXT NOT NULL,
    "type" "LedgerAccountType" NOT NULL,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "isActive" BOOLEAN NOT NULL DEFAULT true,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "ledger_accounts_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "ledger_transactions" (
    "id" TEXT NOT NULL,
    "type" "LedgerTransactionType" NOT NULL,
    "description" TEXT NOT NULL,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "brandProfileId" TEXT,
    "campaignId" TEXT,
    "campaignReservationId" TEXT,
    "conversionId" TEXT,
    "commissionId" TEXT,
    "referenceId" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "ledger_transactions_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "ledger_entries" (
    "id" TEXT NOT NULL,
    "ledgerTransactionId" TEXT NOT NULL,
    "ledgerAccountId" TEXT NOT NULL,
    "debitAmount" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "creditAmount" DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "ledger_entries_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "withdrawal_requests" (
    "id" TEXT NOT NULL,
    "walletId" TEXT NOT NULL,
    "payoutMethodId" TEXT NOT NULL,
    "amount" DECIMAL(12,2) NOT NULL,
    "currency" TEXT NOT NULL DEFAULT 'SAR',
    "status" "WithdrawalStatus" NOT NULL DEFAULT 'REQUESTED',
    "beneficiarySnapshotEncrypted" TEXT NOT NULL,
    "beneficiarySnapshotIv" TEXT NOT NULL,
    "beneficiarySnapshotTag" TEXT NOT NULL,
    "ibanSnapshotEncrypted" TEXT NOT NULL,
    "ibanSnapshotIv" TEXT NOT NULL,
    "ibanSnapshotTag" TEXT NOT NULL,
    "maskedIbanSnapshot" TEXT NOT NULL,
    "payoutReference" TEXT,
    "adminNotes" TEXT,
    "requestedAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,
    "processedAt" TIMESTAMP(3),

    CONSTRAINT "withdrawal_requests_pkey" PRIMARY KEY ("id")
);

-- CreateTable
CREATE TABLE "audit_logs" (
    "id" TEXT NOT NULL,
    "userId" TEXT,
    "action" TEXT NOT NULL,
    "entityType" TEXT NOT NULL,
    "entityId" TEXT NOT NULL,
    "payloadDiff" JSONB,
    "ipHash" TEXT,
    "createdAt" TIMESTAMP(3) NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT "audit_logs_pkey" PRIMARY KEY ("id")
);

-- CreateIndex
CREATE UNIQUE INDEX "users_email_key" ON "users"("email");

-- CreateIndex
CREATE UNIQUE INDEX "brand_profiles_userId_key" ON "brand_profiles"("userId");

-- CreateIndex
CREATE UNIQUE INDEX "marketer_profiles_userId_key" ON "marketer_profiles"("userId");

-- CreateIndex
CREATE INDEX "marketer_specializations_marketerProfileId_isPrimary_idx" ON "marketer_specializations"("marketerProfileId", "isPrimary");

-- CreateIndex
CREATE UNIQUE INDEX "marketer_specializations_marketerProfileId_specialization_key" ON "marketer_specializations"("marketerProfileId", "specialization");

-- CreateIndex
CREATE UNIQUE INDEX "kyc_profiles_marketerProfileId_key" ON "kyc_profiles"("marketerProfileId");

-- CreateIndex
CREATE INDEX "kyc_profiles_nationalIdFingerprint_idx" ON "kyc_profiles"("nationalIdFingerprint");

-- CreateIndex
CREATE UNIQUE INDEX "payout_methods_marketerProfileId_ibanFingerprint_key" ON "payout_methods"("marketerProfileId", "ibanFingerprint");

-- CreateIndex
CREATE UNIQUE INDEX "social_accounts_marketerProfileId_platform_handle_key" ON "social_accounts"("marketerProfileId", "platform", "handle");

-- CreateIndex
CREATE INDEX "brand_stores_webhookSecretFingerprint_idx" ON "brand_stores"("webhookSecretFingerprint");

-- CreateIndex
CREATE UNIQUE INDEX "brand_stores_brandProfileId_storeDomain_key" ON "brand_stores"("brandProfileId", "storeDomain");

-- CreateIndex
CREATE UNIQUE INDEX "products_brandProfileId_sku_key" ON "products"("brandProfileId", "sku");

-- CreateIndex
CREATE INDEX "campaigns_status_idx" ON "campaigns"("status");

-- CreateIndex
CREATE INDEX "campaigns_brandProfileId_status_idx" ON "campaigns"("brandProfileId", "status");

-- CreateIndex
CREATE INDEX "campaign_budget_reservations_campaignId_status_idx" ON "campaign_budget_reservations"("campaignId", "status");

-- CreateIndex
CREATE UNIQUE INDEX "campaign_products_campaignId_productId_key" ON "campaign_products"("campaignId", "productId");

-- CreateIndex
CREATE INDEX "campaign_applications_status_idx" ON "campaign_applications"("status");

-- CreateIndex
CREATE UNIQUE INDEX "campaign_applications_campaignId_marketerProfileId_key" ON "campaign_applications"("campaignId", "marketerProfileId");

-- CreateIndex
CREATE UNIQUE INDEX "tracking_links_campaignApplicationId_key" ON "tracking_links"("campaignApplicationId");

-- CreateIndex
CREATE UNIQUE INDEX "tracking_links_shortCode_key" ON "tracking_links"("shortCode");

-- CreateIndex
CREATE INDEX "tracking_links_shortCode_idx" ON "tracking_links"("shortCode");

-- CreateIndex
CREATE INDEX "clicks_trackingLinkId_createdAt_idx" ON "clicks"("trackingLinkId", "createdAt");

-- CreateIndex
CREATE INDEX "clicks_createdAt_idx" ON "clicks"("createdAt");

-- CreateIndex
CREATE INDEX "conversions_clickId_idx" ON "conversions"("clickId");

-- CreateIndex
CREATE INDEX "conversions_attributedMarketerId_status_idx" ON "conversions"("attributedMarketerId", "status");

-- CreateIndex
CREATE UNIQUE INDEX "conversions_brandStoreId_externalOrderId_key" ON "conversions"("brandStoreId", "externalOrderId");

-- CreateIndex
CREATE INDEX "conversion_items_conversionId_idx" ON "conversion_items"("conversionId");

-- CreateIndex
CREATE UNIQUE INDEX "commissions_conversionId_key" ON "commissions"("conversionId");

-- CreateIndex
CREATE INDEX "commissions_status_matureAt_idx" ON "commissions"("status", "matureAt");

-- CreateIndex
CREATE INDEX "commissions_marketerProfileId_status_idx" ON "commissions"("marketerProfileId", "status");

-- CreateIndex
CREATE UNIQUE INDEX "brand_payments_ledgerTransactionId_key" ON "brand_payments"("ledgerTransactionId");

-- CreateIndex
CREATE UNIQUE INDEX "brand_payments_idempotencyKey_key" ON "brand_payments"("idempotencyKey");

-- CreateIndex
CREATE INDEX "brand_payments_brandProfileId_status_idx" ON "brand_payments"("brandProfileId", "status");

-- CreateIndex
CREATE UNIQUE INDEX "wallets_brandProfileId_key" ON "wallets"("brandProfileId");

-- CreateIndex
CREATE UNIQUE INDEX "wallets_marketerProfileId_key" ON "wallets"("marketerProfileId");

-- CreateIndex
CREATE UNIQUE INDEX "ledger_accounts_code_key" ON "ledger_accounts"("code");

-- CreateIndex
CREATE INDEX "ledger_transactions_type_referenceId_idx" ON "ledger_transactions"("type", "referenceId");

-- CreateIndex
CREATE INDEX "ledger_transactions_brandProfileId_createdAt_idx" ON "ledger_transactions"("brandProfileId", "createdAt");

-- CreateIndex
CREATE INDEX "ledger_transactions_createdAt_idx" ON "ledger_transactions"("createdAt");

-- CreateIndex
CREATE INDEX "ledger_entries_ledgerAccountId_createdAt_idx" ON "ledger_entries"("ledgerAccountId", "createdAt");

-- CreateIndex
CREATE INDEX "withdrawal_requests_status_requestedAt_idx" ON "withdrawal_requests"("status", "requestedAt");

-- CreateIndex
CREATE INDEX "audit_logs_entityType_entityId_idx" ON "audit_logs"("entityType", "entityId");

-- CreateIndex
CREATE INDEX "audit_logs_createdAt_idx" ON "audit_logs"("createdAt");

-- AddForeignKey
ALTER TABLE "brand_profiles" ADD CONSTRAINT "brand_profiles_userId_fkey" FOREIGN KEY ("userId") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "marketer_profiles" ADD CONSTRAINT "marketer_profiles_userId_fkey" FOREIGN KEY ("userId") REFERENCES "users"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "marketer_specializations" ADD CONSTRAINT "marketer_specializations_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "kyc_profiles" ADD CONSTRAINT "kyc_profiles_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "payout_methods" ADD CONSTRAINT "payout_methods_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "social_accounts" ADD CONSTRAINT "social_accounts_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "brand_stores" ADD CONSTRAINT "brand_stores_brandProfileId_fkey" FOREIGN KEY ("brandProfileId") REFERENCES "brand_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "products" ADD CONSTRAINT "products_brandProfileId_fkey" FOREIGN KEY ("brandProfileId") REFERENCES "brand_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "campaigns" ADD CONSTRAINT "campaigns_brandProfileId_fkey" FOREIGN KEY ("brandProfileId") REFERENCES "brand_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "campaign_budget_reservations" ADD CONSTRAINT "campaign_budget_reservations_campaignId_fkey" FOREIGN KEY ("campaignId") REFERENCES "campaigns"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "campaign_budget_reservations" ADD CONSTRAINT "campaign_budget_reservations_brandProfileId_fkey" FOREIGN KEY ("brandProfileId") REFERENCES "brand_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "campaign_products" ADD CONSTRAINT "campaign_products_campaignId_fkey" FOREIGN KEY ("campaignId") REFERENCES "campaigns"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "campaign_products" ADD CONSTRAINT "campaign_products_productId_fkey" FOREIGN KEY ("productId") REFERENCES "products"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "campaign_applications" ADD CONSTRAINT "campaign_applications_campaignId_fkey" FOREIGN KEY ("campaignId") REFERENCES "campaigns"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "campaign_applications" ADD CONSTRAINT "campaign_applications_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "tracking_links" ADD CONSTRAINT "tracking_links_campaignId_fkey" FOREIGN KEY ("campaignId") REFERENCES "campaigns"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "tracking_links" ADD CONSTRAINT "tracking_links_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "tracking_links" ADD CONSTRAINT "tracking_links_campaignApplicationId_fkey" FOREIGN KEY ("campaignApplicationId") REFERENCES "campaign_applications"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "clicks" ADD CONSTRAINT "clicks_trackingLinkId_fkey" FOREIGN KEY ("trackingLinkId") REFERENCES "tracking_links"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "conversions" ADD CONSTRAINT "conversions_brandStoreId_fkey" FOREIGN KEY ("brandStoreId") REFERENCES "brand_stores"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "conversions" ADD CONSTRAINT "conversions_clickId_fkey" FOREIGN KEY ("clickId") REFERENCES "clicks"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "conversion_items" ADD CONSTRAINT "conversion_items_conversionId_fkey" FOREIGN KEY ("conversionId") REFERENCES "conversions"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "conversion_items" ADD CONSTRAINT "conversion_items_productId_fkey" FOREIGN KEY ("productId") REFERENCES "products"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "commissions" ADD CONSTRAINT "commissions_conversionId_fkey" FOREIGN KEY ("conversionId") REFERENCES "conversions"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "commissions" ADD CONSTRAINT "commissions_campaignId_fkey" FOREIGN KEY ("campaignId") REFERENCES "campaigns"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "commissions" ADD CONSTRAINT "commissions_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "brand_payments" ADD CONSTRAINT "brand_payments_brandProfileId_fkey" FOREIGN KEY ("brandProfileId") REFERENCES "brand_profiles"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "brand_payments" ADD CONSTRAINT "brand_payments_ledgerTransactionId_fkey" FOREIGN KEY ("ledgerTransactionId") REFERENCES "ledger_transactions"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "wallets" ADD CONSTRAINT "wallets_brandProfileId_fkey" FOREIGN KEY ("brandProfileId") REFERENCES "brand_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "wallets" ADD CONSTRAINT "wallets_marketerProfileId_fkey" FOREIGN KEY ("marketerProfileId") REFERENCES "marketer_profiles"("id") ON DELETE CASCADE ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ledger_transactions" ADD CONSTRAINT "ledger_transactions_brandProfileId_fkey" FOREIGN KEY ("brandProfileId") REFERENCES "brand_profiles"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ledger_transactions" ADD CONSTRAINT "ledger_transactions_campaignId_fkey" FOREIGN KEY ("campaignId") REFERENCES "campaigns"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ledger_transactions" ADD CONSTRAINT "ledger_transactions_campaignReservationId_fkey" FOREIGN KEY ("campaignReservationId") REFERENCES "campaign_budget_reservations"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ledger_transactions" ADD CONSTRAINT "ledger_transactions_conversionId_fkey" FOREIGN KEY ("conversionId") REFERENCES "conversions"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ledger_transactions" ADD CONSTRAINT "ledger_transactions_commissionId_fkey" FOREIGN KEY ("commissionId") REFERENCES "commissions"("id") ON DELETE SET NULL ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ledger_entries" ADD CONSTRAINT "ledger_entries_ledgerTransactionId_fkey" FOREIGN KEY ("ledgerTransactionId") REFERENCES "ledger_transactions"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "ledger_entries" ADD CONSTRAINT "ledger_entries_ledgerAccountId_fkey" FOREIGN KEY ("ledgerAccountId") REFERENCES "ledger_accounts"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "withdrawal_requests" ADD CONSTRAINT "withdrawal_requests_walletId_fkey" FOREIGN KEY ("walletId") REFERENCES "wallets"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "withdrawal_requests" ADD CONSTRAINT "withdrawal_requests_payoutMethodId_fkey" FOREIGN KEY ("payoutMethodId") REFERENCES "payout_methods"("id") ON DELETE RESTRICT ON UPDATE CASCADE;

-- AddForeignKey
ALTER TABLE "audit_logs" ADD CONSTRAINT "audit_logs_userId_fkey" FOREIGN KEY ("userId") REFERENCES "users"("id") ON DELETE SET NULL ON UPDATE CASCADE;


-- =========================================================================
-- NOMOWLY PHASE 2.2 HARDENING ENFORCEMENTS
-- =========================================================================

-- 1. Single Primary Specialization Partial Unique Index
CREATE UNIQUE INDEX IF NOT EXISTS "unique_single_primary_specialization" 
ON "marketer_specializations" ("marketerProfileId") 
WHERE "isPrimary" = true;

-- 2. Single Active Campaign Budget Reservation Partial Unique Index
CREATE UNIQUE INDEX IF NOT EXISTS "unique_active_campaign_reservation" 
ON "campaign_budget_reservations" ("campaignId") 
WHERE "status" = 'ACTIVE';

-- 3. Ledger Immutability (Append-Only Protection)
CREATE OR REPLACE FUNCTION prevent_ledger_mutation()
RETURNS TRIGGER AS $$
BEGIN
  RAISE EXCEPTION 'Financial Ledger records are immutable and append-only. Modification or deletion is strictly prohibited.';
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_ledger_transactions_immutable ON "ledger_transactions";
CREATE TRIGGER trg_ledger_transactions_immutable
BEFORE UPDATE OR DELETE ON "ledger_transactions"
FOR EACH ROW EXECUTE FUNCTION prevent_ledger_mutation();

DROP TRIGGER IF EXISTS trg_ledger_entries_immutable ON "ledger_entries";
CREATE TRIGGER trg_ledger_entries_immutable
BEFORE UPDATE OR DELETE ON "ledger_entries"
FOR EACH ROW EXECUTE FUNCTION prevent_ledger_mutation();
