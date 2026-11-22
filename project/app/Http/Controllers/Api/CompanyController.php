<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyUser;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
