<?php
/**
 * Class to manage the plugins post types.
 *
 * @class   YITH_WC_Points_Rewards_Post_Types
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_WC_Points_Rewards_Post_Types' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Post_Types
	 */
	class YITH_WC_Points_Rewards_Post_Types {

		/**
		 * Level Badges Post Type
		 *
		 * @var string
		 * @static
		 */
		public static $level_badge = 'ywpar-level-badge';


		/**
		 * Banner Post Type
		 *
		 * @var string
		 * @static
		 */
		public static $banner = 'ywpar-banner';

		/**
		 * Earning Rule Post Type
		 *
		 * @var string
		 * @static
		 */
		public static $earning_rule = 'ywpar-earning-rule';

		/**
		 * Redeem Rule Post Type
		 *
		 * @var string
		 * @static
		 */
		public static $redeeming_rule = 'ywpar-redeeming-rule';

		/**
		 * Hook in methods.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_post_types' ), 5 );
			add_action( 'admin_init', array( __CLASS__, 'add_capabilities' ) );
			add_action( 'pre_get_posts', array( __CLASS__, 'order_by_priority' ), 10 );
		}

		/**
		 * Order by priority the post type that have the priority meta.
		 *
		 * @param WP_Query $query Query.
		 */
		public static function order_by_priority( $query ) {
			if ( isset( $query->query, $query->query['post_type'] ) &&
				( in_array(
					$query->query['post_type'],
					array(
						self::$banner,
						self::$earning_rule,
						self::$redeeming_rule,
					),
					true
				) ) ) {
				$query->set( 'orderby', 'meta_value_num' );
				$query->set( 'meta_key', '_priority' );
				$query->set( 'order', 'ASC' );
			}

		}

		/**
		 * Register core post types.
		 */
		public static function register_post_types() {

			if ( post_type_exists( self::$level_badge ) ) {
				return;
			}

			/**
			 * DO_ACTION: ywpar_before_register_post_type
			 *
			 * hook before custom post type registration.
			 */
			do_action( 'ywpar_before_register_post_type' );

			/*  LEVELS & BADGE  */

			$labels_level_badge = array(
				'name'               => esc_html_x(
					'Levels & Badges',
					'Post Type General Name',
					'yith-woocommerce-points-and-rewards'
				),
				'singular_name'      => esc_html_x(
					'Levels & Badges',
					'Post Type Singular Name',
					'yith-woocommerce-points-and-rewards'
				),
				'add_new_item'       => esc_html__( 'Add new level', 'yith-woocommerce-points-and-rewards' ),
				'add_new'            => esc_html__( '+ Add new level', 'yith-woocommerce-points-and-rewards' ),
				'new_item'           => esc_html__( 'Add new level', 'yith-woocommerce-points-and-rewards' ),
				'edit_item'          => esc_html__( 'Edit level', 'yith-woocommerce-points-and-rewards' ),
				'view_item'          => esc_html__( 'View level', 'yith-woocommerce-points-and-rewards' ),
				'search_items'       => esc_html__( 'Search level', 'yith-woocommerce-points-and-rewards' ),
				'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-points-and-rewards' ),
				'not_found_in_trash' => esc_html__( 'Not found in Trash', 'yith-woocommerce-points-and-rewards' ),
			);

			$level_badge_post_type_args = array(
				'labels'              => $labels_level_badge,
				'supports'            => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'menu_position'       => 10,
				'capability_type'     => self::$level_badge,
				'capabilities'        => self::get_capabilities( self::$level_badge ),
				'show_in_nav_menus'   => false,
				'has_archive'         => true,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'publicly_queryable'  => false,
				'query_var'           => false,
			);

			register_post_type(
				self::$level_badge,
				/**
				 * APPLY_FILTERS: ywpar_register_post_type_level_badge
				 *
				 * filter the register post type args.
				 *
				 * @param array $args .
				 */
				apply_filters( 'ywpar_register_post_type_level_badge', $level_badge_post_type_args )
			);

			/*  BANNERS  */

			$labels_banner = array(
				'name'               => esc_html_x(
					'Banners',
					'Post Type General Name',
					'yith-woocommerce-points-and-rewards'
				),
				'singular_name'      => esc_html_x(
					'Banner',
					'Post Type Singular Name',
					'yith-woocommerce-points-and-rewards'
				),
				'add_new_item'       => esc_html__( 'Add banner', 'yith-woocommerce-points-and-rewards' ),
				'add_new'            => esc_html__( '+ Add banner', 'yith-woocommerce-points-and-rewards' ),
				'new_item'           => esc_html__( 'Add new banner', 'yith-woocommerce-points-and-rewards' ),
				'edit_item'          => esc_html__( 'Edit banner', 'yith-woocommerce-points-and-rewards' ),
				'view_item'          => esc_html__( 'View banner', 'yith-woocommerce-points-and-rewards' ),
				'search_items'       => esc_html__( 'Search banner', 'yith-woocommerce-points-and-rewards' ),
				'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-points-and-rewards' ),
				'not_found_in_trash' => esc_html__( 'Not found in Trash', 'yith-woocommerce-points-and-rewards' ),
			);

			$banner_post_type_args = array(
				'labels'              => $labels_banner,
				'supports'            => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'menu_position'       => 10,
				'capability_type'     => self::$banner,
				'capabilities'        => self::get_capabilities( self::$banner ),
				'show_in_nav_menus'   => false,
				'has_archive'         => true,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'publicly_queryable'  => false,
				'query_var'           => false,
			);

			register_post_type(
				self::$banner,
				/**
				 * APPLY_FILTERS: ywpar_register_post_type_banner
				 *
				 * filter the register post type args.
				 *
				 * @param array $args .
				 */
				apply_filters( 'ywpar_register_post_type_banner', $banner_post_type_args )
			);

			/*  EARNING RULES  */

			$labels_earning_rule = array(
				'name'               => esc_html_x(
					'Points Assignment Rules',
					'Post Type General Name',
					'yith-woocommerce-points-and-rewards'
				),
				'singular_name'      => esc_html_x(
					'Points Assignment Rule',
					'Post Type Singular Name',
					'yith-woocommerce-points-and-rewards'
				),
				'add_new_item'       => esc_html__( 'Add new rule', 'yith-woocommerce-points-and-rewards' ),
				'add_new'            => esc_html__( '+ Add new rule', 'yith-woocommerce-points-and-rewards' ),
				'new_item'           => esc_html__( 'Add new rule', 'yith-woocommerce-points-and-rewards' ),
				'edit_item'          => esc_html__( 'Edit rule', 'yith-woocommerce-points-and-rewards' ),
				'view_item'          => esc_html__( 'View rule', 'yith-woocommerce-points-and-rewards' ),
				'search_items'       => esc_html__( 'Search rule', 'yith-woocommerce-points-and-rewards' ),
				'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-points-and-rewards' ),
				'not_found_in_trash' => esc_html__( 'Not found in Trash', 'yith-woocommerce-points-and-rewards' ),
			);

			$earning_rule_post_type_args = array(
				'labels'              => $labels_earning_rule,
				'supports'            => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'menu_position'       => 10,
				'capability_type'     => self::$earning_rule,
				'capabilities'        => self::get_capabilities( self::$earning_rule ),
				'show_in_nav_menus'   => false,
				'has_archive'         => true,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'publicly_queryable'  => false,
				'query_var'           => false,
			);

			register_post_type(
				self::$earning_rule,
				/**
				 * APPLY_FILTERS: ywpar_register_post_type_earning_rule
				 *
				 * filter the register post type args.
				 *
				 * @param array $args .
				 */
				apply_filters( 'ywpar_register_post_type_earning_rule', $earning_rule_post_type_args )
			);

			/*  REDEEMING RULES  */

			$labels_redeeming_rule = array(
				'name'               => esc_html_x(
					'Points Redeeming Rules',
					'Post Type General Name',
					'yith-woocommerce-points-and-rewards'
				),
				'singular_name'      => esc_html_x(
					'Points Redeeming Rule',
					'Post Type Singular Name',
					'yith-woocommerce-points-and-rewards'
				),
				'add_new_item'       => esc_html__( 'Add new rule', 'yith-woocommerce-points-and-rewards' ),
				'add_new'            => esc_html__( '+ Add new rule', 'yith-woocommerce-points-and-rewards' ),
				'new_item'           => esc_html__( 'Add new rule', 'yith-woocommerce-points-and-rewards' ),
				'edit_item'          => esc_html__( 'Edit rule', 'yith-woocommerce-points-and-rewards' ),
				'view_item'          => esc_html__( 'View rule', 'yith-woocommerce-points-and-rewards' ),
				'search_items'       => esc_html__( 'Search rule', 'yith-woocommerce-points-and-rewards' ),
				'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-points-and-rewards' ),
				'not_found_in_trash' => esc_html__( 'Not found in Trash', 'yith-woocommerce-points-and-rewards' ),
			);

			$redeeming_rule_post_type_args = array(
				'labels'              => $labels_redeeming_rule,
				'supports'            => false,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'menu_position'       => 10,
				'capability_type'     => self::$redeeming_rule,
				'capabilities'        => self::get_capabilities( self::$redeeming_rule ),
				'show_in_nav_menus'   => false,
				'has_archive'         => true,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'publicly_queryable'  => false,
				'query_var'           => false,
			);

			register_post_type(
				self::$redeeming_rule,
				/**
				 * APPLY_FILTERS: ywpar_register_post_type_redeeming_rule
				 *
				 * filter the register post type args.
				 *
				 * @param array $args .
				 */
				apply_filters( 'ywpar_register_post_type_redeeming_rule', $redeeming_rule_post_type_args )
			);
			/**
			 * DO_ACTION: ywpar_after_register_post_type
			 *
			 * hook after custom post type registration.
			 */
			do_action( 'ywpar_after_register_post_type' );

		}

		/**
		 * Get capabilities for custom post type
		 *
		 * @param string $capability_type Capability name.
		 *
		 * @return  array
		 *
		 * @since 2.2.0
		 */
		public static function get_capabilities( $capability_type ) {
			return array(
				'edit_post'              => "edit_{$capability_type}",
				'read_post'              => "read_{$capability_type}",
				'delete_post'            => "delete_{$capability_type}",
				'edit_posts'             => "edit_{$capability_type}s",
				'edit_others_posts'      => "edit_others_{$capability_type}s",
				'publish_posts'          => "publish_{$capability_type}s",
				'read_private_posts'     => "read_private_{$capability_type}s",
				'delete_posts'           => "delete_{$capability_type}s",
				'delete_private_posts'   => "delete_private_{$capability_type}s",
				'delete_published_posts' => "delete_published_{$capability_type}s",
				'delete_others_posts'    => "delete_others_{$capability_type}s",
				'edit_private_posts'     => "edit_private_{$capability_type}s",
				'edit_published_posts'   => "edit_published_{$capability_type}s",
				'create_posts'           => "edit_{$capability_type}s",
				'manage_posts'           => "manage_{$capability_type}s",
			);
		}

		/**
		 * Add the capability
		 */
		public static function add_capabilities() {
			self::add_admin_capabilities( self::$level_badge );
			self::add_admin_capabilities( self::$banner );
			self::add_admin_capabilities( self::$earning_rule );
			self::add_admin_capabilities( self::$redeeming_rule );
		}

		/**
		 * Add management capabilities to Admin and Shop Manager
		 *
		 * @param string $ctp Custom post type.
		 *
		 * @return  void
		 * @since   2.2.0
		 */
		public static function add_admin_capabilities( $ctp ) {
			$caps = self::get_capabilities( $ctp );

			$roles = array(
				'administrator',
				'shop_manager',
			);

			if ( ywpar_is_multivendor_active() ) {
				$roles[] = 'yith_vendor';
			}

			foreach ( $roles as $role_slug ) {

				$role = get_role( $role_slug );

				if ( ! $role ) {
					continue;
				}

				foreach ( $caps as $key => $cap ) {
					$role->add_cap( $cap );
				}
			}
		}

		/**
		 * Duplicate post type
		 *
		 * @param WP_Post $original_post Original post.
		 * @param string  $post_type Post type.
		 *
		 * @return int
		 */
		public static function duplicate_post( $original_post, $post_type ) {
			$new_title = $original_post->post_title . esc_html_x(
				' - Copy',
				'Name of duplicated post type',
				'yith-woocommerce-points-and-rewards'
			);
			$new_post  = array(
				'post_status' => 'publish',
				'post_type'   => $post_type,
				'post_title'  => $new_title,
			);

			$new_post_id = wp_insert_post( $new_post );
			$metas       = get_post_meta( $original_post->ID );

			if ( ! empty( $metas ) ) {
				foreach ( $metas as $meta_key => $meta_value ) {
					if ( '_edit_lock' === $meta_key || '_edit_last' === $meta_key ) {
						continue;
					}

					update_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value[0] ) );
				}
			}

			update_post_meta( $new_post_id, '_name', $new_title );

			return $new_post_id;
		}


	}

}
