<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

test('the storefront is public and only lists products enabled for the website', function () {
    Product::factory()->create([
        'name' => 'Visible Wireless Earbuds',
        'is_active' => true,
        'show_on_storefront' => true,
    ]);
    Product::factory()->create([
        'name' => 'Website Hidden Charger',
        'is_active' => true,
        'show_on_storefront' => false,
    ]);
    Product::factory()->create([
        'name' => 'Inactive Phone Case',
        'is_active' => false,
        'show_on_storefront' => true,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Visible Wireless Earbuds')
        ->assertDontSee('Website Hidden Charger')
        ->assertDontSee('Inactive Phone Case')
        ->assertSee('Staff login')
        ->assertSee('Shop by category')
        ->assertSee('New arrivals')
        ->assertSee('Why customers choose us')
        ->assertSee('Retail & wholesale', false);
});

test('the storefront uses a website-only price and supports hiding prices', function () {
    Product::factory()->create([
        'name' => 'Special Price Powerbank',
        'is_active' => true,
        'show_on_storefront' => true,
        'selling_price' => 7500,
        'storefront_price' => 6990,
        'show_storefront_price' => true,
    ]);
    Product::factory()->create([
        'name' => 'Call For Price Smartphone',
        'is_active' => true,
        'show_on_storefront' => true,
        'selling_price' => 125000,
        'storefront_price' => 119000,
        'show_storefront_price' => false,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Rs 6,990.00')
        ->assertSee('Ask for price')
        ->assertDontSee('Rs 7,500.00')
        ->assertDontSee('Rs 119,000.00')
        ->assertDontSee('Rs 125,000.00');
});

test('customers can search and filter storefront products', function () {
    $phones = Category::factory()->create(['name' => 'Smartphones']);
    $accessories = Category::factory()->create(['name' => 'Accessories']);

    $phone = Product::factory()->for($phones)->create([
        'name' => 'Nova Smartphone',
        'is_active' => true,
        'show_on_storefront' => true,
    ]);
    $accessory = Product::factory()->for($accessories)->create([
        'name' => 'Braided Charging Cable',
        'is_active' => true,
        'show_on_storefront' => true,
    ]);

    Livewire::test('pages::storefront')
        ->set('search', 'Nova')
        ->assertSeeHtml('wire:key="storefront-product-'.$phone->id.'"')
        ->assertDontSeeHtml('wire:key="storefront-product-'.$accessory->id.'"')
        ->set('search', '')
        ->set('categoryId', $accessories->id)
        ->assertSeeHtml('wire:key="storefront-product-'.$accessory->id.'"')
        ->assertDontSeeHtml('wire:key="storefront-product-'.$phone->id.'"');
});

test('staff can configure storefront visibility and pricing from the product form', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    Livewire::test('pages::products.edit', ['product' => $product])
        ->set('form.show_on_storefront', false)
        ->set('form.show_storefront_price', false)
        ->set('form.storefront_price', 4990)
        ->call('save')
        ->assertHasNoErrors();

    $product->refresh();

    expect($product->show_on_storefront)->toBeFalse()
        ->and($product->show_storefront_price)->toBeFalse()
        ->and($product->storefront_price)->toBe('4990.00');
});
