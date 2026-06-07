<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;

test('a4 invoice print layout can continue onto another page', function () {
    $customer = Customer::query()->create([
        'name' => 'PDF Customer',
        'phone' => '0773333333',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);

    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-PDF-1001',
        'date' => today()->toDateString(),
        'subtotal_amount' => 20000,
        'discount_amount' => 1000,
        'tax_amount' => 0,
        'grand_total' => 19000,
        'paid_amount' => 9000,
        'due_amount' => 10000,
        'payment_status' => 'partial',
        'profit' => 5000,
    ]);

    foreach (range(1, 18) as $index) {
        $product = Product::factory()->create([
            'name' => "PDF Cable {$index}",
            'selling_price' => 1000,
            'is_active' => true,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'cost_price' => 500,
            'selling_price' => 1000,
            'subtotal' => 1000,
        ]);
    }

    $sale->payments()->create([
        'amount' => 9000,
        'payment_method' => 'cash',
        'date' => today()->toDateString(),
        'notes' => 'Receipt payment',
    ]);

    $html = view('partials.a4-invoice', [
        'sale' => $sale->load(['customer', 'items.product', 'payments']),
        'devName' => 'TwinsOfte',
    ])->render();

    expect($html)
        ->toContain('min-h-[281mm]')
        ->toContain('overflow-visible')
        ->toContain('Total Paid')
        ->toContain('Due Balance')
        ->not->toContain('overflow: hidden !important')
        ->not->toContain('flex h-[281mm]');
});
