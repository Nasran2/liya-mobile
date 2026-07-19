<?php

use App\Models\InvestorSetting;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Investor Settings')] class extends Component
{
    public bool $module_enabled = false;
    public string $default_profit_percentage = '0.00';

    public function mount()
    {
        abort_unless(auth()->user()->hasPermission('manage_investors'), 403);
        
        $this->module_enabled = InvestorSetting::where('key', 'module_enabled')->value('value') === '1';
        $this->default_profit_percentage = InvestorSetting::where('key', 'default_profit_percentage')->value('value') ?? '0.00';
    }

    public function saveSettings()
    {
        $this->validate([
            'module_enabled' => 'boolean',
            'default_profit_percentage' => 'numeric|min:0|max:100',
        ]);

        InvestorSetting::updateOrCreate(
            ['key' => 'module_enabled'],
            ['value' => $this->module_enabled ? '1' : '0', 'type' => 'boolean']
        );

        InvestorSetting::updateOrCreate(
            ['key' => 'default_profit_percentage'],
            ['value' => (string) $this->default_profit_percentage, 'type' => 'decimal']
        );

        Flux::toast(variant: 'success', text: __('Investor settings updated successfully.'));
    }
}; ?>

<div class="flex flex-col gap-6 max-w-3xl">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="font-display text-2xl font-bold tracking-tight text-zinc-950 dark:text-zinc-50">{{ __('Investor Settings') }}</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Configure system-wide behavior for the Investor Management module.') }}</p>
        </div>
    </div>

    <form wire:submit="saveSettings" class="app-card p-6 flex flex-col gap-6">
        <h2 class="text-lg font-bold text-zinc-950 dark:text-zinc-50 border-b border-zinc-200 dark:border-zinc-800 pb-2">
            {{ __('General Settings') }}
        </h2>

        <div class="space-y-6">
            <flux:switch wire:model="module_enabled" label="{{ __('Enable Investor Module') }}" description="{{ __('Turn on to track investor profit allocations on sales and fundings on purchases.') }}" />
            
            <flux:input type="number" step="0.01" wire:model="default_profit_percentage" label="{{ __('System Default Profit Percentage') }}" description="{{ __('The default percentage used if an investor does not have a specific percentage set.') }}" />
        </div>

        <div class="flex items-center justify-end mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-800">
            <flux:button type="submit" variant="primary">
                {{ __('Save Settings') }}
            </flux:button>
        </div>
    </form>
</div>
