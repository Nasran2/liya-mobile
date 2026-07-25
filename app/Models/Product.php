<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'category_id',
    'brand_id',
    'unit_id',
    'name',
    'sku',
    'barcode',
    'image_path',
    'compatible_models',
    'color',
    'actual_cost',
    'cost_price',
    'selling_price',
    'wholesale_price',
    'stock_quantity',
    'minimum_stock',
    'warranty_enabled',
    'warranty_period_days',
    'is_active',
    'show_on_storefront',
    'show_storefront_price',
    'storefront_price',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    /** @var array<string, bool> */
    protected $attributes = [
        'show_on_storefront' => true,
        'show_storefront_price' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actual_cost' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'wholesale_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'minimum_stock' => 'integer',
            'warranty_enabled' => 'boolean',
            'warranty_period_days' => 'integer',
            'is_active' => 'boolean',
            'show_on_storefront' => 'boolean',
            'show_storefront_price' => 'boolean',
            'storefront_price' => 'decimal:2',
        ];
    }

    public function scopeVisibleOnStorefront(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('show_on_storefront', true);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
