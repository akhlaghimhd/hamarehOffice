<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanyUser;
use App\Models\NumberSequence;
use App\Models\Permission;
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

        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id, 'key' => 'locale'],
            ['type' => 'string', 'value' => 'fa', 'is_encrypted' => false],
        );

        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id, 'key' => 'brand_name'],
            ['type' => 'string', 'value' => 'هماره', 'is_encrypted' => false],
        );

        CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id, 'key' => 'brand_full_name'],
            ['type' => 'string', 'value' => 'هوشمند آرمان هماره', 'is_encrypted' => false],
        );

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
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        if (Schema::hasTable('platform_owners')) {
            DB::table('platform_owners')->updateOrInsert(
                ['user_id' => $user->id],
                ['is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }
}
