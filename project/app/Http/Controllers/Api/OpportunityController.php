<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyPartyRole;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $items = Opportunity::query()
            ->with(['party:id,name,type', 'stage:id,name,code'])
            ->where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (Opportunity $o) => [
                'id' => $o->id,
                'title' => $o->title,
                'amount' => $o->amount,
                'status' => $o->status,
                'next_step' => $o->next_step,
                'party' => $o->party ? [
                    'id' => $o->party->id,
                    'name' => $o->party->name,
                ] : null,
                'stage' => $o->stage ? [
                    'id' => $o->stage->id,
                    'name' => $o->stage->name,
                    'code' => $o->stage->code,
                ] : null,
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'party_id' => ['required', 'integer'],
            'stage_id' => ['nullable', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'need_summary' => ['nullable', 'string'],
            'next_step' => ['nullable', 'string', 'max:255'],
        ]);

        $partyOk = CompanyPartyRole::query()
            ->where('company_id', $companyId)
            ->where('party_id', $data['party_id'])
            ->where('is_active', true)
            ->exists();

        if (! $partyOk) {
            return response()->json(['message' => 'طرف تجاری در این شرکت معتبر نیست.'], 422);
        }

        $stageId = $data['stage_id'] ?? OpportunityStage::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('id');

        if (! $stageId) {
            return response()->json(['message' => 'مرحله فرصت برای شرکت تعریف نشده است.'], 422);
        }

        $opp = Opportunity::query()->create([
            'company_id' => $companyId,
            'party_id' => $data['party_id'],
            'stage_id' => $stageId,
            'title' => $data['title'],
            'amount' => $data['amount'] ?? null,
            'need_summary' => $data['need_summary'] ?? null,
            'next_step' => $data['next_step'] ?? null,
            'owner_user_id' => $request->user()->id,
            'status' => 'open',
        ]);

        $opp->load(['party:id,name', 'stage:id,name,code']);

        return response()->json([
            'data' => [
                'id' => $opp->id,
                'title' => $opp->title,
                'party' => $opp->party,
                'stage' => $opp->stage,
            ],
        ], 201);
    }

    public function stages(CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $stages = OpportunityStage::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'sort_order']);

        return response()->json(['data' => $stages]);
    }
}
