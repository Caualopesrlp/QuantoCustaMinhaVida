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
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('recurring_template_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->date('occurred_on')->nullable();
            $table->string('description');
            $table->bigInteger('amount_cents');
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'year', 'month']);
            $table->index(['user_id', 'category_id']);
            $table->index(['user_id', 'year', 'month', 'recurring_template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
