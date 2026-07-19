<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['sale_id', 'investor_id', 'percentage', 'profit_amount', 'status'])]
class SaleInvestorAllocation extends Model
{
    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:4',
            'profit_amount' => 'decimal:2',
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
}
