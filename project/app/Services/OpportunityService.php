<?php

namespace App\Services;

use App\Models\NumberSequence;
use App\Models\Opportunity;
use App\Models\OpportunityActivity;
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
                'contact_mobile' => $data['contact_mobile'] ?? null,
                'contact_phone' => $data['contact_phone'] ?? null,
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
