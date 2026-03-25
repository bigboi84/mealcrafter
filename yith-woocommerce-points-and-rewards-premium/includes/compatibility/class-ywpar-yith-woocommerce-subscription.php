<?php
/**
 * Class to add compatibility with YITH WooCommerce Affiliates Premium
 *
 * @class   YITH_WC_Points_Rewards_WooCommerce_Currency_Switcher
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

/**
 * YWPAR_YITH_WooCommerce_Subscription class to add compatibility with YITH WooCommerce Affiliates Premium
 *
 * @class   YWPAR_YITH_WooCommerce_Subscription
 * @package YITH WooCommerce Points and Rewards
 * @since   1.6.0
 */
if ( ! class_exists( 'YWPAR_YITH_WooCommerce_Subscription' ) ) {
	/**
	 * Class YWPAR_YITH_WooCommerce_Subscription
	 */
	class YWPAR_YITH_WooCommerce_Subscription {
		/**
		 * Single instance of the class
		 *
		 * @var YWPAR_YITH_WooCommerce_Subscription
		 * @since 1.6.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YWPAR_YITH_WooCommerce_Subscription
		 * @since 1.6.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used.
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'ywpar_extra_points_options', array( $this, 'add_subscription_extra_options' ) );
			add_filter( 'ywpar_add_order_points', array( $this, 'check_renew' ), 10, 2 );

			if ( 'yes' === ywpar_get_option( 'earn_points_on_fee' ) ) {
				add_filter( 'ywpar_get_point_earned_price', array( $this, 'earn_points_on_fee' ), 10, 3 );
			}
			if ( 'yes' === ywpar_get_option( 'earn_points_on_renew' ) ) {
				add_action( 'ywsbs_subscription_payment_complete', array( $this, 'assign_points_on_renew' ), 10, 2 );
			}

		}

		/**
		 * Check the new order.
		 *
		 * @param bool $result Results.
		 * @param int  $order_id Order id.
		 *
		 * @return bool
		 */
		public function check_renew( $result, $order_id ) {
			$order      = wc_get_order( $order_id );
			$is_a_renew = $order->get_meta( 'is_a_renew', true );
			return 'yes' === $is_a_renew;
		}

		/**
		 * Add point to subscription product calculation if there's a fee.
		 *
		 * @param float      $price Amount.
		 * @param string     $currency Currency.
		 * @param WC_Product $product Product.
		 *
		 * @return mixed
		 */
		public function earn_points_on_fee( $price, $currency, $product ) {

			$is_subscription = function_exists( 'ywsbs_is_subscription_product' ) ? ywsbs_is_subscription_product( $product ) : YITH_WC_Subscription()->is_subscription( $product );

			if ( $is_subscription && 'yes' === $product->get_meta( '_ywsbs_enable_fee', true ) ) {
				$signup_fee = $product->get_meta( '_ywsbs_fee', true );
				if ( $signup_fee ) {
					$price += $signup_fee;
				}
			}

			return $price;
		}

		/**
		 * Assign point to customer on renew order
		 *
		 * @param YITH_WC_Subscription $subscription Subscription object.
		 * @param WC_Order             $order Order.
		 */
		public function assign_points_on_renew( $subscription, $order ) {
			$parent_order = wc_get_order( $subscription->order_id );
			$customer     = $subscription->user_id;

			if ( ! $parent_order || ! $customer ) {
				return;
			}

			$point_earned = $parent_order->get_meta( '_ywpar_points_earned', true );
			$is_set       = $order->get_meta( '_ywpar_points_earned', true );

			if ( $is_set || ! $point_earned ) {
				return;
			}

			$renew_points = 0;
			$order_items  = $parent_order->get_items();

			$subscription_order_item = (int) $subscription->get( 'order_item_id' );

			if ( ! empty( $order_items ) ) {
				foreach ( $order_items as $order_item ) {
					if( $order_item->get_id() === $subscription_order_item ){
						$renew_points = (int) $order_item->get_meta( '_ywpar_total_points' );
					}
				}
			}

			if ( $renew_points > 0 ) {
				$customer = ywpar_get_customer( $order->get_customer_id() );
				if ( $customer ) {
					$order->update_meta_data( '_ywpar_points_earned', $renew_points );
					// translators: First placeholder: number of points; second placeholder: label of points.
					$order->add_order_note( sprintf( _x( 'Customer earned %1$d %2$s for this purchase.', 'First placeholder: number of points; second placeholder: label of points', 'yith-woocommerce-points-and-rewards' ), $renew_points, ywpar_get_option( 'points_label_plural' ) ), 0 );
					$customer->update_points( $renew_points, 'renew_order', array( 'order_id' => $order->get_id() ) );
					$order->save();
				}
			}

		}

		/**
		 * Add subscription option to Points and Rewards Extra Points options.
		 *
		 * @param array $options Options.
		 * @return mixed
		 * @since  3.0.0
		 */
		public function add_subscription_extra_options( $options ) {

			$subscription_option = array(
				'subscription_title'     => array(
					'name' => __( 'Extra points for subscribers', 'yith-woocommerce-points-and-rewards' ),
					'type' => 'title',
					'id'   => 'ywpar_subscription_title',
				),

				'earn_points_on_fee'     => array(
					'name'      => __( 'Enable to assign points on the subscriptions fees', 'yith-woocommerce-points-and-rewards' ),
					'desc'      => __( 'Enable to assign points on the subscriptions fees. If disabled, users will get points only for the price of the subscription-based products.', 'yith-woocommerce-points-and-rewards' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'default'   => 'no',
					'id'        => 'ywpar_earn_points_on_fee',
				),

				'earn_points_on_renew'   => array(
					'name'      => __( '	Enable to assign points on each renewal order', 'yith-woocommerce-points-and-rewards' ),
					'desc'      => __( 'Enable to assign points on each renewal order (each subscription payment). If disabled, users will get points only for the 1st payment.', 'yith-woocommerce-points-and-rewards' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'default'   => 'no',
					'id'        => 'ywpar_earn_points_on_renew',
				),

				'label_renew_order'      => array(
					'name'      => esc_html_x( 'Renew order label', 'Reason to show when the customer earns points from recurring payment', 'yith-woocommerce-points-and-rewards' ),
					'desc'      => __( 'Enter the renew order label to identify point assignment in the user\'s points history page', 'yith-woocommerce-points-and-rewards' ),
					'type'      => 'yith-field',
					'yith-type' => 'text',
					'default'   => __( 'Renew order', 'yith-woocommerce-points-and-rewards' ),
					'id'        => 'ywpar_label_renew_order',
					'deps'      => array(
						'id'    => 'ywpar_earn_points_on_renew',
						'value' => 'yes',
						'type'  => 'hide',
					),
				),

				'subscription_title_end' => array(
					'type' => 'sectionend',
					'id'   => 'ywpar_subscription_title_end',
				),
			);

			$all_options             = array_merge( $options['points-extra'], $subscription_option );
			$options['points-extra'] = $all_options;

			return $options;
		}
	}
}

if ( ! function_exists( 'YWPAR_YITH_WooCommerce_Subscription' ) ) {
	/**
	 * Unique access to instance of YWPAR_YITH_WooCommerce_Subscription class
	 *
	 * @return YWPAR_YITH_WooCommerce_Subscription
	 */
	function YWPAR_YITH_WooCommerce_Subscription() { //phpcs:ignore
		return YWPAR_YITH_WooCommerce_Subscription::get_instance();
	}
}

YWPAR_YITH_WooCommerce_Subscription();

