/**
 * Seamless background auto-update (admin + client portals)
 * Justice Law Firm
 *
 * The portal polls the current page in the background every few seconds and
 * swaps the markup of the dynamic parts (stat cards, notification bell and
 * data tables) in place. Nothing is reloaded, so there is no scroll jump, no
 * spinner and no page flicker - only the changed markup is touched.
 *
 * Requests are paused while the tab is hidden so a background tab never
 * hammers the server.
 *
 * Include this file on any portal page that renders live data:
 *   <script src="assets/js/auto-update.js"></script>
 */
(function () {
    'use strict';

    var POLL_INTERVAL = 3000;

    /**
     * Parts of the page that are refreshed when the server sends new markup.
     * - selector        : which elements to look for
     * - removeIfMissing : drop the element when the fresh page no longer has it
     *                     (used for the notification badge, which disappears
     *                     once every notification has been read)
     */
    var TARGETS = [
        { selector: '[data-auto-update]' },
        { selector: '.stats-grid' },
        { selector: '.stats-cards-container' },
        { selector: '.notification-badge', removeIfMissing: true },
        { selector: '.notification-list' },
        { selector: '.sidebar-nav .badge', removeIfMissing: true },
        { selector: '.table-container' }
    ];

    var timer = null;
    var inFlight = false;
    var paused = false;

    /** True when polling should be skipped (hidden tab or manual pause). */
    function shouldSkip() {
        return paused || document.hidden;
    }

    /** Collect the target elements of a document, grouped per selector. */
    function collectGroups(doc) {
        var groups = [];

        TARGETS.forEach(function (target) {
            var nodes = doc.querySelectorAll(target.selector);
            groups.push({
                target: target,
                nodes: Array.prototype.slice.call(nodes)
            });
        });

        return groups;
    }

    /**
     * Copy the fresh markup into the live page.
     * Only the containers whose content actually changed are touched.
     */
    function refresh(doc) {
        var liveGroups = collectGroups(document);
        var freshGroups = collectGroups(doc);

        liveGroups.forEach(function (liveGroup, index) {
            var freshGroup = freshGroups[index];
            if (!freshGroup) return;

            liveGroup.nodes.forEach(function (current, position) {
                var fresh = freshGroup.nodes[position];

                if (!fresh) {
                    // The element is gone on the server (e.g. no unread badge)
                    if (liveGroup.target.removeIfMissing && current.parentNode) {
                        current.parentNode.removeChild(current);
                    }
                    return;
                }

                var newHtml = fresh.innerHTML;

                // Nothing changed - leave the DOM completely alone
                if (newHtml === current.innerHTML) return;

                // The visitor is typing or has a control focused inside
                if (current.contains(document.activeElement)) return;

                var scrollLeft = current.scrollLeft;
                var scrollTop = current.scrollTop;
                var pageScroll = window.pageYOffset;

                current.innerHTML = newHtml;

                // Keep the reader exactly where they were
                current.scrollLeft = scrollLeft;
                current.scrollTop = scrollTop;
                if (window.pageYOffset !== pageScroll) {
                    window.scrollTo(0, pageScroll);
                }
            });
        });
    }

    /** Fetch the current page and refresh the dynamic parts in place. */
    function poll() {
        if (shouldSkip() || inFlight) return;

        inFlight = true;

        var url;
        try {
            url = new URL(window.location.href);
        } catch (e) {
            inFlight = false;
            return;
        }

        // Bust the cache so the server really re-renders the page
        url.searchParams.set('_auto', Date.now());

        fetch(url.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store'
        })
        .then(function (response) {
            // Signed out or session replaced - fall back to a normal reload
            if (response.redirected) {
                window.location.reload();
                return null;
            }
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.text();
        })
        .then(function (html) {
            if (!html) return;
            var doc = new DOMParser().parseFromString(html, 'text/html');
            refresh(doc);
        })
        .catch(function (error) {
            console.error('Auto-update error:', error);
        })
        .then(function () {
            inFlight = false;
        });
    }

    /** Start the polling loop (only when the page has something to refresh). */
    function start() {
        if (timer) return;

        var hasTargets = collectGroups(document).some(function (group) {
            return group.nodes.length > 0;
        });

        if (!hasTargets) return;

        timer = window.setInterval(poll, POLL_INTERVAL);

        // Catch up straight away when the visitor comes back to the tab
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) poll();
        });
    }

    /** Small public handle so a page can pause or trigger a refresh. */
    window.autoUpdate = {
        interval: POLL_INTERVAL,
        refresh: poll,
        pause: function () {
            paused = true;
        },
        resume: function () {
            paused = false;
            poll();
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
