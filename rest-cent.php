<?php

/**
 * @link              https://www.rest-cent.de/
 * @since             1.0.0
 * @package           Rest_Cent
 *
 * @wordpress-plugin
 * Plugin Name:       Rest-Cent Donations
 * Description:       Integrate with the Rest-Cent service provider to donate amount to different charities.
 * Version:           1.0.0
 * Author:            Rest-Cent Systems GmbH
 * Author URI:        https://www.rest-cent.de/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rest-cent-donations
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP: 7.2
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'REST_CENT_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rest-cent-activator.php
 */
function activate_rest_cent() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rest-cent-activator.php';
	Rest_Cent_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rest-cent-deactivator.php
 */
function deactivate_rest_cent() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-rest-cent-deactivator.php';
	Rest_Cent_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_rest_cent' );
register_deactivation_hook( __FILE__, 'deactivate_rest_cent' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rest-cent.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rest_cent() {

	$plugin = new Rest_Cent();
	$plugin->run();

}
run_rest_cent();
