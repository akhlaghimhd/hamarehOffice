<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\OpportunityStage;
use App\Models\OpportunityStep;
use App\Services\OpportunityService;
use App\Support\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(private OpportunityService $service) {}

    public function index(Request $request, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $q = Opportunity::query()
            ->with(['stage:id,name,code', 'owner:id,name', 'assignee:id,name', 'currentStep'])
            ->where('company_id', $companyId);

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
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('product_name', 'like', "%{$search}%");
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
            'industry' => ['nullable', 'string', 'max:255'],
            'industry_note' => ['nullable', 'string', 'max:500'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_role' => ['nullable', 'string', 'max:255'],
            'contact_mobile' => ['nullable', 'string', 'max:32'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:1000'],
            'application_need' => ['nullable', 'string', 'max:2000'],
            'tirage' => ['nullable', 'string', 'max:255'],
            'budget_band' => ['nullable', 'string', 'max:255'],
            'purchase_timing' => ['nullable', 'string', 'max:255'],
            'customer_value_focus' => ['nullable', 'string', 'max:500'],
            'main_objection' => ['nullable', 'string', 'max:1000'],
            'next_step' => ['required', 'string', 'max:500'],
            'next_step_due_on' => ['nullable', 'date'],
            'stage_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,nurturing,paused,won,lost'],
            'lost_reason' => ['nullable', 'string', 'max:255'],
            'result_note' => ['nullable', 'string'],
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
                'steps' => fn ($q) => $q->orderBy('sort_order'),
                'activities' => fn ($q) => $q->orderByDesc('happened_at')->limit(50),
                'referrals' => fn ($q) => $q->orderByDesc('id')->limit(20),
            ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return response()->json(['data' => $this->detail($opp)]);
    }

    public function update(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'organization_name' => ['sometimes', 'required', 'string', 'max:255'],
            'products' => ['nullable', 'array'],
            'products.*' => ['string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_role' => ['nullable', 'string', 'max:255'],
            'contact_mobile' => ['nullable', 'string', 'max:32'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'next_step' => ['nullable', 'string', 'max:500'],
            'next_step_due_on' => ['nullable', 'date'],
            'stage_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'in:active,nurturing,paused,won,lost'],
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
        ]);

        $opp = Opportunity::query()->where('company_id', $companyId)->findOrFail($id);
        $step = OpportunityStep::query()
            ->where('opportunity_id', $opp->id)
            ->where('is_current', true)
            ->where('status', 'open')
            ->firstOrFail();

        $opp = $this->service->closeStep($opp, $step, $data, $request->user()->id);

        return response()->json(['data' => $this->detail($opp->load([
            'stage', 'owner:id,name', 'assignee:id,name',
            'steps' => fn ($q) => $q->orderBy('sort_order'),
            'activities' => fn ($q) => $q->orderByDesc('happened_at')->limit(50),
        ]))]);
    }

    public function storeActivity(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $opp = Opportunity::query()
            ->with('currentStep')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        $activity = $this->service->addActivity($opp, $request->user()->id, $data);

        return response()->json(['data' => [
            'id' => $activity->id,
            'subject' => $activity->subject,
            'body' => $activity->body,
            'type' => $activity->type,
            'happened_at' => $activity->happened_at?->toIso8601String(),
        ]], 201);
    }

    public function assign(Request $request, int $id, CompanyContext $context): JsonResponse
    {
        $companyId = $context->check();

        $data = $request->validate([
            'to_user_id' => ['required', 'integer', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:1000'],
            'action' => ['nullable', 'string', 'max:50'],
        ]);

        $opp = Opportunity::query()->where('company_id', $companyId)->findOrFail($id);
        $opp = $this->service->assign($opp, $request->user()->id, $data);

        return response()->json(['data' => $this->listItem($opp)]);
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
                'status' => $s->status,
                'result' => $s->result,
                'is_current' => $s->is_current,
            ])->values() ?? [],
        ]);
    }
}
