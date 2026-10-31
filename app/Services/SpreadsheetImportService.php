<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\RecurringTemplateRepositoryInterface;
use App\Support\Money;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SpreadsheetImportService
{
    private const MONTH_MAP = [
        'janeiro' => 1,
        'fevereiro' => 2,
        'março' => 3,
        'marco' => 3,
        'abril' => 4,
        'maio' => 5,
        'junho' => 6,
        'julho' => 7,
        'agosto' => 8,
        'setembro' => 9,
        'outubro' => 10,
        'novembro' => 11,
        'dezembro' => 12,
    ];

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly RecurringTemplateRepositoryInterface $recurringTemplateRepository,
    ) {}

    /**
     * Importa o arquivo .xlsx no padrão da planilha Finanças.xlsx para a conta do usuário.
     *
     * @return array{templates_imported: int, transactions_imported: int, months_processed: array<int, string>}
     */
    public function importForUser(User $user, string $filePath): array
    {
        if (! is_file($filePath)) {
            throw new InvalidArgumentException("Arquivo não encontrado: {$filePath}");
        }

        $this->categoryService->ensureDefaultCategoriesForUser($user);

        $sharedStrings = $this->readSharedStrings($filePath);
        $sheets = $this->readWorkbookSheets($filePath);

        $templatesImported = 0;
        $transactionsImported = 0;
        $monthsProcessed = [];

        foreach ($sheets as $sheet) {
            $sheetName = trim($sheet['name']);
            $sheetXml = $this->readEntryFromZip($filePath, $sheet['target']);
            if ($sheetXml === null) {
                continue;
            }

            if (mb_strtoupper($sheetName) === 'X26') {
                $rows = $this->extractSectionRows($sheetXml, $sharedStrings);
                foreach ($rows as $row) {
                    if ($row['amount_cents'] <= 0) {
                        continue;
                    }

                    $category = $this->resolveOrCreateCategory($user, $row['raw_label'], $row['section']);

                    $existing = $this->recurringTemplateRepository->findByDescriptionAndCategory(
                        userId: (int) $user->id,
                        categoryId: (int) $category->id,
                        description: $category->name
                    );

                    if ($existing) {
                        $this->recurringTemplateRepository->update($existing, [
                            'amount_cents' => $row['amount_cents'],
                            'notes' => $row['notes'],
                            'is_active' => true,
                        ]);
                    } else {
                        $this->recurringTemplateRepository->create([
                            'user_id' => $user->id,
                            'category_id' => $category->id,
                            'description' => $category->name,
                            'amount_cents' => $row['amount_cents'],
                            'notes' => $row['notes'],
                            'is_active' => true,
                        ]);
                    }

                    $templatesImported++;
                }

                continue;
            }

            $parsedPeriod = $this->parseMonthYearFromSheetName($sheetName);
            if ($parsedPeriod === null) {
                continue;
            }

            ['year' => $year, 'month' => $month] = $parsedPeriod;
            $monthsProcessed[] = "{$sheetName} ({$month}/{$year})";

            $rows = $this->extractSectionRows($sheetXml, $sharedStrings);
            foreach ($rows as $row) {
                if ($row['amount_cents'] <= 0) {
                    continue;
                }

                $category = $this->resolveOrCreateCategory($user, $row['raw_label'], $row['section']);

                $recurringTemplate = null;
                if (in_array($category->type, [CategoryType::Income, CategoryType::FixedExpense], true)) {
                    $recurringTemplate = $this->recurringTemplateRepository->findByDescriptionAndCategory(
                        userId: (int) $user->id,
                        categoryId: (int) $category->id,
                        description: $category->name
                    );
                }

                $description = $row['notes'] && $category->type === CategoryType::VariableExpense
                    ? $row['notes']
                    : $category->name;

                $notes = ($description === $row['notes']) ? null : $row['notes'];

                Transaction::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'category_id' => $category->id,
                        'year' => $year,
                        'month' => $month,
                        'description' => $description,
                    ],
                    [
                        'recurring_template_id' => $recurringTemplate?->id,
                        'occurred_on' => CarbonImmutable::create($year, $month, 1)->toDateString(),
                        'amount_cents' => $row['amount_cents'],
                        'notes' => $notes,
                    ]
                );

                $transactionsImported++;
            }
        }

        return [
            'templates_imported' => $templatesImported,
            'transactions_imported' => $transactionsImported,
            'months_processed' => $monthsProcessed,
        ];
    }

    /**
     * @param array<int, string> $sharedStrings
     * @return array<int, array{section: string, raw_label: string, amount_cents: int, notes: ?string}>
     */
    private function extractSectionRows(string $sheetXml, array $sharedStrings): array
    {
        $xml = new SimpleXMLElement($sheetXml);
        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $rows = $xml->xpath('//m:sheetData/m:row') ?: [];
        $currentSection = null;
        $extracted = [];

        foreach ($rows as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $ref = (string) $c['r'];
                $col = preg_replace('/\d+/', '', $ref) ?: '';
                $type = (string) $c['t'];
                $val = isset($c->v) ? (string) $c->v : '';

                if ($type === 's' && $val !== '' && isset($sharedStrings[(int) $val])) {
                    $val = $sharedStrings[(int) $val];
                } elseif (isset($c->is)) {
                    $c->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                    $texts = $c->xpath('.//m:t') ?: [];
                    $val = implode('', array_map(static fn ($t) => (string) $t, $texts));
                }

                $cells[$col] = trim($val);
            }

            $colA = $cells['A'] ?? '';
            $colB = $cells['B'] ?? '';
            $colC = $cells['C'] ?? '';

            if ($colA === '') {
                continue;
            }

            $upperA = mb_strtoupper($colA);

            if (str_contains($upperA, 'RECEITA') && ! str_contains($upperA, 'TOTAL') && ! str_contains($upperA, 'RESUMO')) {
                if ($colB === '') {
                    $currentSection = 'income';

                    continue;
                }
            }

            if (str_contains($upperA, 'DESPESAS FIXAS')) {
                $currentSection = 'fixed_expense';

                continue;
            }

            if (str_contains($upperA, 'DESPESAS VARIÁVEIS') || str_contains($upperA, 'DESPESAS VARIAVEIS')) {
                $currentSection = 'variable_expense';

                continue;
            }

            if (str_contains($upperA, 'RESUMO DO MÊS') || str_contains($upperA, '% DA RENDA')) {
                $currentSection = null;

                continue;
            }

            if ($currentSection === null) {
                continue;
            }

            // Ignora cabeçalhos e linhas de subtotal/dica
            if (
                in_array($upperA, ['DESCRIÇÃO', 'DESCRICAO', 'TOTAL RECEITA', 'SUBTOTAL FIXAS', 'SUBTOTAL VARIÁVEIS'], true)
                || str_starts_with($colA, '💡')
            ) {
                continue;
            }

            $amountCents = ($colB !== '' && is_numeric($colB))
                ? Money::toCents((float) $colB)
                : 0;

            $extracted[] = [
                'section' => $currentSection,
                'raw_label' => $colA,
                'amount_cents' => $amountCents,
                'notes' => $colC !== '' ? $colC : null,
            ];
        }

        return $extracted;
    }

    private function resolveOrCreateCategory(User $user, string $rawLabel, string $section): Category
    {
        [$icon, $cleanName] = $this->splitIconAndName($rawLabel);

        $type = match (true) {
            mb_strtolower($cleanName) === 'investimento' => CategoryType::Investment,
            $section === 'income' => CategoryType::Income,
            $section === 'fixed_expense' => CategoryType::FixedExpense,
            default => CategoryType::VariableExpense,
        };

        $existing = $this->categoryRepository->findByNameAndType((int) $user->id, $cleanName, $type);
        if ($existing) {
            return $existing;
        }

        return $this->categoryRepository->create([
            'user_id' => $user->id,
            'name' => $cleanName,
            'icon' => $icon ?: ($type === CategoryType::Income ? '💰' : '📌'),
            'type' => $type->value,
            'sort_order' => 10,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitIconAndName(string $rawLabel): array
    {
        $trimmed = trim($rawLabel);

        if (preg_match('/^([^\p{L}\p{N}\s]+)\s*(.+)$/u', $trimmed, $matches)) {
            return [trim($matches[1]), trim($matches[2])];
        }

        return ['', $trimmed];
    }

    /**
     * @return ?array{year: int, month: int}
     */
    private function parseMonthYearFromSheetName(string $sheetName): ?array
    {
        if (! preg_match('/^([A-Za-zÀ-ÿ]+)\s*(\d{2,4})$/u', trim($sheetName), $matches)) {
            return null;
        }

        $monthKey = mb_strtolower($matches[1]);
        if (! isset(self::MONTH_MAP[$monthKey])) {
            return null;
        }

        $rawYear = (int) $matches[2];
        $year = $rawYear < 100 ? 2000 + $rawYear : $rawYear;

        return [
            'year' => $year,
            'month' => self::MONTH_MAP[$monthKey],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function readSharedStrings(string $filePath): array
    {
        $xmlContent = $this->readEntryFromZip($filePath, 'xl/sharedStrings.xml');
        if ($xmlContent === null) {
            return [];
        }

        $xml = new SimpleXMLElement($xmlContent);
        $xml->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $strings = [];
        foreach ($xml->xpath('//m:si') ?: [] as $si) {
            $si->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
            $texts = $si->xpath('.//m:t') ?: [];
            $strings[] = implode('', array_map(static fn ($t) => (string) $t, $texts));
        }

        return $strings;
    }

    /**
     * @return array<int, array{name: string, target: string}>
     */
    private function readWorkbookSheets(string $filePath): array
    {
        $workbookXml = $this->readEntryFromZip($filePath, 'xl/workbook.xml');
        $relsXml = $this->readEntryFromZip($filePath, 'xl/_rels/workbook.xml.rels');

        if ($workbookXml === null || $relsXml === null) {
            throw new RuntimeException('Estrutura de planilha .xlsx inválida.');
        }

        $rels = new SimpleXMLElement($relsXml);
        $relMap = [];
        foreach ($rels->Relationship as $rel) {
            $id = (string) $rel['Id'];
            $target = ltrim((string) $rel['Target'], '/');
            if (! str_starts_with($target, 'xl/')) {
                $target = 'xl/' . $target;
            }
            $relMap[$id] = $target;
        }

        $wb = new SimpleXMLElement($workbookXml);
        $wb->registerXPathNamespace('m', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');

        $sheets = [];
        foreach ($wb->xpath('//m:sheets/m:sheet') ?: [] as $sheet) {
            $attrs = $sheet->attributes();
            $rAttrs = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $rId = (string) ($rAttrs['id'] ?? '');
            $name = (string) ($attrs['name'] ?? '');

            if ($name !== '' && isset($relMap[$rId])) {
                $sheets[] = [
                    'name' => $name,
                    'target' => $relMap[$rId],
                ];
            }
        }

        return $sheets;
    }

    private function readEntryFromZip(string $filePath, string $entryName): ?string
    {
        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            if ($zip->open($filePath) === true) {
                $content = $zip->getFromName($entryName);
                $zip->close();

                return $content !== false ? $content : null;
            }
        }

        // Fallback via comando unzip do sistema caso a extensão ext-zip não esteja ativa no CLI host
        $cmd = sprintf('unzip -p %s %s 2>/dev/null', escapeshellarg($filePath), escapeshellarg($entryName));
        $output = shell_exec($cmd);

        return is_string($output) && $output !== '' ? $output : null;
    }
}
