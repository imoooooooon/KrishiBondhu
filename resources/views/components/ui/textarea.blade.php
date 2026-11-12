{{-- <x-ui.textarea name="symptoms" label="Describe the problem" hint="What changed, and since when?" /> --}}
@props(['name', 'label', 'value' => null, 'hint' => null, 'rows' => 4, 'id' => null])
@php
    $id = $id ?? 'field-'.str_replace(['[', ']', '.'], '-', $name);
    $error = $errors->first($name);
    $describedBy = $error ? $id.'-error' : ($hint ? $id.'-hint' : null);
@endphp
<div class="field">
    <label class="field__label" for="{{ $id }}">{{ $label }}</label>
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" {{ $attributes->class(['textarea']) }} @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif @if ($error) aria-invalid="true" @endif>{{ old($name, $value) }}</textarea>
    @if ($hint && ! $error)<p class="field__hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif
    @if ($error)<p class="field__error" id="{{ $id }}-error"><x-ui.icon name="circle-alert" size="16" />{{ $error }}</p>@endif
</div>
