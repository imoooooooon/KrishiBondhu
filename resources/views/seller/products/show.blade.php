{{--
    The owner's page for one product (route seller.products.show, owner only). F25.
    Controller: Seller\AgriculturalProductController@show. Every form here sends the version this page was opened with.
    Removing: the "Remove product" link reloads this page with ?confirm=remove, which shows a confirmation box.
    Nothing is changed by opening a link (GET); only the final button (DELETE) removes. No JavaScript needed.
--}}
<x-layouts.app :title="$product->name">
    <x-slot:head><link rel="stylesheet" href="{{ asset('css/shop.css').'?v='.@filemtime(public_path('css/shop.css')) }}"></x-slot:head>

    <div class="container">
        <x-ui.page-header :title="$product->name" class="supply-text">
            <x-slot:breadcrumbs>
                <a href="{{ route('dashboard') }}">{{ __('Dashboard') }}</a>
                <x-ui.icon name="chevron-right" size="14" />
                <a href="{{ route('seller.products.index') }}">{{ __('My products') }}</a>
            </x-slot:breadcrumbs>
            <x-slot:actions>
                @if ($product->isPublished())
                    <x-ui.button size="md" variant="secondary" icon="external-link" :href="route('shop.show', $product)">{{ __('View in shop') }}</x-ui.button>
                @endif
                <x-ui.button size="md" icon="pencil" :href="route('seller.products.edit', $product)">{{ __('Edit') }}</x-ui.button>
            </x-slot:actions>
        </x-ui.page-header>


        @if ($errors->has('operation') || $errors->has('version') || $errors->has('confirm_remove'))
            <x-ui.alert tone="danger" role="alert" class="mb-6" :title="__('This was not done')">
                <ul class="supply-error-list">
                    @foreach ([...$errors->get('operation'), ...$errors->get('version'), ...$errors->get('confirm_remove')] as $message)
                        <li>{{ $message }}</li>
                    @endforeach
                </ul>
            </x-ui.alert>
        @endif

        @unless ($product->isPublished())
            <x-ui.alert tone="warning" class="mb-6" :title="__('Hidden from buyers')">
                {{ __('Only you can see this product. Publish it again when it is available.') }}
            </x-ui.alert>
        @endunless

        <div class="supply-layout">
            <x-ui.card class="stack">
                @include('agricultural-products._photo')
                <div class="cluster cluster--between">
                    @include('agricultural-products._status-badge')
                    <span class="t-caption t-muted">{{ __('Updated :date', ['date' => $product->updated_at->translatedFormat('j M Y')]) }}</span>
                </div>
                @include('agricultural-products._facts', ['showPrivate' => true])
            </x-ui.card>

            <div class="stack">
                <x-ui.card class="stack stack-sm">
                    <h2 class="t-title-3">{{ __('Visibility') }}</h2>
                    @if ($product->isPublished())
                        <p class="t-callout t-muted">{{ __('Buyers can see this product. Hide it when you stop selling it for a while.') }}</p>
                        <form method="POST" action="{{ route('seller.products.update', $product) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="operation" value="unpublish">
                            <input type="hidden" name="version" value="{{ $product->version }}">
                            <x-ui.button type="submit" size="md" variant="secondary">{{ __('Unpublish') }}</x-ui.button>
                        </form>
                    @else
                        <p class="t-callout t-muted">{{ __('Publishing shows the product to buyers again. A product with no stock shows as out of stock.') }}</p>
                        <form method="POST" action="{{ route('seller.products.update', $product) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="operation" value="publish">
                            <input type="hidden" name="version" value="{{ $product->version }}">
                            <x-ui.button type="submit" size="md" icon="eye">{{ __('Publish again') }}</x-ui.button>
                        </form>
                    @endif
                    <p class="t-caption t-muted">
                        {{ __('Added on :date', ['date' => $product->created_at->translatedFormat('j M Y')]) }}
                        @if ($product->published_at) · {{ __('First published :date', ['date' => $product->published_at->translatedFormat('j M Y')]) }} @endif
                    </p>
                </x-ui.card>

                @if ($confirmRemove)
                    <section class="card supply-danger stack stack-sm" id="remove-confirmation" aria-labelledby="remove-title" tabindex="-1">
                        <h2 class="t-title-3" id="remove-title">{{ __('Remove this product?') }}</h2>
                        <p class="t-callout supply-text">
                            <strong>{{ $product->name }}</strong> —
                            {{ __('It will disappear from your products and the shop. This screen cannot restore it.') }}
                        </p>
                        <form method="POST" action="{{ route('seller.products.destroy', $product) }}" class="cluster">
                            @csrf @method('DELETE')
                            <input type="hidden" name="confirm_remove" value="1">
                            <input type="hidden" name="version" value="{{ $product->version }}">
                            <x-ui.button type="submit" size="md" variant="danger" icon="trash-2">{{ __('Remove product') }}</x-ui.button>
                            <x-ui.button size="md" variant="tertiary" :href="route('seller.products.show', $product)">{{ __('Cancel') }}</x-ui.button>
                        </form>
                    </section>
                @else
                    <section class="card supply-danger stack stack-sm" aria-labelledby="danger-title">
                        <h2 class="t-title-3" id="danger-title">{{ __('Remove product') }}</h2>
                        <p class="t-callout t-muted">{{ __('Removing hides the product everywhere. To hide it only for a while, use Unpublish.') }}</p>
                        <div><x-ui.button size="md" variant="danger" icon="trash-2" :href="route('seller.products.show', [$product, 'confirm' => 'remove']).'#remove-confirmation'">{{ __('Remove product') }}</x-ui.button></div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</x-layouts.app>
