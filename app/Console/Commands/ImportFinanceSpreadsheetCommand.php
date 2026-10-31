<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\SpreadsheetImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ImportFinanceSpreadsheetCommand extends Command
{
    protected $signature = 'finance:import
                            {file=Finanças.xlsx : Caminho do arquivo .xlsx}
                            {--email= : E-mail do usuário destino}
                            {--name=Cauã Lopes : Nome do usuário caso precise ser criado}
                            {--password=password : Senha inicial caso o usuário seja criado}';

    protected $description = 'Importa os dados da planilha Finanças.xlsx localmente para a conta de um usuário sem expor dados no código-fonte';

    public function handle(SpreadsheetImportService $importService): int
    {
        $fileArg = (string) $this->argument('file');
        $filePath = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

        if (! is_file($filePath)) {
            $this->error("Arquivo não encontrado em: {$filePath}");

            return self::FAILURE;
        }

        $email = (string) ($this->option('email') ?: '');
        $user = $email !== ''
            ? User::query()->where('email', $email)->first()
            : User::query()->first();

        if (! $user) {
            $targetEmail = $email !== '' ? $email : 'caua@quantocustaminhavida.local';
            $user = User::query()->create([
                'name' => (string) $this->option('name'),
                'email' => $targetEmail,
                'password' => Hash::make((string) $this->option('password')),
                'rule_needs_pct' => 50,
                'rule_wants_pct' => 30,
                'rule_investments_pct' => 20,
            ]);

            $this->info("Usuário criado: {$user->name} <{$user->email}>");
        } else {
            $this->info("Importando para o usuário: {$user->name} <{$user->email}>");
        }

        $result = $importService->importForUser($user, $filePath);

        $this->info("✅ Templates fixos (X26) importados: {$result['templates_imported']}");
        $this->info("✅ Transações mensais importadas: {$result['transactions_imported']}");
        $this->info('📅 Meses processados: ' . implode(', ', $result['months_processed']));

        return self::SUCCESS;
    }
}
