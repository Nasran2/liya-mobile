<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $warrantyEnabled = fake()->boolean(35);

        return [
            'category_id' => Category::factory(),
            'brand_id' => Brand::factory(),
            'unit_id' => Unit::factory(),
            'name' => fake()->words(3, true),
            'sku' => fake()->unique()->bothify('SKU-####??'),
            'barcode' => fake()->boolean(70) ? fake()->unique()->ean13() : null,
            'image_path' => null,
            'compatible_models' => fake()->boolean(60) ? fake()->words(2, true) : null,
            'color' => fake()->safeColorName(),
            'cost_price' => fake()->randomFloat(2, 80, 2400),
            'selling_price' => fake()->randomFloat(2, 120, 3600),
            'wholesale_price' => fake()->boolean(60)
                ? fake()->randomFloat(2, 100, 3200)
                : null,
            'stock_quantity' => fake()->numberBetween(0, 140),
            'minimum_stock' => fake()->numberBetween(0, 20),
            'warranty_enabled' => $warrantyEnabled,
            'warranty_period_days' => $warrantyEnabled
                ? fake()->randomElement([7, 30, 90, 180, 365])
                : null,
            'is_active' => fake()->boolean(90),
            'show_on_storefront' => true,
            'show_storefront_price' => true,
            'storefront_price' => null,
        ];
    }
}
