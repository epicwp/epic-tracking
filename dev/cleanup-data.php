<?php
/**
 * Clean up dummy data by truncating all plugin tables.
 *
 * Usage: wp eval-file dev/cleanup-data.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'epictr_event_log',
	$wpdb->prefix . 'epictr_events',
	$wpdb->prefix . 'epictr_visits',
);

foreach ( $tables as $table ) {
	if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) ) {
		$wpdb->query( "TRUNCATE TABLE {$table}" );
		WP_CLI::log( "  Truncated {$table}" );
	} else {
		WP_CLI::warning( "  Table {$table} does not exist, skipping." );
	}
}

WP_CLI::success( 'All plugin tables have been emptied.' );
