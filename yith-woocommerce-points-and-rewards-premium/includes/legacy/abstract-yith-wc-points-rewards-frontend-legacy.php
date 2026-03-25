<?php //phpcs:ignore phpcs: WordPress.Files.FileName.InvalidClassFileName.
/**
 * YITH_WC_Points_Rewards_Frontend_Legacy Legacy Abstract Class.
 *
 * @class   YITH_WC_Points_Rewards_Frontend_Legacy
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Frontend_Legacy' ) ) {
	/**
	 * Class YITH_WC_Points_Rewards_Frontend_Legacy
	 */
	abstract class YITH_WC_Points_Rewards_Frontend_Legacy {


		/**
		 * |--------------------------------------------------------------------------
		 * | Deprecated Methods
		 * |--------------------------------------------------------------------------
		 */

		/**
		 * Return the message with the placeholder replaced.
		 *
		 * @param WC_Product $product Product.
		 * @param string     $message Message.
		 * @param int        $product_points Points.
		 * @param bool       $loop Loop.
		 * @deprecated 3.0.0
		 * @return mixed
		 */
		private function replace_placeholder_on_product_message( $product, $message, $product_points, $loop = false ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::replace_placeholder_on_product_message', '3.0.0', 'ywpar_replace_placeholder_on_product_message' );
			return ywpar_replace_placeholder_on_product_message( $product, $message, $product_points, $loop = false );
		}

		/**
		 * Enqueue Scripts and Styles
		 *
		 * @return void
		 * @since  1.0.0
		 * @deprecated 3.0.0
		 */
		public function enqueue_styles_scripts() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::enqueue_styles_scripts', '3.0.0', 'YITH_WC_Points_Rewards_Assets::enqueue_frontend_scripts' );
		}

		/**
		 * Shortcode my account points
		 *
		 * @return string
		 * @since  1.1.3
		 * @deprecated 3.0.0
		 */
		public function shortcode_my_account_points() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::shortcode_my_account_points', '3.0.0', 'YITH_WC_Points_Rewards_Shortcodes::get_my_account_points' );
			return YITH_WC_Points_Rewards_Shortcodes::get_instance()->get_my_account_points();
		}

		/**
		 * Add points section to my-account page
		 *
		 * @return string
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function my_account_points() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::my_account_points', '3.0.0', 'YITH_WC_Points_Rewards_Shortcodes::get_my_account_points' );
			return YITH_WC_Points_Rewards_Shortcodes::get_instance()->get_my_account_points();
		}

		/**
		 * Add message in single product page
		 *
		 * @param array $atts .
		 *
		 * @return string
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function show_single_product_message( $atts ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::show_single_product_message', '3.0.0', 'YITH_WC_Points_Rewards_Shortcodes::get_single_product_message' );
			return YITH_WC_Points_Rewards_Shortcodes::get_instance()->get_single_product_message( $atts );
		}

		/**
		 * Shortcode to show the Checkout Thresholds Extra Points Message
		 *
		 * @param array $atts Shortcode params.
		 *
		 * @return  string
		 * @since   1.7.9
		 * @deprecated 3.0.0
		 */
		public function show_checkout_thresholds_message( $atts ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::show_checkout_thresholds_message', '3.0.0', 'YITH_WC_Points_Rewards_Shortcodes::get_checkout_thresholds_message' );
			return YITH_WC_Points_Rewards_Shortcodes::get_instance()->get_checkout_thresholds_message( $atts );
		}

		/**
		 * Add customer birth date field to checkout process
		 *
		 * @param array $fields Fields.
		 *
		 * @return  array
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function add_birthday_field_checkout( $fields ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::add_birthday_field_checkout', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points_Birthdate::add_birthday_field_checkout' );
			return YITH_WC_Points_Rewards_Extra_Points_Birthdate::add_birthday_field_checkout( $fields );
		}

		/**
		 * Add the menu item on WooCommerce My account Menu
		 * before the Logout item menu.
		 *
		 * @param array $wc_menu WooCommerce menu.
		 * @deprecated 3.0.0
		 * @return mixed
		 */
		public function ywpar_add_points_menu_items( $wc_menu ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::ywpar_add_points_menu_items', '3.0.0', 'yith_points()->ywpar_add_points_menu_items' );
			return yith_points()->ywpar_add_points_menu_items( $wc_menu );
		}

		/**
		 * Check if the permalink should be flushed.
		 *
		 * @param array $rules Rules.
		 * @deprecated 3.0.0
		 * @return bool
		 */
		public function rewrite_rules( $rules ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::rewrite_rules', '3.0.0', 'yith_points()->rewrite_rules' );
			return yith_points()->rewrite_rules( $rules );
		}

		/**
		 * Add customer birthdate field to edit account page
		 *
		 * @return  void
		 * @throws Exception Throws Exception message.
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function add_birthday_field() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Frontend::add_birthday_field', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points_Birthdate::add_birthday_field' );
			YITH_WC_Points_Rewards_Extra_Points_Birthdate::add_birthday_field();
		}

	}
}

if ( ! function_exists( 'YITH_WC_Points_Rewards_Frontend' ) ) {
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards_Frontend class
	 *
	 * @deprecated 3.0.0
	 * @since      1.0.0
	 * @return YITH_WC_Points_Rewards_Frontend
	 */
	function YITH_WC_Points_Rewards_Frontend() { //phpcs:ignore
		_deprecated_function( __FUNCTION__, '3.0.0', 'yith_points()->frontend' );
		return yith_points()->frontend;
	}
}
