<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\OpportunityStep;
use App\Services\OpportunityInsightService;
use App\Services\OpportunityService;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(
        private OpportunityService $service,
        private OpportunityInsightService $insight,
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
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($qq) use ($search) {
                $qq->where('organization_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $items = $q->orderByDesc('id')->paginate(min(50, max(10, (int) $request->query('per_page', 20))));

        return response()->json([
            'data' => collect($items->items())->map(fn (Opportunity $o) => $this->listItem($o))->values(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function store(Request $request, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'organization_name' => ['required', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'need_summary' => ['nullable', 'string'],
            'stage_id' => ['nullable', 'integer'],
            'assignee_user_id' => ['nullable', 'integer'],
            'next_step_title' => ['nullable', 'string', 'max:500'],
            'next_step_due_on' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ]);

        $opp = $this->service->createQuick($companyId, $request->user()->id, $data);

        return response()->json(['data' => $this->detail($opp)], 201);
    }

    public function show(int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $opp = Opportunity::query()
            ->where('company_id', $companyId)
            ->with([
                'stage',
                'owner:id,name',
                'assignee:id,name',
                'steps' => fn ($q) => $q->orderBy('sort_order')->with('assignee:id,name'),
                'activities' => fn ($q) => $q->orderByDesc('id')->limit(50),
                'referrals' => fn ($q) => $q->orderByDesc('id')->limit(30)->with(['fromUser:id,name', 'toUser:id,name']),
            ])
            ->findOrFail($id);

        return response()->json(['data' => $this->detail($opp)]);
    }

    public function insight(int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $opp = Opportunity::query()
            ->where('company_id', $companyId)
            ->with(['stage', 'steps', 'activities'])
            ->findOrFail($id);

        return response()->json(['data' => $this->insight->forOpportunity($opp)]);
    }

    public function update(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'organization_name' => ['sometimes', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'need_summary' => ['nullable', 'string'],
            'stage_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,nurturing,paused,won,lost'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ]);

        $opp = Opportunity::query()->where('company_id', $companyId)->findOrFail($id);
        $opp = $this->service->updateBasic($opp, $data);

        return response()->json(['data' => $this->detail($opp->load([
            'stage', 'owner:id,name', 'assignee:id,name',
            'steps' => fn ($q) => $q->orderBy('sort_order')->with('assignee:id,name'),
            'activities' => fn ($q) => $q->orderByDesc('id')->limit(50),
            'referrals' => fn ($q) => $q->orderByDesc('id')->limit(30)->with(['fromUser:id,name', 'toUser:id,name']),
        ]))]);
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

        $assigneeId = (int) ($opp->assigned_to_user_id ?? $opp->owner_user_id);
        $isReferredAway = $assigneeId > 0 && $assigneeId !== (int) $opp->owner_user_id;

        if ($isOwner && $isReferredAway) {
            return response()->json([
                'message' => 'فرصت در ارجاع است. تا بازگشت، بستن گام / تعریف گام جدید برای مالک مجاز نیست.',
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
            'type' => ['required', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'result' => ['nullable', 'string'],
            'happened_at' => ['nullable', 'date'],
            'step_id' => ['nullable', 'integer'],
        ]);

        $opp = Opportunity::query()
            ->where('company_id', $companyId)
            ->with('currentStep')
            ->findOrFail($id);

        $userId = (int) $request->user()->id;
        $isOwner = (int) $opp->owner_user_id === $userId;
        $assigneeId = (int) ($opp->assigned_to_user_id ?? $opp->owner_user_id);
        $isReferredAway = $assigneeId > 0 && $assigneeId !== (int) $opp->owner_user_id;

        // وقتی فرصت ارجاع شده، مالک قفل است تا بازگشت
        if ($isOwner && $isReferredAway) {
            return response()->json([
                'message' => 'فرصت در ارجاع است. تا بازگشت به شما، ثبت فعالیت برای مالک مجاز نیست.',
            ], 403);
        }

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
        $userId = (int) $request->user()->id;
        if ((int) $opp->owner_user_id !== $userId) {
            return response()->json([
                'message' => 'فقط مالک فرصت می‌تواند ارجاع بدهد.',
            ], 403);
        }
        $assigneeId = (int) ($opp->assigned_to_user_id ?? $opp->owner_user_id);
        if ($assigneeId !== (int) $opp->owner_user_id) {
            return response()->json([
                'message' => 'فرصت هم‌اکنون در ارجاع است. تا بازگشت به مالک، ارجاع مجدد مجاز نیست.',
            ], 403);
        }
        $opp = $this->service->assign($opp, $userId, $data);

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
            ->get(['id', 'name', 'code', 'sort_order']);

        return response()->json(['data' => $stages]);
    }

    private function listItem(Opportunity $o): array
    {
        return [
            'id' => $o->id,
            'code' => $o->code,
            'organization_name' => $o->organization_name,
            'title' => $o->title,
            'status' => $o->status,
            'priority' => $o->priority,
            'stage' => $o->stage ? ['id' => $o->stage->id, 'name' => $o->stage->name, 'code' => $o->stage->code] : null,
            'owner' => $o->owner ? ['id' => $o->owner->id, 'name' => $o->owner->name] : null,
            'assignee' => $o->assignee ? ['id' => $o->assignee->id, 'name' => $o->assignee->name] : null,
            'current_step' => $o->relationLoaded('currentStep') && $o->currentStep ? [
                'id' => $o->currentStep->id,
                'title' => $o->currentStep->title,
                'due_on' => $o->currentStep->due_on?->toDateString(),
                'assignee' => $o->relationLoaded('currentStep') && $o->currentStep->assignee ? [
                    'id' => $o->currentStep->assignee->id,
                    'name' => $o->currentStep->assignee->name,
                ] : null,
            ] : null,
        ];
    }

    private function detail(Opportunity $o): array
    {
        return [
            'id' => $o->id,
            'code' => $o->code,
            'organization_name' => $o->organization_name,
            'title' => $o->title,
            'need_summary' => $o->need_summary,
            'status' => $o->status,
            'priority' => $o->priority,
            'stage' => $o->stage ? ['id' => $o->stage->id, 'name' => $o->stage->name, 'code' => $o->stage->code] : null,
            'owner' => $o->owner ? ['id' => $o->owner->id, 'name' => $o->owner->name] : null,
            'assignee' => $o->assignee ? ['id' => $o->assignee->id, 'name' => $o->assignee->name] : null,
            'steps' => ($o->steps ?? collect())->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'status' => $s->status,
                'is_current' => (bool) $s->is_current,
                'due_on' => $s->due_on?->toDateString(),
                'closed_at' => $s->closed_at?->toIso8601String(),
                'result' => $s->result,
                'assignee' => $s->assignee ? ['id' => $s->assignee->id, 'name' => $s->assignee->name] : null,
            ])->values(),
            'activities' => ($o->activities ?? collect())->map(fn ($a) => [
                'id' => $a->id,
                'type' => $a->type,
                'subject' => $a->subject,
                'body' => $a->body,
                'result' => $a->result,
                'step_id' => $a->step_id,
                'happened_at' => $a->happened_at?->toIso8601String(),
                'created_at' => $a->created_at?->toIso8601String(),
            ])->values(),
            'referrals' => ($o->referrals ?? collect())->map(fn ($r) => [
                'id' => $r->id,
                'note' => $r->note,
                'created_at' => $r->created_at?->toIso8601String(),
                'from_user' => $r->fromUser ? ['id' => $r->fromUser->id, 'name' => $r->fromUser->name] : null,
                'to_user' => $r->toUser ? ['id' => $r->toUser->id, 'name' => $r->toUser->name] : null,
            ])->values(),
        ];
    }
}
