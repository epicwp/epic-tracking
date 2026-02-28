(function () {
    'use strict';

    if (typeof eptVisualConfig === 'undefined') return;

    var config = eptVisualConfig;
    var isSelecting = false;
    var highlightOverlay = null;
    var sidebar = null;
    var eventsContainer = null;
    var eventForm = null;
    var selectorInput = null;
    var referenceInput = null;
    var eventTagInput = null;
    var selectBtn = null;

    // --- Build sidebar UI ---
    function buildSidebar() {
        sidebar = document.createElement('div');
        sidebar.id = 'ept-visual-sidebar';
        sidebar.innerHTML =
            '<div class="ept-sidebar-header">'
            + '<h2>Epic Tracking</h2>'
            + '<a href="' + escAttr(config.exitUrl) + '" class="ept-close-btn">&times;</a>'
            + '</div>'
            + '<div class="ept-sidebar-content">'
            + '<div id="ept-events-list">'
            + '<h3>Configured Events</h3>'
            + '<div id="ept-events-container">'
            + '<p class="ept-loading">Loading events...</p>'
            + '</div>'
            + '</div>'
            + '<hr>'
            + '<button id="ept-select-element" class="button button-primary">Select Element</button>'
            + '<div id="ept-event-form" style="display:none;">'
            + '<h3>Configure Event</h3>'
            + '<div class="ept-form-group">'
            + '<label for="ept-selector">Selector</label>'
            + '<input type="text" id="ept-selector" class="regular-text" readonly>'
            + '</div>'
            + '<div class="ept-form-group">'
            + '<label for="ept-reference-name">Reference Name</label>'
            + '<input type="text" id="ept-reference-name" class="regular-text" placeholder="e.g. CTA Button Hero">'
            + '</div>'
            + '<div class="ept-form-group">'
            + '<label for="ept-event-tag">Event Tag</label>'
            + '<input type="text" id="ept-event-tag" class="regular-text" placeholder="e.g. cta_hero_click">'
            + '</div>'
            + '<div class="ept-form-actions">'
            + '<button id="ept-save-event" class="button button-primary">Save Event</button>'
            + '<button id="ept-cancel-event" class="button">Cancel</button>'
            + '</div>'
            + '</div>'
            + '</div>';

        document.body.appendChild(sidebar);
        document.body.classList.add('ept-visual-mode-active');

        // Cache references
        eventsContainer = document.getElementById('ept-events-container');
        eventForm = document.getElementById('ept-event-form');
        selectorInput = document.getElementById('ept-selector');
        referenceInput = document.getElementById('ept-reference-name');
        eventTagInput = document.getElementById('ept-event-tag');
        selectBtn = document.getElementById('ept-select-element');

        // Bind sidebar buttons
        selectBtn.addEventListener('click', function () {
            if (isSelecting) {
                stopSelecting();
            } else {
                startSelecting();
            }
        });

        document.getElementById('ept-save-event').addEventListener('click', saveEvent);
        document.getElementById('ept-cancel-event').addEventListener('click', function () {
            eventForm.style.display = 'none';
        });

        // Create highlight overlay for hover effect
        highlightOverlay = document.createElement('div');
        highlightOverlay.id = 'ept-highlight-overlay';
        highlightOverlay.style.display = 'none';
        document.body.appendChild(highlightOverlay);
    }

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
                highlightElement(this.getAttribute('data-selector'));
            });
        });
    }

    // --- Element selection ---
    function startSelecting() {
        isSelecting = true;
        selectBtn.textContent = 'Cancel Selection';
        document.body.classList.add('ept-selecting-active');

        document.addEventListener('mouseover', onMouseOver, true);
        document.addEventListener('click', onClick, true);
    }

    function stopSelecting() {
        isSelecting = false;
        selectBtn.textContent = 'Select Element';
        document.body.classList.remove('ept-selecting-active');
        highlightOverlay.style.display = 'none';

        document.removeEventListener('mouseover', onMouseOver, true);
        document.removeEventListener('click', onClick, true);
    }

    function onMouseOver(e) {
        // Ignore events on sidebar and overlay
        if (isSidebarElement(e.target)) return;

        var rect = e.target.getBoundingClientRect();
        highlightOverlay.style.display = 'block';
        highlightOverlay.style.top = (rect.top + window.scrollY) + 'px';
        highlightOverlay.style.left = (rect.left + window.scrollX) + 'px';
        highlightOverlay.style.width = rect.width + 'px';
        highlightOverlay.style.height = rect.height + 'px';
    }

    function onClick(e) {
        // Ignore clicks on sidebar and overlay
        if (isSidebarElement(e.target)) return;

        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        var selector = generateSelector(e.target);
        selectorInput.value = selector;
        referenceInput.value = '';
        eventTagInput.value = '';
        eventForm.style.display = 'block';
        referenceInput.focus();

        stopSelecting();

        return false;
    }

    function isSidebarElement(el) {
        return el.closest('#ept-visual-sidebar') !== null
            || el.closest('#ept-highlight-overlay') !== null
            || el.closest('#wpadminbar') !== null;
    }

    // --- Selector generation ---
    function generateSelector(el) {
        // Priority 1: Elementor data-id
        if (el.getAttribute('data-id')) {
            return '[data-id="' + el.getAttribute('data-id') + '"]';
        }

        // Priority 2: HTML id (skip WP/Elementor internal IDs)
        if (el.id && !el.id.match(/^\d/) && !el.id.match(/^(wp-|elementor-)/)) {
            return '#' + CSS.escape(el.id);
        }

        // Priority 3: Build unique selector
        return buildUniqueSelector(el);
    }

    function buildUniqueSelector(el) {
        var parts = [];
        var current = el;

        while (current && current !== document.body) {
            var tag = current.tagName.toLowerCase();

            // Check for data-id (Elementor)
            if (current.getAttribute('data-id')) {
                parts.unshift('[data-id="' + current.getAttribute('data-id') + '"]');
                break;
            }

            // Check for id
            if (current.id && !current.id.match(/^\d/) && !current.id.match(/^(wp-|elementor-)/)) {
                parts.unshift('#' + CSS.escape(current.id));
                break;
            }

            // Use nth-of-type for disambiguation
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
    function saveEvent() {
        var referenceName = referenceInput.value.trim();
        var eventTag = eventTagInput.value.trim();
        var selector = selectorInput.value.trim();

        if (!referenceName || !eventTag || !selector) {
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
    }

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

    // --- Highlight element ---
    function highlightElement(selector) {
        try {
            var el = document.querySelector(selector);
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
            // Invalid selector
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
    buildSidebar();
    loadEvents();
})();
