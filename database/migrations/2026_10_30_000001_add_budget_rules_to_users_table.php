<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('rule_needs_pct')->default(50)->after('password');
            $table->unsignedTinyInteger('rule_wants_pct')->default(30)->after('rule_needs_pct');
            $table->unsignedTinyInteger('rule_investments_pct')->default(20)->after('rule_wants_pct');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rule_needs_pct', 'rule_wants_pct', 'rule_investments_pct']);
        });
    }
};
