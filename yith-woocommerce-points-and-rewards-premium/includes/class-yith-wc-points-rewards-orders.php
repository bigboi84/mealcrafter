<?php
/**
 * Class to manage the points in the orders
 *
 * @class   YITH_WC_Points_Rewards_Orders
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_WC_Points_Rewards_Orders' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Orders
	 */
	class YITH_WC_Points_Rewards_Orders {

		/**
		 * Array to save the id of order processed.
		 *
		 * @var array
		 */
		protected $orders_processed = array();

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Orders
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Orders
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

			// register the coupon and the point used at checkout.
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'add_order_meta' ), 10 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'deduce_order_points' ), 20 );

			//integration for checkout blocks
			add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'add_order_meta' ), 10 );
			add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'deduce_order_points' ), 20 );


			add_action( 'woocommerce_order_status_changed', array( $this, 'clear_ywpar_coupon_after_create_order' ), 10, 2 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'manage_order_points_for_order_status_changed' ), 20, 4 );
			add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'save_order_item_points' ), 100, 4 );

			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Init the class.
		 */
		public function init() {

			// remove points when the order is refunded.
			if ( ywpar_get_option( 'reassing_redeemed_points_refund_order' ) === 'yes' ) {
				add_action( 'woocommerce_order_partially_refunded', array( $this, 'remove_redeemed_points_from_orders' ), 11, 2 );
				add_action( 'woocommerce_order_fully_refunded', array( $this, 'remove_redeemed_points_from_orders' ), 11, 2 );
				add_action( 'wp_ajax_nopriv_woocommerce_delete_refund', array( $this, 'add_redeemed_order_points' ), 9, 2 );
				add_action( 'wp_ajax_woocommerce_delete_refund', array( $this, 'add_redeemed_order_points' ), 9, 2 );
			}

			if ( ywpar_get_option( 'remove_point_refund_order' ) === 'yes' ) {
				add_action( 'woocommerce_order_refunded', array( $this, 'remove_order_points_refund' ), 11, 2 );
			}
		}


		/**
		 * Manage points when an order changes the status
		 *
		 * @param int      $order_id Order id.
		 * @param string   $from     From order status.
		 * @param string   $to       To order status.
		 * @param WC_Order $order    WC_Order.
		 */
		public function manage_order_points_for_order_status_changed( $order_id, $from, $to, $order ) {

			$remove_points = $this->get_order_status_to_remove_earned_points();
			$assign_points = $this->get_order_status_to_assign_earned_points();

			if ( in_array( $from, $assign_points, true ) && in_array( $to, $remove_points, true ) ) {
				$this->remove_earned_point_from_order( $order_id );
			}

			$add_points    = $this->get_order_status_to_add_redeemed_points_to_order();      // pending, on-hold, completed, processing.
			$remove_points = $this->get_order_status_to_remove_redeemed_points_from_order(); // cancelled, failed.

			if ( in_array( $from, $remove_points, true ) && in_array( $to, $add_points, true ) ) {
				$this->add_redeemed_order_points( $order_id );
			}

			if ( in_array( $from, $add_points, true ) && in_array( $to, $remove_points, true ) ) {
				$this->remove_redeemed_points_from_orders( $order_id );
			}
		}

		/**
		 * Remove points to the order from order_id
		 *
		 * @param int $order_id Order id.
		 *
		 * @return void
		 * @since   1.0.0
		 */
		public function remove_earned_point_from_order( $order_id ) {

			$order         = wc_get_order( $order_id );
			$point_earned  = $order->get_meta( '_ywpar_points_earned', true );
			$customer_user = $order->get_customer_id();

			if ( '' === $point_earned || $customer_user <= 0 ) {
				return;
			}

			$customer = ywpar_get_customer( $customer_user );
			if ( ! $customer ) {
				return;
			}

			$action = 'order_' . $order->get_status();
			$order->update_meta_data( '_ywpar_points_earned', '' );
			$customer->update_points( -$point_earned, $action, array( 'order_id' => $order_id, 'remove_collected_points' => true ) );

			// translators:First placeholder: number of points; second placeholder: label of points.
			$order->add_order_note( sprintf( _x( 'Removed %1$d %2$s for order %3$s.', 'First placeholder: number of points; second placeholder: label of points', 'yith-woocommerce-points-and-rewards' ), -$point_earned, ywpar_get_option( 'points_label_plural' ), ywpar_get_action_label( $action ) ), 0 );
			$order->save();
		}


		/**
		 * Return the list of order status where the earned points will be removed
		 */
		private function get_order_status_to_remove_earned_points() {
			$status = array();
			if ( ywpar_get_option( 'remove_point_order_deleted' ) === 'yes' ) {
				$status = array( 'cancelled', 'failed' );
			}
			/**
			 * APPLY_FILTERS: ywpar_order_status_to_remove_earned_points
			 *
			 * filter the order status to remove earned points.
			 *
			 * @param array $status default is array of cancelled and failed.
			 */
			return apply_filters( 'ywpar_order_status_to_remove_earned_points', $status );
		}

		/**
		 * Return the list of order status where the earned points will be removed
		 */
		private function get_order_status_to_assign_earned_points() {
			$status = array();
			if ( ywpar_get_option( 'remove_point_order_deleted' ) === 'yes' ) {
				$status = array( 'completed', 'processing' );
			}
			/**
			 * APPLY_FILTERS: ywpar_order_status_to_assign_earned_points
			 *
			 * filter the order status to assign earned points.
			 *
			 * @param array $status default is array of completed and processing.
			 */
			return apply_filters( 'ywpar_order_status_to_assign_earned_points', $status );
		}


		/**
		 * Return the list of order status where the redeemed points removed to the customer will be rewarded
		 */
		private function get_order_status_to_remove_redeemed_points_from_order() {
			$status = array();
			if ( ywpar_get_option( 'remove_point_order_deleted' ) === 'yes' ) {
				$status = array( 'cancelled', 'failed' );
			}
			/**
			 * APPLY_FILTERS: ywpar_order_status_to_remove_redeemed_points
			 *
			 * filter the order status to remove redeemed points.
			 *
			 * @param array $status default is array of cancelled and failed.
			 */
			return apply_filters( 'ywpar_order_status_to_remove_redeemed_points', $status );
		}

		/**
		 * Return the list of order status where the redeemed points will be removed from the customer and added to the order
		 */
		private function get_order_status_to_add_redeemed_points_to_order() {
			$status = array();
			if ( ywpar_get_option( 'remove_point_order_deleted' ) === 'yes' ) {
				$status = array( 'pending', 'on-hold', 'completed', 'processing' );
			}
			/**
			 * APPLY_FILTERS: ywpar_order_status_to_assign_redeemed_points
			 *
			 * filter the order status to assign redeemed points.
			 *
			 * @param array $status default is array of pending, on-hold, completed and processing.
			 */
			return apply_filters( 'ywpar_order_status_to_assign_redeemed_points', $status );
		}

		/**
		 * Save the value of ywpar_total_points inside the order item.
		 *
		 * @param WC_Order_Item_Product $item          Item.
		 * @param string                $cart_item_key Cart Item key.
		 * @param array                 $values        Values.
		 * @param WC_Order              $order         Order.
		 */
		public function save_order_item_points( $item, $cart_item_key, $values, $order ) {
			if ( isset( $values['ywpar_total_points'] ) ) {
				$item->update_meta_data( '_ywpar_total_points', $values['ywpar_total_points'] );
			}
		}

		/**
		 * Add points to the customer from previous orders
		 *
		 * @param string $from Start date.
		 * @param int    $step Offset.
		 * @param array  $data Array with info.
		 */
		public function add_points_to_previous_orders( $from, $step = 1, $data = array() ) {
			/**
			 * APPLY_FILTERS: ywpar_apply_previous_order_posts_per_page
			 *
			 * filter how many posts per page to use in the query.
			 *
			 * @param int $value default 20.
			 */
			$posts_per_page = apply_filters( 'ywpar_apply_previous_order_posts_per_page', 2 );
			$offset         = ( $step ) * $posts_per_page;
			$success_count  = 0;
			$args = array(
				'type'   => 'shop_order',
				'return' => 'ids',
				'paged' => $offset,
				'limit'  => $posts_per_page,
				'paginate' => true,
				'status' => apply_filters( 'ywpar_previous_orders_statuses', array( 'wc-processing', 'wc-completed' ) ),
			);

			/**
			 * If this filter return true will not checked the order meta _ywpar_points_earned.
			 */
			if ( ! apply_filters( 'ywpar_force_add_points_to_previous_orders', false ) ) {
				$args['meta_query'] = array(
						'key'     => '_ywpar_points_earned',
						'compare' => 'NOT EXISTS',
				);
			}

			if ( '' !== $from ) {
				$args['date_created'] = '>=' . strtotime( $from );
			}

			$results = wc_get_orders( $args );
			
			if ( $results && is_object( $results ) ) {
				$total_posts = $results->total;
				$orders_ids  = $results->orders;
			
				if ( is_array( $orders_ids ) ) {
					foreach ( $orders_ids as $order_id ) {
						if ( apply_filters( 'ywpar_apply_points_on_previous_orders_bulk', true, $order_id ) ) {
							$this->add_points_to_order( $order_id );
						}

						if ( apply_filters( 'yith_points_rewards_remove_rewards_points', false, $order_id ) ) {
							$this->add_redeemed_order_points( $order_id );
						}
						$success_count++;
					}

					if ( $success_count >= $posts_per_page ) {
						$success_count += $offset;

						return array(
							'next_step'  => ++$step,
							'message'    => '',
							'percentage' => $total_posts ? ceil( $success_count / $total_posts * 100 ) : 0,
						);
					}
				}

			}
			$success_count += $offset;
			// translators: Total number of orders updated.
			$response = sprintf( _nx( '<strong>%d</strong> order has been updated', '<strong>%d</strong> orders have been updated', $success_count, 'Total number of orders updated.', 'yith-woocommerce-points-and-rewards' ), $success_count );

			return array(
				'next_step'  => 'done',
				'message'    => $response,
				'percentage' => '',
			);

		}

		/**
		 * Add points to order
		 *
		 * @param int $order_id Order id.
		 *
		 * @return void;
		 */
		public function add_points_to_order( $order_id ) {

			if ( in_array( $order_id, $this->orders_processed, true ) ) {
				return;
			}

			$order          = wc_get_order( $order_id );
			$ywpar_customer = $this->get_customer_by_order( $order );

			if ( ! $this->validate_order_before_add_points( $order ) || ! $ywpar_customer || ! $ywpar_customer->is_enabled() ) {
				array_push( $this->orders_processed, $order_id );

				return;
			}

			$total_points = $this->get_earned_total_points( $order );

			$order->update_meta_data( '_ywpar_points_earned', $total_points );
			$order->update_meta_data( '_ywpar_conversion_points', yith_points()->earning->get_conversion_option( $order->get_currency(), $order ) );
			// translators: First placeholder: number of points; second placeholder: label of points.
			$order->add_order_note( sprintf( _x( 'Customer earned %1$d %2$s for this purchase.', 'First placeholder: number of points; second placeholder: label of points', 'yith-woocommerce-points-and-rewards' ), $total_points, ywpar_get_option( 'points_label_plural' ) ), 0 );
			$order->save();

			array_push( $this->orders_processed, $order_id );
			/**
			 * DO_ACTION: ywpar_added_earned_points_to_order
			 *
			 * Hook on points added to the order.
			 *
			 * @param WC_Order $order the order.
			 */
			do_action( 'ywpar_added_earned_points_to_order', $order );
			$ywpar_customer->update_points( $total_points, 'order_completed', array( 'order_id' => $order_id ) );
			yith_points()->extra_points->handle_actions( array( 'num_of_orders', 'amount_spent', 'checkout_threshold' ), $ywpar_customer, $order_id );
		}

		/**
		 * Validate the order before add points
		 *
		 * @param WC_Order $order Order object.
		 *
		 * @return bool
		 */
		private function validate_order_before_add_points( $order ) {
			$is_valid = true;

			if ( ! apply_filters( 'ywpar_force_add_points_to_previous_orders', false ) && $order instanceof WC_Order ) {
				$is_set = $order->get_meta( '_ywpar_points_earned', true );

				if ( ! empty( $is_set ) ) {
					$is_valid = false;
				}
			}

			if ( $is_valid && 'yes' === ywpar_get_option( 'disable_earning_while_reedeming', 'no' ) && $this->has_reward_coupon_applied( $order ) ) {
				$is_valid = false;
			}

			/**
			 * APPLY_FILTERS: ywpar_validate_order_before_add_points
			 *
			 * filter if the order is validated.
			 *
			 * @param bool $is_valid .
			 * @param WC_Order $order .
			 */
			return apply_filters( 'ywpar_validate_order_before_add_points', $is_valid, $order );
		}

		/**
		 * Return the total points of an order.
		 *
		 * @param WC_Order $order Order object.
		 *
		 * @return int
		 */
		private function get_earned_total_points( $order ) {
			$currency   = $order->get_currency();
			$tot_points = $order->get_meta( 'ywpar_points_from_cart' );

			// this is necessary for old orders.
			if ( '' === $tot_points ) {
				$tot_points  = 0;
				$order_items = $order->get_items();

				if ( ! empty( $order_items ) ) {
					foreach ( $order_items as $order_item ) {
						$item_points = $this->calculate_order_item_points( $order_item, $currency, true );
						$tot_points  += $item_points * $order_item->get_quantity();
					}
				}

				if ( defined( 'YITH_YWPAR_DOING_BULK_ACTION' ) && YITH_YWPAR_DOING_BULK_ACTION && $order->get_total_discount() > 0 && 'yes' === ywpar_get_option( 'remove_points_coupon' ) ) {
					$remove_points     = 0;
					$conversion_points = yith_points()->earning->get_conversion_option( $currency, $order );
					if ( 0 < $conversion_points['money'] * $conversion_points['points'] ) {
						$remove_points = $order->get_total_discount() / $conversion_points['money'] * $conversion_points['points'];
					}
					$tot_points -= $remove_points;
				}
			}

			return yith_ywpar_round_points( $tot_points );
		}

		/**
		 * Return the point calculated for this order item.
		 *
		 * @param WC_Order_Item_Product $order_item Order item.
		 * @param string                $currency   Currency.
		 * @param bool                  $integer    Precision of points.
		 *
		 * @return int
		 */
		public function calculate_order_item_points( $order_item, $currency, $integer = false ) {
			$product_id  = $order_item->get_variation_id() ? $order_item->get_variation_id() : $order_item->get_product_id();
			$product     = wc_get_product( $product_id );
			$order_total = (float) $order_item->get_total();

			$tax_mode = ywpar_get_option( 'earn_prices_tax', get_option( 'woocommerce_tax_display_shop', 'incl' ) );
			if ( apply_filters( 'ywpar_include_tax_totals_on_point_calculation', 'incl' === $tax_mode, $product, $order_item ) ) {
				$order_total += (float) $order_item->get_total_tax();
			}

			$line_price        = $order_total / $order_item->get_quantity();
			$points_from_price = yith_points()->earning->get_points_earned_from_price( $line_price, $currency );

			$points_from_product = false;
			if ( $product instanceof WC_Product ) {
				$points_from_product = yith_points()->earning->calculate_product_points( $product, $currency, false );
			}

			$points = ( false !== $points_from_product ) ? min( $points_from_price, $points_from_product ) : $points_from_price;
			$points = $integer ? yith_ywpar_round_points( $points ) : $points;

			/**
			 * APPLY_FILTERS: ywpar_get_calculate_product_points_in_order
			 *
			 * filter the points value.
			 *
			 * @param int $points .
			 * @param int|float $points_from_price .
			 * @param int|float $points_from_product .
			 * @param int $product_id .
			 * @param bool $integer .
			 * @param WC_Order_Item_Product $order_item .
			 */
			return apply_filters( 'ywpar_get_calculate_product_points_in_order', $points, $points_from_price, $points_from_product, $product_id, $integer, $order_item );
		}

		/**
		 * Check of the order has a reward coupon
		 *
		 * @param WC_Order $order Order object.
		 *
		 * @return bool
		 */
		public function has_reward_coupon_applied( $order ) {
			$has_reward_coupon = false;
			$coupons           = $order->get_coupon_codes();
			foreach ( $coupons as $c ) {
				if ( false !== strpos( $c, 'ywpar_' ) ) {
					$has_reward_coupon = true;
					break;
				}
			}

			return $has_reward_coupon;
		}

		/**
		 * Return the points customer object by order
		 *
		 * @param WC_Order $order Order.
		 *
		 * @return bool|YITH_WC_Points_Rewards_Customer
		 */
		public function get_customer_by_order( $order ) {

			$customer = false;

			if ( $order instanceof WC_Order ) {
				$customer_id = $order->get_customer_id();

				if ( 0 === $customer_id && ywpar_get_option( 'assign_points_to_registered_guest', 'no' ) === 'yes' ) {
					$customer    = get_user_by( 'email', $order->get_billing_email() );
					$customer_id = $customer ? $customer->ID : false;

					if ( $customer_id ) {
						$customer = ywpar_get_customer( $customer_id );
					}
				} else {
					$customer = ywpar_get_customer( $customer_id );
				}
			}

			return $customer;
		}

		/**
		 * Returns the list of all postmeta of orders used be plugin
		 *
		 * @return array
		 * @since 3.0.0
		 */
		public static function get_ordermeta_list() {
			$ordermeta = array( '_ywpar_points_earned', '_ywpar_conversion_points', '_ywpar_total_points_refunded' );

			return apply_filters( 'ywpar_ordermeta_list', $ordermeta );
		}


		/**
		 * Register the coupon amount and points in the post meta of order
		 * if there's a rewards
		 *
		 * @param int $order_id Order id.
		 *
		 * @return mixed
		 * @since  3.0.0
		 */
		public function add_order_meta( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}
			$used_coupons  = $order->get_coupon_codes();
			$order_coupons = ywpar_check_redeeming_coupons( $used_coupons );

			if ( ! $order_coupons || apply_filters( 'ywpar_not_add_order_meta', false, $order_id ) ) {
				return;
			}

			$order->update_meta_data( '_ywpar_coupon_amount', WC()->session->get( 'ywpar_coupon_code_discount' ) );
			$order->update_meta_data( '_ywpar_coupon_points', WC()->session->get( 'ywpar_coupon_code_points' ) );

			$order->save();
		}

		/**
		 * Remove the coupons after that the order is created
		 *
		 * @param WC_Order $order       Order.
		 * @param string   $status_from Previous status.
		 *
		 * @return void
		 */
		public function clear_ywpar_coupon_after_create_order( $order, $status_from ) {

			if ( 'pending' !== $status_from ) {
				return;
			}

			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			$order_coupon = ywpar_check_redeeming_coupons( $order->get_coupon_codes() );
			if ( $order_coupon ) {
				$order_coupon->delete( true );
			}
		}

		/**
		 * Remove user points used to redeem
		 *
		 * @param int|WC_Order $order Order.
		 *
		 * @return void
		 * @since    3.0.0
		 */
		public function deduce_order_points( $order ) {
			if ( is_numeric( $order ) ) {
				$order = wc_get_order( $order );
			}

			if ( ! $order ) {
				return;
			}

			$used_coupons  = $order->get_coupon_codes();
			$order_coupons = ywpar_check_redeeming_coupons( $used_coupons );
			$customer_user = $order->get_customer_id();

			/**
			 * APPLY_FILTERS: ywpar_not_deduce_order_points
			 *
			 * filter if deduce the order points.
			 *
			 * @param bool $is_deduce .
			 * @param int $order_id .
			 */
			// check if the coupon was used in the order.
			if ( 0 === $customer_user || ! $order_coupons || '' !== $order->get_meta( '_ywpar_redemped_points' ) || apply_filters( 'ywpar_not_deduce_order_points', false, $order->get_id() ) ) {
				return;
			}

			$points          = $order->get_meta( '_ywpar_coupon_points' );
			$discount_amount = $order->get_meta( '_ywpar_coupon_amount' );
			$customer_user   = $order->get_customer_id();
			$customer        = ywpar_get_customer( $customer_user );

			if ( ! $customer || 0 === (int) $points || empty( $points ) ) {
				return;
			}

			$total_points = $customer->get_total_points();

			$customer->update_points( -(int) $points, 'redeemed_points', array( 'order_id' => $order->get_id() ) );

			if ( $discount_amount ) {
				$customer->add_total_discount( $discount_amount );
			}

			$customer->save();

			if ( apply_filters( 'ywpar_flush_cache', false ) ) {
				wp_cache_flush();
			}
			if ( apply_filters( 'ywpar_update_wp_cache', false ) ) {
				$cached_user_meta                               = wp_cache_get( $customer_user, 'user_meta' );
				$cached_user_meta['_ywpar_user_total_discount'] = array( $total_points + $discount_amount );
				wp_cache_set( $customer_user, $cached_user_meta, 'user_meta' );
			}

			$order->update_meta_data( '_ywpar_redemped_points', $points );
			$order->save();
			// translators:First placeholder: number of points; second placeholder: label of points.
			$order->add_order_note( sprintf( _x( 'Customer redeemed %1$d %2$s to get a reward', 'First placeholder: number of points; second placeholder: label of points', 'yith-woocommerce-points-and-rewards' ), abs( (int) $points ), ywpar_get_option( 'points_label_plural' ) ), 0 );

		}

		/**
		 * Removed the redeemed points when an order changes status from cancelled to complete
		 *
		 * @param int $order_id Order id.
		 *
		 * @return void
		 * @since  3.0.0
		 */
		public function add_redeemed_order_points( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}
			$customer_user = $order->get_customer_id();

			if ( 0 === $customer_user ) {
				return;
			}

			$customer                   = ywpar_get_customer( $customer_user );
			$redeemed_points            = $order->get_meta( '_ywpar_redemped_points' );
			$redeemed_points_reassigned = $order->get_meta( '_ywpar_redemped_points_reassigned' );

			if ( ! $customer || '' === $redeemed_points || '' === $redeemed_points_reassigned ) {
				return;
			}

			$action = 'order_' . $order->get_status();

			$customer->update_points( -$redeemed_points, $action, array( 'order_id' => $order_id ) );
			// translators: 'First placeholder: number of points; second placeholder: reason'.
			$order->add_order_note( sprintf( _x( 'Removed %1$s for %2$s.', 'First placeholder: number of points; second placeholder: label of points', 'yith-woocommerce-points-and-rewards' ), -$redeemed_points . ' ' . ywpar_get_option( 'points_label_plural' ), ywpar_get_action_label( $action ) ), 0 );
			$order->update_meta_data( '_ywpar_redemped_points_reassigned', '' );
			$order->save();
		}

		/**
		 * Add the redeemed points when an order is cancelled
		 *
		 * @param int    $order_id         Order id.
		 * @param string $new_order_status Order status.
		 *
		 * @return void
		 * @since  3.0.0
		 */
		public function remove_redeemed_points_from_orders( $order_id, $new_order_status = 'refunded' ) {
			$order         = wc_get_order( $order_id );
            $customer_user = $order->get_customer_id() ?: get_user_by( 'email', $order->get_billing_email() );

			if ( 0 === $customer_user ) {
				return;
			}

			$customer                   = ywpar_get_customer( $customer_user );
			$points                     = $order->get_meta( '_ywpar_redemped_points' );
			$redeemed_points_reassigned = $order->get_meta( '_ywpar_redemped_points_reassigned' );
			$discount_amount            = (int) $order->get_meta( '_ywpar_coupon_amount' );

			if ( ! $customer || '' === $points || '' !== $redeemed_points_reassigned ) {
				return;
			}

			$action = 'refunded' ? 'order_refund' : 'order_cancelled';
			$customer->add_total_discount( -$discount_amount );
			$customer->update_points( $points, $action, array( 'order_id' => $order_id, 'remove_redeemed_points' => true ) );
			$customer->add_rewarded_points( -$points );

			// translators: 'First placeholder: number of points; second placeholder: reason of action'.
			$order->add_order_note( sprintf( _x( 'Returned %1$s to customer for %2$s.', 'First placeholder: number of points; second placeholder: reason of action', 'yith-woocommerce-points-and-rewards' ), $points . ' ' . ywpar_get_option( 'points_label_plural' ), ywpar_get_action_label( $action ) ), 0 );
			$order->update_meta_data( '_ywpar_redemped_points_reassigned', $points );
			$order->save();
		}


		/**
		 * Remove points to the order if there's a partial refund
		 *
		 * @param int $order_id  Order id.
		 * @param int $refund_id Refund id.
		 *
		 * @return void
		 * @since   3.0.0
		 */
		public function remove_order_points_refund( $order_id, $refund_id ) {

			$action = current_action();
			$order  = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$point_earned = $order->get_meta( '_ywpar_points_earned' );
			$user_id      = $order->get_user_id();

			if ( $point_earned <= 0 || ! $user_id ) {
				return;
			}
			$customer = ywpar_get_customer( $user_id );

			$actual_points = $customer->get_total_points();
			if ( $actual_points <= 0 ) {
				return; // if the use has not points means he used them so we cannot get them back
			}

			/**
			 * WC_Order_Refund
			 *
			 * @var WC_Order_Refund $refund
			 */
			$refund                = wc_get_order( $refund_id );
			$new_total_refunded    = 0;
			$total_points_refunded = $order->get_meta( '_ywpar_total_points_refunded' );
			$total_points_refunded = empty( $total_points_refunded ) ? 0 : $total_points_refunded;
			$refund_items          = $refund->get_items( array( 'line_item', 'tax', 'shipping', 'fee', 'coupon' ) );

			$currency               = $order->get_currency();
			$total_points_to_refund = 0;
			if ( $refund_items ) {
				foreach ( $refund_items as $refund_item ) {
					/**
					 * WC_Order_Item_Product
					 *
					 * @var WC_Order_Item_Product $refund_item
					 */
					if ( ! $refund_item instanceof WC_Order_Item_Product ) {
						continue;
					}

					$product                 = $refund_item->get_product();
					$line_item_id            = $refund_item->get_meta( '_refunded_item_id' );
					$original_item           = $order->get_item( $line_item_id );
					$product_point_to_refund = $original_item->get_meta( '_ywpar_total_points' );

					if ( 'incl' === ywpar_get_option( 'earn_prices_tax', get_option( 'woocommerce_tax_display_shop', 'incl' ) ) ) {
						$original_total = ( $original_item->get_total() + $original_item->get_total_tax() );
						$refund_total   = ( -1.0 ) * ( $refund_item->get_total() + $refund_item->get_total_tax() );
					} else {
						$original_total = $original_item->get_total();
						$refund_total   = ( -1.0 ) * $refund_item->get_total();
					}

					if ( '' === $product_point_to_refund || (float) $original_total !== (float) $refund_total ) {
						$product_can_earn = yith_points()->earning->get_product_points( $product, $currency, true, $customer );
						if ( $product_can_earn > 0 ) {
							$product_point_to_refund = yith_points()->earning->get_points_earned_from_price( $refund_total, $currency, true );

							$valid_rules = YITH_WC_Points_Rewards_Helper::get_earning_rules_valid_for_product( $product, $customer );

							$product_rules    = array();
							$on_sale_rules    = array();
							$categories_rules = array();
							$tags_rules       = array();
							$general_rules    = array();

							if ( $valid_rules ) {
								foreach ( $valid_rules as $valid_rule ) {

									switch ( $valid_rule->get_apply_to() ) {
										case 'selected_products':
											array_push( $product_rules, $valid_rule );
											break;
										case 'on_sale_products':
											array_push( $on_sale_rules, $valid_rule );
											break;
										case 'selected_categories':
											array_push( $categories_rules, $valid_rule );
											break;
										case 'selected_tags':
											array_push( $tags_rules, $valid_rule );
											break;
										default:
											array_push( $general_rules, $valid_rule );
									}
								}

								if ( ! empty( $product_rules ) ) {
									$valid_rule              = $product_rules[0];
									$product_point_to_refund = $valid_rule->calculate_points( $product, $product_point_to_refund, $currency );
								}
								if ( ! empty( $on_sale_rules ) ) {
									$valid_rule              = $on_sale_rules[0];
									$product_point_to_refund = $valid_rule->calculate_points( $product, $product_point_to_refund, $currency );
								} elseif ( ! empty( $categories_rules ) ) {
									$valid_rule              = $categories_rules[0];
									$product_point_to_refund = $valid_rule->calculate_points( $product, $product_point_to_refund, $currency );
								} elseif ( ! empty( $tags_rules ) ) {
									$valid_rule              = $tags_rules[0];
									$product_point_to_refund = $valid_rule->calculate_points( $product, $product_point_to_refund, $currency );
								} elseif ( ! empty( $general_rules ) ) {
									$valid_rule              = $general_rules[0];
									$product_point_to_refund = $valid_rule->calculate_points( $product, $product_point_to_refund, $currency );
								}
							}
						}
					}
					$total_points_to_refund += abs( $product_point_to_refund );
				}

				$new_total_refunded = $total_points_to_refund;

			} else {
				$total_points_to_refund = $point_earned - $total_points_refunded;
				$new_total_refunded     = $total_points_to_refund;
			}
			$new_total_refunded = $new_total_refunded >= $point_earned ? $point_earned : $new_total_refunded;

			if ( $new_total_refunded > 0 ) {
				$total_points_to_refund = $new_total_refunded > $actual_points ? $actual_points : $new_total_refunded;
				$order->update_meta_data( '_ywpar_total_points_refunded', $total_points_to_refund );
				$order->save();

				/**
				 * DO_ACTION: ywpar_customer_removed_points
				 *
				 * action triggered before remove point to customer in a refund.
				 *
				 * @param int $total_points_to_refund the points to refund.
				 * @param WC_Order $order the order.
				 */
				do_action( 'ywpar_customer_removed_points', $total_points_to_refund, $order );
				$customer->update_points( -$total_points_to_refund, 'order_refund', array( 'order_id' => $order_id, 'remove_collected_points' => true ) );
				// translators:First placeholder: number of point; second placeholder: label of points.
				$order->add_order_note( sprintf( _x( 'Removed %1$d %2$s to customer for order refund.', 'First placeholder: number of point; second placeholder: label of points', 'yith-woocommerce-points-and-rewards' ), $total_points_to_refund, ywpar_get_option( 'points_label_plural' ) ), 0 );

			}
		}
	}

}
