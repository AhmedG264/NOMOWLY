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

            // Phase 5A: Brand Conversions Management
            Route::get('/conversions', [\App\Http\Controllers\Brand\ConversionController::class, 'index'])->name('brand.conversions.index');
            Route::post('/conversions/{conversion}/approve', [\App\Http\Controllers\Brand\ConversionController::class, 'approve'])->name('brand.conversions.approve');
            Route::post('/conversions/{conversion}/reject', [\App\Http\Controllers\Brand\ConversionController::class, 'reject'])->name('brand.conversions.reject');
            Route::post('/conversions/{conversion}/cancel', [\App\Http\Controllers\Brand\ConversionController::class, 'cancel'])->name('brand.conversions.cancel');
        });

        // Marketer Protected Area
        Route::prefix('marketer')->middleware('role:MARKETER')->group(function () {
            Route::get('/', function () {
                return redirect()->route('marketer.dashboard');
            });
            Route::get('/dashboard', [\App\Http\Controllers\Marketer\CampaignController::class, 'index'])->name('marketer.dashboard');
            Route::get('/campaigns', [\App\Http\Controllers\Marketer\CampaignController::class, 'index'])->name('marketer.campaigns.index');
            Route::post('/campaigns/{campaign}/apply', [\App\Http\Controllers\Marketer\CampaignController::class, 'apply'])->name('marketer.campaigns.apply');
        });
    });
});
