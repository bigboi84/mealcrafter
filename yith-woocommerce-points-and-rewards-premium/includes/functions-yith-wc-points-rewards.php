<?php
/**
 * Implements helper functions for YITH WooCommerce Points and Rewards
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

use Elementor\Plugin;

defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'ywpar_get_action_label' ) ) {
	/**
	 * Gets the label for an action
	 *
	 * @param string $label Label.
	 *
	 * @return string
	 * @since  3.0.0
	 */
	function ywpar_get_action_label( $label ) {
		return apply_filters( 'yith_ywpar_action_label', ywpar_get_option( 'label_' . $label, '' ), $label );
	}
}

if ( ! function_exists( 'ywpar_get_manage_points_capability' ) ) {
	/**
	 * Gets the capability to manage points
	 *
	 * @return string
	 * @since  3.0.0
	 */
	function ywpar_get_manage_points_capability() {
		$capability = 'yes' === ywpar_get_option( 'enabled_shop_manager' ) ? 'manage_woocommerce' : 'manage_options';
		/**
		 * APPLY_FILTERS: yith_ywpar_manage_points_capability
		 *
		 * filter the capability - defaul manage_options.
		 */
		return apply_filters( 'yith_ywpar_manage_points_capability', $capability );
	}
}

if ( ! function_exists( 'yith_ywpar_get_roles' ) ) {
	/**
	 * Return the roles of users
	 *
	 * @return array
	 * @since 1.0.0
	 */
	function yith_ywpar_get_roles() {
		global $wp_roles;
		$roles = array();
		if ( $wp_roles ) {
			foreach ( $wp_roles->get_names() as $key => $role ) {
				$roles[ $key ] = translate_user_role( $role );
			}
		}

		return $roles;
	}
}


if ( ! function_exists( 'yith_ywpar_get_roles_enabled_to_earn' ) ) {
	/**
	 * Return the roles of users enabled to earn
	 *
	 * @return array
	 * @since 3.0.0
	 */
	function yith_ywpar_get_roles_enabled_to_earn() {

		$looks_for_roles_enabled_to_earn = ywpar_get_option( 'user_role_enabled_type', 'all' );

		$user_role_enabled = ( empty( $looks_for_roles_enabled_to_earn ) || 'all' === $looks_for_roles_enabled_to_earn ) ? array( 'all' ) : ywpar_get_option( 'user_role_enabled', array( 'all' ) );

		/**
		 * APPLY_FILTERS: ywpar_roles_enabled_to_earn
		 *
		 * filter the roles enabeld to earn points.
		 *
		 * @param array $roles
		 */
		return apply_filters( 'ywpar_roles_enabled_to_earn', (array) $user_role_enabled );
	}
}

if ( ! function_exists( 'yith_ywpar_get_roles_enabled_to_redeem' ) ) {
	/**
	 * Return the roles of users enabled to redeem points
	 *
	 * @return array
	 * @since 3.0.0
	 */
	function yith_ywpar_get_roles_enabled_to_redeem() : array {

		$looks_for_roles_enabled_to_redeem_points = ywpar_get_option( 'user_role_redeem_type', 'all' );

		$user_role_enabled = ( 'roles' !== $looks_for_roles_enabled_to_redeem_points ) ? array( 'all' ) : ywpar_get_option( 'user_role_redeem_enabled', array( 'all' ) );
		/**
		 * APPLY_FILTERS: ywpar_roles_enabled_to_redeem
		 *
		 * filter the roles enabeld to redeem points.
		 *
		 * @param array $roles
		 */
		return apply_filters( 'ywpar_roles_enabled_to_redeem', (array) $user_role_enabled );
	}
}


if ( ! function_exists( 'yith_ywpar_round_points' ) ) {
	/**
	 * Return rounded points.
	 *
	 * @param numeric $points Points to round.
	 *
	 * @return int
	 * @since 3.0.0
	 */
	function yith_ywpar_round_points( $points ) {

		
		$is_really_float = is_numeric($points) && strpos($points, '.') !== false;

		if ( ! is_float( $points ) || ! $is_really_float ) {
			return $points;
		}

		$floor          = ( 'down' === ywpar_get_option( 'points_round_type', 'down' ) || apply_filters( 'ywpar_floor_points', false ) );
		$rounded_points = $floor ? floor( $points ) : ceil( $points );
		return (int) $rounded_points;
	}
}


if ( ! function_exists( 'ywpar_get_point_customer_from_order' ) ) {
	/**
	 * Return the customer point from an order
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return YITH_WC_Points_Rewards_Customer|bool
	 * @since 3.0.0
	 */
	function ywpar_get_point_customer_from_order( $order ) {

		$customer_user_id = $order->get_customer_id();

		if ( 0 === $customer_user_id && ywpar_get_option( 'assign_points_to_registered_guest', 'no' ) === 'yes' ) {
			$customer_user    = get_user_by( 'email', $order->get_billing_email() );
			$customer_user_id = $customer_user ? $customer_user->ID : false;
		}

		$customer = $customer_user_id ? ywpar_get_customer( $customer_user_id ) : false;
		return $customer;
	}
}


if ( ! function_exists( 'ywpar_get_order_status_to_earn_points' ) ) {
	/**
	 * Return the order status list to earn points.
	 *
	 * @return mixed|void
	 */
	function ywpar_get_order_status_to_earn_points() {
		$options = array(
			'woocommerce_order_status_completed'  => esc_html__( 'Order Completed', 'yith-woocommerce-points-and-rewards' ),
			'woocommerce_payment_complete'        => esc_html__( 'Payment Completed', 'yith-woocommerce-points-and-rewards' ),
			'woocommerce_order_status_processing' => esc_html__( 'Order Processing', 'yith-woocommerce-points-and-rewards' ),
		);

		/**
		 * APPLY_FILTERS: ywpar_order_status_to_earn_points
		 *
		 * filter the order statuses to earn points to show in the backend related option.
		 *
		 * @param array $statuses
		 */
		return apply_filters( 'ywpar_order_status_to_earn_points', $options );
	}
}

if ( ! function_exists( 'ywpar_get_custom_attributes_of_custom_field' ) ) {
	/**
	 * Return string to add to the custom field on panel option
	 *
	 * @param array $field Custom Field.
	 * @return mixed|void
	 */
	function ywpar_get_custom_attributes_of_custom_field( $field ) {
		if ( ! isset( $field['custom_attributes'] ) ) {
			$field['custom_attributes'] = '';
		} elseif ( is_array( $field['custom_attributes'] ) ) {
			$custom_attributes = array();
			foreach ( $field['custom_attributes'] as $attribute => $attribute_value ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';
			}

			$field['custom_attributes'] = implode( ' ', $custom_attributes );
		}

		return $field['custom_attributes'];
	}
}

if ( ! function_exists( 'ywpar_is_multivendor_active' ) ) {

	/**
	 * Check if YITH WooCommerce Multi Vendor is active
	 *
	 * @return  boolean
	 * @since   3.0.0
	 */
	function ywpar_is_multivendor_active() {
		return defined( 'YITH_WPV_PREMIUM' ) && YITH_WPV_PREMIUM;
	}
}

if ( ! function_exists( 'ywpar_check_valid_admin_page' ) ) {
	/**
	 * Return if the current pagenow is valid for a post_type, useful if you want add metabox, scripts inside the editor of a particular post type.
	 *
	 * @param string $post_type_name Post type.
	 *
	 * @return bool
	 * @since 3.0.0
	 */
	function ywpar_check_valid_admin_page( $post_type_name ) {
		global $pagenow;
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';

		$post = isset( $_REQUEST['post'] ) ? $_REQUEST['post'] : ( isset( $_REQUEST['post_ID'] ) ? $_REQUEST['post_ID'] : 0 ); // phpcs:ignore
		$post = get_post( $post );

		return 'edit-' . $post_type_name === $screen_id || ( $post && $post->post_type === $post_type_name ) || ( 'post-new.php' === $pagenow && isset( $_REQUEST['post_type'] ) && $post_type_name === $_REQUEST['post_type'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
}

if ( ! function_exists( 'ywpar_get_user_levels' ) ) {
	/**
	 * Return the user level list.
	 *
	 * @return array
	 * @since 3.0.0
	 */
	function ywpar_get_user_levels() {

		$levels_badges  = YITH_WC_Points_Rewards_Helper::get_levels_badges();
		$levels_options = array();

		foreach ( $levels_badges as $level_id => $level ) {
			$levels_options[ $level_id ] = $level->get_name();
		}

		return $levels_options;
	}
}


if ( ! function_exists( 'ywpar_date_placeholders' ) ) {
	/**
	 * Get Date placeholders
	 *
	 * @return  array
	 * @since   1.6.0
	 */
	function ywpar_date_placeholders() {
		/**
		 * APPLY_FILTERS: ywpar_date_placeholders
		 *
		 * filter the date placeholder for birthday option.
		 *
		 * @param array $dates
		 */
		return apply_filters(
			'ywpar_date_placeholders',
			array(
				'yy-mm-dd' => 'YYYY-MM-DD',
				'yy/mm/dd' => 'YYYY/MM/DD',
				'mm-dd-yy' => 'MM-DD-YYYY',
				'mm/dd/yy' => 'MM/DD/YYYY',
				'dd-mm-yy' => 'DD-MM-YYYY',
				'dd/mm/yy' => 'DD/MM/YYYY',
			)
		);
	}
}

if ( ! function_exists( 'ywpar_get_date_patterns' ) ) {
	/**
	 * Get Date patterns
	 *
	 * @return  array
	 * @since   1.6.0
	 */
	function ywpar_get_date_patterns() {
		/**
		 * APPLY_FILTERS: ywpar_date_patterns
		 *
		 * filter the date patterns for birtday option - to use with ywpar_date_placeholders filter.
		 *
		 * @param
		 */
		return apply_filters(
			'ywpar_date_patterns',
			array(
				'yy-mm-dd' => '([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
				'yy/mm/dd' => '([0-9]{4})\/(0[1-9]|1[012])\/(0[1-9]|1[0-9]|2[0-9]|3[01])',
				'mm-dd-yy' => '(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])-([0-9]{4})',
				'mm/dd/yy' => '(0[1-9]|1[012])\/(0[1-9]|1[0-9]|2[0-9]|3[01])\/([0-9]{4})',
				'dd-mm-yy' => '(0[1-9]|1[0-9]|2[0-9]|3[01])-(0[1-9]|1[012])-([0-9]{4})',
				'dd/mm/yy' => '(0[1-9]|1[0-9]|2[0-9]|3[01])\/(0[1-9]|1[012])\/([0-9]{4})',
			)
		);
	}
}


if ( ! function_exists( 'ywpar_get_active_extra_points_rules' ) ) {
	/**
	 * Get a list of all active extra points rules
	 *
	 * @return array List of active extra points rules by slug.
	 * @since  3.0.0
	 */
	function ywpar_get_active_extra_points_rules() {
		$options = array(
			'enable_points_on_daily_login_exp',
			'enable_points_on_registration_exp',
			'enable_points_on_completed_profile_exp',
			'enable_points_on_referral_registration_exp',
			'enable_points_on_referral_purchase_exp',
			'enable_point_on_collected_points_exp',
			'enable_point_on_achieve_level_exp',
			'enable_number_of_points_exp',
			'enable_points_on_birthday_exp',
			'enable_review_exp',
			'enable_num_order_exp',
			'enable_checkout_threshold_exp',
			'enable_amount_spent_exp',
			'enable_point_on_membership_plan_exp',
		);

		$active_options = array();

		foreach ( $options as $option ) {
			if ( 'yes' === ywpar_get_option( $option ) ) {
				array_push( $active_options, $option );
			}
		}

		return $active_options;

	}
}

if ( ! function_exists( 'ywpar_get_banners' ) ) {
	/**
	 * Get all banners by type
	 *
	 * @param string $type Type of banners ( target | get_points | simple ).
	 * @return array
	 * @since  3.0.0
	 **/
	function ywpar_get_banners( $type = 'all' ) {
		return YITH_WC_Points_Rewards_Helper::get_banners( $type );
	}
}

if ( ! function_exists( 'ywpar_get_banners_template_filename' ) ) {
	/**
	 * Return the banner template filename by type
	 *
	 * @param string $type Type of banner.
	 * @return string
	 * @since  3.0.0
	 **/
	function ywpar_get_banner_template_filename( $type ) {
		$templates = array(
			'enable_points_on_referral_registration_exp' => 'get-points-refer-registration',
			'enable_points_on_referral_purchase_exp'     => 'get-points-refer-purchase',
			'enable_review_exp'                          => 'get-points-review',
			'enable_points_on_completed_profile_exp'     => 'get-points-profile',
			'enable_point_on_achieve_level_exp'          => 'target-levels',
			'enable_number_of_points_exp'                => 'target-points-collected',
			'enable_amount_spent_exp'                    => 'target-amount-spent',
		);
		return isset( $templates[ $type ] ) ? $templates[ $type ] : 'simple';
	}
}

if ( ! function_exists( 'ywpar_get_banner_precompiled_texts' ) ) {
	/**
	 * Get Banner pre-compiled text (subtitle) values
	 *
	 * @return  array
	 * @since   3.0.0
	 */
	function ywpar_get_banner_precompiled_texts() {

		$precompiled_titles = array(
			'enable_point_on_achieve_level_exp'          => esc_html__( 'When you achieve %points%', 'yith-woocommerce-points-and-rewards' ),
			'enable_number_of_points_exp'                => esc_html__( 'When you achieve %points%', 'yith-woocommerce-points-and-rewards' ),
			'enable_amount_spent_exp'                    => esc_html__( 'When you spend a total of %amount% in our shop you will earn an extra %points%', 'yith-woocommerce-points-and-rewards' ),
			'enable_points_on_referral_registration_exp' => esc_html__( 'Get %points% for each user that registers by clicking on your referral link', 'yith-woocommerce-points-and-rewards' ),
			'enable_points_on_referral_purchase_exp'     => esc_html__( 'Get %points% for each user that complete an order by clicking on your referral link', 'yith-woocommerce-points-and-rewards' ),
			'enable_review_exp'                          => esc_html__( "Get %points% extra if you leave a review for %products% you've purchased", 'yith-woocommerce-points-and-rewards' ),
			'enable_points_on_completed_profile_exp'     => esc_html__( 'and get %points%', 'yith-woocommerce-points-and-rewards' ),
		);

		return $precompiled_titles;

	}
}

if ( ! function_exists( 'ywpar_get_precompiled_text' ) ) {
	/**
	 * Get precompiled title value by type
	 *
	 * @param string $type Type.
	 * @return  string
	 * @since   3.0.0
	 */
	function ywpar_get_precompiled_text( $type ) {
		$precompiled_texts = ywpar_get_banner_precompiled_texts();
		return isset( $precompiled_texts[ $type ] ) ? $precompiled_texts[ $type ] : '';
	}
}

if ( ! function_exists( 'ywpar_get_banner_precompiled_titles' ) ) {
	/**
	 * Get Banner precomplied title values
	 *
	 * @return  array
	 * @since   3.0.0
	 */
	function ywpar_get_banner_precompiled_titles() {

		$precompiled_titles = array(
			'enable_point_on_achieve_level_exp'          => esc_html__( 'Upgrade to %level%', 'yith-woocommerce-points-and-rewards' ),
			'enable_number_of_points_exp'                => esc_html__( 'Get %points% Extra Points', 'yith-woocommerce-points-and-rewards' ),
			'enable_amount_spent_exp'                    => esc_html__( 'Get %points% when you spend %amount%', 'yith-woocommerce-points-and-rewards' ),
			'enable_points_on_referral_registration_exp' => esc_html__( 'Refer a friend', 'yith-woocommerce-points-and-rewards' ),
			'enable_points_on_referral_purchase_exp'     => esc_html__( 'Referral purchase', 'yith-woocommerce-points-and-rewards' ),
			'enable_review_exp'                          => esc_html__( 'Leave a review', 'yith-woocommerce-points-and-rewards' ),
			'enable_points_on_completed_profile_exp'     => esc_html__( 'Complete your profile', 'yith-woocommerce-points-and-rewards' ),
		);

		return $precompiled_titles;

	}
}

if ( ! function_exists( 'ywpar_get_precompiled_title' ) ) {
	/**
	 * Get precompiled title value by type
	 *
	 * @param string $type Type.
	 * @return  string
	 * @since   3.0.0
	 */
	function ywpar_get_precompiled_title( $type ) {

		$precompiled_titles = ywpar_get_banner_precompiled_titles();

		return isset( $precompiled_titles[ $type ] ) ? $precompiled_titles[ $type ] : '';
	}
}

if ( ! function_exists( 'ywpar_get_option' ) ) {
	/**
	 * Get options from db
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Default value.
	 *
	 * @return mixed
	 * @since   1.0.0
	 */
	function ywpar_get_option( $option, $value = false ) {

		// new version.
		$db_value = get_option( 'ywpar_' . $option, $value );

		if ( false !== $db_value ) {
			$value = $db_value;
		} else {
			// get all options.
			$options = get_option( 'yit_ywpar_options', $value );
			if ( isset( $options[ $option ] ) ) {
				$value = $options[ $option ];
			}
		}

		return maybe_unserialize( $value );
	}
}


if ( ! function_exists( 'ywpar_get_currency' ) ) {
	/**
	 * Get currency
	 *
	 * @param string $currency Currency.
	 *
	 * @return string
	 * @since   3.0.0
	 */
	function ywpar_get_currency( $currency = '' ) {
		return empty( $currency ) ? apply_filters( 'ywpar_multi_currency_current_currency', get_woocommerce_currency() ) : $currency;
	}
}

if ( ! function_exists( 'ywpar_hide_points_for_guests' ) ) {
	/**
	 * Check if the points for guest are visible or not
	 *
	 * @return bool
	 * @since   3.0.0
	 */
	function ywpar_hide_points_for_guests() {
		/**
		 * APPLY_FILTERS: ywpar_hide_messages
		 *
		 * filter if to hide messages to the gustes.
		 */
		return apply_filters( 'ywpar_hide_messages', false ) || ( ywpar_get_option( 'hide_point_system_to_guest' ) === 'yes' && ! is_user_logged_in() );
	}
}

if ( ! function_exists( 'ywpar_check_date_interval' ) ) {
	/**
	 * Check the validate on an interval of date
	 *
	 * @param int $datefrom Start date.
	 * @param int $dateto End date.
	 *
	 * @return bool
	 * @since   3.0.0
	 */
	function ywpar_check_date_interval( $datefrom, $dateto ) {

		$now = time();

		if ( empty( $datefrom ) && empty( $dateto ) ) {
			return true;
		}

		$dateto = (int) $dateto + ( 24 * 60 * 60 ) - 1;

		if ( empty( $datefrom ) && '' !== $dateto && $now <= $dateto ) {
			return true;
		}

		if ( empty( $dateto ) && '' !== $datefrom && $now >= $datefrom ) {
			return true;
		}

		return ( ! empty( $datefrom ) && $now >= $datefrom ) && ( ! empty( $dateto ) && $now <= $dateto );
	}
}


if ( ! function_exists( 'ywpar_automatic_earning_points_enabled' ) ) {
	/**
	 * Check if the assigment points is automatic or manual
	 *
	 * @return bool
	 * @since   3.0.0
	 */
	function ywpar_automatic_earning_points_enabled() {
		/**
		 * APPLY_FILTERS: ywpar_enable_points_upon_sales
		 *
		 * enable or not the earning on Sale products.
		 */
		return apply_filters( 'ywpar_enable_points_upon_sales', ywpar_get_option( 'enable_points_upon_sales', 'yes' ) === 'yes' );
	}
}


if ( ! function_exists( 'ywpar_exclude_product_on_sale' ) ) {
	/**
	 * Check if the product on sale are excluded
	 *
	 * @param WC_Product $product Product.
	 * @return bool
	 * @since   3.0.0
	 */
	function ywpar_exclude_product_on_sale( $product ) {
		/**
		 * APPLY_FILTERS: ywpar_exclude_product_on_sale
		 *
		 * filter if current product is on sale or not.
		 *
		 * @param bool $is_sale
		 * @param WC_Product $product
		 */
		return apply_filters( 'ywpar_exclude_product_on_sale', 'yes' === ywpar_get_option( 'exclude_product_on_sale' ) && $product->is_on_sale(), $product );
	}
}


if ( ! function_exists( 'ywpar_replace_placeholder_on_product_message' ) ) {
	/**
	 * Return the message with the placeholder replaced.
	 *
	 * @param WC_Product $product Product.
	 * @param string     $message Message.
	 * @param int        $product_points Points.
	 * @param bool       $loop Loop.
	 *
	 * @return mixed
	 */
	function ywpar_replace_placeholder_on_product_message( $product, $message, $product_points, $loop = false ) {
		$singular   = ywpar_get_option( 'points_label_singular' );
		$plural     = ywpar_get_option( 'points_label_plural' );
		$class_name = $loop ? 'product_point_loop' : 'product_point';

		/* avoid this calculation if the related placeholder is not present in the message */
		if ( strpos( $message, '{price_discount_fixed_conversion}' ) !== false ) {
			$product_discount = ( 'fixed' === yith_points()->redeeming->get_conversion_method() ) ? yith_points()->redeeming->calculate_price_worth( $product, $product_points, false ) : '';
		} else {
			$product_discount = 0;
		}
		$product_points_formatted = apply_filters( 'ywpar_product_points_formatted', $product_points );
		// replace {points} placeholder.
		$message = str_replace( '{points}', '<span data-basepoints="' . $product_points . '" class="' . esc_attr( $class_name ) . '">' . $product_points_formatted . '</span>', $message );

		// replace {price_discount_fixed_conversion} placeholder.
		if ( strpos( $product_discount, '-' ) > 0 ) {
			$values = explode( '-', $product_discount );
			$product_discount_formatted = wc_price( $values[0] ) . '-' . wc_price( $values[1] );
		} else {
			$product_discount_formatted = wc_price( $product_discount );
		}
		$message = empty( $product_discount ) ? str_replace( '{price_discount_fixed_conversion}', '', $message ) : str_replace( '{price_discount_fixed_conversion}', '<span data-baseworthprice="' . $product_discount . '" class="product-point-conversion">' . $product_discount_formatted . '</span>', $message );

		// replace {points_label} placeholder.
		$points_label = apply_filters( 'ywpar_override_points_label', ( $product_points > 1 || strpos( $product_points, '-' ) > 0 ) ? $plural : $singular, $product_points, $plural, $singular );
		$message      = str_replace( '{points_label}', $points_label, $message );

		return $message;
	}
}


if ( ! function_exists( 'ywpar_get_date_formats' ) ) {
	/**
	 * Get Date formats
	 *
	 * @return  array
	 * @since   1.6.0
	 */
	function ywpar_get_date_formats() {

		return apply_filters(
			'ywpar_date_formats',
			array(
				'yy-mm-dd' => 'Y-m-d',
				'yy/mm/dd' => 'Y/m/d',
				'mm-dd-yy' => 'm-d-Y',
				'mm/dd/yy' => 'm/d/Y',
				'dd-mm-yy' => 'd-m-Y',
				'dd/mm/yy' => 'd/m/Y',
			)
		);

	}
}

if ( ! function_exists( 'ywpar_get_usable_comments' ) ) {
	/**
	 * Return the comments that can be use to assign extra points
	 *
	 * @param int    $user_id User id.
	 * @param string $starter_date Start date.
	 *
	 * @return array|int
	 */
	function ywpar_get_usable_comments( $user_id, $starter_date ) {

		$args = array(
			'status'    => 1,
			'user_id'   => $user_id,
			'post_type' => 'product',
			'number'    => '',
			'parent'    => 0 //needed to avoid to give points to replies
		);

		if ( $starter_date ) {
			$d                  = explode( '-', $starter_date );
			$args['date_query'] = array(
				array(
					'after'     => array(
						'year'  => $d[0],
						'month' => $d[1],
						'day'   => $d[2],
					),
					'inclusive' => true,
				),
			);
		}

		$usable_comments = get_comments( $args );

		return $usable_comments;

	}
}

if ( ! function_exists( 'ywpar_get_customer_order_count' ) ) {
	/**
	 * Calculate the amount of all order completed and processed of a user
	 *
	 * @param int    $user_id User id.
	 * @param string $starter_date Started date.
	 *
	 * @return float
	 * @since    1.1.3
	 */
	function ywpar_get_customer_order_count( $user_id, $starter_date ) {

		/**
		 * APPLY_FILTERS: ywpar_wc_get_orders_args
		 * 
		 * get orders args
		 * 
		 * @param array wc get orders args
		 */
		$orders = wc_get_orders(
			apply_filters(
				'ywpar_wc_get_orders_args',
				array(
					'customer'   => $user_id,
					'status'     => array( 'wc-completed', 'wc-processing' ),
					'limit'      => -1,
					'date_after' => $starter_date,
				)
			)
		);

		return count( $orders );

	}
}

if ( ! function_exists( 'ywpar_order_has_redeeming_coupon' ) ) {
	/**
	 * Check if the order has a redeeming coupon.
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return bool
	 * @since  3.0.0
	 */
	function ywpar_order_has_redeeming_coupon( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}
		$coupon_points = $order->get_meta( '_ywpar_coupon_points' );
		return ! empty( $coupon_points );
	}
}

if ( ! function_exists( 'ywpar_cart_has_redeeming_coupon' ) ) {
	/**
	 * Check if the cart has a redeeming coupon.
	 *
	 * @return bool|WC_Coupon
	 * @since  3.0.0
	 */
	function ywpar_cart_has_redeeming_coupon() {
		$has_redeeming_coupon = false;
		if ( WC()->cart ) {
			$coupons              = WC()->cart->get_applied_coupons();
			$has_redeeming_coupon = ywpar_check_redeeming_coupons( $coupons );
		}

		return $has_redeeming_coupon;
	}
}

if ( ! function_exists( 'ywpar_remove_redeeming_coupon' ) ) {
	/**
	 * Remove the reward coupons
	 *
	 * @return void
	 * @since  3.5.3
	 */
	function ywpar_remove_redeeming_coupon() {
		$has_redeeming_coupon = ywpar_cart_has_redeeming_coupon();
		if ( $has_redeeming_coupon ) {
			WC()->cart->remove_coupon( $has_redeeming_coupon->get_code );
		}
	}
}

if ( ! function_exists( 'ywpar_cart_has_shared_coupon' ) ) {
	/**
	 * Check if the cart has a shared coupon.
	 *
	 * @return bool|WC_Coupon
	 * @since  3.0.0
	 */
	function ywpar_cart_has_shared_coupon() {
		$has_shared_coupon = false;
		if ( WC()->cart ) {
			$coupons = WC()->cart->get_applied_coupons();
			foreach ( $coupons as $coupon ) {
				try {
					if ( ! $coupon instanceof WC_Coupon ) {
						$coupon = new WC_Coupon( $coupon );
					}

					if ( ! empty( $coupon->get_meta( 'ywpar_shared_coupon_customer' ) ) ) {
						$has_shared_coupon = true;
						break;
					}
				}catch( Exception $e ){
					continue;
				}
			}
		}

		return $has_shared_coupon;
	}
}

if ( ! function_exists( 'ywpar_check_redeeming_coupons' ) ) {
	/**
	 * Check if in the list there is a reward coupon.
	 *
	 * @param array $coupons Coupon list.
	 *
	 * @return bool|WC_Coupon
	 * @since 3.0.0
	 */
	function ywpar_check_redeeming_coupons( $coupons ) {
		$is_redeeming_coupon = false;
		if ( $coupons ) {
			foreach ( $coupons as $c ) {
				if ( ywpar_is_redeeming_coupon( $c ) ) {
					$is_redeeming_coupon = ( $c instanceof WC_Coupon ) ? $c : new WC_Coupon( $c );
				}
			}
		}
		return $is_redeeming_coupon;
	}
}

if ( ! function_exists( 'ywpar_is_redeeming_coupon' ) ) {
	/**
	 * Check if the Coupon is a redeeming coupon.
	 *
	 * @param string|WC_Coupon $coupon Coupon to check.
	 * @return bool
	 * @since  3.0.0
	 */
	function ywpar_is_redeeming_coupon( $coupon ) {
		try {
			if ( ! $coupon instanceof WC_Coupon ) {
				$coupon = new WC_Coupon( $coupon );
			}
			return ! empty( $coupon->get_meta( 'ywpar_coupon' ) );
		}catch ( Exception $e ){
			return false;
		}
	}
}

if ( ! function_exists( 'ywpar_coupon_is_valid' ) ) {
	/**
	 * Check if a coupon is valid
	 *
	 * @param WC_Coupon $coupon Coupon.
	 * @param array     $object Object.
	 *
	 * @return bool|WP_Error
	 * @throws Exception Throws an Exception.
	 */
	function ywpar_coupon_is_valid( $coupon, $object = array() ) {
		$wc_discounts = new WC_Discounts( $object );
		$valid        = $wc_discounts->is_coupon_valid( $coupon );
		return is_wp_error( $valid ) ? false : $valid;
	}
}


if ( ! function_exists( 'ywpar_get_customer' ) ) {
	/**
	 * Return the points and rewards customer
	 *
	 * @param mixed $customer Customer.
	 *
	 * @return YITH_WC_Points_Rewards_Customer
	 */
	function ywpar_get_customer( $customer ) {
		if ( ! $customer || is_null( $customer ) ) {
			return ywpar_get_current_customer();
		}

		if ( $customer instanceof YITH_WC_Points_Rewards_Customer ) {
			return $customer;
		}

		if ( $customer instanceof WC_Customer ) {
			$customer = $customer->get_id();
		}

		if ( $customer instanceof WP_User ) {
			$customer = $customer->ID;
		}

		return ( is_numeric( $customer ) && $customer > 0 ) ? YITH_WC_Points_Rewards_Helper::get_customer( $customer ) : false;
	}
}

if ( ! function_exists( 'ywpar_get_current_customer' ) ) {
	/**
	 * Return the current customer point
	 *
	 * @param WP_User|YITH_WC_Points_Rewards_Customer $user User.
	 *
	 * @return YITH_WC_Points_Rewards_Customer
	 * @since 3.0.0
	 */
	function ywpar_get_current_customer( $user = null ) {
		if ( $user instanceof YITH_WC_Points_Rewards_Customer ) {
			return $user;
		}
		return YITH_WC_Points_Rewards_Helper::get_current_point_customer( $user );
	}
}

if ( ! function_exists( 'ywpar_get_current_customer_id' ) ) {
	/**
	 * Return the current customer point id
	 *
	 * @param WP_User $user User.
	 *
	 * @return int
	 * @since 3.0.0
	 */
	function ywpar_get_current_customer_id( $user = null ) {
		$customer = ywpar_get_current_customer( $user );
		return $customer ? $customer->get_id() : 0;
	}
}

if ( ! function_exists( 'ywpar_get_blog_suffix' ) ) {
	/**
	 * Return the suffix for current blog.
	 *
	 * @return string
	 * @since 3.0.0
	 */
	function ywpar_get_blog_suffix( $user = null ) {
		$blog_id = get_current_blog_id();
		$suffix  = ( 1 === $blog_id ) ? '' : '_' . $blog_id;
		/**
		 * APPLY_FILTERS: ywpar_blog_suffix
		 *
		 * filter the blog suffix.
		 */
		return apply_filters( 'ywpar_blog_suffix', $suffix );
	}
}

if ( ! function_exists( 'ywpar_get_user_role' ) ) {
	/**
	 * Return the user role
	 *
	 * @param WP_User $user User.
	 *
	 * @return string|array
	 * @since   3.0.0
	 */
	function ywpar_get_user_role( $user = null ) {
		$customer = ywpar_get_current_customer( $user );
		return $customer ? $customer->get_roles() : 'guest';
	}
}


if ( ! function_exists( 'yith_ywpar_calculate_user_total_orders_amount' ) ) {
	/**
	 * Calculate the amount of all order completed and processed of a user
	 *
	 * @param int|string $user_id User id can be also the email for guests.
	 * @param int        $order_id Order id.
	 * @param string     $starter_date Starter date.
	 *
	 * @return float
	 * @since 1.1.3
	 */
	function yith_ywpar_calculate_user_total_orders_amount( $user_id, $order_id = 0, $starter_date = '' ) {
		$total_amount = 0;
		$last_order   = wc_get_order( $order_id );
		/**
		* APPLY_FILTERS: ywpar_calculate_user_total_orders_amount_get_orders_query
		*
		* Filter the query for getting ordes during user total orders amount.
		* 
		* @param string $user_id the  user id
		* @param string $starter_date date in format YYYY-MM-DD
		*
		* @return array
		* 
		*/
		$orders       = wc_get_orders(
			apply_filters(
				'ywpar_calculate_user_total_orders_amount_get_orders_query',
				array(
					'customer'   => $user_id,
					'status'     => array( 'wc-completed', 'wc-processing' ),
					'date_after' => $starter_date,
				),
				$user_id,
				$starter_date
			)
		);

		if ( $last_order ) {
			/**
			 * APPLY_FILTERS: ywpar_calculate_user_total_orders_amount_last_order
			 * 
			 * last order subtotal
			 * 
			 * @param float $last_order_subtotal last order subtotal
			 * @param WC_Order $last_order woocommerce order
			 */
			$total_amount += apply_filters( 'ywpar_calculate_user_total_orders_amount_last_order', $last_order->get_subtotal(), $last_order );
		}

		if ( 'yes' === ywpar_get_option( 'assign_points_to_registered_guest' ) ) {
			$customer       = new WC_Customer( $user_id );
			$billing_email  = $customer->get_billing_email();
			$query_customer = empty( $billing_email ) ? $customer->get_id() : $billing_email;
			$orders_guest   = wc_get_orders(
				apply_filters(
					'ywpar_calculate_user_total_orders_amount_get_orders_query',
					array(
						'customer'   => $query_customer,
						'status'     => array( 'wc-completed', 'wc-processing' ),
						'date_after' => $starter_date,
					),
					$query_customer,
					$starter_date
				)
			);

			$orders = array_merge( $orders, $orders_guest );
			$orders = array_unique( $orders );
		}

		if ( $orders ) {
			foreach ( $orders as $order ) {
				if ( $order_id && (int) $order_id === $order->get_id() ) {
					continue;
				}

				$total_amount += apply_filters( 'ywpar_calculate_user_total_orders_amount_order', $order->get_subtotal(), $order );
			}
		}

		return $total_amount;
	}
}



if ( ! function_exists( 'ywpar_get_product_price' ) ) {
	/**
	 * Return the price based on tax settings on redeeming points
	 *
	 * @param WC_Product $product Product.
	 * @param string     $action This action can be earn or redeeem.
	 * @param string     $currency Currency.
	 * @param int        $qty Quantity.
	 * @param float      $price Price.
	 *
	 * @return float|string
	 */
	function ywpar_get_product_price( $product, $action = 'earn', $currency = '', $qty = 1, $price = '' ) {

		if ( '' === $price && $product instanceof WC_Product ) {
			$price = $product->get_price();
		}

		if ( 'earn' === $action ) {
			$currency      = ywpar_get_currency( $currency );
			$tax_mode      = ywpar_get_option( 'earn_prices_tax', get_option( 'woocommerce_tax_display_shop', 'incl' ) );
			$display_price = 'incl' === $tax_mode ? yit_get_price_including_tax( $product, $qty, $price ) : yit_get_price_excluding_tax( $product, $qty, $price );
			$display_price = apply_filters( 'ywpar_get_point_earned_price', (float) $display_price, $currency, $product, 'product' ); // retro-compatibility.
		} else {
			$tax_mode      = apply_filters( 'ywpar_get_price_tax_on_points', ywpar_get_option( 'redeem_prices_tax', get_option( 'woocommerce_tax_display_shop', 'incl' ) ) );
			$display_price = 'incl' === $tax_mode ? yit_get_price_including_tax( $product, $qty, $price ) : yit_get_price_excluding_tax( $product, $qty, $price );
		}
		/**
		 * APPLY_FILTERS: ywpar_get_product_price
		 * 
		 * return the price of product depending on the options about tax in reedeming settings
		 * 
		 * @param string $display_price price to display.
		 * @param string $action default 'earn'.
		 * @param string $currency currency.
		 * @param int    $qty quantity.
		 * @param string $price product price.
		 * @param WC_Product $product product object
		 */
		return apply_filters( 'ywpar_get_product_price', $display_price, $action, $currency, $qty, $price, $product );
	}
}

if ( ! function_exists( 'ywpar_options_porting' ) ) {
	/**
	 * Option porting
	 *
	 * @param array $old_options Old options.
	 */
	function ywpar_options_porting( $old_options ) {
		$earn_points_for_role    = array();
		$rewards_points_for_role = array();
		$reward_method           = isset( $old_options['conversion_rate_method'] ) ? $old_options['conversion_rate_method'] : 'fixed';
		foreach ( $old_options as $key => $value ) {
			if ( strpos( $key, 'earn_points_role_' ) !== false ) {
				$new_value         = $value;
				$new_value['role'] = str_replace( 'earn_points_role_', '', $key );

				$earn_points_for_role['role_conversion'][] = $new_value;
				continue;
			}
			if ( strpos( $key, 'rewards_points_role_' ) !== false ) {
				$new_value                                    = $value;
				$new_value['role']                            = str_replace( 'rewards_points_role_', '', $key );
				$rewards_points_for_role['role_conversion'][] = $new_value;
				continue;
			}
			if ( 'extra_points' === $key ) {
				$ywpar_amount_spent_exp       = array();
				$ywpar_review_exp             = array();
				$ywpar_num_order_exp          = array();
				$ywpar_number_of_points_exp   = array();
				$ywpar_points_on_registration = '';
				if ( $value ) {
					foreach ( $value as $extrp ) {
						if ( isset( $extrp['option'] ) ) {
							switch ( $extrp['option'] ) {
								case 'reviews':
									$ywpar_review_exp['list'][] = array(
										'number' => $extrp['value'],
										'points' => $extrp['points'],
										'repeat' => isset( $extrp['repeat'] ) ? $extrp['repeat'] : 0,
									);
									break;
								case 'num_of_orders':
									$ywpar_num_order_exp['list'][] = array(
										'number' => $extrp['value'],
										'points' => $extrp['points'],
										'repeat' => isset( $extrp['repeat'] ) ? $extrp['repeat'] : 0,
									);
									break;
								case 'reviews':
									break;
								case 'amount_spent':
									$ywpar_amount_spent_exp['list'][] = array(
										'number' => $extrp['value'],
										'points' => $extrp['points'],
										'repeat' => isset( $extrp['repeat'] ) ? $extrp['repeat'] : 0,
									);
									break;
								case 'points':
									$ywpar_number_of_points_exp['list'][] = array(
										'number' => $extrp['value'],
										'points' => $extrp['points'],
										'repeat' => isset( $extrp['repeat'] ) ? $extrp['repeat'] : 0,
									);
									break;
								case 'registration':
									$ywpar_points_on_registration = $extrp['points'];
									break;
							}
						}
					}

					if ( $ywpar_amount_spent_exp ) {
						update_option( 'ywpar_enable_amount_spent_exp', 'yes' );
						update_option( 'ywpar_amount_spent_exp', $ywpar_amount_spent_exp );
					}

					if ( $ywpar_review_exp ) {
						update_option( 'ywpar_enable_review_exp', 'yes' );
						update_option( 'ywpar_review_exp', $ywpar_review_exp );
					}

					if ( $ywpar_num_order_exp ) {
						update_option( 'ywpar_enable_num_order_exp', 'yes' );
						update_option( 'ywpar_num_order_exp', $ywpar_num_order_exp );
					}

					if ( $ywpar_number_of_points_exp ) {
						update_option( 'ywpar_enable_number_of_points_exp', 'yes' );
						update_option( 'ywpar_number_of_points_exp', $ywpar_number_of_points_exp );
					}

					if ( ! empty( $ywpar_points_on_registration ) ) {
						update_option( 'ywpar_enable_points_on_registration_exp', 'yes' );
						update_option( 'ywpar_points_on_registration', $ywpar_points_on_registration );
					}
				}

				continue;
			}

			$key   = 'ywpar_' . $key;
			$key   = apply_filters( 'ywpar_porting_options_key', $key, $value );
			$value = apply_filters( 'ywpar_porting_options_value', $value, $key );

			update_option( $key, $value );
		}

		if ( $earn_points_for_role ) {
			update_option( 'ywpar_earn_points_role_conversion_rate', $earn_points_for_role );
		}

		if ( $rewards_points_for_role ) {
			$key = 'fixed' === $reward_method ? 'ywpar_rewards_points_role_rewards_fixed_conversion_rate' : 'ywpar_rewards_points_role_rewards_percentage_conversion_rate';
			update_option( $key, $rewards_points_for_role );
		}

	}
}

if ( ! function_exists( 'ywpar_conversion_points_multilingual' ) ) {
	/**
	 * Convert rules for multi currency
	 */
	function ywpar_conversion_points_multilingual() {
		$old_conversion = get_option( 'yit_ywpar_multicurrency', false );
		if ( ! $old_conversion ) {
			$default_currency = get_woocommerce_currency();
			$roles            = yith_ywpar_get_roles();

			$options = array(
				'earn_points_conversion_rate',
				'rewards_conversion_rate',
				'rewards_percentual_conversion_rate',
			);

			foreach ( $options as $option_name ) {
				$conversion_role     = ywpar_get_option( $option_name );
				$new_conversion_role = get_conversion_rate_with_default_currency( $conversion_role, $default_currency );
				update_option( 'ywpar_' . $option_name, $new_conversion_role );
			}

			$options_by_roles = array( 'earn_points_role_', 'rewards_points_role_', 'rewards_points_percentual_role_' );
			foreach ( $options_by_roles as $option_name ) {
				foreach ( $roles as $role ) {
					$conversion_role     = ywpar_get_option( $option_name . $role );
					$new_conversion_role = get_conversion_rate_with_default_currency( $conversion_role, $default_currency );
					update_option( 'ywpar_' . $option_name . $role, $new_conversion_role );
				}
			}

			update_option( 'yit_ywpar_multicurrency', true );
		}
	}
}

if ( ! function_exists( 'get_conversion_rate_with_default_currency' ) ) {
	/**
	 * Return the conversion rate with the default currency
	 *
	 * @param array  $options Options.
	 * @param string $currency Currency.
	 *
	 * @return array
	 */
	function get_conversion_rate_with_default_currency( $options, $currency ) {
		$new_option = array();
		if ( isset( $options['points'] ) ) {
			$new_option[ $currency ] = $options;
		} else {
			$new_option = $options;
		}

		return $new_option;
	}
}


if ( ! function_exists( 'ywpar_add_order_points_summary' ) ) {
	/**
	 * Add a summary of points earned and redeemed inside the order.
	 *
	 * @param WC_Order|int $order Order.
	 */
	function ywpar_add_order_points_summary( $order ) {
		if ( is_numeric( $order ) ) {
			$order = wc_get_order( $order );
		}

		if ( ! $order instanceof WC_Order || ! $order->get_customer_id() ) {
			return;
		}

		$user = ywpar_get_customer( $order->get_customer_id() );

		if ( ! $user->is_enabled() ) {
			return;
		}


		$message        = '';
		$point_earned   = $order->get_meta( '_ywpar_points_earned' );
		$point_redeemed = $order->get_meta( '_ywpar_redemped_points' );
		$plural         = ywpar_get_option( 'points_label_plural' );
		$ywpar_points_from_cart = $order->get_meta( 'ywpar_points_from_cart' );
		if ( $point_earned ) {
			$message = sprintf( '<strong>%s %s</strong> <span>%d</span>', esc_html( $plural ), esc_html( __( 'earned:', 'yith-woocommerce-points-and-rewards' ) ), esc_html( $point_earned ) );
		} elseif( $ywpar_points_from_cart ) {
			$message = sprintf( '<strong>%s %s</strong> <span>%d</span>', esc_html( $plural ), esc_html( __( 'earned:', 'yith-woocommerce-points-and-rewards' ) ), esc_html( $ywpar_points_from_cart ) );
		}
		if ( $point_redeemed ) {
			$message .= $message ? '<br>' : '';
			$message .= sprintf( '<strong>%s %s</strong> <span>%d</span>', esc_html( $plural ), esc_html( __( 'used:', 'yith-woocommerce-points-and-rewards' ) ), esc_html( $point_redeemed ) );
		}

		/**
		 * APPLY_FILTERS: ywpar_add_order_points_summary
		 *
		 * filter the points order note message.
		 *
		 * @param string $message
		 * @param WC_Order $order
		 * @param int $points_earned
		 * @param int $points_redeemed
		 */
		echo apply_filters( 'ywpar_add_order_points_summary', $message ? '<p class="ywpar-order-point-summary">' . $message . '</p>' : '', $order, $point_earned, $point_redeemed ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}



if ( ! function_exists( 'ywpar_is_elementor_editor' ) ) {
	/**
	 * Check if is an elementor editor
	 *
	 * @return bool
	 */
	function ywpar_is_elementor_editor() {
		if ( did_action( 'admin_action_elementor' ) ) {
			return Plugin::$instance->editor->is_edit_mode();
		}

		return is_admin() && isset( $_REQUEST['action'] ) && in_array( sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ), array( 'elementor', 'elementor_ajax' ) ); //phpcs:ignore
	}
}