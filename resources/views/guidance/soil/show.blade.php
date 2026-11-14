{{--
    Soil guidance result (route guidance.soil.show, owner only). F15. Controller: SoilGuidanceController@show.

    Shows one of four states: in progress (the page reloads by itself), waiting, failed (with "Try again" when
    allowed) or ready (the guidance in sections). The AI text is printed with {{ }} only, so any HTML in it is
    shown as plain text and never runs. No raw JSON is shown.
--}}
@php
    $guidance = $record->guidance ?? [];
    $notStarted = $record->status === \App\Models\SoilGuidanceRecord::STATUS_PENDING;
    // The sections of a ready result: [key in the saved guidance, heading, icon, text when the list is empty].
    $sections = [
        ['general_soil_care', __('General soil care'), 'leaf', null],
        ['fertilizer_guidance', __('Fertilizer guidance'), 'sprout', __('No fertilizer suggestion could be made from these observations.')],
        ['limitations', __('What this guidance cannot tell you'), 'triangle-alert', null],
        ['follow_up_questions', __('Questions that would help'), 'circle-help', null],
    ];
@endphp
<x-layouts.app :title="__('Soil guidance for :crop', ['crop' => $record->crop_name])">
    <x-slot:head>
        <link rel="stylesheet" href="{{ asset('css/soil-guidance.css').'?v='.@filemtime(public_path('css/soil-guidance.css')) }}">
        {{-- While another request is still asking the AI service, look again every 5 seconds (no JavaScript needed). --}}
        @if ($record->isInProgress())<meta http-equiv="refresh" content="5">@endif
    </x-slot:head>

    <div class="container">
        <x-ui.page-header :title="__('Soil guidance for :crop', ['crop' => $record->crop_name])" class="soil-text">
            <x-slot:breadcrumbs>
                <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                <x-ui.icon name="chevron-right" size="14" />
                <a href="{{ route('guidance.soil.index') }}">{{ __('My soil guidance') }}</a>
            </x-slot:breadcrumbs>
            <x-slot:actions>
                <x-ui.button size="md" icon="plus" :href="route('guidance.soil.create')">{{ __('New request') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>

        <p class="cluster mb-6">
            <x-ui.badge :tone="$record->statusTone()">{{ $record->statusLabel() }}</x-ui.badge>
            <span class="t-callout t-muted">
                <time datetime="{{ $record->created_at->toIso8601String() }}">{{ __('Requested :date', ['date' => $record->requestedAtForDisplay()]) }}</time>
            </span>
        </p>

        @error('retry')<x-ui.alert tone="warning" role="alert" class="mb-6">{{ $message }}</x-ui.alert>@enderror

        <div class="soil-layout">
            <div class="stack">
                @if ($record->isCompleted())
                    <x-ui.card class="stack stack-sm">
                        <h2 class="t-title-3">{{ __('Summary of your observations') }}</h2>
                        <p class="soil-text">{{ $guidance['observations_summary'] ?? '' }}</p>
                    </x-ui.card>

                    @foreach ($sections as [$key, $heading, $icon, $emptyText])
                        @php $items = $record->guidanceList($key); @endphp
                        @if ($items !== [] || $emptyText)
                            <x-ui.card class="stack stack-sm">
                                <h2 class="t-title-3 soil-heading"><x-ui.icon :name="$icon" size="20" /> {{ $heading }}</h2>
                                @if ($items === [])
                                    <p class="t-muted">{{ $emptyText }}</p>
                                @else
                                    <ul class="soil-list">
                                        @foreach ($items as $item)<li class="soil-text">{{ $item }}</li>@endforeach
                                    </ul>
                                @endif
                            </x-ui.card>
                        @endif
                    @endforeach

                    <x-ui.alert tone="info">
                        {{ __('This guidance is based on the information you provided. It does not replace a soil test or advice from a qualified agricultural professional.') }}
                    </x-ui.alert>
                    <p class="t-caption t-muted">{{ __('Written by an AI service from what you described. It can be wrong.') }}</p>
                @elseif ($record->isInProgress())
                    <x-ui.alert tone="info" role="status" :title="__('Preparing your guidance')">
                        {{ __('This can take up to half a minute. This page checks again by itself every few seconds.') }}
                        <x-slot:action>
                            <x-ui.button size="md" variant="secondary" :href="route('guidance.soil.show', $record)">{{ __('Check now') }}</x-ui.button>
                        </x-slot:action>
                    </x-ui.alert>
                @else
                    {{-- Failed, stuck, or never started. --}}
                    @if ($notStarted)
                        <x-ui.alert tone="info" :title="__('Guidance has not been generated yet')">
                            {{ __('Your observations have been saved, but they have not been sent to the AI service yet.') }}
                        </x-ui.alert>
                    @else
                        <x-ui.alert tone="danger" :title="__('Guidance could not be generated')">{{ $record->failureMessage() }}</x-ui.alert>
                    @endif

                    @if ($record->canRetry())
                        <form method="POST" action="{{ route('guidance.soil.retry', $record) }}" class="stack stack-sm" data-soil-form>
                            @csrf
                            <div class="cluster">
                                <x-ui.button type="submit" icon="sprout" data-submit-once>{{ __('Try again') }}</x-ui.button>
                                <span class="t-callout t-muted">{{ trans_choice(':count try left for this request.|:count tries left for this request.', $record->attemptsLeft(), ['count' => $record->attemptsLeft()]) }}</span>
                            </div>
                            <p class="t-caption t-muted">{{ __('Trying again sends the same observations. To change them, make a new request.') }}</p>
                            <p class="t-callout soil-wait" role="status" data-processing-note hidden>
                                <x-ui.icon name="clock" size="16" />
                                {{ __('Preparing your guidance. This can take up to half a minute. Please keep this page open.') }}
                            </p>
                        </form>
                    @else
                        <p class="t-callout t-muted">{{ __('No more tries are left for this request. You can make a new request.') }}</p>
                    @endif
                @endif
            </div>

            <x-ui.card class="stack">
                <h2 class="t-title-3">{{ __('What you told us') }}</h2>
                <dl class="soil-facts">
                    <div><dt>{{ __('Crop') }}</dt><dd class="soil-text">{{ $record->crop_name }}</dd></div>
                    <div><dt>{{ __('Soil colour') }}</dt><dd>{{ $record->soilColorLabel() }}</dd></div>
                    <div><dt>{{ __('Moisture') }}</dt><dd>{{ $record->moistureLabel() }}</dd></div>
                    <div><dt>{{ __('Texture') }}</dt><dd>{{ $record->textureLabel() }}</dd></div>
                    <div><dt>{{ __('Other things you noticed') }}</dt><dd class="soil-text soil-text--lines">{{ $record->observations ?? __('Nothing added') }}</dd></div>
                </dl>
                @if ($record->hasPhoto())
                    <figure class="soil-photo">
                        <img src="{{ $record->photoUrl() }}" alt="{{ __('The soil photo you added') }}" loading="lazy">
                        <figcaption class="t-caption t-muted">{{ __('Your photo. Only you can see it.') }}</figcaption>
                    </figure>
                @else
                    <p class="t-caption t-muted">{{ __('No photo was added.') }}</p>
                @endif
            </x-ui.card>
        </div>
    </div>

    <x-slot:scripts><script src="{{ asset('js/soil-guidance.js').'?v='.@filemtime(public_path('js/soil-guidance.js')) }}" defer></script></x-slot:scripts>
</x-layouts.app>
