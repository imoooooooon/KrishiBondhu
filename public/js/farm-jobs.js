/*
 | Farm jobs (F20). Small helpers; the pages still work without JavaScript.
 | 1. Job form: show the "Unit" field only when the wage basis is "unit".
 | 2. Close button: ask for confirmation before sending a form marked with data-confirm.
 */
(function () {
    'use strict';

    var basis = document.querySelector('[data-wage-basis]');
    var unitField = document.querySelector('[data-unit-label-field]');

    if (basis && unitField) {
        var showOrHideUnit = function () {
            unitField.hidden = basis.value !== 'unit';
        };
        basis.addEventListener('change', showOrHideUnit);
        showOrHideUnit();
    }

    document.addEventListener('submit', function (event) {
        var message = event.target.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
})();
