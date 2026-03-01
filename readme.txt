=== Epic Tracking ===
Contributors: epicwpsolutions
Tags: analytics, tracking, events, statistics, privacy
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, privacy-first visit and event tracking built directly into WordPress. No external services required.

== Description ==

Epic Tracking gives you actionable insights about your WordPress site without relying on third-party analytics services. All data stays in your own database.

**Features:**

* **Visit tracking** — Automatically logs page views with referrer, device, browser, and OS data.
* **Event tracking** — Define custom click events on any element using CSS selectors.
* **Visual mode** — Point-and-click interface to set up event tracking without writing code.
* **Dashboard** — View visit and event statistics with date filtering and per-page breakdowns.
* **Bot filtering** — Automatically excludes known bots and crawlers from your data.
* **User-agent parsing** — Breaks down visits by device type, browser, and operating system.
* **Role exclusion** — Exclude logged-in users by role from being tracked.
* **Privacy-first** — No cookies, no external requests, no personal data collection. Visitors are identified by a non-reversible hash.

== Installation ==

1. Upload the `epic-tracking` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Tracking** in the admin sidebar to view your dashboard and configure settings.

== Frequently Asked Questions ==

= Does this plugin send data to external services? =

No. All tracking data is stored in your WordPress database. No external requests are made.

= Will this slow down my site? =

Epic Tracking is designed to be lightweight. Visit logging happens via an asynchronous AJAX request after the page has loaded, so it does not affect page render time.

= How are visitors identified? =

Visitors are assigned a non-reversible hashed identifier based on their IP address and user agent. No cookies are used and no personal data is stored.

= What happens when I uninstall the plugin? =

All plugin database tables and options are removed when you delete the plugin through the WordPress admin. Deactivating the plugin does not remove data.

= Can I exclude certain user roles from tracking? =

Yes. Go to **Tracking > Settings** and select which roles should be excluded from visit and event tracking.

= How does bot filtering work? =

The plugin maintains a list of known bot and crawler user-agent patterns. Requests matching these patterns are automatically excluded from tracking data.

== Screenshots ==

1. Dashboard overview with visit and event statistics.
2. Per-page visit breakdown with device, browser, and OS charts.
3. Visual mode for point-and-click event setup.
4. Event tracking configuration and statistics.
5. Plugin settings page.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Epic Tracking.
