{{--
    Main page layout. Every page uses it:
    <x-layouts.app title="My listings" description="...">
        <div class="container"> ... </div>
    </x-layouts.app>
    Set :narrow="true" for forms and reading pages (720 px column). Page-specific scripts go in the "scripts" slot.
--}}
@props(['title' => null, 'description' => null, 'narrow' => false])
@php
    $assetVersion = fn (string $path) => asset($path).'?v='.(@filemtime(public_path($path)) ?: '1');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'Krishi Bondhu') }}</title>
    @if ($description)<meta name="description" content="{{ $description }}">@endif
    <meta name="theme-color" content="#134A23">
    <script>
        (function () {
            var theme;
            try { theme = localStorage.getItem('kb-theme'); } catch (e) {}
            if (theme !== 'light' && theme !== 'dark') {
                theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', theme);
            document.documentElement.classList.add('js');
        })();
    </script>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="preload" href="{{ asset('fonts/inter-tight/inter-tight-latin-wght-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ $assetVersion('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ $assetVersion('css/app.css') }}">
    {{ $head ?? '' }}
</head>
<body>
    <a class="skip-link" href="#main">{{ __('Skip to content') }}</a>

    @include('partials.site-header')

    <main id="main" class="main" tabindex="-1">
        @if (session('status'))
            <div class="container {{ $narrow ? 'container--narrow' : '' }} mb-6">
                <x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>
            </div>
        @endif
        @if ($narrow)
            <div class="container container--narrow">{{ $slot }}</div>
        @else
            {{ $slot }}
        @endif
    </main>

    @include('partials.site-footer')

    <script src="{{ $assetVersion('js/app.js') }}" defer></script>
    {{ $scripts ?? '' }}
</body>
</html>
