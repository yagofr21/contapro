<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expected_incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 19, 4);
            $table->string('currency', 3)->default('BRL');
            $table->foreignId('account_id')->constrained('financial_accounts')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->date('expected_date');
            $table->timestamp('received_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'expected_date']);
            $table->index(['user_id', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expected_incomes');
    }
};
