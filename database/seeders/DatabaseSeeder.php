<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Services\SpreadsheetImportService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(SpreadsheetImportService $importService): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'caualopes.icm@gmail.com'],
            [
                'name' => 'Cauã Lopes',
                'password' => Hash::make('password'),
                'rule_needs_pct' => 50,
                'rule_wants_pct' => 30,
                'rule_investments_pct' => 20,
            ]
        );

        $spreadsheetPath = base_path('Finanças.xlsx');
        if (is_file($spreadsheetPath) && $user->transactions()->count() === 0) {
            $importService->importForUser($user, $spreadsheetPath);
        }
    }
}
