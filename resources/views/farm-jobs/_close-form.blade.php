{{--
    "Close job" button (route farmer.jobs.close). A small form because closing changes data (PATCH + CSRF).
    data-confirm: public/js/farm-jobs.js asks "are you sure?" before the form is sent.
--}}
<form method="POST" action="{{ route('farmer.jobs.close', $job) }}"
    data-confirm="{{ __('Close this job? Workers will no longer see it. You cannot reopen it.') }}">
    @csrf
    @method('PATCH')
    <x-ui.button type="submit" size="md" variant="danger" icon="x">{{ __('Close job') }}</x-ui.button>
</form>
