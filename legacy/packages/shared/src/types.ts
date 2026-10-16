export type UserRoleType = 'SUPER_ADMIN' | 'BRAND' | 'MARKETER';
export type SpecializationTypeEnum = 'CREATOR' | 'AFFILIATE' | 'MEDIA_BUYER';
export type KycStatusEnum = 'NOT_STARTED' | 'PENDING' | 'VERIFIED' | 'REJECTED';
export type CampaignStatusEnum = 'DRAFT' | 'PENDING_REVIEW' | 'ACTIVE' | 'PAUSED' | 'COMPLETED' | 'CANCELLED' | 'REJECTED';
export type CommissionTypeEnum = 'PERCENTAGE' | 'FLAT_FEE';
export type CommissionStatusEnum = 'PENDING' | 'AVAILABLE' | 'PAID' | 'VOIDED' | 'ADJUSTED';
export type WithdrawalStatusEnum = 'REQUESTED' | 'UNDER_REVIEW' | 'APPROVED' | 'PROCESSING' | 'PAID' | 'FAILED' | 'REJECTED' | 'CANCELLED';

export interface UserSessionPayload {
  userId: string;
  email: string;
  role: UserRoleType;
  brandProfileId?: string;
  marketerProfileId?: string;
}

export interface EncryptedPayload {
  ciphertext: string;
  iv: string;
  tag: string;
  keyVersion: string;
}

export interface BalancedLedgerEntryInput {
  accountCode: string;
  debitAmount: string | number;
  creditAmount: string | number;
}

export interface PostTransactionInput {
  type: string;
  description: string;
  currency?: string;
  brandProfileId?: string;
  campaignId?: string;
  campaignReservationId?: string;
  conversionId?: string;
  commissionId?: string;
  referenceId?: string;
  entries: BalancedLedgerEntryInput[];
}
