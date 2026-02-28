# Epic Tracking Plugin — Design Document

## Overview

Lightweight WordPress plugin for tracking page visits and click events. Admins configure click tracking via a visual mode overlay, visitors are tracked via minimal JavaScript.

## Requirements

- **MVP scope**: Page visits + click event tracking
- **Visual mode**: Admin bar toggle, iframe-based page preview with sidebar for configuring events
- **Unique visitors**: Cookie-based (first-party, `ept_visitor_id`)
- **Bot filtering**: Server-side User-Agent check against known bot patterns
- **Admin exclusion**: Configurable per user role in settings
- **Elementor support**: Prefer `[data-id]` selectors when available for stability
- **Data access**: WP Admin dashboard (REST API planned for later)
- **Event storage**: Individual records per event occurrence

## Architecture

### Tech Stack

- PHP 7.4+ with PSR-4 autoloading via Composer
- Namespace: `EpicTracking`
- Vanilla JavaScript (no frameworks)
- WordPress AJAX API for tracking endpoints
- Custom database tables via `dbDelta()`

### File Structure

```
epic-tracking/
├── composer.json
├── epic-tracking.php              # Main plugin file, bootstrap
├── src/
│   ├── Database.php               # EpicTracking\Database
│   ├── Tracker.php                # EpicTracking\Tracker
│   ├── VisualMode.php             # EpicTracking\VisualMode
│   ├── Admin.php                  # EpicTracking\Admin
│   └── BotFilter.php              # EpicTracking\BotFilter
├── assets/
│   ├── js/
│   │   ├── tracker.js             # Frontend visitor tracking
│   │   └── visual-mode.js         # Visual mode UI
│   └── css/
│       ├── visual-mode.css        # Visual mode styling
│       └── admin.css              # Admin dashboard styling
└── templates/
    └── admin-dashboard.php        # Dashboard template
```

## Database Schema

### `{prefix}_ept_events` — Configured tracking events

| Column         | Type                  | Description                              |
|----------------|-----------------------|------------------------------------------|
| id             | BIGINT AUTO_INCREMENT | PK                                       |
| page_url       | VARCHAR(500)          | Page path (without domain)               |
| selector       | VARCHAR(500)          | CSS selector to the element              |
| reference_name | VARCHAR(255)          | Human-readable name ("CTA Button Hero")  |
| event_tag      | VARCHAR(100)          | Event identifier ("cta_hero_click")      |
| event_type     | VARCHAR(50)           | "click" (later: "scroll", "hover", etc.) |
| created_at     | DATETIME              | Created timestamp                        |
| updated_at     | DATETIME              | Last modified timestamp                  |

### `{prefix}_ept_visits` — Page visits

| Column     | Type                  | Description                    |
|------------|-----------------------|--------------------------------|
| id         | BIGINT AUTO_INCREMENT | PK                             |
| visitor_id | VARCHAR(64)           | Cookie-based unique visitor ID |
| page_url   | VARCHAR(500)          | Visited URL path               |
| referrer   | VARCHAR(500)          | Referrer URL                   |
| user_agent | VARCHAR(500)          | Browser User-Agent string      |
| created_at | DATETIME              | Timestamp                      |

### `{prefix}_ept_event_log` — Logged click events

| Column     | Type                  | Description                    |
|------------|-----------------------|--------------------------------|
| id         | BIGINT AUTO_INCREMENT | PK                             |
| event_id   | BIGINT                | FK to ept_events.id            |
| visitor_id | VARCHAR(64)           | Cookie-based visitor ID        |
| page_url   | VARCHAR(500)          | URL where click occurred       |
| created_at | DATETIME              | Timestamp                      |

## Visual Mode

### Activation

1. Admin clicks "Epic Tracking" in the WP admin bar
2. Current page loads inside an iframe in a full-width wrapper layout
3. Sidebar panel appears on the right

### Sidebar Panel

- List of configured events for the current page
- "Select Element" button to enter selection mode
- Each event shows: reference name, event tag, selector

### Element Selection

1. User clicks "Select Element"
2. Hovering over elements in the iframe highlights them with an overlay
3. Clicking an element:
   - Generates an optimal CSS selector (prefers `[data-id]` for Elementor elements)
   - Shows a form: reference name + event tag
4. Save via AJAX to `ept_save_event` endpoint (nonce-protected)

### Selector Generation Strategy

Priority order for generating stable selectors:
1. `[data-id="abc123"]` — Elementor element IDs (most stable)
2. `#element-id` — HTML id attribute
3. Unique class-based selector with parent context
4. Full path selector as last resort

## Frontend Tracking

### Page Load Flow

1. `tracker.js` loaded on all frontend pages (target: < 2KB gzipped)
2. Check for `ept_visitor_id` cookie; generate UUID and set if missing (1 year, first-party, SameSite=Lax)
3. Send visit AJAX call: `{ page_url, referrer, visitor_id }`
4. Read inline event config (injected via `wp_localize_script`)
5. For each configured event: `document.querySelector(selector)` → set `data-ept-id` attribute
6. Single delegated event listener on `document` for `[data-ept-id]` clicks
7. On click → fire-and-forget AJAX call: `{ event_id, visitor_id }`

### AJAX Endpoints

| Endpoint               | Auth      | Purpose                    |
|------------------------|-----------|----------------------------|
| `ept_track_visit`      | nopriv    | Log a page visit           |
| `ept_track_event`      | nopriv    | Log a click event          |
| `ept_save_event`       | admin     | Save event config          |
| `ept_delete_event`     | admin     | Delete event config        |
| `ept_get_page_events`  | admin     | Get events for a page URL  |

### Performance Considerations

- Event config is inline JSON (no extra HTTP request)
- One delegated event listener (not per-element)
- AJAX tracking calls are fire-and-forget (non-blocking)
- Bot filtering is server-side only

## Bot Filtering

Server-side User-Agent check against known patterns:
- Googlebot, Bingbot, Slurp, DuckDuckBot, Baiduspider
- Generic: bot, crawl, spider, scrape patterns
- Applied when processing AJAX tracking calls

## UI Design Philosophy

**WordPress-native look and feel throughout.** No custom design system, no gradients, no flashy elements.

- Use WordPress admin CSS classes and components (`wp-list-table`, `widefat`, `postbox`, `button`, `button-primary`, etc.)
- Follow WP admin color scheme and spacing conventions
- Use `wp_admin_notice()` for feedback messages
- Standard WordPress form elements for settings
- Visual mode sidebar: clean white panel with WP-native typography, borders, and spacing — consistent with the WP admin sidebar aesthetic

## Admin Dashboard

### Pages

1. **Dashboard** — Overview of visits and events
   - Visits per page (total + unique visitors)
   - Top clicked events
   - Date range filter (today, 7 days, 30 days)
2. **Settings** — Plugin configuration
   - Excluded user roles (checkboxes per WP role)

### Data Display

WordPress `WP_List_Table` for tabular data. Standard WP `postbox` containers for summary cards. No charting library in MVP — plain numbers and tables.

## Settings

Stored in `wp_options` as `ept_settings`:
- `excluded_roles`: array of WP role slugs to exclude from tracking (default: `['administrator']`)

## Security

- All admin AJAX endpoints require valid nonce
- Tracking endpoints validate and sanitize all input
- Visitor IDs are random UUIDs (no PII)
- SQL queries use `$wpdb->prepare()` throughout
- CSS selectors are sanitized before storage

## Future Extensibility

- REST API endpoints for external data access
- Additional event types (scroll, hover, form submit)
- Data export (CSV)
- Retention policy / auto-cleanup of old data
- Dashboard charts and graphs
