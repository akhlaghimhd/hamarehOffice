<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 32)->default('note'); // note, call, meeting, task
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();

            $table->index(['company_id', 'opportunity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_activities');
    }
};
