<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBudgetRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rule_needs_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'rule_wants_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'rule_investments_pct' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $sum = (int) $this->input('rule_needs_pct', 0)
                    + (int) $this->input('rule_wants_pct', 0)
                    + (int) $this->input('rule_investments_pct', 0);

                if ($sum !== 100) {
                    $validator->errors()->add(
                        'rule_needs_pct',
                        "A soma dos percentuais da regra deve ser exatamente 100% (atual: {$sum}%)."
                    );
                }
            },
        ];
    }
}
