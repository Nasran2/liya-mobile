<?php

use App\Models\Investor;
use App\Models\InvestorSetting;
use Flux\Flux;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Investor Directory')] class extends Component
{
    use WithPagination;

    public function mount()
    {
        abort_unless(auth()->user()->hasPermission('manage_investors'), 403);
    }

    public string $search = '';

    // Create / Edit modal state
    public bool $modalOpen = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public string $nic = '';
    public $default_profit_percentage = 0.00;
    public bool $is_active = true;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    #[Computed]
    public function investors()
    {
        return Investor::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('phone', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function defaultProductInvestorId()
    {
        return (int) InvestorSetting::where('key', 'default_product_investor_id')->value('value');
    }

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'phone', 'email', 'address', 'nic', 'default_profit_percentage', 'is_active']);
        $this->modalOpen = true;
    }

    public function openEditModal(int $id)
    {
        $this->resetValidation();
        $investor = Investor::findOrFail($id);
        $this->editingId = $investor->id;
        $this->name = $investor->name;
        $this->phone = $investor->phone ?? '';
        $this->email = $investor->email ?? '';
        $this->address = $investor->address ?? '';
        $this->nic = $investor->nic ?? '';
        $this->default_profit_percentage = $investor->default_profit_percentage;
        $this->is_active = $investor->is_active;
        $this->modalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'nic' => 'nullable|string|max:50',
            'default_profit_percentage' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        if ($this->editingId) {
            $investor = Investor::findOrFail($this->editingId);
            $investor->update([
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'nic' => $this->nic,
                'default_profit_percentage' => $this->default_profit_percentage,
                'is_active' => $this->is_active,
            ]);
            Flux::toast(variant: 'success', text: __('Investor updated successfully.'));
        } else {
            $lastId = Investor::max('id') ?? 0;
            $code = 'INV-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
            
            Investor::create([
                'code' => $code,
                'name' => $this->name,
                'phone' => $this->phone,
                'email' => $this->email,
                'address' => $this->address,
                'nic' => $this->nic,
                'default_profit_percentage' => $this->default_profit_percentage,
                'is_active' => $this->is_active,
            ]);
            Flux::toast(variant: 'success', text: __('Investor added successfully.'));
        }

        $this->modalOpen = false;
    }

    public function toggleActive(int $id)
    {
        $investor = Investor::findOrFail($id);
        $investor->update(['is_active' => !$investor->is_active]);
        Flux::toast(variant: 'success', text: __('Investor status updated.'));
    }

    public function setAsDefault(int $id)
    {
        InvestorSetting::updateOrCreate(
            ['key' => 'default_product_investor_id'],
            ['value' => (string) $id]
        );
        unset($this->defaultProductInvestorId);
        Flux::toast(variant: 'success', text: __('Default product sponsor updated.'));
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Investor Directory') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Manage investors, their contact details, and profit allocation percentages.') }}</p>
        </div>
        <flux:button wire:click="openCreateModal" variant="primary" class="w-full sm:w-auto">
            <flux:icon.plus class="size-4 mr-1" />
            {{ __('Add Investor') }}
        </flux:button>
    </div>

    <div class="app-card p-4">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name or phone...') }}" />
    </div>

    <div class="app-card">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-zinc-50 border-b border-zinc-200 text-zinc-500 dark:bg-zinc-900/50 dark:border-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">{{ __('Name') }}</th>
                        <th class="px-6 py-3 font-medium">{{ __('Contact') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Profit %') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Balance') }}</th>
                        <th class="px-6 py-3 font-medium text-center">{{ __('Status') }}</th>
                        <th class="px-6 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->investors as $investor)
                        <tr wire:key="investor-{{ $investor->id }}" class="hover:bg-zinc-50/50 dark:hover:bg-zinc-800/50 transition">
                            <td class="px-6 py-4 font-medium text-zinc-900 dark:text-zinc-100">
                                <a href="{{ route('investors.show', $investor) }}" class="hover:underline text-indigo-600 dark:text-indigo-400" wire:navigate>
                                    {{ $investor->name }}
                                </a>
                                @if($this->defaultProductInvestorId === $investor->id)
                                    <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[10px] font-medium text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400">
                                        <flux:icon.star class="size-3" variant="solid" /> Default
                                    </span>
                                @endif
                                @if($investor->nic)
                                    <div class="text-xs text-zinc-500 font-normal">NIC: {{ $investor->nic }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-zinc-600 dark:text-zinc-400">
                                <div>{{ $investor->phone ?? '-' }}</div>
                                @if($investor->email)
                                    <div class="text-xs">{{ $investor->email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                {{ number_format($investor->default_profit_percentage, 2) }}%
                            </td>
                            <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-zinc-100">
                                Rs {{ number_format($investor->total_payable, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button type="button" wire:click="toggleActive({{ $investor->id }})" class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold focus:outline-none transition {{ $investor->is_active ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-100 text-rose-700 hover:bg-rose-200 dark:bg-rose-500/10 dark:text-rose-400' }}">
                                    @if ($investor->is_active)
                                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span></span>
                                        {{ __('Active') }}
                                    @else
                                        <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                        {{ __('Inactive') }}
                                    @endif
                                </button>
                            </td>
                            <td class="px-6 py-4 text-right">
                                @if($this->defaultProductInvestorId !== $investor->id)
                                    <flux:button size="sm" variant="subtle" wire:click="setAsDefault({{ $investor->id }})" title="{{ __('Set as Default Sponsor') }}">
                                        <flux:icon.star class="size-4" />
                                    </flux:button>
                                @endif
                                <flux:button size="sm" variant="subtle" wire:click="openEditModal({{ $investor->id }})">
                                    <flux:icon.pencil-square class="size-4" />
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon.users class="size-8 mx-auto mb-3 text-zinc-400" />
                                <p class="font-medium">{{ __('No investors found') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-zinc-200 p-4 dark:border-zinc-800">
            {{ $this->investors->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal wire:model="modalOpen" class="w-full max-w-xl">
        <form wire:submit="save" class="flex flex-col gap-6">
            <div>
                <h2 class="text-lg font-bold text-zinc-950 dark:text-zinc-50">
                    {{ $editingId ? __('Edit Investor') : __('Add Investor') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Fill in the investor details below.') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:input wire:model="name" label="{{ __('Full Name') }}" placeholder="{{ __('John Doe') }}" required />
                </div>
                
                <flux:input wire:model="phone" label="{{ __('Phone Number') }}" placeholder="{{ __('07X XXX XXXX') }}" />
                <flux:input type="email" wire:model="email" label="{{ __('Email Address') }}" placeholder="{{ __('john@example.com') }}" />
                
                <flux:input wire:model="nic" label="{{ __('National Identity Card (NIC)') }}" placeholder="{{ __('XXXXXXXXXV') }}" />
                <flux:input type="number" step="0.01" wire:model="default_profit_percentage" label="{{ __('Default Profit %') }}" placeholder="10.00" required />
                
                <div class="sm:col-span-2">
                    <flux:textarea wire:model="address" label="{{ __('Address') }}" placeholder="{{ __('123 Main St...') }}" />
                </div>
                
                <div class="sm:col-span-2">
                    <flux:switch wire:model="is_active" label="{{ __('Account Active') }}" description="{{ __('Inactive investors will not appear in POS allocations.') }}" />
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 mt-2">
                <flux:button wire:click="$set('modalOpen', false)" variant="subtle">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">{{ __('Save Investor') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
