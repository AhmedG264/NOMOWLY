<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
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
        $financialPermissions = [
            [
                'name' => 'finance.view',
                'label' => 'عرض السجلات المالية والمحافظ',
                'category' => 'المالية والمحافظ المحاسبية',
                'description' => 'الاطلاع على لوحة القيادة المالية، أرصدة المحافظ، وميزان المراجعة.',
            ],
            [
                'name' => 'finance.audit',
                'label' => 'تدقيق القيود المحاسبية ومطابقتها',
                'category' => 'المالية والمحافظ المحاسبية',
                'description' => 'فحص قيود دفتر الأستاذ، التحقق من توازن المدين والدائن، ومراجعة تسويات العملات.',
            ],
            [
                'name' => 'finance.manage',
                'label' => 'إدارة المحافظ والتسويات الإدارية',
                'category' => 'المالية والمحافظ المحاسبية',
                'description' => 'تجميد وتنشيط المحافظ وإجراء التسويات المالية والقيد العكسي.',
            ],
            [
                'name' => 'wallets.view',
                'label' => 'استعراض كشوف حساب المحافظ',
                'category' => 'المالية والمحافظ المحاسبية',
                'description' => 'استعراض كشف الحساب التفصيلي والحركات التاريخية لأي محفظة.',
            ],
        ];

        $superAdminRole = Role::where('name', 'SUPER_ADMIN')->first();

        foreach ($financialPermissions as $permData) {
            $permission = Permission::firstOrCreate(
                ['name' => $permData['name']],
                [
                    'id' => (string) Str::uuid(),
                    'label' => $permData['label'],
                    'category' => $permData['category'],
                    'description' => $permData['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            if ($superAdminRole) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $superAdminRole->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if (!$exists) {
                    $insertData = [
                        'role_id' => $superAdminRole->id,
                        'permission_id' => $permission->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (Schema::hasColumn('permission_role', 'id')) {
                        $insertData['id'] = (string) Str::uuid();
                    }

                    DB::table('permission_role')->insert($insertData);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionNames = ['finance.view', 'finance.audit', 'finance.manage', 'wallets.view'];
        $permIds = Permission::whereIn('name', $permissionNames)->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permIds)->delete();
        Permission::whereIn('name', $permissionNames)->delete();
    }
};
