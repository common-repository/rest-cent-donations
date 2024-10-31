<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.rest-cent.de/
 * @since      1.0.0
 *
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/includes
 * @author     Rest-Cent Systems GmbH <dominik.held@rest-cent.de>
 */
class Rest_Cent {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Rest_Cent_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'REST_CENT_VERSION' ) ) {
			$this->version = REST_CENT_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'rest-cent';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Rest_Cent_Loader. Orchestrates the hooks of the plugin.
	 * - Rest_Cent_i18n. Defines internationalization functionality.
	 * - Rest_Cent_Admin. Defines all hooks for the admin area.
	 * - Rest_Cent_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rest-cent-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-rest-cent-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-rest-cent-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-rest-cent-public.php';

		$this->loader = new Rest_Cent_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Rest_Cent_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Rest_Cent_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Rest_Cent_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

		$this->loader->add_action( 'woocommerce_admin_field_button', $plugin_admin, 'restcent_field_button');
		$this->loader->add_action( 'woocommerce_admin_field_charity_selection', $plugin_admin, 'restcent_field_charities');

		$this->loader->add_action( 'woocommerce_settings_tabs_array', $plugin_admin, 'add_settings_tab', 50);
		$this->loader->add_action( 'woocommerce_settings_tabs_settings_tab_restcent', $plugin_admin, 'settings_tab');
		$this->loader->add_action( 'woocommerce_update_options_settings_tab_restcent', $plugin_admin, 'update_settings');

		$this->loader->add_action( 'wp_ajax_csv_pull', $plugin_admin, 'csv_pull_restcent');
		$this->loader->add_action( 'wp_ajax_rc_fetch_charities', $plugin_admin, 'fetch_charities');

		$this->loader->add_action( 'admin_notices', $plugin_admin, 'rc_login_notice' );

		// $this->loader->add_action( 'added_option_rc_shop_id', $plugin_admin, 'fetch_charities', 10, 2);
		// $this->loader->add_action( 'updated_option_rc_shop_id', $plugin_admin, 'fetch_charities', 10, 3);

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Rest_Cent_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action('init', $plugin_public, 'rc_register_session');

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

		$this->loader->add_action( 'woocommerce_review_order_after_order_total', $plugin_public, 'order_after_order_total' );
		$this->loader->add_action( 'woocommerce_after_checkout_billing_form', $plugin_public, 'ts_checkout_order_review', 4, 3 );

		$this->loader->add_action( 'wp_ajax_restcent_source_newshop', $plugin_public, 'restcent_source_newshop' );
		$this->loader->add_action( 'wp_ajax_nopriv_restcent_source_newshop', $plugin_public, 'restcent_source_newshop' );

		// $this->loader->add_action( 'wp_ajax_restcent_source_rest_sent_login', $plugin_public, 'restcent_source_rest_sent_login' );
		// $this->loader->add_action( 'wp_ajax_nopriv_restcent_source_rest_sent_login', $plugin_public, 'restcent_source_rest_sent_login' );

		$this->loader->add_action( 'woocommerce_cart_totals_before_order_total', $plugin_public, 'woo_add_cart_fee' );
		$this->loader->add_action( 'woocommerce_before_calculate_totals', $plugin_public, 'woo_add_cart_fee' );
		$this->loader->add_action( 'woocommerce_checkout_create_order', $plugin_public, 'save_order_custom_meta_data', 10, 2 );
		$this->loader->add_action( 'woocommerce_after_order_itemmeta', $plugin_public, 'custom_link_after_order_itemmeta', 20, 3  );

		$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_public, 'so_payment_complete' );
		$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_public, 'order_complete_donaton_complete' );
		$this->loader->add_action( 'woocommerce_order_status_cancelled', $plugin_public, 'action_woocommerce_cancelled_order');
		$this->loader->add_action( 'woocommerce_order_status_refunded', $plugin_public, 'action_woocommerce_cancelled_order');
		$this->loader->add_action( 'woocommerce_order_status_processing', $plugin_public, 'order_processing_donaton_processing');

		$this->loader->add_action( 'isa_add_twinty_four_hours', $plugin_public, 'restcent_source_outdated_transaction_cron');

		$this->loader->add_action( 'wp_ajax_fetch_charities', $plugin_public, 'every_six_hours_event_func');
		$this->loader->add_action( 'wp_ajax_nopriv_fetch_charities', $plugin_public, 'every_six_hours_event_func');
		$this->loader->add_action( 'isa_add_six_hours', $plugin_public, 'every_six_hours_event_func');

		$this->loader->add_action( 'woocommerce_before_order_itemmeta', $plugin_public, 'so_32457241_before_order_itemmeta', 10, 3);
		// $this->loader->add_action( 'add_meta_boxes', $plugin_public, 'tcg_tracking_box', 10, 2);

		$this->loader->add_action( 'wp_ajax_restcent_source_outdated_transactions', $plugin_public, 'restcent_source_outdated_transactions');
		$this->loader->add_action( 'wp_ajax_nopriv_restcent_source_outdated_transactions', $plugin_public, 'restcent_source_outdated_transactions');

		$this->loader->add_action( 'woocommerce_cart_calculate_fees', $plugin_public, 'woocommerce_cart_calculate_fees');

		$this->loader->add_action( 'wp_ajax_add_donation', $plugin_public, 'add_donation_function');
		$this->loader->add_action( 'wp_ajax_nopriv_add_donation', $plugin_public, 'add_donation_function');

		$this->loader->add_action( 'pre_get_posts', $plugin_public, 'hide_out_of_stock_products_from_search');
		$this->loader->add_action( 'wp_footer', $plugin_public, 'add_custom_script_to_wp_footer');

		$this->loader->add_filter( 'cron_schedules', $plugin_public, 'isa_add_every_twinty_four_hours');
		$this->loader->add_filter( 'cron_schedules', $plugin_public, 'isa_add_every_six_hours');

		$this->loader->add_filter( 'woocommerce_order_item_name', $plugin_public, 'ts_product_image_on_thankyou', 10, 3 );
		$this->loader->add_filter( 'woocommerce_product_query_meta_query', $plugin_public, 'hide_out_of_stock_products_from_shop', 10, 2 );


	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Rest_Cent_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
