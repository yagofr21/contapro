<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'name']);
        });

        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->foreignId('broker_id')->nullable()->after('asset_id')->constrained()->nullOnDelete();
            $table->decimal('split_from', 20, 8)->nullable()->after('fees');
            $table->decimal('split_to', 20, 8)->nullable()->after('split_from');
            $table->decimal('realized_cost_basis', 19, 4)->nullable()->after('net_amount');
            $table->decimal('realized_profit_loss', 19, 4)->nullable()->after('realized_cost_basis');
        });
    }

    public function down(): void
    {
        Schema::table('asset_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('broker_id');
            $table->dropColumn([
                'split_from',
                'split_to',
                'realized_cost_basis',
                'realized_profit_loss',
            ]);
        });

        Schema::dropIfExists('brokers');
    }
};
