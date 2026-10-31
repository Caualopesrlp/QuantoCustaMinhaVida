<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class RecurringTemplateRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'expense_type_id' => ['nullable', 'integer', 'exists:expense_types,id'],
            'description' => ['required', 'string', 'max:150'],
            'amount' => [
                'required',
                function (string $attribute, mixed $value, Closure $fail): void {
                    try {
                        $cents = Money::toCents((string) $value);
                        if ($cents <= 0) {
                            $fail('O valor deve ser maior que zero.');
                        }
                    } catch (InvalidArgumentException) {
                        $fail('Informe um valor monetário válido (ex: 180,00).');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
