<?php

use App\Models\Investor;
use App\Models\InvestorLedgerEntry;
use App\Models\SaleInvestorAllocation;
use App\Models\InvestorPurchaseFunding;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Investor Dashboard')] class extends Component
{
    public function mount()
    {
        abort_unless(auth()->user()->hasPermission('manage_investors'), 403);
    }

    #[Computed]
    public function metrics()
    {
        $activeInvestors = Investor::where('is_active', true)->count();
        
        $totalProfitDistributed = InvestorLedgerEntry::whereIn('transaction_type', ['sale_profit'])
            ->sum('profit_credit');
            
        $totalPurchaseFunding = InvestorPurchaseFunding::sum('funded_amount');
            
        $totalWithdrawn = InvestorLedgerEntry::whereIn('transaction_type', ['profit_payment', 'purchase_repayment', 'combined_payment'])
            ->sum(DB::raw('profit_debit + purchase_debit'));

        $totalBalance = InvestorLedgerEntry::whereIn('id', function ($query) {
            $query->select(DB::raw('MAX(id)'))->from('investor_ledger_entries')->groupBy('investor_id');
        })->sum('total_payable_balance');

        return [
            'active_investors' => $activeInvestors,
            'total_profit' => (float) $totalProfitDistributed,
            'total_funding' => (float) $totalPurchaseFunding,
            'total_withdrawn' => (float) $totalWithdrawn,
            'total_balance' => (float) $totalBalance,
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <div>
        <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Investor Dashboard') }}</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Overview of investor contributions, distributed profits, and balances.') }}</p>
    </div>

    <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <!-- Active Investors -->
        <div class="app-card p-4">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-400">
                    <flux:icon.users class="size-5" />
                </div>
                <flux:badge size="sm" color="violet">{{ __('Total') }}</flux:badge>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Active Investors') }}</p>
            <p class="mt-1 font-display text-xl font-bold text-zinc-950 dark:text-zinc-50">{{ number_format($this->metrics['active_investors']) }}</p>
        </div>

        <!-- Total Profit Distributed -->
        <div class="app-card p-4">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <flux:icon.arrow-trending-up class="size-5" />
                </div>
                <flux:badge size="sm" color="emerald">{{ __('Profit') }}</flux:badge>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Total Profit Distributed') }}</p>
            <p class="mt-1 font-display text-xl font-bold text-zinc-950 dark:text-zinc-50">Rs {{ number_format($this->metrics['total_profit'], 2) }}</p>
        </div>

        <!-- Total Purchase Funding -->
        <div class="app-card p-4">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400">
                    <flux:icon.banknotes class="size-5" />
                </div>
                <flux:badge size="sm" color="blue">{{ __('Funding') }}</flux:badge>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Total Purchase Funding') }}</p>
            <p class="mt-1 font-display text-xl font-bold text-zinc-950 dark:text-zinc-50">Rs {{ number_format($this->metrics['total_funding'], 2) }}</p>
        </div>

        <!-- Total Unpaid Balance -->
        <div class="app-card p-4">
            <div class="flex items-center justify-between">
                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400">
                    <flux:icon.wallet class="size-5" />
                </div>
                <flux:badge size="sm" color="rose">{{ __('Due') }}</flux:badge>
            </div>
            <p class="mt-4 text-xs font-semibold uppercase tracking-wider text-zinc-400">{{ __('Total Unpaid Balance') }}</p>
            <p class="mt-1 font-display text-xl font-bold text-zinc-950 dark:text-zinc-50">Rs {{ number_format($this->metrics['total_balance'], 2) }}</p>
        </div>
    </section>

    <!-- Quick Actions -->
    <div class="app-card p-6">
        <h2 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">{{ __('Quick Actions') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <flux:button href="{{ route('investors.index') }}" wire:navigate class="w-full">
                <flux:icon.users class="size-4 mr-2" />
                {{ __('Manage Investors') }}
            </flux:button>
            <flux:button href="{{ route('investors.payments') }}" wire:navigate class="w-full">
                <flux:icon.banknotes class="size-4 mr-2" />
                {{ __('Process Payments') }}
            </flux:button>
            <flux:button href="{{ route('investors.reports') }}" wire:navigate class="w-full">
                <flux:icon.document-chart-bar class="size-4 mr-2" />
                {{ __('View Reports') }}
            </flux:button>
            <flux:button href="{{ route('investors.settings') }}" wire:navigate class="w-full">
                <flux:icon.cog-6-tooth class="size-4 mr-2" />
                {{ __('Settings') }}
            </flux:button>
        </div>
    </div>
</div>
