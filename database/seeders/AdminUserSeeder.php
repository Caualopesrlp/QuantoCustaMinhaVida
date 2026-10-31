<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Remove conta genérica antiga caso exista
        User::where('email', 'admin@finanflow.com')->delete();

        // Garante que apenas o perfil principal tenha permissão de Admin
        $primaryUser = User::where('email', 'caualopes.icm@gmail.com')->first();

        if ($primaryUser) {
            $primaryUser->update([
                'role' => 'admin',
                'is_admin' => true,
            ]);

            User::where('id', '!=', $primaryUser->id)->update([
                'role' => 'user',
                'is_admin' => false,
            ]);

            return;
        }

        // Caso o banco esteja vazio (ambiente novo), cria a conta principal já como Admin
        User::create([
            'name' => 'Cauã Lopes',
            'email' => 'caualopes.icm@gmail.com',
            'password' => Hash::make('12345678'),
            'role' => 'admin',
            'is_admin' => true,
            'rule_needs_pct' => 50,
            'rule_wants_pct' => 30,
            'rule_investments_pct' => 20,
        ]);
    }
}
