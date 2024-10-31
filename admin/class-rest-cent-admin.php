<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.rest-cent.de/
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/admin
 */

/**
 * The admin-specific functionality of the plugin.
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Rest_Cent
 * @subpackage Rest_Cent/admin
 * @author     Rest-Cent Systems GmbH <dominik.held@rest-cent.de>
 */
class Rest_Cent_Admin {
	public const RC_SERVER_LIVE = 'https://server.rest-cent.de';
	public const RC_SERVER_DEV = 'https://dev.server.rest-cent.de';
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of this plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 * An instance of this class should be passed to the run() function
		 * defined in Rest_Cent_Loader as all of the hooks are defined
		 * in that particular class.
		 * The Rest_Cent_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rest-cent-admin.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		/**
		 * This function is provided for demonstration purposes only.
		 * An instance of this class should be passed to the run() function
		 * defined in Rest_Cent_Loader as all of the hooks are defined
		 * in that particular class.
		 * The Rest_Cent_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rest-cent-admin.js', [ 'jquery' ], $this->version, false );
	}

	public function rc_login_notice() {
		if ( isset( $_GET['rc-success'] ) ) : ?>
            <div class="notice-success notice">
                <p><?php
					echo __( 'Connection established successfully', 'rest-cent-donations' ); ?></p>
            </div>
		<?php
		endif;

		if ( isset( $_GET['rc-error'] ) ) : ?>
            <div class="notice-error notice">
                <p><?php
					echo __( 'Please check login details', 'rest-cent-donations' ); ?></p>
            </div>
		<?php
		endif;
	}

	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['settings_tab_restcent'] = __( 'Rest-Cent', 'rest-cent-donations' );

		return $settings_tabs;
	}

	public function settings_tab() {
		woocommerce_admin_fields( $this->restcent_get_settings() );
	}

	public function fetch_charities() {
		global $wpdb;
		$email            = WC_Admin_Settings::get_option( 'rc_shop_email' );
		$password         = WC_Admin_Settings::get_option( 'rc_shop_password' );
		$restcent_shop_id = WC_Admin_Settings::get_option( 'rc_shop_id' );

		$fields = [ 'email' => $email, 'password' => $password ];
		$data   = self::rcPost( $fields, '/auth/authenticate/' );
		if ( $data ) {
			$data                      = json_decode( $data, true );
			$restcent_shop_accessToken = $data['accessToken'];
			$token                     = $data['accessToken'];
			$idtoken                   = $data['idToken'];
			$refreshToken              = $data['refreshToken'];

			if ( ! empty( $restcent_shop_accessToken ) ) {
				$headers = [
					'Authorization' => 'Bearer ' . $token,
					'idToken'       => $idtoken,
					'refreshToken'  => $refreshToken
				];

				$result = self::rcRequest( [], '/charities/user/' . $restcent_shop_id, 'GET', $headers );

				if ( $result ) {
					$responseData = json_decode( $result );
					if ( isset( $responseData[0] ) ) {
						$table_name = $wpdb->prefix . 'restcent_charities';
						$wpdb->delete( $table_name, [ 'userid' => $restcent_shop_id ] );
						foreach ( $responseData as $charities ) {
							if ( $charities->name != '' && $charities->id != '' ) {
								$imid = $this->add_media_rc( $charities->logo_url, $charities->id );
								$wpdb->insert( $table_name, [ 'userid' => $restcent_shop_id, 'restcent_charities_id' => $charities->id, 'name' => $charities->name, 'logo_url' => $imid ] );
								if ( ! get_option( 'rc_selected_shop_charity' ) ) {
									update_option( 'rc_selected_shop_charity', $charities->id );
								}
							}
						}

						wp_redirect( admin_url( '/admin.php?page=wc-settings&tab=settings_tab_restcent&rc-success=1', 'admin' ), 301 );
						exit;
					}
				}
			}
		}

		update_option( 'rc_selected_shop_charity', null );
		wp_redirect( admin_url( '/admin.php?page=wc-settings&tab=settings_tab_restcent&rc-error=1' ), 301 );
		exit;
	}

	public function update_settings() {
		woocommerce_update_options( $this->restcent_get_settings() );
	}

	public function add_media_rc( $url, $id ) {
		$image_url  = $url; // Define the image URL here
		$link_array = explode( '/', $image_url );
		$image_name = end( $link_array );
		// Separate the filename into a name and extension.
		$ext        = pathinfo( $image_name, PATHINFO_EXTENSION );
		$upload_dir = wp_upload_dir(); // Set upload folder
		$response   = wp_remote_get( $image_url ); // Get image data
		$image_data = wp_remote_retrieve_body( $response );

		$filename = $id . '.' . strtolower( $ext ); // Create image file name
		$filename = sanitize_file_name( $filename );
		// Check folder permission and define file location
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}

		if ( file_exists( $file ) ) {
			$args      = [
				'post_type'      => 'attachment',
				'name'           => $filename,
				'posts_per_page' => - 1,
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
				'post_title'     => $filename,
				'post_content'   => '',
				'post_status'    => 'inherit'
			];
			// Create the attachment
			$attach_id = wp_insert_attachment( $attachment, $file );
			add_post_meta( $attach_id, 'url', $image_url );
			// Include image.php
			require_once( ABSPATH . 'wp-admin/includes/image.php' );
			// Define attachment metadata
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
			// Assign metadata to attachment
			wp_update_attachment_metadata( $attach_id, $attach_data );
		}

		return $attach_id;
	}

	public function restcent_field_charities( $value ) {
		global $wpdb;
		$restcent_shop_id   = WC_Admin_Settings::get_option( 'rc_shop_id' );
		$query              = 'select * from `' . $wpdb->prefix . "restcent_charities` where userid='" . $restcent_shop_id . "'";
		$restcent_charities = $wpdb->get_results( $query );

		$option_value = WC_Admin_Settings::get_option( $value['id'] );
		$description  = WC_Admin_Settings::get_field_description( $value );
		?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo esc_html( $description['tooltip_html'] ); ?>
            </th>

            <td class="forminp forminp-<?php echo esc_attr( $value['type'] ) ?>">
                <select name="<?php echo esc_attr( $value['id'] ); ?>"
                        id="<?php echo esc_attr( $value['id'] ); ?>"
                        data-placeholder="<?php echo esc_attr( $value['name'] ) ?>">
					<?php
					if ( count( $restcent_charities ) > 0 ) {
						foreach ( $restcent_charities as $charities_list ) {
							echo '<option ' . ( $charities_list->restcent_charities_id === $option_value ? 'selected' : '' ) . ' value="' . esc_attr($charities_list->restcent_charities_id) . '">' . esc_attr($charities_list->name) . '</option>';
						}
					} else {
						echo '<option value="">No records found</option>';
					}
					?>
                </select>

				<?php
				echo wp_kses_post( $description['description'] ); ?>

            </td>
        </tr>
		<?php
	}

	public function restcent_field_button( $value ) {
		$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
		$description  = WC_Admin_Settings::get_field_description( $value );

		?>

        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo esc_html( $description['tooltip_html'] ); ?>
            </th>

            <td class="forminp forminp-<?php echo esc_attr( $value['type'] ) ?>">
                <a id="<?php echo esc_attr( $value['id'] ); ?>"
                   href="<?php echo esc_url( $value['link'] ); ?>"
                   style="<?php echo esc_attr( $value['css'] ); ?>"
                   class="<?php echo esc_attr( $value['class'] ); ?>"
                ><?php echo esc_attr( $value['name'] ); ?></a>
				<?php echo wp_kses_post( $description['description'] ); ?>
            </td>
        </tr>

		<?php
	}

	public function csv_pull_restcent() {
		global $wpdb;
		$file    = 'donations_csv';
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}restcent_donations;", ARRAY_A );
		if ( empty( $results ) ) {
			return;
		}

		$csv_output = '"' . implode( '","', array_keys( $results[0] ) ) . '";' . "\n";;

		foreach ( $results as $row ) {
			$csv_output .= '"' . implode( '","', $row ) . '";' . "\n";
		}

		$csv_output .= "\n";
		$filename   = $file . '_' . date( 'Y-m-d_H-i' );
		header( 'Content-type: application/vnd.ms-excel' );
		header( 'Content-disposition: csv' . date( 'Y-m-d' ) . '.csv' );
		header( 'Content-disposition: filename=' . $filename . '.csv' );
		print $csv_output;
		exit;
	}

	public function restcent_get_settings() {
		return [
			'rc_credentials_section_title'  => [
				'name' => __( 'Credentials', 'rest-cent-donations' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'rc_credentials_section_title'
			],
			'rc_shop_email'                 => [
				'name'              => __( 'Email', 'rest-cent-donations' ) . ' *',
				'type'              => 'text',
				'desc'              => '',
				'custom_attributes' => [ 'required' => 'required' ],
				'id'                => 'rc_shop_email'
			],
			'rc_shop_password'              => [
				'name'              => __( 'Password', 'rest-cent-donations' ) . ' *',
				'type'              => 'password',
				'desc'              => '',
				'custom_attributes' => [ 'required' => 'required' ],
				'id'                => 'rc_shop_password'
			],
			'rc_shop_id'                    => [
				'name'              => __( 'Shop ID', 'rest-cent-donations' ) . ' *',
				'type'              => 'text',
				'custom_attributes' => [ 'required' => 'required' ],
				'desc'              => __( 'You will receive this store ID after logging in to the Rest-Cent portal under the menu item \'Settings\'', 'rest-cent-donations' ),
				'id'                => 'rc_shop_id'
			],
			'rc_fetchdata'                  => [
				'name'  => __( 'Fetch from RestCent', 'rest-cent-donations' ),
				'type'  => 'button',
				'desc'  => __( 'This button allows you to directly load the settings from the Rest-Cent portal, e.g. downloading your selected nonprofit organizations. However, the plugin also synchronizes your settings regularly every 24 hours, so the manual download is only necessary if you want to synchronize any changes immediately, e.g. when using the plugin for the first time.',
					'rest-cent-donations' ),
				'link'  => site_url() . '/wp-admin/admin-ajax.php?action=rc_fetch_charities',
				'class' => 'button-secondary',
				'id'    => 'rc_fetchdata'
			],
			'rc_credentials_section_end'    => [
				'type' => 'sectionend',
				'id'   => 'rc_credentials_section_end'
			],
			'rc_settings_section_title'     => [
				'name' => __( 'Settings', 'rest-cent-donations' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'rc_settings_section_title'
			],
			'rc_shop_donation_enabled'      => [
				'name'    => __( 'Allow shop owner donations', 'rest-cent-donations' ),
				'type'    => 'select',
				'desc'    => __( 'Here you activate the store donation function, e.g. the donation of decimal places of a purchase. The donation function for your buyers is not affected, this is always active as long as the plugin is active.', 'rest-cent-donations' ),
				'id'      => 'rc_shop_donation_enabled',
				'options' => [
					'yes' => __( 'Yes', 'rest-cent-donations' ),
					'no'  => __( 'No', 'rest-cent-donations' )
				]
			],
			'rc_min_cart_value_donation'    => [
				'name' => __( 'Cart value for shop donations', 'rest-cent-donations' ),
				'type' => 'text',
				'desc' => __( 'Set the minimum transaction amount which will enable shop donations. Set 0 to donate on all purchases.', 'rest-cent-donations' ),
				'id'   => 'rc_min_cart_value_donation'
			],
			'rc_charities_refresh_interval' => [
				'name'    => __( 'Shuffle charity selection', 'rest-cent-donations' ),
				'type'    => 'select',
				'desc'    => __( 'Here you can set an interval to change the organizations automatically in a set cycle. This is useful if you have selected multiple organizations. Your store donations will be automatically assigned to the next organization in the set frequency.',
					'rest-cent-donations' ),
				'id'      => 'rc_charities_refresh_interval',
				'options' => [
					'6 hours'              => __( '6 hours', 'rest-cent-donations' ),
					'12 hours'             => __( '12 hours', 'rest-cent-donations' ),
					'24 hours'             => __( '24 hours', 'rest-cent-donations' ),
					'1 week'               => __( '1 week', 'rest-cent-donations' ),
					'2 weeks'              => __( '2 weeks', 'rest-cent-donations' ),
					'1 month'              => __( '1 month', 'rest-cent-donations' ),
					'after every purchase' => __( 'After every purchase', 'rest-cent-donations' ),
				]
			],
			'rc_selected_shop_charity'      => [
				'name' => __( 'Selected charity', 'rest-cent-donations' ),
				'type' => 'charity_selection',
				'desc' => __( 'Here you can choose the non-profit organization that will receive your shop donations. Depending on the frequency set for the automatic change of the organization, you can see the current organization set by the system.', 'rest-cent-donations' ),
				'id'   => 'rc_selected_shop_charity'
			],
			'rc_settings_section_end'       => [
				'type' => 'sectionend',
				'id'   => 'rc_settings_section_end'
			],
			'rc_misc_section_title'         => [
				'name' => __( 'Misc', 'rest-cent-donations' ),
				'type' => 'title',
				'desc' => '',
				'id'   => 'rc_misc_section_title'
			],

			'rc_enabled_logs'     => [
				'name'    => __( 'Enable logs', 'rest-cent-donations' ),
				'type'    => 'select',
				'desc'    => __( 'Here you activate if you want to log the different processes the plugin performs. Can be used to debug issues.', 'rest-cent-donations' ),
				'id'      => 'rc_enabled_logs',
				'options' => [
					'no'  => __( 'No', 'rest-cent-donations' ),
					'yes' => __( 'Yes', 'rest-cent-donations' ),
				]
			],
			'rc_server_url'       => [
				'name'    => __( 'Enable Test Mode', 'rest-cent-donations' ),
				'type'    => 'select',
				'desc'    => __( 'The test mode allows a detailed error analysis in our test environment. Please activate this mode only if you are asked to do so in a support case.', 'rest-cent-donations' ),
				'id'      => 'rc_server_url',
				'options' => [
					self::RC_SERVER_LIVE => __( 'No', 'rest-cent-donations' ),
					self::RC_SERVER_DEV  => __( 'Yes', 'rest-cent-donations' ),
				]
			],
			'rc_download_data'    => [
				'name'  => __( 'Export Donation Data', 'rest-cent-donations' ),
				'type'  => 'button',
				'desc'  => __( 'Allows you to create a CSV export of your donations', 'rest-cent-donations' ),
				'link'  => site_url() . '/wp-admin/admin-ajax.php?action=csv_pull',
				'class' => 'button-secondary',
				'id'    => 'rc_download_data'
			],
			'rc_misc_section_end' => [
				'type' => 'sectionend',
				'id'   => 'rc_misc_section_end'
			]
		];
	}

	public static function rcPost( array $body, string $url, array $headers = [] ) {
		$rc_server_url = WC_Admin_Settings::get_option( 'rc_server_url' ) ?? self::RC_SERVER_LIVE;
		$postData      = [
			'body'        => json_encode( $body ),
			'data_format' => 'body',
			'timeout'     => '30',
			'redirection' => '10',
			'sslverify'   => false,
			'method'      => 'POST',
			'headers'     => array_merge( [
				'Cache-Control' => 'no-cache',
				'Content-Type'  => 'application/json',
			], $headers )
		];

		$result_fetch = wp_remote_post( $rc_server_url . $url, $postData );

		if ( ! is_wp_error( $result_fetch ) ) {
			$data = wp_remote_retrieve_body( $result_fetch );
			if ( get_option( 'rc_enabled_logs' ) ) {
				error_log( print_r( [ 'request' => $body, 'response' => $data ], true ) );
			}
		} else {
			if ( get_option( 'rc_enabled_logs' ) ) {
				error_log( print_r( [ 'error' => true, 'request' => $body, 'response' => $result_fetch->get_error_message() ], true ) );
			}
			$data = false;
		}

		return $data;
	}

	public static function rcRequest( array $body, string $url, string $method, array $headers = [] ) {
		$rc_server_url = WC_Admin_Settings::get_option( 'rc_server_url' ) ?? self::RC_SERVER_LIVE;
		$postData      = [
			'data_format' => 'body',
			'body'        => $body,
			'method'      => $method,
			'sslverify'   => false,
			'timeout'     => '30',
			'redirection' => '10',
			'headers'     => array_merge( [
				'cache-control' => 'no-cache',
				'content-type'  => 'application/json',
			], $headers )
		];

		$result_fetch = wp_remote_request( $rc_server_url . $url, $postData );

		if ( ! is_wp_error( $result_fetch ) ) {
			$data = wp_remote_retrieve_body( $result_fetch );
			if ( get_option( 'rc_enabled_logs' ) ) {
				error_log( print_r( [ 'request' => $body, 'response' => $data ], true ) );
			}
		} else {
			if ( get_option( 'rc_enabled_logs' ) ) {
				error_log( print_r( [ 'error' => true, 'request' => $body, 'response' => $result_fetch->get_error_message() ], true ) );
			}
			$data = false;
		}

		return $data;
	}
}
