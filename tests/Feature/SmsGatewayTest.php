<?php

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\ChequePaymentService;
use App\Services\SmsNotificationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function createSmsSale(float $amount = 2500): Sale
{
    $customer = Customer::query()->create([
        'name' => 'SMS Customer',
        'phone' => '0771234567',
        'address' => 'Colombo',
        'opening_balance' => 0,
        'due_balance' => $amount,
    ]);

    return Sale::query()->create([
        'customer_id' => $customer->id,
        'invoice_no' => 'INV-SMS-'.fake()->unique()->numberBetween(1000, 9999),
        'date' => today(),
        'subtotal_amount' => $amount,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'grand_total' => $amount,
        'paid_amount' => 0,
        'due_amount' => $amount,
        'payment_status' => 'due',
        'profit' => 0,
    ]);
}

function enableSmsSettings(): void
{
    Setting::set('sms_enabled', '1', 'sms');
    Setting::set('sms_textit_id', '94758822269', 'sms');
    Setting::set('sms_textit_password', '6886', 'sms');
    Setting::set('sms_textit_base_url', 'https://textit.biz/sendmsg', 'sms');
    Setting::set('sms_notify_sale_enabled', '1', 'sms');
    Setting::set('sms_notify_payment_enabled', '1', 'sms');
    Setting::set('sms_notify_cheque_passed_enabled', '1', 'sms');
    Setting::set('sms_notify_cheque_reminder_enabled', '1', 'sms');
}

test('public bill link opens without authentication', function () {
    $sale = createSmsSale();

    $this->get(route('public.bill', ['sale' => $sale->invoice_no]))
        ->assertSuccessful()
        ->assertSee($sale->invoice_no)
        ->assertSee('SMS Customer')
        ->assertSee('Retail Bill')
        ->assertSee('Print / Save PDF')
        ->assertDontSee('Tax Invoice')
        ->assertDontSee('Sales Tax');
});

test('sale sms includes the public bill link', function () {
    enableSmsSettings();
    Setting::set('sms_template_sale', 'Bill {invoice_no}: {bill_link}', 'sms');
    Http::fake(['textit.biz/*' => Http::response('OK:SMS123')]);

    $sale = createSmsSale();

    app(SmsNotificationService::class)->notifySaleCreated($sale);

    Http::assertSent(function (Request $request) use ($sale): bool {
        return str_contains($request->url(), 'textit.biz/sendmsg')
            && $request->data()['to'] === '94771234567'
            && str_contains($request->data()['text'], route('public.bill', ['sale' => $sale->invoice_no]));
    });

    expect(SmsLog::query()->where('ref_no', 'SALE-'.$sale->id)->where('status', 'success')->exists())->toBeTrue();
});

test('sale sms includes cash cheque hold due and public bill details', function () {
    enableSmsSettings();
    Setting::set('sms_template_sale', 'Bill {invoice_no} Cash Rs {cash_payment} Cheques {cheque_payments} Hold Rs {hold_amount} Due Rs {due} Link {bill_link}', 'sms');
    Http::fake(['textit.biz/*' => Http::response('OK:SMS123')]);

    $sale = createSmsSale(100000);
    $sale->update([
        'paid_amount' => 30000,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
    ]);
    $sale->payments()->create([
        'amount' => 30000,
        'payment_method' => 'cash',
        'date' => today(),
        'reference' => 'CASH-COUNTER',
    ]);
    $sale->payments()->create([
        'amount' => 70000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'CHQ-4321',
        'cheque_bank' => 'BOC',
        'cheque_no' => 'CHQ-4321',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    app(SmsNotificationService::class)->notifySaleCreated($sale);

    Http::assertSent(function (Request $request) use ($sale): bool {
        $text = $request->data()['text'];

        return str_contains($text, 'Cash Rs 30,000.00')
            && str_contains($text, 'CHQ-4321 '.today()->addDays(2)->toDateString().' Rs 70,000.00 (Pending)')
            && str_contains($text, 'Hold Rs 70,000.00')
            && str_contains($text, 'Due Rs 0.00')
            && str_contains($text, route('public.bill', ['sale' => $sale->invoice_no]));
    });
});

test('legacy sale sms template is enriched with cheque hold and bill link', function () {
    enableSmsSettings();
    Setting::set('sms_template_sale', 'Hi {customer_name}, thank you for your purchase of Rs {total} at {business_name}. Inv: {invoice_no}. Paid: Rs {paid}, Due: Rs {due}.', 'sms');
    Http::fake(['textit.biz/*' => Http::response('OK:SMS123')]);

    $sale = createSmsSale(2450);
    $sale->update([
        'paid_amount' => 250,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
    ]);
    $sale->payments()->create([
        'amount' => 250,
        'payment_method' => 'cash',
        'date' => today(),
    ]);
    $sale->payments()->create([
        'amount' => 1200,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => '32132',
        'cheque_no' => '32132',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);
    $sale->payments()->create([
        'amount' => 1000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => '876',
        'cheque_no' => '876',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    app(SmsNotificationService::class)->notifySaleCreated($sale);

    Http::assertSent(function (Request $request) use ($sale): bool {
        $text = $request->data()['text'];

        return str_contains($text, 'Cash Rs 250.00')
            && str_contains($text, '32132 '.today()->addDays(2)->toDateString().' Rs 1,200.00 (Pending)')
            && str_contains($text, '876 '.today()->addDays(2)->toDateString().' Rs 1,000.00 (Pending)')
            && str_contains($text, 'Hold Rs 2,200.00')
            && str_contains($text, route('public.bill', ['sale' => $sale->invoice_no]));
    });
});

test('cheque passed sms includes cheque details and public bill link', function () {
    enableSmsSettings();
    Setting::set('sms_template_cheque_passed', 'Passed {cheque_no} {cheque_date} Rs {payment_amount} Hold Rs {hold_amount} Due Rs {due} View {bill_link}', 'sms');
    Http::fake(['textit.biz/*' => Http::response('OK:SMS123')]);

    $sale = createSmsSale(5000);
    $sale->update([
        'paid_amount' => 0,
        'due_amount' => 0,
        'payment_status' => 'cheque_pending',
    ]);
    $payment = $sale->payments()->create([
        'amount' => 5000,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'CHQ-900',
        'cheque_bank' => 'BOC',
        'cheque_no' => 'CHQ-900',
        'cheque_date' => today()->addDay(),
        'cheque_status' => 'pending',
    ]);

    app(ChequePaymentService::class)->pass($payment);

    Http::assertSent(function (Request $request) use ($sale): bool {
        $text = $request->data()['text'];

        return str_contains($text, 'Passed CHQ-900 '.today()->addDay()->toDateString().' Rs 5,000.00')
            && str_contains($text, 'Hold Rs 0.00')
            && str_contains($text, 'Due Rs 0.00')
            && str_contains($text, route('public.bill', ['sale' => $sale->invoice_no]));
    });

    expect(Payment::query()->where('cheque_no', 'CHQ-900')->value('cheque_status'))->toBe('passed');
});

test('sms settings page saves gateway toggles and editable templates', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.sms')
        ->set('sms_enabled', true)
        ->set('sms_textit_id', '94758822269')
        ->set('sms_textit_password', '6886')
        ->set('sms_textit_base_url', 'https://textit.biz/sendmsg')
        ->set('sms_notify_payment_enabled', false)
        ->set('sms_template_sale', 'Sale {invoice_no} {bill_link}')
        ->set('sms_template_payment', 'Paid {payment_amount}')
        ->set('sms_template_cheque_passed', 'Passed {cheque_no}')
        ->set('sms_template_cheque_reminder', 'Reminder {cheque_date}')
        ->call('saveSettings')
        ->assertHasNoErrors();

    expect(Setting::get('sms_enabled'))->toBe('1')
        ->and(Setting::get('sms_notify_payment_enabled'))->toBe('0')
        ->and(Setting::get('sms_template_sale'))->toBe('Sale {invoice_no} {bill_link}');
});

test('sms settings page shows recent sms logs', function () {
    $user = User::factory()->create([
        'role' => 'super_admin',
        'is_active' => true,
    ]);

    SmsLog::query()->create([
        'phone' => '94771234567',
        'message' => 'Bill INV-SMS-1001 sent to customer',
        'status' => 'success',
        'response' => 'OK:SMS123',
        'ref_no' => 'SALE-1001',
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.sms')
        ->assertSee('SMS Logs')
        ->assertSee('94771234567')
        ->assertSee('Bill INV-SMS-1001 sent to customer')
        ->assertSee('OK:SMS123')
        ->assertSee('SALE-1001');
});

test('cheque reminder command sends once for cheques due in two days', function () {
    enableSmsSettings();
    Setting::set('sms_template_cheque_reminder', 'Cheque {cheque_no} due {cheque_date} for {invoice_no}', 'sms');
    Http::fake(['textit.biz/*' => Http::response('OK:SMS123')]);

    $sale = createSmsSale();
    $payment = $sale->payments()->create([
        'amount' => 2500,
        'payment_method' => 'cheque',
        'date' => today(),
        'reference' => 'CHQ-900',
        'cheque_bank' => 'BOC',
        'cheque_no' => 'CHQ-900',
        'cheque_date' => today()->addDays(2),
        'cheque_status' => 'pending',
    ]);

    $this->artisan('sms:send-cheque-reminders')->assertSuccessful();
    $this->artisan('sms:send-cheque-reminders')->assertSuccessful();

    expect(SmsLog::query()->where('ref_no', 'CHQ-REM-'.$payment->id.'-'.$payment->cheque_date->format('Ymd'))->count())->toBe(1);
});
