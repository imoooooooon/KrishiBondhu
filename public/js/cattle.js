/*
 | Digital cattle profiles (F6). Progressive enhancement only: every form and link works without this file.
 | 1. Error summary gets focus after a failed submit.
 | 2. Age: show only the inputs for the chosen option (exact / estimated / unknown), and on the edit page only
 |    when "Replace age information" is chosen. Without JS all inputs show, and the server ignores the others.
 | 3. Breed and health-date inputs hide when "Not known" is chosen.
 | 4. Photo preview, submit-once, and a "Copy ID" button. Text is always set with textContent.
 */
(function () {
    'use strict';

    var summary = document.querySelector('[data-error-summary]');
    if (summary) { summary.focus(); }

    function checked(name) {
        var input = document.querySelector('input[name="' + name + '"]:checked');
        return input ? input.value : null;
    }

    function sync() {
        var editor = document.querySelector('[data-age-editor]');
        if (editor) {
            var update = checked('age_update');
            editor.hidden = update === 'keep';
            var basis = checked('age_basis');
            document.querySelectorAll('[data-age-group]').forEach(function (group) {
                group.hidden = group.getAttribute('data-age-group') !== basis;
            });
        }
        var breed = document.querySelector('[data-breed-field]');
        if (breed) { breed.hidden = checked('breed_known') === 'no'; }
        document.querySelectorAll('[data-date-field]').forEach(function (field) {
            var choice = field.previousElementSibling && field.previousElementSibling.querySelector('input[type="radio"]:checked');
            field.hidden = !!choice && choice.value === 'no';
        });
    }

    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-age-basis], [data-age-update], [data-breed-choice], [data-date-choice]')) { sync(); }
    });
    sync();

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

    document.querySelectorAll('[data-cattle-form]').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('[data-submit-once]');
            if (button) { window.setTimeout(function () { button.disabled = true; }, 0); }
        });
    });

    document.querySelectorAll('[data-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.getAttribute('data-copy'));
            if (!target || !navigator.clipboard) { return; }
            navigator.clipboard.writeText(target.textContent.trim()).then(function () {
                button.querySelector('span').textContent = button.getAttribute('data-copied');
            });
        });
    });
})();
