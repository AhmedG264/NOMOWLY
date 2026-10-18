<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\Payment\PaymentGatewayManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enforce HTTPS URL generation when request is forwarded as HTTPS (Cloud Run, reverse proxies, Hostinger SSL)
        // or when running in production or behind secure domains.
        // Avoid unconditionally forcing HTTPS for local HTTP development.
        if (
            $this->app->environment("production") ||
            env("APP_FORCE_HTTPS", false) ||
            request()->isSecure() ||
            (request()->hasHeader("X-Forwarded-Proto") && request()->header("X-Forwarded-Proto") === "https") ||
            (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]) && $_SERVER["HTTP_X_FORWARDED_PROTO"] === "https") ||
            str_contains(request()->getHost(), "run.app")
        ) {
            URL::forceScheme("https");
        }
        // Server-side Authorization Gates
        // Super Admin holds universal authorization
        Gate::before(function (User $user, string $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        Gate::define('access-super-admin', function (User $user) {
            return $user->isSuperAdmin();
        });

        Gate::define('access-brand', function (User $user) {
            return $user->role === UserRole::BRAND;
        });

        Gate::define('access-marketer', function (User $user) {
            return $user->role === UserRole::MARKETER;
        });

        Gate::policy(\App\Models\Campaign::class, \App\Policies\CampaignPolicy::class);

        // Ensure Vite generated asset URLs are always same-origin relative paths (e.g. /build/assets/app-*.css)
        // This guarantees flawless loading across reverse proxies, custom domains, and production environments.
        \Illuminate\Support\Facades\Vite::createAssetPathsUsing(function ($path) {
            return '/' . ltrim($path, '/');
        });
    }
}
