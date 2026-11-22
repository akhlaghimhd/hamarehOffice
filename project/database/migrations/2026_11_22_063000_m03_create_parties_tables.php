<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parties', function (Blueprint $table) {
            $table->id();
            $table->string('type', 16); // person | organization
            $table->string('name');
            $table->string('national_id', 32)->nullable();
            $table->string('economic_code', 32)->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'name']);
        });

        Schema::create('company_party_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32); // customer, supplier, contractor, partner, sales_representative
            $table->string('local_code', 64)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('account_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'party_id', 'role']);
            $table->index(['company_id', 'role', 'is_active']);
        });

        Schema::create('party_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('party_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('mobile', 32)->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['party_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('party_contacts');
        Schema::dropIfExists('company_party_roles');
        Schema::dropIfExists('parties');
    }
};
