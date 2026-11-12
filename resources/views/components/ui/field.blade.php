{{--
    Text input with label, hint and validation error. Reads $errors and old() automatically.
    <x-ui.field name="quantity" label="Quantity" type="number" hint="In kilograms" prefix="kg" required />
--}}
@props(['name', 'label', 'type' => 'text', 'hint' => null, 'value' => null, 'prefix' => null, 'optional' => false, 'id' => null, 'error' => null])
@php
    $id = $id ?? 'field-'.str_replace(['[', ']', '.'], '-', $name);
    $errorKey = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
    $error = $error ?? $errors->first($errorKey);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($error ? $id.'-error' : ''));
@endphp
<div class="field">
    <label class="field__label" for="{{ $id }}">{{ $label }} @if ($optional)<span class="field__optional">({{ __('optional') }})</span>@endif</label>
    @if ($prefix)
        <div class="input-group">
            <span class="input-group__addon">{{ $prefix }}</span>
    @endif
    <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($errorKey, $value) }}"
        {{ $attributes->class(['input']) }}
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif>
    @if ($prefix)
        </div>
    @endif
    @if ($hint)<p class="field__hint" id="{{ $id }}-hint">{{ $hint }}</p>@endif
    @if ($error)<p class="field__error" id="{{ $id }}-error"><x-ui.icon name="circle-alert" size="16" />{{ $error }}</p>@endif
</div>
