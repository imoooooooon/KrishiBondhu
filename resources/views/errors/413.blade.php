{{--
    413 Payload Too Large: the whole form was bigger than the server accepts (PHP post_max_size), so PHP threw the
    form away before Laravel could read it. Most often caused by large photos. Nothing was saved.
--}}
<x-layouts.app :title="__('Photos too large')" narrow>
    <x-ui.page-header :title="__('Your photos were too large to upload')" />
    <div class="stack">
        <x-ui.alert tone="warning" :title="__('Nothing was saved.')">
            {{ __('Each photo must be 5 MB or smaller, at most 5 photos. Choose smaller photos and fill in the form again.') }}
        </x-ui.alert>
        <div class="cluster">
            <x-ui.button icon="list" :href="route('farmer.listings.index')">{{ __('My crop listings') }}</x-ui.button>
            <x-ui.button variant="secondary" icon="house" :href="route('home')">{{ __('Home') }}</x-ui.button>
        </div>
    </div>
</x-layouts.app>
