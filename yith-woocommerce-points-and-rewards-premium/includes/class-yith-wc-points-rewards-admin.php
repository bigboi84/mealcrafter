<?php
/**
 * Main Admin Class
 *
 * @class   YITH_WC_Points_Rewards_Admin
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;


require_once YITH_YWPAR_INC . 'legacy/abstract-yith-wc-points-rewards-admin-legacy.php';

if ( ! class_exists( 'YITH_WC_Points_Rewards_Admin' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards
	 */
	class YITH_WC_Points_Rewards_Admin extends YITH_WC_Points_Rewards_Admin_Legacy {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Admin
		 */
		protected static $instance;

		/**
		 * Panel Object
		 *
		 * @var YIT_Plugin_Panel_WooCommerce
		 */
		protected $panel;

		/**
		 * Panel Page
		 *
		 * @var string
		 */
		public static $panel_page = 'yith_woocommerce_points_and_rewards';

		/**
		 * Wp List Table
		 *
		 * @var Wp List Table
		 */
		public $cpt_obj;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Admin
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

			// Add action links.
			add_filter(
				'plugin_action_links_' . plugin_basename( YITH_YWPAR_DIR . '/' . basename( YITH_YWPAR_FILE ) ),
				array( $this, 'action_links' )
			);
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );

			// Panel.
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			add_filter( 'admin_body_class', array( $this, 'filter_body_class' ) );

			add_action(
				'update_option_ywpar_user_role_redeem_type',
				array( $this, 'handle_redeem_rules_option_update' ),
				10,
				2
			);
			add_action(
				'update_option_ywpar_user_role_enabled_type',
				array( $this, 'handle_earning_rules_option_update' ),
				10,
				2
			);

			add_action(
				'woocommerce_admin_settings_sanitize_option_ywpar_my_account_page_endpoint',
				array( $this, 'end_point_sanitize' )
			);
			add_action(
				'woocommerce_admin_settings_sanitize_option_ywpar_affiliates_earning_conversion',
				array( $this, 'sanitize_affiliates_earning_conversion_field' )
			);

			/* Add widgets into the dashboard */
			add_action( 'wp_dashboard_setup', array( $this, 'ywpar_points_widgets' ) );
			/* Import export */
			add_action( 'admin_init', array( $this, 'actions_from_settings_panel' ), 9 );

			add_action( 'admin_init', array( $this, 'add_filter_to_get_option' ) );
			add_action( 'admin_notices', array( $this, 'check_coupon' ) );

			/* Handle custom tab Emails */
			add_action( 'ywpar_custom_emails_tab', array( $this, 'print_emails_tab' ) );
			add_action( 'wp_ajax_ywpar_save_email_settings', array( $this, 'save_email_settings' ) );
			add_action( 'wp_ajax_ywpar_save_email_status', array( $this, 'save_email_status' ) );

			YITH_WC_Points_Rewards_Porting::get_instance();
			YITH_WC_Points_Rewards_Import_Export::get_instance();
			YITH_WC_Points_Rewards_Customer_Bulk_Actions::get_instance();

			/* reset products points transient if the conversion option is updated */
			add_action( 'woocommerce_update_option', array( $this, 'ywpar_delete_product_points_transient' ) );

		}

		/**
		 * Reset products points transient if the conversion option is updated.
		 *
		 * @param array $option Old value.
		 *
		 * @since 4.2.0
		 */
		function ywpar_delete_product_points_transient( $option ) {
			if ( isset( $option['id'] ) && 'ywpar_earn_points_conversion_rate' === $option['id'] ) {
				delete_transient('ywpar_product_points');
			}
		}

		/**
		 * Handle the redeem option changes to update the user type option inside the single rule.
		 *
		 * @param mixed $old_value Old value.
		 * @param mixed $value Current value.
		 *
		 * @since 3.0.0
		 */
		public function handle_redeem_rules_option_update( $old_value, $value ) {
			if ( $old_value !== $value || 'roles' === $value ) {
				$this->update_user_type_option( 'redeeming' );
			}
		}

		/**
		 * Handle the earning option changes to update the user type option inside the single rule.
		 *
		 * @param mixed $old_value Old value.
		 * @param mixed $value Current value.
		 *
		 * @since 3.0.0
		 */
		public function handle_earning_rules_option_update( $old_value, $value ) {
			if ( $old_value !== $value || 'roles' === $value ) {
				$this->update_user_type_option();
			}
		}

		/**
		 * Get update the option user type of the rules
		 *
		 * @param string $rule_type Rule type ('redeem' or 'earning').
		 *
		 * @since 3.0.0
		 */
		public function update_user_type_option( $rule_type = 'earning' ) {

			if ( 'redeeming' === $rule_type ) {
				$post_type = YITH_WC_Points_Rewards_Post_Types::$redeeming_rule;
				$callback  = 'ywpar_get_redeeming_rule';
			} else {
				$post_type = YITH_WC_Points_Rewards_Post_Types::$earning_rule;
				$callback  = 'ywpar_get_earning_rule';
			}

			$args = array(
				'numberposts' => - 1,
				'post_type'   => $post_type,
				'post_status' => 'any',
				'fields'      => 'ids',
			);

			$rules = get_posts( $args );

			if ( $rules ) {
				foreach ( $rules as $rule ) {
					$rule = $callback( $rule );
					$rule->update_user_type_options();
				}
			}
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0.0
		 * @use      /Yit_Plugin_Panel class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}
			/**
			 * APPLY_FILTERS: ywpar_show_admin_tabs
			 *
			 * filter the option panel tab.
			 *
			 * @param array $admin_tabs admin tabs.
			 */
			$admin_tabs = apply_filters(
				'ywpar_show_admin_tabs',
				array(
					'customers' => array(
						'title' => esc_html__( 'Customers Points', 'yith-woocommerce-points-and-rewards' ),
						'icon'  => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path></svg>',
					),
					'points'        => array(
						'title' => esc_html__( 'Points Options', 'yith-woocommerce-points-and-rewards' ),
						'icon'  => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"></path></svg>',
					),
					'redeem'        => array(
						'title' => esc_html__( 'Points Redeeming', 'yith-woocommerce-points-and-rewards' ),
						'icon'  => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"></path></svg>',
					),
					'customization' => array(
						'title'       => esc_html__( 'Customization', 'yith-woocommerce-points-and-rewards' ),
						'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l1.5 1.5.75-.75V8.758l2.276-.61a3 3 0 10-3.675-3.675l-.61 2.277H12l-.75.75 1.5 1.5M15 11.25l-8.47 8.47c-.34.34-.8.53-1.28.53s-.94.19-1.28.53l-.97.97-.75-.75.97-.97c.34-.34.53-.8.53-1.28s.19-.94.53-1.28L12.75 9M15 11.25L12.75 9"></path></svg>',
						'description' => esc_html__(
							'Customize the labels and the messages shown in your shop related to points collection.',
							'yith-woocommerce-points-and-rewards'
						),
					),
					'emails'        => array(
						'title'       => esc_html__( 'Emails', 'yith-woocommerce-points-and-rewards' ),
						'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"></path></svg>',
						'description' => esc_html__(
							'Enable and customize email notifications sent to your customers related to points collection.',
							'yith-woocommerce-points-and-rewards'
						),
					),
				)
			);

			/**
			 * APPLY_FILTERS: ywpar_admin_panel_options
			 *
			 * filter the arguments to create admin panel.
			 *
			 * @param array $panel_options the array of the args to create the admin panel.
			 */
			$args = apply_filters(
				'ywpar_admin_panel_options',
				array(
					'create_menu_page' => true,
					'parent_slug'      => '',
					'page_title'       => 'YITH WooCommerce Points and Rewards',
					'menu_title'       => 'Points and Rewards',
					'capability'       => ywpar_get_manage_points_capability(),
					'parent'           => '',
					'is_premium'       => true,
					'parent_page'      => 'yith_plugin_panel',
					'page'             => self::$panel_page,
					'admin-tabs'       => $admin_tabs,
					'options-path'     => YITH_YWPAR_DIR . '/plugin-options',
					'class'            => yith_set_wrapper_class( 'yith-plugin-fw-wp-page-wrapper' ),
					'plugin_slug'      => YITH_YWPAR_SLUG,
					'plugin-url'       => YITH_YWPAR_URL,
					'help_tab'         => array(
						'main_video' => array(
							'desc' => _x(
								'Check this video to learn how to set up a <b>points and rewards system</b> in your shop:',
								'[HELP TAB] Video title',
								'yith-woocommerce-points-and-rewards'
							),
							'url'  => array(
								'it' => 'https://www.youtube.com/embed/uruZyS6WsMo',
								'es' => 'https://www.youtube.com/embed/qPTGoBO8SpU',
								'en' => 'https://www.youtube.com/embed/YAUlmPNfSd8',
							),
						),
						'playlists'  => array(
							'it' => 'https://www.youtube.com/playlist?list=PL9c19edGMs08i5GAhu8OgMVMC8yC4OYFd',
							'es' => 'https://www.youtube.com/playlist?list=PL9Ka3j92PYJNWedcFj1onQk2wElm0OPlV',
							'en' => 'https://www.youtube.com/playlist?list=PLDriKG-6905miawZZHGXOOW1Pi_J5riDa',
						),
						'hc_url'     => 'https://support.yithemes.com/hc/en-us/categories/360003469637-YITH-WOOCOMMERCE-POINTS-AND-REWARDS',
					),
					'is_premium'       => defined( YITH_YWPAR_PREMIUM ),
					'ui_version'       => 2,
					'your_store_tools' => array(
						'items' => array(
							'wishlist'               => array(
								'name'           => 'YITH WooCommerce Wishlist',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/wishlist.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-wishlist/',
								'description'    => _x(
									'Allow your customers to create lists of products they want and share them with family and friends.',
									'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Wishlist',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_WCWL_PREMIUM' ),
								'is_recommended' => true,
							),
							'gift-cards'             => array(
								'name'           => 'YITH WooCommerce Gift Cards',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/gift-cards.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-gift-cards/',
								'description'    => _x(
									'Sell gift cards in your shop to increase your earnings and attract new customers.',
									'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Gift Cards',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_YWGC_PREMIUM' ),
								'is_recommended' => true,
							),
							'request-a-quote'        => array(
								'name'           => 'YITH WooCommerce Request a Quote',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/request-a-quote.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-request-a-quote/',
								'description'    => _x(
									'Hide prices and/or the "Add to cart" button and let your customers request a custom quote for every product.',
									'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Request a Quote',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_YWRAQ_PREMIUM' ),
								'is_recommended' => false,
							),
							'ajax-product-filter'         => array(
								'name'           => 'YITH WooCommerce Ajax Product Filter',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/ajax-product-filter.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-ajax-product-filter/',
								'description'    => _x(
									'Help your customers to easily find the products they are looking for and improve the user experience of your shop.',
									'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Ajax Product Filter',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_WCAN_PREMIUM' ),
								'is_recommended' => false,
							),
							'product-addons'         => array(
								'name'           => 'YITH WooCommerce Product Add-Ons & Extra Options',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/product-add-ons.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-product-add-ons/',
								'description'    => _x(
									'Add paid or free advanced options to your product pages using fields like radio buttons, checkboxes, drop-downs, custom text inputs, and more.',
									'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Product Add-Ons',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_WAPO_PREMIUM' ),
								'is_recommended' => false,
							),
							'dynamic-pricing'        => array(
								'name'           => 'YITH WooCommerce Dynamic Pricing & Discounts',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/dynamic-pricing-and-discounts.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-dynamic-pricing-and-discounts/',
								'description'    => _x(
									'Increase conversions through dynamic discounts and price rules, and build powerful and targeted offers.',
									'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Dynamic Pricing & Discounts',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_YWDPD_PREMIUM' ),
								'is_recommended' => false,
							),
							'customize-my-account'   => array(
								'name'           => 'YITH WooCommerce Customize My Account Page',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/customize-myaccount-page.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-customize-my-account-page/',
								'description'    => _x(
									'Customize the My Account page of your customers by creating custom sections with promotions and ad-hoc content based on your needs.',
									'[YOUR STORE TOOLS TAB] Description for plugin YITH WooCommerce Customize My Account',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_WCMAP_PREMIUM' ),
								'is_recommended' => false,
							),
							'recover-abandoned-cart' => array(
								'name'           => 'YITH WooCommerce Recover Abandoned Cart',
								'icon_url'       => YITH_YWPAR_ASSETS_URL . '/images/plugins/recover-abandoned-cart.svg',
								'url'            => '//yithemes.com/themes/plugins/yith-woocommerce-recover-abandoned-cart/',
								'description'    => _x(
									'Contact users who have added products to the cart without completing the order and try to recover lost sales.',
									'[YOUR STORE TOOLS TAB] Description for plugin Recover Abandoned Cart',
									'yith-woocommerce-points-and-rewards'
								),
								'is_active'      => defined( 'YITH_YWRAC_PREMIUM' ),
								'is_recommended' => false,
							),
						),
					),
				)
			);

			// enable shop manager to change Customer points.
			if ( 'yes' === ywpar_get_option( 'enabled_shop_manager' ) ) {
				// enable shop manager to set Points Setting.
				add_filter(
					'option_page_capability_yit_' . $args['parent'] . '_options',
					array( $this, 'change_capability' )
				);
				add_filter( 'yit_plugin_panel_menu_page_capability', array( $this, 'change_capability' ) );
			}

			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_YWPAR_DIR . '/plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );

			add_action( 'yith_ywpar_customers', array( $this, 'customers_tab' ) );
			add_action( 'yith_ywpar_bulk', array( $this, 'bulk_tab' ) ); // TODO: remove code
			add_action( 'yith_ywpar_ranking_tab', array( $this, 'ranking_tab' ) );
			add_action( 'yith_ywpar_import_export', array( $this, 'import_export' ) );
			add_filter(
				'yith_plugin_fw_get_field_template_path',
				array( $this, 'get_yith_panel_custom_template' ),
				10,
				2
			);

			$this->save_default_options();
		}


		/** Add body class for plugin panel pages.
		 *
		 * @param string $class Body class.
		 *
		 * @return string Body class.
		 */
		public function filter_body_class( $class ) {
			global $post;
			$post_types = array(
				'ywpar-earning-rule',
				'ywpar-level-badge',
				'wpar-redeeming-rule',
				'ywpar-banner',
				'ywpar-redeeming-rule',
			);
			if ( ( isset( $_GET['page'] ) && 'yith_woocommerce_points_and_rewards' === $_GET['page'] ) ||
				 ( isset( $_GET['post_type'] ) && in_array( $_GET['post_type'], $post_types ) ) ||
				 ( $post instanceof WP_Post && in_array( $post->post_type, $post_types ) )
			) {
				$class .= ' yith_woocommerce_points_and_rewards_panel_page ';
			}

			return $class;
		}

		/**
		 * Save default options when the plugin is installed
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function save_default_options() {
			$options                = maybe_unserialize( get_option( 'yit_ywpar_options', array() ) );
			$current_option_version = get_option( 'yit_ywpar_option_version', '0' );
			$forced                 = isset( $_GET['update_ywpar_options'] ) && $_GET['update_ywpar_options'] === 'forced'; //phpcs:ignore
			$multicurrency          = get_option( 'yit_ywpar_multicurrency' );

			if ( version_compare( $current_option_version, YITH_YWPAR_VERSION, '>=' ) && ! $forced ) {
				return;
			}

			if ( version_compare( $current_option_version, '1.7.0', '<' ) ) {
				// check if there's the old expiration mode.
				yith_points()->expiration_points->reset_expiration_points();

				// retro-compatibility.
				$new_option = array_merge( $this->panel->get_default_options(), (array) $options );
				update_option( 'yit_ywpar_options', $new_option );

				ywpar_options_porting( $options );
			}

			if ( false === $multicurrency ) {
				ywpar_conversion_points_multilingual();
			}

			update_option( 'yit_ywpar_option_version', YITH_YWPAR_VERSION );
		}

		/**
		 * Customer List Table Tab
		 *
		 * Load the customers tab template on admin page
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function customers_tab() {

			$view_type = isset( $_REQUEST['user_id'] ) ? 'history' : 'list'; //phpcs:ignore

			if ( 'history' === $view_type ) {
				require_once YITH_YWPAR_INC . 'admin/class-yith-wc-points-rewards-customer-history-list-table.php';
				$user_id        = abs( sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) ) ); //phpcs:ignore
				$ywpar_customer = ywpar_get_customer( $user_id );
				$this->cpt_obj  = new YITH_WC_Points_Rewards_Customer_History_List_Table( array( 'customer' => $ywpar_customer ) );

			} else {
				require_once YITH_YWPAR_INC . 'admin/class-yith-wc-points-rewards-customers-list-table.php';
				$this->cpt_obj = new YITH_WC_Points_Rewards_Customers_List_Table();
			}

			$customers_tab = YITH_YWPAR_VIEWS_PATH . '/tabs/customers/customers-tab.php';

			$link = remove_query_arg( array( 'action', 'user_id' ) );

			if ( file_exists( $customers_tab ) ) {
				include_once $customers_tab;
			}

		}

		/**
		 * Bulk action tab.
		 */
		public function bulk_tab() {
			$bulk_actions = YITH_YWPAR_VIEWS_PATH . '/tabs/bulk-actions.php';
			if ( file_exists( $bulk_actions ) ) {
				include_once $bulk_actions;
			}
		}

		/**
		 * Shortcodes Tab Template
		 *
		 * Load the Shortcodes tab template on admin page.
		 *
		 * @return   void
		 * @since    2.1.0
		 */
		public function ranking_tab() {
			$ranking_view = YITH_YWPAR_VIEWS_PATH . '/tabs/ranking-tab.php';
			if ( file_exists( $ranking_view ) ) {
				include_once $ranking_view;
			}
		}

		/**
		 * Import Export Tab Template
		 *
		 * Load the Bulk tab template on admin page
		 *
		 * @return   void
		 * @since    2.0.0
		 */
		public function import_export() {
			$import_export = YITH_YWPAR_VIEWS_PATH . '/tabs/import_export-tab.php';
			if ( file_exists( $import_export ) ) {
				include_once $import_export;
			}
		}

		/**
		 * Action Links
		 *
		 * @param array $links Links plugin array.
		 *
		 * @return mixed
		 * @use    plugin_action_links_{$plugin_file_name}
		 */
		public function action_links( $links ) {

			if ( function_exists( 'yith_add_action_links' ) ) {
				$links = yith_add_action_links( $links, self::$panel_page, true, YITH_YWPAR_SLUG );
			}

			return $links;
		}

		/**
		 * Add the action links to plugin admin page.
		 *
		 * @param array  $new_row_meta_args Plugin Meta New args.
		 * @param string $plugin_meta Plugin Meta.
		 * @param string $plugin_file Plugin file.
		 * @param array  $plugin_data Plugin data.
		 * @param string $status Status.
		 * @param string $init_file Init file.
		 *
		 * @return array
		 */
		public function plugin_row_meta(
			$new_row_meta_args,
			$plugin_meta,
			$plugin_file,
			$plugin_data,
			$status,
			$init_file = 'YITH_YWPAR_INIT'
		) {

			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['slug']       = YITH_YWPAR_SLUG;
				$new_row_meta_args['is_premium'] = true;
			}

			return $new_row_meta_args;
		}

		/**
		 * Add custom option template to panel
		 *
		 * @param string $template Template to filter.
		 * @param array  $field Field.
		 *
		 * @return string
		 */
		public function get_yith_panel_custom_template( $template, $field ) {
			$custom_option_types = array(
				'options-conversion',
				'options-conversion-earning',
				'options-role-conversion',
				'options-expire',
				'options-levels-badges-range',
				'options-extrapoints-timing',
				'options-extrapoints-levels',
				'options-extrapoints',
				'options-extrapoints-multi',
				'options-percentage-conversion',
				'options-role-conversion',
				'options-role-percentage-conversion',
				'options-restrictions-text-input',
				'options-extrapoints-membership-plans',
			);

			remove_all_filters( 'woocommerce_currency_symbol' );
			$field_type = $field['type'];
			if ( isset( $field['type'] ) && in_array( $field['type'], $custom_option_types, true ) ) {
				$template = YITH_YWPAR_VIEWS_PATH . "/panel/types/{$field_type}.php";
			}

			return $template;
		}

		/**
		 * Modify the capability
		 *
		 * @param string $capability Capability.
		 *
		 * @return string
		 */
		public function change_capability( $capability ) {
			return 'manage_woocommerce';
		}

		/**
		 * Fix My Points page endpoint, replacing spaces with -
		 *
		 * @param string $value Value to sanitize.
		 *
		 * @return string
		 * @since 1.7.3
		 */
		public function end_point_sanitize( $value ) {
			return empty( $value ) ? '' : strtolower( preg_replace( '/\s+/', '-', $value ) );
		}

		/**
		 * Sanitize affiliate conversion field
		 *
		 * @param mixed $value Value to sanitize.
		 *
		 * @return mixed|string
		 */
		public function sanitize_affiliates_earning_conversion_field( $value ) {
			return empty( $value ) ? '' : maybe_serialize( $value );
		}


		/**
		 * Add a widgets to the dashboard.
		 *
		 * This function is hooked into the 'wp_dashboard_setup' action below.
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function ywpar_points_widgets() {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				wp_add_dashboard_widget(
					'ywpar_points_hit_widget',
					__( 'Best Point Earners', 'yith-woocommerce-points-and-rewards' ),
					array( $this, 'points_hit_widget' )
				);
				wp_add_dashboard_widget(
					'ywpar_points_best_rewards_widget',
					__( 'Best Point Rewards', 'yith-woocommerce-points-and-rewards' ),
					array( $this, 'best_rewards_widget' )
				);
			}
		}

		/**
		 * Print the dashboard widget with the users with best points
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function points_hit_widget() {

			$users = yith_points()->points_log->get_best_users(
				'all_time',
				apply_filters( 'ywpar_points_hit_widget_items_number', 10 ) // APPLY_FILTERS: ywpar_points_hit_widget_items_number | change the number of items to show | @param int $num
			);

			if ( ! empty( $users ) ) {
				$table = '<table cellpadding="5" class="ywpar_points_hit_widget">';
				foreach ( $users as $user ) {
					$customer    = ywpar_get_customer( $user->user_id );
					$table      .= '<tr>';
					$table      .= '<td>' . get_avatar( $customer->get_id(), '32' ) . '</td>';
					$points      = $customer->get_total_points();
					$history_url = admin_url( 'admin.php?yit_plugin_panel&page=' . self::$panel_page . '&tab=customers&action=update&user_id=' . $customer->get_id() );

					$table .= '<td>' . esc_html( $customer->get_wc_customer()->get_display_name() ) . '</td>';
					$table .= '<td class="points">' . esc_html( $points ) . '</td>';
					$table .= '<td class="history"><a href="' . esc_url( $history_url ) . '" class="button button-primary"> ' . esc_html(
						__(
							'View History',
							'yith-woocommerce-points-and-rewards'
						)
					) . '</a></td>';
					$table .= '</tr>';
				}
				$table .= '</table>';

				echo wp_kses_post( $table );
			} else {
				esc_html_e( 'No users found', 'yith-woocommerce-points-and-rewards' );
			}

		}

		/**
		 * Print the dashboard widget with the users with best discounts
		 *
		 * @return  void
		 * @since   1.0.0
		 */
		public function best_rewards_widget() {

			/**
			 * APPLY_FILTERS: ywpar_best_rewards_widget_items_number
			 *
			 * change the number of items to show.
			 *
			 * @param int $number
			 */
			$users = $this->user_list_discount( apply_filters( 'ywpar_best_rewards_widget_items_number', 10 ) );

			if ( ! empty( $users ) ) {
				$table = '<table cellpadding="5" class="ywpar_points_hit_widget">';
				foreach ( $users as $user ) {
					$customer    = ywpar_get_customer( $user->ID );
					$table      .= '<tr>';
					$table      .= '<td>' . get_avatar( $user->ID, '32' ) . '</td>';
					$discount    = $customer->get_total_discount();
					$history_url = admin_url( 'admin.php?yit_plugin_panel&page=' . self::$panel_page . '&tab=customers&action=update&user_id=' . $user->ID );

					$table .= '<td>' . esc_html( $user->display_name ) . '</td>';
					$table .= '<td class="points">' . wp_kses_post( wc_price( $discount ) ) . '</td>';
					$table .= '<td class="history"><a href="' . esc_url( $history_url ) . '" class="button button-primary"> ' . esc_html__(
						'View History',
						'yith-woocommerce-points-and-rewards'
					) . '</a></td>';
					$table .= '</tr>';
				}
				$table .= '</table>';
				echo wp_kses_post( $table );
			} else {
				echo esc_html( __( 'No users found', 'yith-woocommerce-points-and-rewards' ) );
			}

		}

		/**
		 * Returns the list of user order by the meta '_ywpar_user_total_discount' that is the
		 * total amount saved by each customer
		 *
		 * @param int $number Number of results.
		 *
		 * @return array
		 */
		public function user_list_discount( $number ) {
			$user_query = new WP_User_Query(
				array(
					'number'   => $number,
					'meta_key' => '_ywpar_user_total_discount' . ywpar_get_blog_suffix(),
					//phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					'orderby'  => 'meta_value_num',
					'order'    => 'DESC',
					'fields'   => array( 'ID', 'display_name' ),
				)
			);

			$users = $user_query->get_results();

			return $users;
		}

		/**
		 * Import point from csv
		 *
		 * @return void
		 */
		public function actions_from_settings_panel() {

			if ( ! isset( $_REQUEST['page'], $_REQUEST['ywpar_safe_submit_field'], $_REQUEST['_wpnonce'], $_REQUEST['delimiter'], $_REQUEST['csv_format'] ) || ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ),
				'ywpar_import_export'
			) || self::$panel_page !== $_REQUEST['page'] ) {
				return;
			}

			YITH_WC_Points_Rewards_Porting::get_instance()->import_export( $_REQUEST );
		}

		/**
		 * Add hook to filter plugin options
		 */
		public function add_filter_to_get_option() {
			$option_list = array(
				'ywpar_rewards_percentual_conversion_rate',
				'ywpar_rewards_points_role_rewards_fixed_conversion_rate',
				'ywpar_rewards_points_role_rewards_percentage_conversion_rate',
				'ywpar_earn_points_conversion_rate',
				'ywpar_rewards_conversion_rate',
				'ywpar_earn_points_role_conversion_rate',
				'ywpar_review_exp',
				'ywpar_num_order_exp',
				'ywpar_amount_spent_exp',
				'ywpar_number_of_points_exp',
				'ywpar_checkout_threshold_exp',
			);

			foreach ( $option_list as $option ) {
				add_filter( 'option_' . $option, array( $this, 'maybe_serialize' ), 10, 2 );
			}
		}

		/**
		 * Serialize the value
		 *
		 * @param mixed  $value Value.
		 * @param string $option Option name.
		 *
		 * @return mixed
		 */
		public function maybe_serialize( $value, $option ) {
			return maybe_serialize( $value );
		}

		/**
		 * Display Admin Notice if coupons are enabled
		 *
		 * @return void
		 * @since  1.7.6
		 */
		public function check_coupon() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( isset( $_GET['page'] ) && 'yith_woocommerce_points_and_rewards' === $_GET['page'] ) { //phpcs:ignore
				if ( 'yes' !== get_option( 'woocommerce_enable_coupons' ) && 'yes' !== get_option(
					'ywpar_dismiss_disabled_coupons_warning_message',
					'no'
				) ) { ?>
					<div id="message" class="notice notice-warning ywpar_disabled_coupons">
						<p>
							<strong>
								<?php
								esc_html_e(
									'YITH WooCommerce Points and Rewards',
									'yith-woocommerce-points-and-rewards'
								);
								?>
							</strong>
						</p>

						<p>
							<?php
							esc_html_e(
								'WooCommerce coupon system has been disabled. In order to make YITH WooCommerce Points and Rewards work correctly, you have to enable coupons.',
								'yith-woocommerce-points-and-rewards'
							);
							?>
						</p>

						<p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=general' ) ); ?>">
								<?php
								echo esc_html__(
									'Enable the use of coupons',
									'yith-woocommerce-points-and-rewards'
								);
								?>
							</a>
						</p>
					</div>
					<?php
				}
			}
		}

		/**
		 * Print Emails tab.
		 *
		 * @return void
		 */
		public function print_emails_tab() {
			require YITH_YWPAR_VIEWS_PATH . '/tabs/emails-tab.php';
		}

		/**
		 * Handle saving for email settings.
		 *
		 * @return void
		 */
		public function save_email_settings() {
			$options = array();
			if ( wp_verify_nonce( $_POST['nonce'], 'ywpar_email_settings' ) && isset( $_POST['params'] ) ) {
				parse_str( $_POST['params'], $options );
				foreach ( $options as $key => $value ) {
					update_option( 'ywpar_' . $key, $value );
				}
                if( isset( $_POST['email_key'] ) && 'update_points' === $_POST['email_key'] && isset( $_POST['nonce'] )){
                    $value = isset( $options['update_point_mail_on_admin_action'] ) ? 'yes' : 'no';
	                update_option( 'ywpar_update_point_mail_on_admin_action', $value );
                }
			}
			die();
		}


		/**
		 * Handle saving for email status.
		 *
		 * @return void
		 */
		public function save_email_status() {
			if ( isset( $_POST['enabled'] ) && isset( $_POST['option_name'] ) && wp_verify_nonce( $_POST['nonce'], 'ywpar_email_status' ) ) {
				update_option( 'ywpar_' . $_POST['option_name'], $_POST['enabled'] );
			}
			die();
		}


	}

}
