(function () {
    'use strict';

    if (typeof eptConfig === 'undefined') return;

    var COOKIE_NAME = 'ept_visitor_id';
    var COOKIE_DAYS = 365;

    function getVisitorId() {
        var match = document.cookie.match(new RegExp('(^| )' + COOKIE_NAME + '=([^;]+)'));
        if (match) return match[2];

        var id = generateUUID();
        var expires = new Date(Date.now() + COOKIE_DAYS * 864e5).toUTCString();
        document.cookie = COOKIE_NAME + '=' + id + '; expires=' + expires + '; path=/; SameSite=Lax';
        return id;
    }

    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = (Math.random() * 16) | 0;
            var v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    function sendBeacon(action, data) {
        var formData = new FormData();
        formData.append('action', action);
        for (var key in data) {
            formData.append(key, data[key]);
        }

        if (navigator.sendBeacon) {
            navigator.sendBeacon(eptConfig.ajaxUrl, formData);
        } else {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', eptConfig.ajaxUrl, true);
            xhr.send(formData);
        }
    }

    // --- Init ---
    var visitorId = getVisitorId();

    // Track page visit
    sendBeacon('ept_track_visit', {
        visitor_id: visitorId,
        page_url: eptConfig.pageUrl,
        referrer: document.referrer || '',
    });

    // Bind configured events
    if (eptConfig.events && eptConfig.events.length > 0) {
        // Set data-ept-id on matched elements
        eptConfig.events.forEach(function (evt) {
            var el = document.querySelector(evt.selector);
            if (el) {
                el.setAttribute('data-ept-id', evt.id);
            }
        });

        // Single delegated click listener
        document.addEventListener('click', function (e) {
            var target = e.target.closest('[data-ept-id]');
            if (!target) return;

            sendBeacon('ept_track_event', {
                event_id: target.getAttribute('data-ept-id'),
                visitor_id: visitorId,
                page_url: eptConfig.pageUrl,
            });
        });
    }
})();
