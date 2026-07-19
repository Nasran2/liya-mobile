<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['purchase_id', 'investor_id', 'funded_amount', 'repaid_amount', 'payment_method', 'reference_no', 'bank_account', 'notes', 'status'])]
class InvestorPurchaseFunding extends Model
{
    protected function casts(): array
    {
        return [
            'funded_amount' => 'decimal:2',
            'repaid_amount' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }
    
    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(InvestorPaymentAllocation::class, 'allocatable');
    }
}
