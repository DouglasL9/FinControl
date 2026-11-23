<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'unit_id', 'recurring_payable_id', 'paid_by_user_id', 'description', 'provider', 'amount', 'due_date', 'paid_at'])]
class Payable extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recurringPayable(): BelongsTo
    {
        return $this->belongsTo(RecurringPayable::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function paidBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'paid_by_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(PayableHistory::class);
    }

    public function status(): string
    {
        if ($this->paid_at !== null) {
            return 'paid';
        }

        return $this->due_date->lt(today()) ? 'overdue' : 'pending';
    }
}
