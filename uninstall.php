<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://www.rest-cent.de/
 * @since      1.0.0
 *
 * @package    Rest_Cent
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}restcent_charities");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}restcent_donations");

$rcpr_id = wc_get_product_id_by_sku( 'rc-donation' );
if ( $rcpr_id ) {
	wp_delete_post($rcpr_id, true);
}

// Delete options
$options = [
	'rc_credentials_section_title',
	'rc_shop_email',
	'rc_shop_password',
	'rc_shop_id',
	'rc_fetchdata',
	'rc_credentials_section_end',
	'rc_settings_section_title',
	'rc_shop_donation_enabled',
	'rc_min_cart_value_donation',
	'rc_charities_refresh_interval',
	'rc_selected_shop_charity',
	'rc_settings_section_end',
	'rc_misc_section_title',
	'rc_enabled_logs',
	'rc_server_url',
	'rc_download_data',
	'rc_misc_section_end'
];

foreach ($options as $option) {
	delete_option($option);
}
