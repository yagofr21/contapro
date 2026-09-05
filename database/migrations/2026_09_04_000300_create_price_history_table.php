<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('price_date');
            $table->decimal('open', 20, 8)->nullable();
            $table->decimal('high', 20, 8)->nullable();
            $table->decimal('low', 20, 8)->nullable();
            $table->decimal('close', 20, 8);
            $table->decimal('adjusted_close', 20, 8)->nullable();
            $table->unsignedBigInteger('volume')->nullable();
            $table->timestamps();

            $table->unique(['asset_id', 'price_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_history');
    }
};
