<?php
/**
 * Class to redeem points
 *
 * @class   YITH_WC_Points_Rewards_Redeeming
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

require_once YITH_YWPAR_INC . 'legacy/abstract-yith-wc-points-rewards-redeeming-legacy.php';

if ( ! class_exists( 'YITH_WC_Points_Rewards_Redeeming' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Redeeming
	 */
	class YITH_WC_Points_Rewards_Redeeming extends YITH_WC_Points_Rewards_Redeeming_Legacy {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Redeeming
		 */
		protected static $instance;

		/**
		 * Max discount
		 *
		 * @var mixed
		 */
		protected $max_discount = false;

		/**
		 * Max points
		 *
		 * @var mixed
		 */
		protected $max_points = false;

		/**
		 * Coupon code
		 *
		 * @var string
		 */
		protected $current_coupon_code = '';

		/**
		 * Max percentage discount
		 *
		 * @var mixed
		 */
		protected $max_percentage_discount = false;

		/**
		 * Coupon prefix
		 *
		 * @var string
		 */
		protected $label_coupon_prefix = 'ywpar_discount';

		/**
		 * Order class
		 *
		 * @var YITH_WC_Points_Rewards_Orders
		 */
		public $order;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Redeeming
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

			$this->order = YITH_WC_Points_Rewards_Orders::get_instance();
			if ( 'yes' !== ywpar_get_option( 'enable_rewards_points' ) ) {
				return;
			}

			if ( is_user_logged_in() ) {
				// add coupon on cart.
				add_action( 'wp_loaded', array( $this, 'apply_discount' ), 30 );
				if ( apply_filters( 'ywpar_redeem_uses_ajax', false ) ) {
					add_action( 'wc_ajax_ywpar_apply_points', array( $this, 'apply_discount' ) );
				}

				// update discount.
				add_action( 'woocommerce_cart_item_removed', array( $this, 'update_discount' ) );
				add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'update_discount' ) );
				add_action( 'woocommerce_before_cart_item_quantity_zero', array( $this, 'update_discount' ) );
				add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'update_discount' ), 99 );

				add_filter( 'woocommerce_coupon_message', array( $this, 'coupon_rewards_message' ), 15, 3 );
				add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'coupon_label' ), 10, 2 );
				add_action( 'woocommerce_removed_coupon', array( $this, 'clear_current_coupon' ) );

				add_action( 'wc_ajax_ywpar_calc_discount_value', array( $this, 'calc_discount_value' ) );

				if ( ywpar_get_option( 'autoapply_points_cart_checkout', 'no' ) === 'yes' ) {
					add_action( 'template_redirect', array( $this, 'auto_apply_discount' ), 30 );
					add_action( 'woocommerce_checkout_order_processed', array( $this, 'clean_auto_apply_session' ) );
					add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'clean_auto_apply_session' ) );
					add_action( 'woocommerce_check_cart_items', array( $this, 'clean_auto_apply_session' ) );
					add_action( 'woocommerce_before_thankyou', array( $this, 'clean_auto_apply_session' ) );
				}
			}

			// clear coupon.
			add_action( 'wp_loaded', array( $this, 'ywpar_set_cron' ) );
			add_action( 'ywpar_clean_cron', array( $this, 'clear_coupons' ) );

		}

		/**
		 * Return the max discount that can be used in the cart fore rewards
		 * must be called after the function calculate_points_and_discount
		 *
		 * @return  float
		 * @since   1.0.0
		 */
		public function get_max_discount() {
			// APPLY_FILTERS: ywpar_rewards_max_discount | filter the max discount
			return apply_filters( 'ywpar_rewards_max_discount', $this->max_discount );
		}

		/**
		 * Return the value in money of points
		 *
		 * @param int   $points Points.
		 * @param mixed $customer Customer.
		 * @param bool  $formatted If the worth amount should be formatted..
		 *
		 * @return float
		 */
		public function calculate_price_worth_from_points( $points, $customer, $formatted = true ) {
			$worth = '';
			if ( $points > 0 ) {
				$conversion_method = $this->get_conversion_method();
				$conversion_rate   = $this->get_conversion_rate_rewards( '', $customer );
				if ( 'fixed' === $conversion_method ) {
					$money = $conversion_rate['money'];
					$worth = abs( ( $points / $conversion_rate['points'] ) * $money );
					$worth = $formatted ? wc_price( abs( ( $points / $conversion_rate['points'] ) * $money ) ) : $worth;
				} else {
					$discount     = $conversion_rate['discount'];
					$to_redeem    = ( ( (int)$points / (int)$conversion_rate['points'] ) * (int)$discount );		
					$to_redeem    = $to_redeem > 100 ? 100 : $to_redeem;
					$max_discount = $this->get_max_percentage_discount_to_redeem();
					$min_discount = $this->get_min_percentage_discount_to_redeem();
					$to_redeem    = ( ! empty( $max_discount ) && $max_discount < $to_redeem ) ? $max_discount : $to_redeem;
					$to_redeem    = ( ! empty( $min_discount ) && $min_discount > $to_redeem ) ? '' : $to_redeem;
					$worth        = empty( $to_redeem ) ? '' : ( $formatted ? sprintf( '%s %s', $to_redeem . '%', _x( 'on order total', '20% on order total', 'yith-woocommerce-points-and-rewards' ) ) : $to_redeem );
				}
			}

			return $worth;
		}

		/**
		 * Return the amount worth of a product.
		 *
		 * @param int|WC_Product $product Product.
		 * @param int            $points_earned Product points earned from the product.
		 * @param bool           $formatted Format price or not.
		 *
		 * @return float
		 */
		public function calculate_price_worth( $product, $points_earned, $formatted = false ) {
			$price_worth = 0;
			$product     = is_numeric( $product ) ? wc_get_product( $product ) : $product;
			if ( ! $product ) {
				return $price_worth;
			}

			if ( $product->is_type( 'variable' ) ) {
				$variations  = $product->get_available_variations();
				$price_worth = array();
				if ( $variations ) {
					foreach ( $variations as $variation ) {
						$price_worth[ $variation['variation_id'] ] = $this->calculate_price_worth( $variation['variation_id'], yith_points()->earning->get_product_points( $variation['variation_id'] ), false );
					}

					$price_worth = array_unique( $price_worth );

					if ( count( $price_worth ) === 0 ) {
						$return = $formatted ? wc_price( 0 ) : 0;
					} elseif ( count( $price_worth ) === 1 ) {
						$return = $formatted ? wc_price( reset( $price_worth ) ) : reset( $price_worth );
					} else {
						$return = $formatted ? wc_price( min( $price_worth ) ) . '-' . wc_price( max( $price_worth ) ) : min( $price_worth ) . '-' . max( $price_worth );
					}

					return $return;
				}
			}

			$product_price           = ywpar_get_product_price( $product, 'redeem' );
			$price_from_point_earned = yith_points()->earning->get_price_from_point_earned( $points_earned );

			if ( $price_from_point_earned !== $product_price ) {
				$product_price = $price_from_point_earned;
			}

			$max_discount      = $this->calculate_product_max_discount( $product, $product_price );
			$conversion_method = $this->get_conversion_method();

			if ( 'fixed' === $conversion_method ) {
				$conversion  = $this->get_conversion_rate_rewards();
				$point_value = (int)$conversion['money'] / (int)$conversion['points'];
				$price_worth = $points_earned * $point_value;
				$price_worth = $price_worth > $max_discount ? $max_discount : $price_worth;

			}

			/**
			 * DO_ACTION: before_return_calculate_price_worth
			 *
			 * action triggered before return calculate price worth.
			 *
			 * @param
			 */
			do_action( 'before_return_calculate_price_worth' );

			$formatted_price_worth = $formatted ? wc_price( $price_worth ) : $price_worth;
			/**
			 * APPLY_FILTERS: ywpar_calculate_product_discount
			 *
			 * filter the formatted price worth.
			 *
			 * @param string $formatted_price_worth .
			 * @param int $product_id .
			 * @param int|float $price_worth . 
			 * @param int $points_earned .
			 */
			return apply_filters( 'ywpar_calculate_product_discount', $formatted_price_worth, $product->get_id(), $price_worth, $points_earned );
		}

		/**
		 * Check if there is a minimum cart to achieve to redeem points
		 *
		 * @return bool
		 * @since 3.0.0
		 */
		public function is_valid_redeeming_points() {
			$is_valid = true;

			if ( $this->is_restriction_enabled() ) {
				$subtotal       = $this->get_cart_subtotal();
				$minimum_amount = $this->get_minimum_cart_amount_to_redeem();

				if ( $subtotal < $minimum_amount ) {
					$is_valid = false;
				}
			}

			return $is_valid;
		}

		/**
		 * Return the conversion method that can be used in the cart fore rewards
		 *
		 * @return  string
		 * @since   1.1.3
		 */
		public function get_conversion_method() {
			/**
			 * APPLY_FILTERS: ywpar_conversion_method
			 *
			 * filter the conversion method.
			 */
			return apply_filters( 'ywpar_conversion_method', ywpar_get_option( 'conversion_rate_method' ) );
		}

		/**
		 * Return the conversion rate to redeem points
		 *
		 * @param string                               $currency Currency.
		 * @param YITH_WC_Points_Rewards_Customer|null $customer Customer.
		 *
		 * @return array
		 **/
		public function get_conversion_rate_rewards( $currency = '', $customer = null ) {

			$currency = ywpar_get_currency( $currency );
			$customer = ywpar_get_customer( $customer );

			$conversion = array();

			$valid_rules = YITH_WC_Points_Rewards_Helper::get_valid_redeeming_rules( $customer );

			if ( $valid_rules ) {
				foreach ( $valid_rules as $rule ) {
					$rule = ywpar_get_redeeming_rule( $rule );

					if ( 'conversion_rate' === $rule->get_type() ) {
						$conversions = 'fixed' === $this->get_conversion_method() ? $rule->get_conversion_rate() : $rule->get_percentage_conversion_rate();
						/**
						 * APPLY_FILTERS: ywpar_redeeming_check_rule_validation
						 *
						 * filter the rule validation.
						 * 
						 * @param bool $validate .
						 * @param YITH_WC_Points_Rewards_Customer $customer .
						 * @param array $conversion .
						 * @param string $currency .
						 */
						if ( isset( $conversions[ $currency ] ) && apply_filters('ywpar_redeeming_check_rule_validation', true, $customer, $conversions, $currency)) {
							$conversion = $conversions[ $currency ];
							break;
						}
					}
				}
			}

			if ( empty( $conversion ) ) {
				$conversion = $this->get_main_conversion_rate( $currency );
			}
			/**
			 * APPLY_FILTERS: ywpar_rewards_conversion_rate
			 *
			 * filter the conversion rate.
			 * 
			 * @param array
			 */
			return apply_filters( 'ywpar_rewards_conversion_rate', $conversion );
		}

		/**
		 * Return the global conversion rate.
		 *
		 * @param string $currency Currency.
		 *
		 * @return array
		 * @since 3.0.0
		 */
		public function get_main_conversion_rate( $currency ) {
			$currency = ywpar_get_currency( $currency );

			if ( 'fixed' === $this->get_conversion_method() ) {

				$conversions = ywpar_get_option( 'rewards_conversion_rate' );
				$conversion  = isset( $conversions[ $currency ] ) ? $conversions[ $currency ] : array(
					'money'  => 0,
					'points' => 0,
				);
				/**
				 * APPLY_FILTERS: ywpar_rewards_conversion_rate
				 *
				 * filter the main conversion rate.
				 *
				 * @param array $conversion
				 */
				$conversion           = apply_filters( 'ywpar_rewards_conversion_rate', $conversion );
				$conversion['money']  = ( empty( $conversion['money'] ) ) ? 1 : $conversion['money'];
				$conversion['points'] = ( empty( $conversion['points'] ) ) ? 1 : $conversion['points'];
			} else {
				$conversions = ywpar_get_option( 'rewards_percentual_conversion_rate' );
				$conversion  = isset( $conversions[ $currency ] ) ? $conversions[ $currency ] : array(
					'points'   => 0,
					'discount' => 0,
				);
				/**
				 * APPLY_FILTERS: ywpar_rewards_percentual_conversion_rate
				 *
				 * filter the main conversion rate for percentage option.
				 *
				 * @param array $conversion
				 */
				$conversion  = apply_filters( 'ywpar_rewards_percentual_conversion_rate', $conversion );

				$conversion['points']   = ( empty( $conversion['points'] ) ) ? 1 : $conversion['points'];
				$conversion['discount'] = ( empty( $conversion['discount'] ) ) ? 1 : $conversion['discount'];
			}

			return $conversion;
		}

		/**
		 * Return if the rewards restrictions are enabled
		 *
		 * @return bool
		 * @since 3.0.0
		 */
		public function is_restriction_enabled() {
			return 'yes' === ywpar_get_option( 'apply_redeem_restrictions', 'no' );
		}

		/**
		 * Return the minimum amount on cart  necessary to redeem
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_minimum_cart_amount_to_redeem() {
			return $this->is_restriction_enabled() ? (float) ywpar_get_option( 'minimum_amount_to_redeem', '' ) : '';
		}

		/**
		 * Return the minimum amount discount necessary to redeem points
		 *
		 * Only for fixed reward method
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_min_discount_amount_to_redeem() {
			return $this->is_restriction_enabled() ? ywpar_get_option( 'minimum_amount_discount_to_redeem', '' ) : '';
		}

		/**
		 * Return the max discount amount that a user can reach
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_max_discount_amount_to_redeem() {
			return $this->is_restriction_enabled() ? ywpar_get_option( 'max_points_discount', '' ) : '';
		}

		/**
		 * Return the min discount percentage that a user can reach
		 *
		 * Only for percentage reward method.
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_min_percentage_discount_to_redeem() {
			return $this->is_restriction_enabled() ? ywpar_get_option( 'min_percentual_discount', '' ) : '';
		}

		/**
		 * Return the max discount percentage that a user can reach
		 *
		 * Only for percentage reward method.
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_max_percentage_discount_to_redeem() {
			return $this->is_restriction_enabled() ? ywpar_get_option( 'max_percentual_discount', '' ) : '';
		}

		/**
		 * Calculate the points of a product/variation for a single item
		 *
		 * @param float                                $discount_amount Discount Amount.
		 * @param YITH_WC_Points_Rewards_Customer|null $customer Customer.
		 * @param string                               $currency Currency.
		 *
		 * @return  int $points
		 * @since   1.0.0
		 */
		public function calculate_rewards_discount( $discount_amount = 0.0, $customer = null, $currency = '' ) {
			$customer = ywpar_get_customer( $customer );
			if ( ! $customer ) {
				return 0;
			}
			$currency      = ywpar_get_currency( $currency );
			/**
			 * APPLY_FILTERS: ywpar_rewards_max_points_from_user
			 *
			 * filter the customer usable points.
			 *
			 * @param int $total_points total usable points.
			 * @param int $customer id
			 */
			$points_usable = apply_filters( 'ywpar_rewards_max_points_from_user', $customer->get_total_points(), $customer->get_id() );

			if ( $points_usable <= 0 ) {
				return false;
			}

			$this->max_discount = 0;
			$this->max_points   = 0;

			$minimum_amount_to_redeem = $this->get_minimum_cart_amount_to_redeem();
			$subtotal                 = $this->get_cart_subtotal() + (float) $discount_amount;

			if ( '' !== $minimum_amount_to_redeem && $subtotal < $minimum_amount_to_redeem ) {
				return false;
			}

			$conversion                      = $this->get_conversion_rate_rewards( $currency, $customer );
			$general_max_discount            = $this->get_max_discount_amount_to_redeem();
			$general_max_percentage_discount = $this->get_max_percentage_discount_to_redeem();
			$general_min_percentage_discount = $this->get_min_percentage_discount_to_redeem();

			$this->calculate_max_discount_on_cart( $currency, $customer );

			if ( $subtotal < $this->max_discount ) {
				$this->max_discount = $subtotal;
			}

			$this->max_discount = apply_filters( 'ywpar_set_max_discount_for_minor_subtotal', $this->max_discount, $subtotal );
			
			if ( $this->get_conversion_method() === 'fixed' ) {
				$minimum_amount_discount_to_redeem = $this->get_min_discount_amount_to_redeem();

				if ( '' !== $general_max_discount ) {
					// check if is present % inside the option for retro compatibility.
					if ( strpos( $general_max_discount, '%' ) === false ) {
						$max_discount = ( $subtotal >= (float) $general_max_discount ) ? (float) $general_max_discount : $subtotal;
					} else {
						$general_max_discount = (float) str_replace( '%', '', $general_max_discount );
						$max_discount         = $subtotal * $general_max_discount / 100;
					}

					if ( $max_discount < $this->max_discount ) {
						$this->max_discount = $max_discount;
					}
				}

				/**
				 * APPLY_FILTERS: ywpar_calculate_rewards_discount_max_discount_fixed
				 *
				 * filter the max discount for fixed conversion type.
				 */
				$this->max_discount = apply_filters( 'ywpar_calculate_rewards_discount_max_discount_fixed', $this->max_discount );
				$conversion_result  = $conversion['money'] * $conversion['points'];
				$this->max_points   = (int) $conversion_result > 0 ? yith_ywpar_round_points( $this->max_discount / $conversion['money'] * $conversion['points'] ) : 0;
				
				if ( $this->max_points > $points_usable ) {
					$this->max_points   = $points_usable;
					$this->max_discount = $this->max_points / $conversion['points'] * $conversion['money'];
				} else {
					$this->max_discount = $this->max_points / $conversion['points'] * $conversion['money'];
				}
				
				if ( $this->max_discount < $minimum_amount_discount_to_redeem ) {
					return '';
				}
			} else {

				if ( $subtotal > 0 ) {
					/**
					 * APPLY_FILTERS: ywpar_cart_discount_percentage
					 *
					 * filter the discount for percentage conversion type.
					 *
					 * @param int|float $discount .
					 * @param array $conversion
					 * @param int $general_max_percentage_discount
					 */
					$cart_discount_percentage = apply_filters( 'ywpar_cart_discount_percentage', $this->max_discount / $subtotal * 100, $conversion, $general_max_percentage_discount );

					if ( '' !== $general_min_percentage_discount && $cart_discount_percentage < $general_min_percentage_discount ) {
						return '';
					}
					if ( '' !== $general_max_percentage_discount && $general_max_percentage_discount < $cart_discount_percentage ) {
						$cart_discount_percentage = (float) $general_max_percentage_discount;
						$max_points               = round( $cart_discount_percentage / $conversion['discount'] ) * $conversion['points'];
					} else {
						$max_points               = round( $cart_discount_percentage / $conversion['discount'] ) * $conversion['points'];
						$cart_discount_percentage = round( $max_points / $conversion['points'] ) * $conversion['discount'];
					}

					if ( ( '' !== $general_max_percentage_discount && $cart_discount_percentage > $general_max_percentage_discount ) || ( '' !== $general_min_percentage_discount && $cart_discount_percentage < $general_min_percentage_discount ) ) {
						return '';
					}

					// must be floor because to calculate the right max points.
					$max_percentage_discount = floor( $points_usable / $conversion['points'] ) * $conversion['discount'];
					if ( '' !== $general_min_percentage_discount && $max_percentage_discount < $general_min_percentage_discount ) {
						return '';
					}

					if ( $points_usable >= $max_points ) {
						$this->max_points              = $max_points;
						$this->max_percentage_discount = $cart_discount_percentage;
						$this->max_discount            = ( $this->max_discount * $this->max_percentage_discount ) / 100;
					} else {
						$this->max_percentage_discount = $max_percentage_discount;
						$this->max_points              = round( $this->max_percentage_discount / $conversion['discount'] ) * $conversion['points'];
						$this->max_discount            = apply_filters( 'ywpar_calculate_rewards_discount_max_discount_percentual', ( $this->max_discount * $this->max_percentage_discount ) / 100 );
					}
				}
			}

			/**
			 * APPLY_FILTERS: ywpar_calculate_rewards_discount_max_discount
			 *
			 * filter the max discount.
			 *
			 * @param int|float $max_discount
			 * @param YITH_WC_Points_Rewards_Redeeming $_this
			 * @param array $conversion
			 */
			$this->max_discount = apply_filters( 'ywpar_calculate_rewards_discount_max_discount', $this->max_discount, $this, $conversion );
			/**
			 * APPLY_FILTERS: ywpar_calculate_rewards_discount_max_points
			 *
			 * filter the max points.
			 *
			 * @param int $max_points
			 * @param YITH_WC_Points_Rewards_Redeeming $_this
			 * @param array $conversion
			 */
			$this->max_points   = apply_filters( 'ywpar_calculate_rewards_discount_max_points', $this->max_points, $this, $conversion );

			return $this->max_discount;
		}

		/**
		 * Get max discount of a product.
		 *
		 * @param WC_Product                              $product Product.
		 * @param float                                   $price Product price.
		 * @param string                                  $currency Currency.
		 * @param WP_User|YITH_WC_Points_Rewards_Customer $user User.
		 *
		 * @return int|float
		 * @since 3.0.0
		 */
		public function get_product_max_discount( $product, $price = '', $currency = '', $user = null ) {
			$currency            = ywpar_get_currency( $currency );
			$customer            = ywpar_get_customer( $user );
			$max_discount_cached = wp_cache_get( 'ywpar_product_max_discount', 'ywpar_points' );
			$max_discount_cached = $max_discount_cached ? $max_discount_cached : array();
			$max_discount        = false;

			if ( false !== $max_discount_cached ) {
				if ( isset( $max_discount_cached[ $product->get_id() ][ $customer->get_id() ][ $currency ] ) ) {
					$max_discount = $max_discount_cached[ $product->get_id() ][ $customer->get_id() ][ $currency ];
				}
			}

			if ( ! $max_discount ) {
				$max_discount                                                                  = $this->calculate_product_max_discount( $product, $price, $currency, $customer );
				$max_discount_cached[ $product->get_id() ][ $customer->get_id() ][ $currency ] = $max_discount;
				wp_cache_set( 'ywpar_product_max_discount', $max_discount_cached, 'ywpar_points' );
			}

			return $max_discount;
		}

		/**
		 * Return the max discount that can be applied to a product
		 *
		 * @param WC_Product                              $product Product.
		 * @param float                                   $price Product price.
		 * @param string                                  $currency Currency.
		 * @param WP_User|YITH_WC_Points_Rewards_Customer $user User.
		 *
		 * @return float
		 *
		 * @since 3.0.0
		 */
		public function calculate_product_max_discount( $product, $price = '', $currency = '', $user = null ) {

			$is_product_page = function_exists( 'is_product' ) && is_product();

			if ( 'yes' === ywpar_get_option( 'redeeem_exclude_product_on_sale', 'no' ) && $product && $product->is_on_sale() && ! $is_product_page ) {
				return 0;
			}

			$max_discount         = empty( $price ) && 0 !== $price ? ywpar_get_product_price( $product, 'redeem' ) : (float) $price;
			$general_max_discount = '';
			if ( $this->is_restriction_enabled() && $max_discount > 0 ) {
				if ( 'fixed' === $this->get_conversion_method() ) {
					$general_max_discount = (float) ywpar_get_option( 'max_points_product_discount' );
				}
/*
				else {
					$general_max_percentage_discount = (float) ywpar_get_option( 'max_percentual_discount' );
					$general_max_discount            = ( $max_discount * $general_max_percentage_discount ) / 100;
				}*/
			}

			if ( ! empty( $general_max_discount ) && $general_max_discount < $max_discount ) {
				$max_discount = $general_max_discount;
			}

			$valid_rules = YITH_WC_Points_Rewards_Helper::get_redeeming_rules_valid_for_product( $product, $user );

			$product_rules    = array();
			$on_sale_rules    = array();
			$categories_rules = array();
			$tags_rules       = array();
			$general_rules    = array();

			if ( $valid_rules ) {

				foreach ( $valid_rules as $valid_rule ) {
					/**
					 * Current redeeming rule
					 *
					 * @var YITH_WC_Points_Rewards_Redeeming_Rule $valid_rule
					 */
					if ( $valid_rule->get_type() === 'conversion_rate' ) {
						continue;
					}

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
				} elseif ( ! empty( $on_sale_rules ) ) {
					$valid_rule = $on_sale_rules[0];
				} elseif ( ! empty( $categories_rules ) ) {
					$valid_rule = $categories_rules[0];
				} elseif ( ! empty( $tags_rules ) ) {
					$valid_rule = $tags_rules[0];
				} elseif ( ! empty( $general_rules ) ) {
					$valid_rule = $general_rules[0];
				}

				$max_discount = $valid_rule->calculate_max_discount( $price, $currency );

			}

			return $max_discount;
		}

		/**
		 * Calculate the max discount on cart.
		 *
		 * @param string                          $currency Currency.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 *
		 * @since 3.0.0
		 */
		public function calculate_max_discount_on_cart( $currency, $customer ) {
			if ( ! WC()->cart ) {
				return;
			}

			foreach ( WC()->cart->get_cart_contents() as $cart_item ) {
				$product_id   = ! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id'];
				/**
				 * APPLY_FILTERS: ywpar_calculate_rewards_discount_item_price
				 *
				 * filter the cart item price.
				 *
				 * @param float|string $item_price
				 * @param WC_Cart_Item $cart_item
				 * @param int $product_id
				 */
				$item_price   = apply_filters( 'ywpar_calculate_rewards_discount_item_price', ywpar_get_product_price( $cart_item['data'], 'redeem' ), $cart_item, $product_id );
				$max_discount = $this->get_product_max_discount( $cart_item['data'], $item_price, $currency, $customer );

				if ( 0 !== $max_discount ) {
					$this->max_discount += $max_discount * $cart_item['quantity'];
				}
			}
		}

		/**
		 * Cart subtotal
		 *
		 * @return float
		 * @since 3.0.0
		 */
		private function get_cart_subtotal() {
			if ( apply_filters( 'ywpar_exclude_taxes_from_calculation', 'excl' === ywpar_get_option( 'redeem_prices_tax' ) ) ) {
				$subtotal = ( WC()->cart->get_subtotal() - WC()->cart->get_discount_total() );
			} else {
				$subtotal = ( ( WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() ) - ( WC()->cart->get_discount_total() + WC()->cart->get_discount_tax() ) );
			}

			/**
			 * APPLY_FILTERS: ywpar_rewards_points_cart_subtotal
			 *
			 * filter cart subtotal.
			 */
			return apply_filters( 'ywpar_rewards_points_cart_subtotal', $subtotal );
		}

		/**
		 * Return the max percentage discount that can be used in the cart for rewards
		 *
		 * @return  float
		 * @since   3.0.0
		 */
		public function get_max_percentage_discount() {
			/**
			 * APPLY_FILTERS: ywpar_rewards_max_percentual_discount
			 *
			 * filter the max percentage discount.
			 */
			return apply_filters( 'ywpar_rewards_max_percentual_discount', $this->max_percentage_discount );
		}

		/**
		 * Return the max points that can be used in the cart for rewards
		 * must be called after the function calculate_points_and_discount
		 *
		 * @return  int
		 * @since   1.0.0
		 */
		public function get_max_points() {
			/**
			 * APPLY_FILTERS: ywpar_rewards_max_points
			 *
			 * filter the max points.
			 */			
			return apply_filters( 'ywpar_rewards_max_points', $this->max_points );
		}

		/**
		 * Apply the discount to cart after that the user set the number of points
		 *
		 * @return void
		 */
		public function apply_discount() {

			if ( wp_verify_nonce( 'ywpar_input_points_nonce', 'ywpar_apply_discounts' ) || ! is_user_logged_in() || ! isset( $_POST['ywpar_rate_method'], $_POST['ywpar_points_max'], $_POST['ywpar_max_discount'] ) || ( isset( $_POST['coupon_code'] ) && ! empty( $_POST['coupon_code'] ) ) ) {
				return;
			}

			$posted = $_POST;
			$this->apply_discount_calculation( $posted );
		}

		/**
		 * Return the coupon code
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function get_coupon_code_prefix() {
			/**
			 * APPLY_FILTERS: ywpar_label_coupon
			 *
			 * filter the coupon label.
			 */			
			return apply_filters( 'ywpar_label_coupon', $this->label_coupon_prefix );
		}

		/**
		 * Add the coupon to the cart.
		 *
		 * @param array $posted Data.
		 * @param bool  $apply_coupon Apply coupon or not.
		 *
		 * @throws Exception Throws an Exception.
		 */
		public function apply_discount_calculation( $posted, $apply_coupon = true ) {
			/**
			 * DO_ACTION: ywpar_before_apply_discount_calculation
			 *
			 * hook before apply discount calculation.
			 *
			 */
			do_action( 'ywpar_before_apply_discount_calculation' );
			$max_points   = sanitize_text_field( wp_unslash( $posted['ywpar_points_max'] ) );
			$max_discount = sanitize_text_field( wp_unslash( $posted['ywpar_max_discount'] ) );

			$reward_method = $this->get_conversion_method();

			$discount = 0;
			
			if ( 'fixed' === $reward_method ) {

				if ( ! isset( $posted['ywpar_input_points_check'], $posted['ywpar_input_points'] ) || empty( $posted['ywpar_input_points_check'] ) || empty( $posted['ywpar_input_points'] ) ) {
					return;
				}

				$input_points            = sanitize_text_field( wp_unslash( $posted['ywpar_input_points'] ) );
				$input_points            = ( $input_points > $max_points ) ? $max_points : $input_points;
				$conversion              = $this->get_conversion_rate_rewards();
				$input_max_discount      = (int)$input_points / (int)$conversion['points'] * (int)$conversion['money'];
				$input_max_discount      = ( $input_max_discount > $max_discount ) ? $max_discount : $input_max_discount;
				$minimum_discount_amount = $this->get_min_discount_amount_to_redeem();

				if ( ! empty( $minimum_discount_amount ) && $input_max_discount < $minimum_discount_amount ) {
					$input_max_discount = $minimum_discount_amount;
					$input_points       = $conversion['points'] / $conversion['money'] * $input_max_discount;
				}

				if ( $input_max_discount > 0 ) {
					WC()->session->set( 'ywpar_coupon_code_points', $input_points );
					WC()->session->set( 'ywpar_coupon_code_discount', $input_max_discount );

					$discount = $input_max_discount;
					/**
					 * APPLY_FILTERS: ywpar_adjust_discount_value
					 *
					 * filter the dicount value.
					 */
					$discount = apply_filters( 'ywpar_adjust_discount_value', $discount );
				};

			} else {
				WC()->session->set( 'ywpar_coupon_code_points', $max_points );
				WC()->session->set( 'ywpar_coupon_code_discount', $max_discount );
				$discount = $max_discount;
			}

			WC()->session->set( 'ywpar_coupon_posted', $posted );

			// apply the coupon in cart.
			if ( $apply_coupon && $discount ) {
				$coupon = $this->get_current_coupon();
				
				$coupon->set_usage_count( 0 );

				$is_new = $coupon->get_amount() <= 0;
				if ( apply_filters( 'ywpar_change_coupon_type_discount', false, $discount, $coupon ) ) {
					$type_discount = 'percent';
					$discount      = '100';
				} else {
					$type_discount = 'fixed_cart';
				}

				if ( $coupon->get_discount_type() !== $type_discount ) {
					$coupon->set_discount_type( $type_discount );
				}

				if ( $coupon->get_amount() !== $discount ) {
					$coupon->set_amount( $discount );
				}

				/**
				 * APPLY_FILTERS: ywpar_allow_free_shipping
				 *
				 * allow free shipping coupon/discount.
				 */
				$allow_free_shipping = apply_filters( 'ywpar_allow_free_shipping', ywpar_get_option( 'allow_free_shipping_to_redeem', 'no' ) === 'yes', $discount );

				if ( $coupon->get_free_shipping() !== $allow_free_shipping ) {
					$coupon->set_free_shipping( $allow_free_shipping );
				}

				$valid = ywpar_coupon_is_valid( $coupon, WC()->cart );

				if ( ! $valid ) {
					$args = array(
						'id'             => false,
						'discount_type'  => $type_discount,
						'individual_use' => false,
						'usage_limit'    => $this->get_usage_limit(),
					);

					$coupon->add_meta_data( 'ywpar_coupon', 1 );
					$coupon->read_manual_coupon( $coupon->get_code(), $args );
				} else {
					if ( '' === $coupon->get_meta( 'ywpar_coupon' ) ) {
						$coupon->update_meta_data( 'ywpar_coupon', 1 );
						$is_new = true;
					}
				}

				if ( $is_new || ! empty( $coupon->get_changes() ) ) {
					$coupon->save();
				}

				$coupon_label = $coupon->get_code();

				if ( ywpar_coupon_is_valid( $coupon, WC()->cart ) && ! WC()->cart->has_discount( $coupon_label ) ) {
					WC()->cart->add_discount( $coupon_label );
					$this->update_discount();
				}
			}
		}

		/**
		 * Returns the usage limit parameter to do a coupon. The function check the option 'other_coupons'.
		 * if this option is equal to 'ywpar' usage limit will be equal 1
		 *
		 * @return bool
		 */
		protected function get_usage_limit() {
			return (int) ( ywpar_get_option( 'other_coupons' ) === 'ywpar' );
		}

		/**
		 * Update the coupon code points and discount
		 *
		 * @return void
		 * @since  1.3.0
		 */
		public function update_discount() {
			$coupon = ywpar_cart_has_redeeming_coupon();
			if ( $coupon ) {
				if ( ! $coupon instanceof WC_Coupon ) {
					$coupon = new WC_Coupon( $coupon );
				}
				if ( ywpar_get_option( 'enable_rewards_points' ) !== 'yes' ) {
					WC()->cart->remove_coupon( $coupon->get_code() );

					return;
				}
				$posted               = WC()->session->get( 'ywpar_coupon_posted' );
				/**
				 * APPLY_FILTERS: ywpar_exclude_taxes_from_calculation
				 *
				 * set if exlude tax from calculation while creating the coupon.
				 */
				$ex_tax               = apply_filters( 'ywpar_exclude_taxes_from_calculation', false );
				$coupon_real_discount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), $ex_tax );
				$max_discount         = $this->calculate_rewards_discount( $coupon_real_discount );

				if ( $max_discount ) {
					// minimum subtotal cart requested to redeem points.
					$minimum_amount             = $this->get_minimum_cart_amount_to_redeem();
					$minimum_discount_requested = $this->get_min_discount_amount_to_redeem();

					$subtotal = $this->get_cart_subtotal() + $coupon->get_amount();
					if ( ( '' !== $minimum_amount && $subtotal < $minimum_amount ) || ( '' !== $minimum_discount_requested && $max_discount < $minimum_discount_requested ) ) {
						WC()->cart->remove_coupon( $coupon->get_code() );
					} else {
						$max_points                   = $this->get_max_points();
						$posted['ywpar_max_discount'] = $max_discount;
						$posted['ywpar_points_max']   = $max_points;
						$this->apply_discount_calculation( $posted, true );
					}
				} else {
					WC()->cart->remove_coupon( $coupon->get_code() );
				}
			}
		}

		/**
		 * Return the coupon to apply
		 *
		 * @return WC_Coupon
		 */
		public function get_current_coupon() {

			$coupon = false;

			if ( empty( $this->current_coupon_code ) ) {
				// check if in the cart.
				$coupons_in_cart = WC()->cart->get_applied_coupons();

				foreach ( $coupons_in_cart as $coupon_in_cart_code ) {
					if ( ywpar_is_redeeming_coupon( $coupon_in_cart_code ) ) {
						$this->current_coupon_code = $coupon_in_cart_code;
						break;
					}
				}
			}

			if ( empty( $this->current_coupon_code ) ) {
				if ( is_user_logged_in() ) {
					/**
					 * APPLY_FILTERS: ywpar_coupon_code
					 *
					 * filter the coupon code.
					 */
					$this->current_coupon_code = apply_filters( 'ywpar_coupon_code', $this->get_coupon_code_prefix() . '_' . get_current_user_id(), $this->get_coupon_code_prefix() );
				}
			}

			if ( ! empty( $this->current_coupon_code ) ) {
				$coupon = new WC_Coupon( $this->current_coupon_code );
			}

			return $coupon;
		}

		/**
		 * Set the coupon label in cart.
		 *
		 * @param string    $label Label.
		 * @param WC_Coupon $coupon Coupon.
		 *
		 * @return string
		 */
		public function coupon_label( $label, $coupon ) {
			// APPLY_FILTERS: ywpar_coupon_label: change the label of redeeming point coupon.
			$points_coupon_label = apply_filters( 'ywpar_coupon_label', ywpar_get_option( 'label_applied_coupon', esc_html__( 'Redeem points', 'yith-woocommerce-points-and-rewards' ) ) );

			return ywpar_is_redeeming_coupon( $coupon ) ? esc_html( $points_coupon_label ) : $label;
		}

		/**
		 * Check if the discount applied follow the rule in the setting about more
		 * coupons in the cart
		 *
		 * @param string $coupon_code Coupon code.
		 *
		 * @return bool|string
		 */
		public function check_coupons_in_cart( $coupon_code ) {

			$message       = false;
			$other_coupons = ywpar_get_option( 'other_coupons' );

			if ( empty( WC()->cart ) ) {
				return $message;
			}

			$ywpar_added_coupon           = ywpar_check_redeeming_coupons( array( $coupon_code ) );
			$ywpar_coupon_in_cart         = ywpar_cart_has_redeeming_coupon();
			$ywpar_cart_has_shared_coupon = ywpar_cart_has_shared_coupon();
			$applied_coupons              = WC()->cart->get_applied_coupons();

			/**
			 * APPLY_FILTERS: ywpar_limit_share_and_redeem_coupon_in_cart
			 *
			 * set if limit the shared coupons - default true.
			 */
			if ( 'both' === $other_coupons && apply_filters( 'ywpar_limit_share_and_redeem_coupon_in_cart', true ) ) {
				$coupon_priority = apply_filters( 'ywpar_limit_share_coupon_priority', 'ywpar' );
				$other_coupons   = ( $ywpar_cart_has_shared_coupon && $ywpar_coupon_in_cart ) ? $coupon_priority : $other_coupons;
			}

			if ( 'both' === $other_coupons ) {
				return $message;
			}

			switch ( $other_coupons ) {
				case 'ywpar':
					if ( $ywpar_added_coupon instanceof WC_Coupon ) {
						foreach ( $applied_coupons as $coupon_cart_code ) {
							if ( $coupon_code !== $coupon_cart_code ) {
								WC()->cart->remove_coupon( $coupon_cart_code );
								$message = 'removed_wc_coupon';
							}
						}
					} elseif ( $ywpar_coupon_in_cart instanceof WC_Coupon ) {
						WC()->cart->remove_coupon( $coupon_code );
						$message = 'removed_wc_coupon';
					}
					break;
				case 'wc_coupon':
					/**
					 * APPLY_FILTERS: ywpar_check_ywpar_coupon_before_remove
					 *
					 * check coupon before remove.
					 *
					 * @param bool $check default true.
					 * @param string $coupon code
					 * @param array $applied_coupons coupons applied to the cart.
					 * @param bool $ywpar_coupon_in_cart if in cart there's a redeem points coupon.
					 */
					if ( $ywpar_added_coupon instanceof WC_Coupon && count( $applied_coupons ) > 1 && apply_filters( 'ywpar_check_ywpar_coupon_before_remove', true, $coupon_code, $applied_coupons, $ywpar_coupon_in_cart ) ) {
						WC()->cart->remove_coupon( $coupon_code );
						$message = 'removed_par';
					} elseif ( $ywpar_coupon_in_cart instanceof WC_Coupon && apply_filters( 'ywpar_check_ywpar_coupon_before_remove', true, $coupon_code, $applied_coupons, $ywpar_coupon_in_cart ) ) {
						$coupon_in_cart_code = $ywpar_coupon_in_cart->get_code();
						if ( $coupon_in_cart_code !== $coupon_code ) {
							WC()->cart->remove_coupon( $coupon_in_cart_code );
							$message = 'removed_par';
						}
					}
					break;
			}
			/**
			 * APPLY_FILTERS: ywpar_check_coupons_in_cart
			 *
			 * check coupons in cart message.
			 *
			 * @param string $message
			 * @param string $coupon code
			 */
			return apply_filters( 'ywpar_check_coupons_in_cart', $message, $coupon_code );
		}

		/**
		 * Set the message when the discount is applied with success
		 *
		 * @param string    $message Message.
		 * @param string    $message_code Code message.
		 * @param WC_Coupon $coupon Coupon.
		 *
		 * @return string
		 */
		public function coupon_rewards_message( $message, $message_code, $coupon ) {
			$message_changed = $this->check_coupons_in_cart( $coupon->get_code() );

			$is_par = ywpar_is_redeeming_coupon( $coupon );
			/**
			 * APPLY_FILTERS: ywpar_discount_applied_message
			 *
			 * filter the discount applied message.
			 */
			$m      = $is_par ? apply_filters( 'ywpar_discount_applied_message', esc_html__( 'Reward discount applied Successfully', 'yith-woocommerce-points-and-rewards' ) ) : $message;
			if ( $message_changed ) {
				switch ( $message_changed ) {
					case 'removed_par':
						if ( ! $is_par ) {
							$m = esc_html__( 'Reward Discount has been removed. You can\'t use this discount with other coupons.', 'yith-woocommerce-points-and-rewards' );
						} else {
							$m = esc_html__( 'You can\'t use this coupon in conjunction with other coupons', 'yith-woocommerce-points-and-rewards' );
						}
						break;
					case 'removed_wc_coupon':
						if ( $is_par ) {
							$m = esc_html__( 'Coupon removed. You can\'t use this coupon with a reward discount', 'yith-woocommerce-points-and-rewards' );
						} else {
							$m = esc_html__( 'You can\'t use this coupon with a rewards discount', 'yith-woocommerce-points-and-rewards' );
						}
						break;
					default:
				}
			}

			return ( WC_Coupon::WC_COUPON_SUCCESS === $message_code ) ? $m : $message;
		}

		/**
		 * Set cron to clear coupon
		 */
		public function ywpar_set_cron() {
			if ( ! wp_next_scheduled( 'ywpar_clean_cron' ) ) {
				/**
				 * APPLY_FILTERS: ywpar_set_cron_time
				 *
				 * filter the cron time - default 'daily'.
				 *
				 * @param
				 */
				$duration = apply_filters( 'ywpar_set_cron_time', 'daily' );
				wp_schedule_event( time(), $duration, 'ywpar_clean_cron' );
			}
		}

		/**
		 * Auto Apply Redeeming Points in cart/checkout pages
		 *
		 * @return void
		 * @throws WC_Data_Exception Throws Exception.
		 * @since  1.6.7
		 */
		public function auto_apply_discount() {

			if ( ! is_cart() && ! is_checkout() ) {
				return;
			}

			$customer = ywpar_get_current_customer();
			if ( ! $customer || ! $customer->is_enabled( 'redeem' ) ) {
				return;
			}

			// Clean the session ywpar_automatically_applied value if more than one hour has passed.
			// this is like a session clean for auto apply discount value.
			$prev = WC()->session->get( 'ywpar_automatically_applied_time' );
			$applied = WC()->session->get( 'ywpar_automatically_applied' );
			if ( ! empty( $prev ) && $applied ) {
				$now      = new DateTime();
				$interval = $prev->diff( $now );
				/**
				 * APPLY_FILTERS: ywpar_autoapply_clean_time_interval
				 *
				 * filter auto apply points time interval - default value 60.
				 *
				 * @param
				 */
				if ( intval( $interval->i ) >= apply_filters( 'ywpar_autoapply_clean_time_interval', 60 ) ) {
					WC()->session->set( 'ywpar_automatically_applied', false );
				}
			}

			$ywpar_automatically_applied = WC()->session->get( 'ywpar_automatically_applied' );

			if ( empty( $ywpar_automatically_applied ) ) {
				$values = array();

				$values['ywpar_rate_method']        = $this->get_conversion_method();
				$values['ywpar_max_discount']       = $this->calculate_rewards_discount();
				$values['ywpar_points_max']         = $this->get_max_points();
				$values['ywpar_input_points']       = $values['ywpar_points_max'];
				$values['ywpar_input_points_check'] = 1;

				$this->apply_discount_calculation( $values );

				WC()->session->set( 'ywpar_automatically_applied', true );
				$d = new DateTime();
				WC()->session->set( 'ywpar_automatically_applied_time', $d );
			}

		}

		/**
		 * Clean Auto Apply Redeeming Point session info in order to re-apply after checkout completed and order has set to completed or cart emptied
		 *
		 * @param int $order Order ID.
		 *
		 * @return void
		 * @since  1.6.7
		 */
		public function clean_auto_apply_session( $order = 0 ) {
			if ( $order ) {
				WC()->session->set( 'ywpar_automatically_applied', false );
				WC()->session->set( 'ywpar_automatically_applied_time', '' );
			} else {
				if ( WC()->cart->get_cart_contents_count() === 0 ) {
					WC()->session->set( 'ywpar_automatically_applied', false );
					WC()->session->set( 'ywpar_automatically_applied_time', '' );
				}
			}
		}

		/**
		 * Remove the coupons created dynamically
		 *
		 * @param string $coupon_code The coupon code removed.
		 *
		 * @return void
		 */
		public function clear_current_coupon( $coupon_code ) {
			$current_coupon = $this->get_current_coupon();
			if ( $current_coupon instanceof WC_Coupon && $current_coupon->get_code() === $coupon_code && apply_filters( 'ywpar_clear_current_coupon', true ) ) {
				$current_coupon->delete();
			}
		}


		/**
		 * Calculate the discount value for Reward Message
		 * *
		 *
		 * @return void
		 * @since  2.0.0
		 */
		public function calc_discount_value() {
			check_ajax_referer( 'calc_discount_value', 'security' );
			if ( isset( $_POST['input_points'], $_POST['max_points'], $_POST['method'] ) ) {
				$input_points = sanitize_text_field( wp_unslash( $_POST['input_points'] ) );
				$max_points   = sanitize_text_field( wp_unslash( $_POST['max_points'] ) );
				$input_points = $input_points > $max_points ? $max_points : $input_points;

				$to_redeem = '';
				if ( 'fixed' === sanitize_text_field( wp_unslash( $_POST['method'] ) ) ) {
					$rates     = $this->get_conversion_rate_rewards( get_woocommerce_currency() );
					$money     = $rates['money'];
					$to_redeem = abs( ( $input_points / $rates['points'] ) * $money );
					$to_redeem = apply_filters( 'ywpar_calculate_rewards_discount_max_discount', $to_redeem, $this, $rates );
					$to_redeem = wc_price( $to_redeem );
				}

				wp_send_json( array( 'to_redeem' => $to_redeem ) );
			} else {
				wp_send_json( array( 'to_redeem' => '' ) );
			}
		}

		/**
		 * Clear coupons after use
		 */
		public function clear_coupons() {

			$args = array(
				'post_type'       => 'shop_coupon',
				'posts_per_pages' => - 1,
				'meta_key'        => 'ywpar_coupon', //phpcs:ignore
				'meta_value'      => 1, //phpcs:ignore
			);

			$coupons = get_posts( $args );
			if ( $coupons ) {
				foreach ( $coupons as $coupon ) {
					wp_delete_post( $coupon->ID, true );
				}
			}
		}
	}

}
