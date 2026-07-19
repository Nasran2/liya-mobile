<?php

use App\Models\Investor;
use App\Models\InvestorLedgerEntry;
use App\Models\InvestorProfitTransaction;
use App\Models\InvestorPurchaseFunding;
use App\Models\InvestorPayment;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Investor Profile')] class extends Component
{
    use WithPagination;

    public Investor $investor;
    public string $activeTab = 'ledger';

    public function mount(Investor $investor)
    {
        abort_unless(auth()->user()->hasPermission('manage_investors'), 403);
        $this->investor = $investor;
    }

    #[Computed]
    public function ledgerEntries()
    {
        return InvestorLedgerEntry::where('investor_id', $this->investor->id)
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'ledgerPage');
    }

    #[Computed]
    public function profitTransactions()
    {
        return InvestorProfitTransaction::with('sale')
            ->where('investor_id', $this->investor->id)
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'profitPage');
    }

    #[Computed]
    public function purchaseFundings()
    {
        return InvestorPurchaseFunding::with('purchase')
            ->where('investor_id', $this->investor->id)
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'fundingPage');
    }

    #[Computed]
    public function payments()
    {
        return InvestorPayment::where('investor_id', $this->investor->id)
            ->orderByDesc('id')
            ->paginate(15, ['*'], 'paymentPage');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }
}; ?>

<div class="flex flex-col gap-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <flux:button href="{{ route('investors.index') }}" variant="subtle" size="sm" class="mb-1" wire:navigate>
                    <flux:icon.arrow-left class="size-4" />
                </flux:button>
                <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">
                    {{ $investor->name }}
                </h1>
                @if($investor->is_active)
                    <flux:badge color="emerald" size="sm">{{ __('Active') }}</flux:badge>
                @else
                    <flux:badge color="rose" size="sm">{{ __('Inactive') }}</flux:badge>
                @endif
            </div>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                {{ __('Code:') }} {{ $investor->code }} &bull; {{ __('Joined:') }} {{ $investor->created_at->format('M d, Y') }} &bull; {{ __('Default Profit:') }} {{ number_format($investor->default_profit_percentage, 2) }}%
            </p>
        </div>
        <flux:button href="{{ route('investors.payments') }}" wire:navigate variant="primary" class="w-full sm:w-auto">
            <flux:icon.banknotes class="size-4 mr-1" />
            {{ __('Process Payment') }}
        </flux:button>
    </div>

    <!-- Stats -->
    <section class="grid gap-4 sm:grid-cols-3">
        <!-- Profit Balance -->
        <div class="app-card p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <flux:icon.arrow-trending-up class="size-5" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Unpaid Profit') }}</p>
                    <p class="font-display text-xl font-bold text-zinc-950 dark:text-zinc-50">Rs {{ number_format($investor->profit_balance, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Purchase Balance -->
        <div class="app-card p-5">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400">
                    <flux:icon.banknotes class="size-5" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Unpaid Funding') }}</p>
                    <p class="font-display text-xl font-bold text-zinc-950 dark:text-zinc-50">Rs {{ number_format($investor->purchase_balance, 2) }}</p>
                </div>
            </div>
        </div>

        <!-- Total Payable -->
        <div class="app-card p-5 border-l-4 border-indigo-500">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-400">
                    <flux:icon.wallet class="size-5" />
                </div>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Total Due') }}</p>
                    <p class="font-display text-xl font-bold text-zinc-950 dark:text-zinc-50">Rs {{ number_format($investor->total_payable, 2) }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabs Navigation -->
    <div class="border-b border-zinc-200 dark:border-zinc-800">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button wire:click="setTab('ledger')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'ledger' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                {{ __('Ledger History') }}
            </button>
            <button wire:click="setTab('profits')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'profits' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                {{ __('Sale Profits') }}
            </button>
            <button wire:click="setTab('fundings')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'fundings' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                {{ __('Purchase Fundings') }}
            </button>
            <button wire:click="setTab('payments')" class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium {{ $activeTab === 'payments' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                {{ __('Payments (Withdrawals)') }}
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="app-card">
        @if ($activeTab === 'ledger')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Description') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Amount IN (+)') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Amount OUT (-)') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($this->ledgerEntries as $entry)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $entry->date->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $entry->description }}
                                    <div class="text-xs text-zinc-500 font-normal uppercase">{{ str_replace('_', ' ', $entry->transaction_type) }}</div>
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                    @if($entry->profit_debit > 0 || $entry->purchase_debit > 0)
                                        Rs {{ number_format($entry->profit_debit + $entry->purchase_debit, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-rose-600 dark:text-rose-400">
                                    @if($entry->profit_credit > 0 || $entry->purchase_credit > 0)
                                        Rs {{ number_format($entry->profit_credit + $entry->purchase_credit, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-zinc-900 dark:text-zinc-100">
                                    Rs {{ number_format($entry->total_payable_balance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.document-text class="size-8 mx-auto mb-3 text-zinc-400" />
                                    <p class="font-medium">{{ __('No ledger entries found.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $this->ledgerEntries->links() }}
            </div>

        @elseif ($activeTab === 'profits')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Sale Invoice') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Sale Total') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Net Profit') }}</th>
                            <th class="px-6 py-3 font-medium text-center">{{ __('Your %') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Your Profit') }}</th>
                            <th class="px-6 py-3 font-medium text-center">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($this->profitTransactions as $profit)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $profit->date->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $profit->sale->invoice_no ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-right text-zinc-600 dark:text-zinc-400">
                                    Rs {{ number_format($profit->net_sales_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                    Rs {{ number_format($profit->eligible_profit, 2) }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ number_format($profit->investor_percentage, 2) }}%
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                    Rs {{ number_format($profit->investor_profit_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($profit->status === 'paid')
                                        <flux:badge color="emerald" size="sm">{{ __('Paid') }}</flux:badge>
                                    @elseif($profit->status === 'partial')
                                        <flux:badge color="amber" size="sm">{{ __('Partial') }}</flux:badge>
                                    @elseif($profit->status === 'reversed')
                                        <flux:badge color="rose" size="sm">{{ __('Reversed') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Unpaid') }}</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.arrow-trending-up class="size-8 mx-auto mb-3 text-zinc-400" />
                                    <p class="font-medium">{{ __('No sale profits found.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $this->profitTransactions->links() }}
            </div>
            
        @elseif ($activeTab === 'fundings')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Purchase Invoice') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Method') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Funded Amount') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Repaid Amount') }}</th>
                            <th class="px-6 py-3 font-medium text-center">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($this->purchaseFundings as $funding)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $funding->created_at->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $funding->purchase->invoice_no ?? '-' }}
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 uppercase text-xs">
                                    {{ $funding->payment_method ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-blue-600 dark:text-blue-400">
                                    Rs {{ number_format($funding->funded_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                    Rs {{ number_format($funding->repaid_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($funding->status === 'paid')
                                        <flux:badge color="emerald" size="sm">{{ __('Paid') }}</flux:badge>
                                    @elseif($funding->status === 'partial')
                                        <flux:badge color="amber" size="sm">{{ __('Partial') }}</flux:badge>
                                    @elseif($funding->status === 'reversed')
                                        <flux:badge color="rose" size="sm">{{ __('Reversed') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Unpaid') }}</flux:badge>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.banknotes class="size-8 mx-auto mb-3 text-zinc-400" />
                                    <p class="font-medium">{{ __('No purchase fundings found.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $this->purchaseFundings->links() }}
            </div>
            
        @elseif ($activeTab === 'payments')
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Payment No') }}</th>
                            <th class="px-6 py-3 font-medium">{{ __('Method') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Profit Portion') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Funding Repaid') }}</th>
                            <th class="px-6 py-3 font-medium text-right">{{ __('Total Paid') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($this->payments as $payment)
                            <tr class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition">
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                    {{ $payment->date->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $payment->payment_no }}
                                </td>
                                <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 uppercase text-xs">
                                    {{ $payment->payment_method }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-zinc-600 dark:text-zinc-400">
                                    Rs {{ number_format($payment->profit_payment_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-zinc-600 dark:text-zinc-400">
                                    Rs {{ number_format($payment->purchase_repayment_amount, 2) }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-rose-600 dark:text-rose-400">
                                    Rs {{ number_format($payment->total_payment, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                    <flux:icon.banknotes class="size-8 mx-auto mb-3 text-zinc-400" />
                                    <p class="font-medium">{{ __('No payments found.') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-zinc-200 dark:border-zinc-800">
                {{ $this->payments->links() }}
            </div>
        @endif
    </div>
</div>
