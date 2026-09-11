<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_rules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('keyword', 255);

            $table->string('display_name', 255)
                ->nullable();

            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'user_id',
                'account_id',
                'keyword',
            ]);

            $table->index([
                'user_id',
                'account_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_rules');
    }
};