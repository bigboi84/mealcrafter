<?php
/**
 * Collection of all deprecated functions
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'ywpar_woocommerce_currency_switcher_currency_list' ) ) {

	/**
	 * Return the list of active currencies.
	 *
	 * @param array $currencies Currencies.
	 *
	 * @deprecated 2.2.0 Use ywpar_woocommerce_currency_switcher()->get_currency_list()
	 * @return array
	 * @since  1.5.3
	 */
	function ywpar_woocommerce_currency_switcher_currency_list( $currencies ) {
		global $WOOCS; //phpcs:ignore
		$enabled_currencies = array_keys( $WOOCS->get_currencies() ); //phpcs:ignore
		return ! empty( $enabled_currencies ) ? $enabled_currencies : $currencies;
	}
};

if ( ! function_exists( 'ywpar_woocs_before_rewards_message' ) ) {
	/**
	 * Remove some filter before rewards message
	 *
	 * @deprecated  2.2.0 ywpar_woocommerce_currency_switcher()->before_currency_loop()
	 * @since 1.5.2
	 */
	function ywpar_woocs_before_rewards_message() {
		global $WOOCS; //phpcs:ignore
		remove_filter( 'wc_price_args', array( $WOOCS, 'wc_price_args' ), 9999 ); //phpcs:ignore
		remove_filter( 'raw_woocommerce_price', array( $WOOCS, 'raw_woocommerce_price' ), 9999 ); //phpcs:ignore
	}
}


if ( ! function_exists( 'ywpar_woocs_after_rewards_message' ) ) {
	/**
	 * Add some filter after rewards message
	 *
	 * @deprecated  2.2.0 ywpar_woocommerce_currency_switcher()->after_rewards_message()
	 * @since 1.5.2
	 */
	function ywpar_woocs_after_rewards_message() {
		global $WOOCS; //phpcs:ignore
		add_filter( 'wc_price_args', array( $WOOCS, 'wc_price_args' ), 9999 ); //phpcs:ignore
		if ( ! $WOOCS->is_multiple_allowed ) { //phpcs:ignore
			add_filter( 'raw_woocommerce_price', array( $WOOCS, 'raw_woocommerce_price' ), 9999 ); //phpcs:ignore
		}

	}
}

if ( ! function_exists( 'ywpar_woocs_convert_price' ) ) {
	/**
	 * Convert price
	 *
	 * @deprecated  2.2.0 ywpar_woocommerce_currency_switcher()->convert_price()
	 * @param float  $price Price to convert.
	 * @param string $currency Currency.
	 *
	 * @return float|int
	 * @since  1.5.3
	 */
	function ywpar_woocs_convert_price( $price, $currency = '' ) {
		global $WOOCS; //phpcs:ignore
		if ( $WOOCS->is_multiple_allowed || ! $WOOCS ) { //phpcs:ignore
			return $price;
		}
		$currencies = $WOOCS->get_currencies(); //phpcs:ignore
		$currency = empty( $currency ) ? $WOOCS->current_currency : $currency; //phpcs:ignore
		if ( isset( $currencies[ $currency ] ) ) {
			$price = $price * $currencies[ $currency ]['rate'];
		}

		return $price;
	}
}

if ( ! function_exists( 'ywpar_woocs_hide_value_for_max_discount' ) ) {
	/**
	 * Hide value for max discount.
	 *
	 * @deprecated  2.2.0 ywpar_woocommerce_currency_switcher()->hide_value_for_max_discount()
	 * @param float $discount Discount.
	 *
	 * @return int
	 * @since  1.5.3
	 */
	function ywpar_woocs_hide_value_for_max_discount( $discount ) {
		global $WOOCS; //phpcs:ignore
		if ( $WOOCS->is_multiple_allowed ) { //phpcs:ignore
			$currencies = $WOOCS->get_currencies(); //phpcs:ignore
			return $WOOCS->back_convert( $discount, $currencies[$WOOCS->current_currency]['rate'] ); //phpcs:ignore
		}
		remove_all_filters( 'ywpar_calculate_rewards_discount_max_discount_fixed' );
		remove_all_filters( 'ywpar_calculate_rewards_discount_max_discount_percentual' );

		return yith_points()->redeeming->calculate_rewards_discount();
	}
}

if ( ! function_exists( 'ywpar_woocs_adjust_discount_value' ) ) {
	/**
	 * Adjust the discount value
	 *
	 * @deprecated  2.2.0 ywpar_woocommerce_currency_switcher()->adjust_discount_value()
	 * @param float $discount Discount.
	 * @return float
	 * @since  1.5.3
	 */
	function ywpar_woocs_adjust_discount_value( $discount ) {
		global $WOOCS; //phpcs:ignore
		if ( $WOOCS->is_multiple_allowed ) { //phpcs:ignore
			$currencies = $WOOCS->get_currencies(); //phpcs:ignore
			$discount = $WOOCS->back_convert( $discount, $currencies[$WOOCS->current_currency]['rate'] ); //phpcs:ignore
		}
		return $discount;
	}
}

if ( ! function_exists( 'yith_ywpar_locate_template' ) ) {
	/**
	 * Locate the templates and return the path of the file found
	 *
	 * @deprecated  2.2.0 Unnecessary function.
	 * @param string $path Path.
	 * @param array  $var Array.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function yith_ywpar_locate_template( $path, $var = null ) {

		global $woocommerce;

		if ( function_exists( 'WC' ) ) {
			$woocommerce_base = WC()->template_path();
		} elseif ( defined( 'WC_TEMPLATE_PATH' ) ) {
			$woocommerce_base = WC_TEMPLATE_PATH;
		} else {
			$woocommerce_base = $woocommerce->plugin_path() . '/templates/';
		}

		$template_woocommerce_path = $woocommerce_base . $path;
		$template_path             = '/' . $path;
		$plugin_path               = YITH_YWPAR_DIR . 'templates/' . $path;

		$located = locate_template(
			array(
				$template_woocommerce_path, // Search in <theme>/woocommerce/.
				$template_path, // Search in <theme>/.
				$plugin_path, // Search in <plugin>/templates/.
			)
		);

		if ( ! $located && file_exists( $plugin_path ) ) {
			return apply_filters( 'yith_ywpar_locate_template', $plugin_path, $path );
		}

		return apply_filters( 'yith_ywpar_locate_template', $located, $path );
	}
}

if ( ! function_exists( 'ywpar_get_price' ) ) {
	/**
	 * Return the price based on tax settings on redeeming points
	 *
	 * @param WC_Product $product Product.
	 * @param int        $qty Quantity.
	 * @param float      $price Price.
	 *
	 * @return float|string
	 * @deprecated
	 */
	function ywpar_get_price( $product, $qty = 1, $price = '' ) {
		return ywpar_get_product_price( $product, 'redeem', $qty, $price );
	}
}
