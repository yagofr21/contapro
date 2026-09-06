<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('portfolio_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('status', 16)->default('previewed');
            $table->string('original_filename');
            $table->char('file_sha256', 64);
            $table->json('summary');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'kind', 'file_sha256']);
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->json('raw');
            $table->json('normalized')->nullable();
            $table->char('fingerprint', 64)->nullable()->index();
            $table->string('status', 16);
            $table->json('errors')->nullable();
            $table->string('importable_type')->nullable();
            $table->unsignedBigInteger('importable_id')->nullable();
            $table->timestamps();

            $table->unique(['import_batch_id', 'row_number']);
            $table->index(['import_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('import_batches');
    }
};
