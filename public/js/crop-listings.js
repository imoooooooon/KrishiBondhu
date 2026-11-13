/*
 | Crop listings and the crop marketplace (F1). Progressive enhancement only: every form, link and filter
 | works without this file. Text is always inserted with textContent, never innerHTML, so text typed by a
 | farmer can never run as code.
 |
 | 1. Error summary: move focus to it after a failed submit, so screen readers announce the problems.
 | 2. Listing form: live "You are offering 250 kg at ৳35 per kg." summary; the price label follows the unit;
 |    a note when the unit is changed (nothing is converted).
 | 3. Photo picker: previews and a count of selected photos, with a warning above the 5-photo limit.
 | 4. Submit once: the button is disabled after the first press (the server also ignores duplicates).
 | 5. Gallery: the small photos replace the large one without reloading the page.
 | 6. "Coming soon" message for actions owned by features that are not built yet (forms with data-planned).
 */
(function () {
    'use strict';

    // 1. Error summary
    var summary = document.querySelector('[data-error-summary]');
    if (summary) { summary.focus(); }

    // 2. Listing form summary and unit label
    var quantity = document.querySelector('[data-listing-quantity]');
    var unit = document.querySelector('[data-listing-unit]');
    var price = document.querySelector('[data-listing-price]');
    var sentence = document.querySelector('[data-listing-summary]');

    if (quantity && unit && price && sentence) {
        var units = {};
        try { units = JSON.parse(sentence.getAttribute('data-units')) || {}; } catch (e) { /* keep defaults */ }
        var priceLabel = document.querySelector('label[for="' + price.id + '"]');
        var priceLabelTemplate = priceLabel ? priceLabel.textContent.trim() : '';
        var initialUnitName = units[unit.getAttribute('data-initial')] || '';
        var unitNote = document.querySelector('[data-unit-note]');
        var plainNumber = /^[0-9০-৯]+([.][0-9০-৯]+)?$/;

        var update = function () {
            var unitName = units[unit.value] || unit.value;
            var q = quantity.value.trim();
            var p = price.value.trim();
            if (plainNumber.test(q) && plainNumber.test(p)) {
                sentence.textContent = sentence.getAttribute('data-template')
                    .split(':quantity').join(q).split(':price').join(p).split(':unit').join(unitName);
            } else {
                sentence.textContent = sentence.getAttribute('data-empty');
            }
            if (priceLabel && initialUnitName) {
                priceLabel.textContent = priceLabelTemplate.split(initialUnitName).join(unitName);
            }
            if (unitNote) { unitNote.hidden = unit.value === unit.getAttribute('data-initial'); }
        };

        [quantity, price].forEach(function (input) { input.addEventListener('input', update); });
        unit.addEventListener('change', update);
    }

    // 3. Photo previews
    var photoInput = document.querySelector('[data-photo-input]');
    var previews = document.querySelector('[data-photo-previews]');
    var photoStatus = document.querySelector('[data-photo-status]');
    var objectUrls = [];

    if (photoInput && previews && photoStatus && window.URL && URL.createObjectURL) {
        photoInput.addEventListener('change', function () {
            objectUrls.forEach(function (url) { URL.revokeObjectURL(url); });
            objectUrls = [];
            while (previews.firstChild) { previews.removeChild(previews.firstChild); }

            var files = Array.prototype.slice.call(photoInput.files || []);
            var max = parseInt(photoInput.getAttribute('data-max'), 10) || 5;
            var existing = parseInt(photoInput.getAttribute('data-existing'), 10) || 0;
            var removing = document.querySelectorAll('input[name="remove_photo_ids[]"]:checked').length;
            var total = existing - removing + files.length;

            files.forEach(function (file, index) {
                var item = document.createElement('li');
                item.className = 'listing-photo-grid__item';
                var caption = document.createElement('span');
                caption.className = 't-caption';
                caption.textContent = (index + 1) + '. ' + file.name;
                if (/^image\/(jpeg|png)$/.test(file.type)) {
                    var url = URL.createObjectURL(file);
                    objectUrls.push(url);
                    var img = document.createElement('img');
                    img.src = url;
                    img.alt = '';
                    item.appendChild(img);
                }
                item.appendChild(caption);
                previews.appendChild(item);
            });
            previews.hidden = files.length === 0;

            var status = files.length + ' / ' + max;
            if (total > max) { status += ' — ' + (document.documentElement.lang === 'bn' ? 'সর্বোচ্চ ' + max + 'টি ছবি দেওয়া যায়।' : 'You can have at most ' + max + ' photos.'); }
            photoStatus.textContent = files.length ? status : '';
        });
    }

    // 4. Submit once
    document.querySelectorAll('[data-listing-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('[data-submit-once]');
            if (!button) { return; }
            // Disable after the browser has collected the form data, so the request is still sent.
            window.setTimeout(function () {
                button.disabled = true;
                var label = button.querySelector('span');
                if (label && button.getAttribute('data-busy-label')) { label.textContent = button.getAttribute('data-busy-label'); }
            }, 0);
        });
    });

    // 5. Gallery
    document.querySelectorAll('[data-gallery]').forEach(function (gallery) {
        var main = gallery.querySelector('[data-gallery-main]');
        var caption = gallery.querySelector('[data-gallery-caption]');
        if (!main) { return; }
        gallery.addEventListener('click', function (event) {
            var link = event.target.closest('[data-gallery-thumb]');
            if (!link || event.ctrlKey || event.metaKey || event.shiftKey) { return; }
            event.preventDefault();
            main.src = link.getAttribute('data-src');
            main.width = parseInt(link.getAttribute('data-width'), 10);
            main.height = parseInt(link.getAttribute('data-height'), 10);
            main.alt = link.getAttribute('data-alt');
            if (caption) { caption.textContent = link.getAttribute('data-caption'); }
            gallery.querySelectorAll('[data-gallery-thumb]').forEach(function (other) { other.removeAttribute('aria-current'); });
            link.setAttribute('aria-current', 'true');
        });
    });

    // 6. "Coming soon" message
    var toast;
    var toastTimer;
    function showToast(message) {
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'planned-toast';
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            toast.hidden = true;
            toast.innerHTML = '<svg class="icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><p></p><button type="button"></button>';
            var close = toast.querySelector('button');
            close.textContent = '×';
            close.setAttribute('aria-label', document.documentElement.lang === 'bn' ? 'বন্ধ করুন' : 'Close');
            close.addEventListener('click', function () { toast.hidden = true; });
            document.body.appendChild(toast);
        }
        toast.querySelector('p').textContent = message;
        toast.hidden = false;
        window.clearTimeout(toastTimer);
        toastTimer = window.setTimeout(function () { toast.hidden = true; }, 6000);
    }

    document.addEventListener('submit', function (event) {
        var message = event.target.getAttribute('data-planned');
        if (message) {
            event.preventDefault();
            showToast(message);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && toast && !toast.hidden) { toast.hidden = true; }
    });
})();
