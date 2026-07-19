<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'investor_id', 'payment_no', 'date', 'payment_type', 'profit_payment_amount', 
    'purchase_repayment_amount', 'total_payment', 'payment_method', 'reference_no', 
    'notes', 'created_by'
])]
class InvestorPayment extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'profit_payment_amount' => 'decimal:2',
            'purchase_repayment_amount' => 'decimal:2',
            'total_payment' => 'decimal:2',
        ];
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(InvestorPaymentAllocation::class);
    }
}
