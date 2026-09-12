<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installments', function (Blueprint $table) {
            $table->decimal('total_amount', 19, 4)->nullable()->after('amount');
        });

        DB::table('installments')->update([
            'total_amount' => DB::raw('amount * total_count'),
        ]);

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('installment_id')
                ->nullable()
                ->after('transfer_id')
                ->constrained('installments')
                ->nullOnDelete();

            $table->index('installment_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['installment_id']);
            $table->dropColumn('installment_id');
        });

        Schema::table('installments', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });
    }
};
