{{--
    Cattle profile (route cattle.show, owner only). F6. Controller: CattleController@show.
    Archive: the link reloads this page with ?confirm=archive; only the final DELETE button archives. No JS needed.
--}}
@php
    $archived = $cattle->isArchived();
    $notRecorded = __('Not recorded');
@endphp
<x-layouts.app :title="$cattle->title()">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/cattle.css') }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="$cattle->title()" class="cattle-text">
            <x-slot:breadcrumbs>
                <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                <x-ui.icon name="chevron-right" size="14" />
                <a href="{{ route('cattle.index') }}">{{ __('My cattle') }}</a>
            </x-slot:breadcrumbs>
            <x-slot:actions>
                @unless ($archived)
                    <x-ui.button size="md" variant="secondary" icon="plus" :href="route('cattle.health-history.create', $cattle)">{{ __('Add health entry') }}</x-ui.button>
                    <x-ui.button size="md" icon="pencil" :href="route('cattle.edit', $cattle)">{{ __('Edit profile') }}</x-ui.button>
                @endunless
            </x-slot:actions>
        </x-ui.page-header>

        <p class="cluster mb-6 cattle-id-line">
            <span class="t-callout t-muted">{{ __('KB ID') }}</span>
            <code class="cattle-id" id="cattle-kb-id">{{ $cattle->displayId() }}</code>
            <button type="button" class="btn btn--tertiary btn--sm js-only" data-copy="#cattle-kb-id" data-copied="{{ __('Copied') }}"><span>{{ __('Copy ID') }}</span></button>
        </p>

        @foreach (['cattle', 'version', 'confirm_archive', 'operation'] as $key)
            @error($key)<x-ui.alert tone="danger" role="alert" class="mb-6">{{ $message }}</x-ui.alert>@enderror
        @endforeach

        @if ($archived)
            <x-ui.alert tone="warning" class="mb-6" :title="__('Archived')">
                {{ __('This animal is not in your active list. Its profile and history are kept. Restore it to make changes.') }}
                <x-slot:action>
                    <form method="POST" action="{{ route('cattle.update', $cattle) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="operation" value="restore">
                        <input type="hidden" name="version" value="{{ $cattle->version }}">
                        <x-ui.button type="submit" size="md" icon="arrow-left">{{ __('Restore') }}</x-ui.button>
                    </form>
                </x-slot:action>
            </x-ui.alert>
        @endif

        <div class="cattle-layout">
            <figure class="cattle-photo">
                <img src="{{ $cattle->photoUrl('display') }}" width="{{ $cattle->photo_width }}" height="{{ $cattle->photo_height }}"
                    alt="{{ __('Photo of :name', ['name' => $cattle->title()]) }}">
            </figure>

            <div class="stack">
                <x-ui.card class="stack">
                    <h2 class="t-title-3">{{ __('Profile') }}</h2>
                    <dl class="cattle-facts">
                        <div><dt>{{ __('Farm tag') }}</dt><dd class="cattle-text">{{ $cattle->farm_tag ?? $notRecorded }}</dd></div>
                        <div><dt>{{ __('Nickname') }}</dt><dd class="cattle-text">{{ $cattle->nickname ?? $notRecorded }}</dd></div>
                        <div><dt>{{ __('Sex') }}</dt><dd>{{ $cattle->sexLabel() }}</dd></div>
                        <div><dt>{{ __('Breed') }}</dt><dd class="cattle-text">{{ $cattle->breed_description ?? $notRecorded }}
                            @if ($cattle->breed_description)<span class="t-caption t-muted cattle-facts__note">{{ __('Described by the farmer') }}</span>@endif</dd></div>
                        <div><dt>{{ __('Age') }}</dt><dd>
                            {{ $cattle->ageLabel() }}
                            <span class="t-caption t-muted cattle-facts__note">
                                @if ($cattle->age_basis === 'exact')
                                    {{ __('Exact: born :date', ['date' => $cattle->birth_date->translatedFormat('j M Y')]) }}
                                @elseif ($cattle->age_basis === 'estimated')
                                    {{ __('Estimated as :age on :date', ['age' => $cattle->estimated_age_months === 0 ? __('less than 1 month') : \App\Services\CattleAge::yearsAndMonths($cattle->estimated_age_months), 'date' => $cattle->age_recorded_on->translatedFormat('j M Y')]) }}
                                @endif
                            </span>
                        </dd></div>
                    </dl>
                </x-ui.card>

                <x-ui.card class="stack">
                    <h2 class="t-title-3">{{ __('Parents') }}</h2>
                    <dl class="cattle-facts">
                        <div><dt>{{ __('Mother (dam)') }}</dt><dd class="cattle-text cattle-text--lines">{{ $cattle->mother_details ?? $notRecorded }}</dd></div>
                        <div><dt>{{ __('Father (sire)') }}</dt><dd class="cattle-text cattle-text--lines">{{ $cattle->father_details ?? $notRecorded }}</dd></div>
                    </dl>
                    <p class="t-caption t-muted">{{ __('These are your notes, not linked or verified records.') }}</p>
                </x-ui.card>
            </div>
        </div>

        <section class="cattle-section" id="health-history" aria-labelledby="history-title">
            <div class="cluster cluster--between">
                <h2 class="t-title-2" id="history-title">{{ __('Health history') }}</h2>
                @unless ($archived)
                    <x-ui.button size="md" variant="secondary" icon="plus" :href="route('cattle.health-history.create', $cattle)">{{ __('Add health entry') }}</x-ui.button>
                @endunless
            </div>

            @if ($entries->isEmpty())
                <x-ui.empty-state icon="heart-pulse" :title="__('No health history recorded')"
                    :description="__('This does not confirm the animal’s health. Add past vaccinations, vet visits or treatments when you know them.')" />
            @else
                <ol class="cattle-timeline">
                    @foreach ($entries as $entry)
                        <li class="card cattle-entry">
                            <div class="cluster cluster--between">
                                <span class="t-callout {{ $entry->occurred_on ? '' : 't-muted' }}">
                                    <x-ui.icon name="calendar" size="16" />
                                    {{ $entry->occurred_on ? $entry->occurred_on->translatedFormat('j M Y') : __('Date not recorded') }}
                                </span>
                                <x-ui.badge tone="earth">{{ $entry->typeLabel() }}</x-ui.badge>
                            </div>
                            <h3 class="t-title-3 cattle-text">{{ $entry->title }}</h3>
                            <p class="cattle-text cattle-text--lines">{{ $entry->details }}</p>
                            <p class="t-caption t-muted">
                                @if ($entry->source_label){{ __('Source: :source', ['source' => $entry->source_label]) }} · @endif
                                {{ __('Recorded :date', ['date' => $entry->created_at->translatedFormat('j M Y')]) }}
                                @if ($entry->wasEdited()) · {{ __('Edited :date', ['date' => $entry->updated_at->translatedFormat('j M Y')]) }}@endif
                            </p>
                            @unless ($archived)
                                <div><x-ui.button size="sm" variant="tertiary" icon="pencil" :href="route('cattle.health-history.edit', [$cattle, $entry])">{{ __('Edit entry') }}<span class="visually-hidden"> {{ $entry->title }}</span></x-ui.button></div>
                            @endunless
                        </li>
                    @endforeach
                </ol>
                {{ $entries->fragment('health-history')->links('partials.pagination') }}
            @endif
        </section>

        <section class="cattle-section" aria-labelledby="connected-title">
            <h2 class="t-title-2" id="connected-title">{{ __('More for this animal') }}</h2>
            <div class="list-group">
                @foreach ($connected as $link)
                    <x-ui.list-row :icon="$link['icon']" :title="$link['label']" :subtitle="$link['description']" :href="$link['url']">
                        @if ($link['planned'])<x-ui.badge tone="info" icon="clock">{{ __('Planned') }}</x-ui.badge>@endif
                    </x-ui.list-row>
                @endforeach
            </div>
            <p class="t-caption t-muted mt-2">{{ __('“Planned” features are not built yet. Their pages say so and do not save anything.') }}</p>
        </section>

        @unless ($archived)
            <section class="cattle-section">
                @if ($confirmArchive)
                    <div class="card cattle-archive stack stack-sm" id="archive-confirmation" tabindex="-1" aria-labelledby="archive-title">
                        <h2 class="t-title-3" id="archive-title">{{ __('Archive this animal?') }}</h2>
                        <p class="t-callout">{{ __('It will leave your active list. Its profile and history will remain available in Archived, and you can restore it.') }}</p>
                        <form method="POST" action="{{ route('cattle.destroy', $cattle) }}" class="cluster">
                            @csrf @method('DELETE')
                            <input type="hidden" name="confirm_archive" value="1">
                            <input type="hidden" name="version" value="{{ $cattle->version }}">
                            <x-ui.button type="submit" size="md" variant="danger">{{ __('Archive') }}</x-ui.button>
                            <x-ui.button size="md" variant="tertiary" :href="route('cattle.show', $cattle)">{{ __('Cancel') }}</x-ui.button>
                        </form>
                    </div>
                @else
                    <x-ui.card class="stack stack-sm">
                        <h2 class="t-title-3">{{ __('Archive') }}</h2>
                        <p class="t-callout t-muted">{{ __('Move this animal out of your active list (for example, it is no longer on your farm). Nothing is deleted.') }}</p>
                        <div><x-ui.button size="md" variant="secondary" :href="route('cattle.show', [$cattle, 'confirm' => 'archive']).'#archive-confirmation'">{{ __('Archive…') }}</x-ui.button></div>
                    </x-ui.card>
                @endif
            </section>
        @endunless
    </div>

    <x-slot:scripts><script src="{{ asset('js/cattle.js') }}" defer></script></x-slot:scripts>
</x-layouts.app>
