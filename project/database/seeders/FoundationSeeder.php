<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanyUser;
use App\Models\NumberSequence;
use App\Models\Permission;
use App\Models\PlatformOwner;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['code' => 'HAMAREH'],
            [
                'name' => 'هوشمند آرمان هماره',
                'is_active' => true,
            ],
        );

        NumberSequence::query()->updateOrCreate(
            ['company_id' => $company->id, 'document_type' => 'quote'],
            ['prefix' => 'Q', 'next_number' => 1, 'padding' => 5, 'reset_policy' => 'never'],
        );

        NumberSequence::query()->updateOrCreate(
            ['company_id' => $company->id, 'document_type' => 'order'],
            ['prefix' => 'O', 'next_number' => 1, 'padding' => 5, 'reset_policy' => 'never'],
        );

        foreach ([
            ['key' => 'locale', 'value' => 'fa'],
            ['key' => 'brand_name', 'value' => 'هماره'],
            ['key' => 'brand_full_name', 'value' => 'هوشمند آرمان هماره'],
        ] as $setting) {
            CompanySetting::query()->updateOrCreate(
                ['company_id' => $company->id, 'key' => $setting['key']],
                ['type' => 'string', 'value' => $setting['value'], 'is_encrypted' => false],
            );
        }

        $user = User::query()->updateOrCreate(
            ['email' => 'admin@hamareh.local'],
            ['name' => 'مدیر', 'password' => Hash::make('password')],
        );

        $membership = null;
        if (Schema::hasTable('company_user')) {
            $membership = CompanyUser::query()->updateOrCreate(
                ['company_id' => $company->id, 'user_id' => $user->id],
                ['is_default' => true, 'is_active' => true],
            );
        }

        if (Schema::hasTable('permissions')) {
            $perm = Permission::query()->updateOrCreate(
                ['name' => 'dashboard.view'],
                ['group' => 'general', 'label' => 'مشاهده داشبورد'],
            );

            $role = Role::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'admin'],
                ['label' => 'مدیر شرکت', 'is_system' => true],
            );

            $role->permissions()->syncWithoutDetaching([$perm->id]);

            if ($membership && Schema::hasTable('company_user_role')) {
                DB::table('company_user_role')->updateOrInsert(
                    [
                        'company_user_id' => $membership->id,
                        'role_id' => $role->id,
                    ],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        // مالک پلتفرم = حساب هماره (بدون UI تنظیمات مالک)
        if (Schema::hasTable('platform_owners')) {
            PlatformOwner::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['is_active' => true],
            );
        }
    }
}
