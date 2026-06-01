<?php

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Livewire\Livewire;

function returnWorkflowUser(): User
{
    return User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);
}

function saleForReturn(Customer $customer, Product $product, array $overrides = []): Sale
{
    $sale = Sale::query()->create(array_merge([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-RET-'.fake()->unique()->numberBetween(1000, 9999),
        'date' => today()->toDateString(),
        'subtotal_amount' => 500,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 500,
        'paid_amount' => 500,
        'due_amount' => 0,
        'payment_status' => 'paid',
        'profit' => 300,
    ], $overrides));

    $sale->items()->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'cost_price' => 200,
        'selling_price' => 500,
        'subtotal' => 500,
    ]);

    return $sale;
}

test('same product exchange records replacement cost as expense', function () {
    $customer = Customer::query()->create([
        'name' => 'Exchange Customer',
        'phone' => '0771111111',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Exchange Screen Protector',
        'cost_price' => 200,
        'selling_price' => 500,
        'stock_quantity' => 5,
        'is_active' => true,
    ]);
    $sale = saleForReturn($customer, $product);

    Livewire::actingAs(returnWorkflowUser())
        ->test('pages::pos.returns')
        ->call('selectSale', $sale->id)
        ->call('updateReturnQty', $product->id, 1)
        ->set('returnType', 'exchange')
        ->set('returnNotes', 'Same product replacement')
        ->call('submitReturn')
        ->assertHasNoErrors();

    $expense = Expense::query()->firstOrFail();

    expect($product->refresh()->stock_quantity)->toBe(4)
        ->and((float) $expense->amount)->toBe(200.0)
        ->and($expense->category)->toBe('Product Return Replacement Cost')
        ->and($sale->returns()->count())->toBe(1)
        ->and((float) $sale->refresh()->due_amount)->toBe(0.0)
        ->and((float) $customer->refresh()->due_balance)->toBe(0.0);
});

test('return without replacement reduces invoice and customer due', function () {
    $customer = Customer::query()->create([
        'name' => 'Due Return Customer',
        'phone' => '0772222222',
        'opening_balance' => 0,
        'due_balance' => 500,
    ]);
    $product = Product::factory()->create([
        'name' => 'Due Return Charger',
        'cost_price' => 200,
        'selling_price' => 500,
        'stock_quantity' => 3,
        'is_active' => true,
    ]);
    $sale = saleForReturn($customer, $product, [
        'paid_amount' => 0,
        'due_amount' => 500,
        'payment_status' => 'due',
    ]);

    Livewire::actingAs(returnWorkflowUser())
        ->test('pages::pos.returns')
        ->call('selectSale', $sale->id)
        ->call('updateReturnQty', $product->id, 1)
        ->set('returnType', 'adjust_due')
        ->call('submitReturn')
        ->assertHasNoErrors();

    expect((float) $sale->refresh()->due_amount)->toBe(0.0)
        ->and($sale->payment_status)->toBe('paid')
        ->and((float) $customer->refresh()->due_balance)->toBe(0.0)
        ->and($product->refresh()->stock_quantity)->toBe(4)
        ->and(Expense::query()->count())->toBe(0);
});

test('pos returns page and sidebar link are available on mobile layout', function () {
    $user = returnWorkflowUser();

    $this->actingAs($user)
        ->get(route('pos.returns'))
        ->assertOk()
        ->assertSee('POS Returns')
        ->assertSee('Search invoice, customer, or phone');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee(route('pos.returns'), false)
        ->assertSee('Returns');
});
