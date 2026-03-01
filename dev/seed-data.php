<?php
/**
 * Seed dummy data for screenshot creation.
 *
 * Usage: wp eval-file dev/seed-data.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$events_table    = $wpdb->prefix . 'ept_events';
$visits_table    = $wpdb->prefix . 'ept_visits';
$event_log_table = $wpdb->prefix . 'ept_event_log';

// Check tables exist.
if ( ! $wpdb->get_var( "SHOW TABLES LIKE '{$events_table}'" ) ) {
	WP_CLI::error( 'Tables do not exist. Activate the plugin first.' );
}

// Check if data already exists.
$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$events_table}" );
if ( $existing > 0 ) {
	WP_CLI::error( 'Tables already contain data. Run cleanup-data.php first.' );
}

WP_CLI::log( 'Seeding dummy data...' );

// ─── Events ───────────────────────────────────────────────────────────

$events = array(
	array( '/',                      'a.btn-buy-now',        'Buy Now Button',      'buy-now' ),
	array( '/',                      'form#newsletter .btn', 'Newsletter Signup',   'newsletter-signup' ),
	array( '/pricing/',              'a.btn-start-trial',    'Start Free Trial',    'start-trial' ),
	array( '/pricing/',              'a.compare-plans',      'Compare Plans',       'compare-plans' ),
	array( '/contact/',              'form#contact .submit', 'Submit Contact Form', 'contact-submit' ),
	array( '/blog/getting-started/', 'a.download-guide',     'Download Guide',      'download-guide' ),
	array( '/products/',             'button.add-to-cart',   'Add to Cart',         'add-to-cart' ),
	array( '/products/',             'a.view-details',       'View Details',        'view-details' ),
);

$event_ids = array();
$now       = current_time( 'mysql' );

foreach ( $events as $event ) {
	$wpdb->insert(
		$events_table,
		array(
			'page_url'       => $event[0],
			'selector'       => $event[1],
			'reference_name' => $event[2],
			'event_tag'      => $event[3],
			'event_type'     => 'click',
			'created_at'     => $now,
			'updated_at'     => $now,
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	$event_ids[] = $wpdb->insert_id;
}

WP_CLI::log( sprintf( '  Inserted %d events.', count( $event_ids ) ) );

// ─── Visitor IDs ──────────────────────────────────────────────────────

$visitor_ids = array();
for ( $i = 0; $i < 150; $i++ ) {
	$visitor_ids[] = hash( 'sha256', 'visitor_' . $i . '_seed' );
}

// Subset for event triggers.
$event_visitor_ids = array_slice( $visitor_ids, 0, 50 );

// ─── Visit data config ───────────────────────────────────────────────

$pages = array(
	'/'                       => 25,
	'/about/'                 => 8,
	'/pricing/'               => 15,
	'/contact/'               => 8,
	'/blog/'                  => 10,
	'/blog/getting-started/'  => 7,
	'/products/'              => 12,
	'/products/widget-pro/'   => 6,
	'/docs/'                  => 5,
	'/careers/'               => 4,
);

$referrers = array(
	''                            => 30,
	'https://www.google.com'      => 35,
	'https://twitter.com'         => 10,
	'https://www.facebook.com'    => 10,
	'https://github.com'          => 8,
	'https://www.reddit.com'      => 7,
);

$devices = array(
	'desktop' => 60,
	'mobile'  => 30,
	'tablet'  => 10,
);

$browsers = array(
	'Chrome'  => 55,
	'Safari'  => 20,
	'Firefox' => 15,
	'Edge'    => 10,
);

$os_list = array(
	'Windows' => 40,
	'macOS'   => 25,
	'iOS'     => 15,
	'Android' => 15,
	'Linux'   => 5,
);

$countries = array(
	'Netherlands'    => 25,
	'United States'  => 20,
	'Germany'        => 15,
	'United Kingdom' => 12,
	'France'         => 8,
	'Belgium'        => 7,
	'Canada'         => 5,
	'Australia'      => 4,
	'Spain'          => 2,
	'Brazil'         => 2,
);

$country_codes = array(
	'Netherlands'    => 'NL',
	'United States'  => 'US',
	'Germany'        => 'DE',
	'United Kingdom' => 'GB',
	'France'         => 'FR',
	'Belgium'        => 'BE',
	'Canada'         => 'CA',
	'Australia'      => 'AU',
	'Spain'          => 'ES',
	'Brazil'         => 'BR',
);

/**
 * Pick a random item from a weighted array.
 */
function ept_weighted_random( $items ) {
	$total = array_sum( $items );
	$rand  = wp_rand( 1, $total );
	$sum   = 0;
	foreach ( $items as $item => $weight ) {
		$sum += $weight;
		if ( $rand <= $sum ) {
			return $item;
		}
	}
	return array_key_first( $items );
}

// ─── Visits ───────────────────────────────────────────────────────────

$total_visits    = wp_rand( 800, 1200 );
$base_per_day    = $total_visits / 30;
$visit_rows      = array();

for ( $day = 29; $day >= 0; $day-- ) {
	$date = gmdate( 'Y-m-d', strtotime( "-{$day} days" ) );
	$dow  = (int) gmdate( 'N', strtotime( $date ) ); // 1=Mon, 7=Sun.

	// Weekday multiplier (weekdays get more traffic).
	$multiplier = ( $dow <= 5 ) ? wp_rand( 100, 140 ) / 100 : wp_rand( 50, 80 ) / 100;

	// Slight upward trend: newer days get slightly more visits.
	$trend = 1 + ( ( 29 - $day ) * 0.01 );

	$day_visits = max( 5, (int) round( $base_per_day * $multiplier * $trend ) );

	for ( $v = 0; $v < $day_visits; $v++ ) {
		$hour   = wp_rand( 6, 23 );
		$minute = wp_rand( 0, 59 );
		$second = wp_rand( 0, 59 );

		$timestamp = sprintf( '%s %02d:%02d:%02d', $date, $hour, $minute, $second );

		$page     = ept_weighted_random( $pages );
		$referrer = ept_weighted_random( $referrers );
		$device   = ept_weighted_random( $devices );
		$browser  = ept_weighted_random( $browsers );
		$os       = ept_weighted_random( $os_list );
		$country  = ept_weighted_random( $countries );
		$code     = $country_codes[ $country ];
		$visitor  = $visitor_ids[ wp_rand( 0, count( $visitor_ids ) - 1 ) ];

		$visit_rows[] = array(
			'visitor_id'   => $visitor,
			'page_url'     => $page,
			'referrer'     => $referrer,
			'user_agent'   => '',
			'device_type'  => $device,
			'browser'      => $browser,
			'os'           => $os,
			'country'      => $country,
			'country_code' => $code,
			'created_at'   => $timestamp,
		);
	}
}

// Batch insert visits.
$batch_size = 50;
$chunks     = array_chunk( $visit_rows, $batch_size );

foreach ( $chunks as $chunk ) {
	$values      = array();
	$placeholders = array();

	foreach ( $chunk as $row ) {
		$placeholders[] = '(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)';
		$values[]       = $row['visitor_id'];
		$values[]       = $row['page_url'];
		$values[]       = $row['referrer'];
		$values[]       = $row['user_agent'];
		$values[]       = $row['device_type'];
		$values[]       = $row['browser'];
		$values[]       = $row['os'];
		$values[]       = $row['country'];
		$values[]       = $row['country_code'];
		$values[]       = $row['created_at'];
	}

	$sql = "INSERT INTO {$visits_table} (visitor_id, page_url, referrer, user_agent, device_type, browser, os, country, country_code, created_at) VALUES " . implode( ', ', $placeholders );

	$wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

WP_CLI::log( sprintf( '  Inserted %d visits over 30 days.', count( $visit_rows ) ) );

// ─── Event log ────────────────────────────────────────────────────────

// Build a map of page_url => event IDs for linking.
$page_event_map = array();
foreach ( $events as $idx => $event ) {
	$page_url = $event[0];
	if ( ! isset( $page_event_map[ $page_url ] ) ) {
		$page_event_map[ $page_url ] = array();
	}
	$page_event_map[ $page_url ][] = $event_ids[ $idx ];
}

$total_triggers = wp_rand( 200, 350 );
$event_log_rows = array();

// Pages that have events.
$event_pages        = array_keys( $page_event_map );
$event_page_weights = array();
foreach ( $event_pages as $ep ) {
	$event_page_weights[ $ep ] = isset( $pages[ $ep ] ) ? $pages[ $ep ] : 5;
}

for ( $t = 0; $t < $total_triggers; $t++ ) {
	$day       = wp_rand( 0, 29 );
	$date      = gmdate( 'Y-m-d', strtotime( "-{$day} days" ) );
	$hour      = wp_rand( 6, 23 );
	$minute    = wp_rand( 0, 59 );
	$second    = wp_rand( 0, 59 );
	$timestamp = sprintf( '%s %02d:%02d:%02d', $date, $hour, $minute, $second );

	$page      = ept_weighted_random( $event_page_weights );
	$ev_ids    = $page_event_map[ $page ];
	$event_id  = $ev_ids[ wp_rand( 0, count( $ev_ids ) - 1 ) ];
	$visitor   = $event_visitor_ids[ wp_rand( 0, count( $event_visitor_ids ) - 1 ) ];

	$event_log_rows[] = array(
		'event_id'   => $event_id,
		'visitor_id' => $visitor,
		'page_url'   => $page,
		'created_at' => $timestamp,
	);
}

// Batch insert event log.
$chunks = array_chunk( $event_log_rows, $batch_size );

foreach ( $chunks as $chunk ) {
	$values       = array();
	$placeholders = array();

	foreach ( $chunk as $row ) {
		$placeholders[] = '(%d, %s, %s, %s)';
		$values[]       = $row['event_id'];
		$values[]       = $row['visitor_id'];
		$values[]       = $row['page_url'];
		$values[]       = $row['created_at'];
	}

	$sql = "INSERT INTO {$event_log_table} (event_id, visitor_id, page_url, created_at) VALUES " . implode( ', ', $placeholders );

	$wpdb->query( $wpdb->prepare( $sql, $values ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

WP_CLI::log( sprintf( '  Inserted %d event triggers.', count( $event_log_rows ) ) );

WP_CLI::success( 'Dummy data seeded successfully!' );
