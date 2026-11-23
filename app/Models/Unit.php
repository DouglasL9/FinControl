<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'postal_code', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'phone', 'is_active'])]
class Unit extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recurringPayables(): HasMany
    {
        return $this->hasMany(RecurringPayable::class);
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }
}
