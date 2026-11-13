{{--
    Simple page links built from the design-system buttons (Laravel's default pagination view needs Tailwind).
    Usage: {{ $items->links('partials.pagination') }}
--}}
@if ($paginator->hasPages())
    <nav class="cluster cluster--between mt-8" aria-label="{{ __('Pages') }}">
        <x-ui.button size="md" variant="secondary" icon="chevron-left" :href="$paginator->previousPageUrl()" :disabled="$paginator->onFirstPage()" rel="prev">{{ __('Previous') }}</x-ui.button>
        <span class="t-callout t-muted t-num">{{ __('Page :current of :last', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}</span>
        <x-ui.button size="md" variant="secondary" icon-after="chevron-right" :href="$paginator->nextPageUrl()" :disabled="! $paginator->hasMorePages()" rel="next">{{ __('Next') }}</x-ui.button>
    </nav>
@endif
