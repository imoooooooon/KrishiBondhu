{{-- Global footer: deep-green section in both themes. --}}
<footer class="site-footer">
    <div class="container">
        <div class="site-footer__grid">
            <div class="stack">
                <x-ui.logo on-dark />
                <p class="t-callout site-footer__tagline">{{ __('Fair prices, healthy herds and reliable hands for farmers across Bangladesh.') }}</p>
            </div>
            <div>
                <p class="site-footer__heading">{{ __('Buy and sell') }}</p>
                <ul class="site-footer__links" role="list">
                    <li><a href="{{ route('marketplace.index') }}">{{ __('Crop marketplace') }}</a></li>
                    <li><a href="{{ route('bulk-requests.index') }}">{{ __('Bulk requests') }}</a></li>
                    <li><a href="{{ route('market-prices.index') }}">{{ __('Market prices') }}</a></li>
                    <li><a href="{{ route('shop.index') }}">{{ __('Farm supplies') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="site-footer__heading">{{ __('Farm care') }}</p>
                <ul class="site-footer__links" role="list">
                    <li><a href="{{ route('cattle.index') }}">{{ __('Cattle records') }}</a></li>
                    <li><a href="{{ route('health.index') }}">{{ __('Crop and animal health') }}</a></li>
                    <li><a href="{{ route('vets.index') }}">{{ __('Find a vet') }}</a></li>
                    <li><a href="{{ route('jobs.index') }}">{{ __('Farm jobs') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="site-footer__heading">{{ __('Project') }}</p>
                <ul class="site-footer__links" role="list">
                    <li><a href="{{ route('design-system') }}">{{ __('Design system') }}</a></li>
                    <li><a href="https://github.com/imoooooooon/KrishiBondhu/blob/main/docs/IA.md">{{ __('Information architecture') }}</a></li>
                    <li><a href="https://github.com/imoooooooon/KrishiBondhu">{{ __('Source code') }}</a></li>
                </ul>
            </div>
        </div>
        <div class="site-footer__bottom">
            <p>© {{ date('Y') }} Krishi Bondhu · {{ __('CSE470 group project, BRAC University') }}</p>
            <p lang="bn">কৃষকের পাশে, প্রতিদিন · <a href="https://lipighor.com">ওয়েবফন্ট- lipighor.com</a></p>
        </div>
    </div>
</footer>
