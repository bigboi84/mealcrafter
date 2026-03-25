<?php
/**
 * WC Blocks Integration
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 **/
use Automattic\WooCommerce\StoreApi\Schemas\V1\CheckoutSchema;

defined( 'YITH_YWPAR_VERSION' ) || exit;

if ( ! class_exists( 'YITH_WC_Checkout_Blocks' ) ) {
	/**
	 * WC Blocks Integration
	 *
	 * @class       YITH_WC_Checkout_Blocks
	 * @package     YITH WooCommerce Points and Rewards
	 * @author      YITH <plugins@yithemes.com>
	 * @since       3.24.0
	 */
	class YITH_WC_Checkout_Blocks {
		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Checkout_Blocks
		 */
		protected static $instance;

		/**
		 * Get Class Main Instance
		 *
		 * @return YITH_WC_Checkout_Blocks
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}
		
		/**
		 * Constructor
		 */
		private function __construct() {
			/* execute integration only if the related option is set */
			if ( get_option( 'ywpar_enable_points_on_birthday_exp', 'no' ) === 'yes' && in_array( 'checkout', get_option( 'ywpar_birthday_date_field_where', array() ) ) ) {
				/* checkout block integraton */
				add_action( 'woocommerce_blocks_loaded', array( $this, 'add_plugin_blocks' ) );
				add_filter(
					'__experimental_woocommerce_blocks_add_data_attributes_to_block',
					array( $this, 'add_birthday_field_block_data_attributes'),
				);
			}
		}

		/**
		 * Add Plugin Blocks to WooCommerce blocks
		 */
		public function add_plugin_blocks() {

			/* if the current user just have a birthdate do not show the birtdate field */
			$birthdate = '';
			if ( is_user_logged_in() ) {
				$birthdate = get_user_meta( get_current_user_id(), 'yith_birthday', true );
				if ( empty( $birthdate ) ) {
					$birthdate = get_user_meta( get_current_user_id(), 'ywces_birthday', true );
	
					if ( ! empty( $birthdate ) ) {
						update_user_meta( get_current_user_id(), 'yith_birthday', $birthdate );
					}
				}
			} else {
				/* if not logged show the datepicket in case the option to create account in checkout is active */
				$birthdate = 'yes' === get_option( 'woocommerce_enable_signup_and_login_from_checkout', 'no' ) ? '' : 'notshow';
			}

			if ( ! empty( $birthdate ) && ! is_admin() ) {
				return;
			}

			add_action(
				'woocommerce_blocks_checkout_block_registration',
				array( $this, 'register_checkout_blocks' )
			);

			/* register birthdate endpoint so it will be avalable in api request */
			woocommerce_store_api_register_endpoint_data(
				array(
					'endpoint'        => CheckoutSchema::IDENTIFIER,
					'namespace'       => 'ywpar/birthdate-block',
					'data_callback'   => array( $this, 'data_callback' ),
					'schema_callback' => array( $this, 'schema_callback' ),
					'schema_type'     => ARRAY_A,
				)
			);

			/* save birtdate on api call */
			add_action( 'woocommerce_store_api_checkout_update_customer_from_request', array( $this, 'save_birthdate' ), 10, 2 );

		}

		/**
		 * Save birthday data to customer on api checkout_update_customer_from_request
		 * 
		 * @param \WP_REST_Request $request Full details about the request.
		 * @param WC_Customer $customer wc custoemr object.
		 */
		public function save_birthdate( $customer, $request ) {
			if ( ! empty( $request['extensions']['ywpar/birthdate-block']['ywpar_birthday'] ) ) {
				$birth_date    = $request['extensions']['ywpar/birthdate-block']['ywpar_birthday'];
				$date_format   = get_option( 'ywpar_birthday_date_format', 'YYYY-MM-DD' );
				$date_formats  = ywpar_get_date_formats();
				
				$date       = DateTime::createFromFormat( $date_formats[ $date_format ], $birth_date );
				$birth_date = $date->format( 'Y-m-d' );
				update_user_meta( $customer->get_id(), 'yith_birthday',  $birth_date );
			}
		}

		/**
		 * Add block schema in WooCommerce
		 *
		 * @return array[]
		 */
		public function schema_callback() {
			return array(
				'ywpar_birthday' => array(
					'type'     => 'string',
					'readonly' => true
				),
			);
		}

		/**
		 * Return the data callback
		 *
		 * @return array
		 */
		public function data_callback() {
			return array();
		}
		/**
		 * Register checkout blocks
		 * 
		 * @param IntegrationRegistry $integration_registry The registry
		 *
		 * @return void
		 */
		public function register_checkout_blocks( $integration_registry ) {
			require_once YITH_YWPAR_INC . '/blocks/checkout-blocks/class-ywpar-checkout-blocks-integration.php';
			$integration_registry->register( new YWPAR_Checkout_Blocks_Integration() );
		}
		
		/**
		 * Render plugin block available to WC block only
		 */
		public function add_birthday_field_block_data_attributes( $allowed_blocks ) {
			$allowed_blocks[] = 'ywpar/birthdate-block';
			return $allowed_blocks;
		}

	}
	
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards_Blocks class
	 *
	 * @return YITH_WC_Points_Rewards_Blocks
	 */
	function YITH_WC_Checkout_Blocks() {
		return YITH_WC_Checkout_Blocks::get_instance();
	}
}