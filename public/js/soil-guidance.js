/*
 | Soil-based fertilizer guidance (F15). Progressive enhancement only: every form works without this file.
 | 1. After a failed submit, the error message at the top gets focus.
 | 2. A preview of the chosen photo (set with a local object URL; nothing is uploaded early).
 | 3. After "Get guidance" or "Try again" is pressed: the button is disabled, so a second click cannot send the
 |    form twice, and a "please wait" line appears. The server has its own protection as well.
 */
(function () {
    'use strict';

    var summary = document.querySelector('[data-error-summary]');
    if (summary) { summary.focus(); }

    var photo = document.querySelector('[data-photo-input]');
    var preview = document.querySelector('[data-photo-preview]');
    if (photo && preview && window.URL && URL.createObjectURL) {
        photo.addEventListener('change', function () {
            var file = photo.files && photo.files[0];
            if (preview.src) { URL.revokeObjectURL(preview.src); }
            preview.hidden = !(file && /^image\/(jpeg|png)$/.test(file.type));
            if (!preview.hidden) { preview.src = URL.createObjectURL(file); }
        });
    }

    document.querySelectorAll('[data-soil-form]').forEach(function (form) {
        var button = form.querySelector('[data-submit-once]');
        var note = form.querySelector('[data-processing-note]');

        form.addEventListener('submit', function () {
            // Wait one tick, so the browser still sends the form before the button is disabled.
            window.setTimeout(function () {
                if (button) { button.disabled = true; }
                if (note) { note.hidden = false; }
            }, 0);
        });

        // Coming back with the browser's Back button: make the form usable again.
        window.addEventListener('pageshow', function () {
            if (button) { button.disabled = false; }
            if (note) { note.hidden = true; }
        });
    });
})();
