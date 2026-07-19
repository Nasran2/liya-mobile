<?php

use App\Models\Investor;
use App\Models\InvestorPayment;
use App\Services\InvestorService;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Investor Payments')] class extends Component
{
    use WithPagination;

    public function mount()
    {
        abort_unless(auth()->user()->hasPermission('manage_investors'), 403);
    }

    public string $search = '';

    // Payment Modal State
    public bool $modalOpen = false;
    public ?int $selectedInvestorId = null;
    public $amount = 0.00;
    public string $payment_method = 'cash';
    public string $reference_no = '';
    public string $notes = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function payments()
    {
        return InvestorPayment::with('investor')
            ->whereHas('investor', function($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orWhere('payment_no', 'like', '%' . $this->search . '%')
            ->orderByDesc('id')
            ->paginate(15);
    }

    #[Computed]
    public function activeInvestors()
    {
        return Investor::where('is_active', true)
            ->where('balance', '>', 0)
            ->orderBy('name')
            ->get();
    }

    public function openPaymentModal()
    {
        $this->resetValidation();
        $this->reset(['selectedInvestorId', 'amount', 'payment_method', 'reference_no', 'notes']);
        $this->modalOpen = true;
    }

    public function savePayment()
    {
        $this->validate([
            'selectedInvestorId' => 'required|exists:investors,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque',
            'reference_no' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        $investor = Investor::findOrFail($this->selectedInvestorId);

        if ($this->amount > $investor->balance) {
            $this->addError('amount', __('Amount cannot exceed investor balance of Rs :balance', ['balance' => $investor->balance]));
            return;
        }

        app(InvestorService::class)->processPayment(
            $investor,
            (float) $this->amount,
            $this->payment_method,
            $this->reference_no,
            $this->notes
        );

        Flux::toast(variant: 'success', text: __('Payment processed successfully.'));
        $this->modalOpen = false;
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Investor Payments') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Process and track profit withdrawals by investors.') }}</p>
        </div>
        <flux:button wire:click="openPaymentModal" variant="primary" class="w-full sm:w-auto">
            <flux:icon.banknotes class="size-4 mr-1" />
            {{ __('Process Payment') }}
        </flux:button>
    </div>

    <div class="app-card p-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by payment no or investor name...') }}" />
    </div>

    <div class="app-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">{{ __('Payment No') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Date') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Investor') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Method') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Amount') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->payments as $payment)
                        <tr wire:key="payment-{{ $payment->id }}" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition">
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $payment->payment_no }}
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                {{ $payment->date->format('Y-m-d') }}
                            </td>
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $payment->investor->name }}
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400 uppercase text-xs">
                                {{ $payment->payment_method }}
                                @if($payment->reference_no)
                                    <div class="text-zinc-400 lowercase">{{ $payment->reference_no }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                Rs {{ number_format($payment->amount, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon.banknotes class="size-8 mx-auto mb-3 text-zinc-400" />
                                <p class="font-medium">{{ __('No payments found') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            {{ $this->payments->links() }}
        </div>
    </div>

    <!-- Process Payment Modal -->
    <flux:modal wire:model="modalOpen" class="w-full max-w-xl">
        <form wire:submit="savePayment" class="flex flex-col gap-6">
            <div>
                <h2 class="text-lg font-bold text-zinc-950 dark:text-zinc-50">{{ __('Process Investor Payment') }}</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Record a profit withdrawal payment to an investor.') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:select wire:model="selectedInvestorId" label="{{ __('Investor') }}" placeholder="{{ __('Select Investor') }}" required>
                        @foreach ($this->activeInvestors as $inv)
                            <option value="{{ $inv->id }}">{{ $inv->name }} (Bal: Rs {{ number_format($inv->balance, 2) }})</option>
                        @endforeach
                    </flux:select>
                </div>
                
                <flux:input type="number" step="0.01" wire:model="amount" label="{{ __('Amount (Rs)') }}" placeholder="0.00" required />
                <flux:select wire:model="payment_method" label="{{ __('Payment Method') }}" required>
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cheque">Cheque</option>
                </flux:select>
                
                <div class="sm:col-span-2">
                    <flux:input wire:model="reference_no" label="{{ __('Reference Number') }}" placeholder="{{ __('Transaction ID or Cheque No') }}" />
                </div>
                
                <div class="sm:col-span-2">
                    <flux:textarea wire:model="notes" label="{{ __('Notes') }}" placeholder="{{ __('Optional payment details...') }}" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-2">
                <flux:button wire:click="$set('modalOpen', false)" variant="subtle">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Process Payment') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
