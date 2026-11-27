<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\CompanyUser;
use App\Models\NumberSequence;
use App\Models\OpportunityStage;
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
            ['name' => 'هوشمند آرمان هماره', 'is_active' => true],
        );

        foreach (['quote' => 'Q', 'order' => 'O', 'opportunity' => 'OPP-'] as $type => $prefix) {
            NumberSequence::query()->updateOrCreate(
                ['company_id' => $company->id, 'document_type' => $type],
                ['prefix' => $prefix, 'next_number' => 1, 'padding' => $type === 'opportunity' ? 3 : 5, 'reset_policy' => 'never'],
            );
        }

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
            $permissionDefs = [
                ['name' => 'dashboard.view', 'group' => 'general', 'label' => 'مشاهده داشبورد'],
                ['name' => 'opportunity.view', 'group' => 'marketing', 'label' => 'مشاهده فرصت'],
                ['name' => 'opportunity.manage', 'group' => 'marketing', 'label' => 'مدیریت فرصت'],
                ['name' => 'opportunity.step.manage', 'group' => 'marketing', 'label' => 'ویرایش/حذف گام (مدیر)'],
                ['name' => 'opportunity.activity.create', 'group' => 'marketing', 'label' => 'ثبت فعالیت'],
            ];

            $permIds = [];
            foreach ($permissionDefs as $def) {
                $perm = Permission::query()->updateOrCreate(
                    ['name' => $def['name']],
                    ['group' => $def['group'], 'label' => $def['label']],
                );
                $permIds[] = $perm->id;
            }

            $adminRole = Role::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'admin'],
                ['label' => 'مدیر شرکت', 'is_system' => true],
            );
            $adminRole->permissions()->sync($permIds);

            $salesPermNames = ['dashboard.view', 'opportunity.view', 'opportunity.activity.create'];
            $salesIds = Permission::query()->whereIn('name', $salesPermNames)->pluck('id')->all();
            $salesRole = Role::query()->updateOrCreate(
                ['company_id' => $company->id, 'name' => 'sales'],
                ['label' => 'کارشناس فروش', 'is_system' => true],
            );
            $salesRole->permissions()->sync($salesIds);

            if ($membership && Schema::hasTable('company_user_role')) {
                DB::table('company_user_role')->updateOrInsert(
                    ['company_user_id' => $membership->id, 'role_id' => $adminRole->id],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        if (Schema::hasTable('platform_owners')) {
            PlatformOwner::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['is_active' => true],
            );
        }

        if (Schema::hasTable('opportunity_stages')) {
            $stages = [
                ['code' => 'initial_lead', 'name' => 'سرنخ اولیه', 'sort_order' => 10],
                ['code' => 'quick_qualify', 'name' => 'احراز صلاحیت سریع', 'sort_order' => 20],
                ['code' => 'need_discovery', 'name' => 'کشف نیاز', 'sort_order' => 30],
                ['code' => 'product_demo', 'name' => 'نمایش محصول', 'sort_order' => 40],
                ['code' => 'commercial_proposal', 'name' => 'ارائه پیشنهاد تجاری', 'sort_order' => 50],
                ['code' => 'negotiate_close', 'name' => 'مذاکره و بستن', 'sort_order' => 60],
            ];
            foreach ($stages as $stage) {
                OpportunityStage::query()->updateOrCreate(
                    ['company_id' => $company->id, 'code' => $stage['code']],
                    [
                        'name' => $stage['name'],
                        'sort_order' => $stage['sort_order'],
                        'is_won' => false,
                        'is_lost' => false,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
