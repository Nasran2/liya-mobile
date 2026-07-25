<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['purchase_id', 'product_id', 'quantity', 'actual_cost', 'cost_price', 'selling_price', 'actual_subtotal', 'subtotal'])]
class PurchaseItem extends Model
{
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'actual_cost' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'actual_subtotal' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
