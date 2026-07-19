<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['investor_payment_id', 'allocatable_type', 'allocatable_id', 'amount'])]
class InvestorPaymentAllocation extends Model
{
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(InvestorPayment::class, 'investor_payment_id');
    }

    public function allocatable(): MorphTo
    {
        return $this->morphTo();
    }
}
