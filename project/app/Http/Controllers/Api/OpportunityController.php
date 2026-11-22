<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyPartyRole;
use App\Models\Opportunity;
use App\Models\OpportunityActivity;
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
            ->map(fn (Opportunity $o) => $this->serialize($o));

        return response()->json(['data' => $items]);
    }

    public function show(int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $opp = Opportunity::query()
            ->with([
                'party:id,name,type,phone,email',
                'stage:id,name,code',
                'activities' => fn ($q) => $q->orderByDesc('happened_at')->limit(50),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json([
            'data' => array_merge($this->serialize($opp), [
                'need_summary' => $opp->need_summary,
                'activities' => $opp->activities->map(fn (OpportunityActivity $a) => [
                    'id' => $a->id,
                    'type' => $a->type,
                    'subject' => $a->subject,
                    'body' => $a->body,
                    'happened_at' => $a->happened_at?->toIso8601String(),
                ]),
            ]),
        ]);
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

        $stage = null;
        if (! empty($data['stage_id'])) {
            $stage = OpportunityStage::query()
                ->where('company_id', $companyId)
                ->where('id', $data['stage_id'])
                ->first();
        }

        if (! $stage) {
            $stage = OpportunityStage::query()
                ->where('company_id', $companyId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();
        }

        if (! $stage) {
            return response()->json(['message' => 'مرحله فرصت برای شرکت تعریف نشده است.'], 422);
        }

        $status = 'open';
        if ($stage->is_won) {
            $status = 'won';
        } elseif ($stage->is_lost) {
            $status = 'lost';
        }

        $opp = Opportunity::query()->create([
            'company_id' => $companyId,
            'party_id' => $data['party_id'],
            'stage_id' => $stage->id,
            'title' => $data['title'],
            'amount' => $data['amount'] ?? null,
            'need_summary' => $data['need_summary'] ?? null,
            'next_step' => $data['next_step'] ?? null,
            'owner_user_id' => $request->user()->id,
            'status' => $status,
        ]);

        $opp->load(['party:id,name', 'stage:id,name,code']);

        return response()->json(['data' => $this->serialize($opp)], 201);
    }

    public function updateStage(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'stage_id' => ['required', 'integer'],
            'next_step' => ['nullable', 'string', 'max:255'],
        ]);

        $opp = Opportunity::query()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $stage = OpportunityStage::query()
            ->where('company_id', $companyId)
            ->where('id', $data['stage_id'])
            ->firstOrFail();

        $status = 'open';
        if ($stage->is_won) {
            $status = 'won';
        } elseif ($stage->is_lost) {
            $status = 'lost';
        }

        $opp->update([
            'stage_id' => $stage->id,
            'status' => $status,
            'next_step' => $data['next_step'] ?? $opp->next_step,
        ]);

        $opp->load(['party:id,name', 'stage:id,name,code']);

        return response()->json(['data' => $this->serialize($opp)]);
    }

    public function storeActivity(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $opp = Opportunity::query()
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $data = $request->validate([
            'type' => ['nullable', 'in:note,call,meeting,task'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $activity = OpportunityActivity::query()->create([
            'company_id' => $companyId,
            'opportunity_id' => $opp->id,
            'user_id' => $request->user()->id,
            'type' => $data['type'] ?? 'note',
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'],
            'happened_at' => now(),
        ]);

        return response()->json([
            'data' => [
                'id' => $activity->id,
                'type' => $activity->type,
                'subject' => $activity->subject,
                'body' => $activity->body,
                'happened_at' => $activity->happened_at?->toIso8601String(),
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
            ->get(['id', 'name', 'code', 'sort_order', 'is_won', 'is_lost']);

        return response()->json(['data' => $stages]);
    }

    private function serialize(Opportunity $o): array
    {
        return [
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
        ];
    }
}
