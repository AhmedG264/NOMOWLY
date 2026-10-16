<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Server-side Authorization Gates
        Gate::define('access-super-admin', function (User $user) {
            return $user->role === UserRole::SUPER_ADMIN;
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
