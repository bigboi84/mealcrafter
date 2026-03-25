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
 * YWPAR_YITH_WooCommerce_Affiliates class to add compatibility with YITH WooCommerce Affiliates Premium
 *
 * @class   YWPAR_YITH_WooCommerce_Affiliates
 * @package YITH WooCommerce Points and Rewards
 * @since   1.6.0
 */
if ( ! class_exists( 'YWPAR_YITH_WooCommerce_Affiliates' ) ) {
	/**
	 * Class YWPAR_YITH_WooCommerce_Affiliates
	 */
	class YWPAR_YITH_WooCommerce_Affiliates {
		/**
		 * Single instance of the class
		 *
		 * @var YWPAR_YITH_WooCommerce_Affiliates
		 * @since 1.6.0
		 */
		protected static $instance;

		/**
		 * Check if the
		 *
		 * @var bool
		 */
		protected $commission_assigned = false;

		/**
		 * List of status that allows referred user to receive commissions
		 *
		 * @var mixed
		 * @since 1.0.0
		 */
		protected $unassigned_status = array(
			'not-confirmed',
			'cancelled',
			'refunded',
			'trash',
		);

		/**
		 * List of status that don't allows referred user to receive commissions
		 *
		 * @var mixed
		 * @since 1.0.0
		 */
		protected $assigned_status = array(
			'pending',
			'pending-payment',
			'paid',
		);

		/**
		 * Returns single instance of the class
		 *
		 * @return YWPAR_YITH_WooCommerce_Affiliates
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
			add_filter( 'ywpar_extra_points_options', array( $this, 'add_affiliate_extra_options' ) );
			if ( 'yes' === ywpar_get_option( 'affiliates_enabled' ) ) {
				add_filter( 'yith_wcaf_create_order_commission_skip_zero_commissions', '__return_false' );
				add_filter( 'yith_wcaf_create_order_commissions', array( $this, 'calculate_point_earned_by_affiliate' ), 10, 4 );
				add_action( 'yith_wcaf_referral_totals_table', array( $this, 'add_point_earned_by_affiliate' ) );
				add_action( 'yith_wcaf_commission_created', array( $this, 'add_points_to_affiliate' ) );
				add_action( 'yith_wcaf_commission_status_changed', array( $this, 'check_points_commission' ), 10, 3 );
				add_action( 'woocommerce_order_partially_refunded', array( $this, 'remove_order_points_refund' ), 11, 2 );
				add_action( 'ywpar_customer_removed_points', array( $this, 'customer_removed_points' ), 12, 2 );
			}
		}

		/**
		 * Remove points for partial refund when the calculation type id percentage.
		 *
		 * @param int      $points Points.
		 * @param WC_Order $order Order.
		 */
		public function customer_removed_points( $points, $order ) {
			$calculation_type = ywpar_get_option( 'affiliates_earning_conversion_points' );
			if ( 'percentage' === $calculation_type ) {
				$percentage        = (int) ywpar_get_option( 'affiliates_earning_percentage' );
				$commission_points = round( $points * $percentage / 100 );
				$affiliate_token   = $order->get_meta( '_yith_wcaf_referral' );
				$affiliate         = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_token( $affiliate_token );
				$customer          = ywpar_get_customer( $affiliate['user_id'] );
				if ( ! $customer ) {
					return;
				}
				$customer->update_points(
					-$commission_points,
					'affiliates',
					array(
						'description' => __( 'Removed commission for refunded order', 'yith-woocommerce-points-and-rewards' ),
						'order_id'    => $order->get_id(),
					)
				);
			}
		}

		/**
		 * Check the status of the commission to add or remove point to the affiliate.
		 *
		 * @param int    $commission_id Commission id.
		 * @param string $new_status New order status.
		 * @param string $old_status Old order status.
		 */
		public function check_points_commission( $commission_id, $new_status, $old_status ) {
			if ( in_array( $old_status, $this->assigned_status, true ) && in_array( $new_status, $this->unassigned_status, true ) ) {
				// remove points.
				$this->remove_points_to_affiliate( $commission_id );
			}
		}

		/**
		 * Add this row inside the metabox affiliate of the order.
		 *
		 * @param WC_Order $order Order.
		 */
		public function add_point_earned_by_affiliate( $order ) {
			$already_registered = $order->get_meta( '_ywpar_affiliate_commission_registered' );
			$tot_points         = $this->get_total_points( $order );

			if ( $already_registered && $tot_points ) {
				?>
				<tr>
					<td class="label" colspan=3><?php esc_html_e( 'Points earned:', 'yith-woocommerce-points-and-rewards' ); ?></td>
					<td class="total"><?php echo esc_html( $tot_points ); ?></td>
				</tr>
				<?php
			}
		}

		/**
		 *  Return the current point earned by the affiliate
		 *
		 * @param WC_Order $order Order.
		 *
		 * @return int
		 */
		public function get_total_points( $order ) {
			$commission_points     = (int) $order->get_meta( '_ywpar_affiliate_commission_point' );
			$total_points_refunded = (int) $order->get_meta( '_ywpar_affiliate_total_points_refunded' );
			$tot_point             = $total_points_refunded ? ( $commission_points - $total_points_refunded ) : $commission_points;

			return $tot_point;
		}


		/**
		 * Calculate the points to assign to the affiliates.
		 *
		 * @param bool   $result Result.
		 * @param int    $order_id Order id.
		 * @param string $token Referral token.
		 * @param string $token_origin Referral token origin.
		 *
		 * @return mixed
		 */
		public function calculate_point_earned_by_affiliate( bool $result, int $order_id, string $token, string $token_origin ) {
			$affiliate         = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_token( $token );
			$order             = wc_get_order( $order_id );
			$commission_points = 0;

			if ( $affiliate && $order instanceof WC_Order ) {
				$calculation_type = ywpar_get_option( 'affiliates_earning_conversion_points' );

				switch ( $calculation_type ) {
					case 'fixed':
						$commission_points = ywpar_get_option( 'affiliates_earning_fixed' );
						break;
					case 'percentage':
						$points_earned_by_customer = (int) $order->get_meta( 'ywpar_points_from_cart' );
						$percentage                = (int) ywpar_get_option( 'affiliates_earning_percentage' );
						$commission_points         = round( $points_earned_by_customer * $percentage / 100 );
						break;
					case 'conversion':
						$conversion_rate = ywpar_get_option( 'affiliates_earning_conversion' );
						$order_currency  = $order->get_currency();
						if ( isset( $conversion_rate[ $order_currency ] ) && $order->get_subtotal() >= $conversion_rate[ $order_currency ]['money'] ) {
							$conversion_rate   = $conversion_rate[ $order->get_currency() ];
							$commission_points = round( $order->get_subtotal() / $conversion_rate['money'] ) * $conversion_rate['points'];
						}
						break;
					default:
				}
			}

			$commission_points = apply_filters( 'ywpar_commission_points_for_affiliate', $commission_points, $order_id, $token );

			if ( $commission_points > 0 ) {
				$order->update_meta_data( '_ywpar_affiliate_commission_point', $commission_points );
				$order->save();
			}

			return $result;
		}

		/**
		 * Assign the points to affiliate.
		 *
		 * @param int $commission_id Commission id.
		 */
		public function add_points_to_affiliate( $commission_id ) {
			if ( $this->commission_assigned ) {
				return;
			}

			$commission = YITH_WCAF_Commission_Handler()->get_commission( $commission_id );

			if ( ! $commission ) {
				return;
			}

			$order_id = $commission['order_id'];
			$order    = wc_get_order( $order_id );
			if ( ! $order instanceof WC_Order ) {
				return;
			}
			$affiliate = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_id( $commission['affiliate_id'] );
			$customer  = ywpar_get_customer( $affiliate['user_id'] );

			if ( ! $customer ) {
				return;
			}
			$already_registered = $order->get_meta( '_ywpar_affiliate_commission_registered' );
			if ( apply_filters( 'ywpar_add_affiliate_commission_points', true, $affiliate, $order, $commission ) && $affiliate && ! $already_registered ) {
				$commission_points = $order->get_meta( '_ywpar_affiliate_commission_point' );
				$order->update_meta_data( '_ywpar_affiliate_commission_registered', true );
				$order->save();
				$customer->update_points( $commission_points, 'affiliates', array( 'order_id' => $order_id ) );
			}

			$this->commission_assigned = true;
		}

		/**
		 * Remove points to the affiliate
		 *
		 * @param int $commission_id Commission id.
		 */
		public function remove_points_to_affiliate( $commission_id ) {
			$commission = YITH_WCAF_Commission_Handler()->get_commission( $commission_id );

			if ( ! $commission ) {
				return;
			}

			$affiliate          = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_id( $commission['affiliate_id'] );
			$order_id           = $commission['order_id'];
			$order              = wc_get_order( $order_id );
			$already_registered = $order->get_meta( '_ywpar_affiliate_commission_registered' );

			$customer = ywpar_get_customer( $affiliate['user_id'] );
			if ( ! $customer ) {
				return;
			}

			if ( $affiliate && $order instanceof WC_Order && $already_registered ) {
				$tot_points = $this->get_total_points( $order );
				$order->update_meta_data( '_ywpar_affiliate_commission_registered', false );
				$order->save();
				$customer->update_points(
					-$tot_points,
					'affiliates',
					array(
						'description' => __( 'Removed commission', 'yith-woocommerce-points-and-rewards' ),
						'order_id'    => $order_id,
					)
				);
			}
		}

		/**
		 * Add affiliate option to Points and Rewards Extra Points options.
		 *
		 * @param array $options Options.
		 * @since  3.0.0
		 */
		public function add_affiliate_extra_options( $options ) {

			$currency = ywpar_get_currency();
			$section1 = array(
				'affiliates_title'                     => array(
					'name' => esc_html__(
						'Extra points to YITH WooCommerce Affiliates',
						'yith-woocommerce-points-and-rewards'
					),
					'type' => 'title',
					'id'   => 'ywpar_affiliates_title',
				),

				'affiliates_enabled'                   => array(
					'name'      => esc_html__(
						'Assign points to the affiliates',
						'yith-woocommerce-points-and-rewards'
					),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'default'   => 'no',
					'id'        => 'ywpar_affiliates_enabled',
				),

				'affiliates_earning_conversion_points' => array(
					'name'             => esc_html__( 'Points for affiliates', 'yith-woocommerce-points-and-rewards' ),
					'desc'             => esc_html__(
						'Select the method to calculate points for affiliates.',
						'yith-woocommerce-points-and-rewards'
					),
					'type'             => 'yith-field',
					'yith-type'        => 'select',
					'default'          => 'default',
					'options'          => array(
						'default'    => __( 'Select a value', 'yith-woocommerce-points-and-rewards' ),
						'fixed'      => esc_html__(
							'Fixed amount of points for each order',
							'yith-woocommerce-points-and-rewards'
						),
						'percentage' => esc_html__(
							'Percent of points earned by customer',
							'yith-woocommerce-points-and-rewards'
						),
						'conversion' => esc_html__(
							'Conversion based on order subtotal',
							'yith-woocommerce-points-and-rewards'
						),
					),
					'disabled_options' => array( 'default' ),
					'id'               => 'ywpar_affiliates_earning_conversion_points',
				),

				'affiliates_earning_fixed'             => array(
					'name'              => esc_html__(
						'Number of points earned for each commission',
						'yith-woocommerce-points-and-rewards'
					),
					'desc'              => esc_html__(
						'Set a fix amount of points to assign for each commission.',
						'yith-woocommerce-points-and-rewards'
					),
					'type'              => 'yith-field',
					'yith-type'         => 'number',
					'default'           => 0,
					'id'                => 'ywpar_affiliates_earning_fixed',
					'custom_attributes' => array(
						'data-deps'       => 'ywpar_affiliates_earning_conversion_points',
						'data-deps_value' => 'fixed',
						'style'           => 'width:70px',
						'data-desc'       => 'points',
					),
				),

				'affiliates_earning_percentage'        => array(
					'name'              => esc_html__( 'Percent of points', 'yith-woocommerce-points-and-rewards' ),
					'desc'              => esc_html__(
						'Percent of points earned by customer.',
						'yith-woocommerce-points-and-rewards'
					),
					'type'              => 'yith-field',
					'yith-type'         => 'number',
					'default'           => 0,
					'step'              => 1,
					'min'               => 0,
					'max'               => 100,
					'custom_attributes' => array(
						'data-deps'       => 'ywpar_affiliates_earning_conversion_points',
						'data-deps_value' => 'percentage',
						'style'           => 'width:70px',
						'data-desc'       => '%',
					),
					'id'                => 'ywpar_affiliates_earning_percentage',

				),

				'affiliates_earning_conversion'        => array(
					'name'              => esc_html__(
						'Assign points based on the order subtotal.',
						'yith-woocommerce-points-and-rewards'
					),
					'desc'              => esc_html__(
						'Decide how many points will be assigned to each order based on the currency.',
						'yith-woocommerce-points-and-rewards'
					),
					'yith-type'         => 'options-conversion-earning',
					'type'              => 'yith-field',
					'default'           => array(
						$currency => array(
							'points' => 1,
							'money'  => 10,
						),
					),
					'id'                => 'ywpar_affiliates_earning_conversion',
					'custom_attributes' => array(
						'data-deps'       => 'ywpar_affiliates_earning_conversion_points',
						'data-deps_value' => 'conversion',
					),
				),

				'label_affiliates'                     => array(
					'name'      => __( 'Affiliate commission', 'yith-woocommerce-points-and-rewards' ),
					'desc'      => '',
					'type'      => 'yith-field',
					'yith-type' => 'text',
					'default'   => __( 'Affiliate commission', 'yith-woocommerce-points-and-rewards' ),
					'id'        => 'ywpar_label_affiliates',
				),

				'affiliates_title_end'                 => array(
					'type' => 'sectionend',
					'id'   => 'ywpar_affiliates_title_end',
				),

			);

			$all_options             = array_merge( $options['points-extra'], $section1 );
			$options['points-extra'] = $all_options;

			return $options;
		}

		/**
		 * Remove points to the order if there's a partial refund
		 * when the
		 *
		 * @param int $order_id Order id.
		 * @param int $refund_id Refund id.
		 *
		 * @return void
		 * @since   1.0.0
		 */
		public function remove_order_points_refund( $order_id, $refund_id ) {
			// only for conversion points.
			$calculation_type = ywpar_get_option( 'affiliates_earning_conversion_points' );
			if ( 'conversion' !== $calculation_type ) {
				return;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}

			$affiliate_token = $order->get_meta( '_yith_wcaf_referral' );
			$affiliate       = YITH_WCAF_Affiliate_Handler()->get_affiliate_by_token( $affiliate_token );
			$point_earned    = (float) $order->get_meta( '_ywpar_affiliate_commission_point' );
			if ( '' === $point_earned ) {
				return;
			}

			$refund_obj    = new WC_Order_Refund( $refund_id );
			$refund_amount = $refund_obj->get_amount();

			$order_total                   = $order->get_total();
			$order_subtotal                = $order->get_subtotal();
			$order_remaining_refund_amount = $order->get_remaining_refund_amount();
			$total_points_refunded         = (float) $order->get_meta( '_ywpar_affiliate_total_points_refunded' );

			if ( $refund_amount > 0 ) {
				$points = 0;
				if ( $order_remaining_refund_amount > 0 ) {
					if ( $refund_amount > $order_subtotal ) {
						// shipping must be removed from.
						$order_shipping_total = $order->get_shipping_total();
						$refund_amount        = $refund_amount - $order_shipping_total;
					}

					$conversion_rate = ywpar_get_option( 'affiliates_earning_conversion' );

					if ( isset( $conversion_rate[ $order->get_currency() ] ) ) {
						$conversion_rate = $conversion_rate[ $order->get_currency() ];
						if ( abs( $order_total ) === $refund_amount ) {
							$points = $point_earned;
						} else {
							$points = round( $refund_amount / $conversion_rate['money'] * $conversion_rate['points'] );
						}
					}
				}

				// fix the points to refund calculation if points are more of the gap.
				$gap                    = $point_earned - $total_points_refunded;
				$points                 = ( $points > $gap ) ? $gap : $points;
				$action                 = 'affiliates';
				$total_points_refunded += $points;
				$customer               = ywpar_get_customer( $affiliate['user_id'] );
				$customer->update_points(
					-$points,
					$action,
					array(
						'description' => __( 'Order refunded', 'yith-woocommerce-points-and-rewards' ),
						'order_id'    => $order_id,
					)
				);

				$order->update_meta_data( '_ywpar_affiliate_total_points_refunded', $total_points_refunded );
			}
		}
	}
}

if ( ! function_exists( 'YWPAR_YITH_WooCommerce_Affiliates' ) ) {
	/**
	 * Unique access to instance of YWPAR_YITH_WooCommerce_Affiliates class
	 *
	 * @return YWPAR_YITH_WooCommerce_Affiliates
	 */
	function YWPAR_YITH_WooCommerce_Affiliates() { //phpcs:ignore
		return YWPAR_YITH_WooCommerce_Affiliates::get_instance();
	}
}

YWPAR_YITH_WooCommerce_Affiliates();

