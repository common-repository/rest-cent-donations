<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://www.rest-cent.de/
 * @since      1.0.0
 *
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 * @author     Rest-Cent Systems GmbH <dominik.held@rest-cent.de>
 */
class Rest_Cent_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'rest-cent-donations',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
