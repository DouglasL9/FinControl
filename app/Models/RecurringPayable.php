<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'unit_id', 'supplier_id', 'created_by_user_id', 'description', 'provider', 'amount', 'due_day', 'starts_on', 'ends_on', 'is_active'])]
class RecurringPayable extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_day' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }
}
