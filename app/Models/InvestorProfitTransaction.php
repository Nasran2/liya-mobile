<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'sale_id', 'investor_id', 'date', 'sales_subtotal', 'discount', 'tax', 
    'net_sales_amount', 'cost_of_goods', 'gross_profit', 'deducted_expenses', 
    'eligible_profit', 'investor_percentage', 'investor_profit_amount', 
    'paid_amount', 'status', 'calculation_method', 'created_by'
])]
class InvestorProfitTransaction extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'sales_subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax' => 'decimal:2',
            'net_sales_amount' => 'decimal:2',
            'cost_of_goods' => 'decimal:2',
            'gross_profit' => 'decimal:2',
            'deducted_expenses' => 'decimal:2',
            'eligible_profit' => 'decimal:2',
            'investor_percentage' => 'decimal:4',
            'investor_profit_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function paymentAllocations(): MorphMany
    {
        return $this->morphMany(InvestorPaymentAllocation::class, 'allocatable');
    }
}
