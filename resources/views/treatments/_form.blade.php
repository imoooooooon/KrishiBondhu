@csrf

<x-ui.select
    name="category"
    :label="__('Category')"
    :value="$treatment->category"
    :options="[
        'crop' => __('Crop'),
        'animal' => __('Animal'),
    ]"
    required
/>

<x-ui.field
    name="subject_name"
    :label="__('Crop or Animal Name')"
    :value="$treatment->subject_name"
    maxlength="120"
    required
/>

<x-ui.textarea
    name="problem"
    :label="__('Problem / Symptoms')"
    :value="$treatment->problem"
    rows="3"
    required
/>

<x-ui.textarea
    name="treatment_notes"
    :label="__('Treatment & Medicine Notes')"
    :value="$treatment->treatment_notes"
    rows="3"
    required
/>

<div class="grid-2">
    <x-ui.field
        name="treated_on"
        type="date"
        :label="__('Treatment Date')"
        :value="$treatment->treated_on?->format('Y-m-d')"
        required
    />

    <x-ui.field
        name="follow_up_date"
        type="date"
        :label="__('Follow-up Date (Optional)')"
        :value="$treatment->follow_up_date?->format('Y-m-d')"
        optional
    />
</div>

<x-ui.select
    name="status"
    :label="__('Status')"
    :value="$treatment->status ?? 'ongoing'"
    :options="[
        'ongoing' => __('Ongoing'),
        'completed' => __('Completed'),
    ]"
    required
/>

<div>
    <x-ui.button type="submit" icon="check">{{ $submitLabel }}</x-ui.button>
</div>