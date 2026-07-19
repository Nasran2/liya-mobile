<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable([
    'investor_id', 'date', 'transaction_no', 'transaction_type', 'source_type', 'source_id',
    'description', 'profit_debit', 'profit_credit', 'profit_balance',
    'purchase_debit', 'purchase_credit', 'purchase_balance', 'total_payable_balance', 'created_by'
])]
class InvestorLedgerEntry extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'profit_debit' => 'decimal:2',
            'profit_credit' => 'decimal:2',
            'profit_balance' => 'decimal:2',
            'purchase_debit' => 'decimal:2',
            'purchase_credit' => 'decimal:2',
            'purchase_balance' => 'decimal:2',
            'total_payable_balance' => 'decimal:2',
        ];
    }

    public function investor(): BelongsTo
    {
        return $this->belongsTo(Investor::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
