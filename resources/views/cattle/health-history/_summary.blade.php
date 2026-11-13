{{-- Which animal am I writing about? A small identity card at the top of the health-entry forms. --}}
<x-ui.card class="cattle-summary mb-6">
    <img src="{{ $cattle->photoUrl('thumb') }}" width="{{ $cattle->thumbnail_width }}" height="{{ $cattle->thumbnail_height }}" alt="">
    <div class="stack stack-sm">
        <p class="t-title-3 cattle-text">{{ $cattle->title() }}</p>
        <p class="t-caption t-muted"><span class="cattle-id">{{ $cattle->displayId() }}</span> · {{ $cattle->ageLabel() }}</p>
    </div>
</x-ui.card>
