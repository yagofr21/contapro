<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 32);
            $table->string('name');
            $table->string('type', 16);
            $table->string('market', 16);
            $table->string('currency', 3);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['market', 'symbol']);
        });

        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('currency', 3)->default('BRL');
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
        });

        Schema::create('portfolio_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 20, 8)->default(0);
            $table->decimal('average_cost', 20, 8)->default(0);
            $table->timestamps();

            $table->unique(['portfolio_id', 'asset_id']);
        });

        Schema::create('asset_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portfolio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->restrictOnDelete();
            $table->string('type', 16);
            $table->decimal('quantity', 20, 8)->default(0);
            $table->decimal('unit_price', 20, 8)->default(0);
            $table->decimal('fees', 19, 4)->default(0);
            $table->date('transaction_date');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['portfolio_id', 'transaction_date']);
            $table->index(['asset_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transactions');
        Schema::dropIfExists('portfolio_holdings');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('assets');
    }
};
