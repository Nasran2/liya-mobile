<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('business info saves second phone and br number', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-info')
        ->set('business_name', 'Sanco Enterprises')
        ->set('business_tagline', 'Mobile Accessories')
        ->set('business_email', 'sales@sanco.test')
        ->set('business_phone', '+94 759151515')
        ->set('business_phone_2', '+94 772222222')
        ->set('business_br_number', 'BR-2026-001')
        ->set('business_address', 'Warakapola')
        ->call('saveSettings')
        ->assertHasNoErrors();

    expect(Setting::get('business_phone_2'))->toBe('+94 772222222')
        ->and(Setting::get('business_br_number'))->toBe('BR-2026-001');
});

test('business info saves uploaded logo', function () {
    Storage::fake('public');

    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.business-info')
        ->set('business_name', 'Sanco Enterprises')
        ->set('business_tagline', 'Mobile Accessories')
        ->set('business_email', 'sales@sanco.test')
        ->set('business_phone', '+94 759151515')
        ->set('business_address', 'Warakapola')
        ->set('business_logo', UploadedFile::fake()->image('sanco-logo.png', 180, 120))
        ->call('saveSettings')
        ->assertHasNoErrors();

    $logoPath = Setting::get('business_logo');

    expect($logoPath)->toStartWith('logos/');
    Storage::disk('public')->assertExists($logoPath);
});

test('public bill shows second phone and br number', function () {
    Storage::fake('public');

    Setting::set('business_name', 'Sanco Enterprises', 'general');
    Setting::set('business_logo', 'logos/sanco-logo.png', 'general');
    Setting::set('business_phone', '+94 759151515', 'general');
    Setting::set('business_phone_2', '+94 772222222', 'general');
    Setting::set('business_br_number', 'BR-2026-001', 'general');
    Setting::set('business_email', 'sales@sanco.test', 'general');
    Setting::set('business_address', 'Warakapola', 'general');
    config(['app.dev_name' => 'Twinsofte.com']);
    Storage::disk('public')->put('logos/sanco-logo.png', 'fake-logo');

    $customer = Customer::query()->create([
        'name' => 'Bill Customer',
        'phone' => '0771234567',
        'opening_balance' => 0,
        'due_balance' => 0,
    ]);
    $sale = Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-BR-100',
        'date' => today(),
        'subtotal_amount' => 1000,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => 1000,
        'paid_amount' => 1000,
        'due_amount' => 0,
        'payment_status' => 'paid',
        'profit' => 0,
    ]);

    $this->get(route('public.bill', ['sale' => $sale->invoice_no]))
        ->assertSuccessful()
        ->assertSee('+94 759151515')
        ->assertSee('+94 772222222')
        ->assertSee('BR No')
        ->assertSee('BR-2026-001')
        ->assertSee('/storage/logos/sanco-logo.png')
        ->assertSee('Powered by Twinsofte.com');
});

test('sidebar shows business name and developer footer', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    Setting::set('business_name', 'Sanco Enterprises', 'general');
    config(['app.dev_name' => 'Twinsofte.com']);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSee('Sanco Enterprises')
        ->assertSee('Powered by Twinsofte.com')
        ->assertDontSee('Laravel Starter Kit');
});
