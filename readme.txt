=== Epic Tracking ===
Contributors: epicwpsolutions
Tags: event tracking, click tracking, analytics, tracking, privacy
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight visit and event tracking for WordPress. Track page views, button clicks, form submissions, and more — all from your WordPress admin, with no external services.

== Description ==

Epic Tracking gives you actionable insights about your WordPress site without relying on third-party analytics services. All data stays in your own database.

Set up click tracking without writing a single line of code. Open the visual editor on any page, click the element you want to track, and save. No Google Tag Manager or external scripts needed.

**Features:**

* **Visual event editor** — Point-and-click setup for tracking clicks on any element. No code or tag managers needed.
* **Event analytics** — See which events fire most, with trigger counts and unique visitor stats.
* **Visit tracking** — Automatic page view logging with referrer, device, browser, OS, and country data.
* **Country geolocation** — See where your visitors come from with automatic IP-based country detection.
* **Privacy-first** — No cookies, no external analytics services, no personal data collection. Visitors are identified by a non-reversible hash.
* **Bot filtering** — Automatically excludes known bots and crawlers.
* **Role exclusion** — Exclude logged-in users by role from being tracked.

== Installation ==

1. Upload the `epic-tracking` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Tracking** in the admin sidebar to view your dashboard and configure settings.

== Frequently Asked Questions ==

= Does this plugin send data to external services? =

All tracking data is stored in your WordPress database. The only external request is an optional IP geolocation lookup to ip-api.com to determine visitor country. No personal data is shared with this service.

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

1. Visual editor — point and click to set up event tracking on any element.
2. Dashboard — visit and event statistics with date filtering.
3. Page detail — per-page breakdown with events, referrers, devices, browsers, OS, and countries.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release of Epic Tracking.
