<?php

namespace App\Services;

use App\Models\NumberSequence;
use App\Models\Opportunity;
use App\Models\OpportunityActivity;
use App\Models\OpportunityReferral;
use App\Models\OpportunityStage;
use App\Models\OpportunityStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpportunityService
{
    public function nextCode(int $companyId): string
    {
        return DB::transaction(function () use ($companyId) {
            $seq = NumberSequence::query()
                ->where('company_id', $companyId)
                ->where('document_type', 'opportunity')
                ->lockForUpdate()
                ->first();

            if (! $seq) {
                $seq = NumberSequence::query()->create([
                    'company_id' => $companyId,
                    'document_type' => 'opportunity',
                    'prefix' => 'OPP-',
                    'next_number' => 1,
                    'padding' => 3,
                    'reset_policy' => 'never',
                ]);
            }

            $number = $seq->next_number;
            $seq->update(['next_number' => $number + 1]);

            return $seq->prefix.str_pad((string) $number, $seq->padding, '0', STR_PAD_LEFT);
        });
    }

    public function defaultStageId(int $companyId): int
    {
        $id = OpportunityStage::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('id');

        if (! $id) {
            throw ValidationException::withMessages([
                'stage_id' => 'مرحله قیف برای شرکت تعریف نشده است.',
            ]);
        }

        return (int) $id;
    }

    public function createQuick(int $companyId, int $userId, array $data): Opportunity
    {
        return DB::transaction(function () use ($companyId, $userId, $data) {
            $stageId = $data['stage_id'] ?? $this->defaultStageId($companyId);

            $products = array_values(array_filter(
                $data['products'] ?? [],
                fn ($p) => is_string($p) && trim($p) !== ''
            ));

            if (empty($products) && ! empty($data['product_name'])) {
                $products = [trim((string) $data['product_name'])];
            }

            $productName = $products[0] ?? null;
            if (count($products) > 1) {
                $productName = implode('، ', $products);
            }

            $opp = Opportunity::query()->create([
                'company_id' => $companyId,
                'code' => $this->nextCode($companyId),
                'organization_name' => $data['organization_name'],
                'product_name' => $productName,
                'products' => $products,
                'contact_name' => $data['contact_name'] ?? null,
                'stage_id' => $stageId,
                'status' => $data['status'] ?? 'active',
                'owner_user_id' => $userId,
                'assigned_to_user_id' => $data['assignee_user_id'] ?? $userId,
                'next_step' => $data['next_step'],
                'next_step_due_on' => $data['next_step_due_on'] ?? null,
                'opening_note' => $data['opening_note'] ?? null,
                'last_interaction_at' => now(),
            ]);

            OpportunityStep::query()->create([
                'company_id' => $companyId,
                'opportunity_id' => $opp->id,
                'title' => $data['next_step'],
                'due_on' => $data['next_step_due_on'] ?? null,
                'assignee_user_id' => $data['assignee_user_id'] ?? $userId,
                'status' => 'open',
                'is_current' => true,
                'sort_order' => 1,
            ]);

            return $opp->load(['stage', 'owner', 'assignee', 'currentStep']);
        });
    }

    public function updateBasic(Opportunity $opp, array $data): Opportunity
    {
        if (array_key_exists('products', $data)) {
            $products = array_values(array_filter(
                $data['products'] ?? [],
                fn ($p) => is_string($p) && trim($p) !== ''
            ));
            $opp->products = $products;
            $opp->product_name = count($products) > 1
                ? implode('، ', $products)
                : ($products[0] ?? null);
        }

        foreach ([
            'organization_name',
            'contact_name',
            'contact_role',
            'contact_mobile',
            'contact_phone',
            'contact_email',
            'next_step',
            'next_step_due_on',
            'status',
            'stage_id',
        ] as $field) {
            if (array_key_exists($field, $data)) {
                $opp->{$field} = $data[$field];
            }
        }

        $opp->last_interaction_at = now();
        $opp->save();

        if (! empty($data['next_step']) || array_key_exists('next_step_due_on', $data)) {
            $step = OpportunityStep::query()
                ->where('opportunity_id', $opp->id)
                ->where('is_current', true)
                ->where('status', 'open')
                ->first();

            if ($step) {
                if (! empty($data['next_step'])) {
                    $step->title = $data['next_step'];
                }
                if (array_key_exists('next_step_due_on', $data)) {
                    $step->due_on = $data['next_step_due_on'];
                }
                $step->save();
            }
        }

        return $opp->load(['stage', 'owner', 'assignee', 'currentStep']);
    }

    public function addActivity(Opportunity $opp, int $userId, array $data): OpportunityActivity
    {
        $body = $data['body'] ?? '';
        if (! empty($data['result'])) {
            $body = trim($body."\nنتیجه/واکنش: ".$data['result']);
        }

        $stepId = $data['step_id'] ?? $opp->currentStep?->id;

        $activity = OpportunityActivity::query()->create([
            'company_id' => $opp->company_id,
            'opportunity_id' => $opp->id,
            'step_id' => $stepId,
            'user_id' => $userId,
            'type' => $data['type'] ?? 'note',
            'subject' => $data['subject'] ?? null,
            'body' => $body,
            'happened_at' => $data['happened_at'] ?? now(),
        ]);

        $opp->last_interaction_at = now();
        $opp->save();

        return $activity;
    }

    public function assign(Opportunity $opp, int $fromUserId, array $data): Opportunity
    {
        return DB::transaction(function () use ($opp, $fromUserId, $data) {
            $toUserId = (int) $data['to_user_id'];
            $stepTitle = trim((string) ($data['step_title'] ?? ''));
            if ($stepTitle === '') {
                $stepTitle = 'ماموریت ارجاع‌شده';
            }

            OpportunityReferral::query()->create([
                'company_id' => $opp->company_id,
                'opportunity_id' => $opp->id,
                'from_user_id' => $fromUserId,
                'to_user_id' => $toUserId,
                'action' => $data['action'] ?? 'assign',
                'step_title' => $stepTitle,
                'step_due_on' => $data['step_due_on'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'note' => $data['note'] ?? null,
                'created_at' => now(),
            ]);

            OpportunityStep::query()
                ->where('opportunity_id', $opp->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $max = (int) $opp->steps()->max('sort_order');
            $step = OpportunityStep::query()->create([
                'company_id' => $opp->company_id,
                'opportunity_id' => $opp->id,
                'title' => $stepTitle,
                'due_on' => $data['step_due_on'] ?? null,
                'assignee_user_id' => $toUserId,
                'status' => 'open',
                'is_current' => true,
                'sort_order' => $max + 1,
            ]);

            $opp->assigned_to_user_id = $toUserId;
            $opp->next_step = $stepTitle;
            $opp->next_step_due_on = $data['step_due_on'] ?? null;
            $opp->last_interaction_at = now();
            $opp->save();

            OpportunityActivity::query()->create([
                'company_id' => $opp->company_id,
                'opportunity_id' => $opp->id,
                'step_id' => $step->id,
                'user_id' => $fromUserId,
                'type' => 'assign',
                'subject' => 'ارجاع فرصت',
                'body' => trim(($data['note'] ?? '')."\nماموریت: ".$stepTitle),
                'happened_at' => now(),
            ]);

            return $opp->load(['stage', 'owner', 'assignee', 'currentStep', 'steps']);
        });
    }

    public function closeStep(Opportunity $opp, OpportunityStep $step, array $data, int $userId): Opportunity
    {
        return DB::transaction(function () use ($opp, $step, $data, $userId) {
            $step->update([
                'status' => 'closed',
                'result' => $data['result'],
                'closed_at' => now(),
                'is_current' => false,
            ]);

            if (! empty($data['stage_id'])) {
                $opp->stage_id = $data['stage_id'];
            }
            if (! empty($data['status'])) {
                $opp->status = $data['status'];
            }

            if (! empty($data['next_step_title'])) {
                $max = (int) $opp->steps()->max('sort_order');
                OpportunityStep::query()->create([
                    'company_id' => $opp->company_id,
                    'opportunity_id' => $opp->id,
                    'title' => $data['next_step_title'],
                    'due_on' => $data['next_step_due_on'] ?? null,
                    'assignee_user_id' => $data['assignee_user_id'] ?? $opp->assigned_to_user_id ?? $userId,
                    'status' => 'open',
                    'is_current' => true,
                    'sort_order' => $max + 1,
                ]);
                $opp->next_step = $data['next_step_title'];
                $opp->next_step_due_on = $data['next_step_due_on'] ?? null;
            } else {
                $opp->next_step = null;
                $opp->next_step_due_on = null;
            }

            if (! empty($data['return_to_owner']) && $opp->owner_user_id) {
                $opp->assigned_to_user_id = $opp->owner_user_id;
            }

            $opp->last_interaction_at = now();
            $opp->save();

            OpportunityActivity::query()->create([
                'company_id' => $opp->company_id,
                'opportunity_id' => $opp->id,
                'step_id' => $step->id,
                'user_id' => $userId,
                'type' => 'step_close',
                'subject' => 'بستن گام',
                'body' => $data['result'],
                'happened_at' => now(),
            ]);

            return $opp->load(['stage', 'owner', 'assignee', 'currentStep', 'steps']);
        });
    }
}
