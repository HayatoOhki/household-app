<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name', 100);

            $table->string('type', 30);

            $table->unsignedInteger('sort_order')
                ->default(10000);

            $table->timestamps();

            $table->unique([
                'user_id',
                'name',
            ]);

            $table->index([
                'user_id',
                'type',
            ]);

            $table->index([
                'user_id',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};