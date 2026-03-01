=== Epic Tracking – Easy Event Tracking for WordPress ===
Contributors: epicwpsolutions
Tags: event tracking, click tracking, analytics, page views, statistics
Requires at least: 6.0
Tested up to: 6.9
Stable tag: 1.3.3
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easy event tracking for WordPress. Track clicks, form submissions, and page views — no code, no cookies, no third-party scripts.

== Description ==

Epic Tracking makes event tracking easy. Set up click and event tracking on any page without writing code — just point, click, and save. All data stays in your own database with zero external dependencies.

= Visual Event Tracking — No Code Required =

Track button clicks, form submissions, link clicks, CTA conversions, and custom events without Google Tag Manager or any external scripts. Open the visual editor on any page, click the element you want to track, give it a name, and you're done. Events start recording immediately.

= Event Analytics Dashboard =

See exactly which events fire and how often. The built-in dashboard gives you:

* Event trigger counts with unique visitor breakdowns
* Per-page event detail views
* Page view statistics with date range filtering
* Referrer, device, browser, OS, and country data per page
* Daily traffic charts and trend visualization
* Sortable tables with pagination

= Privacy-First by Design =

Epic Tracking uses no cookies and stores no personal data. Visitors are identified using a non-reversible hash — fully compatible with GDPR and privacy regulations. Your event and analytics data never leaves your server.

= Features =

* **Visual event editor** — Point-and-click setup for tracking clicks on any element
* **Custom event tags** — Organize events with tags for easy filtering and reporting
* **Event analytics** — See which events fire most, with trigger counts and unique visitors
* **Page view tracking** — Automatic visit logging with full referrer and device data
* **Country geolocation** — Automatic IP-based country detection for visitor locations
* **Bot filtering** — Known bots and crawlers are automatically excluded
* **Role exclusion** — Exclude administrators or any user role from being tracked
* **Date range filtering** — Quick presets (today, last 7 days, last 30 days) and custom ranges
* **Lightweight** — No impact on page load speed; tracking runs asynchronously after render
* **Self-hosted** — All data stored in your WordPress database, no external dependencies
* **Clean uninstall** — All tables and options are removed when you delete the plugin

= Use Cases =

* Track which CTAs and buttons get the most clicks on your landing pages
* Measure form submission rates and conversion events without tag managers
* Monitor page views and traffic trends without Google Analytics
* See which referrers drive the most traffic to specific pages
* Understand visitor demographics: device type, browser, OS, and country
* Run a cookie-free, privacy-compliant event tracking setup

= Why Epic Tracking? =

Most WordPress event tracking requires Google Tag Manager, custom JavaScript, or expensive third-party services. Epic Tracking gives you a visual editor to set up event tracking in seconds — no code, no external scripts, no data leaving your server. Unlike generic analytics plugins, Epic Tracking is purpose-built for tracking clicks and events on your WordPress site.

== Installation ==

1. Upload the `epic-tracking` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Navigate to **Tracking** in the admin sidebar to view your dashboard.
4. To set up event tracking, visit any page on your site and click **Edit Tracking** in the admin bar.

== Frequently Asked Questions ==

= Does this plugin use cookies? =

No. Epic Tracking does not set any cookies. Visitors are identified using a non-reversible hash of their IP address and user agent.

= Does this plugin send data to external services? =

All tracking data is stored in your WordPress database. The only external request is an optional IP geolocation lookup to ip-api.com to determine visitor country. No personal data is shared with this service.

= Is this plugin GDPR compliant? =

Epic Tracking is designed with privacy in mind. It stores no personal data, uses no cookies, and keeps all data on your own server. No data is shared with third parties.

= Will this slow down my site? =

No. Visit logging happens via an asynchronous AJAX request after the page has fully loaded, so it does not affect page render time or Core Web Vitals.

= How do I track button clicks or form submissions? =

Visit any page on your site and click **Edit Tracking** in the WordPress admin bar. This opens the visual editor where you can click on any element to set up tracking — no code required.

= Can I see which pages get the most traffic? =

Yes. The dashboard shows a sortable table of all pages with total visits and unique visitors. Click any page to see a detailed breakdown including referrers, devices, browsers, OS, and countries.

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
