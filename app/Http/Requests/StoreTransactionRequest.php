<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;

class StoreTransactionRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'occurred_on' => ['nullable', 'date'],
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
                        $fail('Informe um valor monetário válido (ex: 45,90).');
                    }
                },
            ],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
