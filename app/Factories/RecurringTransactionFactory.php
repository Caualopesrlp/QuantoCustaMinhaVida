<?php

declare(strict_types=1);

namespace App\Factories;

use App\Models\RecurringTemplate;
use Carbon\CarbonImmutable;

class RecurringTransactionFactory
{
    /**
     * Constrói os atributos de uma Transaction mensal a partir de um RecurringTemplate (aba modelo X26).
     *
     * @return array<string, mixed>
     */
    public function makeAttributesForMonth(RecurringTemplate $template, int $year, int $month): array
    {
        $occurredOn = CarbonImmutable::create($year, $month, 1)->toDateString();

        return [
            'user_id' => $template->user_id,
            'category_id' => $template->category_id,
            'expense_type_id' => $template->expense_type_id,
            'recurring_template_id' => $template->id,
            'year' => $year,
            'month' => $month,
            'occurred_on' => $occurredOn,
            'description' => $template->description,
            'amount_cents' => $template->amount_cents,
            'notes' => $template->notes,
        ];
    }
}
