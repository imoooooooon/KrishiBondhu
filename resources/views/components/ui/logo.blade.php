{{--
    Krishi Bondhu brand: sprout mark + bilingual wordmark (real text, not an image).
    <x-ui.logo />  ·  <x-ui.logo :wordmark="false" size="32" />  ·  <x-ui.logo on-dark />
    Colours follow the theme through CSS (--logo-leaf, --logo-sprout). Do not recolour or redraw the mark.
--}}
@props(['size' => 36, 'wordmark' => true, 'onDark' => false])
<span {{ $attributes->class(['logo', 'logo--on-dark' => $onDark]) }}>
    <svg class="logo__mark" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <path d="M8 33.5h24" stroke="var(--logo-leaf)" stroke-width="3" stroke-linecap="round"/>
        <path d="M20 33.5V18" stroke="var(--logo-leaf)" stroke-width="3" stroke-linecap="round"/>
        <path d="M20 25c-7.5 0-12-4.5-12-12 7.5 0 12 4.5 12 12Z" fill="var(--logo-leaf)"/>
        <path d="M20 19.5c0-7.5 4.5-12 12-12 0 7.5-4.5 12-12 12Z" fill="var(--logo-sprout)"/>
        <path d="M21.5 18 29 10.5" stroke="var(--logo-leaf)" stroke-width="1.6" stroke-linecap="round"/>
    </svg>
    @if ($wordmark)
        <span class="logo__text">
            <span class="logo__name" lang="en">Krishi Bondhu</span>
            <span class="logo__name-bn" lang="bn">কৃষি বন্ধু</span>
        </span>
    @else
        <span class="visually-hidden">Krishi Bondhu</span>
    @endif
</span>
