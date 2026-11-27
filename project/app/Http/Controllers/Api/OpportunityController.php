<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\OpportunityStep;
use App\Services\CompanyContext;
use App\Services\OpportunityInsightService;
use App\Services\OpportunityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(
        private OpportunityService $service,
        private OpportunityInsightService $insightService,
    ) {}

    public function index(Request $request, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $q = Opportunity::query()
            ->where('company_id', $companyId)
            ->with(['stage:id,name,code', 'owner:id,name', 'assignee:id,name', 'currentStep']);

        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($stageId = $request->query('stage_id')) {
            $q->where('stage_id', $stageId);
        }
        if ($search = $request->query('q')) {
            $q->where(function ($qq) use ($search) {
                $qq->where('organization_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('id')->limit(100)->get()->map(fn (Opportunity $o) => $this->listItem($o));

        return response()->json(['data' => $items]);
    }

    public function store(Request $request, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'products' => ['nullable', 'array'],
            'products.*' => ['string', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'next_step' => ['required', 'string', 'max:500'],
            'next_step_due_on' => ['nullable', 'date'],
            'stage_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,nurturing,paused,won,lost'],
            'opening_note' => ['nullable', 'string'],
            'assignee_user_id' => ['nullable', 'integer'],
        ]);

        $opp = $this->service->createQuick($companyId, $request->user()->id, $data);

        return response()->json(['data' => $this->listItem($opp)], 201);
    }

    public function show(int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $opp = Opportunity::query()
            ->with([
                'stage',
                'owner:id,name',
                'assignee:id,name',
                'steps' => fn ($q) => $q->orderBy('sort_order')->with('assignee:id,name'),
                'activities' => fn ($q) => $q->orderByDesc('id')->limit(50),
                'referrals' => fn ($q) => $q->orderByDesc('id')->limit(30)->with(['fromUser:id,name', 'toUser:id,name']),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json(['data' => $this->detail($opp)]);
    }

    public function insight(int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        try {
            $opp = Opportunity::query()
                ->with([
                    'stage:id,name,code',
                    'steps' => fn ($q) => $q->orderBy('sort_order'),
                    'activities' => fn ($q) => $q->orderByDesc('id')->limit(30),
                ])
                ->where('company_id', $companyId)
                ->findOrFail($id);

            return response()->json(['data' => $this->insightService->forOpportunity($opp)]);
        } catch (\Throwable $e) {
            return response()->json(['data' => ['source' => 'local', 'llm_error' => true, 'message' => $e->getMessage()]], 200);
        }
    }

    public function update(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'organization_name' => ['sometimes', 'string', 'max:255'],
            'products' => ['nullable', 'array'],
            'products.*' => ['string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_role' => ['nullable', 'string', 'max:255'],
            'contact_mobile' => ['nullable', 'string', 'max:50'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'next_step' => ['nullable', 'string', 'max:500'],
            'next_step_due_on' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,nurturing,paused,won,lost'],
            'stage_id' => ['nullable', 'integer'],
        ]);

        $opp = Opportunity::query()->where('company_id', $companyId)->findOrFail($id);
        $opp = $this->service->updateBasic($opp, $data);

        return response()->json(['data' => $this->listItem($opp)]);
    }

    public function closeStep(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'result' => ['required', 'string'],
            'stage_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,nurturing,paused,won,lost'],
            'next_step_title' => ['nullable', 'string', 'max:500'],
            'next_step_due_on' => ['nullable', 'date'],
            'assignee_user_id' => ['nullable', 'integer'],
            'return_to_owner' => ['nullable', 'boolean'],
        ]);

        $opp = Opportunity::query()->where('company_id', $companyId)->findOrFail($id);
        $userId = (int) $request->user()->id;
        $isOwner = (int) $opp->owner_user_id === $userId;

        if (! $isOwner && in_array($data['status'] ?? null, ['won', 'lost'], true)) {
            return response()->json([
                'message' => 'فقط مالک فرصت می‌تواند فرصت را نهایی (موفق/ناموفق) ببندد.',
            ], 403);
        }

        if (! $isOwner && ! empty($data['next_step_title'])) {
            return response()->json([
                'message' => 'تعریف گام جدید فقط برای مالک فرصت مجاز است.',
            ], 403);
        }

        $step = OpportunityStep::query()
            ->where('opportunity_id', $opp->id)
            ->where('is_current', true)
            ->where('status', 'open')
            ->firstOrFail();

        if (! $isOwner && (int) ($step->assignee_user_id ?? 0) !== $userId) {
            return response()->json([
                'message' => 'فقط مسئول همین گام می‌تواند آن را ببندد.',
            ], 403);
        }

        if (! $isOwner && ! array_key_exists('return_to_owner', $data)) {
            $data['return_to_owner'] = true;
        }

        $opp = $this->service->closeStep($opp, $step, $data, $userId);

        return response()->json(['data' => $this->detail($opp->load([
            'stage', 'owner:id,name', 'assignee:id,name',
            'steps' => fn ($q) => $q->orderBy('sort_order')->with('assignee:id,name'),
            'activities' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'referrals' => fn ($q) => $q->orderByDesc('id')->limit(30)->with(['fromUser:id,name', 'toUser:id,name']),
        ]))]);
    }

    public function storeActivity(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:50'],
            'result' => ['nullable', 'string', 'max:2000'],
            'happened_at' => ['nullable', 'date'],
            'step_id' => ['nullable', 'integer'],
        ]);

        $opp = Opportunity::query()
            ->with('currentStep')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $userId = (int) $request->user()->id;
        $isOwner = (int) $opp->owner_user_id === $userId;
        if (! $isOwner) {
            $stepId = $data['step_id'] ?? $opp->currentStep?->id;
            $step = $stepId
                ? OpportunityStep::query()
                    ->where('opportunity_id', $opp->id)
                    ->where('id', $stepId)
                    ->first()
                : null;
            if (! $step || (int) $step->assignee_user_id !== $userId) {
                return response()->json([
                    'message' => 'فقط می‌توانید زیر گام ارجاع‌شده به خودتان فعالیت ثبت کنید.',
                ], 403);
            }
        }

        $activity = $this->service->addActivity($opp, $userId, $data);

        return response()->json(['data' => [
            'id' => $activity->id,
            'subject' => $activity->subject,
            'body' => $activity->body,
            'type' => $activity->type,
            'step_id' => $activity->step_id,
            'happened_at' => $activity->happened_at?->toIso8601String(),
            'created_at' => $activity->created_at?->toIso8601String(),
        ]], 201);
    }

    public function assign(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'step_title' => ['required', 'string', 'max:500'],
            'step_due_on' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'note' => ['nullable', 'string', 'max:1000'],
            'action' => ['nullable', 'string', 'max:50'],
        ]);

        $opp = Opportunity::query()->where('company_id', $companyId)->findOrFail($id);
        $opp = $this->service->assign($opp, $request->user()->id, $data);

        return response()->json(['data' => $this->detail($opp->load([
            'stage', 'owner:id,name', 'assignee:id,name',
            'steps' => fn ($q) => $q->orderBy('sort_order')->with('assignee:id,name'),
            'activities' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'referrals' => fn ($q) => $q->orderByDesc('id')->limit(30)->with(['fromUser:id,name', 'toUser:id,name']),
        ]))]);
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

    private function listItem(Opportunity $o): array
    {
        $products = is_array($o->products) ? $o->products : [];
        if (empty($products) && $o->product_name) {
            $products = [$o->product_name];
        }

        return [
            'id' => $o->id,
            'code' => $o->code,
            'organization_name' => $o->organization_name,
            'product_name' => $o->product_name,
            'products' => $products,
            'contact_name' => $o->contact_name,
            'status' => $o->status,
            'next_step' => $o->next_step,
            'next_step_due_on' => $o->next_step_due_on?->format('Y-m-d'),
            'stage' => $o->stage ? ['id' => $o->stage->id, 'name' => $o->stage->name, 'code' => $o->stage->code] : null,
            'owner' => $o->owner ? ['id' => $o->owner->id, 'name' => $o->owner->name] : null,
            'assignee' => $o->assignee ? ['id' => $o->assignee->id, 'name' => $o->assignee->name] : null,
            'current_step' => $o->currentStep ? [
                'id' => $o->currentStep->id,
                'title' => $o->currentStep->title,
                'due_on' => $o->currentStep->due_on?->format('Y-m-d'),
                'status' => $o->currentStep->status,
                'closed_at' => $o->currentStep->closed_at?->format('Y-m-d H:i:s'),
                'assignee' => $o->currentStep->relationLoaded('assignee') && $o->currentStep->assignee ? [
                    'id' => $o->currentStep->assignee->id,
                    'name' => $o->currentStep->assignee->name,
                ] : null,
            ] : null,
        ];
    }

    private function detail(Opportunity $o): array
    {
        return array_merge($this->listItem($o), [
            'contact_role' => $o->contact_role,
            'contact_mobile' => $o->contact_mobile,
            'contact_phone' => $o->contact_phone,
            'contact_email' => $o->contact_email,
            'address' => $o->address,
            'application_need' => $o->application_need,
            'tirage' => $o->tirage,
            'budget_band' => $o->budget_band,
            'opening_note' => $o->opening_note,
            'industry' => $o->industry,
            'industry_note' => $o->industry_note,
            'customer_value_focus' => $o->customer_value_focus,
            'main_objection' => $o->main_objection,
            'lost_reason' => $o->lost_reason,
            'result_note' => $o->result_note,
            'steps' => $o->steps?->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'due_on' => $s->due_on?->format('Y-m-d'),
                'closed_at' => $s->closed_at?->format('Y-m-d H:i:s'),
                'status' => $s->status,
                'result' => $s->result,
                'is_current' => (bool) $s->is_current,
                'assignee' => $s->assignee ? ['id' => $s->assignee->id, 'name' => $s->assignee->name] : null,
            ])->values() ?? [],
            'activities' => $o->activities?->map(fn ($a) => [
                'id' => $a->id,
                'step_id' => $a->step_id,
                'type' => $a->type,
                'subject' => $a->subject,
                'body' => $a->body,
                'happened_at' => $a->happened_at?->format('Y-m-d H:i:s'),
                'created_at' => $a->created_at?->format('Y-m-d H:i:s'),
            ])->values() ?? [],
            'referrals' => $o->referrals?->map(fn ($r) => [
                'id' => $r->id,
                'action' => $r->action,
                'step_title' => $r->step_title,
                'step_due_on' => $r->step_due_on?->format('Y-m-d'),
                'priority' => $r->priority,
                'note' => $r->note,
                'created_at' => $r->created_at?->format('Y-m-d H:i:s'),
                'from_user' => $r->fromUser ? ['id' => $r->fromUser->id, 'name' => $r->fromUser->name] : null,
                'to_user' => $r->toUser ? ['id' => $r->toUser->id, 'name' => $r->toUser->name] : null,
            ])->values() ?? [],
        ]);
    }
}
