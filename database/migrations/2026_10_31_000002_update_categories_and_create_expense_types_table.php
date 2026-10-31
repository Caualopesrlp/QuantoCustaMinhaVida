<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('color', 20)->default('#6366f1')->after('name');
        });

        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['category_id', 'name']);
        });

        Schema::table('recurring_templates', function (Blueprint $table) {
            $table->foreignId('expense_type_id')
                ->nullable()
                ->after('category_id')
                ->constrained('expense_types')
                ->nullOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('expense_type_id')
                ->nullable()
                ->after('category_id')
                ->constrained('expense_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_type_id');
        });

        Schema::table('recurring_templates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_type_id');
        });

        Schema::dropIfExists('expense_types');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
