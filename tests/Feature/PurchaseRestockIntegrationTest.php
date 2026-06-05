<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

test('dashboard displays restock buttons linking to purchases create page', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $this->actingAs($user);

    $product = Product::factory()->create([
        'name' => 'Low Stock Cable',
        'stock_quantity' => 1,
        'minimum_stock' => 5,
    ]);

    $response = $this->get(route('dashboard'));
    $response->assertOk();

    // It should contain a link to purchases.create with the product_id parameter
    $expectedUrl = route('purchases.create', ['product_id' => $product->id]);
    $response->assertSee(e($expectedUrl), false);
});

test('purchases create page auto-populates cart with product_id from query parameters', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $product = Product::factory()->create([
        'name' => 'Restock Product',
        'cost_price' => 12.50,
        'selling_price' => 25.00,
    ]);

    Livewire::actingAs($user)
        ->withQueryParams(['product_id' => $product->id])
        ->test('pages::purchases.create')
        ->assertSet('cart.0.product_id', $product->id)
        ->assertSet('cart.0.name', 'Restock Product')
        ->assertSet('cart.0.cost_price', 12.50)
        ->assertSet('cart.0.selling_price', 25.00);
});

test('purchases create can add a new product with minimum stock alert', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

    $component = Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('newProductName', 'Quick Stock Cable')
        ->set('newProductCostPrice', 90)
        ->set('newProductSellingPrice', 150)
        ->set('newProductWholesalePrice', 150)
        ->set('newProductMinimumStock', 5)
        ->call('saveNewProduct')
        ->assertHasNoErrors();

    $product = Product::query()->where('name', 'Quick Stock Cable')->firstOrFail();

    $component->assertSet('cart.0.product_id', $product->id);

    expect((int) $product->stock_quantity)->toBe(0)
        ->and((int) $product->minimum_stock)->toBe(5);
});

test('suppliers list page auto-opens ledger drawer when supplier_id is passed', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Bulk Supplier',
        'opening_balance' => 0,
        'due_balance' => 100,
    ]);

    Livewire::actingAs($user)
        ->withQueryParams(['supplier_id' => $supplier->id])
        ->test('pages::parties.suppliers')
        ->assertSet('selectedSupplierId', $supplier->id);
});

test('purchases index links to supplier list page and products details', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

    $supplier = Supplier::query()->create([
        'name' => 'Premium Supplier',
        'opening_balance' => 0,
        'due_balance' => 100,
    ]);

    $product = Product::factory()->create([
        'name' => 'Restocked Earphones',
    ]);

    $purchase = Purchase::query()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'PUR-TEST-123',
        'date' => '2026-05-21',
        'total_amount' => 1500,
        'discount' => 0,
        'tax' => 0,
        'grand_total' => 1500,
        'paid_amount' => 1500,
        'due_amount' => 0,
        'payment_status' => 'paid',
    ]);

    $purchase->items()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'cost_price' => 150,
        'selling_price' => 200,
        'subtotal' => 1500,
    ]);

    // Check purchase listing page has supplier links
    $response = $this->actingAs($user)->get(route('purchases.index'));
    $response->assertOk();
    $supplierLink = route('parties.suppliers', ['supplier_id' => $supplier->id]);
    $response->assertSee(e($supplierLink), false);

    // Check Livewire component includes supplier and product links when drawer is open
    Livewire::actingAs($user)
        ->test('pages::purchases.index')
        ->call('viewInvoice', $purchase->id)
        ->assertSee(route('parties.suppliers', ['supplier_id' => $supplier->id]))
        ->assertSee(route('products.show', $product->id));
});

test('purchase can be edited from the purchase form and recalculates stock payments and supplier due', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Editable Supplier',
        'opening_balance' => 0,
        'due_balance' => 100,
    ]);
    $product = Product::factory()->create([
        'name' => 'Editable Restock Item',
        'cost_price' => 100,
        'selling_price' => 150,
        'stock_quantity' => 10,
    ]);

    $purchase = Purchase::query()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'PUR-EDIT-100',
        'date' => '2026-05-21',
        'total_amount' => 200,
        'discount' => 0,
        'tax' => 0,
        'grand_total' => 200,
        'paid_amount' => 100,
        'due_amount' => 100,
        'payment_status' => 'partial',
    ]);

    $purchase->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'cost_price' => 100,
        'selling_price' => 150,
        'subtotal' => 200,
    ]);

    $purchase->payments()->create([
        'amount' => 100,
        'payment_method' => 'cash',
        'date' => '2026-05-21',
        'reference' => 'OLD-CASH',
        'notes' => 'Original purchase payment',
    ]);

    $this->actingAs($user)
        ->get(route('purchases.index'))
        ->assertOk()
        ->assertSee(route('purchases.edit', $purchase), false);

    Livewire::actingAs($user)
        ->test('pages::purchases.create', ['purchase' => $purchase])
        ->assertSet('editingPurchaseId', $purchase->id)
        ->assertSet('invoice_no', 'PUR-EDIT-100')
        ->assertSet('cart.0.product_id', $product->id)
        ->call('updateCartRow', 0, 'quantity', 3)
        ->set('paymentRows.0.amount', 250)
        ->set('paymentRows.0.reference', 'UPDATED-CASH')
        ->call('savePurchase')
        ->assertHasNoErrors()
        ->assertRedirect(route('purchases.index', absolute: false));

    $purchase->refresh()->load('items', 'payments');

    expect((float) $purchase->grand_total)->toBe(300.0)
        ->and((float) $purchase->paid_amount)->toBe(250.0)
        ->and((float) $purchase->due_amount)->toBe(50.0)
        ->and($purchase->payment_status)->toBe('partial')
        ->and($purchase->items)->toHaveCount(1)
        ->and((int) $purchase->items->first()->quantity)->toBe(3)
        ->and($purchase->payments)->toHaveCount(1)
        ->and((float) $purchase->payments->first()->amount)->toBe(250.0)
        ->and($purchase->payments->first()->reference)->toBe('UPDATED-CASH')
        ->and((int) $product->refresh()->stock_quantity)->toBe(11)
        ->and((float) $supplier->refresh()->due_balance)->toBe(50.0);
});

test('purchase cheque number must be unique against existing customer and supplier cheques', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $purchaseSupplier = Supplier::query()->create([
        'name' => 'Purchase Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $duplicateCustomer = Customer::query()->create([
        'name' => 'Duplicate Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $duplicateSupplier = Supplier::query()->create([
        'name' => 'Duplicate Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Duplicate Cheque Stock',
        'cost_price' => 100,
        'selling_price' => 150,
    ]);

    $duplicateCustomer->payments()->create([
        'amount' => 1000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'DUP-CUSTOMER-100',
        'cheque_no' => 'DUP-CUSTOMER-100',
        'cheque_date' => today()->addDay(),
        'cheque_status' => 'pending',
    ]);
    $duplicateSupplier->payments()->create([
        'amount' => 1000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'DUP-SUPPLIER-100',
        'cheque_no' => 'DUP-SUPPLIER-100',
        'cheque_date' => today()->addDay(),
        'cheque_status' => 'pending',
    ]);

    $customerDuplicate = Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('invoice_no', 'PUR-DUP-CUSTOMER-100')
        ->set('supplier_id', $purchaseSupplier->id)
        ->set('cart', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'cost_price' => 100,
            'selling_price' => 150,
            'subtotal' => 100,
        ]])
        ->set('paymentRows.0.amount', 100)
        ->set('paymentRows.0.method', 'cheque')
        ->set('paymentRows.0.cheque_type', 'own')
        ->set('paymentRows.0.cheque_bank', 'BOC')
        ->set('paymentRows.0.cheque_date', today()->addDay()->toDateString())
        ->set('paymentRows.0.cheque_no', 'DUP-CUSTOMER-100')
        ->call('savePurchase')
        ->assertHasErrors(['paymentRows.0.cheque_no']);

    expect($customerDuplicate->errors()->first('paymentRows.0.cheque_no'))->toContain('customer Duplicate Customer');

    $supplierDuplicate = Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('invoice_no', 'PUR-DUP-SUPPLIER-100')
        ->set('supplier_id', $purchaseSupplier->id)
        ->set('cart', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'cost_price' => 100,
            'selling_price' => 150,
            'subtotal' => 100,
        ]])
        ->set('paymentRows.0.amount', 100)
        ->set('paymentRows.0.method', 'cheque')
        ->set('paymentRows.0.cheque_type', 'own')
        ->set('paymentRows.0.cheque_bank', 'BOC')
        ->set('paymentRows.0.cheque_date', today()->addDay()->toDateString())
        ->set('paymentRows.0.cheque_no', 'DUP-SUPPLIER-100')
        ->call('savePurchase')
        ->assertHasErrors(['paymentRows.0.cheque_no']);

    expect($supplierDuplicate->errors()->first('paymentRows.0.cheque_no'))->toContain('supplier Duplicate Supplier');
});

test('purchase cannot use the same manual cheque number twice in one invoice', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Same Invoice Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Same Invoice Cheque Stock',
        'cost_price' => 100,
        'selling_price' => 150,
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('invoice_no', 'PUR-DUP-ROWS-100')
        ->set('supplier_id', $supplier->id)
        ->set('cart', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 2,
            'cost_price' => 100,
            'selling_price' => 150,
            'subtotal' => 200,
        ]])
        ->set('paymentRows.0.amount', 100)
        ->set('paymentRows.0.method', 'cheque')
        ->set('paymentRows.0.cheque_type', 'own')
        ->set('paymentRows.0.cheque_no', 'SAME-ROW-100')
        ->set('paymentRows.0.cheque_bank', 'BOC')
        ->set('paymentRows.0.cheque_date', today()->addDay()->toDateString())
        ->call('addPaymentRow', 'cheque')
        ->set('paymentRows.1.amount', 100)
        ->set('paymentRows.1.method', 'cheque')
        ->set('paymentRows.1.cheque_type', 'own')
        ->set('paymentRows.1.cheque_no', 'same-row-100')
        ->set('paymentRows.1.cheque_bank', 'BOC')
        ->set('paymentRows.1.cheque_date', today()->addDay()->toDateString())
        ->call('savePurchase')
        ->assertHasErrors(['paymentRows.1.cheque_no']);

    expect($component->errors()->first('paymentRows.1.cheque_no'))->toContain('payment #1');
});

test('purchase cheque number shows duplicate supplier error while typing', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $existingSupplier = Supplier::query()->create([
        'name' => 'Existing Cheque Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $purchaseSupplier = Supplier::query()->create([
        'name' => 'New Purchase Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Live Duplicate Cheque Stock',
        'cost_price' => 740,
        'selling_price' => 900,
    ]);
    $existingPurchase = Purchase::query()->create([
        'supplier_id' => $existingSupplier->id,
        'invoice_no' => 'PUR-LIVE-DUP-OLD',
        'date' => today(),
        'total_amount' => 30000,
        'discount' => 0,
        'tax' => 0,
        'grand_total' => 30000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
    ]);
    $existingPurchase->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => '765499',
        'cheque_no' => '765499',
        'cheque_bank' => 'com',
        'cheque_date' => today()->addDay(),
        'cheque_status' => 'pending',
        'cheque_type' => 'own',
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('supplier_id', $purchaseSupplier->id)
        ->set('cart', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 5,
            'cost_price' => 740,
            'selling_price' => 900,
            'subtotal' => 3700,
        ]])
        ->set('paymentRows.0.amount', 3700)
        ->set('paymentRows.0.method', 'cheque')
        ->set('paymentRows.0.cheque_type', 'own')
        ->set('paymentRows.0.cheque_bank', 'com')
        ->set('paymentRows.0.cheque_date', today()->toDateString())
        ->set('paymentRows.0.cheque_no', '765499')
        ->assertHasErrors(['paymentRows.0.cheque_no']);

    expect($component->errors()->first('paymentRows.0.cheque_no'))->toContain('supplier Existing Cheque Supplier');
});

test('purchase can be recorded with a selected party cheque hold', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Party Cheque Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Party Cheque Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Party Cheque Stock',
        'cost_price' => 100,
        'selling_price' => 150,
    ]);

    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-PC-100',
        'date' => today(),
        'subtotal_amount' => 1000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 1000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);

    $customerCheque = $sale->payments()->create([
        'amount' => 1000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'PC-100',
        'cheque_bank' => 'NDB',
        'cheque_no' => 'PC-100',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('invoice_no', 'PUR-PC-100')
        ->set('supplier_id', $supplier->id)
        ->set('cart', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 10,
            'cost_price' => 100,
            'selling_price' => 150,
            'subtotal' => 1000,
        ]])
        ->set('paid_amount', 1000)
        ->set('payment_method', 'cheque')
        ->set('cheque_type', 'party')
        ->call('selectPartyCheque', $customerCheque->id)
        ->call('savePurchase')
        ->assertHasNoErrors();

    $purchase = Purchase::query()->where('invoice_no', 'PUR-PC-100')->firstOrFail();
    $supplierPayment = $purchase->payments()->firstOrFail();

    expect($purchase->payment_status)->toBe('cheque_pending')
        ->and((float) $purchase->due_amount)->toBe(0.0)
        ->and((float) $supplier->refresh()->due_balance)->toBe(0.0)
        ->and($supplierPayment->cheque_type)->toBe('party')
        ->and($supplierPayment->source_payment_id)->toBe($customerCheque->id)
        ->and($supplierPayment->party_customer_id)->toBe($customer->id);
});

test('supplier payoff can be recorded with a cheque hold', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Payoff Cheque Supplier',
        'opening_balance' => 0,
        'due_balance' => 500,
    ]);

    Livewire::actingAs($user)
        ->test('pages::parties.suppliers')
        ->call('initiatePayment', $supplier->id)
        ->set('payMethod', 'own_cheque')
        ->set('payAmount', 500)
        ->set('payChequeNo', 'SUP-CHQ-100')
        ->set('payChequeBank', 'BOC')
        ->set('payChequeDate', today()->addDays(3)->toDateString())
        ->call('savePayment')
        ->assertHasNoErrors();

    $payment = Payment::query()->where('paymentable_type', Supplier::class)->firstOrFail();

    expect($payment->payment_method)->toBe('cheque')
        ->and($payment->cheque_status)->toBe('pending')
        ->and($payment->cheque_type)->toBe('own')
        ->and($payment->cheque_no)->toBe('SUP-CHQ-100')
        ->and((float) $supplier->refresh()->due_balance)->toBe(0.0);
});

test('supplier payoff can be recorded with a party cheque hold', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Party Payoff Supplier',
        'opening_balance' => 0,
        'due_balance' => 1000,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Party Payoff Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-PAYOFF-100',
        'date' => today(),
        'subtotal_amount' => 600,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 600,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);
    $customerCheque = $sale->payments()->create([
        'amount' => 600,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'PAYOFF-CHQ-100',
        'cheque_bank' => 'NDB',
        'cheque_no' => 'PAYOFF-CHQ-100',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test('pages::parties.suppliers')
        ->call('initiatePayment', $supplier->id)
        ->set('payMethod', 'party_cheque')
        ->call('selectPayPartyCheque', $customerCheque->id)
        ->call('savePayment')
        ->assertHasNoErrors();

    $payment = Payment::query()->where('paymentable_type', Supplier::class)->firstOrFail();

    expect($payment->cheque_type)->toBe('party')
        ->and($payment->source_payment_id)->toBe($customerCheque->id)
        ->and($payment->party_customer_id)->toBe($customer->id)
        ->and($payment->cheque_no)->toBe('PAYOFF-CHQ-100')
        ->and((float) $supplier->refresh()->due_balance)->toBe(400.0);
});

test('supplier ledger shows cheque status badge for cheque payoffs', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Ledger Cheque Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);

    $supplier->payments()->create([
        'amount' => 120,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'LEDGER-CHQ-100',
        'cheque_bank' => 'BOC',
        'cheque_no' => 'LEDGER-CHQ-100',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
        'cheque_type' => 'own',
    ]);

    Livewire::actingAs($user)
        ->test('pages::parties.suppliers')
        ->call('viewLedger', $supplier->id)
        ->assertSee('Pending');
});

test('purchase create defaults to party cheque type', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->assertSet('cheque_type', 'party');
});

test('purchase create auto-fills paid amount for cash and bank transfer', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $product = Product::factory()->create([
        'name' => 'Auto Fill Stock',
        'cost_price' => 100,
        'selling_price' => 150,
    ]);

    Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->call('selectProduct', $product->id)
        ->assertSet('paid_amount', 100.0)
        ->set('discount', 10)
        ->assertSet('paid_amount', 90.0)
        ->set('payment_method', 'bank_transfer')
        ->call('updateCartRow', 0, 'quantity', 2)
        ->assertSet('paid_amount', 190.0);
});

test('party cheque lower than total leaves supplier due with partial status', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Partial Cheque Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Partial Cheque Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Partial Cheque Stock',
        'cost_price' => 100,
        'selling_price' => 150,
    ]);

    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-PC-200',
        'date' => today(),
        'subtotal_amount' => 600,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 600,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);

    $customerCheque = $sale->payments()->create([
        'amount' => 600,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'PC-200',
        'cheque_bank' => 'BOC',
        'cheque_no' => 'PC-200',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('invoice_no', 'PUR-PC-200')
        ->set('supplier_id', $supplier->id)
        ->set('cart', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 10,
            'cost_price' => 100,
            'selling_price' => 150,
            'subtotal' => 1000,
        ]])
        ->set('payment_method', 'cheque')
        ->set('cheque_type', 'party')
        ->call('selectPartyCheque', $customerCheque->id)
        ->call('savePurchase')
        ->assertHasNoErrors();

    $purchase = Purchase::query()->where('invoice_no', 'PUR-PC-200')->firstOrFail();

    expect((float) $purchase->due_amount)->toBe(400.0)
        ->and($purchase->payment_status)->toBe('partial')
        ->and((float) $supplier->refresh()->due_balance)->toBe(400.0);
});

test('purchase can be paid with cash saved party cheque manual party cheque and own cheques', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Mixed Payment Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Saved Cheque Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $product = Product::factory()->create([
        'name' => 'Bulk Mixed Purchase Stock',
        'cost_price' => 100000,
        'selling_price' => 125000,
        'stock_quantity' => 0,
    ]);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-MIXED-100',
        'date' => today(),
        'subtotal_amount' => 30000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 30000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);
    $savedPartyCheque = $sale->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => '76820',
        'cheque_bank' => 'NDB',
        'cheque_no' => '76820',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('invoice_no', 'PUR-MIXED-100')
        ->set('supplier_id', $supplier->id)
        ->set('cart', [[
            'product_id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'quantity' => 1,
            'cost_price' => 100000,
            'selling_price' => 125000,
            'subtotal' => 100000,
        ]])
        ->set('paymentRows.0.amount', 20000)
        ->set('paymentRows.0.method', 'cash')
        ->set('paymentRows.0.reference', 'Cash 20k')
        ->call('addPaymentRow', 'cheque')
        ->call('selectPaymentRowPartyCheque', 1, $savedPartyCheque->id)
        ->call('addPaymentRow', 'cheque')
        ->set('paymentRows.2.amount', 10000)
        ->set('paymentRows.2.method', 'cheque')
        ->set('paymentRows.2.cheque_type', 'own')
        ->set('paymentRows.2.cheque_no', '765413')
        ->set('paymentRows.2.cheque_bank', 'BOC')
        ->set('paymentRows.2.cheque_date', today()->addDays(4)->toDateString())
        ->call('addPaymentRow', 'cheque')
        ->set('paymentRows.3.amount', 30000)
        ->set('paymentRows.3.method', 'cheque')
        ->set('paymentRows.3.cheque_type', 'party')
        ->set('paymentRows.3.cheque_no', '786546')
        ->set('paymentRows.3.cheque_bank', 'Commercial Bank')
        ->set('paymentRows.3.cheque_date', today()->addDays(3)->toDateString())
        ->set('paymentRows.3.party_cheque_search', '786546')
        ->call('addPaymentRow', 'cheque')
        ->set('paymentRows.4.amount', 10000)
        ->set('paymentRows.4.method', 'cheque')
        ->set('paymentRows.4.cheque_type', 'own')
        ->set('paymentRows.4.cheque_no', 'OWN-10000')
        ->set('paymentRows.4.cheque_bank', 'Peoples Bank')
        ->set('paymentRows.4.cheque_date', today()->addDays(5)->toDateString())
        ->call('savePurchase')
        ->assertHasNoErrors();

    $purchase = Purchase::query()->where('invoice_no', 'PUR-MIXED-100')->firstOrFail();
    $payments = $purchase->payments()->orderBy('id')->get();

    expect($purchase->payment_status)->toBe('cheque_pending')
        ->and((float) $purchase->paid_amount)->toBe(20000.0)
        ->and((float) $purchase->due_amount)->toBe(0.0)
        ->and((float) $supplier->refresh()->due_balance)->toBe(0.0)
        ->and($payments)->toHaveCount(5)
        ->and($payments[0]->payment_method)->toBe('cash')
        ->and((float) $payments[0]->amount)->toBe(20000.0)
        ->and($payments[1]->cheque_type)->toBe('party')
        ->and($payments[1]->source_payment_id)->toBe($savedPartyCheque->id)
        ->and($payments[1]->party_customer_id)->toBe($customer->id)
        ->and($payments[2]->cheque_type)->toBe('own')
        ->and($payments[2]->cheque_no)->toBe('765413')
        ->and($payments[3]->cheque_type)->toBe('party')
        ->and($payments[3]->source_payment_id)->toBeNull()
        ->and($payments[3]->cheque_no)->toBe('786546')
        ->and($payments[4]->cheque_type)->toBe('own')
        ->and($payments[4]->cheque_no)->toBe('OWN-10000');
});

test('saved customer cheque selection hides manual party cheque fields', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $customer = Customer::query()->create([
        'name' => 'Hide Manual Cheque Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-HIDE-CHEQUE-100',
        'date' => today(),
        'subtotal_amount' => 30000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 30000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);
    $savedPartyCheque = $sale->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'SAVED-76820',
        'cheque_bank' => 'NDB',
        'cheque_no' => 'SAVED-76820',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test('pages::purchases.create')
        ->set('paymentRows.0.amount', 30000)
        ->set('paymentRows.0.method', 'cheque')
        ->set('paymentRows.0.cheque_type', 'party')
        ->assertSee('Party Cheque No')
        ->assertSee('Manual party cheque')
        ->call('selectPaymentRowPartyCheque', 0, $savedPartyCheque->id)
        ->assertSee('Saved customer cheque selected')
        ->assertDontSee('Party Cheque No')
        ->assertDontSee('Manual party cheque')
        ->call('clearPaymentRowPartyCheque', 0)
        ->assertSee('Party Cheque No')
        ->assertSee('Manual party cheque');
});

test('purchase bill view shows saved and manual cheque details', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Bill Cheque Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Bill Cheque Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-BILL-CHQ-100',
        'date' => today(),
        'subtotal_amount' => 30000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 30000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);
    $customerCheque = $sale->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'BILL-SAVED-100',
        'cheque_bank' => 'NDB',
        'cheque_no' => 'BILL-SAVED-100',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);
    $purchase = Purchase::query()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'PUR-BILL-CHQ-100',
        'date' => today(),
        'total_amount' => 60000,
        'discount' => 0,
        'tax' => 0,
        'grand_total' => 60000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
    ]);
    $purchase->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'BILL-SAVED-100',
        'cheque_bank' => 'NDB',
        'cheque_no' => 'BILL-SAVED-100',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
        'cheque_type' => 'party',
        'source_payment_id' => $customerCheque->id,
        'party_customer_id' => $customer->id,
    ]);
    $purchase->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'MANUAL-BILL-100',
        'cheque_bank' => 'Commercial Bank',
        'cheque_no' => 'MANUAL-BILL-100',
        'cheque_date' => today()->addDays(3),
        'cheque_status' => 'pending',
        'cheque_type' => 'party',
    ]);

    Livewire::actingAs($user)
        ->test('pages::purchases.index')
        ->call('viewInvoice', $purchase->id)
        ->assertSee('Cheque No')
        ->assertSee('BILL-SAVED-100')
        ->assertSee('Bill Cheque Customer')
        ->assertSee('INV-BILL-CHQ-100')
        ->assertSee('MANUAL-BILL-100')
        ->assertSee('Commercial Bank')
        ->assertSee('Manual / Not saved');
});

test('customer ledger shows saved party cheque used for supplier purchase', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Ledger Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Ledger Party Customer',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-LEDGER-CHQ-100',
        'date' => today(),
        'subtotal_amount' => 30000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 30000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);
    $customerCheque = $sale->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'LEDGER-CHQ-100',
        'cheque_bank' => 'NDB',
        'cheque_no' => 'LEDGER-CHQ-100',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);
    $purchase = Purchase::query()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'PUR-LEDGER-CHQ-100',
        'date' => today(),
        'total_amount' => 30000,
        'discount' => 0,
        'tax' => 0,
        'grand_total' => 30000,
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
    ]);
    $purchase->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'LEDGER-CHQ-100',
        'cheque_bank' => 'NDB',
        'cheque_no' => 'LEDGER-CHQ-100',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
        'cheque_type' => 'party',
        'source_payment_id' => $customerCheque->id,
        'party_customer_id' => $customer->id,
    ]);

    Livewire::actingAs($user)
        ->test('pages::parties.customers')
        ->call('viewLedger', $customer->id)
        ->assertSee('Customer cheque used for supplier purchase')
        ->assertSee('LEDGER-CHQ-100')
        ->assertSee('PUR-LEDGER-CHQ-100')
        ->assertSee('Ledger Supplier');
});

test('purchase index shows pending cheque hold amounts', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    $supplier = Supplier::query()->create([
        'name' => 'Hold Amount Supplier',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $purchase = Purchase::query()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'PUR-HOLD-AMOUNT-100',
        'date' => today(),
        'total_amount' => 133000,
        'discount' => 0,
        'tax' => 0,
        'grand_total' => 133000,
        'paid_amount' => 15000,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
    ]);
    $purchase->payments()->create([
        'amount' => 108000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'HOLD-108000',
        'cheque_no' => 'HOLD-108000',
        'cheque_date' => today()->addDays(3),
        'cheque_status' => 'pending',
        'cheque_type' => 'party',
    ]);
    $purchase->payments()->create([
        'amount' => 10000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'PASSED-10000',
        'cheque_no' => 'PASSED-10000',
        'cheque_date' => today()->subDay(),
        'cheque_status' => 'passed',
        'cheque_type' => 'own',
    ]);

    Livewire::actingAs($user)
        ->test('pages::purchases.index')
        ->assertSee('Cheque Hold Amount')
        ->assertSee('Rs 108,000.00')
        ->assertSee('Hold Amount');
});
