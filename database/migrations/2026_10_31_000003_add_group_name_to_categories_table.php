<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('group_name', 100)->nullable()->after('user_id');
            $table->index(['user_id', 'group_name']);
        });

        DB::table('categories')->whereNull('group_name')->where('type', 'income')->update(['group_name' => 'Receitas']);
        DB::table('categories')->whereNull('group_name')->where('type', 'fixed_expense')->update(['group_name' => 'Despesas Fixas']);
        DB::table('categories')->whereNull('group_name')->where('type', 'variable_expense')->update(['group_name' => 'Despesas Variáveis']);
        DB::table('categories')->whereNull('group_name')->where('type', 'investment')->update(['group_name' => 'Investimentos']);
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'group_name']);
            $table->dropColumn('group_name');
        });
    }
};
