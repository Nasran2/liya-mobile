<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Livewire;

test('customer ledger shows bills and payment paid dates with bill numbers', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Ledger Customer',
        'phone' => '0771112222',
        'opening_balance' => 500,
        'due_balance' => 1500,
    ]);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-LEDGER-1',
        'date' => '2026-05-18',
        'subtotal_amount' => 2500,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 2500,
        'paid_amount' => 1000,
        'due_amount' => 1500,
        'payment_status' => 'partial',
        'profit' => 0,
    ]);

    $sale->payments()->create([
        'amount' => 1000,
        'payment_method' => 'cash',
        'date' => '2026-05-19',
        'reference' => 'CASH-77',
        'notes' => 'Customer paid against bill.',
    ]);

    Livewire::actingAs($user)
        ->test('pages::parties.customers')
        ->call('viewLedger', $customer->id)
        ->assertSee('Bills & Payment Timeline')
        ->assertSee('Bill No')
        ->assertSee('INV-LEDGER-1')
        ->assertSee('Bill Date')
        ->assertSee('2026-05-18')
        ->assertSee('Paid Date')
        ->assertSee('2026-05-19')
        ->assertSee('Payment received for bill')
        ->assertSee('CASH-77')
        ->assertSee('Opening Balance');
});

test('customer transactions page filters payment methods and opens invoice details', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);
    $customer = Customer::query()->create([
        'name' => 'Transactions Customer',
        'phone' => '0771113333',
        'opening_balance' => 0,
        'due_balance' => 1200,
    ]);
    $product = Product::factory()->create(['name' => 'Customer Transaction Cable']);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-TXN-100',
        'date' => '2026-05-20',
        'subtotal_amount' => 3000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 3000,
        'paid_amount' => 1800,
        'due_amount' => 1200,
        'payment_status' => 'cheque_pending',
        'profit' => 0,
    ]);

    $sale->items()->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'cost_price' => 700,
        'selling_price' => 1500,
        'subtotal' => 3000,
    ]);
    $sale->payments()->create([
        'amount' => 600,
        'payment_method' => 'cash',
        'date' => '2026-05-20',
        'reference' => 'CASH-TXN-100',
    ]);
    $sale->payments()->create([
        'amount' => 1800,
        'payment_method' => 'cheque',
        'date' => '2026-05-20',
        'reference' => 'CHQ-TXN-100',
        'cheque_no' => 'CHQ-TXN-100',
        'cheque_date' => '2026-05-25',
        'cheque_status' => 'pending',
    ]);

    Livewire::actingAs($user)
        ->test('pages::parties.customer-transactions')
        ->assertSet('selectedCustomerId', null)
        ->assertSee('Transactions Customer')
        ->assertDontSee('Total Amount Due')
        ->call('selectCustomer', $customer->id)
        ->assertSee('Pending Amount')
        ->assertSee('Total Cheques')
        ->assertSee('Pending Cheques')
        ->assertSee('INV-TXN-100')
        ->assertSee('bg-amber-100/80')
        ->assertSee('Rs 1,200.00')
        ->assertDontSee('Bill created')
        ->set('paymentMethod', 'cheque')
        ->set('chequeStatus', 'pending')
        ->assertSee('CHQ-TXN-100')
        ->call('showChequeList', 'pending')
        ->assertSet('selectedChequeStatus', 'pending')
        ->assertSee('Cheque Details')
        ->assertSee('Cheque No')
        ->assertSee('Invoice No')
        ->call('viewSaleDetail', $sale->id)
        ->assertSet('selectedSaleId', $sale->id)
        ->assertSee('Customer Transaction Cable');
});

test('supplier transactions page filters cheque status and opens purchase invoice details', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);
    $supplier = Supplier::query()->create([
        'name' => 'Transactions Supplier',
        'company_name' => 'Transaction Wholesale',
        'opening_balance' => 0,
        'due_balance' => 500,
    ]);
    $product = Product::factory()->create(['name' => 'Supplier Transaction Cable']);
    $purchase = Purchase::query()->create([
        'supplier_id' => $supplier->id,
        'invoice_no' => 'PUR-TXN-100',
        'date' => '2026-05-21',
        'total_amount' => 5000,
        'discount' => 0,
        'tax' => 0,
        'grand_total' => 5000,
        'paid_amount' => 2500,
        'due_amount' => 2500,
        'payment_status' => 'cheque_pending',
    ]);

    $purchase->items()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'cost_price' => 1000,
        'selling_price' => 1400,
        'subtotal' => 5000,
    ]);
    $purchase->payments()->create([
        'amount' => 500,
        'payment_method' => 'cash',
        'date' => '2026-05-21',
        'reference' => 'SUP-CASH-TXN',
    ]);
    $purchase->payments()->create([
        'amount' => 2500,
        'payment_method' => 'cheque',
        'date' => '2026-05-21',
        'reference' => 'SUP-CHQ-TXN',
        'cheque_no' => 'SUP-CHQ-TXN',
        'cheque_date' => '2026-05-27',
        'cheque_status' => 'returned',
        'cheque_type' => 'own',
    ]);

    Livewire::actingAs($user)
        ->test('pages::parties.supplier-transactions')
        ->assertSet('selectedSupplierId', null)
        ->assertSee('Transactions Supplier')
        ->assertDontSee('Total Amount Due')
        ->call('selectSupplier', $supplier->id)
        ->assertSee('Pending Amount')
        ->assertSee('Total Cheques')
        ->assertSee('Pending Cheques')
        ->assertSee('PUR-TXN-100')
        ->assertSee('bg-cyan-100/80')
        ->assertSee('Rs 2,500.00')
        ->assertDontSee('Restock invoice created')
        ->set('paymentMethod', 'cheque')
        ->set('chequeStatus', 'returned')
        ->assertSee('SUP-CHQ-TXN')
        ->assertSee('Returned')
        ->call('showChequeList', 'returned')
        ->assertSet('selectedChequeStatus', 'returned')
        ->assertSee('Cheque Details')
        ->assertSee('Cheque No')
        ->assertSee('Invoice No')
        ->call('viewPurchaseDetail', $purchase->id)
        ->assertSet('selectedPurchaseId', $purchase->id)
        ->assertSee('Supplier Transaction Cable');
});
