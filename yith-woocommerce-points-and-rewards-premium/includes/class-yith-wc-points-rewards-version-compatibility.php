<?php
/**
 * Manage plugin versions compatibility
 *
 * @class   YITH_WC_Points_Rewards
 * @since   2.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Version_Compatibility' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Version_Compatibility
	 */
	class YITH_WC_Points_Rewards_Version_Compatibility {
		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards
		 */
		protected static $instance;


		/**
		 * Number of items to process
		 *
		 * @var int
		 */
		protected $number_of_items_to_process = 20;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Version_Compatibility
		 * @since 2.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize version compatibility class
		 *
		 * @since  2.0.0
		 */
		public function __construct() {
			add_action( 'ywpar_3_0_update_categories', array( $this, 'import_categories' ), 10, 1 );
			add_action( 'ywpar_3_0_update_products', array( $this, 'import_products' ), 10, 1 );

			$current_option_version = get_option( 'yit_ywpar_option_version' );
			if ( $current_option_version ) {
				if ( version_compare( $current_option_version, YITH_YWPAR_VERSION, '<' ) ) {

					if ( 'processing' === get_option( 'ywpar_3_0_0_import_status' ) ) {
						if ( is_admin() ) {
							add_action( 'admin_notices', array( $this, 'add_porting_notice' ) );
						}
					}

					// before version 2.0.0.
					if ( version_compare( get_option( 'ywpar_db_version', 0 ), '2.0.0', '<' ) ) {
						$this->compatibility_to_version_200();
					}

					if ( version_compare( get_option( 'ywpar_db_version', 0 ), '2.0.4', '<' ) ) {
						$this->extra_points_to_multicurrency_support();
					}

					if ( version_compare( get_option( 'ywpar_db_version', 0 ), '3.0.0', '<' ) ) {
						add_action( 'admin_init', array( $this, 'porting_to_3_0' ), 30 );
					}
				}
			} else {
				update_option( 'yit_ywpar_option_version', YITH_YWPAR_VERSION );
			}
		}

		/**
		 * Print the porting notice
		 */
		public function add_porting_notice() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ( isset( $_GET['page']) && 'yith_woocommerce_points_and_rewards' === $_GET['page'] ) ) { //phpcs:ignore
				?>
					<div id="message" class="notice notice-info">
						<div class="ywpar_processing_porting">
							<div class="ywpar_processing_porting_icon"><img src="<?php echo esc_url( YITH_YWPAR_ASSETS_URL . '/images/spinner.gif' ); ?>" /></div>
							<div class="ywpar_processing_porting_content">
								<p>
									<strong><?php esc_html_e( 'Update of YITH WooCommerce Points and Rewards: we are regenerating the points rules.', 'yith-woocommerce-points-and-rewards' ); ?></strong>
								</p>

								<p>
									<?php esc_html_e( "The process is automatic, you will not lose any data and it will only take a few minutes. Don't worry if something looks weird for now, please wait.", 'yith-woocommerce-points-and-rewards' ); ?>
								</p>
							</div>
						</div>

					</div>
					<?php
			}
		}
		/**
		 * Add supports for multicurrency in extra points section
		 *
		 * @since  2.0.4
		 */
		public function extra_points_to_multicurrency_support() {
			$cart__threshold_options = get_option( 'ywpar_checkout_threshold_exp', '' );
			$cart_totals_options     = get_option( 'ywpar_amount_spent_exp', '' );

			if ( ! empty( $cart__threshold_options ) ) {
				$new_list                = array( 'list' => array() );
				$cart__threshold_options = $cart__threshold_options['list'];

				foreach ( $cart__threshold_options as $top => $l ) {
					$new_list['list'][] = array( get_woocommerce_currency() => $l );
				}

				update_option( 'ywpar_checkout_threshold_exp', $new_list );
			}

			if ( ! empty( $cart_totals_options ) ) {
				$new_list            = array( 'list' => array() );
				$cart_totals_options = $cart_totals_options['list'];

				foreach ( $cart_totals_options as $top => $l ) {
					$new_list['list'][] = array( get_woocommerce_currency() => $l );
				}

				update_option( 'ywpar_amount_spent_exp', $new_list );
			}

			update_option( 'ywpar_db_version', '2.0.4' );

		}

		/**
		 * Add the compatibility to the version 2.0.0
		 */
		private function compatibility_to_version_200() {

			update_option( 'ywpar_db_version', '2.0.0' );

			/* Assign Points to option in Points Options > Standard Points */
			$ywpar_user_role_enabled_type_value = get_option( 'ywpar_user_role_enabled', 'all' );
			if ( empty( $ywpar_user_role_enabled_type_value ) || ( is_array( $ywpar_user_role_enabled_type_value ) && count( $ywpar_user_role_enabled_type_value ) > 0 && in_array( 'all', $ywpar_user_role_enabled_type_value, true ) ) ) {
				update_option( 'ywpar_user_role_enabled_type', 'all' );
			} else {
				update_option( 'ywpar_user_role_enabled_type', 'roles' );
			}

			/* Expiration field option in Points Options > Standard Points from int to array */
			$exp_days     = get_option( 'ywpar_days_before_expiration', 0 );
			$expire_value = array(
				'number' => $exp_days,
				'time'   => 'days',
			);

			update_option( 'ywpar_days_before_expiration', maybe_serialize( $expire_value ) );

			if ( '' === $exp_days || 0 === intval( $exp_days ) ) {
				update_option( 'ywpar_enable_expiration_point', 'no' );
			}

			/* User that can redeem points option in Points Options > Standard Points */
			$ywpar_user_role_redeem_enabled_type_value = get_option( 'ywpar_user_role_redeem_enabled', 'all' );
			if ( empty( $ywpar_user_role_redeem_enabled_type_value ) || ( is_array( $ywpar_user_role_redeem_enabled_type_value ) && count( $ywpar_user_role_redeem_enabled_type_value ) > 0 && in_array( 'all', $ywpar_user_role_redeem_enabled_type_value, true ) ) ) {
				update_option( 'ywpar_user_role_redeem_type', 'all' );
			} else {
				update_option( 'ywpar_user_role_redeem_type', 'roles' );
			}

			/* Apply Redeeming Restrictions option */
			$apply_redeem_restrictions = 'yes';
			if ( '' === get_option( 'ywpar_max_points_discount', '' ) && '' === get_option( 'ywpar_minimum_amount_to_redeem', '' ) && '' === get_option( 'ywpar_minimum_amount_discount_to_redeem', '' ) && '' === get_option( 'ywpar_max_points_product_discount', '' ) ) {
				$apply_redeem_restrictions = 'no';
			}

			if ( '' === get_option( 'ywpar_max_percentual_discount', '' ) && '' === get_option( 'ywpar_minimum_amount_to_redeem', '' ) ) {
				$apply_redeem_restrictions = 'no';
			}
			update_option( 'ywpar_apply_redeem_restrictions', $apply_redeem_restrictions );

			update_option( 'ywpar_enabled_rewards_cart_message_layout_style', 'default' );
			if ( get_option( 'ywpar_enabled_rewards_cart_message' ) === 'yes' ) {
				/* use old style for reward message */
				update_option( 'ywpar_enabled_rewards_cart_message_layout_style', 'custom' );
			}
		}

		/**
		 * Start the schedule to move the category and product options to the new rule list.
		 *
		 * @since 3.0.0
		 */
		public function porting_to_3_0() {

			if ( 'yes' === get_option( 'earning_rule_for_role_created' ) ) {
				return;
			}
			if ( 'yes' === ywpar_get_option( 'enable_conversion_rate_for_role', 'no' ) ) {
				self::create_earning_rule_for_role();
			}

			if ( 'yes' === ywpar_get_option( 'rewards_points_for_role', 'no' ) ) {
				self::create_redeeming_rule_for_role();
			}

			$hook = 'ywpar_3_0_update_categories';
			$args = array( 'offset' => 0 );
			if ( ! as_next_scheduled_action( $hook, $args ) ) {
				as_schedule_single_action( time() + 10, $hook, $args, 'ywpar_update_3_0' );
			}
			update_option( 'ywpar_3_0_0_import_status', 'processing' );
			update_option( 'earning_rule_for_role_created', 'yes' );
			update_option( 'ywpar_db_version', '3.0.0' );
			update_option( 'yit_ywpar_option_version', '3.0.0' );

		}

		/**
		 * Handle the scheduled hook to process the category rules
		 *
		 * @param int $offset Offset.
		 */
		public function import_categories( $offset ) {

			$categories = get_terms(
				array(
					'taxonomy' => 'product_cat',
					'offset'   => $offset,
					'number'   => $this->number_of_items_to_process,
				)
			);

			if ( $categories ) {
				foreach ( $categories as $category ) {
					$this->process_category( $category );
				}

				$hook = 'ywpar_3_0_update_categories';
				$args = array( 'offset' => (int) $offset + $this->number_of_items_to_process );

				if ( ! as_next_scheduled_action( $hook, $args ) ) {
					as_schedule_single_action( time() + 10, $hook, $args, 'ywpar_update_3_0' );
				}
			} else {
				$hook = 'ywpar_3_0_update_products';
				$args = array( 'offset' => 0 );
				if ( ! as_next_scheduled_action( $hook, $args ) ) {
					as_schedule_single_action( time() + 10, $hook, $args, 'ywpar_update_3_0' );
				}
			}
		}

		/**
		 * Process a category
		 *
		 * @param WP_Term $category Category to process.
		 */
		private function process_category( $category ) {

			$cat_override_enabled = get_term_meta( $category->term_id, 'ywpar_override_global_product_category', true );

			if ( 'yes' === $cat_override_enabled ) {

				$args = array(
					'object_id'     => $category->term_id,
					'apply_to'      => 'selected_categories',
					'type'          => get_term_meta( $category->term_id, 'ywpar_earning_type_product_category', true ),
					'point_earned'  => '',
					'is_scheduled'  => 'scheduled' === get_term_meta( $category->term_id, 'ywpar_earning_schedule_product_category', true ) ? 'yes' : 'no',
					'schedule_from' => '',
					'schedule_to'   => '',
				);

				if ( 'not_assign' !== $args['type'] ) {
					$args['point_earned'] = get_term_meta( $category->term_id, 'point_earned', true );
				}

				if ( 'yes' !== $args['is_scheduled'] ) {
					$args['schedule_from'] = get_term_meta( $category->term_id, 'point_earned_dates_from', true );
					$args['schedule_to']   = get_term_meta( $category->term_id, 'point_earned_dates_to', true );
				}

				$rule_existent = YITH_WC_Points_Rewards_Helper::search_earning_rule( $args );

				if ( $rule_existent ) {
					if ( ! in_array( $category->term_id, (array) $rule_existent->get_apply_to_categories_list() ) ) { //phpcs:ignore
						$rule_existent->add_category_to_the_list( $category->term_id );
						$rule_existent->save();
					}
				} else {
					self::create_earnig_rule( $category->name, $args );
				}
			}

			$cat_override_discount_enabled = get_term_meta( $category->term_id, 'ywpar_override_global_discount_product_category', true );
			$cat_discount_type             = get_term_meta( $category->term_id, 'ywpar_redeem_type_product_category', true );
			if ( 'yes' === $cat_override_discount_enabled && 'no_limit' !== $cat_discount_type ) {
				$args = array(
					'object_id'         => $category->term_id,
					'apply_to'          => 'selected_categories',
					'type'              => 'max_discount',
					'max_discount_type' => $cat_discount_type,
					'max_discount'      => (int) get_term_meta( $category->term_id, 'max_point_discount', true ),
				);

				$rule_existent = YITH_WC_Points_Rewards_Helper::search_redeeming_rule( $args );

				if ( $rule_existent ) {
					if ( ! in_array( $category->term_id, (array) $rule_existent->get_apply_to_categories_list() ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$rule_existent->add_category_to_the_list( $category->term_id );
						$rule_existent->save();
					}
				} else {
					self::create_redeeming_rule( $category->name, $args );
				}
			}
		}

		/**
		 * Create a new Earning Rule
		 *
		 * @param string $name Name of rule.
		 * @param array  $args Arguments to set.
		 */
		public static function create_earnig_rule( $name, $args ) {
			$new_post = array(
				'post_status' => 'publish',
				'post_type'   => YITH_WC_Points_Rewards_Post_Types::$earning_rule,
				'post_title'  => $name . ' - ' . esc_html_x( 'Imported rule', 'yith-woocommerce-points-and-rewards' ),
			);

			$new_post_id = wp_insert_post( $new_post );
			$new_rule    = ywpar_get_earning_rule( $new_post_id );
			$new_rule->set_name( $name . ' - ' . esc_html_x( 'Imported rule', 'yith-woocommerce-points-and-rewards' ) );
			$new_rule->set_points_type_conversion( $args['type'] );

			if ( isset( $args['point_earned'] ) ) {
				if ( 'fixed' === $args['type'] ) {
					$new_rule->set_fixed_points_to_earn( $args['point_earned'] );
				} else {
					$new_rule->set_percentage_points_to_earn( $args['point_earned'] );
				}
			}

			$new_rule->set_apply_to( $args['apply_to'] );

			if ( 'selected_categories' === $args['apply_to'] ) {
				$new_rule->set_apply_to_categories_list( array( $args['object_id'] ) );
			}

			if ( 'selected_products' === $args['apply_to'] ) {
				$new_rule->set_apply_to_products_list( array( $args['object_id'] ) );
			}

			$new_rule->set_is_rule_scheduled( $args['is_scheduled'] );

			if ( 'yes' === $args['is_scheduled'] ) {
				$new_rule->set_rule_schedule(
					array(
						'from' => ! empty( $args['schedule_from'] ) ? gmdate( 'Y-m-d', $args['schedule_from'] ) : '',
						'to'   => ! empty( $args['schedule_to'] ) ? gmdate( 'Y-m-d', $args['schedule_to'] ) : '',
					)
				);
			}

			if ( isset( $args['earn_points_conversion_rate'] ) ) {
				$new_rule->set_earn_points_conversion_rate( $args['earn_points_conversion_rate'] );
			}

			$global_role_setting = ywpar_get_option( 'user_role_enabled_type', 'all' );

			if ( 'all' === $global_role_setting ) {
				if ( isset( $args['user_roles_list'] ) ) {
					$new_rule->set_user_type( 'roles' );
					$new_rule->set_user_roles_list( $args['user_roles_list'] );
				} else {
					$new_rule->set_user_type( 'all' );
				}
			} else {
				$user_roles = ywpar_get_option( 'user_role_enabled', array() );
				$user_list  = isset( $args['user_roles_list'] ) ? $args['user_roles_list'] : $user_roles;
				$new_rule->set_user_type( $user_list );
			}

			$new_rule->save();
		}

		/**
		 * Create a new Redeeming Rule
		 *
		 * @param string $name Name of rule.
		 * @param array  $args Arguments to set.
		 */
		public static function create_redeeming_rule( $name, $args ) {

			$new_post = array(
				'post_status' => 'publish',
				'post_type'   => YITH_WC_Points_Rewards_Post_Types::$redeeming_rule,
				'post_title'  => $name . ' - ' . esc_html_x( 'Imported rule', 'yith-woocommerce-points-and-rewards' ),
			);

			$new_post_id = wp_insert_post( $new_post );
			$new_rule    = ywpar_get_redeeming_rule( $new_post_id );

			$new_rule->set_name( $name . ' - ' . esc_html_x( 'Imported rule', 'yith-woocommerce-points-and-rewards' ) );
			$new_rule->set_type( $args['type'] );

			if ( 'max_discount' === $args['type'] && isset( $args['max_discount_type'] ) ) {
				$new_rule->set_maximum_discount_type( $args['max_discount_type'] );
				if ( 'fixed' === $args['max_discount_type'] ) {
					$new_rule->set_max_discount( $args['max_discount'] );

				} else {
					$new_rule->set_max_discount_percentage( $args['max_discount'] );
				}
			}

			if ( 'conversion_rate' === $args['type'] ) {
				if ( isset( $args['conversion_rate'] ) ) {
					$new_rule->set_conversion_rate( $args['conversion_rate'] );
				}

				if ( isset( $args['percentage_conversion_rate'] ) ) {
					$new_rule->set_percentage_conversion_rate( $args['percentage_conversion_rate'] );
				}
			}
			$new_rule->set_apply_to( $args['apply_to'] );

			if ( 'selected_categories' === $args['apply_to'] ) {
				$new_rule->set_apply_to_categories_list( array( $args['object_id'] ) );
			}

			if ( 'selected_products' === $args['apply_to'] ) {
				$new_rule->set_apply_to_products_list( array( $args['object_id'] ) );
			}

			$global_role_setting = ywpar_get_option( 'user_role_redeem_type', 'all' );

			if ( 'all' === $global_role_setting ) {
				if ( isset( $args['user_roles_list'] ) ) {
					$new_rule->set_user_type( 'roles' );
					$new_rule->set_user_roles_list( $args['user_roles_list'] );
				} else {
					$new_rule->set_user_type( 'all' );
				}
			} else {
				$user_roles = ywpar_get_option( 'user_role_redeem_enabled', array() );
				$user_list  = isset( $args['user_roles_list'] ) ? $args['user_roles_list'] : $user_roles;
				$new_rule->set_user_type( $user_list );
			}

			$new_rule->save();
		}

		/**
		 * Handle the scheduled hook to process the category rules
		 *
		 * @param int $offset Offset.
		 */
		public function import_products( $offset ) {

			$products = wc_get_products(
				array(
					'limit'  => $this->number_of_items_to_process,
					'offset' => $offset,
				)
			);

			if ( $products ) {
				foreach ( $products as $product ) {
					$this->process_product( $product );
				}

				$hook = 'ywpar_3_0_update_products';
				$args = array( 'offset' => (int) $offset + $this->number_of_items_to_process );

				if ( ! as_next_scheduled_action( $hook, $args ) ) {
					as_schedule_single_action( time() + 10, $hook, $args, 'ywpar_update_3_0' );
				}
			} else {
				delete_option( 'ywpar_3_0_0_import_status' );
			}
		}


		/**
		 * Process a product
		 *
		 * @param WC_Product $product Product to process.
		 */
		private function process_product( $product ) {

			if ( $product->is_type( 'variable' ) ) {
				$variations = $product->get_available_variations();
				if ( $variations ) {
					foreach ( $variations as $variation ) {
						$variation = wc_get_product( $variation['variation_id'] );
						$this->process_product( $variation );
					}
				}
			}
			$override_enabled = $product->get_meta( '_ywpar_override_points_earning' );

			if ( 'yes' === $override_enabled ) {
				$args = array(
					'object_id'     => $product->get_id(),
					'apply_to'      => 'selected_products',
					'type'          => $product->get_meta( '_ywpar_fixed_or_percentage' ),
					'point_earned'  => '',
					'is_scheduled'  => $product->get_meta( '_ywpar_override_points_date' ),
					'schedule_from' => '',
					'schedule_to'   => '',
				);

				if ( 'not_assign' !== $args['type'] ) {
					$args['point_earned'] = $product->get_meta( '_ywpar_point_earned' );
				}

				if ( 'yes' === $args['is_scheduled'] ) {
					$args['schedule_from'] = $product->get_meta( '_ywpar_point_earned_dates_from' );
					$args['schedule_to']   = $product->get_meta( '_ywpar_point_earned_dates_to' );
				}

				$rule_existent = YITH_WC_Points_Rewards_Helper::search_earning_rule( $args );

				if ( $rule_existent ) {
					if ( ! in_array( $product->get_id(), (array) $rule_existent->get_apply_to_products_list() ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$rule_existent->add_product_to_the_list( $product->get_id() );
						$rule_existent->save();
					}
				} else {
					self::create_earnig_rule( $product->get_name(), $args );
				}
			}

			$override_max_discount_enabled = $product->get_meta( '_ywpar_override_maximum_discount' );
			$max_discount_type             = $product->get_meta( '_ywpar_maximum_discount_type' );
			if ( 'yes' === $override_max_discount_enabled && 'no_limit' !== $max_discount_type ) {

				$args = array(
					'object_id'         => $product->get_id(),
					'apply_to'          => 'selected_products',
					'type'              => 'max_discount',
					'max_discount_type' => $max_discount_type,
					'max_discount'      => (int) $product->get_meta( '_ywpar_max_point_discount' ),
				);

				$rule_existent = YITH_WC_Points_Rewards_Helper::search_redeeming_rule( $args );

				if ( $rule_existent ) {

					if ( ! in_array( $product->get_id(), (array) $rule_existent->get_apply_to_products_list() ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$rule_existent->add_product_to_the_list( $product->get_id() );
						$rule_existent->save();
					}
				} else {
					self::create_redeeming_rule( $product->get_name(), $args );
				}
			}

		}


		/**
		 * Create earning rule for specific role
		 */
		protected static function create_earning_rule_for_role() {

			$role_conversion_rates = ywpar_get_option( 'earn_points_role_conversion_rate', array() );

			if ( isset( $role_conversion_rates['role_conversion'] ) ) {
				foreach ( $role_conversion_rates['role_conversion'] as $rate ) {
					$role = $rate['role'];
					unset( $rate['role'] );
					$args = array(
						'apply_to'                    => 'all_products',
						'type'                        => 'override',
						'earn_points_conversion_rate' => $rate,
						'is_scheduled'                => 'no',
						'user_role_enabled_type'      => 'roles',
						'user_roles_list'             => array( $role ),
					);

					$rule_existent = YITH_WC_Points_Rewards_Helper::search_earning_rule( $args );

					if ( ! $rule_existent ) {
						self::create_earnig_rule( ucfirst( $role ), $args );
					} else {
						$rule_existent->add_role_to_the_list( $args['user_roles_list'][0] );
						$rule_existent->save();
					}
				}
			}

		}

		/**
		 * Create earning rule for specific role
		 */
		protected static function create_redeeming_rule_for_role() {

			$conversion_method = yith_points()->redeeming->get_conversion_method();

			if ( 'fixed' === $conversion_method ) {
				$role_conversion_rates = ywpar_get_option( 'rewards_points_role_rewards_fixed_conversion_rate', array() );
			} else {
				$role_conversion_rates = ywpar_get_option( 'rewards_points_role_rewards_percentage_conversion_rate', array() );
			}

			$label_conversion_rate = 'fixed' === $conversion_method ? 'conversion_rate' : 'percentage_conversion_rate';

			if ( isset( $role_conversion_rates['role_conversion'] ) ) {
				foreach ( $role_conversion_rates['role_conversion'] as $rate ) {
					$role = $rate['role'];
					unset( $rate['role'] );

					$args = array(
						'apply_to'               => 'all_products',
						'type'                   => 'conversion_rate',
						$label_conversion_rate   => $rate,
						'user_role_enabled_type' => 'roles',
						'user_roles_list'        => array( $role ),
					);

					$rule_existent = YITH_WC_Points_Rewards_Helper::search_redeeming_rule( $args );

					if ( ! $rule_existent ) {
						self::create_redeeming_rule( ucfirst( $role ), $args );
					} elseif ( isset( $args['user_roles_list'][0] ) ) {
						$rule_existent->add_role_to_the_list( $args['user_roles_list'][0] );
						$rule_existent->save();
					}
				}
			}

		}
	}



}


if ( ! function_exists( 'YITH_WC_Points_Rewards_Version_Compatibility' ) ) {
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards class
	 *
	 * @return YITH_WC_Points_Rewards_Version_Compatibility
	 */
	function YITH_WC_Points_Rewards_Version_Compatibility() { //phpcs:ignore
		return YITH_WC_Points_Rewards_Version_Compatibility::get_instance();
	}
}
