/*
 * Krishi Bondhu — small, dependency-free browser behaviour for the shell.
 * Everything here is progressive enhancement: pages work without JavaScript.
 * Feature-specific scripts belong in their own file in public/js/, loaded only on the pages that need them.
 */
(function () {
    'use strict';

    var root = document.documentElement;

    // Theme: light / dark, remembered per browser. The initial value is set inline in <head> to avoid a flash.
    function setTheme(theme) {
        root.setAttribute('data-theme', theme);
        try { localStorage.setItem('kb-theme', theme); } catch (e) { /* storage unavailable */ }
        document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', theme === 'dark' ? 'true' : 'false');
        });
    }

    document.addEventListener('click', function (event) {
        var themeButton = event.target.closest('[data-theme-toggle]');
        if (themeButton) {
            setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
            return;
        }

        var menuButton = event.target.closest('[data-menu-toggle]');
        if (menuButton) {
            var header = menuButton.closest('.site-header');
            var open = !header.hasAttribute('data-open');
            header.toggleAttribute('data-open', open);
            menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
            return;
        }

        // Design-system page: preview a region in English or Bangla.
        var langButton = event.target.closest('[data-preview-lang]');
        if (langButton) {
            var target = document.getElementById(langButton.getAttribute('aria-controls'));
            if (target) {
                target.setAttribute('lang', langButton.getAttribute('data-preview-lang'));
                langButton.parentElement.querySelectorAll('[data-preview-lang]').forEach(function (b) {
                    b.setAttribute('aria-pressed', b === langButton ? 'true' : 'false');
                });
            }
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        var header = document.querySelector('.site-header[data-open]');
        if (header) {
            header.removeAttribute('data-open');
            var button = header.querySelector('[data-menu-toggle]');
            if (button) { button.setAttribute('aria-expanded', 'false'); button.focus(); }
        }
    });

    // Header gains a hairline and shadow once the page scrolls.
    var siteHeader = document.querySelector('.site-header');
    if (siteHeader) {
        var onScroll = function () { siteHeader.toggleAttribute('data-scrolled', window.scrollY > 4); };
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    }

    // Once-only reveal for elements marked data-reveal.
    var revealed = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window && revealed.length) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '0px 0px -10% 0px' });
        revealed.forEach(function (el) { observer.observe(el); });
    } else {
        revealed.forEach(function (el) { el.classList.add('is-visible'); });
    }

    setTheme(root.getAttribute('data-theme') || 'light');
})();
