<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'cnpj', 'contact_name', 'email', 'phone', 'address', 'notes', 'is_active'])]
class Supplier extends Model
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

    public function formattedCnpj(): ?string
    {
        if ($this->cnpj === null) {
            return null;
        }

        return preg_replace('/\A(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})\z/', '$1.$2.$3/$4-$5', $this->cnpj)
            ?? $this->cnpj;
    }
}
