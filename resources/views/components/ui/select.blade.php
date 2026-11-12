{{--
    Select with label. :options is [value => label].
    <x-ui.select name="district" label="District" :options="$districts" placeholder="Choose a district" />
--}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null, 'id' => null])
@php
    $id = $id ?? 'field-'.str_replace(['[', ']', '.'], '-', $name);
    $error = $errors->first($name);
    $selected = (string) old($name, $value);
    $describedBy = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp
<div class="field">
    <label class="field__label" for="{{ $id }}">{{ $label }}</label>
    <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->class(['select']) }} @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif>
        @if ($placeholder)<option value="" disabled @selected($selected === '')>{{ $placeholder }}</option>@endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
    </select>
    @if ($hint && ! $error)<p class="field__hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif
    @if ($error)<p class="field__error" id="{{ $id }}-error"><x-ui.icon name="circle-alert" size="16" />{{ $error }}</p>@endif
</div>
