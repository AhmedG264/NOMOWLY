<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Production Safety:
     * In production (APP_ENV=production), demo fixtures must NEVER be automatically loaded.
     * Demo data should only be loaded in local development and testing environments.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->info('Nomowly Production Mode: Skipping development demo seeders.');
            return;
        }

        // Local & Testing Demo Data
        $this->call(CampaignSeeder::class);
    }
}
