<?php
/**
 * Class to manage referral program
 *
 * @class   YITH_WC_Points_Rewards_Referral
 * @since  3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_WC_Points_Rewards_Referral' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Referral
	 */
	class YITH_WC_Points_Rewards_Referral {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Referral
		 */
		protected static $instance;

		/**
		 * Referral var name
		 *
		 * @var bool
		 * @since 3.0.0
		 */
		protected static $referral_var_name = 'ref';

		/**
		 * Referral cookie name
		 *
		 * @var bool
		 * @since 3.0.0
		 */
		protected $referral_cookie_name = 'wp-ywpar_referral_token';

		/**
		 * Referral cookie expire time
		 *
		 * @var string
		 * @since 3.0.0
		 */
		protected $referral_cookie_exp = WEEK_IN_SECONDS;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Referral
		 * @since  3.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 3.0.0
		 */
		private function __construct() {

            add_action( 'init', array( $this, 'init'), 30 );

			// extrapoint on referral registration.
			if ( 'yes' === ywpar_get_option( 'enable_points_on_referral_registration_exp' ) ) {
				add_action( 'user_register', array( $this, 'extra_points_referral_registration' ), 99 );
				if ( 'yes' === ywpar_get_option( 'remove_points_on_referral_registration_exp' ) ) {
					add_action( 'delete_user', array( $this, 'remove_points_on_referral_registration_user_cancelled' ), 10, 1 );
					add_action( 'ywpar_banned_user', array( $this, 'remove_points_on_referral_registration_user_cancelled' ), 10, 1 );
				}
			}

			// extrapoint on referral purchase - remove option active on user cancellation.
			if ( 'yes' === ywpar_get_option( 'enable_points_on_referral_purchase_exp' ) ) {
				add_action( 'ywpar_saved_points_earned_from_cart', array( $this, 'prepare_referral_order_meta' ), 10, 1 );
				add_action( 'ywpar_added_earned_points_to_order', array( $this, 'assign_points_to_referred' ), 10, 1 );
				add_action( 'woocommerce_order_status_completed', array( $this, 'assign_referral_points_for_guest_orders' ) );
				if ( 'yes' === ywpar_get_option( 'remove_points_on_referral_purchase_exp' ) ) {
					add_action( 'delete_user', array( $this, 'remove_points_on_referral_purchase_user_cancelled' ), 2, 1 );
					add_action( 'ywpar_banned_user', array( $this, 'remove_points_on_referral_purchase_user_cancelled' ), 2, 1 );
				}
			}
		}

		/**
         * Initialize the referral class
         *
		 * @return void
		 */
		public function init( ) {
			$var_name    = apply_filters( 'ywpar_referral_var_name', self::$referral_var_name );
			$this->referral_cookie_name = apply_filters( 'ywpar_referral_cookie_name', $this->referral_cookie_name );
			$this->referral_cookie_exp  = apply_filters( 'ywpar_referral_cookie_exp', $this->referral_cookie_exp );


			if ( ! empty( $_REQUEST[ $var_name ] ) ) { //phpcs:ignore
				$customer = ywpar_get_customer( sanitize_text_field( wp_unslash( $_REQUEST[ $var_name ] ) ) ); //phpcs:ignore
				$current_user_id = ywpar_get_current_customer_id();
				if ( $customer && $customer->is_enabled() && $current_user_id !== $customer->get_id() ) {
					$this->set_referral_cookie();
				}
			}
        }
		/**
		 * Assign points to customer referred if an order is made by guest
		 *
		 * @param int $order_id Order id.
		 */
		public function assign_referral_points_for_guest_orders( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( 0 !== $order->get_user_id() ) {
				return;
			}
			$ref_infos = $order->get_meta( 'ywpar_referral_purchase' );
			if ( $ref_infos && ! ( $ref_infos['used'] ) ) {
				$this->assign_points_to_referred( $order );
			}
		}

		/**
		 * Assign points to referred user when the points are assigned to the customer
		 *
		 * @param WC_Order $order Order.
		 */
		public function assign_points_to_referred( $order ) {
			$ref = $order->get_meta( 'ywpar_referral_purchase' );
			if ( $ref ) {
				$this->add_referral_points( 'purchase', $ref, $order );
			}
		}

		/**
		 * Set referral Cookie
		 *
		 * @since  3.0.0
		 */
		public function set_referral_cookie() {
			$var_name    = apply_filters( 'ywpar_referral_var_name', self::$referral_var_name );
			$token = sanitize_text_field( wp_unslash( $_REQUEST[ $var_name ] ) ); //phpcs:ignore
			// sets cookie for referrer id.
			setcookie( $this->referral_cookie_name, $token, time() + intval( $this->referral_cookie_exp ), COOKIEPATH, COOKIE_DOMAIN, wc_site_is_https() && is_ssl(), true );
		}

		/**
		 * Assign points when new user register through the user's referral link
		 *
		 * @param  integer $user_id User id.
		 *
		 * @since  3.0.0
		 */
		public function extra_points_referral_registration( $user_id ) {
			$customer = ywpar_get_customer( $user_id );
			if ( ! $customer || ! $customer->is_enabled() ) {
				return;
			}
			$ref_id            = $this->get_token_by_referral_cookie(); // get referral id from cookie.
			$customer_referral = ywpar_get_customer( $ref_id );
			if ( ! $customer_referral || ! $customer_referral->is_enabled() ) {
				return;
			}

			$points_to_add = (int) ywpar_get_option( 'points_referral_registration' );
			if ( $points_to_add ) {
				/* set new user meta with ref information */
				$ref_info = array(
					'ref_id'    => $ref_id,
					'date_time' => apply_filters( 'ywpar_points_registration_date', date_i18n( 'Y-m-d H:i:s' ) ),
					'points'    => $points_to_add,
				);

				$customer->set_referral_registration( $ref_info );
				$customer->save();
				$this->add_referral_points( 'registration', $ref_info, '', $user_id );
			}

		}

		/**
		 * Add referral points to a purchase or user registration
		 *
		 * @param string   $type   Type of referral: purchase or registration.
		 * @param array    $ref    Information about referral ref_id, date_time, points.
		 * @param WC_Order $order   WC order object; needed for purchase type.
		 * @param int      $user_id   User id that did the action.
		 *
		 * @return void
		 * @since   3.0.0
		 */
		public function add_referral_points( $type, $ref, $order = '', $user_id = 0 ) {

			if ( ! isset( $ref['ref_id'] ) ) {
				return;
			}

			$customer_referral = ywpar_get_customer( $ref['ref_id'] );
			if ( ! $customer_referral || ! $customer_referral->is_enabled() ) {
				return;
			}

			$action        = '';
			$description   = '';
			$order_id      = '';
			$points_to_add = isset( $ref['points'] ) ? $ref['points'] : 0;

			switch ( $type ) {
				case 'purchase':
					$action      = 'referral_purchase_exp';
					$description = apply_filters( 'ywpar_referral_purchase_description', esc_html__( 'Points from a referred purchase', 'yith-woocommerce-points-and-rewards' ) );
					if ( ( $order->get_customer_id() === $customer_referral->get_id() ) || ( $customer_referral->get_wc_customer()->get_email() === $order->get_billing_email() ) ) {
						return;
					}
					$ref['used'] = true;
					$order->update_meta_data( 'ywpar_referral_purchase', $ref );
					$order->save();
					$order_id = $order->get_id();
					break;
				case 'registration':
					$action = 'referral_registration_exp';
					// translators: Placeholder is the id of the customer.
					$text        = sprintf( esc_html_x( 'Points from registration of a new user with ID (%1$d)', 'Placeholder is the id of the customer.', 'yith-woocommerce-points-and-rewards' ), $user_id );
					$description = apply_filters( 'ywpar_referral_registration_description', $text, $user_id );
					break;
			}

			if ( '' !== $action && $points_to_add > 0 ) {
				$customer_referral->update_points( $points_to_add, $action, array( 'description' => $description, 'order_id' => $order_id ) );
				/**
				 * DO_ACTION: ywpar_updated_points_referral
				 *
				 * action description.
				 *
				 * @param int $order_id the ID of the order.
				 * @param string $action the kind of action.
				 * @param YITH_WC_Points_Rewards_Customer $customer_referral the referral user object.
				 */
				do_action( 'ywpar_updated_points_referral', $order_id, $action, $customer_referral );
			}
		}

		/**
		 * Remove points from a referral when referred user has been deleted
		 *
		 * @param int $id       ID of the user to delete.
		 *
		 * @since  2.1.0
		 */
		public function remove_points_on_referral_registration_user_cancelled( $id ) {
			$customer      = ywpar_get_customer( $id );
			$referral_info = $customer->get_referral_registration();
			if ( isset( $referral_info['ref_id'], $referral_info['points'] ) ) {
				$customer_referral = ywpar_get_customer( $referral_info['ref_id'] );
				if ( ! $customer_referral ) {
					return;
				}
				// translators: placeholder is the user id.
				$description = sprintf( __( 'The referred user with ID (%1$d) has been deleted', 'yith-woocommerce-points-and-rewards' ), $id );
				$customer_referral->update_points( - (int) $referral_info['points'], 'ref_removed_registration_exp', array( 'description' => $description ) );
				$customer->set_referral_registration( array() );
				$customer->save();
			}
		}

		/**
		 * Prepare the referral meta for the order.
		 *
		 * @param WC_Order $order Current order.
		 */
		public function prepare_referral_order_meta( $order ) {
			/* check about referral purchase option - if active add the ref to the order */
			$ref_id = $this->get_token_by_referral_cookie(); // get referral id from cookie.

			if ( '' !== $ref_id && 0 !== (int) ywpar_get_option( 'points_referral_purchase', 0 ) ) {
				/* prepare referral info for meta */
				$ref_info = array(
					'ref_id'    => $ref_id,
					'date_time' => apply_filters( 'ywpar_points_registration_date', date_i18n( 'Y-m-d H:i:s' ) ), // this filter is for log table date.
					'points'    => ywpar_get_option( 'points_referral_purchase', 0 ),
					'used'      => false,
				);

				$order->update_meta_data( 'ywpar_referral_purchase', $ref_info );
				$order->save();
			}
		}


		/**
		 * Remove points from a referral purchase when referred user has been deleted or banned
		 *
		 * @param int $id       ID of the user to delete.
		 *                      Default null, for no reassignment.
		 * @since  3.0.0
		 */
		public function remove_points_on_referral_purchase_user_cancelled( $id ) {
			$customer     = ywpar_get_customer( $id );
			$orders_query = apply_filters(
				'ywpar_get_orders_query_on_cancelled_user',
				array(
					'customer' => $customer->get_id(),
				),
				$id
			);

			$orders = wc_get_orders( $orders_query );

			if ( $orders ) {
				foreach ( $orders as $order ) {
					$ref_infos = $order->get_meta( 'ywpar_referral_purchase' );

					if ( $ref_infos && true === $ref_infos['used'] && ! isset( $ref_infos['removed'] ) ) { // we have referral and his points were applied so remove them.
						$customer_referral = ywpar_get_customer( $ref_infos['ref_id'] );
						if ( ! $customer_referral ) {
							return;
						}
						$ref_infos = array_merge( $ref_infos, array( 'removed' => true ) );
						$order->update_meta_data( 'ywpar_referral_purchase', $ref_infos );
						$order->save();
						// translators: Placeholder is the if of the order.
						$description = sprintf( esc_html_x( 'Points removed from order with ID %1$s because the user has been banned or deleted', ' Placeholder is the if of the order.', 'yith-woocommerce-points-and-rewards' ), $order->get_id() );
						$customer_referral->update_points( - (int) $ref_infos['points'], 'ref_removed_purchase_exp', array( 'description' => $description ) );
					}
				}
			}
		}

		/**
		 * Get referral token from Cookie
		 *
		 * @return string token of the referral
		 *
		 * @since  3.0.0
		 */
		private function get_token_by_referral_cookie() {
			$token = '';
			if ( isset( $_COOKIE[ $this->referral_cookie_name ] ) ) {
				$token = sanitize_text_field( wp_unslash( $_COOKIE[ $this->referral_cookie_name ] ) );
			}

			return $token;
		}

		/**
		 * Return the referral link
		 *
		 * @param int $user_id User id.
		 * @return string
		 */
		public static function get_referral_link( $user_id ) {
			global $sitepress;
			$var_name    = apply_filters( 'ywpar_referral_var_name', self::$referral_var_name );
			$referral_link = add_query_arg( $var_name, $user_id, get_bloginfo( 'wpurl' ) );

			if ( $sitepress ) {
				$referral_link = apply_filters( 'wpml_permalink', $referral_link );
			}

			return apply_filters( 'ywpar_referral_link', $referral_link, $user_id );
		}

		/**
		 * Print user referral copy field
		 *
		 * @param string $user_id User id.
		 *
		 * @since  3.0.0
		 */
		public static function print_user_referral_field( $user_id ) {
			?>
			<div id="ywpar-copy-to-clipboard-wrapper">

			<div class="ywpar-copy-to-clipboard_field-wrap">
                <label for="ywpar_referral_user_link" class="screen-reader-text"><?php echo esc_html__('Get the referral link', 'yith-woocommerce-points-and-rewards'); ?></label>
				<input type="text"
						id="ywpar_referral_user_link"
						class="ywpar-copy-to-clipboard__field"
						value="<?php echo esc_attr( self::get_referral_link( $user_id ) ); ?>"
						readonly
				>
				<div class="ywpar-copy-to-clipboard__tip"><?php echo esc_html_x( 'Copied!', 'Copy-to-clipboard message', 'yith-woocommerce-points-and-rewards' ); ?></div>
			</div>
			<div class="ywpar-copy-to-clipboard__copy">
				<i class="ywpar-copy-to-clipboard__copy__icon yith-icon yith-icon-copy"></i>
				<span class="ywpar-copy-to-clipboard__copy__text"><?php echo esc_html_x( 'Copy', 'Copy-to-clipboard button text', 'yith-woocommerce-points-and-rewards' ); ?></span>
			</div>
		</div>
<?php

		}
	}

}
