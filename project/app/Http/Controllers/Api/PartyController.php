<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyPartyRole;
use App\Models\Party;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartyController extends Controller
{
    public function index(CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $roles = CompanyPartyRole::query()
            ->with('party')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (CompanyPartyRole $r) => [
                'id' => $r->id,
                'role' => $r->role,
                'local_code' => $r->local_code,
                'party' => [
                    'id' => $r->party?->id,
                    'type' => $r->party?->type,
                    'name' => $r->party?->name,
                    'phone' => $r->party?->phone,
                    'email' => $r->party?->email,
                    'is_active' => $r->party?->is_active,
                ],
            ]);

        return response()->json(['data' => $roles]);
    }

    public function store(Request $request, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'type' => ['required', 'in:person,organization'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', 'in:customer,supplier,contractor,partner,sales_representative'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email'],
            'national_id' => ['nullable', 'string', 'max:32'],
            'local_code' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
        ]);

        $row = DB::transaction(function () use ($data, $companyId) {
            $party = Party::query()->create([
                'type' => $data['type'],
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'national_id' => $data['national_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'is_active' => true,
            ]);

            $role = CompanyPartyRole::query()->create([
                'company_id' => $companyId,
                'party_id' => $party->id,
                'role' => $data['role'],
                'local_code' => $data['local_code'] ?? null,
                'is_active' => true,
            ]);

            $role->load('party');

            return $role;
        });

        return response()->json([
            'data' => [
                'id' => $row->id,
                'role' => $row->role,
                'party' => [
                    'id' => $row->party->id,
                    'type' => $row->party->type,
                    'name' => $row->party->name,
                ],
            ],
        ], 201);
    }
}
