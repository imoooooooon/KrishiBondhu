{{-- Status badge for a job. $status comes from FarmJob::displayStatus(): open | closed | past. --}}
@if ($status === 'open')
    <x-ui.badge tone="success" icon="circle-check">{{ __('Open for workers') }}</x-ui.badge>
@elseif ($status === 'closed')
    <x-ui.badge icon="x">{{ __('Closed') }}</x-ui.badge>
@else
    <x-ui.badge tone="warning" icon="clock">{{ __('Date passed') }}</x-ui.badge>
@endif
