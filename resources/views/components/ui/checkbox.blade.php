{{--
    Checkbox or radio with a 44 px tall label.
    <x-ui.checkbox name="delivery[]" value="pickup" label="Buyer collects from the farm" />
    <x-ui.checkbox type="radio" name="wage_type" value="daily" label="Daily wage" />
--}}
@props(['name', 'label', 'value' => '1', 'checked' => false, 'type' => 'checkbox'])
@php
    $oldKey = str_replace('[]', '', $name);
    $isChecked = old($oldKey) !== null ? in_array((string) $value, array_map('strval', (array) old($oldKey)), true) : $checked;
@endphp
<label class="check">
    <input type="{{ $type }}" name="{{ $name }}" value="{{ $value }}" @checked($isChecked) {{ $attributes }}>
    <span>{{ $label }}</span>
</label>
