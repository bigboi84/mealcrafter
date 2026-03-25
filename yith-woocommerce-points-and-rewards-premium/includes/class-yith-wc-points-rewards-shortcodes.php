<?php
/**
 * Class to implement shortcodes
 *
 * @class   YITH_WC_Points_Rewards_Shortcodes
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

use function PHPSTORM_META\type;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Shortcodes' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Shortcodes
	 */
	class YITH_WC_Points_Rewards_Shortcodes {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Shortcodes
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Shortcodes
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
			add_shortcode( 'yith_points_product_message', array( $this, 'get_single_product_message' ) );
			add_shortcode( 'yith_points_product_message_loop', array( $this, 'get_single_product_message_loop' ) );
			add_shortcode( 'yith_checkout_thresholds_message', array( $this, 'get_checkout_thresholds_message' ) );

			add_shortcode( 'ywpar_my_account_points', array( $this, 'get_my_account_points' ) );
			add_shortcode( 'ywpar_customers_points', array( $this, 'get_customers_points' ) );

			add_shortcode( 'yith_ywpar_points', array( $this, 'get_current_customer_points' ) );
			add_shortcode( 'yith_ywpar_points_list', array( $this, 'get_current_customer_history' ) );

			add_shortcode( 'ywpar_referral_link', array( $this, 'get_referral_link' ) );
		}

		/**
		 * Return referral url for specified or current user
		 *
		 * @param array $atts Attributes.
		 *
		 * @return string
		 * @since  3.20.0
		 */		
		public function get_referral_link( $atts ) {
			$atts = shortcode_atts( array(
				'user_id'  => 0,
				'title'    => esc_html__( 'Share to Earn', 'yith-woocommerce-points-and-rewards' ),
				'subtitle' => esc_html__( 'Your referral link', 'yith-woocommerce-points-and-rewards' ),
			), $atts );

			$content = '';

			$userd_id = 'auto' === $atts['user_id'] ? get_current_user_id() : ( (int)$atts['user_id'] > 0 ? $atts['user_id'] : 0 );

			if ( $userd_id ) {
				wp_enqueue_style( 'yith-plugin-fw-icon-font' );
				wp_enqueue_script( 'ywpar_frontend_my_account' );
				ob_start();
				echo '<div id="ywpar_referral_link_sc">';
				echo !empty( $atts['title'] ) ? '<p>' . esc_html( $atts['title'] ) . '</p>' : '';
				echo !empty( $atts['subtitle'] ) ? '<span>' . esc_html( $atts['subtitle'] ) . '</span>' : '';
				YITH_WC_Points_Rewards_Referral::print_user_referral_field( $userd_id );
				echo '</div>';
				$content = ob_get_clean(); 
			}

			return $content;

		}
		/**
		 * Return the message for single product page
		 *
		 * @param array $atts Attributes.
		 *
		 * @return string
		 * @since  2.2.0
		 */
		public function get_single_product_message( $atts ) {

			if ( ywpar_hide_points_for_guests() || 'yes' !== ywpar_get_option( 'enabled_single_product_message' ) ) {
				return '';
			}

			$colors = ywpar_get_option(
				'single_product_points_message_colors',
				array(
					'text_color'       => '#000000',
					'background_color' => '#E4F6F3',
				)
			);
			
			$atts = shortcode_atts(
				array(
					'product_id'       => 0,
					'message'          => ywpar_get_option( 'single_product_message' ),
					'text_color'       => apply_filters( 'ywpar_single_product_message_text_color', $colors['text_color'] ), // retro compatibility.
					'background_color' => apply_filters( 'ywpar_single_product_message_bg_color', $colors['background_color'] ), // retro compatibility.
				),
				$atts
			);

			$product_id = intval( $atts['product_id'] );
			$message    = htmlspecialchars_decode($atts['message'], ENT_QUOTES);

			if ( ! $product_id ) {
				global $product;
				if ( ( ! $product || 'string' === gettype( $product ) ) && 'product' === get_post_type( get_the_ID() ) ) {
					$product = wc_get_product( get_the_ID() );
				}
			} else {
				$product = wc_get_product( $product_id );
			}

			if ( ! $product || $product->is_type( 'external' ) ) {
				return '';
			}

			$product_points = yith_points()->earning->get_product_points( $product );

			if ( ( strpos( $product_points, '-' ) >= 0 && ( '0-0' === $product_points ) ) || ( false === strpos( $product_points, '-' ) && (int) $product_points <= 0 ) ) {
				return '';
			}

			$message      = ywpar_replace_placeholder_on_product_message( $product, $message, $product_points );
			$colors_style = 'style="background-color:' . $atts['background_color'] . '; color: ' . $atts['text_color'] . '"';

			$class = 'hide';
			if ( $product->is_type( 'variable' ) ) {
				$message = '<div class="yith-par-message yith-par-message-product ' . esc_attr( $class ) . '" ' . $colors_style . ' >' . $message . '</div><div class="yith-par-message-variation ' . esc_attr( $class ) . '" ' . $colors_style . ' >' . $message . '</div>';
			} else {
				$message = '<div class="yith-par-message yith-par-message-product" ' . $colors_style . '>' . $message . '</div>';
			}
			// APPLY_FILTER : ywpar_point_message_single_page: filtering the point message on single product page.
			return apply_filters( 'ywpar_point_message_single_page', $message, $product, $class );
		}

		/**
		 * Return the message for loop
		 *
		 * @param array $atts Attributes.
		 *
		 * @return string
		 * @since   2.2.0
		 */
		public function get_single_product_message_loop( $atts ) {

			if ( ywpar_hide_points_for_guests() ) {
				return '';
			}

			$colors = ywpar_get_option(
				'loop_points_message_colors',
				array(
					'text_color'       => '#000000',
					'background_color' => 'rgba(255,255,255,0)',
					'border_color'     => '#000000',
				)
			);

			$border_color     = $colors['border_color'] ?? '#000000';
			$text_color       = $colors['text_color'] ?? '#000000';
			$background_color = $colors['background_color'] ?? 'rgba(255,255,255,0)';

			$atts = shortcode_atts(
				array(
					'product_id'       => 0,
					'message'          => ywpar_get_option( 'loop_message' ),
					'text_color'       => apply_filters( 'ywpar_single_product_message_text_color', $text_color ), // retro compatibility.
					'background_color' => apply_filters( 'ywpar_loop_message_bg_color', $background_color ), // retro compatibility.
					'border_color'     => apply_filters( 'ywpar_loop_message_border_color', $border_color ),
				),
				$atts
			);

			$product_id = intval( $atts['product_id'] );

			if ( ! $product_id ) {
				global $product;
			} else {
				$product = wc_get_product( $product_id );
			}

			if ( ! $product || $product->is_type( 'external' ) ) {
				return '';
			}

			$product_points = yith_points()->earning->get_product_points( $product );

			if ( (int) $product_points <= 0 ) {
				return '';
			}

			$message      = ywpar_replace_placeholder_on_product_message( $product, $atts['message'], $product_points, true );
			$colors_style = 'style="background-color:' . $background_color . '; color: ' . $text_color . ';border-color:' . $border_color . '"';

			$message = '<div ' . $colors_style . ' class="yith-par-message yith-par-message-loop"><div class="yith-par-message-inner-content">' . wp_kses_post( $message ) . '</div></div>';

			// APPLY_FILTER : ywpar_point_message_single_page: filtering the point message on single product page.
			return apply_filters( 'ywpar_single_product_message_in_loop', $message, $product, $product_points );
		}

		/**
		 * Shortcode to show the Checkout Thresholds Extra Points Message
		 *
		 * @param array $atts Shortcode params.
		 *
		 * @return  string
		 * @since  2.2.0
		 */
		public function get_checkout_thresholds_message( $atts ) {

			if ( ywpar_hide_points_for_guests() ) {
				return '';
			}

			$atts = shortcode_atts(
				array(
					'title' => ywpar_get_option( 'checkout_threshold_show_message_title' ),
				),
				$atts
			);

			$message = '';

			$checkout_thresholds = ywpar_get_option( 'checkout_threshold_exp' );
			$current_currency    = ywpar_get_currency();
			$thresholds          = array();

			foreach ( $checkout_thresholds['list'] as $list ) {
				if ( isset( $list[ $current_currency ] ) ) {
					$thresholds[] = $list[ $current_currency ];
				}
			}

			array_multisort( array_column( $thresholds, 'number' ), SORT_DESC, $thresholds );

			if ( ! empty( $thresholds ) ) {
				ob_start();
				echo '<div id="yith-par-message-checkout_threshold" class="woocommerce-cart-notice woocommerce-info">';
				if ( trim( $atts['title'] ) !== '' ) {
					echo '<h4>' . esc_html( $atts['title'] ) . '</h4>';
				}
				echo '<p><strong>' . esc_html__( 'Points you can get:', 'yith-woocommerce-points-and-rewards' ) . '</strong></p>';
				/**
				 * DO_ACTION: ywpar_checkout_thresholds_message_before
				 *
				 * hook before the checkout thresholds message.
				 */
				do_action( 'ywpar_checkout_thresholds_message_before' );

				foreach ( $thresholds as $checkout_threshold ) {
					echo '<p>' . wp_kses_post( wc_price( $checkout_threshold['number'] ) ) . ' - ' . esc_html( $checkout_threshold['points'] ) . ' ' .
						wp_kses_post( ywpar_get_option( 'points_label_plural' ) ) . '</p>';
				}
				/**
				 * DO_ACTION: ywpar_checkout_thresholds_message_after
				 *
				 * hook after the checkout thresholds message.
				 */
				do_action( 'ywpar_checkout_thresholds_message_after' );
				echo '</div>';
				$message = ob_get_clean();
			}

			return $message;
		}

		/**
		 * Shortcode my account points
		 *
		 * @return string
		 * @since  2.2.0
		 */
		public function get_my_account_points() {
			$customer = ywpar_get_current_customer();
			if ( ! $customer || ! $customer->is_enabled() ) {
				return '';
			}

			ob_start();
			wc_get_template( '/myaccount/my-points-view.php', null, '', YITH_YWPAR_TEMPLATE_PATH );
			return ob_get_clean();
		}

		/**
		 * Shortcode Customers' Points
		 *
		 * @param array $atts Attributes.
		 * @return string
		 * @since  2.2.0
		 */
		public function get_customers_points( $atts ) {

			$atts = shortcode_atts(
				array(
					'style'            => 'simple',
					'tabs'             => 'yes',
					'num_of_customers' => 3,
				),
				$atts,
				'ywpar_best_users'
			);

			wp_enqueue_style( 'ywpar_icons' );
			$atts['tabs']  = ( in_array( $atts['tabs'], array( 'yes', 'true', '1' ) ) ) ? 'yes' : 'no'; //phpcs:ignore
			$atts['times'] = ( 'yes' === $atts['tabs'] ) ? array( 'all_time', 'last_year', 'last_month', 'this_week', 'today' ) : array( 'all_time' );

			ob_start();
			wc_get_template( '/shortcodes/ywpar-customers-points.php', $atts, '', YITH_YWPAR_TEMPLATE_PATH );
			return ob_get_clean();
		}

		/**
		 * Shortcode to show the current customer points
		 *
		 * @param array $atts Attributes.
		 * @param null  $content Content.
		 *
		 * @return string|void
		 */
		public function get_current_customer_points( $atts, $content = null ) {
			$customer = ywpar_get_current_customer();
			if ( ! $customer || ! $customer->is_enabled() ) {
				return '';
			}

			$a = shortcode_atts(
				array(
					'label'      => __( 'Your credit is ', 'yith-woocommerce-points-and-rewards' ),
					'show_worth' => 'no',
				),
				$atts
			);

			$points   = $customer->get_total_points();
			$singular = ywpar_get_option( 'points_label_singular' );
			$plural   = ywpar_get_option( 'points_label_plural' );

			$toredeem = 'yes' === $a['show_worth'] ? yith_points()->redeeming->calculate_price_worth_from_points( $points, $customer ) : '';

			ob_start();

			echo '<p>' . esc_html( $a['label'] ) . ' ';
			// translators: First placeholder: number of points; second and third placeholder: labels of points.
			printf( wp_kses_post( _nx( '<strong>%1$s</strong> %2$s', '<strong>%1$s</strong> %3$s', $points, 'First placeholder: number of points; second and third placeholder: labels of points', 'yith-woocommerce-points-and-rewards' ) ), esc_html( $points ), esc_html( $singular ), esc_html( $plural ) );
			if ( ! empty( $toredeem ) ) {
				echo ' <span class="ywpar_worth">(' . __( 'worth', 'yith-woocommerce-points-and-rewards' ) . ' ' . $toredeem . ')</span>';
			}
			echo '</p>';

			return ob_get_clean();
		}

		/**
		 * Shortcode of the list of points in my account
		 *
		 * @param array $atts Attributes.
		 * @param null  $content Content.
		 *
		 * @return string|void
		 */
		public function get_current_customer_history( $atts, $content = null ) {

			$customer = ywpar_get_current_customer();
			if ( ! $customer || ! $customer->is_enabled() ) {
				return '';
			}

			ob_start();

			wc_get_template( '/myaccount/my-points-view.php', null, '', YITH_YWPAR_TEMPLATE_PATH );

			return ob_get_clean();

		}
	}
}
