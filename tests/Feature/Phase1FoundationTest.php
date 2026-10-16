<?php

namespace Tests\Feature;

use Tests\TestCase;

class Phase1FoundationTest extends TestCase
{
    /**
     * Test that the home page returns 200 OK and renders Arabic RTL layout.
     */
    public function test_home_page_renders_arabic_rtl_layout(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('lang="ar"', false);
        $response->assertSee('Tajawal');
        $response->assertSee('منصة نمولي');
        $response->assertSee('Nomowly');
    }

    /**
     * Test that regional multi-currencies are configured correctly with EGP as default.
     */
    public function test_regional_currencies_configuration(): void
    {
        $currencies = config('nomowly.currencies');

        $this->assertIsArray($currencies);
        $this->assertArrayHasKey('EGP', $currencies);
        $this->assertArrayHasKey('SAR', $currencies);
        $this->assertArrayHasKey('AED', $currencies);
        $this->assertArrayHasKey('KWD', $currencies);
        $this->assertArrayHasKey('QAR', $currencies);
        $this->assertArrayHasKey('BHD', $currencies);
        $this->assertArrayHasKey('OMR', $currencies);

        $this->assertEquals('EGP', config('nomowly.default_currency'));
    }

    /**
     * Test that database configuration targets MySQL with InnoDB engine.
     */
    public function test_mysql_configuration(): void
    {
        $mysqlConfig = config('database.connections.mysql');

        $this->assertEquals('mysql', $mysqlConfig['driver']);
        $this->assertEquals('utf8mb4', $mysqlConfig['charset']);
        $this->assertEquals('utf8mb4_unicode_ci', $mysqlConfig['collation']);
        $this->assertEquals('InnoDB', $mysqlConfig['engine']);
        $this->assertTrue($mysqlConfig['strict']);
    }

    /**
     * Test that database queue is configured.
     */
    public function test_queue_configuration(): void
    {
        $queueConfig = config('queue.connections.database');

        $this->assertNotNull($queueConfig);
        $this->assertEquals('database', $queueConfig['driver']);
        $this->assertEquals('jobs', $queueConfig['table']);
    }

    /**
     * Test that nomowly:health artisan command executes successfully.
     */
    public function test_nomowly_health_command(): void
    {
        $this->artisan('nomowly:health')
            ->expectsOutputToContain('Nomowly Platform Health Check')
            ->expectsOutputToContain('Laravel Version:')
            ->expectsOutputToContain('Hostinger Shared Hosting Compatibility: Ready')
            ->assertExitCode(0);
    }

    /**
     * Test that core migration files exist.
     */
    public function test_core_migration_files_exist(): void
    {
        $this->assertFileExists(database_path('migrations/0001_01_01_000000_create_users_table.php'));
        $this->assertFileExists(database_path('migrations/0001_01_01_000001_create_cache_table.php'));
        $this->assertFileExists(database_path('migrations/0001_01_01_000002_create_jobs_table.php'));
    }
}
