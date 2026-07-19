<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'code', 'name', 'company_name', 'phone', 'whatsapp', 'email', 'address', 'nic', 
    'start_date', 'default_profit_percentage', 'profit_calculation_method', 
    'opening_profit_balance', 'opening_purchase_balance', 'notes', 'is_active', 
    'created_by', 'updated_by'
])]
class Investor extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'is_active' => 'boolean',
            'default_profit_percentage' => 'decimal:4',
            'opening_profit_balance' => 'decimal:2',
            'opening_purchase_balance' => 'decimal:2',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function saleAllocations(): HasMany
    {
        return $this->hasMany(SaleInvestorAllocation::class);
    }

    public function purchaseFundings(): HasMany
    {
        return $this->hasMany(InvestorPurchaseFunding::class);
    }

    public function profitTransactions(): HasMany
    {
        return $this->hasMany(InvestorProfitTransaction::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvestorPayment::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(InvestorLedgerEntry::class);
    }
    
    public function getProfitBalanceAttribute()
    {
        return $this->ledgerEntries()->orderBy('id', 'desc')->value('profit_balance') ?? 0;
    }
    
    public function getPurchaseBalanceAttribute()
    {
        return $this->ledgerEntries()->orderBy('id', 'desc')->value('purchase_balance') ?? 0;
    }
    
    public function getTotalPayableAttribute()
    {
        return $this->ledgerEntries()->orderBy('id', 'desc')->value('total_payable_balance') ?? 0;
    }
}
