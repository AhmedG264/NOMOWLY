<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Roles Table
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 64)->unique();
            $table->string('label', 128);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->index();
            $table->timestamps();
        });

        // 2. Permissions Table
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 64)->unique();
            $table->string('label', 128);
            $table->string('category', 64)->index();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 3. User - Role Pivot Table
        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
        });

        // 4. Role - Permission Pivot Table
        Schema::create('permission_role', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
        });

        // 5. Seed Default Roles
        $now = now();
        $rolesData = [
            [
                'name' => UserRole::SUPER_ADMIN->value,
                'label' => 'المدير العام (Super Admin)',
                'description' => 'صلاحيات إدارية وأمنية مطلقة في النظام واللوحة الإشرافية بالكامل.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'OPERATIONS_MANAGER',
                'label' => 'مدير العمليات (Operations Manager)',
                'description' => 'إشراف عام على سير العمليات، ومتابعة الحملات، والمستخدمين، وسجل التدقيق.',
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'FINANCIAL_REVIEWER',
                'label' => 'المراجع المالي (Financial Reviewer)',
                'description' => 'مراجعة واعتماد وتدقيق التحويلات المالية والعمولات والعمليات المحاسبية.',
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'AUDITOR',
                'label' => 'المراقب والمدقق (Auditor)',
                'description' => 'صلاحية قراءة وفحص سجل العمليات والتحويلات والحملات دون إمكانية التعديل.',
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'CAMPAIGN_MANAGER',
                'label' => 'مدير الحملات (Campaign Manager)',
                'description' => 'إدارة حملات العلامات التجارية ومراجعة طلبات انضمام المسوقين وتعديل الحالات.',
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'CUSTOMER_SUPPORT',
                'label' => 'خدمة العملاء والدعم الفني (Customer Support)',
                'description' => 'استعراض الحسابات والحملات لتقديم المساعدة للمستخدمين دون صلاحيات تعديل مالي.',
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => UserRole::BRAND->value,
                'label' => 'العلامة التجارية (Brand Owner)',
                'description' => 'صاحب المتجر أو العلامة التجارية، إطلاق الحملات ومراجعة المسوقين والتحويلات.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => UserRole::MARKETER->value,
                'label' => 'المسوق وصانع المحتوى (Marketer)',
                'description' => 'الترويج للحملات، واستخراج روابط وأكواد التتبع ومتابعة أداء التحويلات والعمولات.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($rolesData as &$r) {
            $r['id'] = (string) Str::uuid();
        }
        unset($r);
        DB::table('roles')->insert($rolesData);

        // 6. Seed Categorized Permissions
        $permissionsData = [
            // Users
            ['name' => 'users.view', 'label' => 'عرض قائمة وتفاصيل المستخدمين', 'category' => 'المستخدمون'],
            ['name' => 'users.create', 'label' => 'إنشاء مستخدمين جدد', 'category' => 'المستخدمون'],
            ['name' => 'users.update', 'label' => 'تعديل بيانات المستخدمين', 'category' => 'المستخدمون'],
            ['name' => 'users.suspend', 'label' => 'تعليق وإيقاف حسابات المستخدمين', 'category' => 'المستخدمون'],
            ['name' => 'users.activate', 'label' => 'إعادة تفعيل حسابات المستخدمين', 'category' => 'المستخدمون'],
            ['name' => 'users.assign_roles', 'label' => 'إسناد وتعديل أدوار المستخدمين', 'category' => 'المستخدمون'],

            // Campaigns
            ['name' => 'campaigns.view', 'label' => 'عرض ومتابعة الحملات', 'category' => 'الحملات'],
            ['name' => 'campaigns.create', 'label' => 'إنشاء حملات جديدة', 'category' => 'الحملات'],
            ['name' => 'campaigns.update', 'label' => 'تعديل بيانات الحملات', 'category' => 'الحملات'],
            ['name' => 'campaigns.approve', 'label' => 'الموافقة على نشر الحملات', 'category' => 'الحملات'],
            ['name' => 'campaigns.reject', 'label' => 'رفض نشر الحملات', 'category' => 'الحملات'],
            ['name' => 'campaigns.pause', 'label' => 'إيقاف الحملات مؤقتاً', 'category' => 'الحملات'],
            ['name' => 'campaigns.resume', 'label' => 'استئناف تشغيل الحملات', 'category' => 'الحملات'],

            // Applications
            ['name' => 'applications.view', 'label' => 'عرض طلبات انضمام المسوقين', 'category' => 'طلبات المشاركة'],
            ['name' => 'applications.approve', 'label' => 'اعتماد طلبات المسوقين وتوليد الأكواد', 'category' => 'طلبات المشاركة'],
            ['name' => 'applications.reject', 'label' => 'رفض طلبات انضمام المسوقين', 'category' => 'طلبات المشاركة'],

            // Conversions & Commissions
            ['name' => 'conversions.view', 'label' => 'عرض التحويلات والمبيعات', 'category' => 'التحويلات والعمولات'],
            ['name' => 'conversions.approve', 'label' => 'اعتماد التحويلات والعمولات المستحقة', 'category' => 'التحويلات والعمولات'],
            ['name' => 'conversions.reject', 'label' => 'رفض التحويلات غير المؤهلة', 'category' => 'التحويلات والعمولات'],
            ['name' => 'conversions.cancel', 'label' => 'إلغاء تحويلات معتمدة سابقاً', 'category' => 'التحويلات والعمولات'],

            // Audit Log
            ['name' => 'audit_logs.view', 'label' => 'عرض وتصفية سجل العمليات والمراقبة', 'category' => 'سجل المراقبة والتدقيق'],

            // Roles & Permissions
            ['name' => 'roles.view', 'label' => 'عرض قائمة وتفاصيل الأدوار والصلاحيات', 'category' => 'الأدوار والصلاحيات'],
            ['name' => 'roles.create', 'label' => 'إنشاء أدوار إدارية جديدة', 'category' => 'الأدوار والصلاحيات'],
            ['name' => 'roles.update', 'label' => 'تعديل مسميات ووصف الأدوار', 'category' => 'الأدوار والصلاحيات'],
            ['name' => 'roles.assign_permissions', 'label' => 'تعديل وتعيين صلاحيات الأدوار', 'category' => 'الأدوار والصلاحيات'],

            // Settings
            ['name' => 'settings.view', 'label' => 'عرض إعدادات النظام', 'category' => 'إعدادات المنصة'],
            ['name' => 'settings.update', 'label' => 'تحديث إعدادات وسياسات المنصة', 'category' => 'إعدادات المنصة'],
        ];

        $insertedPermissions = [];
        foreach ($permissionsData as $perm) {
            $id = (string) Str::uuid();
            $insertedPermissions[$perm['name']] = $id;
            DB::table('permissions')->insert([
                'id' => $id,
                'name' => $perm['name'],
                'label' => $perm['label'],
                'category' => $perm['category'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 7. Map Role Permissions
        $rolesByName = DB::table('roles')->pluck('id', 'name');

        // Helper to attach permissions to a role
        $attachPermissions = function (string $roleName, array $permNames) use ($rolesByName, $insertedPermissions, $now) {
            if (!isset($rolesByName[$roleName])) return;
            $roleId = $rolesByName[$roleName];
            foreach ($permNames as $pName) {
                if (isset($insertedPermissions[$pName])) {
                    DB::table('permission_role')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $insertedPermissions[$pName],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        };

        // Auditor Permissions (strictly read-only)
        $attachPermissions('AUDITOR', [
            'audit_logs.view',
            'users.view',
            'campaigns.view',
            'applications.view',
            'conversions.view',
            'roles.view',
            'settings.view',
        ]);

        // Financial Reviewer Permissions
        $attachPermissions('FINANCIAL_REVIEWER', [
            'conversions.view',
            'conversions.approve',
            'conversions.reject',
            'conversions.cancel',
            'campaigns.view',
            'audit_logs.view',
        ]);

        // Campaign Manager Permissions
        $attachPermissions('CAMPAIGN_MANAGER', [
            'campaigns.view',
            'campaigns.create',
            'campaigns.update',
            'campaigns.approve',
            'campaigns.reject',
            'campaigns.pause',
            'campaigns.resume',
            'applications.view',
            'applications.approve',
            'applications.reject',
            'conversions.view',
        ]);

        // Customer Support Permissions (No financial permissions)
        $attachPermissions('CUSTOMER_SUPPORT', [
            'users.view',
            'campaigns.view',
            'applications.view',
            'conversions.view',
        ]);

        // Operations Manager Permissions
        $attachPermissions('OPERATIONS_MANAGER', [
            'users.view',
            'users.suspend',
            'users.activate',
            'campaigns.view',
            'campaigns.approve',
            'campaigns.pause',
            'campaigns.resume',
            'applications.view',
            'conversions.view',
            'audit_logs.view',
            'roles.view',
        ]);

        // Super Admin receives all permissions explicitly
        foreach ($insertedPermissions as $pId) {
            DB::table('permission_role')->insert([
                'role_id' => $rolesByName[UserRole::SUPER_ADMIN->value],
                'permission_id' => $pId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 8. CRITICAL: Seamlessly Migrate ALL Existing Users to RBAC
        $existingUsers = DB::table('users')->select('id', 'role')->get();
        foreach ($existingUsers as $u) {
            $userRoleStr = strtoupper(trim((string) $u->role));
            if (isset($rolesByName[$userRoleStr])) {
                DB::table('role_user')->insert([
                    'user_id' => $u->id,
                    'role_id' => $rolesByName[$userRoleStr],
                    'assigned_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
