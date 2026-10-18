<?php

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class BootstrapAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nomowly:bootstrap-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bootstrap the initial Super Admin account idempotently from environment variables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting Nomowly Super Admin bootstrap procedure...');

        $adminEmail = getenv('ADMIN_EMAIL') ?: env('ADMIN_EMAIL') ?: config('nomowly.admin_email');
        $adminInitialPassword = getenv('ADMIN_INITIAL_PASSWORD') ?: env('ADMIN_INITIAL_PASSWORD') ?: config('nomowly.admin_initial_password');

        if (empty($adminEmail)) {
            $this->error('ERROR: ADMIN_EMAIL environment variable is not set.');
            return self::FAILURE;
        }

        if (empty($adminInitialPassword)) {
            $this->error('ERROR: ADMIN_INITIAL_PASSWORD environment variable is not set.');
            return self::FAILURE;
        }

        // 1. Validate Email
        $validator = Validator::make(
            ['email' => $adminEmail],
            ['email' => ['required', 'string', 'email']]
        );

        if ($validator->fails()) {
            $this->error('ERROR: ADMIN_EMAIL is invalid: ' . $validator->errors()->first('email'));
            return self::FAILURE;
        }

        // 2. Validate Password strength (minimum 10 characters, letters, numbers, symbols)
        $passwordValidator = Validator::make(
            ['password' => $adminInitialPassword],
            [
                'password' => [
                    'required',
                    'string',
                    Password::min(10)
                        ->letters()
                        ->numbers()
                        ->symbols(),
                ],
            ]
        );

        if ($passwordValidator->fails()) {
            $this->error('ERROR: ADMIN_INITIAL_PASSWORD does not meet security requirements: ' . $passwordValidator->errors()->first('password'));
            return self::FAILURE;
        }

        // 3. Check whether account already exists
        $existingUser = User::where('email', $adminEmail)->first();

        if ($existingUser) {
            // Safety check: Never silently promote an existing non-admin user
            if ($existingUser->role !== UserRole::SUPER_ADMIN) {
                $this->error("CRITICAL SAFETY HALT: An account with email [{$adminEmail}] already exists with role [{$existingUser->role->value}]. Silent promotion is prohibited. Manual resolution is required.");
                return self::FAILURE;
            }

            // If account is already SUPER_ADMIN, ensure dynamic role is also attached idempotently
            $superAdminRole = \App\Models\Role::where('name', UserRole::SUPER_ADMIN->value)->first();
            if ($superAdminRole && !$existingUser->roles->contains('id', $superAdminRole->id)) {
                $existingUser->roles()->attach($superAdminRole->id, [
                    'assigned_by' => null,
                ]);
            }
            $this->info("IDEMPOTENT STATUS: Super Admin account for [{$adminEmail}] already exists. Credentials were not overwritten.");
            return self::SUCCESS;
        }

        // 4. Create initial Super Admin account
        $admin = User::create([
            'name' => 'المدير العام للنظام',
            'email' => $adminEmail,
            'password' => Hash::make($adminInitialPassword),
            'role' => UserRole::SUPER_ADMIN,
            'status' => UserStatus::ACTIVE,
            'must_change_password' => true,
            'email_verified_at' => now(),
        ]);

        $superAdminRole = \App\Models\Role::where('name', UserRole::SUPER_ADMIN->value)->first();
        if ($superAdminRole) {
            $admin->roles()->attach($superAdminRole->id, [
                'assigned_by' => null,
            ]);
        }

        // 5. Audit Log Entry
        AuditService::log(
            action: AuditAction::SUPER_ADMIN_BOOTSTRAP,
            userId: $admin->id,
            entityType: 'User',
            entityId: $admin->id,
            payloadDiff: [
                'email' => $adminEmail,
                'role' => UserRole::SUPER_ADMIN->value,
                'must_change_password' => true,
            ]
        );

        $this->info("SUCCESS: Initial Super Admin account created successfully for [{$adminEmail}].");
        $this->line("Status: must_change_password is set to true.");

        return self::SUCCESS;
    }
}
