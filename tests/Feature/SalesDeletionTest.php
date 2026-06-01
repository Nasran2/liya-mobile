<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\SaleReturnService;
use Livewire\Livewire;

function salesDeletionUser(): User
{
    return User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);
}

test('deleting a due sale restores stock removes payments and reverses customer due', function () {
    $customer = Customer::query()->create([
        'name' => 'Delete Due Customer',
        'phone' => '0771002000',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Delete Due Cable',
        'cost_price' => 200,
        'selling_price' => 1000,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);

    Livewire::actingAs(salesDeletionUser())
        ->test('pages::pos.index')
        ->call('addToCart', $product->id)
        ->set('customer_id', $customer->id)
        ->set('paymentRows', [[
            'amount' => 400,
            'method' => 'cash',
            'reference' => '',
            'cheque_bank' => '',
            'cheque_no' => '',
            'cheque_date' => '',
        ]])
        ->call('submitCheckout')
        ->assertHasNoErrors();

    $sale = Sale::query()->with('payments')->firstOrFail();

    expect((float) $sale->due_amount)->toBe(600.0)
        ->and((float) $customer->refresh()->due_balance)->toBe(600.0)
        ->and((int) $product->refresh()->stock_quantity)->toBe(4)
        ->and($sale->payments)->toHaveCount(1);

    Livewire::actingAs(salesDeletionUser())
        ->test('pages::sales.index')
        ->set('viewingSaleId', $sale->id)
        ->call('deleteSale')
        ->assertHasNoErrors()
        ->assertSet('viewingSaleId', null);

    expect(Sale::query()->whereKey($sale->id)->exists())->toBeFalse()
        ->and(Payment::query()->where('paymentable_type', Sale::class)->where('paymentable_id', $sale->id)->exists())->toBeFalse()
        ->and((float) $customer->refresh()->due_balance)->toBe(0.0)
        ->and((int) $product->refresh()->stock_quantity)->toBe(5);
});

test('deleting a sale with a cash return keeps only the correct net stock reversal', function () {
    $customer = Customer::query()->create([
        'name' => 'Delete Return Customer',
        'phone' => '0771002001',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Delete Return Charger',
        'cost_price' => 100,
        'selling_price' => 500,
        'stock_quantity' => 7,
        'is_active' => true,
    ]);

    Livewire::actingAs(salesDeletionUser())
        ->test('pages::pos.index')
        ->call('addToCart', $product->id)
        ->call('updateCartQty', 0, 3)
        ->set('customer_id', $customer->id)
        ->set('paymentRows', [[
            'amount' => 1500,
            'method' => 'cash',
            'reference' => '',
            'cheque_bank' => '',
            'cheque_no' => '',
            'cheque_date' => '',
        ]])
        ->call('submitCheckout')
        ->assertHasNoErrors();

    $sale = Sale::query()->with('items')->firstOrFail();
    app(SaleReturnService::class)->process($sale, [
        $product->id => [
            'quantity' => 1,
            'refund_price' => 500,
        ],
    ], 'cash_refund');

    expect((int) $product->refresh()->stock_quantity)->toBe(5)
        ->and(SaleReturn::query()->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(2);

    Livewire::actingAs(salesDeletionUser())
        ->test('pages::sales.index')
        ->set('viewingSaleId', $sale->id)
        ->call('deleteSale')
        ->assertHasNoErrors();

    expect(Sale::query()->whereKey($sale->id)->exists())->toBeFalse()
        ->and(SaleReturn::query()->count())->toBe(0)
        ->and(Payment::query()->count())->toBe(0)
        ->and((int) $product->refresh()->stock_quantity)->toBe(7);
});

test('deleting a sale with an exchange restores the original sale and replacement stock', function () {
    $customer = Customer::query()->create([
        'name' => 'Delete Exchange Customer',
        'phone' => '0771002002',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Delete Exchange Adapter',
        'cost_price' => 100,
        'selling_price' => 500,
        'stock_quantity' => 7,
        'is_active' => true,
    ]);

    Livewire::actingAs(salesDeletionUser())
        ->test('pages::pos.index')
        ->call('addToCart', $product->id)
        ->call('updateCartQty', 0, 3)
        ->set('customer_id', $customer->id)
        ->set('paymentRows', [[
            'amount' => 1500,
            'method' => 'cash',
            'reference' => '',
            'cheque_bank' => '',
            'cheque_no' => '',
            'cheque_date' => '',
        ]])
        ->call('submitCheckout')
        ->assertHasNoErrors();

    $sale = Sale::query()->with('items')->firstOrFail();
    app(SaleReturnService::class)->process($sale, [
        $product->id => [
            'quantity' => 1,
            'refund_price' => 500,
        ],
    ], 'exchange');

    expect((int) $product->refresh()->stock_quantity)->toBe(3)
        ->and(SaleReturn::query()->count())->toBe(1);

    Livewire::actingAs(salesDeletionUser())
        ->test('pages::sales.index')
        ->set('viewingSaleId', $sale->id)
        ->call('deleteSale')
        ->assertHasNoErrors();

    expect(Sale::query()->whereKey($sale->id)->exists())->toBeFalse()
        ->and(SaleReturn::query()->count())->toBe(0)
        ->and((int) $product->refresh()->stock_quantity)->toBe(7);
});
