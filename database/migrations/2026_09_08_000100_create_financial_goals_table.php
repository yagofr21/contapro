<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('target_amount', 19, 4);
            $table->string('currency', 3)->default('BRL');
            $table->foreignId('account_id')->nullable()->constrained('financial_accounts')->cascadeOnDelete();
            $table->date('target_date');
            $table->timestamps();

            $table->index(['user_id', 'target_date']);
            $table->index(['user_id', 'account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_goals');
    }
};
