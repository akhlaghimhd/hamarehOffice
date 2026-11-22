<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\NumberSequence;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FoundationSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->updateOrCreate(
            ['code' => 'HAMAREH'],
            [
                'name' => 'شرکت نمونه هم‌ره',
                'is_active' => true,
            ],
        );

        NumberSequence::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'document_type' => 'quote',
            ],
            [
                'prefix' => 'Q',
                'next_number' => 1,
                'padding' => 5,
                'reset_policy' => 'never',
            ],
        );

        NumberSequence::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'document_type' => 'order',
            ],
            [
                'prefix' => 'O',
                'next_number' => 1,
                'padding' => 5,
                'reset_policy' => 'never',
            ],
        );

        CompanySetting::query()->updateOrCreate(
            [
                'company_id' => $company->id,
                'key' => 'locale',
            ],
            [
                'type' => 'string',
                'value' => 'fa',
                'is_encrypted' => false,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@hamareh.local'],
            [
                'name' => 'مدیر',
                'password' => Hash::make('password'),
            ],
        );
    }
}
