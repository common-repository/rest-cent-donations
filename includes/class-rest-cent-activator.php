<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.rest-cent.de/
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 */

/**
 * Fired during plugin activation.
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 * @author     Rest-Cent Systems GmbH <dominik.held@rest-cent.de>
 */
class Rest_Cent_Activator {
	/**
	 * @since    1.0.0
	 */
	public static function activate() {
		// Require parent plugin
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) && current_user_can( 'activate_plugins' ) ) {
			// Stop activation redirect and show error
			wp_die( 'Sorry, but this plugin requires Woocommerce plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>' );
		}

		global $wpdb;
		$restcent_donations = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'restcent_donations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `order_id` varchar(255) DEFAULT NULL,
			`charity_id` varchar(255) DEFAULT NULL,
			`donator` varchar(255) DEFAULT NULL,			
			`amount` varchar(255) DEFAULT NULL,
            `owner_donation` varchar(255) DEFAULT NULL,
            `rc_donation_id` varchar(255) DEFAULT NULL,
			`server_status` varchar(255) DEFAULT NULL,
            `created_at` datetime NULL DEFAULT NULL, 
			`updated_at` datetime NULL DEFAULT NULL,
            `status` int(11) DEFAULT NULL,
            PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';
		$wpdb->query( $restcent_donations );

		$restcent_charities = 'CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'restcent_charities` (
            `id` int NOT NULL AUTO_INCREMENT,
			 `userid` varchar(255) DEFAULT NULL,
			 `restcent_charities_id` varchar(255) DEFAULT NULL,
			 `name` varchar(255) DEFAULT NULL,
			 `logo_url` varchar(255) DEFAULT NULL,
			 PRIMARY KEY (`id`),
			 UNIQUE KEY `charity_user` (`userid`,`restcent_charities_id`) USING BTREE,
			 KEY `restcent_charities_id` (`restcent_charities_id`) USING BTREE,
			 KEY `userid` (`userid`)
                ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;';
		$wpdb->query( $restcent_charities );

		// Add Featured Image to Post
		$image_url  = plugin_dir_url( __DIR__ ) . 'public/css/rc-icon.png'; // Define the image URL here
		$filename   = 'rc-icon.png';
		$upload_dir = wp_upload_dir(); // Set upload folder
		$response = wp_remote_get( $image_url ); // Get image data
		$image_data = wp_remote_retrieve_body( $response );

		// Check folder permission and define file location
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		if ( file_exists( $file ) ) {
			$args      = [
				'post_type' => 'attachment',
				'name'      => $filename,
			];
			$image     = get_posts( $args )[0];
			$attach_id = $image->ID;
		} else {
			// Create the image  file on the server
			file_put_contents( $file, $image_data );

			// Check image file type
			$wp_filetype = wp_check_filetype( $filename );

			// Set attachment data
			$attachment = [
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => sanitize_file_name( $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit'
			];

			// Create the attachment
			$attach_id = wp_insert_attachment( $attachment, $file );
		}

		update_post_meta( $attach_id, '_wp_attachment_image_alt', 'restcent-charity-logo' );
		// Include image.php
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Define attachment metadata
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );

		// Assign metadata to attachment
		wp_update_attachment_metadata( $attach_id, $attach_data );
		$rcpr_id = wc_get_product_id_by_sku( 'rc-donation' );
		if ( ! $rcpr_id ) {
			$post_id = wp_insert_post( [
				'post_title'   => 'Rest Cent Donation',
				'post_type'    => 'product',
				'post_status'  => 'publish',
				'post_content' => '',
			] );
		} else {
			$args    = [
				'post_type' => 'product',
				'name'      => 'Rest Cent Donation'
			];
			$post    = get_posts( $args )[0];
			$post_id = $post->ID;
		}

		$product = wc_get_product( $post_id );
		$product->set_sku( 'rc-donation' );
		$product->set_regular_price( 0 );
		$product->set_catalog_visibility( 'hidden' );
		$product->save();

		update_post_meta( $post_id, '_sold_individually', 'yes' );
		update_post_meta( $post_id, '_virtual', 'yes' );
		update_post_meta( $post_id, '_tax_status', 'none' );
		update_post_meta( $post_id, '_tax_class', 'zero-rate' );

		set_post_thumbnail( $post_id, $attach_id );
		wp_update_attachment_metadata( $attach_id, $attach_data );
	}
}
