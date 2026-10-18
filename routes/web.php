<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordChangeController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\PublicPagesController;
use Illuminate\Support\Facades\Route;

// Public Product Marketing Routes
Route::get('/', [PublicPagesController::class, 'home'])->name('home');
Route::get('/marketplace', [PublicPagesController::class, 'marketplace'])->name('marketplace');
Route::get('/marketplace/{slug}', [PublicPagesController::class, 'campaignShow'])->name('marketplace.show');
Route::get('/for-brands', [PublicPagesController::class, 'forBrands'])->name('for-brands');
Route::get('/for-marketers', [PublicPagesController::class, 'forMarketers'])->name('for-marketers');
Route::get('/how-it-works', [PublicPagesController::class, 'howItWorks'])->name('how-it-works');
Route::get('/pricing', [PublicPagesController::class, 'pricing'])->name('pricing');

// Phase 5A / 5.1: Attribution Click Tracking Redirect (Rate limited to prevent click bots)
Route::get('/go/{tracking_code}', [\App\Http\Controllers\TrackingController::class, 'track'])
    ->name('tracking.redirect')
    ->middleware('throttle:120,1');

// Phase 5A / 5.1: Authenticated & Signed Conversion Ingest API (Rate limited, HMAC signed)
Route::post('/api/conversions/ingest', [\App\Http\Controllers\Api\ConversionIngestController::class, 'ingest'])
    ->name('api.conversions.ingest')
    ->middleware(['throttle:60,1', 'verify_conversion_signature']);

// Phase 6: Payment Gateway Webhooks (CSRF exempt via bootstrap/app.php, signature verified by service)
Route::post('/api/webhooks/payments/{provider}', [\App\Http\Controllers\Api\PaymentWebhookController::class, 'handle'])
    ->name('api.webhooks.payments')
    ->middleware('throttle:120,1');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    // Login
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    // Password Reset
    Route::get('/forgot-password', [PasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->name('password.store');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Mandatory Password Change (Must be reachable even if must_change_password is true)
    Route::get('/password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('/password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');

    // Email Verification Foundation
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::post('/verify-email', [EmailVerificationController::class, 'verify'])->name('verification.verify');

    // Protected Routes requiring changed password
    Route::middleware('ensure_password_changed')->group(function () {
        // Super Admin Protected Area
        Route::prefix('admin')->middleware('super_admin')->group(function () {
            Route::get('/', function () {
                return redirect()->route('admin.dashboard');
            });
            Route::get('/dashboard', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'index'])->name('admin.dashboard');
            Route::get('/campaigns', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'index'])->name('admin.campaigns.index');
            Route::patch('/campaigns/{campaign}/status', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'toggleStatus'])->name('admin.campaigns.status');

            // Phase 7: Campaign Terms Change Requests Admin Moderation
            Route::get('/campaigns/change-requests', [\App\Http\Controllers\Admin\CampaignTermsReviewController::class, 'index'])->name('admin.campaigns.change-requests.index');
            Route::get('/campaigns/change-requests/{changeRequest}', [\App\Http\Controllers\Admin\CampaignTermsReviewController::class, 'show'])->name('admin.campaigns.change-requests.show');
            Route::post('/campaigns/change-requests/{changeRequest}/approve', [\App\Http\Controllers\Admin\CampaignTermsReviewController::class, 'approve'])->name('admin.campaigns.change-requests.approve');
            Route::post('/campaigns/change-requests/{changeRequest}/reject', [\App\Http\Controllers\Admin\CampaignTermsReviewController::class, 'reject'])->name('admin.campaigns.change-requests.reject');
            Route::patch('/campaigns/{campaign}/premium', [\App\Http\Controllers\Admin\AdminCampaignController::class, 'updatePremium'])->name('admin.campaigns.premium');

            // Phase 8: Subscription Plans & Marketer Subscriptions Management
            Route::get('/subscriptions/plans', [\App\Http\Controllers\Admin\AdminSubscriptionPlanController::class, 'index'])->name('admin.subscriptions.plans.index');
            Route::get('/subscriptions/plans/create', [\App\Http\Controllers\Admin\AdminSubscriptionPlanController::class, 'create'])->name('admin.subscriptions.plans.create');
            Route::post('/subscriptions/plans', [\App\Http\Controllers\Admin\AdminSubscriptionPlanController::class, 'store'])->name('admin.subscriptions.plans.store');
            Route::get('/subscriptions/plans/{plan}/edit', [\App\Http\Controllers\Admin\AdminSubscriptionPlanController::class, 'edit'])->name('admin.subscriptions.plans.edit');
            Route::put('/subscriptions/plans/{plan}', [\App\Http\Controllers\Admin\AdminSubscriptionPlanController::class, 'update'])->name('admin.subscriptions.plans.update');
            Route::patch('/subscriptions/plans/{plan}/status', [\App\Http\Controllers\Admin\AdminSubscriptionPlanController::class, 'toggleStatus'])->name('admin.subscriptions.plans.status');
            Route::get('/subscriptions', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'index'])->name('admin.subscriptions.index');
            Route::get('/subscriptions/{subscription}', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'show'])->name('admin.subscriptions.show');
            Route::post('/subscriptions/{subscription}/suspend', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'suspend'])->name('admin.subscriptions.suspend');
            Route::post('/subscriptions/{subscription}/cancel', [\App\Http\Controllers\Admin\AdminSubscriptionController::class, 'cancel'])->name('admin.subscriptions.cancel');

            // Phase 9: Admin Conversions, Attribution Integrity & Fraud Detection
            Route::prefix('conversions')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\AdminConversionController::class, 'index'])->name('admin.conversions.index');
                Route::get('/rules', [\App\Http\Controllers\Admin\AdminConversionController::class, 'rules'])->name('admin.conversions.rules');
                Route::put('/rules/{rule}', [\App\Http\Controllers\Admin\AdminConversionController::class, 'updateRule'])->name('admin.conversions.rules.update');
                Route::get('/{conversion}', [\App\Http\Controllers\Admin\AdminConversionController::class, 'show'])->name('admin.conversions.show');
                Route::post('/{conversion}/investigate', [\App\Http\Controllers\Admin\AdminConversionController::class, 'investigate'])->name('admin.conversions.investigate');
                Route::post('/{conversion}/approve', [\App\Http\Controllers\Admin\AdminConversionController::class, 'approve'])->name('admin.conversions.approve');
                Route::post('/{conversion}/reject', [\App\Http\Controllers\Admin\AdminConversionController::class, 'reject'])->name('admin.conversions.reject');
            });

            // Phase 2: Audit Logs & System Monitoring
            Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('admin.audit-logs.index');
            Route::get('/audit-logs/{auditLog}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show'])->name('admin.audit-logs.show');

            // Phase 3: Dynamic RBAC (Roles & Permissions Management)
            Route::resource('roles', \App\Http\Controllers\Admin\RoleController::class, ['as' => 'admin']);

            // Phase 3: User Roles & Access Assignment
            Route::get('/users', [\App\Http\Controllers\Admin\UserRoleController::class, 'index'])->name('admin.users.index');
            Route::get('/users/{user}/roles', [\App\Http\Controllers\Admin\UserRoleController::class, 'edit'])->name('admin.users.roles.edit');
            Route::put('/users/{user}/roles', [\App\Http\Controllers\Admin\UserRoleController::class, 'update'])->name('admin.users.roles.update');

            // Phase 4 & 5A: Financial Foundation, Wallets, Settlements & Payouts
            Route::prefix('finance')->group(function () {
                Route::get('/', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('admin.finance.index');
                Route::get('/wallets', [\App\Http\Controllers\Admin\FinanceController::class, 'wallets'])->name('admin.finance.wallets');
                Route::get('/wallets/{wallet}', [\App\Http\Controllers\Admin\FinanceController::class, 'walletShow'])->name('admin.finance.wallets.show');
                Route::get('/transactions', [\App\Http\Controllers\Admin\FinanceController::class, 'transactions'])->name('admin.finance.transactions');
                Route::get('/transactions/{transaction}', [\App\Http\Controllers\Admin\FinanceController::class, 'transactionShow'])->name('admin.finance.transactions.show');
                Route::post('/transactions/{transaction}/reverse', [\App\Http\Controllers\Admin\FinanceController::class, 'reverse'])->name('admin.finance.transactions.reverse');
                Route::get('/trial-balance', [\App\Http\Controllers\Admin\FinanceController::class, 'trialBalance'])->name('admin.finance.trial-balance');

                // Phase 5A: Payout Requests Management & Commission Settlements Audit
                Route::get('/payouts', [\App\Http\Controllers\Admin\PayoutRequestController::class, 'index'])->name('admin.finance.payouts.index');
                Route::get('/payouts/{payoutRequest}', [\App\Http\Controllers\Admin\PayoutRequestController::class, 'show'])->name('admin.finance.payouts.show');
                Route::post('/payouts/{payoutRequest}/approve', [\App\Http\Controllers\Admin\PayoutRequestController::class, 'approve'])->name('admin.finance.payouts.approve');
                Route::post('/payouts/{payoutRequest}/reject', [\App\Http\Controllers\Admin\PayoutRequestController::class, 'reject'])->name('admin.finance.payouts.reject');
                Route::post('/payouts/{payoutRequest}/mark-paid', [\App\Http\Controllers\Admin\PayoutRequestController::class, 'markPaid'])->name('admin.finance.payouts.mark-paid');
                Route::get('/settlements', [\App\Http\Controllers\Admin\PayoutRequestController::class, 'settlements'])->name('admin.finance.settlements.index');

                // Phase 6: Payment Deposits, Refunds & Financial Reconciliation Management
                Route::get('/deposits', [\App\Http\Controllers\Admin\PaymentManagementController::class, 'index'])->name('admin.finance.deposits.index');
                Route::get('/deposits/{deposit}', [\App\Http\Controllers\Admin\PaymentManagementController::class, 'show'])->name('admin.finance.deposits.show');
                Route::post('/deposits/{deposit}/refund', [\App\Http\Controllers\Admin\PaymentManagementController::class, 'storeRefund'])->name('admin.finance.deposits.refund');
                Route::post('/deposits/{deposit}/reconcile', [\App\Http\Controllers\Admin\PaymentManagementController::class, 'reconcile'])->name('admin.finance.deposits.reconcile');
                Route::get('/refunds', [\App\Http\Controllers\Admin\PaymentManagementController::class, 'refunds'])->name('admin.finance.refunds.index');
                Route::get('/reconciliation', [\App\Http\Controllers\Admin\PaymentManagementController::class, 'reconciliation'])->name('admin.finance.reconciliation.index');
            });
        });

        // Brand Protected Area
        Route::prefix('brand')->middleware('role:BRAND')->group(function () {
            Route::get('/', function () {
                return redirect()->route('brand.dashboard');
            });
            Route::get('/dashboard', [\App\Http\Controllers\Brand\CampaignController::class, 'index'])->name('brand.dashboard');
            Route::get('/campaigns', [\App\Http\Controllers\Brand\CampaignController::class, 'index'])->name('brand.campaigns.index');
            Route::get('/campaigns/create', [\App\Http\Controllers\Brand\CampaignController::class, 'create'])->name('brand.campaigns.create');
            Route::post('/campaigns', [\App\Http\Controllers\Brand\CampaignController::class, 'store'])->name('brand.campaigns.store');
            Route::get('/campaigns/{campaign}/edit', [\App\Http\Controllers\Brand\CampaignController::class, 'edit'])->name('brand.campaigns.edit');
            Route::put('/campaigns/{campaign}', [\App\Http\Controllers\Brand\CampaignController::class, 'update'])->name('brand.campaigns.update');
            Route::patch('/campaigns/{campaign}/status', [\App\Http\Controllers\Brand\CampaignController::class, 'toggleStatus'])->name('brand.campaigns.status');
            Route::get('/campaigns/{campaign}/applications', [\App\Http\Controllers\Brand\CampaignController::class, 'applications'])->name('brand.campaigns.applications');
            Route::post('/campaigns/{campaign}/applications/{application}/review', [\App\Http\Controllers\Brand\CampaignController::class, 'reviewApplication'])->name('brand.campaigns.applications.review');

            // Phase 7: Brand Campaign Terms Versioning & Change Requests
            Route::get('/campaigns/{campaign}/terms', [\App\Http\Controllers\Brand\CampaignController::class, 'terms'])->name('brand.campaigns.terms');
            Route::get('/campaigns/{campaign}/change-requests/create', [\App\Http\Controllers\Brand\CampaignController::class, 'createChangeRequest'])->name('brand.campaigns.change-requests.create');
            Route::post('/campaigns/{campaign}/change-requests', [\App\Http\Controllers\Brand\CampaignController::class, 'storeChangeRequest'])->name('brand.campaigns.change-requests.store');

            // Phase 5A: Brand Conversions Management
            Route::get('/conversions', [\App\Http\Controllers\Brand\ConversionController::class, 'index'])->name('brand.conversions.index');
            Route::post('/conversions/{conversion}/approve', [\App\Http\Controllers\Brand\ConversionController::class, 'approve'])->name('brand.conversions.approve');
            Route::post('/conversions/{conversion}/reject', [\App\Http\Controllers\Brand\ConversionController::class, 'reject'])->name('brand.conversions.reject');
            Route::post('/conversions/{conversion}/cancel', [\App\Http\Controllers\Brand\ConversionController::class, 'cancel'])->name('brand.conversions.cancel');

            // Phase 5B & 6: Brand Wallet UI, Escrow & Payment Deposits
            Route::get('/wallet', [\App\Http\Controllers\Brand\WalletController::class, 'index'])->name('brand.wallet.index');
            Route::post('/wallet/deposit', [\App\Http\Controllers\Brand\PaymentDepositController::class, 'store'])->name('brand.wallet.deposit');
            Route::get('/wallet/deposit/callback', [\App\Http\Controllers\Brand\PaymentDepositController::class, 'callback'])->name('brand.wallet.deposit.callback');
        });

        // Marketer Protected Area
        Route::prefix('marketer')->middleware('role:MARKETER')->group(function () {
            Route::get('/', function () {
                return redirect()->route('marketer.dashboard');
            });
            Route::get('/dashboard', [\App\Http\Controllers\Marketer\CampaignController::class, 'index'])->name('marketer.dashboard');
            Route::get('/campaigns', [\App\Http\Controllers\Marketer\CampaignController::class, 'index'])->name('marketer.campaigns.index');
            Route::post('/campaigns/{campaign}/apply', [\App\Http\Controllers\Marketer\CampaignController::class, 'apply'])->name('marketer.campaigns.apply');

            // Phase 7: Marketer Terms Acceptance
            Route::post('/campaigns/applications/{application}/accept-terms/{version}', [\App\Http\Controllers\Marketer\CampaignController::class, 'acceptTerms'])->name('marketer.campaigns.accept-terms');

            // Phase 5A: Marketer Wallet & Payout Requests
            Route::get('/wallet', [\App\Http\Controllers\Marketer\WalletController::class, 'index'])->name('marketer.wallet.index');
            Route::post('/wallet/payout-request', [\App\Http\Controllers\Marketer\WalletController::class, 'storePayoutRequest'])->name('marketer.wallet.payout-request');

            // Phase 8: Marketer Subscription Plans & Entitlements
            Route::get('/plans', [\App\Http\Controllers\Marketer\MarketerSubscriptionController::class, 'plans'])->name('marketer.plans.index');
            Route::get('/plans/{plan}/subscribe', [\App\Http\Controllers\Marketer\MarketerSubscriptionController::class, 'subscribe'])->name('marketer.subscriptions.subscribe');
            Route::post('/subscriptions/process-payment', [\App\Http\Controllers\Marketer\MarketerSubscriptionController::class, 'processPayment'])->name('marketer.subscriptions.process-payment');
            Route::get('/subscriptions', [\App\Http\Controllers\Marketer\MarketerSubscriptionController::class, 'index'])->name('marketer.subscriptions.index');
            Route::get('/subscriptions/{subscription}', [\App\Http\Controllers\Marketer\MarketerSubscriptionController::class, 'show'])->name('marketer.subscriptions.show');
        });
    });
});
