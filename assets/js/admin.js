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
        var pickers = document.querySelectorAll('.epictr-date-picker');
        if (!pickers.length) return;

        pickers.forEach(function (picker) {
            var trigger = picker.querySelector('.epictr-date-picker__trigger');
            var dropdown = picker.querySelector('.epictr-date-picker__dropdown');
            var presetBtns = picker.querySelectorAll('.epictr-date-picker__preset');
            var customSection = picker.querySelector('.epictr-date-picker__custom');
            var customToggle = picker.querySelector('[data-preset="custom"]');
            var applyBtn = picker.querySelector('.epictr-date-picker__apply');
            var inputFrom = picker.querySelector('input[name="date_from"]');
            var inputTo = picker.querySelector('input[name="date_to"]');
            var customFrom = picker.querySelector('.epictr-date-picker__custom-from');
            var customTo = picker.querySelector('.epictr-date-picker__custom-to');

            // Set trigger label
            var triggerLabel = trigger.querySelector('.epictr-date-picker__label');
            triggerLabel.textContent = buildTriggerLabel(inputFrom.value, inputTo.value);

            // Mark active preset
            var active = findActivePreset(inputFrom.value, inputTo.value);
            presetBtns.forEach(function (btn) {
                var check = btn.querySelector('.epictr-date-picker__check');
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
                        var check = btn.querySelector('.epictr-date-picker__check');
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

    // ── Chart ──────────────────────────────────────────────────────────

    var COLORS = {
        visits:  { line: '#2271b1', fill: 'rgba(34,113,177,0.08)' },
        unique:  { line: '#00a32a', fill: 'rgba(0,163,42,0.08)' }
    };

    function shortDate(str) {
        var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        var d = new Date(str + 'T00:00:00');
        return months[d.getMonth()] + ' ' + d.getDate();
    }

    function niceMax(v) {
        if (v <= 0) return 5;
        var magnitude = Math.pow(10, Math.floor(Math.log10(v)));
        var residual = v / magnitude;
        if (residual <= 1) return magnitude;
        if (residual <= 2) return 2 * magnitude;
        if (residual <= 5) return 5 * magnitude;
        return 10 * magnitude;
    }

    function drawChart(container) {
        var data = JSON.parse(container.dataset.chart);
        if (!data.length) return;

        var canvas = container.querySelector('canvas');
        var dpr = window.devicePixelRatio || 1;

        // Legend
        var legend = document.createElement('div');
        legend.className = 'epictr-chart__legend';
        legend.innerHTML =
            '<span class="epictr-chart__legend-item"><span class="epictr-chart__legend-swatch" style="background:' + COLORS.visits.line + '"></span>Total Visits</span>' +
            '<span class="epictr-chart__legend-item"><span class="epictr-chart__legend-swatch" style="background:' + COLORS.unique.line + '"></span>Unique Visitors</span>';
        container.insertBefore(legend, canvas);

        // Tooltip
        var tooltip = document.createElement('div');
        tooltip.className = 'epictr-chart__tooltip';
        container.appendChild(tooltip);

        function render() {
            var rect = canvas.parentElement.getBoundingClientRect();
            var w = canvas.clientWidth;
            var h = canvas.clientHeight;
            canvas.width = w * dpr;
            canvas.height = h * dpr;
            var ctx = canvas.getContext('2d');
            ctx.scale(dpr, dpr);

            var pad = { top: 10, right: 16, bottom: 32, left: 44 };
            var cw = w - pad.left - pad.right;
            var ch = h - pad.top - pad.bottom;

            var visits = data.map(function (d) { return +d.total_visits; });
            var unique = data.map(function (d) { return +d.unique_visitors; });
            var maxVal = niceMax(Math.max.apply(null, visits.concat(unique)));
            var steps = 4;

            // Grid lines + Y labels
            ctx.textAlign = 'right';
            ctx.textBaseline = 'middle';
            ctx.font = '11px -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
            for (var s = 0; s <= steps; s++) {
                var yVal = (maxVal / steps) * s;
                var y = pad.top + ch - (ch * (yVal / maxVal));
                ctx.strokeStyle = '#f0f0f1';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(pad.left, y);
                ctx.lineTo(w - pad.right, y);
                ctx.stroke();
                ctx.fillStyle = '#787c82';
                ctx.fillText(Math.round(yVal).toString(), pad.left - 8, y);
            }

            // X labels
            var n = data.length;
            var maxLabels = Math.floor(cw / 60);
            var labelStep = Math.max(1, Math.ceil(n / maxLabels));
            ctx.textAlign = 'center';
            ctx.textBaseline = 'top';
            for (var i = 0; i < n; i++) {
                if (i % labelStep !== 0 && i !== n - 1) continue;
                var x = pad.left + (cw * i / (n - 1 || 1));
                ctx.fillStyle = '#787c82';
                ctx.fillText(shortDate(data[i].visit_date), x, pad.top + ch + 10);
            }

            function xPos(i) { return pad.left + (cw * i / (n - 1 || 1)); }
            function yPos(v) { return pad.top + ch - (ch * (v / maxVal)); }

            // Draw series (fill + line)
            function drawSeries(values, color) {
                // Fill
                ctx.beginPath();
                ctx.moveTo(xPos(0), yPos(0));
                for (var i = 0; i < n; i++) ctx.lineTo(xPos(i), yPos(values[i]));
                ctx.lineTo(xPos(n - 1), yPos(0));
                ctx.closePath();
                ctx.fillStyle = color.fill;
                ctx.fill();

                // Line
                ctx.beginPath();
                for (var i = 0; i < n; i++) {
                    if (i === 0) ctx.moveTo(xPos(i), yPos(values[i]));
                    else ctx.lineTo(xPos(i), yPos(values[i]));
                }
                ctx.strokeStyle = color.line;
                ctx.lineWidth = 2;
                ctx.lineJoin = 'round';
                ctx.stroke();
            }

            drawSeries(visits, COLORS.visits);
            drawSeries(unique, COLORS.unique);

            // Dots at data points (only when few points)
            if (n <= 31) {
                [{ vals: visits, c: COLORS.visits.line }, { vals: unique, c: COLORS.unique.line }].forEach(function (s) {
                    for (var i = 0; i < n; i++) {
                        ctx.beginPath();
                        ctx.arc(xPos(i), yPos(s.vals[i]), 3, 0, Math.PI * 2);
                        ctx.fillStyle = '#fff';
                        ctx.fill();
                        ctx.strokeStyle = s.c;
                        ctx.lineWidth = 2;
                        ctx.stroke();
                    }
                });
            }

            // Store geometry for hover
            container._chartMeta = { pad: pad, cw: cw, ch: ch, n: n, maxVal: maxVal, xPos: xPos, yPos: yPos, visits: visits, unique: unique };
        }

        render();

        var resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(render, 100);
        });

        // Hover tooltip
        canvas.addEventListener('mousemove', function (e) {
            var meta = container._chartMeta;
            if (!meta) return;
            var canvasRect = canvas.getBoundingClientRect();
            var mx = e.clientX - canvasRect.left;
            var idx = Math.round((mx - meta.pad.left) / (meta.cw / (meta.n - 1 || 1)));
            if (idx < 0 || idx >= meta.n) {
                tooltip.classList.remove('is-visible');
                return;
            }
            var d = data[idx];
            tooltip.innerHTML =
                '<strong>' + shortDate(d.visit_date) + '</strong><br>' +
                'Visits: ' + d.total_visits + '<br>' +
                'Unique: ' + d.unique_visitors;
            tooltip.classList.add('is-visible');

            var tx = meta.xPos(idx);
            var ty = meta.yPos(meta.visits[idx]);
            var tw = tooltip.offsetWidth;
            var left = tx - tw / 2;
            if (left < 0) left = 0;
            if (left + tw > canvas.clientWidth) left = canvas.clientWidth - tw;
            tooltip.style.left = left + 'px';
            tooltip.style.top = (ty - tooltip.offsetHeight - 10) + 'px';
        });

        canvas.addEventListener('mouseleave', function () {
            tooltip.classList.remove('is-visible');
        });
    }

    function initCharts() {
        document.querySelectorAll('.epictr-chart').forEach(drawChart);
    }

    document.addEventListener('DOMContentLoaded', function () {
        init();
        initCharts();
    });
})();
