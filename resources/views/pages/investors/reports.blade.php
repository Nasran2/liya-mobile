<?php

use App\Models\Investor;
use App\Models\InvestorLedgerEntry;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Investor Reports')] class extends Component
{
    use WithPagination;

    public function mount()
    {
        abort_unless(auth()->user()->hasPermission('manage_investors'), 403);
    }

    public ?int $selectedInvestorId = null;
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $type = '';

    public function updating($field)
    {
        $this->resetPage();
    }

    #[Computed]
    public function investors()
    {
        return Investor::orderBy('name')->get();
    }

    #[Computed]
    public function entries()
    {
        $query = InvestorLedgerEntry::with('investor')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($this->selectedInvestorId) {
            $query->where('investor_id', $this->selectedInvestorId);
        }

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }

        return $query->paginate(15);
    }

    public function downloadPdf()
    {
        $query = InvestorLedgerEntry::with('investor')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($this->selectedInvestorId) {
            $query->where('investor_id', $this->selectedInvestorId);
        }

        if ($this->dateFrom) {
            $query->whereDate('date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('date', '<=', $this->dateTo);
        }

        if ($this->type) {
            $query->where('transaction_type', $this->type);
        }

        $entries = $query->get();
        $investorName = $this->selectedInvestorId ? Investor::find($this->selectedInvestorId)?->name : null;

        $data = [
            'entries' => $entries,
            'investorName' => $investorName,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'type' => $this->type,
            'businessName' => Setting::get('business_name', 'My Business'),
            'businessAddress' => Setting::get('business_address', ''),
            'businessPhone' => Setting::get('business_phone', ''),
        ];

        $pdf = Pdf::loadView('pdf.investor-report', $data)
            ->setPaper('a4', 'landscape');

        return response()->streamDownload(
            fn () => print($pdf->output()),
            "investor-ledger-report.pdf"
        );
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Investor Ledger') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('View detailed profit, funding, and payment transactions.') }}</p>
        </div>
        <flux:button wire:click="downloadPdf" variant="primary" class="w-full sm:w-auto">
            <flux:icon.arrow-down-tray class="size-4 mr-1" />
            {{ __('Download PDF') }}
        </flux:button>
    </div>

    <!-- Filters -->
    <div class="app-card p-4 grid gap-4 sm:grid-cols-4 items-end">
        <flux:select wire:model.live="selectedInvestorId" label="{{ __('Investor') }}">
            <option value="">{{ __('All Investors') }}</option>
            @foreach ($this->investors as $inv)
                <option value="{{ $inv->id }}">{{ $inv->name }}</option>
            @endforeach
        </flux:select>
        
        <flux:select wire:model.live="type" label="{{ __('Type') }}">
            <option value="">{{ __('All Transaction Types') }}</option>
            <option value="sale_profit">Profit Allocation</option>
            <option value="purchase_funding">Purchase Funding</option>
            <option value="profit_payment">Profit Payment</option>
            <option value="purchase_repayment">Purchase Repayment</option>
            <option value="combined_payment">Combined Payment</option>
            <option value="adjustment">Manual Adjustment</option>
        </flux:select>
        
        <flux:input type="date" wire:model.live="dateFrom" label="{{ __('From') }}" />
        <flux:input type="date" wire:model.live="dateTo" label="{{ __('To') }}" />
    </div>

    <!-- Ledger Table -->
    <div class="app-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Investor') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Type') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Description') }}</th>
                        <th class="px-6 py-3 font-medium text-right text-emerald-600 dark:text-emerald-400">{{ __('Credit (In)') }}</th>
                        <th class="px-6 py-3 font-medium text-right text-rose-600 dark:text-rose-400">{{ __('Debit (Out)') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Balance') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->entries as $entry)
                        <tr wire:key="entry-{{ $entry->id }}" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition">
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $entry->date->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $entry->investor->name }}
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 uppercase text-xs">
                                <flux:badge size="sm" color="{{ $entry->transaction_type === 'sale_profit' ? 'emerald' : ($entry->transaction_type === 'purchase_funding' ? 'blue' : (in_array($entry->transaction_type, ['profit_payment', 'purchase_repayment', 'combined_payment']) ? 'rose' : 'zinc')) }}">
                                    {{ str_replace('_', ' ', $entry->transaction_type) }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 truncate max-w-xs" title="{{ $entry->description }}">
                                {{ $entry->description }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-emerald-600 dark:text-emerald-400">
                                {{ ($entry->profit_credit > 0 || $entry->purchase_credit > 0) ? 'Rs ' . number_format($entry->profit_credit + $entry->purchase_credit, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-rose-600 dark:text-rose-400">
                                {{ ($entry->profit_debit > 0 || $entry->purchase_debit > 0) ? 'Rs ' . number_format($entry->profit_debit + $entry->purchase_debit, 2) : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                Rs {{ number_format($entry->total_payable_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon.document-text class="size-8 mx-auto mb-3 text-zinc-400" />
                                <p class="font-medium">{{ __('No ledger entries found') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            {{ $this->entries->links() }}
        </div>
    </div>
</div>
