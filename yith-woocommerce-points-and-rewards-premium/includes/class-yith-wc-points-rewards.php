<?php
/**
 * Main Class
 *
 * @class   YITH_WC_Points_Rewards
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

require_once YITH_YWPAR_INC . 'legacy/abstract-yith-wc-points-rewards-legacy.php';

if ( ! class_exists( 'YITH_WC_Points_Rewards' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards
	 */
	class YITH_WC_Points_Rewards extends YITH_WC_Points_Rewards_Legacy {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards
		 */
		protected static $instance;

		/**
		 * Admin.
		 *
		 * @var YITH_WC_Points_Rewards_Admin
		 */
		public $admin;

		/**
		 * Frontend.
		 *
		 * @var YITH_WC_Points_Rewards_Frontend
		 */
		public $frontend;

		/**
		 * Earning.
		 *
		 * @var YITH_WC_Points_Rewards_Earning
		 */
		public $earning;

		/**
		 * Extra points object.
		 *
		 * @var YITH_WC_Points_Rewards_Extra_Points
		 */
		public $extra_points;


		/**
		 * Expiring points object.
		 *
		 * @var YITH_WC_Points_Rewards_Expiration_Points
		 */
		public $expiration_points;

		/**
		 * Redeeming.
		 *
		 * @var YITH_WC_Points_Rewards_Redeeming
		 */
		public $redeeming;

		/**
		 * Points log
		 *
		 * @var YITH_WC_Points_Rewards_Points_Log
		 */
		public $points_log;

		/**
		 * Points log
		 *
		 * @var WC_Logger
		 */
		public $logger;

		/**
		 * Endpoint of my account page.
		 *
		 * @var string
		 */
		public $endpoint;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards
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

			/**
			 * APPLY_FILTERS: ywpar_can_plugin_load
			 *
			 * if the plugin can load.
			 */
			if ( ! apply_filters( 'ywpar_can_plugin_load', true ) ) {
				return;
			}

			// Common YITH hooks.
			add_action( 'plugins_loaded', array( $this, 'load' ), 20 );

			//this is done due to textdomain error in wp 6.7
			add_action( 'init', array( $this, 'load_rule_classes') );
			
			// Register plugin to licence/update system.
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_activation' ), 99 );
			add_action( 'wp_loaded', array( $this, 'register_plugin_for_updates' ), 99 );

			// configure endpoints.
			add_action( 'init', array( $this, 'set_endpoints' ), 5 );

			// set cron.
			add_action( 'init', array( $this, 'set_cron' ), 20 );

			// register widget.
			add_action( 'widgets_init', array( $this, 'register_widgets' ) );

			add_action( 'init', array( $this, 'gutenberg_integration' ) );


			add_action( 'delete_user', array( $this, 'remove_points_info_of_user_cancelled' ), 10, 1 );


			$this->logger = new WC_Logger();

		}
		/**
		 * Load rule classes
		 *
		 * @return void
		 */
		public function load_rule_classes() {
			if ( self::is_request( 'admin' ) ) {
				YITH_WC_Points_Rewards_Editor_Earning_Rules::get_instance();
				YITH_WC_Points_Rewards_Editor_Redeeming_Rules::get_instance();
			}
		}

		/**
		 * Load the integrations
		 */
		public function load_integration() {

			if ( defined( 'YITH_WCAF' ) ) {
				$affiliate_version = defined( 'YITH_WCAF::VERSION' ) ? YITH_WCAF::VERSION : YITH_WCAF::YITH_WCAF_VERSION;
				if ( version_compare( $affiliate_version, '1.8.0', '>=' ) ) {
					require_once YITH_YWPAR_INC . 'compatibility/class-ywpar-yith-woocommerce-affiliates.php';
					YWPAR_YITH_WooCommerce_Affiliates::get_instance();
				}
			}

			if ( defined( 'YITH_WCMBS_PREMIUM' ) && version_compare( YITH_WCMBS_VERSION, '1.5.0', '>=' ) ) {
				require_once YITH_YWPAR_INC . 'compatibility/class-ywpar-yith-woocommerce-membership.php';
				if ( ! class_exists( 'YWPAR_Membership' ) ) {
					class_alias( 'YWPAR_YITH_WooCommerce_Membership', 'YWPAR_Membership' );
				}
			}

			if ( defined( 'YITH_YWSBS_VERSION' ) && version_compare( YITH_YWSBS_VERSION, '2.0', '>=' ) ) {
				require_once YITH_YWPAR_INC . 'compatibility/class-ywpar-yith-woocommerce-subscription.php';
				if ( ! class_exists( 'YWPAR_Subscription' ) ) {
					class_alias( 'YWPAR_YITH_WooCommerce_Subscription', 'YWPAR_Subscription' );
				}
			}

			if ( defined( 'YITH_WPV_VERSION' ) && version_compare( YITH_WPV_VERSION, '3.0', '>=' ) ) {
				require_once YITH_YWPAR_INC . 'compatibility/class-ywpar-yith-woocommerce-product-vendors.php';
				class_alias( 'YWPAR_YITH_WooCommerce_Product_Vendors', 'YWPAR_Multivendor' );
			}

		}


		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 2.0.0
		 */
		public function load() {

			include_once YITH_YWPAR_INC . 'class-yith-wc-points-rewards-autoloader.php';
			include_once YITH_YWPAR_INC . 'class-yith-wc-points-rewards-customer.php';
			require_once YITH_YWPAR_INC . 'objects/class-yith-wc-points-rewards-banner.php';
			require_once YITH_YWPAR_INC . 'objects/class-yith-wc-points-rewards-earning-rule.php';
			require_once YITH_YWPAR_INC . 'objects/class-yith-wc-points-rewards-levels-badge.php';
			require_once YITH_YWPAR_INC . 'objects/class-yith-wc-points-rewards-redeeming-rule.php';
			require_once YITH_YWPAR_INC . '/widgets/class-yith-wc-points-rewards-widget.php';
			require_once YITH_YWPAR_INC . '/widgets/class-yith-wc-points-customers-points-widget.php';

			// load functions and deprecated functions.

			include_once YITH_YWPAR_INC . 'functions-yith-wc-points-rewards.php';
			include_once YITH_YWPAR_INC . 'functions-yith-wc-points-rewards-multi-currency.php';

			YITH_WC_Points_Rewards_Shortcodes::get_instance();
			YITH_WC_Points_Rewards_Assets::get_instance();
			YITH_WC_Points_Rewards_Ajax::get_instance();
			YITH_WC_Points_Rewards_Post_Types::init();
			YITH_WC_Points_Rewards_Email::get_instance();
			YITH_WC_Points_Rewards_Orders::get_instance();
			$this->points_log        = YITH_WC_Points_Rewards_Points_Log::get_instance();
			$this->earning           = YITH_WC_Points_Rewards_Earning::get_instance();
			$this->redeeming         = YITH_WC_Points_Rewards_Redeeming::get_instance();
			$this->extra_points      = YITH_WC_Points_Rewards_Extra_Points::get_instance();
			$this->expiration_points = YITH_WC_Points_Rewards_Expiration_Points::get_instance();

			$this->load_integration();
			
			if ( self::is_request( 'admin' ) ) {
				$this->admin = YITH_WC_Points_Rewards_Admin::get_instance();
				YITH_WC_Points_Rewards_Editor_Levels_Badges::get_instance();
				YITH_WC_Points_Rewards_Editor_Banners::get_instance();
			}

			if ( self::is_request( 'frontend' ) ) {
				$this->frontend = YITH_WC_Points_Rewards_Frontend::get_instance();
			}
			
			/* Wc blocks integration */
			include_once YITH_YWPAR_INC . 'blocks/class-yith-wc-points-rewards-blocks.php';
			YITH_WC_Points_Rewards_Blocks();

			YITH_WC_Points_Rewards_Version_Compatibility::get_instance();

			if ( class_exists( 'WOOCS_STARTER' ) ) {
				require_once YITH_YWPAR_INC . 'compatibility/class-yith-wc-points-rewards-woocommerce-currency-switcher.php';
			}

			if ( ! class_exists( 'YITH_WC_Points_Rewards_Redemption' ) ) {
				class_alias( 'YITH_WC_Points_Rewards_Redeeming', 'YITH_WC_Points_Rewards_Redemption' );
			}

		}

		/**
		 * What type of request is this?
		 *
		 * @param string $type admin, ajax, cron or frontend.
		 *
		 * @return boolean
		 */
		public static function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin() && ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( ! isset( $_REQUEST['context'] ) || ( isset( $_REQUEST['context'] ) && 'frontend' !== $_REQUEST['context'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				case 'ajax':
					return defined( 'DOING_AJAX' );

				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}

			return false;

		}

		/**
		 * Set options
		 *
		 * @param string $option Option name.
		 * @param mixed  $value Value of option.
		 * @return boolean
		 * @since   1.3.0
		 */
		public function set_option( $option, $value ) {
			return update_option( 'ywpar_' . $option, $value );
		}

		/**
		 * Register plugins for activation tab
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function register_plugin_for_activation() {
			if ( ! class_exists( 'YIT_Plugin_Licence' ) ) {
				include_once YITH_YWPAR_DIR . 'plugin-fw/licence/lib/yit-licence.php';
				include_once YITH_YWPAR_DIR . 'plugin-fw/licence/lib/yit-plugin-licence.php';
			}

			YIT_Plugin_Licence()->register( YITH_YWPAR_INIT, YITH_YWPAR_SECRET_KEY, YITH_YWPAR_SLUG );

		}

		/**
		 * Register plugins for update tab
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function register_plugin_for_updates() {
			if ( ! class_exists( 'YIT_Upgrade' ) ) {
				include_once YITH_YWPAR_DIR . 'plugin-fw/lib/yit-upgrade.php';
			}

			YIT_Upgrade()->register( YITH_YWPAR_SLUG, YITH_YWPAR_INIT );

		}

		/**
		 * Set Cron
		 *
		 * Set ywpar_cron action
		 *
		 * @since  1.0.0
		 */
		public function set_cron() {
			if ( ! wp_next_scheduled( 'ywpar_cron' ) ) {
				wp_schedule_event( time(), 'daily', 'ywpar_cron' );
			}
			if ( ! wp_next_scheduled( 'ywpar_cron_birthday' ) && ywpar_get_option( 'enable_points_on_birthday_exp' ) === 'yes' ) {
				$ve = get_option( 'gmt_offset' ) > 0 ? '-' : '+';
				wp_schedule_event( strtotime( '00:00 tomorrow ' . $ve . absint( get_option( 'gmt_offset' ) ) . ' HOURS' ), 'daily', 'ywpar_cron_birthday' );
			}
		}

		/**
		 * Returns the list of all usermeta used be plugin
		 *
		 * @return array
		 * @since 1.1.3
		 */
		public function get_usermeta_list() {
			$usermeta = array();
			if ( current_user_can( 'manage_woocommerce' ) ) {
				$current_customer = ywpar_get_current_customer();
				$data             = $current_customer->get_data();
				$usermeta         = array_keys( $data );
			}

			return apply_filters( 'ywpar_usermeta_list', $usermeta );
		}

		/**
		 * Empty the table of log and delete the post meta to order and usermeta to users
		 *
		 * @return void
		 */
		public function reset_points() {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				global $wpdb;
				$user_meta  = $this->get_usermeta_list();
				$order_meta = YITH_WC_Points_Rewards_Orders::get_ordermeta_list();

				$user_meta  = "'" . implode( "','", $user_meta ) . "'";
				$order_meta = "'" . implode( "','", $order_meta ) . "'";

				$wpdb->query( "DELETE FROM {$wpdb->usermeta}  WHERE {$wpdb->usermeta}.meta_key IN( {$user_meta} )" ); //phpcs:ignore
				$wpdb->query( "DELETE FROM {$wpdb->postmeta}  WHERE {$wpdb->postmeta}.meta_key IN( {$order_meta} )" ); //phpcs:ignore

				delete_option( 'yith_ywpar_porting_done' );
				$this->points_log->truncate_table();
			}

		}

		/**
		 * Register the widgets
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function register_widgets() {
			register_widget( 'YITH_YWPAR_Points_Rewards_Widget' );
			register_widget( 'YITH_YWPAR_Points_Rewards_Customers_Points' );
		}

		/**
		 * Gutenberg Integration
		 */
		public function gutenberg_integration() {
			if ( function_exists( 'yith_plugin_fw_gutenberg_add_blocks' ) ) {
				$blocks = include_once YITH_YWPAR_DIR . 'plugin-options/gutenberg/blocks.php';
				if ( $blocks ) {
					yith_plugin_fw_gutenberg_add_blocks( $blocks );
					if ( defined( 'ELEMENTOR_VERSION' ) && function_exists( 'yith_plugin_fw_register_elementor_widgets' ) ) {
						yith_plugin_fw_register_elementor_widgets( $blocks, true );
					}
					wp_register_style( 'yith-ywpar-gutenberg', YITH_YWPAR_ASSETS_URL . '/css/shortcodes.css', '', YITH_YWPAR_VERSION );
					wp_register_script( 'yith-ywpar-gutenberg', YITH_YWPAR_ASSETS_URL . '/js/blocks' . YITH_YWPAR_SUFFIX . '.js', array( 'jquery' ), YITH_YWPAR_VERSION, true );
				}
			}
		}

		/**
		 * Set My account end points
		 */
		public function set_endpoints() {
			// ____ ONLY FOR ENABLED CUSTOMERS ___.
			$ywpar_customer = ywpar_get_current_customer();

			if ( ! $ywpar_customer || ! $ywpar_customer->is_enabled( 'earn' ) ) {
				return;
			}

			// Add the endpoints to WooCommerce My Account.
			if ( ywpar_get_option( 'show_point_list_my_account_page' ) === 'yes' ) {
				$endpoint                                  = ywpar_get_option( 'my_account_page_endpoint' );
				$this->endpoint                            = ! empty( $endpoint ) ? $endpoint : 'my-points';
				WC()->query->query_vars[ $this->endpoint ] = $this->endpoint;
				WC()->query->query_vars['my-points']       = $this->endpoint;
				add_filter( 'option_rewrite_rules', array( $this, 'rewrite_rules' ), 1 );
				add_filter( 'woocommerce_account_menu_items', array( $this, 'ywpar_add_points_menu_items' ), 20 );

				global $post, $sitepress;
				if ( $post && wc_get_page_id( 'myaccount' ) === $post->ID && ! $sitepress ) {
					function_exists( 'get_home_path' ) && flush_rewrite_rules();
				}
			}

		}

		/**
		 * Check if the permalink should be flushed.
		 *
		 * @param array $rules Rules.
		 *
		 * @return bool
		 */
		public function rewrite_rules( $rules ) {
			return isset( $rules[ "(.?.+?)/{$this->endpoint}(/(.*))?/?$" ] ) ? $rules : false;
		}

		/**
		 * Add the menu item on WooCommerce My account Menu
		 * before the Logout item menu.
		 *
		 * @param array $wc_menu WooCommerce menu.
		 *
		 * @return mixed
		 */
		public function ywpar_add_points_menu_items( $wc_menu ) {

			if ( isset( $wc_menu['customer-logout'] ) ) {
				$logout = $wc_menu['customer-logout'];
				unset( $wc_menu['customer-logout'] );
			}

			$wc_menu[ $this->endpoint ] = ywpar_get_option( 'my_account_page_label', __( 'My Points', 'yith-woocommerce-points-and-rewards' ) );

			if ( isset( $logout ) ) {
				$wc_menu['customer-logout'] = $logout;
			}

			return $wc_menu;
		}

		/**
		 * Remove the info from the customer whem the user is cancelled
		 *
		 * @param int $id       ID of the user to delete.
		 * @since  3.1.0
		 */
		public function remove_points_info_of_user_cancelled( $id ) {
			yith_points()->points_log->remove_user_log( $id );
		}


	}

}


if ( ! function_exists( 'yith_points' ) ) {
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards class
	 *
	 * @return YITH_WC_Points_Rewards
	 */
	function yith_points() {
		return YITH_WC_Points_Rewards::get_instance();
	}
}

