<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payable_id', 'user_id', 'actor_name', 'action', 'old_amount', 'new_amount'])]
class PayableHistory extends Model
{
    public const ACTION_AMOUNT_UPDATED = 'amount_updated';

    public const ACTION_PAYMENT_MARKED = 'payment_marked';

    public const ACTION_PAYMENT_REOPENED = 'payment_reopened';

    protected function casts(): array
    {
        return [
            'old_amount' => 'decimal:2',
            'new_amount' => 'decimal:2',
        ];
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return match ($this->action) {
            self::ACTION_AMOUNT_UPDATED => 'Valor alterado',
            self::ACTION_PAYMENT_MARKED => 'Pagamento marcado como pago',
            self::ACTION_PAYMENT_REOPENED => 'Pagamento desfeito',
            default => 'Alteração registrada',
        };
    }
}
