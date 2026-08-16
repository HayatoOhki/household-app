<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('transaction_date');
            $table->string('type', 30)->index();
            $table->foreignId('account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('counterparty_name', 255)->nullable();
            $table->decimal('amount', 15, 2);
            $table->date('withdrawal_date')->nullable();
            $table->decimal('expense_ratio', 5, 2)->default(0);
            $table->boolean('expense_registered')->default(false);
            $table->boolean('receipt_saved')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'counterparty_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
