<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table) {
            $table->decimal('credit_limit', 19, 4)->nullable()->after('initial_balance');
            $table->unsignedTinyInteger('credit_closing_day')->nullable()->after('credit_limit');
            $table->unsignedTinyInteger('credit_due_day')->nullable()->after('credit_closing_day');
        });
    }

    public function down(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'credit_closing_day', 'credit_due_day']);
        });
    }
};
