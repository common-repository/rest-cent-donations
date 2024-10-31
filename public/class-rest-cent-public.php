<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.rest-cent.de/
 * @since      1.0.0
 * @package    Rest_Cent
 * @subpackage Rest_Cent/public
 */

/**
 * The public-facing functionality of the plugin.
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Rest_Cent
 * @subpackage Rest_Cent/public
 * @author     Rest-Cent Systems GmbH <dominik.held@rest-cent.de>
 */
class Rest_Cent_Public {
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
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
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

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/rest-cent-public.css', [], $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
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

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/rest-cent-public.js', [ 'jquery' ], $this->version, false );
	}

	public function rc_register_session() {
		if ( ! session_id() ) {
			@session_start();
		}
	}

	public function order_after_order_total() {
		$this->rc_register_session();
		global $wpdb, $woocommerce;
		$rc_shop_donation_enabled = get_option( 'rc_shop_donation_enabled' );
		if ( $rc_shop_donation_enabled === 'yes' ) {
			$rc_shop_id                 = get_option( 'rc_shop_id' );
			$rc_selected_shop_charity   = get_option( 'rc_selected_shop_charity' );
			$queryup                    = "select * from `{$wpdb->prefix}restcent_charities` WHERE userid = {$rc_shop_id} AND restcent_charities_id = {$rc_selected_shop_charity} LIMIT 1";
			$charityItem                = $wpdb->get_row( $queryup );
			$cattotal                   = $woocommerce->cart->total - (float) ( @$_SESSION['restcent']['donation_ammount'] );
			$rc_min_cart_value_donation = get_option( 'rc_min_cart_value_donation' );
			$whole                      = (int) $cattotal;
			$frac                       = $cattotal - $whole;
			$owner_donation_amout       = round( $frac, 2 );
			if ( ! $owner_donation_amout ) {
				$owner_donation_amout = 1.00;
			}
			if ( $rc_min_cart_value_donation <= $cattotal ) {
				echo '<input style="display:none" type="number" name="shop_donation_amount" value="' . esc_attr( $owner_donation_amout ) . '" id="shop_donation_amount">';
				echo '<input style="display:none" type="text" name="shop_donation_charity_id" value="' . esc_attr( $charityItem->restcent_charities_id ) . '" id="shop_donation_charity_id">';
				echo '<input style="display:none" type="text" name="shop_donation_charity_name" value="' . esc_attr( $charityItem->name ) . '" id="shop_donation_charity_name">';
				echo '<input style="display:none" type="text" name="shop_donation_charity_logo" value="' . esc_attr( $charityItem->logo_url ) . '" id="shop_donation_charity_logo">';
			}
		}
	}

	public function ts_checkout_order_review() {
		$this->rc_register_session();
		global $wp, $wpdb, $woocommerce;

		$product_id_1             = wc_get_product_id_by_sku( 'rc-donation' );
		$rc_selected_shop_charity = isset( $_SESSION['restcent']['selected_charity'] ) ? sanitize_text_field( $_SESSION['restcent']['selected_charity'] ) : get_option( 'rc_selected_shop_charity' );

		$shopid = isset( $_SESSION['restcent']['rc_login_donator'] ) ? sanitize_text_field( $_SESSION['restcent']['rc_login_donator'] ) : get_option( 'rc_shop_id' );
		// $cattotal = $woocommerce->cart->total;
		// $whole    = (int) $cattotal;
		// $frac     = $cattotal - $whole;
		$btntext = __( 'Donate', 'rest-cent-donations' );

		// Only show donations section if charities are found.
		$queryup           = "select * from `{$wpdb->prefix}restcent_charities` WHERE userid = '{$shopid}'";
		$restcent_charitie = $wpdb->get_results( $queryup );
		if ( ! $restcent_charitie && ! isset( $_GET['sub'] ) ) {
			foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
				if ( $cart_item['data']->get_id() === $product_id_1 ) {
					$product_cart_id = WC()->cart->generate_cart_id( $product_id_1 );
					$cart_item_key   = WC()->cart->find_product_in_cart( $product_cart_id );
					if ( $cart_item_key ) {
						WC()->cart->remove_cart_item( $cart_item_key );
					}
				}
			}

			return;
		}

		$ttl     = ( WC()->cart->get_cart_contents_total() + WC()->cart->get_shipping_total() + WC()->cart->get_total_tax() );
		$remcent = round( ceil( $ttl ) - $ttl, 2 );
		foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
			if ( $cart_item['data']->get_name() === 'Rest Cent Donation' ) {
				$product_cart_id = WC()->cart->generate_cart_id( $product_id_1 );
				$cart_item_key   = WC()->cart->find_product_in_cart( $product_cart_id );
				if ( $cart_item_key ) {
					WC()->cart->remove_cart_item( $cart_item_key );
				}
			} elseif ( $cart_item['data']->get_id() === $product_id_1 ) {
				$btntext = __( 'Update donation amount', 'rest-cent-donations' );
				$remcent = $cart_item['data']->get_price();
			}
		}

		if ( ! $remcent ) {
			$remcent = 1;
		}
		echo '<div id="customise_checkout_field">';
		echo '<div class="customer_checkout_hd">
            <h3>Rest-Cent ' . __( 'donations', 'rest-cent-donations' ) . '</h3> <img alt="restcent-logo" src="' . plugin_dir_url( __DIR__ ) . 'public/css/rest_log.png">
        </div>';
		echo '<input style="display:none" type="hidden" name="currency_symbol" value="' . get_woocommerce_currency_symbol() . '" id="currency_symbol">';
		echo '<p id="rest-cent__para">' . __( 'By selecting the charity below you consent to donate the rounded cents of this order to the select. charity. You can also choose to donate a different amount by entering it in the box below.', 'rest-cent-donations' ) . '</p>';
		echo '<br/>';
		echo '<div class="InputAddOn">
              <input class="InputAddOn-field user_charity_amount" step="0.01" type="number" title="' . get_woocommerce_currency() . '" name="user_charity_amount" value="' . esc_attr(round( $remcent, 2 )) . '" id="total_amount">
            </div>';

		echo '<div class="clear"></div><br/>';
		echo '<input style="display:none" type="text" name="custom_user_shopid" value="" id="custom_user_shopid">';
		echo '<input style="display:none" type="text" name="temp_charity_name" value="" id="temp_charity_name">';
		echo '<input style="display:none" type="hidden" name="charity_logo" value="" id="charity_logo">';
		echo '<input style="display:none" type="hidden" name="charity_name" value="" id="charity_name">';
		echo '<input style="display:none" type="hidden" name="restcent_shop_accessToken" value="" id="restcent_shop_accessToken">';
		echo '<input style="display:none" type="hidden" name="rc_shop_idToken"   value="" id="rc_shop_idToken">';
		echo '<input style="display:none" type="hidden" name="restcent_shop_refreshToken" value="" id="restcent_shop_refreshToken">';

		echo '<select name="charities_list" id="charities_list" class="charities_list">';
		foreach ( $restcent_charitie as $charityItem ) {
			if ( $charityItem->restcent_charities_id == $rc_selected_shop_charity ) {
				$selected = 'selected';
			} else {
				$selected = ' ';
			}
			echo '<option  ' . $selected . ' data-logo-url="' . wp_get_attachment_url( $charityItem->logo_url ) . '" data-logo="' . esc_attr( $charityItem->logo_url ) . '" value="' . esc_attr( $charityItem->restcent_charities_id ) . '">' . esc_attr( $charityItem->name ) . ' </option> ';
		}
		if ( $btntext === __( 'Update donation amount', 'rest-cent-donations' ) ) {
			echo '<option value="cancel">' . __( 'Cancel Donation', 'rest-cent-donations' ) . '</option>';
		}
		echo '</select></label>';
		echo '<div><button class="button" type="button" id="donate_btn">' . esc_html( $btntext ) . '</button>';
		echo '<div class="loadtext" style="display:none;">' . __( 'Please wait loading charities', 'rest-cent-donations' ) . '... </div></div>';
		echo '<div class="custom_donation"><span class="extracent" style="display:none">' . __( 'No Donation', 'rest-cent-donations' ) . '</span><span class="cent_value extracent" style="display:none"></span><span style="display:none" class="extracent">' . get_woocommerce_currency_symbol() .
		     '1</span><span style="display:none" class="extracent">' . get_woocommerce_currency_symbol() . '2</span><span style="display:none" class="extracent">' . get_woocommerce_currency_symbol() . '5</span></div>';
		echo '<div class="login_textsession"><p id="login_msg" style="font-size: 16px;text-align:justify;">' . __( 'Are you already a member of Rest-Cent? Then log in here to select your favourite charity and link the donation to your account.', 'rest-cent-donations' ) .
		     '</p><span class="btn btn-primary" data-toggle="modal" data-target="#exampleModalCenter" id="rest_logo"><a href="' . get_option( 'rc_server_url' ) . '/auth/ui/' . esc_attr( $shopid ) . '?redirect=' . urlencode( home_url( $wp->request ) ) .
		     '"><img alt="restcent-logo" style="width: 10%" src="' .
		     plugin_dir_url( __DIR__ ) . 'public/css/1.png"><span>' . __( 'Login with Rest-Cent', 'rest-cent-donations' ) . '</span></a></span></div>';

		echo '</div><br/>';

		?>
        <script>
            jQuery(document).ready(function($) {
                jQuery('#donate_btn').click(function() {
                    var ajax_url = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
                    var pr = $(this).parent().parent().find('.user_charity_amount').val();
                    var ttl = $(this).parent().parent().find('.charities_list option:selected').html();
                    var imgurl = $(this).parent().parent().find('.charities_list option:selected').data('logo');
                    jQuery.ajax({
                        type: 'post',
                        dataType: 'json',
                        url: ajax_url,
                        data: {action: 'add_donation', donation_ammount: pr, title: ttl, img: imgurl, charityId: $('#charities_list').val()},
                        success: function(response) {
                            jQuery('body').trigger('update_checkout'); //location.reload();
                            if (response.responseText.includes('removed')) {
                                jQuery('#donate_btn').html('<?php echo __( 'Donate', 'rest-cent-donations' ) ?>');
                                jQuery('.charities_list option[value=\'cancel\']').remove();
                            } else {
                                jQuery('#donate_btn').html('<?php echo __( 'Update donation amount', 'rest-cent-donations' ) ?>');
                                if (!jQuery('.charities_list option[value=\'cancel\']').length) {
                                    jQuery('.charities_list').append('<option value="cancel">' + "<?php echo __( 'Cancel Donation', 'rest-cent-donations' ) ?>" + '</option>');
                                }
                            }
                        },
                    }).complete(function(response) {
                        jQuery('body').trigger('update_checkout').trigger('wc_fragment_refresh');
                        setTimeout(function() {
                            jQuery('img[alt="restcent-charity-logo"]').attr('src', jQuery('.charities_list option:selected').data('logo-url')).removeAttr('srcset');
                            jQuery('img[alt="restcent-charity-logo"]').closest('li').find('.item-name').html(jQuery('.charities_list option:selected').html());
                            jQuery('.shopping-cart-total .woocommerce-Price-amount').html(jQuery('.order-total .woocommerce-Price-amount').html());
                            jQuery('.eut-cart-total .woocommerce-Price-amount').html(jQuery('.order-total .woocommerce-Price-amount').html());
                        }, 2000);
                        if (response.responseText.includes('removed')) {
                            jQuery('.charities_list option[value=\'cancel\']').remove();
                            jQuery('#donate_btn').html('<?php echo __( 'Donate', 'rest-cent-donations' ) ?>');
                        } else {
                            jQuery('#donate_btn').html('<?php echo __( 'Update donation amount', 'rest-cent-donations' ) ?>');
                            if (!jQuery('.charities_list option[value=\'cancel\']').length) {
                                jQuery('.charities_list').append('<option value="cancel">' + "<?php echo __( 'Cancel Donation', 'rest-cent-donations' ) ?>" + '</option>');
                            }
                        }
                    });
                });
            });
            var e = document.querySelectorAll('option');
            jQuery('select#charities_list').show();
            jQuery('#charities_list').show();
            jQuery('#login_msg').show();
            jQuery('#rest_logo').show();
            jQuery('.shop_table tfoot tr.fee th').addClass('current_charity');
            jQuery(document).on('click', '.woocommerce-checkout button[name=apply_coupon],.woocommerce-checkout a.woocommerce-remove-coupon', function() {
                jQuery('input#donate_checkbox_name').prop('checked', false);
                setTimeout(function() {
                    var total_price = 0;
                    var val = jQuery('tr.order-total').text();
                    var total_price = val.replace(/[^0-9\.]+/g, '');
                    var owner_price = total_price - Math.floor(total_price);
                    jQuery('.owner_donction').text(owner_price.toFixed(2));
                    jQuery('#shop_donation_amount').val(parseFloat(owner_price).toFixed(2));
                }, 3500);
            });

            var currency_symbol = jQuery('#currency_symbol').val();
            jQuery('input#donate_checkbox_name').click(function() {
                if (jQuery(this).is(':checked')) {
                    Cookies.set('donate_checkboxed', 'true');
                    var val = jQuery('tr.order-total').text();
                    var total_price = val.replace(/[^0-9\.]+/g, '');
                    var total_pr = Math.ceil(total_price);

                    var owner_price = total_price - Math.floor(total_price);
                    var new_total = total_pr - total_price;

                    if (owner_price == 0) {
                        var owner_price = 1.00;
                        jQuery('.owner_donction').text(owner_price.toFixed(2));
                        jQuery('#shop_donation_amount').val(parseFloat(owner_price).toFixed(2));
                    } else {
                        jQuery('.owner_donction').text(owner_price.toFixed(2));
                        jQuery('#shop_donation_amount').val(parseFloat(owner_price).toFixed(2));
                    }

                    jQuery('.extracent').show();
                    if (new_total == 0) {
                        var new_total = '1.00';
                        jQuery('#total_amount').val();
                        jQuery('.cent_value').hide();
                        jQuery('#total_amount').val(parseFloat(new_total).toFixed(2));
                    } else {
                        jQuery('#total_amount').hide();
                        jQuery('#total_amount').val(parseFloat(new_total).toFixed(2));
                        jQuery('.cent_value').text(currency_symbol + parseFloat(new_total).toFixed(2));

                    }

                    jQuery('select#charities_list').show();
                    jQuery('#charities_list').show();
                    jQuery('#login_msg').show();
                    jQuery('#rest_logo').show();
                    jQuery('.shop_table tfoot tr.fee th').addClass('current_charity');
                    jQuery('body').trigger('update_checkout');
                    setTimeout(function() {
                        var logo = jQuery('select#charities_list option:selected').attr('data-logo');
                        var logoUrl = jQuery('select#charities_list option:selected').attr('data-logo-url');
                        jQuery('.shop_table tfoot tr.fee th').css('background', 'url(' + logoUrl + ')');
                        jQuery('#charity_logo').val(logo);
                        var charity_name = jQuery('select#charities_list option:selected').text();
                        jQuery('#charity_name').val(charity_name);
                        jQuery('.shop_table tfoot tr.fee th').css('background-size', '65px 50px');
                        jQuery('.shop_table tfoot tr.fee th').css('background-repeat', 'no-repeat');
                        jQuery('.shop_table tfoot tr.fee th').css('background-position', 'left');
                        jQuery('.shop_table tfoot tr.fee th').css('padding-left', '15%');
                    }, 500);

                } else {
                    //
                }
            });

            jQuery('span.extracent').click(function() {
                var value = jQuery(this).text();
                var new_total = value.replace(/[^0-9\.]+/g, '');
                //alert(new_total);
                if (new_total == '') {
                    jQuery('input#donate_checkbox_name').prop('checked', false);
                    jQuery('#total_amount').hide();
                    jQuery('.extracent').hide();
                    jQuery('select#charities_list').hide();
                    jQuery('#charities_list').hide();
                    jQuery('#login_msg').hide();
                    jQuery('#rest_logo').hide();
                    jQuery('body').trigger('update_checkout');
                } else {
                    jQuery('#total_amount').val(parseFloat(new_total).toFixed(2));
                    jQuery('body').trigger('update_checkout');
                    setTimeout(function() {
                        var logo = jQuery('select#charities_list option:selected').attr('data-logo');
                        var logoUrl = jQuery('select#charities_list option:selected').attr('data-logo-url');
                        var charity_name = jQuery('select#charities_list option:selected').text();
                        jQuery('#charity_name').val(charity_name);
                        jQuery('.shop_table tfoot tr.fee th').css('background', 'url(' + logoUrl + ')');
                        jQuery('#charity_logo').val(logo);
                        jQuery('.shop_table tfoot tr.fee th').css('background-size', '65px 50px');
                        jQuery('.shop_table tfoot tr.fee th').css('background-repeat', 'no-repeat');
                        jQuery('.shop_table tfoot tr.fee th').css('background-position', 'left');
                        jQuery('.shop_table tfoot tr.fee th').css('padding-left', '15%');
                    }, 500);
                }

            });
            jQuery('select#charities_list').change(function() {

                var text = jQuery('option:selected', this).text();
                console.log(text);
                var logo = jQuery('option:selected', this).attr('data-logo');
                var logoUrl = jQuery('option:selected', this).attr('data-logo-url');
                jQuery('#charity_logo').val(logo);
                jQuery('#charity_name').val(text);
                jQuery('.shop_table tfoot tr.fee th').text(text);
                jQuery('.shop_table tfoot tr.fee th').css('background', 'url(' + logoUrl + ')');
                jQuery('.shop_table tfoot tr.fee th').css('background-size', '65px 50px');
                jQuery('.shop_table tfoot tr.fee th').css('background-repeat', 'no-repeat');
                jQuery('.shop_table tfoot tr.fee th').css('background-position', 'left');
                jQuery('.shop_table tfoot tr.fee th').css('padding-left', '15%');

                jQuery('#temp_charity_name').val(text);

            });

            var getUrlParameter = function getUrlParameter(sParam) {
                var sPageURL = window.location.search.substring(1),
                    sURLVariables = sPageURL.split('&'),
                    sParameterName,
                    i;

                for (i = 0; i < sURLVariables.length; i++) {
                    sParameterName = sURLVariables[i].split('=');

                    if (sParameterName[0] === sParam) {
                        return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
                    }
                }
            };

			<?php
			if(isset( $_SESSION['restcent']['rc_login_donator'] )) { ?>
            jQuery('.login_textsession').css('visibility', 'hidden');
            jQuery('.login_textsession').css('height', '4em');
			<?php } ?>

            var sub = getUrlParameter('sub');
            if (sub) {
                jQuery('#donate_btn').attr('disabled', 'disabled');
                jQuery('select#charities_list').html('<option>...</option>');
                setTimeout(function() {
                    jQuery('#custom_user_shopid').val(sub);
                    jQuery('.login_textsession').css('visibility', 'hidden');
                    jQuery('.login_textsession').css('height', '4em');
                    var ajax_url = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
                    jQuery.ajax({
                        type: 'POST',
                        dataType: 'json',
                        url: ajax_url,
                        data: {
                            action: 'restcent_source_newshop',
                            newshop_id: sub,
                        },
                        success: function(data) {
                            jQuery('select#charities_list').html(data['option']);
                            jQuery('#charity_name').val(data['name']);
                            jQuery('#charity_logo').val(data['logo']);
                            jQuery('.shop_table tfoot tr.fee th').text(data['name']);
                            jQuery('.shop_table tfoot tr.fee th').css('background', 'url(' + data['logo_url'] + ')');
                            jQuery('.shop_table tfoot tr.fee th').css('background-size', '65px 50px');
                            jQuery('.shop_table tfoot tr.fee th').css('background-repeat', 'no-repeat');
                            jQuery('.shop_table tfoot tr.fee th').css('background-position', 'left');
                            jQuery('.shop_table tfoot tr.fee th').css('padding-left', '15%');
                            jQuery('.blockUI.blockOverlay').hide();
                            jQuery('#donate_btn').removeAttr('disabled');
                            jQuery('#donate_btn').trigger('click');
                            jQuery('.loadtext').hide();

                        },
                    });
                }, 1700);
            }
        </script>
		<?php
	}

	public function restcent_source_newshop() {
		$this->rc_register_session();
		global $wpdb;

		$newshop_id = sanitize_text_field( $_POST['newshop_id'] );
		$email      = get_option( 'rc_shop_email' );
		$password   = get_option( 'rc_shop_password' );
		$rc_shop_id = $newshop_id;
		$fields     = [ 'email' => $email, 'password' => $password ];

		$data = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		if ( $data ) {
			$data                       = json_decode( $data, true );
			$restcent_shop_accessToken  = $data['accessToken'];
			$rc_shop_idToken            = $data['idToken'];
			$restcent_shop_refreshToken = $data['refreshToken'];
			if ( ! empty( $restcent_shop_accessToken ) ) {
				$token                                    = $restcent_shop_accessToken;
				$shopid                                   = $rc_shop_id;
				$idtoken                                  = $rc_shop_idToken;
				$refreshToken                             = $restcent_shop_refreshToken;
				$_SESSION['restcent']['rc_login_donator'] = $newshop_id;
				$headers                                  = [
					'Authorization' => 'Bearer ' . $token,
					'idToken'       => $idtoken,
					'refreshToken'  => $refreshToken
				];

				$result = \Rest_Cent_Admin::rcRequest( [], '/charities/user/' . $rc_shop_id, 'GET', $headers );
				if ( $result ) {
					$responseData   = json_decode( $result );
					$html['option'] = '';

					$table_name = $wpdb->prefix . 'restcent_charities';
					$wpdb->delete( $table_name, [ 'userid' => $shopid ] );
					foreach ( $responseData as $charityItem ) {
						if ( ! $charityItem->name && ! $charityItem->id ) {
							$imid = $this->add_media_rc( $charityItem->logo_url, $charityItem->id );
							$wpdb->insert( $table_name, [ 'userid' => $rc_shop_id, 'restcent_charities_id' => $charityItem->id, 'name' => $charityItem->name, 'logo_url' => $imid ] );

							$html['option']   .= '<option data-logo-url="' . wp_get_attachment_url( $imid ) . '" data-logo="' . esc_attr( $imid ) . '" value="' . esc_attr( $charityItem->id ) . '">' . esc_attr( $charityItem->name ) . ' </option> ';
							$html['logo']     = $imid;
							$html['logo_url'] = $charityItem->logo_url;
							$html['id']       = $charityItem->id;
							$html['name']     = $charityItem->name;
						}
					}

					$product_id_1 = wc_get_product_id_by_sku( 'rc-donation' );
					foreach ( WC()->cart->get_cart() as $cart_key => $cart_item ) {
						if ( $cart_item['data']->get_id() == $product_id_1 ) {
							$html['option'] .= '<option value="cancel">' . __( 'Cancel Donation', 'rest-cent-donations' ) . '</option>';
						}
					}
					echo json_encode( $html );
				}
			} else {
				echo esc_html( $data['message'] );
			}
		}

		exit;
	}

	public function woo_add_cart_fee() {
		$this->rc_register_session();
		global $wpdb, $woocommerce;
		// @session_start();
		$product_id_1 = wc_get_product_id_by_sku( 'rc-donation' );
		$custom_price = sanitize_text_field( @$_SESSION['restcent']['donation_ammount'] ?? 1 );
		$title        = sanitize_text_field( $_SESSION['restcent']['donation_title'] );
		$imgid        = sanitize_text_field( $_SESSION['restcent']['donation_image'] );

		foreach ( WC()->cart->cart_contents as $key => $value ) {
			if ( $title && $value['data']->get_id() === $product_id_1 ) {
				$value['data']->set_price( $custom_price );
				$value['data']->set_name( $title );
				$value['data']->set_image_id( $imgid );
			}
		}
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( sanitize_text_field( $_POST['post_data'] ), $post_data );
		} else {
			$post_data = $_POST;
		}

		$rc_selected_shop_charity = isset( $_SESSION['restcent']['selected_charity'] ) ? sanitize_text_field( $_SESSION['restcent']['selected_charity'] ) : get_option( 'rc_selected_shop_charity' );
		$queryup                  = "select * from `{$wpdb->prefix}restcent_charities` WHERE restcent_charities_id = '{$rc_selected_shop_charity}'";
		$charityItem              = $wpdb->get_row( $queryup );
		if ( isset( $post_data['donate_checkbox_name'] ) && $post_data['donate_checkbox_name'] === 'on' ) {
			$user_charity_amount = $post_data['user_charity_amount'];
			if ( ! empty( $charityItem->name ) ) {
				$charity_name = $post_data['temp_charity_name'];
				if ( empty( $charity_name ) ) {
					$charity_name = $charityItem->name;
				}
				$woocommerce->cart->add_fee( $charity_name, $user_charity_amount );
			} else {
				$woocommerce->cart->add_fee( __( 'Donate', 'rest-cent-donations' ), $user_charity_amount );
			}
		} else {
			$fees = $woocommerce->cart->get_fees();
			foreach ( $fees as $key => $fee ) {
				if ( $fees[ $key ]->name === __( 'Donate', 'rest-cent-donations' ) ) {
					unset( $fees[ $key ] );
				}
			}
			$woocommerce->cart->fees_api()->set_fees( $fees );
		}
	}

	public function save_order_custom_meta_data( $order, $data ) {
		$this->rc_register_session();
		global $wpdb, $woocommerce;
		$custom_user_shopid       = '';
		$rc_shop_donation_enabled = get_option( 'rc_shop_donation_enabled' );
		if ( isset( $_POST['restcent_shop_accessToken'] ) && ! empty( sanitize_text_field( $_POST['rc_shop_idToken'] ) ) && ! empty( sanitize_text_field( $_POST['restcent_shop_refreshToken'] ) ) ) {
			$order->update_meta_data( 'restcent_shop_accessToken', sanitize_text_field( $_POST['restcent_shop_accessToken'] ) );
			$order->update_meta_data( 'rc_shop_idToken', sanitize_text_field( $_POST['rc_shop_idToken'] ) );
			$order->update_meta_data( 'restcent_shop_refreshToken', sanitize_text_field( $_POST['restcent_shop_refreshToken'] ) );
		}
		if ( isset( $_SESSION['restcent']['donation_title'] ) ) {
			$order->update_meta_data( 'user_charity_name', sanitize_text_field( $_SESSION['restcent']['donation_title'] ) );

			if ( isset( $_POST['custom_user_shopid'] ) && ! empty( sanitize_text_field( $_POST['custom_user_shopid'] ) ) ) {
				$order->update_meta_data( 'custom_user_shopid', sanitize_text_field( $_POST['custom_user_shopid'] ) );
				$custom_user_shopid = sanitize_text_field( $_POST['custom_user_shopid'] );
			}
			if ( isset( $_POST['charity_logo'] ) && ! empty( sanitize_text_field( $_POST['charity_logo'] ) ) ) {
				$order->update_meta_data( 'user_donation_charity_logo', sanitize_text_field( $_POST['charity_logo'] ) );
			}
			if ( isset( $_POST['charities_list'] ) && ! empty( sanitize_text_field( $_POST['charities_list'] ) ) ) {
				$order->update_meta_data( 'user_charity_id', sanitize_text_field( $_POST['charities_list'] ) );
			}
			if ( isset( $_POST['user_charity_amount'] ) && ! empty( sanitize_text_field( $_POST['user_charity_amount'] ) ) ) {
				$order->update_meta_data( 'user_charity_amount', sanitize_text_field( $_POST['user_charity_amount'] ) );
			}
			if ( $rc_shop_donation_enabled === 'yes' ) {
				$order->update_meta_data( 'shop_donation_charity_id', get_option( 'rc_selected_shop_charity' ) );
				$charityAmount = (float) ( $_POST['user_charity_amount'] ?? 0 );
				$cattotal      = $woocommerce->cart->total - $charityAmount;

				$rc_min_cart_value_donation = get_option( 'rc_min_cart_value_donation' );
				$whole                      = (int) $cattotal;
				$frac                       = $cattotal - $whole;
				$owner_donation_amout       = round( $frac, 2 );
				if ( ! $owner_donation_amout ) {
					$owner_donation_amout = 1;
				}
				if ( $rc_min_cart_value_donation <= $cattotal ) {
					$order->update_meta_data( 'shop_donation_amount', round( $owner_donation_amout, 2 ) );
				}
			}

			$email    = get_option( 'rc_shop_email' );
			$password = get_option( 'rc_shop_password' );

			$fields   = [ 'email' => $email, 'password' => $password ];
			$data     = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );
			$order_id = $order->save();

			if ( ! $data ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'restcent_donations';
				if ( ! empty( sanitize_text_field( $_POST['charities_list'] ) ) && ! empty( sanitize_text_field( $_POST['user_charity_amount'] ) ) ) {
					$user_charity_amount = round( sanitize_text_field( $_POST['user_charity_amount'] ), 2 );
					$charity             = sanitize_text_field( $_POST['charities_list'] );
					$wpdb->insert( $table_name, [ 'order_id' => $order_id, 'owner_donation' => 'false', 'amount' => $user_charity_amount, 'charity_id' => $charity, 'server_status' => 'not connected', 'created_at' => date( 'Y-m-d h:i:s' ), 'status' => '1' ] );
				}
				if ( $rc_shop_donation_enabled === 'yes' ) {
					$shop_donation_amount     = round( sanitize_text_field( $_POST['shop_donation_amount'] ), 2 );
					$rc_selected_shop_charity = get_option( 'rc_selected_shop_charity' );
					$wpdb->insert( $table_name, [ 'order_id' => $order_id, 'amount' => $shop_donation_amount, 'charity_id' => $rc_selected_shop_charity, 'server_status' => 'not connected', 'owner_donation' => 'true', 'created_at' => date( 'Y-m-d h:i:s' ), 'status' => '1' ] );
				}
			} else {
				$data         = json_decode( $data, true );
				$token        = $data['accessToken'];
				$idtoken      = $data['idToken'];
				$refreshToken = $data['refreshToken'];

				$headers = [
					'Authorization' => 'Bearer ' . $token,
					'idToken'       => $idtoken,
					'refreshToken'  => $refreshToken
				];

				$shopid = get_option( 'rc_shop_id' );
				if ( ! empty( sanitize_text_field( $_POST['charities_list'] ) ) && ! empty( sanitize_text_field( $_POST['user_charity_amount'] ) ) ) {
					$user_charity_amount = round( sanitize_text_field( $_POST['user_charity_amount'] ), 2 );
					$charity             = sanitize_text_field( $_POST['charities_list'] );
					if ( empty( $custom_user_shopid ) ) {
						$donator = null;
					} else {
						$donator = $custom_user_shopid;
					}

					$postData = [
						'donator'               => $donator,
						'amount'                => (float) $user_charity_amount,
						'charity'               => $charity,
						'ext_reference'         => (string) $order_id,
						'owner_donation'        => false,
						'status'                => 'PENDING',
						'source_payment_status' => 'PENDING',
						'source_order_status'   => 'PENDING'
					];
					$data     = Rest_Cent_Admin::rcPost( $postData, '/donations', $headers );

					if ( ! $data ) {
						global $wpdb;
						$table_name = $wpdb->prefix . 'restcent_donations';
						$wpdb->insert( $table_name, [
							'order_id'       => $order_id,
							'owner_donation' => 0,
							'donator'        => $custom_user_shopid,
							'amount'         => $user_charity_amount,
							'charity_id'     => $charity,
							'server_status'  => 'not connected',
							'rc_donation_id' => '',
							'created_at'     => date( 'Y-m-d h:i:s' ),
							'status'         => '1'
						] );
					} else {
						global $wpdb;
						$data = json_decode( $data );
						update_post_meta( $order_id, 'rc_donation_id', $data->rc_donation_id );
						$table_name = $wpdb->prefix . 'restcent_donations';
						$wpdb->insert( $table_name, [
							'order_id'       => $order_id,
							'owner_donation' => 0,
							'donator'        => $custom_user_shopid,
							'amount'         => $user_charity_amount,
							'charity_id'     => $charity,
							'server_status'  => 'connected',
							'rc_donation_id' => $data->rc_donation_id,
							'created_at'     => date( 'Y-m-d h:i:s' ),
							'status'         => '1'
						] );
					}
				}

				if ( $rc_shop_donation_enabled === 'yes' && ! empty( sanitize_text_field( $_POST['shop_donation_amount'] ) ) ) {
					$shop_donation_amount     = round( sanitize_text_field( $_POST['shop_donation_amount'] ), 2 );
					$rc_selected_shop_charity = get_option( 'rc_selected_shop_charity' );
					$charitiequeryup          = "select * from `{$wpdb->prefix}restcent_charities` WHERE restcent_charities_id = '{$rc_selected_shop_charity}' AND userid = '{$shopid}'";
					$charities_selected       = $wpdb->get_row( $charitiequeryup );
					$order_id_new             = $order_id;
					$postData                 = [
						'donator'               => $shopid,
						'amount'                => (float) $shop_donation_amount,
						'charity'               => $rc_selected_shop_charity,
						'ext_reference'         => (string) $order_id_new,
						'owner_donation'        => true,
						'status'                => 'PENDING',
						'source_payment_status' => 'PENDING',
						'source_order_status'   => 'PENDING'
					];

					$data = Rest_Cent_Admin::rcPost( $postData, '/donations', $headers );

					if ( ! $data ) {
						global $wpdb;
						$table_name = $wpdb->prefix . 'restcent_donations';
						$wpdb->insert( $table_name, [
							'order_id'       => $order_id_new,
							'donator'        => $shopid,
							'amount'         => $shop_donation_amount,
							'charity_id'     => $rc_selected_shop_charity,
							'server_status'  => 'not connected',
							'owner_donation' => 1,
							'created_at'     => date( 'Y-m-d h:i:s' ),
							'status'         => '1'
						] );
					} else {
						$data = json_decode( $data );
						if ( $data->rc_donation_id ) {
							update_post_meta( $order_id_new, 'shop_rc_donation_id', $data->rc_donation_id );
							global $wpdb;
							$table_name = $wpdb->prefix . 'restcent_donations';
							$wpdb->insert( $table_name, [
								'order_id'       => $order_id_new,
								'donator'        => $shopid,
								'amount'         => $shop_donation_amount,
								'charity_id'     => $rc_selected_shop_charity,
								'server_status'  => 'connected',
								'rc_donation_id' => $data->rc_donation_id,
								'owner_donation' => 1,
								'created_at'     => date( 'Y-m-d h:i:s' ),
								'status'         => '1'
							] );
						}
					}

					$refresh_interval = get_option( 'rc_charities_refresh_interval' );
					if ( $refresh_interval === 'after every purchase' ) {
						$rc_selected_shop_charity = get_option( 'rc_selected_shop_charity' );
						$charitiequeryup          = 'select * from `' . $wpdb->prefix . "restcent_charities` WHERE restcent_charities_id !='$rc_selected_shop_charity' AND userid = '{$shopid}' ORDER BY RAND()";
						$charities_selected       = $wpdb->get_row( $charitiequeryup );
						if ( ! empty( $charities_selected ) ) {
							update_option( 'rc_selected_shop_charity', $charities_selected->restcent_charities_id );
						}
					}
				}
			}
		}
	}

	public function isa_add_every_three_minutes( $schedules ) {
		$time_s           = 60 * 60 * 12;
		$refresh_interval = get_option( 'rc_charities_refresh_interval' );
		if ( $refresh_interval === '12 hours' ) {
			$time_s = 60 * 60 * 12;
		}
		if ( $refresh_interval === '24 hours' ) {
			$time_s = 60 * 60 * 24;
		}
		if ( $refresh_interval === '1 week' ) {
			$time_s = 60 * 60 * 24 * 7;
		}
		if ( $refresh_interval === '2 weeks' ) {
			$time_s = 60 * 60 * 24 * 14;
		}
		if ( $refresh_interval === '1 month' ) {
			$time_s = 60 * 60 * 24 * 30;
		}
		$schedules['every_three_minutes'] = [
			'interval' => $time_s,
			'display'  => __( 'Every 3 Minutes', 'rest-cent-donations' )
		];

		return $schedules;
	}

	public function every_three_minutes_event_func() {
		global $wpdb;
		$refresh_interval = get_option( 'rc_charities_refresh_interval' );
		$seleted_id       = get_option( 'rc_selected_shop_charity' );
		$shopId           = get_option( 'rc_shop_id' );
		if ( $refresh_interval !== 'none' ) {
			$table_name             = $wpdb->prefix . 'restcent_charities';
			$querys                 = "SELECT * FROM " . $table_name . " WHERE restcent_charities_id NOT IN ('$seleted_id') AND userid = '" . $shopId . "' ORDER BY RAND() LIMIT 1";
			$selectedCharity        = $wpdb->get_row( $querys );
			$charities_value_select = $selectedCharity->restcent_charities_id;
			update_option( 'rc_selected_shop_charity', $charities_value_select );
		}
	}

	public function so_payment_complete( $order_id ) {
		global $wpdb, $woocommerce;
		$order      = wc_get_order( $order_id );
		$user       = $order->get_user();
		$email      = get_option( 'rc_shop_email' );
		$password   = get_option( 'rc_shop_password' );
		$rc_shop_id = sanitize_text_field( $_POST['shopid'] );
		$fields     = [ 'email' => $email, 'password' => $password ];
		$data       = Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		if ( $data ) {
			$data         = json_decode( $data, true );
			$token        = $data['accessToken'];
			$idtoken      = $data['idToken'];
			$refreshToken = $data['refreshToken'];

			$headers             = [
				'Authorization' => 'Bearer ' . $token,
				'idToken'       => $idtoken,
				'refreshToken'  => $refreshToken
			];
			$shopid              = get_option( 'rc_shop_id' );
			$transaction_id      = get_post_meta( $order_id, '_transaction_id', true );
			$rc_donation_id      = get_post_meta( $order_id, 'rc_donation_id', true );
			$shop_rc_donation_id = get_post_meta( $order_id, 'shop_rc_donation_id', true );
			$table_name          = $wpdb->prefix . 'restcent_donations';
			$statusData          = [
				'status'                => 'CONFIRMED',
				'source_payment_status' => 'CONFIRMED',
				'source_order_status'   => 'CONFIRMED'
			];
			if ( ! empty( $shop_rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '2' ], [ 'rc_donation_id' => $shop_rc_donation_id ] );
				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $shop_rc_donation_id . '/status', 'PUT', $headers );
				update_post_meta( $order_id, 'shop_rc_donation_id_status', 'CONFIRMED' );
			}

			if ( ! empty( $rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '2' ], [ 'rc_donation_id' => $rc_donation_id ] );
				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $rc_donation_id . '/status', 'PUT', $headers );
				update_post_meta( $order_id, 'rc_donation_id_status', 'CONFIRMED' );
			}
		}
	}

	public function action_woocommerce_cancelled_order( $order_id ) {
		global $wpdb, $woocommerce;
		$order      = wc_get_order( $order_id );
		$email      = get_option( 'rc_shop_email' );
		$password   = get_option( 'rc_shop_password' );
		$rc_shop_id = sanitize_text_field( $_POST['shopid'] );
		$fields     = [ 'email' => $email, 'password' => $password ];
		$data       = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		if ( $data ) {
			$data         = json_decode( $data, true );
			$token        = $data['accessToken'];
			$idtoken      = $data['idToken'];
			$refreshToken = $data['refreshToken'];

			$headers             = [
				'Authorization' => 'Bearer ' . $token,
				'idToken'       => $idtoken,
				'refreshToken'  => $refreshToken
			];
			$shopid              = get_option( 'rc_shop_id' );
			$transaction_id      = get_post_meta( $order_id, '_transaction_id', true );
			$rc_donation_id      = get_post_meta( $order_id, 'rc_donation_id', true );
			$shop_rc_donation_id = get_post_meta( $order_id, 'shop_rc_donation_id', true );
			$table_name          = $wpdb->prefix . 'restcent_donations';
			$statusData          = [
				'status'                => 'CANCELLED',
				'source_payment_status' => 'CANCELLED',
				'source_order_status'   => 'CANCELLED'
			];
			if ( ! empty( $shop_rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '4' ], [ 'rc_donation_id' => $shop_rc_donation_id ] );
				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $shop_rc_donation_id . '/status', 'PUT', $headers );
				update_post_meta( $order_id, 'shop_rc_donation_id_status', 'CANCELLED' );
			}

			if ( ! empty( $rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '4' ], [ 'rc_donation_id' => $rc_donation_id ] );
				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $rc_donation_id . '/status', 'PUT', $headers );
				update_post_meta( $order_id, 'rc_donation_id_status', 'CANCELLED' );
			}
		}
	}

	public function isa_add_every_twinty_four_hours( $schedules ) {
		$time_s                               = 60 * 60 * 24;
		$schedules['every_twinty_four_hours'] = [
			'interval' => $time_s,
			'display'  => __( 'Daily', 'rest-cent-donations' )
		];

		return $schedules;
	}

	public function restcent_source_outdated_transaction_cron() {
		global $wpdb, $woocommerce;

		$email      = get_option( 'rc_shop_email' );
		$password   = get_option( 'rc_shop_password' );
		$rc_shop_id = sanitize_text_field( $_POST['shopid'] );

		$fields = [ 'email' => $email, 'password' => $password ];
		$data   = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		if ( $data ) {
			$data         = json_decode( $data, true );
			$token        = $data['accessToken'];
			$idtoken      = $data['idToken'];
			$refreshToken = $data['refreshToken'];

			$headers = [
				'Authorization' => 'Bearer ' . $token,
				'idToken'       => $idtoken,
				'refreshToken'  => $refreshToken
			];

			$shopid = get_option( 'rc_shop_id' );

			/* update donations on servers */
			$table_name = $wpdb->prefix . 'restcent_donations';
			$sql        = "SELECT * FROM '$table_name' WHERE server_status = 'not connected'";
			$results    = $wpdb->get_results( $sql );
			foreach ( $results as $result ) {
				$donation_amount          = $result->amount;
				$rc_selected_shop_charity = $result->charity_id;
				$order_id_new             = $result->order_id;
				$owner_donation           = (bool) $result->owner_donation;
				$donator                  = $result->donator;
				$ownerpostData            = [
					'donator'        => $donator,
					'amount'         => $donation_amount,
					'charity'        => $rc_selected_shop_charity,
					'ext_reference'  => (string) $order_id_new,
					'owner_donation' => $owner_donation
				];

				$data = Rest_Cent_Admin::rcPost( $ownerpostData, '/donations', $headers );
				if ( $data ) {
					$data = json_decode( $data );
					if ( ! empty( $data->rc_donation_id ) ) {
						if ( $owner_donation ) {
							update_post_meta( $order_id_new, 'shop_rc_donation_id', $data->rc_donation_id );
						} else {
							update_post_meta( $order_id_new, 'rc_donation_id', $data->rc_donation_id );
						}
					}
				}
			}
			/* end update donations on server */

			$orders = get_posts( [
				'post_type'      => 'shop_order',
				'posts_per_page' => '-1',
				'post_status'    => 'wc-processing',
			] );
			foreach ( $orders as $order ) {
				//print_r($order);
				$orderdate = date( 'Y-m-d H:i:s', strtotime( $order->post_date ) );
				//$str = "Jul 02 2013";
				$str = strtotime( date( 'Y-m-d H:i:s' ) ) - ( strtotime( $orderdate ) );
				//echo $today=date("Y-m-d H:i:s");
				$day = floor( $str / 3600 / 24 );

				if ( $day >= 1 ) {
					$order_id            = $order->ID;
					$transaction_id      = get_post_meta( $order_id, '_transaction_id', true );
					$rc_donation_id      = get_post_meta( $order_id, 'rc_donation_id', true );
					$shop_rc_donation_id = get_post_meta( $order_id, 'shop_rc_donation_id', true );
					$table_name          = $wpdb->prefix . 'restcent_donations';

					$user_sataus_donation = 'select * from `' . $wpdb->prefix . "restcent_donations` WHERE rc_donation_id ='$rc_donation_id'";
					$usersataus_donation  = $wpdb->get_row( $user_sataus_donation );
					$user_status          = (bool) $usersataus_donation->status;

					$sataus_donation = 'select * from `' . $wpdb->prefix . "restcent_donations` WHERE rc_donation_id ='$shop_rc_donation_id'";
					$sataus_donation = $wpdb->get_row( $sataus_donation );
					$ownerstatus     = (bool) $sataus_donation->status;
					$statusData      = [
						'status'                => 'ABANDONED',
						'source_payment_status' => 'ABANDONED',
						'source_order_status'   => 'ABANDONED'
					];
					if ( ! empty( $shop_rc_donation_id ) && $ownerstatus ) {
						$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '3' ], [ 'rc_donation_id' => $shop_rc_donation_id ] );
						$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $shop_rc_donation_id . '/status', 'PUT', $headers );
						update_post_meta( $order_id, 'shop_rc_donation_id_status', 'ABANDONED' );
					}

					if ( ! empty( $rc_donation_id ) && $user_status ) {
						$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '3' ], [ 'rc_donation_id' => $rc_donation_id ] );
						$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $rc_donation_id . '/status', 'PUT', $headers );
						update_post_meta( $order_id, 'rc_donation_id_status', 'ABANDONED' );
					}
				}
			}
		}
	}

	public function isa_add_every_six_hours( $schedules ) {
		$time_s           = 60000000 * 60 * 12;
		$refresh_interval = get_option( 'rc_charities_refresh_interval' );
		if ( $refresh_interval === '6 hours' ) {
			$time_s = 60 * 6;
		}
		if ( $refresh_interval === '12 hours' ) {
			$time_s = 60 * 12;
		}
		if ( $refresh_interval === '24 hours' ) {
			$time_s = 60 * 24;
		}
		if ( $refresh_interval === '1 week' ) {
			$time_s = 60 * 60 * 24 * 7;
		}
		if ( $refresh_interval === '2 weeks' ) {
			$time_s = 60 * 60 * 24 * 14;
		}
		if ( $refresh_interval === '1 month' ) {
			$time_s = 60 * 60 * 24 * 30;
		}
		$schedules['every_six_hours'] = [
			'interval' => $time_s,
			'display'  => __( 'Every minute', 'rest-cent-donations' )
		];

		return $schedules;
	}

	public function every_six_hours_event_func() {
		update_option( 'Owner_Donation_time', date( 'h:i:sa' ) );
		global $wpdb;
		$email      = get_option( 'rc_shop_email' );
		$password   = get_option( 'rc_shop_password' );
		$rc_shop_id = get_option( 'rc_shop_id' );
		$fields     = [ 'email' => $email, 'password' => $password ];
		$data       = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		// authenticate user before changing charity
		if ( $data ) {
			$rc_selected_shop_charity = get_option( 'rc_selected_shop_charity' );
			$table_name               = $wpdb->prefix . 'restcent_charities';
			$querys                   = 'SELECT * FROM ' . $table_name . " WHERE restcent_charities_id NOT IN ('$rc_selected_shop_charity') AND userid = '" . $rc_shop_id . "' ORDER BY RAND() LIMIT 1";
			$charities_selected       = $wpdb->get_row( $querys );
			if ( ! empty( $charities_selected ) ) {
				$charities_value_select = $charities_selected->restcent_charities_id;
				update_option( 'rc_selected_shop_charity', $charities_value_select );
			}
		}
	}

	public function so_32457241_before_order_itemmeta( $item_id, $item, $_product ) {
		global $post, $wpdb;
		$order_id  = $post->ID;
		$charityId = get_post_meta( $order_id, 'user_charity_id', true );
		if ( $charityId ) {
			$queryup    = "select * from `{$wpdb->prefix}restcent_charities` WHERE restcent_charities_id = '{$charityId}' LIMIT 1";
			$row        = $wpdb->get_row( $queryup );
			$chartiylog = $row->logo_url;
			if ( ! empty( $chartiylog ) ) {
				$logoUrl = wp_get_attachment_url( $chartiylog );
				?>
                <script>
                    jQuery(document).ready(function($) {
                        jQuery('.admin_chrt_logo').closest('tr').find('img').attr('src', '<?php echo esc_url($logoUrl); ?>');
                        jQuery('.admin_chrt_logo').remove();
                    });
                </script>
				<?php
			}
		}
	}

	public function tcg_tracking_box() {
		add_meta_box(
			'owner_donation_details',
			'Owner Donation Details',
			'tcg_meta_box_callback',
			'shop_order',
			'normal',
			'default'
		);
	}

	// Callback
	public function tcg_meta_box_callback( $post ) {
		$shop_donation_charity_name = get_post_meta( $post->ID, 'shop_donation_charity_name', true );
		$shop_donation_amount       = get_post_meta( $post->ID, 'shop_donation_amount', true );
		$shop_donation_charity_logo = get_post_meta( $post->ID, 'shop_donation_charity_logo', true );
		if ( ! empty( $shop_donation_charity_logo ) ) {
			$Charity_image = '<img style="margin-left:16%;" width="100" src="' . wp_get_attachment_url( $shop_donation_charity_logo ) . '">';
		} else {
			$Charity_image = '';
		}
		if ( ! empty( $shop_donation_amount ) ) {
			echo '<div class="">';
			echo '<label><strong> ' . __( 'Owner Donation Charity', 'rest-cent-donations' ) . ' :</strong></label> <span> ' . esc_attr( $shop_donation_charity_name ) . ' ' . esc_attr( $Charity_image ) . ' </span><br>';
			echo '<label><strong> ' . __( 'Owner Donation Amount', 'rest-cent-donations' ) . ' :</strong></label><span> ' . esc_attr( $shop_donation_amount ) . '</span>';
			echo '</div>';
		}
	}

	public function order_processing_donaton_processing( $order_id ) {
		global $wpdb, $woocommerce;

		$email      = get_option( 'rc_shop_email' );
		$password   = get_option( 'rc_shop_password' );
		$rc_shop_id = sanitize_text_field( $_POST['shopid'] );
		$fields     = [ 'email' => $email, 'password' => $password ];
		$data       = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		if ( $data ) {
			$data         = json_decode( $data, true );
			$token        = $data['accessToken'];
			$idtoken      = $data['idToken'];
			$refreshToken = $data['refreshToken'];

			$order               = wc_get_order( $order_id );
			$user                = $order->get_user();
			$shopid              = get_option( 'rc_shop_id' );
			$transaction_id      = get_post_meta( $order_id, '_transaction_id', true );
			$rc_donation_id      = get_post_meta( $order_id, 'rc_donation_id', true );
			$shop_rc_donation_id = get_post_meta( $order_id, 'shop_rc_donation_id', true );
			$table_name          = $wpdb->prefix . 'restcent_donations';
			$headers             = [
				'Authorization' => 'Bearer ' . $token,
				'idToken'       => $idtoken,
				'refreshToken'  => $refreshToken
			];

			$statusData = [
				'status'                => 'PENDING',
				'source_payment_status' => 'PENDING',
				'source_order_status'   => 'PENDING'
			];
			if ( ! empty( $rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'order_id' => $order_id, 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '3' ], [ 'rc_donation_id' => $rc_donation_id ] );
				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $rc_donation_id . '/status', 'PUT', $headers );
				if ( $data ) {
					$data = json_decode( $data );
					update_post_meta( $order_id, 'rc_donation_id_status', 'PENDING' );
				}
			}

			if ( ! empty( $shop_rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'transaction_id' => $transaction_id, 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '3' ], [ 'rc_donation_id' => $shop_rc_donation_id ] );
				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $shop_rc_donation_id . '/status', 'PUT', $headers );
				if ( $data ) {
					$data = json_decode( $data );
					update_post_meta( $order_id, 'shop_rc_donation_id_status', 'PENDING' );
				}
			}
		}
	}

	public function order_complete_donaton_complete( $order_id ) {
		global $wpdb, $woocommerce;

		$email    = get_option( 'rc_shop_email' );
		$password = get_option( 'rc_shop_password' );

		$fields = [ 'email' => $email, 'password' => $password ];
		$data   = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		if ( $data ) {
			$data         = json_decode( $data, true );
			$token        = $data['accessToken'];
			$idtoken      = $data['idToken'];
			$refreshToken = $data['refreshToken'];

			$order               = wc_get_order( $order_id );
			$user                = $order->get_user();
			$shopid              = get_option( 'rc_shop_id' );
			$transaction_id      = get_post_meta( $order_id, '_transaction_id', true );
			$rc_donation_id      = get_post_meta( $order_id, 'rc_donation_id', true );
			$shop_rc_donation_id = get_post_meta( $order_id, 'shop_rc_donation_id', true );
			$table_name          = $wpdb->prefix . 'restcent_donations';
			$headers             = [
				'Authorization' => 'Bearer ' . $token,
				'idToken'       => $idtoken,
				'refreshToken'  => $refreshToken
			];
			$statusData          = [
				'status'                => 'CONFIRMED',
				'source_payment_status' => 'CONFIRMED',
				'source_order_status'   => 'CONFIRMED'
			];
			if ( ! empty( $rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'order_id' => $order_id, 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '2' ], [ 'rc_donation_id' => $rc_donation_id ] );
				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $rc_donation_id . '/status', 'PUT', $headers );
				if ( $data ) {
					$data = json_decode( $data );
					update_post_meta( $order_id, 'rc_donation_id_status', 'CONFIRMED' );
				}
			}

			if ( ! empty( $shop_rc_donation_id ) ) {
				$wpdb->update( $table_name, [ 'transaction_id' => $transaction_id, 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '2' ], [ 'rc_donation_id' => $shop_rc_donation_id ] );

				$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $shop_rc_donation_id . '/status', 'PUT', $headers );
				if ( $data ) {
					$data = json_decode( $data );
					update_post_meta( $order_id, 'shop_rc_donation_id_status', 'CONFIRMED' );
				}
			}
		}
	}

	public function restcent_source_outdated_transactions() {
		global $wpdb, $woocommerce;
		//$order = wc_get_order( $order_id );
		$email    = get_option( 'rc_shop_email' );
		$password = get_option( 'rc_shop_password' );

		$fields = [ 'email' => $email, 'password' => $password ];
		$data   = \Rest_Cent_Admin::rcPost( $fields, '/auth/authenticate/' );

		if ( $data ) {
			$data         = json_decode( $data, true );
			$statusCode   = $data['statusCode'];
			$token        = $data['accessToken'];
			$idtoken      = $data['idToken'];
			$refreshToken = $data['refreshToken'];

			$shopid = get_option( 'rc_shop_id' );
			$orders = get_posts( [
				'post_type'      => 'shop_order',
				'posts_per_page' => '-1',
				'post_status'    => 'wc-processing',
			] );
			foreach ( $orders as $order ) {
				//print_r($order);
				$orderdate = date( 'Y-m-d H:i:s', strtotime( $order->post_date ) );
				//$str = "Jul 02 2013";
				$str = strtotime( date( 'Y-m-d H:i:s' ) ) - ( strtotime( $orderdate ) );
				//echo $today=date("Y-m-d H:i:s");
				$day = floor( $str / 3600 / 24 );

				if ( $day >= 1 ) {
					$order_id            = $order->ID;
					$transaction_id      = get_post_meta( $order_id, '_transaction_id', true );
					$rc_donation_id      = get_post_meta( $order_id, 'rc_donation_id', true );
					$shop_rc_donation_id = get_post_meta( $order_id, 'shop_rc_donation_id', true );
					$table_name          = $wpdb->prefix . 'restcent_donations';

					$user_sataus_donation = 'select * from `' . $wpdb->prefix . "restcent_donations` WHERE rc_donation_id ='$rc_donation_id'";
					$usersataus_donation  = $wpdb->get_row( $user_sataus_donation );
					$user_status          = $usersataus_donation->status;

					$sataus_donation = 'select * from `' . $wpdb->prefix . "restcent_donations` WHERE rc_donation_id ='$shop_rc_donation_id'";
					$sataus_donation = $wpdb->get_row( $sataus_donation );
					$ownerstatus     = (bool) $sataus_donation->status;
					$headers         = [
						'Authorization' => 'Bearer ' . $token,
						'idToken'       => $idtoken,
						'refreshToken'  => $refreshToken
					];
					$statusData      = [
						'status'                => 'ABANDONED',
						'source_payment_status' => 'ABANDONED',
						'source_order_status'   => 'ABANDONED'
					];
					if ( ! empty( $shop_rc_donation_id ) && $ownerstatus ) {
						$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '3' ], [ 'rc_donation_id' => $shop_rc_donation_id ] );

						$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $rc_donation_id . '/status', 'PUT', $headers );
						if ( $data ) {
							$data = json_decode( $data );
							update_post_meta( $order_id, 'rc_donation_id_status', 'ABANDONED' );
						}
					}

					$shop_rc_donation_id = get_post_meta( $order_id, 'shop_rc_donation_id', true );
					if ( ! empty( $shop_rc_donation_id ) ) {
						$wpdb->update( $table_name, [ 'updated_at' => date( 'Y-m-d h:i:s' ), 'status' => '3' ], [ 'rc_donation_id' => $shop_rc_donation_id ] );
						$data = Rest_Cent_Admin::rcRequest( $statusData, '/donations/' . $shop_rc_donation_id . '/status', 'PUT', $headers );
						if ( $data ) {
							$data = json_decode( $data );
							update_post_meta( $order_id, 'shop_rc_donation_id_status', 'ABANDONED' );
						}
					}
				}
			}
		}
	}

	public function woocommerce_cart_calculate_fees() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return false;
		}

		$this->rc_register_session();
		$passed  = false;
		$ttl     = ( WC()->cart->get_cart_contents_total() + WC()->cart->get_shipping_total() );
		$remcent = ceil( $ttl ) - $ttl;
		if ( get_option( 'rc_shop_donation_enabled' ) === 'yes' && get_option( 'rc_min_cart_value_donation' ) <= $ttl ) {
			if ( $remcent > 0 ) {
				$_SESSION['restcent']['donation_shopowner'] = $remcent;
				$product_id_2                               = wc_get_product_id_by_sku( 'rcdpso-84754' );
				foreach ( WC()->cart->get_cart() as $cart_item ) {
					if ( $cart_item['data']->get_id() == $product_id_2 ) {
						$passed = true;
					}
				}
			}
		}

		return $passed;
	}

	public function add_donation_function() {
		$this->rc_register_session();

		global $wpdb, $woocommerce;
		$product_id_1                             = wc_get_product_id_by_sku( 'rc-donation' );
		$_SESSION['restcent']['donation_ammount'] = sanitize_text_field( $_POST['donation_ammount'] );
		$_SESSION['restcent']['donation_title']   = sanitize_text_field( $_POST['title'] );
		$_SESSION['restcent']['selected_charity'] = sanitize_text_field( $_POST['charityId'] );
		$title                                    = sanitize_text_field( $_POST['title'] );
		$imageId                                  = sanitize_text_field( $_POST['img'] );
		$_SESSION['restcent']['donation_image']   = $imageId;
		if ( $title === __( 'Cancel Donation', 'rest-cent-donations' ) ) {
			unset( $_SESSION['restcent']['donation_ammount'], $_SESSION['restcent']['donation_title'], $_SESSION['restcent']['donation_image'] );

			$product_cart_id                          = WC()->cart->generate_cart_id( $product_id_1 );
			$cart_item_key                            = WC()->cart->find_product_in_cart( $product_cart_id );
			if ( $cart_item_key ) {
				WC()->cart->remove_cart_item( $cart_item_key );
				wc_add_notice( __( 'Donation removed', 'rest-cent-donations' ), 'error' );
				echo 'removed';
				exit();
			}
		}

		$passed = false;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( $cart_item['data']->get_id() === $product_id_1 ) {
				$passed = true;
			}
		}
		if ( ! $passed ) {
			WC()->cart->add_to_cart( $product_id_1 );
			echo 'added';
			wc_add_notice( __( 'Donation added', 'rest-cent-donations' ) );
		}
		echo 'added';
		exit();
	}

	public function ts_product_image_on_checkout( $name, $cart_item, $cart_item_key ) {
		/* Return if not checkout page */
		if ( ! is_checkout() ) {
			return $name;
		}

		/* Get product object */
		$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

		/* Get product thumbnail */
		$thumbnail = $_product->get_image( 'full' );

		/* Add wrapper to image and add some css */
		$image = '<div class="ts-product-image" style="width: 52px; float: left; height: 45px; display: block; padding-right: 10px; vertical-align: middle;">'
		         . $thumbnail .
		         '</div>';

		/* Prepend image to name and return it */

		return $image . $name;
	}

	public function hide_out_of_stock_products_from_shop( $meta_query, $query ) {
		if ( ! is_shop() || is_admin() || is_search() ) {
			return $meta_query;
		}

		$meta_query[] = [
			'key'     => '_sku',
			'value'   => 'rc-donation',
			'compare' => '!='
		];

		return $meta_query;
	}

	public function hide_out_of_stock_products_from_search( $query ) {
		if ( $query->is_search() && $query->is_main_query() ) {
			$meta_query = (array) $query->get( 'meta_query' );

			$meta_query[] = [
				'key'     => '_sku',
				'value'   => 'rc-donation',
				'compare' => '!='
			];

			$query->set( 'meta_query', $meta_query );
		}
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

	public function custom_link_after_order_itemmeta( $item_id, $item, $product ) {
		// Only for "line item" order items
		if ( ! $item->is_type( 'line_item' ) ) {
			return;
		}

		if ( $product->get_sku() === 'rc-donation' && is_admin() ) {
			global $wpdb;
			$order        = wc_get_order( $item->get_order_id() );
			$charities_id = $order->get_meta( 'user_charity_id' );
			$tablename    = $wpdb->prefix . 'restcent_charities';
			$char         = $wpdb->get_row( 'select * from ' . $tablename . ' where restcent_charities_id = "' . $charities_id . '"' );
			if ( $char ) {
				echo '<div style="display:none" class="admin_chrt_logo"> Logo: <img alt="charity-logo"  src="' . wp_get_attachment_url( $char->logo_url ) . '" width="34"></div>';
			}
		}
	}

	public function add_custom_script_to_wp_footer() {
		$this->rc_register_session();

		if ( isset( $_SESSION['restcent']['donation_image'] ) ) {
			$dnimg = wp_get_attachment_url( @$_SESSION['restcent']['donation_image'] );
			$dntl  = sanitize_text_field( @$_SESSION['restcent']['donation_title'] );

			?>
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    jQuery('img[alt="restcent-charity-logo"]').attr('src', "<?php echo esc_url( $dnimg );?>").removeAttr('srcset');
                    jQuery('img[alt="restcent-charity-logo"]').closest('li').find('.item-name').html("<?php echo esc_html( $dntl );?>");
                });
            </script><?php
			$product_id      = wc_get_product_id_by_sku( 'rc-donation' );
			$product_cart_id = WC()->cart->generate_cart_id( $product_id );
			$cart_item_key   = WC()->cart->find_product_in_cart( $product_cart_id );
			if ( $cart_item_key && ! $dntl ) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}
		}

		if ( isset( $_SESSION['restcent'] ) && is_order_received_page() ) {
			unset( $_SESSION['restcent'] );
		}
	}

	public function ts_product_image_on_thankyou( $name, $item, $visible ) {
		/* Return if not thankyou/order-received page */
		if ( ! is_order_received_page() ) {
			return $name;
		}

		/* Get product id */
		$product_id = $item->get_product_id();
		/* Get product object */
		$_product = wc_get_product( $product_id );

		/* Get product thumbnail */
		$thumbnail = $_product->get_image();

		$image = '<div class="ts-product-image" style="width: 52px; height: 45px; display: inline-block; padding-right: 7px; vertical-align: middle;">'
		         . $thumbnail .
		         '</div>';

		$donationId = wc_get_product_id_by_sku( 'rc-donation' );
		if ( $product_id === $donationId ) {
			$charity_logo = get_post_meta( $item->get_order_id(), 'user_donation_charity_logo', true );
			if ( ! empty( $charity_logo ) ) {
				$image = '<div class="ts-product-image" style="width: 52px; height: 45px; display: inline-block; padding-right: 7px; vertical-align: middle;"><img src="' . wp_get_attachment_url( $charity_logo ) . '"></div>';
			}
		}

		return $image . $name;
	}
}
