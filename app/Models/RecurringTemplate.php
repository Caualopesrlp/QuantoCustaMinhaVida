<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'expense_type_id',
        'description',
        'amount_cents',
        'notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function expenseType(): BelongsTo
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function formattedAmount(): string
    {
        return Money::format((int) $this->amount_cents);
    }
}
