<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://www.rest-cent.de/
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 */

/**
 * Fired during plugin deactivation.
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 * @author     Rest-Cent Systems GmbH <dominik.held@rest-cent.de>
 */
class Rest_Cent_Deactivator {
	/**
	 * @since    1.0.0
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
