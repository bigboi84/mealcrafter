<?php
/**
 * Class to share points
 *
 * @class   YITH_WC_Points_Rewards_Share_Points
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

require_once YITH_YWPAR_INC . 'legacy/abstract-yith-wc-points-rewards-redeeming-legacy.php';

if ( ! class_exists( 'YITH_WC_Points_Rewards_Share_Points' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Share_Points
	 */
	class YITH_WC_Points_Rewards_Share_Points {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Share_Points
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Share_Points
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
		 * @since 3.0.0
		 */
		private function __construct() {

		}

		/**
		 * Check if the sharing coupon is limited with a minimum amount.
		 *
		 * @return bool
		 */
		public static function is_enabled() {
			return 'yes' === ywpar_get_option( 'enable_sharing', 'yes' );
		}


		/**
		 * Check if the sharing coupon is limited with a minimum amount.
		 *
		 * @return bool
		 */
		public static function is_limited() {
			return 'yes' === ywpar_get_option( 'apply_limits_to_share_coupon' );
		}


		/**
		 * Check if the sharing coupon is limited with a minimum amount.
		 *
		 * @return bool
		 */
		public static function is_time_limited() {
			return 'yes' === ywpar_get_option( 'enable_expiration_to_share_coupon' );
		}


		/**
		 * Check if the sharing coupon is limited with a minimum amount.
		 *
		 * @return bool
		 */
		public static function get_minimum_amount() {
			return self::is_limited() ? (int) ywpar_get_option( 'min_limit_to_share_coupon', 1 ) : 1;
		}

		/**
		 * Check if the sharing coupon is limited with a minimum amount.
		 *
		 * @return bool
		 */
		public static function get_maximum_amount() {
			return self::is_limited() ? ywpar_get_option( 'max_limit_to_share_coupon', '' ) : '';
		}

		/**
		 * Check if it possible share points starting from a specific amount of points.
		 *
		 * @param int $points_amount Points amount.
		 *
		 * @return bool
		 */
		public static function are_number_points_valid_to_share( $points_amount ) {
			$is_valid = self::is_enabled();

			if ( $is_valid && self::is_limited() ) {
				$is_valid = $points_amount >= self::get_minimum_amount();
			}

			return $is_valid;
		}

		/**
		 * Return the max points usable
		 *
		 * @param int $points_amount Points amount.
		 * @return int
		 */
		public static function get_max_points_usable( $points_amount ) {
			$points_usable = self::is_enabled() ? $points_amount : 0;

			if ( self::is_limited() ) {
				$max_amount    = self::get_maximum_amount();
				$points_usable = ! empty( $max_amount ) && $points_amount > $max_amount ? $max_amount : $points_amount;
			}
			/**
			 * APPLY_FILTERS: ywpar_get_max_points_usable_to_share
			 *
			 * filter the max usable points to share coupon.
			 *
			 * @param int $points_usable
			 * @param int $points_amount
			 */
			return apply_filters( 'ywpar_get_max_points_usable_to_share', $points_usable, $points_amount );
		}

		/**
		 * Generate coupon random code
		 */
		public static function generate_coupon_code() {

			$code = strtoupper( substr( base_convert( sha1( uniqid( wp_rand() ) ), 16, 36 ), 0, 16 ) );

			$code = sprintf(
				'%s-%s-%s',
				substr( $code, 0, 4 ),
				substr( $code, 4, 4 ),
				substr( $code, 8, 4 )
			);

			if ( 0 !== wc_get_coupon_id_by_code( $code ) ) {
				$code = self::generate_coupon_code();
			}

			/**
			 * APPLY_FILTERS: ywpar_share_points_coupon_code
			 *
			 * filter the Share coupon code.
			 */
			return apply_filters( 'ywpar_share_points_coupon_code', $code );
		}

		/**
		 * Return the expiration date
		 *
		 * @return string
		 * @throws Exception Throws an exception.
		 */
		public static function get_date_expires() {
			if ( ! self::is_time_limited() ) {
				return '';
			}
			$days         = ywpar_get_option( 'expiration_time_of_share_coupon', 30 );
			$now          = new DateTime();
			$expires_date = $now->add( new DateInterval( 'P' . $days . 'D' ) );
			/**
			 * APPLY_FILTERS: ywpar_share_points_date_expired
			 *
			 * filter the expiration date for Shared coupon.
			 */
			return apply_filters( 'ywpar_share_points_date_expired', $expires_date->format( 'Y-m-d' ) );
		}

		/**
		 * Create a coupon to share points
		 *
		 * @param int                             $points Points.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 *
		 * @return string|
		 */
		public static function create_coupon( $points, $customer ) {
			/**
			 * APPLY_FILTERS: ywpar_share_points_coupon_args
			 *
			 * filter the create Share coupon args.
			 *
			 * @param array $args
			 * @param int $points
			 * @param YITH_WC_Points_Rewards_Customer $customer.
			 */
			$args = apply_filters(
				'ywpar_share_points_coupon_args',
				array(
					'code'          => self::generate_coupon_code(),
					'discount_type' => yith_points()->redeeming->get_conversion_method() === 'fixed' ? 'fixed_cart' : 'percent',
					'usage_limit'   => 1,
					'amount'        => yith_points()->redeeming->calculate_price_worth_from_points( $points, $customer, false ),
					'date_expires'  => self::get_date_expires(),
					// translators: Placeholder number of points and customer name.
					'description'   => sprintf( esc_html_x( '%1$s converted by customer %2$s', 'Placeholder number of points and customer name', 'yith-woocommerce-points-and-rewards' ), $points . ' ' . ywpar_get_option( 'points_label_plural' ), '(#' . $customer->get_id() . ') ' . $customer->get_wc_customer()->get_display_name() ),
				),
				$points,
				$customer
			);

			$coupon = new WC_Coupon();
			$coupon->set_props( $args );
			$coupon->update_meta_data( 'ywpar_shared_coupon_points', $points );
			$coupon->update_meta_data( 'ywpar_shared_coupon_customer', $customer->get_id() );
			$coupon->save();

			// translators:Placeholder is the coupon code.
			$description = sprintf( esc_html_x( 'Created coupon: %s', 'Text displayed inside the history table. Placeholder is the coupon code.', 'yith-woocommerce-points-and-rewards' ), $coupon->get_code() );
			$customer->update_points( -$points, 'shared_points', array( 'description' => $description ) );

			return $coupon;
		}

		/**
		 * Return the coupon info
		 *
		 * Check if the coupon exists if yes get info from the coupon object.
		 *
		 * @param array  $coupon Coupon info.
		 * @param string $code Coupon code.
		 * .
		 * @return array
		 */
		public static function get_coupon_info( $coupon, $code ) {
			$coupon_info   = array();
			$coupon_id     = wc_get_coupon_id_by_code( $code );
			$coupon_object = is_array( $coupon ) && ( $coupon_id === (int) $coupon['id'] ) ? new WC_Coupon( $code ) : false;

			if ( $coupon_object ) {
				$coupon_info['date_created'] = date_i18n( wc_date_format(), wc_string_to_timestamp( $coupon_object->get_date_created() ) );
				$coupon_info['date_expires'] = empty( $coupon_object->get_date_expires() ) ? '-' : date_i18n( wc_date_format(), wc_string_to_timestamp( $coupon_object->get_date_expires() ) );
				$coupon_info['amount']       = 'fixed_cart' === $coupon_object->get_discount_type() ? wc_price( $coupon_object->get_amount() ) : ( $coupon_object->get_amount() . '%' );
				if ( 1 === $coupon_object->get_usage_count() ) {
					$coupon_info['status'] = sprintf( '<span class="ywpar-share-status used">%s</span>', esc_html_x( 'Used', 'the shared point coupon has beed used', 'yith-woocommerce-points-and-rewards' ) );
				} elseif ( ! empty( $coupon_object->get_date_expires() ) && ( time() > wc_string_to_timestamp( $coupon_object->get_date_expires() ) ) ) {
					$coupon_info['status'] = sprintf( '<span class="ywpar-share-status expired">%s</span>', esc_html_x( 'Expired', 'the shared point coupon has beed used', 'yith-woocommerce-points-and-rewards' ) );
				} else {
					$coupon_info['status'] = sprintf( '<span class="ywpar-share-status not-used">%s</span>', esc_html_x( 'Not used', 'the shared point coupon is still valid', 'yith-woocommerce-points-and-rewards' ) );
				}
			} elseif ( is_array( $coupon ) ) {
				$coupon_info['date_created'] = date_i18n( wc_date_format(), wc_string_to_timestamp( $coupon['date_created'] ) );
				$coupon_info['date_expires'] = empty( $coupon['date_expires'] ) ? '-' : date_i18n( wc_date_format(), wc_string_to_timestamp( $coupon['date_expires'] ) );
				$coupon_info['amount']       = 'fixed_cart' === $coupon['discount_type'] ? wc_price( $coupon['amount'] ) : $coupon['amount'] . '%';
				$coupon_info['status']       = sprintf( '<span class="ywpar-share-status deleted">%s</span>', esc_html_x( 'Deleted', 'the shared point coupon is still valid', 'yith-woocommerce-points-and-rewards' ) );
			}

			return $coupon_info;
		}

		/**
		 * Print the share point tab
		 *
		 * @return string
		 */
		public static function print_tab() {
			ob_start();
			wc_get_template( '/myaccount/ywpar-share-points.php', null, '', YITH_YWPAR_TEMPLATE_PATH );
			return ob_get_clean();
		}

		/**
		 * Print user referral copy field
		 *
		 * @param string $code Coupon code.
		 *
		 * @return string
		 * @since  3.0.0
		 */
		public static function print_coupon_code_field( $code ) {
			$field = array(
				'id'       => 'coupon_code',
				'type'     => 'copy-to-clipboard',
				'readonly' => true, // this is true by default.
				'value'    => $code,
			);

			?>
			<div id="ywpar-copy-to-clipboard-wrapper">
				<div class="ywpar-copy-to-clipboard_field-wrap">
					<input type="text"
							id="ywpar_coupon_code"
							class="ywpar-copy-to-clipboard__field"
							value="<?php echo esc_attr( $code ); ?>"
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
