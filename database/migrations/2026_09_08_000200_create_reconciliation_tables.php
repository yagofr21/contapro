<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->string('kind', 32)->default('financial');
            $table->string('status', 16)->default('previewed');
            $table->date('period_start');
            $table->date('statement_date');
            $table->decimal('declared_balance', 19, 4);
            $table->decimal('detected_balance', 19, 4)->nullable();
            $table->decimal('delta', 19, 4)->nullable();
            $table->json('summary');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'account_id']);
        });

        Schema::create('reconciliation_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw');
            $table->date('entry_date')->nullable();
            $table->decimal('amount', 19, 4);
            $table->text('description')->nullable();
            $table->string('status', 16);
            $table->string('match_rule', 16)->nullable();
            $table->foreignId('matched_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->unique(['reconciliation_id', 'row_number']);
            $table->index(['reconciliation_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reconciliation_rows');
        Schema::dropIfExists('reconciliations');
    }
};
