<?php

use App\Livewire\Concerns\InteractsWithBusinessReports;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Bill Profit Report')] class extends Component
{
    use InteractsWithBusinessReports;

    public string $reportType = 'bill-profits';
};
?>

@include('pages.reports.partials.report-page')
