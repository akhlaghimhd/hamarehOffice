<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\PlatformOwner;
use App\Models\Role;
use App\Models\User;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $memberships = CompanyUser::query()
            ->with('company:id,name,code,is_active')
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->whereHas('company', fn ($q) => $q->where('is_active', true))
            ->orderByDesc('is_default')
            ->get()
            ->map(fn (CompanyUser $m) => [
                'id' => $m->company_id,
                'name' => $m->company?->name,
                'code' => $m->company?->code,
                'is_default' => $m->is_default,
            ]);

        return response()->json(['data' => $memberships]);
    }

    public function switch(Request $request, CompanyContext $context): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
        ]);

        $membership = $context->setForUser($request->user(), (int) $data['company_id']);
        $membership->load('company');

        CompanyUser::query()
            ->where('user_id', $request->user()->id)
            ->update(['is_default' => false]);

        $membership->update(['is_default' => true]);

        return response()->json([
            'company' => [
                'id' => $membership->company_id,
                'name' => $membership->company?->name,
                'code' => $membership->company?->code,
            ],
        ]);
    }

    public function users(CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $users = User::query()
            ->whereIn('id', CompanyUser::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json(['data' => $users]);
    }

    /**
     * فقط مالک پلتفرم — بدون UI اختصاصی در فاز جاری.
     */
    public function store(Request $request): JsonResponse
    {
        if (! PlatformOwner::isOwner($request->user()->id)) {
            return response()->json([
                'message' => 'فقط مالک پلتفرم می‌تواند شرکت جدید ایجاد کند.',
                'code' => 'platform_owner_required',
            ], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:32', 'unique:companies,code'],
            'admin_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $company = DB::transaction(function () use ($data, $request) {
            $company = Company::query()->create([
                'name' => $data['name'],
                'code' => $data['code'] ?? Str::upper(Str::random(6)),
                'is_active' => true,
            ]);

            $adminId = $data['admin_user_id'] ?? $request->user()->id;

            $membership = CompanyUser::query()->create([
                'company_id' => $company->id,
                'user_id' => $adminId,
                'is_default' => false,
                'is_active' => true,
            ]);

            $role = Role::query()->create([
                'company_id' => $company->id,
                'name' => 'admin',
                'label' => 'مدیر شرکت',
                'is_system' => true,
            ]);

            DB::table('company_user_role')->insert([
                'company_user_id' => $membership->id,
                'role_id' => $role->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $company;
        });

        return response()->json([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ],
        ], 201);
    }
}
