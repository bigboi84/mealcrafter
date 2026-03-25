<?php
/**
 * Class to earning points
 *
 * @class   YITH_WC_Points_Rewards_Earning
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

require_once YITH_YWPAR_INC . 'legacy/abstract-yith-wc-points-rewards-earning-legacy.php';

if ( ! class_exists( 'YITH_WC_Points_Rewards_Earning' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Earning
	 */
	class YITH_WC_Points_Rewards_Earning extends YITH_WC_Points_Rewards_Earning_Legacy {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Earning
		 */
		protected static $instance;

		/**
		 * Points applied
		 *
		 * @var bool
		 */
		protected $points_applied = false;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Earning
		 * @since  1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			if ( ! ywpar_automatic_earning_points_enabled() ) {
				return;
			}

			add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_points_earned_from_cart' ) );
			add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'save_points_earned_from_cart' ) );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'set_points_on_cart_item' ), 20, 2 );

			// add points for previous orders to a new registered user.
			if ( 'yes' === ywpar_get_option( 'assign_older_orders_points_to_new_registered_user' ) ) {
				add_action( 'user_register', array( $this, 'add_points_for_previous_orders_on_registration' ) );
			}

			add_action( 'init', array( $this, 'init' ) );
		}

		/**
		 * Save the total point inside the cart item
		 *
		 * @param array  $cart_item     Cart item.
		 * @param string $cart_item_key Cart item key.
		 *
		 * @return array
		 */
		public function set_points_on_cart_item( $cart_item, $cart_item_key ) {
			$cart_item['ywpar_total_points'] = $this->calculate_points_for_cart_item( $cart_item, $cart_item_key, 0 );

			return $cart_item;
		}

		/**
		 *  Start the game for earn point with order and extra points.
		 *
		 *  Triggered by 'init' hook.
		 *
		 * @since  1.6.0
		 */
		public function init() {

			$status_to_earn = ywpar_get_option( 'order_status_to_earn_points' );

			if ( $status_to_earn ) {
				foreach ( $status_to_earn as $hook ) {
					add_action( $hook, array( $this, 'add_order_points' ), 5 );
				}
			}
		}

		/**
		 * Save the points that are in cart in a post meta of the order
		 *
		 * @param int|Automattic\WooCommerce\Admin\Overrides\Order $order_id Order id or Automattic\WooCommerce\Admin\Overrides\Order object.
		 *
		 * @return  void
		 * @since   1.5.0
		 * @version 4.0.0
		 */
		public function save_points_earned_from_cart( $order_id ) {

			$points_from_cart = $this->calculate_points_on_cart();
			$order            = $order_id instanceof Automattic\WooCommerce\Admin\Overrides\Order ? $order_id : wc_get_order( $order_id );
			/**
			 * DO_ACTION: ywpar_saved_points_earned_from_cart
			 *
			 * Hook on saved points earned from cart.
			 *
			 * @param WC_Order $order the order.
			 */
			do_action( 'ywpar_saved_points_earned_from_cart', $order );
			$order->update_meta_data( 'ywpar_points_from_cart', $points_from_cart );
			$order->save();
		}


		/**
		 * Calculate the points for a cart item.
		 *
		 * @param array  $cart_item                Cart item.
		 * @param string $cart_item_key            Cart item key.
		 * @param int    $items_that_have_discount Number of cart items that can obtain points.
		 *
		 * @return int
		 */
		public function calculate_points_for_cart_item( $cart_item, $cart_item_key, $items_that_have_discount = '' ) {

			$cart_contents        = WC()->cart->get_cart_contents();
			$total_product_points = 0;

			if ( isset( $cart_item['bundled_by'], $cart_contents[ $cart_item['bundled_by'] ] ) ) {
				$bundle_id      = $cart_contents[ $cart_item['bundled_by'] ]['product_id'];
				$bundle_product = wc_get_product( $bundle_id );
				if ( $bundle_product ) {
					$p_earned = $bundle_product->get_meta( '_ywpar_point_earned' );
					if ( ! empty( $p_earned ) && $bundle_product->get_meta( '_yith_wcpb_per_item_pricing' ) === 'yes' ) {
						return $total_product_points;
					}
				}
			}

			$product_point = 0;
			if ( apply_filters( 'ywpar_calculate_points_for_product', true, $cart_item, $cart_item_key ) &&
                isset( $cart_item[ 'line_subtotal' ] ) && isset( $cart_item[ 'line_subtotal_tax' ] )
            ) {
				$item_data     = array(
					'total' => $cart_item['line_subtotal'],
					'tax'   => $cart_item['line_subtotal_tax'],
				);
				$product_point = $this->get_product_points( $cart_item['data'], '', false, null, $item_data );
			}

			$total_product_points = 'subtotal' === ywpar_get_option( 'earn_prices_calc', 'unit' ) && ! empty( $item_data ) ? $product_point : floatval( $product_point * $cart_item['quantity'] );

			if ( WC()->cart->applied_coupons && isset( WC()->cart->discount_cart ) && WC()->cart->discount_cart > 0 ) {
				if ( ywpar_get_option( 'remove_points_coupon', 'yes' ) === 'yes' && $cart_item['line_subtotal'] ) {
					// Calculate points for the current cart item based on the proportional discount only for the items that can obtain the points.
					if ( $items_that_have_discount > 0 ) {
						$discount_per_item    = WC()->cart->discount_cart / $items_that_have_discount;
						$total_product_points = ( $cart_item['line_subtotal'] - $discount_per_item ) / $cart_item['line_subtotal'] * $total_product_points;
					} else { // Calculate points for the current cart item based on the proportional discount for all cart items.
						$total_product_points = ( $cart_item['line_total'] / $cart_item['line_subtotal'] ) * $total_product_points;
					}
				}

				/**
				 * APPLY_FILTERS: ywpar_disable_earning_if_there_is_a_coupon
				 *
				 * disable earning if there's a coupon.
				 */
				if ( apply_filters( 'ywpar_disable_earning_if_there_is_a_coupon', false ) || ( 'yes' === ywpar_get_option( 'disable_earning_while_reedeming' ) && ywpar_cart_has_redeeming_coupon() ) ) {
					$total_product_points = 0;
				}
			}

			/**
			 * APPLY_FILTERS: calculate_points_for_cart_item
			 *
			 * Calculate points for cart item
			 *
			 * @param int                            $total_product_points
			 * @param array                          $cart_item
			 * @param string                         $cart_item_key
			 * @param YITH_WC_Points_Rewards_Earning $this
			 */
			$total_product_points = apply_filters( 'calculate_points_for_cart_item', $total_product_points, $cart_item, $cart_item_key, $this );

			return yith_ywpar_round_points( $total_product_points );
		}

		/**
		 * Calculate the total points in the carts
		 *
		 * @param bool $integer Precision of points.
		 *
		 * @return int $points
		 * @since   1.0.0
		 */
		public function calculate_points_on_cart( $integer = true ) {

			$items      = WC()->cart->get_cart();
			$tot_points = 0;

			// First loop to get the number of cart items that can obtain points. This avoids problems when calculating the corresponding points only to items that can obtain the discount.
			$items_that_have_discount = 0;
			foreach ( $items as $cart_item ) {
				$item_data = array(
					'total' => $cart_item['line_subtotal'],
					'tax'   => $cart_item['line_subtotal_tax'],
				);
				if ( $this->get_product_points( $cart_item['data'], '', false, null, $item_data ) > 0 ) {
					++$items_that_have_discount;
				}
			}

			// Second loop for calculating the points for each item.
			foreach ( $items as $cart_item_key => $cart_item ) {
				$tot_points += $this->calculate_points_for_cart_item( $cart_item, $cart_item_key, $items_that_have_discount );
			}
			$tot_points = ( $tot_points < 0 ) ? 0 : $tot_points;
			$tot_points = $integer ? yith_ywpar_round_points( $tot_points ) : $tot_points;
			/**
			 * APPLY_FILTERS: ywpar_calculate_points_on_cart
			 *
			 * filter the points calculated on the cart.
			 *
			 * @param int $tot_points
			 */
			return apply_filters( 'ywpar_calculate_points_on_cart', $tot_points );
		}

		/**
		 * Add points to the order from order_id
		 *
		 * @param int $order_id Order id.
		 *
		 * @return void
		 * @since   1.0.0
		 */
		public function add_order_points( $order_id ) {

			$order  = wc_get_order( $order_id );
			$is_set = $order->get_meta( '_ywpar_points_earned' );

			// return if the points are already calculated.
			if ( '' !== $is_set || is_array( $this->points_applied ) && in_array( $order_id, $this->points_applied, true ) || apply_filters( 'ywpar_add_order_points', false, $order_id ) ) {
				return;
			}
			$customer = ywpar_get_point_customer_from_order( $order );

			if ( ! $customer || ! $customer->is_enabled() ) {
				return;
			}

			// if the order has a redeeming coupon and the option disable_earning_while_reedeming is on return.
			if ( ywpar_get_option( 'disable_earning_while_reedeming', 'no' ) === 'yes' && ywpar_order_has_redeeming_coupon( $order ) ) {
				return;
			}

			$tot_points = trim( $order->get_meta( 'ywpar_points_from_cart' ) );
			/**
			 * APPLY_FILTERS: ywpar_force_calculation_of_order_points
			 *
			 * force to recalculate the points from the order items.
			 *
			 * @param bool $to_force
			 * @param int $points the points to add
			 * @param WC_Order $order the order
			 *
			 */
			$tot_points = apply_filters( 'ywpar_force_calculation_of_order_points', '' === $tot_points, $tot_points, $order ) ? $this->calculate_order_points_from_items( $order, $customer ) : $tot_points;
			/**
			 * APPLY_FILTERS: ywpar_earned_total_points_by_order
			 *
			 * change the points value for the order.
			 *
			 * @param int $tot_points the points to gain.
			 * @param WC_Order $order the order.
			 */			
			$tot_points = apply_filters( 'ywpar_earned_total_points_by_order', $tot_points, $order );

			// update order meta and add note to the order.
			$order->update_meta_data( '_ywpar_points_earned', $tot_points );
			$order->update_meta_data( '_ywpar_conversion_points', $this->get_conversion_option( $order->get_currency(), $order ) );
			$order->save();

			if ( ! $this->points_applied ) {
				$this->points_applied = array();
			}

			$this->points_applied[] = $order_id;
			// translators: First placeholder: number of points; second placeholder: label of points.
			$order->add_order_note( sprintf( _x( 'Customer earned %1$d %2$s for this purchase.', 'First placeholder: number of points; second placeholder: label of points', 'yith-woocommerce-points-and-rewards' ), $tot_points, ywpar_get_option( 'points_label_plural' ) ), 0 );
			/**
			 * DO_ACTION: ywpar_added_earned_points_to_order
			 *
			 * Hook on points added to the order.
			 *
			 * @param WC_Order $order the order.
			 */
			do_action( 'ywpar_added_earned_points_to_order', $order );
			$customer->update_points( $tot_points, 'order_completed', array( 'order_id' => $order_id ) );
			yith_points()->extra_points->handle_actions( array( 'num_of_orders', 'amount_spent', 'checkout_threshold' ), $customer, $order_id );
		}

		/**
		 * Return the global points of an object from price
		 *
		 * @param float  $price    Price of the object.
		 * @param string $currency Currency.
		 * @param bool   $integer  Precision of points.
		 *
		 * @return int|float
		 */
		public function get_points_earned_from_price( $price, $currency, $integer = false ) {
			/**
			 * APPLY_FILTERS: ywpar_get_points_earned_from_price
			 *
			 * Manage the price before to apply conversion.
			 *
			 * @param float                          $price    price.
			 * @param string                         $currency currency.
			 * @param integer                        $integer  precision of points.
			 * @param YITH_WC_Points_Rewards_Earning $this     YITH_WC_Points_Rewards_Earning object
			 */
			$price      = apply_filters( 'ywpar_get_points_earned_from_price', $price, $currency, $integer, $this );
			$conversion = $this->get_conversion_option( $currency );
			$points     = ( (float) $price / (float) $conversion['money'] ) * $conversion['points'];

			return $integer ? yith_ywpar_round_points( $points ) : $points;
		}


		/**
		 * Return the global points of an object from price
		 *
		 * @param int  $points  Points.
		 * @param bool $integer Precision of points.
		 *
		 * @return float
		 * @since   1.0.0
		 */
		public function get_price_from_point_earned( $points, $integer = false ) {
			$conversion = $this->get_conversion_option();

			$price = $points * $conversion['money'] / $conversion['points'];

			return $price;
		}


		/**
		 * Get points of a product.
		 *
		 * @param WC_Product                              $product   Product.
		 * @param string                                  $currency  Currency.
		 * @param bool                                    $integer   Precision of points.
		 * @param WP_User|YITH_WC_Points_Rewards_Customer $user      User.
		 * @param array                                   $item_data The item data.
		 *
		 * @return int|float
		 */
		public function get_product_points( $product, $currency = '', $integer = true, $user = null, $item_data = array() ) {
			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			$product_points = false;
			$currency       = ywpar_get_currency( $currency );

			$customer_id   = ywpar_get_current_customer_id( $user );
			$points_cached = get_transient( 'ywpar_product_points' );
			/**
			 * APPLY_FILTERS: ywpar_get_product_points_cache
			 *
			 * Used to avoid cache
			 *
			 * @param int $points_cached empty array to not use cache
			 */
			$points_cached = apply_filters( 'ywpar_get_product_points_cache', $points_cached ? $points_cached : array() );
			$index         = $currency . '_' . ( $integer ? 'integer' : 'decimal' );

			if ( false !== $points_cached ) {
				if ( isset( $points_cached[ $product->get_id() ][ $customer_id ][ $index ] ) ) {
					$product_points = $points_cached[ $product->get_id() ][ $customer_id ][ $index ];
				}
			}

			if ( ! $product_points ) {
				$points_cached  = array();
				$product_points = 'subtotal' === ywpar_get_option( 'earn_prices_calc', 'unit' ) && ! empty( $item_data ) ? $this->calculate_product_points_by_subtotal( $product, $currency, $integer, $user, $item_data ) : $this->calculate_product_points( $product, $currency, $integer, $user );

				$points_cached[ $product->get_id() ][ $customer_id ][ $index ] = $product_points;
				/**
				 * APPLY_FILTERS: ywpar_product_points_use_transient
				 *
				 * Use Product Points Set Transient
				 *
				 * @param bool $value true by default.
				 */
				if ( apply_filters( 'ywpar_product_points_use_transient', true ) ) {
					set_transient( 'ywpar_product_points', $points_cached, 30 * DAY_IN_SECONDS );
				}
			}

			return $product_points;
		}

		/**
		 * Return the points of a product by subtotal.
		 *
		 * @param WC_Product $product   Product.
		 * @param string     $currency  Currency.
		 * @param bool       $integer   Precision of points.
		 * @param WP_User    $user      User.
		 * @param array      $item_data The item data.
		 *
		 * @return int|float
		 */
		private function calculate_product_points_by_subtotal( $product, $currency, $integer, $user, $item_data ) {
			$calculated_points = 0;
			$currency          = ywpar_get_currency( $currency );
			$product           = is_numeric( $product ) ? wc_get_product( $product ) : $product;

			if ( ! $product instanceof WC_Product || ywpar_exclude_product_on_sale( $product ) ) {
				return $calculated_points;
			}

			$tax_mode          = ywpar_get_option( 'earn_prices_tax', get_option( 'woocommerce_tax_display_shop', 'incl' ) );
			$product_price     = 'incl' === $tax_mode ? $item_data['total'] + $item_data['tax'] : $item_data['total'];
			$product_price     = round( $product_price, wc_get_price_decimals() );
			$calculated_points = $this->get_points_earned_from_price( $product_price, $currency, true );
			$calculated_points = $this->add_points_for_rules( $calculated_points, $product, $user, $currency );

			return $integer ? yith_ywpar_round_points( $calculated_points ) : $calculated_points;
		}

		/**
		 * Return the points of a product.
		 *
		 * @param WC_Product $product  Product.
		 * @param string     $currency Currency.
		 * @param bool       $integer  Precision of points.
		 * @param WP_User    $user     User.
		 *
		 * @return int|float
		 */
		public function calculate_product_points( $product, $currency = '', $integer = true, $user = null ) {
			$calculated_points = 0;
			$currency          = ywpar_get_currency( $currency );
			$product           = is_numeric( $product ) ? wc_get_product( $product ) : $product;

			if ( ! $product instanceof WC_Product || ywpar_exclude_product_on_sale( $product ) ) {
				return $calculated_points;
			}

			if ( $product->is_type( 'variable' ) ) {
				/**
				 * Variable product.
				 *
				 * @var $product WC_Product_Variable
				 */
				return $this->calculate_product_points_on_variable( $product, $integer );
			}

			if ( $product->is_type( 'grouped' ) ) {

				foreach ( $product->get_children() as $child_id ) {
					$child = wc_get_product( $child_id );

					$calculated_points += $this->calculate_product_points( $child, $currency, $integer );
				}

				return $calculated_points;
			}

			$product_price     = ywpar_get_product_price( $product, 'earn', $currency );
			$calculated_points = $this->get_points_earned_from_price( $product_price, $currency, true );

			$calculated_points = $this->add_points_for_rules( $calculated_points, $product, $user, $currency );

			return $integer ? yith_ywpar_round_points( $calculated_points ) : $calculated_points;
		}

		/**
		 * Add points for rules
		 *
		 * @param int        $points   The points.
		 * @param WC_Product $product  The product.
		 * @param WP_User    $user     The user.
		 * @param string     $currency The currency.
		 *
		 * @return int
		 * @since 4.7
		 */
		public function add_points_for_rules( $points, $product, $user, $currency ) {
			$valid_rules = YITH_WC_Points_Rewards_Helper::get_earning_rules_valid_for_product( $product, $user );

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
					$valid_rule = $product_rules[0];
					$points     = $valid_rule->calculate_points( $product, $points, $currency );
				}
				if ( ! empty( $on_sale_rules ) ) {
					$valid_rule = $on_sale_rules[0];
					$points     = $valid_rule->calculate_points( $product, $points, $currency );
				} elseif ( ! empty( $categories_rules ) ) {
					$valid_rule = $categories_rules[0];
					$points     = $valid_rule->calculate_points( $product, $points, $currency );
				} elseif ( ! empty( $tags_rules ) ) {
					$valid_rule = $tags_rules[0];
					$points     = $valid_rule->calculate_points( $product, $points, $currency );
				} elseif ( ! empty( $general_rules ) ) {
					$valid_rule = $general_rules[0];
					$points     = $valid_rule->calculate_points( $product, $points, $currency );
				}
			}

			return $points;
		}

		/**
		 * Calculate the points of a product variable for a single item
		 *
		 * @param WC_Product_Variable|int $product Variable product.
		 * @param bool                    $integer Precision of points.
		 *
		 * @return int|string
		 * @since   1.0.0
		 */
		public function calculate_product_points_on_variable( $product, $integer = true ) {
			$calculated_points = 0;
			$product           = is_numeric( $product ) ? wc_get_product( $product ) : $product;

			if ( ! $product->is_type( 'variable' ) ) {
				return $calculated_points;
			}

			$variations = $product->get_available_variations();
			$points     = array();
			if ( ! empty( $variations ) ) {
				foreach ( $variations as $variation ) {
					$points[] = $this->calculate_product_points( $variation['variation_id'] );
				}
			}

			$points = array_unique( $points );

			if ( count( $points ) === 1 ) {
				$calculated_points = $points[0];
			} elseif ( count( $points ) > 0 ) {
				$calculated_points = min( $points ) . '-' . max( $points );
			}

			/**
			 * APPLY_FILTERS: ywpar_calculate_product_points_on_variable
			 *
			 * filter the points calculated for a product.
			 *
			 * @param int $calculated_points points for the product.
			 * @param WC_Product $product the product.
			 */
			return apply_filters( 'ywpar_calculate_product_points_on_variable', $calculated_points, $product );
		}

		/**
		 * Return the conversion options
		 *
		 * @param string        $currency Currency.
		 * @param bool|WC_Order $order    Order.
		 *
		 * @return  array
		 * @since   1.0.0
		 */
		public function get_conversion_option( $currency = '', $order = false ) {
			$currency = ywpar_get_currency( $currency );

			$conversion = $this->get_main_conversion_option( $currency );

			$conversion['money']  = ( empty( $conversion['money'] ) ) ? 1 : $conversion['money'];
			$conversion['points'] = ( empty( $conversion['points'] ) ) ? 1 : $conversion['points'];

			/**
			 * APPLY_FILTERS: ywpar_conversion_points_rate
			 *
			 * edit the conversion rate.
			 *
			 * @param array $conversion
			 */
			return apply_filters( 'ywpar_conversion_points_rate', $conversion );
		}


		/**
		 * Return the main conversion rate
		 *
		 * @param string $currency Currency.
		 *
		 * @return  array
		 * @since   2.2.0
		 */
		public function get_main_conversion_option( $currency = '' ) {
			$currency   = ywpar_get_currency( $currency );
			$conversion = ywpar_get_option( 'earn_points_conversion_rate' );

			$conversion = isset( $conversion[ $currency ] ) ? $conversion[ $currency ] : array(
				'money'  => 0,
				'points' => 0,
			);

			return apply_filters( 'ywpar_conversion_points_rate', $conversion );
		}

		/**
		 * Assign Points to previous orders on user registration by user email = billing email.
		 *
		 * @param int $user_id user id.
		 *
		 * @since  1.7.3
		 */
		public function add_points_for_previous_orders_on_registration( $user_id ) {
			// getting the user.
			$customer = ywpar_get_customer( $user_id );

			if ( ! $customer ) {
				return;
			}

			$user_email = $customer->get_wc_customer()->get_email();

			if ( empty( $user_email ) ) {
				return;
			}

			$orders_query = array(
				'billing_email' => $user_email,
				'status'        => 'completed',
				'limit'         => -1,
				'orderby'       => 'date',
				'order'         => 'DESC',
			);

			$start_date = yith_points()->points_log->get_start_date_of_all_actions();
			if ( $start_date ) {
				$orders_query['date_completed'] = '>=' . $start_date;
			}

			$orders = wc_get_orders( $orders_query );

			if ( $orders ) {
				foreach ( $orders as $order ) {
					$this->add_order_points( $order->get_id() );
				}
			}
		}

		/**
		 * Calculate order points from order items
		 *
		 * @param WC_Order                        $order    Order.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 *
		 * @return int
		 */
		private function calculate_order_points_from_items( $order, $customer ) {
			$tot_points  = 0;
			$order_items = $order->get_items();

			if ( ! empty( $order_items ) ) {
				foreach ( $order_items as $order_item ) {
					$product = $order_item->get_product();
					/**
					 * APPLY_FILTERS: ywpar_calculate_order_points_from_items_type
					 *
					 * Change from order item subtotal to total.
					 *
					 * @param string                          $price      item price
					 * @param WC_Order                        $order      Order
					 * @param WC_Order_Item_Product           $order_item order item
					 * @param YITH_WC_Points_Rewards_Customer $customer   customer object
					 */
					$price       = apply_filters( 'ywpar_calculate_order_points_from_items_type', $order_item->get_subtotal(), $order, $order_item, $customer );
					$item_points = $this->get_points_earned_from_price( $price, $order->get_currency() );
					if ( $product ) {
						$item_data      = array(
							'total' => $order_item->get_data()['total'],
							'tax'   => $order_item->get_data()['total_tax'],
						);
						$product_points = $this->get_product_points( $product, $order->get_currency(), true, $customer, $item_data );
						// get the minor value.
						$item_points = $product_points < $item_points ? $product_points : $item_points;
						if ( apply_filters( 'ywpar_force_use_points_from_product', false, $product ) ) {
							$item_points = $product_points;
						}
					}

					if ( 'subtotal' !== ywpar_get_option( 'earn_prices_calc', 'unit' )  ) {
						$tot_points += $item_points * $order_item['qty'];
					} else {
						$tot_points += $item_points;
					}
				}
			}

			if ( isset( $_REQUEST['action'] ) && 'ywpar_bulk_action' === sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) || apply_filters( 'ywpar_check_coupon_points_from_item', false, $order ) ) { //phpcs:ignore
				$coupons = $order->get_coupon_codes();
				if ( count( $coupons ) > 0 && ywpar_get_option( 'remove_points_coupon' ) === 'yes' ) {
					$tot_points -= $this->get_points_earned_from_price( $order->get_total_discount(), $order->get_currency() );
				}
			}

			return ( $tot_points < 0 ) ? 0 : yith_ywpar_round_points( $tot_points );
		}
	}

}
