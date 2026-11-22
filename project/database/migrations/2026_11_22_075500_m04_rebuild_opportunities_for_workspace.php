<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean early skeleton if present (dev-only path)
        Schema::dropIfExists('opportunity_activities');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('opportunity_stages');

        Schema::create('opportunity_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_won')->default(false);
            $table->boolean('is_lost')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('organization_name');
            $table->string('product_name')->nullable();
            $table->string('industry')->nullable();
            $table->string('industry_note')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('contact_mobile', 32)->nullable();
            $table->string('contact_phone', 32)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('address')->nullable();
            $table->string('application_need')->nullable();
            $table->string('tirage')->nullable();
            $table->string('budget_band')->nullable();
            $table->string('purchase_timing')->nullable();
            $table->string('customer_value_focus')->nullable();
            $table->text('main_objection')->nullable();
            $table->foreignId('stage_id')->constrained('opportunity_stages')->restrictOnDelete();
            $table->string('status', 32)->default('active');
            // active | nurturing | paused | won | lost
            $table->string('lost_reason')->nullable();
            $table->text('result_note')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('next_step')->nullable();
            $table->date('next_step_due_on')->nullable();
            $table->text('opening_note')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->foreignId('party_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status', 'stage_id']);
            $table->index(['company_id', 'owner_user_id']);
            $table->index(['company_id', 'assigned_to_user_id']);
        });

        Schema::create('opportunity_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->date('due_on')->nullable();
            $table->foreignId('assignee_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 16)->default('open'); // open | closed
            $table->text('result')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'opportunity_id', 'is_current']);
        });

        Schema::create('opportunity_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('step_id')->nullable()->constrained('opportunity_steps')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->default('note');
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();

            $table->index(['company_id', 'opportunity_id']);
        });

        Schema::create('opportunity_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 32)->default('refer'); // refer | return | release
            $table->string('step_title')->nullable();
            $table->date('step_due_on')->nullable();
            $table->string('priority', 16)->default('normal');
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'opportunity_id']);
            $table->index(['company_id', 'to_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_referrals');
        Schema::dropIfExists('opportunity_activities');
        Schema::dropIfExists('opportunity_steps');
        Schema::dropIfExists('opportunities');
        Schema::dropIfExists('opportunity_stages');
    }
};
