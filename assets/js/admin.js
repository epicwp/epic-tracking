(function () {
    'use strict';

    function formatDate(d) {
        var y = d.getFullYear();
        var m = String(d.getMonth() + 1).padStart(2, '0');
        var day = String(d.getDate()).padStart(2, '0');
        return y + '-' + m + '-' + day;
    }

    function formatLabel(d) {
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
    }

    function getPresets() {
        var today = new Date();
        today.setHours(0, 0, 0, 0);

        var yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);

        var last7 = new Date(today);
        last7.setDate(last7.getDate() - 6);

        var last30 = new Date(today);
        last30.setDate(last30.getDate() - 29);

        var thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);

        var lastMonthStart = new Date(today.getFullYear(), today.getMonth() - 1, 1);
        var lastMonthEnd = new Date(today.getFullYear(), today.getMonth(), 0);

        return [
            { label: 'Today', from: formatDate(today), to: formatDate(today) },
            { label: 'Yesterday', from: formatDate(yesterday), to: formatDate(yesterday) },
            { label: 'Last 7 days', from: formatDate(last7), to: formatDate(today) },
            { label: 'Last 30 days', from: formatDate(last30), to: formatDate(today) },
            { label: 'This month', from: formatDate(thisMonthStart), to: formatDate(today) },
            { label: 'Last month', from: formatDate(lastMonthStart), to: formatDate(lastMonthEnd) }
        ];
    }

    function findActivePreset(dateFrom, dateTo) {
        var presets = getPresets();
        for (var i = 0; i < presets.length; i++) {
            if (presets[i].from === dateFrom && presets[i].to === dateTo) {
                return presets[i];
            }
        }
        return null;
    }

    function buildTriggerLabel(dateFrom, dateTo) {
        var preset = findActivePreset(dateFrom, dateTo);
        var from = new Date(dateFrom + 'T00:00:00');
        var to = new Date(dateTo + 'T00:00:00');
        var range = formatLabel(from) + ' \u2013 ' + formatLabel(to);

        if (preset) {
            return preset.label + ': ' + range;
        }
        return 'Custom: ' + range;
    }

    function init() {
        var pickers = document.querySelectorAll('.ept-date-picker');
        if (!pickers.length) return;

        pickers.forEach(function (picker) {
            var trigger = picker.querySelector('.ept-date-picker__trigger');
            var dropdown = picker.querySelector('.ept-date-picker__dropdown');
            var presetBtns = picker.querySelectorAll('.ept-date-picker__preset');
            var customSection = picker.querySelector('.ept-date-picker__custom');
            var customToggle = picker.querySelector('[data-preset="custom"]');
            var applyBtn = picker.querySelector('.ept-date-picker__apply');
            var inputFrom = picker.querySelector('input[name="date_from"]');
            var inputTo = picker.querySelector('input[name="date_to"]');
            var customFrom = picker.querySelector('.ept-date-picker__custom-from');
            var customTo = picker.querySelector('.ept-date-picker__custom-to');

            // Set trigger label
            var triggerLabel = trigger.querySelector('.ept-date-picker__label');
            triggerLabel.textContent = buildTriggerLabel(inputFrom.value, inputTo.value);

            // Mark active preset
            var active = findActivePreset(inputFrom.value, inputTo.value);
            presetBtns.forEach(function (btn) {
                var check = btn.querySelector('.ept-date-picker__check');
                if (active && btn.dataset.label === active.label) {
                    check.style.visibility = 'visible';
                } else {
                    check.style.visibility = 'hidden';
                }
            });

            // Toggle dropdown
            trigger.addEventListener('click', function (e) {
                e.stopPropagation();
                dropdown.classList.toggle('is-open');
            });

            // Preset clicks
            presetBtns.forEach(function (btn) {
                if (btn.dataset.preset === 'custom') return;
                btn.addEventListener('click', function () {
                    inputFrom.value = btn.dataset.from;
                    inputTo.value = btn.dataset.to;
                    picker.submit();
                });
            });

            // Custom range toggle
            if (customToggle) {
                customToggle.addEventListener('click', function () {
                    customSection.classList.toggle('is-visible');
                    // Remove checkmarks from presets
                    presetBtns.forEach(function (btn) {
                        var check = btn.querySelector('.ept-date-picker__check');
                        check.style.visibility = 'hidden';
                    });
                    // Focus first date input
                    if (customSection.classList.contains('is-visible')) {
                        customFrom.focus();
                    }
                });
            }

            // Apply custom range
            if (applyBtn) {
                applyBtn.addEventListener('click', function () {
                    if (!customFrom.value || !customTo.value) return;
                    if (customFrom.value > customTo.value) {
                        var tmp = customFrom.value;
                        customFrom.value = customTo.value;
                        customTo.value = tmp;
                    }
                    inputFrom.value = customFrom.value;
                    inputTo.value = customTo.value;
                    picker.submit();
                });
            }

            // Close on outside click
            document.addEventListener('click', function (e) {
                if (!picker.contains(e.target)) {
                    dropdown.classList.remove('is-open');
                }
            });

            // Close on Escape
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    dropdown.classList.remove('is-open');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
