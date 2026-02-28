(function () {
    'use strict';

    if (typeof eptVisualConfig === 'undefined') return;

    var config = eptVisualConfig;
    var iframe = document.getElementById('ept-visual-iframe');
    var sidebar = document.getElementById('ept-visual-sidebar');
    var eventsContainer = document.getElementById('ept-events-container');
    var selectBtn = document.getElementById('ept-select-element');
    var eventForm = document.getElementById('ept-event-form');
    var selectorInput = document.getElementById('ept-selector');
    var referenceInput = document.getElementById('ept-reference-name');
    var eventTagInput = document.getElementById('ept-event-tag');
    var saveBtn = document.getElementById('ept-save-event');
    var cancelBtn = document.getElementById('ept-cancel-event');

    var isSelecting = false;
    var highlightOverlay = null;

    // --- Set iframe src ---
    iframe.src = config.targetUrl;

    // --- Load events ---
    function loadEvents() {
        var formData = new FormData();
        formData.append('action', 'ept_get_page_events');
        formData.append('nonce', config.nonce);
        formData.append('page_url', config.pageUrl);

        fetch(config.ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    renderEvents(res.data);
                }
            });
    }

    function renderEvents(events) {
        if (!events || events.length === 0) {
            eventsContainer.innerHTML = '<p class="ept-no-events">No events configured for this page.</p>';
            return;
        }

        var html = '';
        events.forEach(function (evt) {
            html += '<div class="ept-event-item" data-event-id="' + evt.id + '">'
                + '<div class="ept-event-item-header">'
                + '<span class="ept-event-item-name">' + escHtml(evt.reference_name) + '</span>'
                + '<span class="ept-event-item-tag">' + escHtml(evt.event_tag) + '</span>'
                + '</div>'
                + '<div class="ept-event-item-selector">' + escHtml(evt.selector) + '</div>'
                + '<div class="ept-event-item-actions">'
                + '<button class="button ept-highlight-event" data-selector="' + escAttr(evt.selector) + '">Highlight</button> '
                + '<button class="button ept-delete-event" data-id="' + evt.id + '">Delete</button>'
                + '</div>'
                + '</div>';
        });
        eventsContainer.innerHTML = html;

        // Bind delete buttons
        eventsContainer.querySelectorAll('.ept-delete-event').forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteEvent(parseInt(this.getAttribute('data-id')));
            });
        });

        // Bind highlight buttons
        eventsContainer.querySelectorAll('.ept-highlight-event').forEach(function (btn) {
            btn.addEventListener('click', function () {
                highlightInIframe(this.getAttribute('data-selector'));
            });
        });
    }

    // --- Element selection ---
    selectBtn.addEventListener('click', function () {
        if (isSelecting) {
            stopSelecting();
        } else {
            startSelecting();
        }
    });

    function startSelecting() {
        isSelecting = true;
        selectBtn.textContent = 'Cancel Selection';
        document.body.classList.add('ept-selecting-active');

        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

        // Create overlay element in iframe
        highlightOverlay = iframeDoc.createElement('div');
        highlightOverlay.id = 'ept-highlight-overlay';
        highlightOverlay.style.cssText = 'position:absolute;background:rgba(0,124,186,0.15);border:2px solid #007cba;pointer-events:none;z-index:999999;transition:all 0.1s ease;display:none;';
        iframeDoc.body.appendChild(highlightOverlay);

        iframeDoc.addEventListener('mouseover', onIframeMouseOver);
        iframeDoc.addEventListener('click', onIframeClick);
    }

    function stopSelecting() {
        isSelecting = false;
        selectBtn.textContent = 'Select Element';
        document.body.classList.remove('ept-selecting-active');

        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        iframeDoc.removeEventListener('mouseover', onIframeMouseOver);
        iframeDoc.removeEventListener('click', onIframeClick);

        if (highlightOverlay && highlightOverlay.parentNode) {
            highlightOverlay.parentNode.removeChild(highlightOverlay);
        }
        highlightOverlay = null;
    }

    function onIframeMouseOver(e) {
        if (!highlightOverlay) return;
        var rect = e.target.getBoundingClientRect();
        var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
        var scrollX = iframeDoc.documentElement.scrollLeft || iframeDoc.body.scrollLeft;
        var scrollY = iframeDoc.documentElement.scrollTop || iframeDoc.body.scrollTop;

        highlightOverlay.style.display = 'block';
        highlightOverlay.style.top = (rect.top + scrollY) + 'px';
        highlightOverlay.style.left = (rect.left + scrollX) + 'px';
        highlightOverlay.style.width = rect.width + 'px';
        highlightOverlay.style.height = rect.height + 'px';
    }

    function onIframeClick(e) {
        e.preventDefault();
        e.stopPropagation();

        var selector = generateSelector(e.target);
        selectorInput.value = selector;
        referenceInput.value = '';
        eventTagInput.value = '';
        eventForm.style.display = 'block';
        referenceInput.focus();

        stopSelecting();
    }

    // --- Selector generation ---
    function generateSelector(el) {
        // Priority 1: Elementor data-id
        if (el.getAttribute('data-id')) {
            return '[data-id="' + el.getAttribute('data-id') + '"]';
        }

        // Priority 2: HTML id
        if (el.id && !el.id.match(/^\d/)) {
            return '#' + CSS.escape(el.id);
        }

        // Priority 3: Build unique selector
        return buildUniqueSelector(el);
    }

    function buildUniqueSelector(el) {
        var parts = [];
        var current = el;

        while (current && current !== current.ownerDocument.body) {
            var tag = current.tagName.toLowerCase();

            // Check for data-id (Elementor)
            if (current.getAttribute('data-id')) {
                parts.unshift('[data-id="' + current.getAttribute('data-id') + '"]');
                break;
            }

            // Check for id
            if (current.id && !current.id.match(/^\d/)) {
                parts.unshift('#' + CSS.escape(current.id));
                break;
            }

            // Use nth-child for disambiguation
            var parent = current.parentElement;
            if (parent) {
                var siblings = Array.from(parent.children).filter(function (s) {
                    return s.tagName === current.tagName;
                });
                if (siblings.length > 1) {
                    var index = siblings.indexOf(current) + 1;
                    tag += ':nth-of-type(' + index + ')';
                }
            }

            parts.unshift(tag);
            current = current.parentElement;
        }

        return parts.join(' > ');
    }

    // --- Save event ---
    saveBtn.addEventListener('click', function () {
        var referenceName = referenceInput.value.trim();
        var eventTag = eventTagInput.value.trim();
        var selector = selectorInput.value.trim();

        if (!referenceName || !eventTag || !selector) {
            alert('Please fill in all fields.');
            return;
        }

        var formData = new FormData();
        formData.append('action', 'ept_save_event');
        formData.append('nonce', config.nonce);
        formData.append('page_url', config.pageUrl);
        formData.append('selector', selector);
        formData.append('reference_name', referenceName);
        formData.append('event_tag', eventTag);

        fetch(config.ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    eventForm.style.display = 'none';
                    loadEvents();
                }
            });
    });

    cancelBtn.addEventListener('click', function () {
        eventForm.style.display = 'none';
    });

    // --- Delete event ---
    function deleteEvent(id) {
        if (!confirm('Delete this event?')) return;

        var formData = new FormData();
        formData.append('action', 'ept_delete_event');
        formData.append('nonce', config.nonce);
        formData.append('id', id);

        fetch(config.ajaxUrl, { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res.success) {
                    loadEvents();
                }
            });
    }

    // --- Highlight element in iframe ---
    function highlightInIframe(selector) {
        try {
            var iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            var el = iframeDoc.querySelector(selector);
            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                el.style.outline = '3px solid #007cba';
                el.style.outlineOffset = '2px';
                setTimeout(function () {
                    el.style.outline = '';
                    el.style.outlineOffset = '';
                }, 2000);
            }
        } catch (e) {
            // Cross-origin or selector error
        }
    }

    // --- Helpers ---
    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function escAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    // --- Init ---
    iframe.addEventListener('load', function () {
        loadEvents();
    });
})();
