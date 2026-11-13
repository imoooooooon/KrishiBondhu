{{--
    F6: health-entry fields. Used by the entry forms (prefix '') and the optional first note on "Register cattle"
    (prefix 'history_', where the names are history_title, history_details, …). $entry is null for a new entry.
--}}
@php
    $p = $prefix ?? '';
    $entry = $entry ?? null;
    $typeName = $p === '' ? 'entry_type' : 'history_type';
    $dateName = $p.'occurred_on';
    $sourceName = $p === '' ? 'source_label' : 'history_source';
    $dateKnown = old($p.'date_known', $entry ? ($entry->occurred_on ? 'yes' : 'no') : 'yes');
@endphp
<x-ui.select :name="$typeName" :label="__('Type')" :options="\App\Models\CattleHealthEntry::typeLabels()" :value="$entry?->entry_type"
    :placeholder="__('Choose a type')" />
<x-ui.field :name="$p.'title'" :label="__('Title')" :value="$entry?->title" maxlength="120" :hint="__('Short, like: FMD vaccine at the upazila camp')" />

<fieldset class="fieldset" id="field-{{ $p }}date_known">
    <legend class="field__label">{{ __('When did it happen?') }}</legend>
    <div class="check-grid">
        <x-ui.checkbox type="radio" :name="$p.'date_known'" value="yes" :label="__('I know the date')" :checked="$dateKnown === 'yes'" data-date-choice />
        <x-ui.checkbox type="radio" :name="$p.'date_known'" value="no" :label="__('Date not known')" :checked="$dateKnown === 'no'" data-date-choice />
    </div>
</fieldset>
<div data-date-field>
    <x-ui.field :name="$dateName" type="date" :label="__('Date')" :value="$entry?->occurred_on?->toDateString()" :max="\App\Services\CattleAge::today()"
        :hint="__('Not in the future. Ignored if you chose “Date not known”.')" />
</div>

<x-ui.textarea :name="$p.'details'" :label="__('Details')" :value="$entry?->details" rows="4" maxlength="2000"
    :hint="__('What happened, as you remember or wrote it down. This is your note, not a diagnosis.')" />
<x-ui.field :name="$sourceName" :label="__('Source')" :value="$entry?->source_label" maxlength="120" optional
    :hint="__('Where the information comes from, like: my notebook, or the vet’s prescription')" />
