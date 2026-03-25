<?php
/**
 * Class to manage the plugins post types.
 *
 * @class   YITH_WC_Points_Rewards_Helper
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value, WordPress.DB.SlowDBQuery.slow_db_query_meta_query
if ( ! class_exists( 'YITH_WC_Points_Rewards_Helper' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Helper
	 */
	class YITH_WC_Points_Rewards_Helper {

		/**
		 * Validated rules.
		 *
		 * @var array
		 */
		protected static $validated_rules = array();

		/**
		 * Validated rules.
		 *
		 * @var array
		 */
		protected static $validated_redeeming_rules = array();

		/**
		 * Validated earning rules for products.
		 *
		 * @var array
		 */
		protected static $validated_product_rules = array();

		/**
		 * Validated redeeming rules for products
		 *
		 * @var array
		 */
		protected static $validated_redeeming_product_rules = array();

		/**
		 * Validated rules.
		 *
		 * @var array
		 */
		protected static $customers = array();

		/**
		 * Levels.
		 *
		 * @var array
		 */
		protected static $levels = array();

		/**
		 * Banners.
		 *
		 * @var array
		 */
		protected static $banners = array();


		/**
		 * Banners.
		 *
		 * @var array
		 */
		protected static $rank_list = array();

		/**
		 * Hook in methods.
		 */
		public static function init() {

		}

		/**
		 * Sort the post by priority meta.
		 *
		 * @param array $sorted Ids list.
		 */
		public static function sort_posts( $sorted ) {
			if ( $sorted ) {
				$old_priority = array();
				foreach ( $sorted as $item ) {
					$old_priority[] = get_post_meta( $item, '_priority', 1 );
				}
				$priority = min( $old_priority );

				foreach ( $sorted as $item ) {
					update_post_meta( $item, '_priority', $priority ++ );
				}
			}
		}

		/**
		 * Get all levels as array
		 *
		 * @param string $status Status.
		 *
		 * @return array
		 * @since   2.2.0
		 **/
		public static function get_levels_badges( $status = 'any' ) {

			if ( isset( self::$levels[ $status ] ) ) {
				return self::$levels[ $status ];
			}
			global $sitepress;
			
			$uixbuilder = ! empty( $_GET['app'] ) && 'uxbuilder' === $_GET['app'] || !empty( $_GET['uxb_iframe'] );

			$args = array(
				'numberposts'      => - 1,
				'post_type'        => YITH_WC_Points_Rewards_Post_Types::$level_badge,
				'post_status'      => 'publish',
				'suppress_filters' => is_null( $sitepress ) || $uixbuilder ? true : false,
			);


			if ( 'any' !== $status ) {
				$args['meta_key']   = '_status'; //phpcs:ignore
				$args['meta_value'] = $status; //phpcs:ignore
			}

			/**
			 * APPLY_FILTERS: ywpar_get_levels_badges_query
			 *
			 * filter the get levels query.
			 *
			 * @param array $args .
			 * @param string $status .
			 */
			$levels_posts = get_posts( apply_filters( 'ywpar_get_levels_badges_query', $args, $status ) );
			$levels       = array();

			foreach ( $levels_posts as $lvl ) {
				$levels[ $lvl->ID ] = ywpar_get_level_badge( $lvl->ID );
			}
			self::$levels[ $status ] = $levels;

			return $levels;
		}


		/**
		 * Search if exists an Earning rule with specific arguments
		 *
		 * @param array $args Arguments.
		 */
		public static function search_earning_rule( $args ) {

			remove_action( 'pre_get_posts', array( 'YITH_WC_Points_Rewards_Post_Types', 'order_by_priority' ), 10 );
			$rule_exists = false;
			$query_args  = array(
				'post_type'   => YITH_WC_Points_Rewards_Post_Types::$earning_rule,
				'numberposts' => - 1,
				'fields'      => 'ids',
				'post_status' => 'publish',
			);

			if ( isset( $args['apply_to'] ) ) {
				$query_args['meta_key']   = '_apply_to';
				$query_args['meta_value'] = $args['apply_to'];
			}

			$earning_rules = get_posts( $query_args );

			if ( $earning_rules ) {
				foreach ( $earning_rules as $rule_id ) {
					$rule      = ywpar_get_earning_rule( $rule_id );
					$rule_type = $rule->get_points_type_conversion();

					if ( $rule_type !== $args['type'] ) {
						continue;
					}

					if ( 'not_assign' !== $rule_type ) {
						if ( 'override' === $rule_type ) {
							$cvr = $rule->get_earn_points_conversion_rate();
							if ( wp_json_encode( $cvr ) !== wp_json_encode( $args['earn_points_conversion_rate'] ) ) {
								continue;
							}
						} else {
							$rule_value = 'fixed' === $rule_type ? $rule->get_fixed_points_to_earn() : $rule->get_percentage_points_to_earn();
							if ( $rule_value != $args['point_earned'] ) { //phpcs:ignore
								continue;
							}
						}
					}

					if ( $args['is_scheduled'] !== $rule->get_is_rule_scheduled() ) {
						continue;
					}

					if ( 'yes' === $rule->get_is_rule_scheduled() ) {
						$rule_schedule = $rule->get_rule_schedule();
						if ( (int) $args['schedule_from'] !== strtotime( $rule_schedule['from'] ) || (int) $args['schedule_to'] !== strtotime( $rule_schedule['to'] ) ) { //phpcs:ignore
							continue;
						}
					}
					$rule_exists = $rule;
					break;
				}
			}

			return $rule_exists;
		}

		/**
		 * Search if the reedeeming rule exists
		 *
		 * @param array $args Arguments.
		 */
		public static function search_redeeming_rule( $args ) {

			remove_action( 'pre_get_posts', array( 'YITH_WC_Points_Rewards_Post_Types', 'order_by_priority' ), 10 );

			$rule_exists = false;

			$query_args = array(
				'post_type'   => YITH_WC_Points_Rewards_Post_Types::$redeeming_rule,
				'numberposts' => - 1,
				'fields'      => 'ids',
				'post_status' => 'publish',
			);

			$redeeming_rules = get_posts( $query_args );
			if ( $redeeming_rules ) {
				foreach ( $redeeming_rules as $rule_id ) {
					$rule      = ywpar_get_redeeming_rule( $rule_id );
					$rule_type = $rule->get_type();

					if ( 'conversion_rate' === $rule_type && isset( $args['conversion_rate'] ) ) {
						if ( ywpar_get_option( 'conversion_rate_method' ) === 'fixed' ) {
							$cvr = $rule->get_conversion_rate();
						} else {
							$cvr = $rule->get_percentage_conversion_rate();
						}

						if ( wp_json_encode( $cvr ) !== wp_json_encode( $args['conversion_rate'] ) ) {
							continue;
						}
					} elseif ( isset( $args['max_discount_type'], $args['max_discount'] ) ) {
						if ( $args['max_discount_type'] !== $rule->get_maximum_discount_type() ) {
							continue;
						}

						if ( 'fixed' === $rule->get_maximum_discount_type() ) {
							if ( $args['max_discount'] !== $rule->get_max_discount() ) {
								continue;
							}
						} else {
							if ( $args['max_discount'] !== $rule->get_max_discount_percentage() ) {
								continue;
							}
						}
					}

					$rule_exists = $rule;
					break;

				}
			}

			return $rule_exists;
		}


		/**
		 * Return the list of earning rules
		 *
		 * @param WP_User $user User.
		 *
		 * @return array
		 */
		public static function get_valid_earning_rules( $user = null ) {

			$customer_id = ywpar_get_current_customer_id( $user );

			if ( isset( self::$validated_rules[ $customer_id ] ) ) {
				return self::$validated_rules[ $customer_id ];
			}

			$args = array(
				'numberposts' => - 1,
				'post_type'   => YITH_WC_Points_Rewards_Post_Types::$earning_rule,
				'post_status' => 'publish',
				'meta_key'    => '_priority',
				'fields'      => 'ids',
				'orderby'     => 'meta_value_num',
				'order'       => 'DESC',
				'meta_query'  => array(
					array(
						'key'     => '_status',
						'value'   => 'on',
						'compare' => '=',
					),
				),
			);

			$earning_rules = get_posts( $args );
			$valid_rules   = array();
			if ( $earning_rules ) {
				foreach ( $earning_rules as $rule ) {
					$earning_rule = ywpar_get_earning_rule( $rule );

					if ( $earning_rule->is_valid_now() && $earning_rule->is_valid_for_user( $customer_id ) ) {
						array_push( $valid_rules, $earning_rule );
					}
				}
			}

			self::$validated_rules[ $customer_id ] = $valid_rules;

			return $valid_rules;

		}

		/**
		 * Return the list of earning rules
		 *
		 * @param WP_User $user User.
		 *
		 * @return array
		 */
		public static function get_valid_redeeming_rules( $user = null ) {

			$customer_id = ywpar_get_current_customer_id( $user );

			if ( isset( self::$validated_redeeming_rules[ $customer_id ] ) ) {
				return self::$validated_redeeming_rules[ $customer_id ];
			}

			$args = array(
				'numberposts' => - 1,
				'post_type'   => YITH_WC_Points_Rewards_Post_Types::$redeeming_rule,
				'post_status' => 'publish',
				'meta_key'    => '_priority',
				'fields'      => 'ids',
				'orderby'     => 'meta_value_num',
				'order'       => 'DESC',
				'meta_query'  => array(
					array(
						'key'     => '_status',
						'value'   => 'on',
						'compare' => '=',
					),
				),
			);

			$redeeming_rules = get_posts( $args );

			$valid_rules = array();
			if ( $redeeming_rules ) {
				foreach ( $redeeming_rules as $rule ) {
					$redeeming_rule = ywpar_get_redeeming_rule( $rule );
					if ( $redeeming_rule->is_valid_for_user( $customer_id ) ) {
						array_push( $valid_rules, $redeeming_rule );
					}
				}
			}

			self::$validated_redeeming_rules[ $customer_id ] = $valid_rules;

			return $valid_rules;

		}

		/**
		 * Return the current customer point
		 *
		 * @param WP_User $user User.
		 *
		 * @return YITH_WC_Points_Rewards_Customer|bool
		 * @since 2.2.0
		 */
		public static function get_current_point_customer( $user = null ) {
			if ( is_null( $user ) && ! is_user_logged_in() ) {
				return false;
			}

			$user = ( ! $user || is_null( $user ) ) ? wp_get_current_user() : $user;

			return ywpar_get_customer( $user );
		}

		/**
		 * Return the current customer point
		 *
		 * @param WP_User $user_id User.
		 *
		 * @return YITH_WC_Points_Rewards_Customer|bool
		 * @since 2.2.0
		 */
		public static function get_customer( $user_id ) {

			if ( ! empty( self::$customers[ $user_id ] ) ) {
				return self::$customers[ $user_id ];
			}

			$customer                    = new YITH_WC_Points_Rewards_Customer( $user_id );
			self::$customers[ $user_id ] = $customer;

			return $customer;
		}

		/**
		 * Return the list of earning rules
		 *
		 * @param WC_Product $product Product.
		 * @param WP_User    $user User.
		 *
		 * @return array|YITH_WC_Points_Rewards_Earning_Rule
		 */
		public static function get_earning_rules_valid_for_product( $product, $user = null ) {

			$customer_id = ywpar_get_current_customer_id( $user );

			if ( isset( self::$validated_product_rules[ $product->get_id() ][ $customer_id ] ) ) {
				return self::$validated_product_rules[ $product->get_id() ][ $customer_id ];
			}

			$valid_rules = self::get_valid_earning_rules( $user );

			$product_rules = array();
			if ( ! $valid_rules ) {
				return $product_rules;
			}

			foreach ( $valid_rules as $valid_rule ) {
				$rule = ywpar_get_earning_rule( $valid_rule );

				if ( $rule->is_valid_for_product( $product->get_id() ) ) {
					array_push( $product_rules, $rule );
				}
			}
			self::$validated_product_rules[ $product->get_id() ][ $customer_id ] = $product_rules;

			return $product_rules;

		}

		/**
		 * Return the list of redeeming rules
		 *
		 * @param WC_Product                              $product Product.
		 * @param WP_User|YITH_WC_Points_Rewards_Customer $user User.
		 *
		 * @return array
		 */
		public static function get_redeeming_rules_valid_for_product( $product, $user = null ) {

			$customer_id = ywpar_get_current_customer_id( $user );

			if ( isset( self::$validated_redeeming_product_rules[ $product->get_id() ][ $customer_id ] ) ) {
				return self::$validated_redeeming_product_rules[ $product->get_id() ][ $customer_id ];
			}

			$valid_rules = self::get_valid_redeeming_rules( $user );

			$product_rules = array();
			if ( ! $valid_rules ) {
				return $product_rules;
			}

			foreach ( $valid_rules as $valid_rule ) {
				$rule = ywpar_get_redeeming_rule( $valid_rule );

				if ( $rule->is_valid_for_product( $product->get_id() ) ) {
					array_push( $product_rules, $rule );
				}
			}
			self::$validated_redeeming_product_rules[ $product->get_id() ][ $customer_id ] = $product_rules;

			return $product_rules;

		}

		/**
		 * Get all banners by type
		 *
		 * @param string $type Type of banners ( target | get_points | simple ).
		 *
		 * @return array
		 * @since  2.2.0
		 **/
		public static function get_banners( $type = 'all' ) {

			if ( isset( self::$banners[ $type ] ) ) {
				return self::$banners[ $type ];
			}

			$banners_query_args = array(
				'numberposts' => - 1,
				'post_type'   => YITH_WC_Points_Rewards_Post_Types::$banner,
				'post_status' => 'publish',
				'meta_query'  => '',
				'orderby'     => 'meta_value_num',
				'meta_key'    => '_priority',
				'order'       => 'ASC',
			);

			$banners_query_args['meta_query'] = array(
				array(
					'key'   => '_status',
					'value' => 'on',
				),
			);

			/**
			 * APPLY_FILTERS: ywpar_get_banners_query
			 *
			 * filter the get banners query.
			 *
			 * @param array $args .
			 */
			$banners_posts = get_posts( apply_filters( 'ywpar_get_banners_query', $banners_query_args ) );
			$banners       = array();

			foreach ( $banners_posts as $banner ) {
				$banner_id = $banner->ID;
				if ( function_exists( 'wpml_get_language_information' ) && defined( 'ICL_LANGUAGE_CODE' ) && ICL_LANGUAGE_CODE !== wpml_get_language_information( '', $banner_id )['language_code'] ) {
					continue;
				}

				$banner      = ywpar_get_banner( $banner_id );
				$banner_type = $banner->get_type();
				if ( $type === $banner_type || ( 'simple' === $banner_type && $type === $banner->get_simple_position() ) ) {
					$banners[ $banner_id ] = $banner;
				}
			}

			self::$banners[ $type ] = $banners;

			return $banners;
		}


		/**
		 * Return the rank list user ids ordered from the highest
		 *
		 * @return array
		 */
		public static function get_rank_list() {
			if ( ! empty( self::$rank_list ) ) {
				return self::$rank_list;
			}

			$args = array(
				'number'     => - 1,
				'order'      => 'DESC',
				'orderby'    => 'meta_value_num',
				'fields'     => 'ids',
				'meta_query' => array(
					'relation' => 'OR',
					array(
						'key'     => '_ywpar_points_collected' . ywpar_get_blog_suffix(),
						'compare' => 'EXISTS',
					),
				)
			);

			$result          = new WP_User_Query( $args );
			self::$rank_list = $result->get_results();

			return self::$rank_list;
		}

	}
}
