<?php
/**
 * WC Blocks Integration
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 **/

defined( 'YITH_YWPAR_VERSION' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Blocks' ) ) {
	/**
	 * WC Blocks Integration
	 *
	 * @class       YITH_WC_Points_Rewards_Blocks
	 * @package     YITH WooCommerce Points and Rewards
	 * @author      YITH <plugins@yithemes.com>
	 * @since       3.24.0
	 */
	class YITH_WC_Points_Rewards_Blocks {
		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Blocks
		 */
		protected static $instance;

		protected $frontend;

		/**
		 * Custom blocks
		 *
		 * @var array
		 */
		public $blocks = array(
			'yith/ywpar-cart-points-message',
			'yith/ywpar-rewards-points-message',
		);

		/**
		 * Get Class Main Instance
		 *
		 * @return YITH_WC_Points_Rewards_Blocks
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}
		
		/**
		 * Constructor
		 *
		 */
		private function __construct() {

			add_filter( 'block_categories_all', array( $this, 'block_category' ), 100, 2 );

			/* register blocks */
			add_action( 'init', array( $this, 'register_blocks' ), 99 );

			if ( ! is_admin() ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'frontend_assets' ), 10 );
			}

			//add_filter( 'ywpar_single_product_page_positions', array( $this, 'add_block_position_to_settings' ) );
		}

		/**
		 * add use as block option to Positions options for Single Product Page points message.
		 * 
		 * @param array $positions list of positions available.
		 */
		public function add_block_position_to_settings( $positions ) {
			$block_position = array(
				'useBlock' => esc_html__( 'Use Shortcode/Block', 'yith-woocommerce-points-and-rewards' ),
			);
			return array_merge( $positions, $block_position );
		}
		/**
		 * load blocks assets for frontend
		 */
		public function frontend_assets() {
			global $post;

			/**
			 * APPLY_FILTERS: ywapr_force_blocks_scripts_frontend
			 *
			 * Force to load blocks frontend script.
			 *
			 * @param bool
			 */
			if ( ( is_cart() && has_block( 'woocommerce/cart', $post ) ) || ( is_checkout() &&  has_block( 'woocommerce/checkout', $post ) ) || apply_filters( 'ywapr_force_blocks_scripts_frontend', false ) ) {
				$asset_file = include YITH_YWPAR_DIR . 'assets/js/blocks/frontend_blocks.asset.php';
				$deps       = array_merge( $asset_file['dependencies'] );
				wp_register_script( 'ywpar-blocks-cart-points-message', YITH_YWPAR_ASSETS_URL . '/js/blocks/frontend_blocks.js', $deps, YITH_YWPAR_VERSION, false );
				wp_enqueue_script( 'ywpar-blocks-cart-points-message' );
				wp_localize_script( 'ywpar-blocks-cart-points-message', 'ywpar_messages_settings', array( 'wc_ajax_url' => WC_AJAX::get_endpoint( '%%endpoint%%' ) ) );
			}

		}

		/**
		 * Register blocks
		 *
		 * @return void
		 */
		public function register_blocks() {
			/**
			 * APPLY_FILTERS: ywpar_load_blocks
			 * 
			 * load blocks
			 * 
			 * @param boolean true default value
			 */
			if ( !is_admin() || !yith_plugin_fw_is_gutenberg_enabled() || !apply_filters( 'ywpar_load_blocks', true ) ) {
				return;
			}
	
			$asset_file = include YITH_YWPAR_DIR . 'assets/js/blocks/blocks.asset.php';
			$deps       = array_merge( $asset_file['dependencies'] );
			wp_register_script( 'ywpar-blocks', YITH_YWPAR_ASSETS_URL . '/js/blocks/blocks.js', $deps, YITH_YWPAR_VERSION, false );
			wp_register_style( 'ywpar-blocks-style-editor', YITH_YWPAR_ASSETS_URL . '/js/blocks/editor.css', array(), YITH_YWPAR_VERSION );
			wp_register_style( 'ywpar-blocks-style', YITH_YWPAR_ASSETS_URL . '/js/blocks/style.css', array(), YITH_YWPAR_VERSION );
			
			$assets = array(
				'script'       => 'ywpar-blocks',
				'editor_style' => 'ywpar-blocks-style-editor',
				'style'        => 'ywpar-blocks-style',
			);

			foreach ( $this->blocks as $block ) {		
				register_block_type( $block, $assets );
			}

			/* get messages texts to use as dummy values in blocks */
			$settings = array(
				'points_message_on_cart'     => get_option( 'ywpar_cart_message', '' ),
				'points_message_on_checkout' => get_option( 'ywpar_checkout_message', '' ),
				'rewards_message'            =>  $this->get_rewards_message_for_blocks_editor_side(),
				'rewards_layout_type'        => 'default' === ywpar_get_option( 'enabled_rewards_cart_message_layout_style' ) ? 'default-layout' : '',
			);
		
			wp_localize_script( 'ywpar-blocks', 'ywpar_blocks_settings', $settings );

			if ( function_exists( 'wp_set_script_translations' ) ) {
				wp_set_script_translations( 'ywpar-blocks', 'yith-woocommerce-points-and-rewards', YITH_YWPAR_DIR . 'languages' );
			}

			
		}

		/**
		 * Get a dummy reward message for block in editor side
		 */
		public function get_rewards_message_for_blocks_editor_side() {
			$message		   = '';
			$conversion_method = yith_points()->redeeming->get_conversion_method();
			$plural            = ywpar_get_option( 'points_label_plural' );

			if ( 'default' === ywpar_get_option( 'enabled_rewards_cart_message_layout_style' ) ) {
				if ( 'fixed' === $conversion_method ) {
					$points_field = '<span><input type="text" min="100" name="ywpar_input_points" class="input-text"  id="ywpar-points-max" value="100"></span>';
					$message      = '<div class="ywpar_apply_discounts">' . apply_filters( 'ywpar_reward_message_format', esc_html__( 'You have {points} {points_label}. Use {points_field} {points_label} to get a discount of {max_discount} on this order.', 'yith-woocommerce-points-and-rewards' ) );
					$message  = str_replace( '{points_label}', '<strong>' . $plural  . '</strong>', $message );
					$message  = str_replace( '{max_discount}', '<strong>' . wc_price( 10 ) . '</strong>', $message );
					$message  = str_replace( '{points}', '<strong>100</strong>', $message );
					$message  = str_replace( '{points_field}', $points_field, $message );
					$message .= ' <span><button type="submit" class="button ywpar_apply_discounts ywpar-fixed-discount" name="ywpar_apply_discounts" id="ywpar_apply_discounts">' . ywpar_get_option( 'label_apply_discounts' ) . '</button></span></div>';	
				} else {
					$message = apply_filters( 'ywpar_reward_percentage_message_format', esc_html__( 'Use {points} {points_label} for a {max_discount} discount on this order!', 'yith-woocommerce-points-and-rewards' ) );
					$message = str_replace( '{points_label}', '<strong>' . $plural . '</strong>', $message );
					$message = str_replace( '{max_discount}', '<strong>' . wc_price( 100 ) . '</strong>', $message );
					$message = str_replace( '{max_percentual_discount}', '100%', $message );
					$message = str_replace( '{points}', '<strong> 100</strong>', $message );
	
					$message .= ' <a title="' . esc_attr( ywpar_get_option( 'label_apply_discounts' ) ) . '" href="#" class="ywpar-button-message ywpar-button-percentage-discount">' . ywpar_get_option( 'label_apply_discounts' ) . '</a>';
					$message .= '<div class="ywpar_apply_discounts_container"></div>';
				}
			} else {
				$message = ywpar_get_option( 'rewards_cart_message' );
				if ( 'fixed' === $conversion_method ) {
					$plural  = ywpar_get_option( 'points_label_plural' );
					$message                 = str_replace( '{points_label}', $plural, $message );
					$message                 = str_replace( '{max_discount}', wc_price( apply_filters( 'ywpar_max_discount_redeem_message', 100, 'custom-layout' ) ), $message );
					$message                 = str_replace( '{points}', 50, $message );
					$message                .= ' <a class="ywpar-button-message" style="pointer-events: none" href="#" title="' . ywpar_get_option( 'label_apply_discounts' ) . '">' . ywpar_get_option( 'label_apply_discounts' ) . '</a>';
				} else {
					$message  = str_replace( '{points_label}', $plural, $message );
					$message  = str_replace( '{max_discount}', wc_price( 100 ), $message );
					$message  = str_replace( '{max_percentual_discount}', '100%', $message );
					$message  = str_replace( '{points}', 100, $message );
					$message .= ' <a class="ywpar-button-message ywpar-button-percentage-discount" href="#" title="' . esc_attr( ywpar_get_option( 'label_apply_discounts' ) ) . '">' . ywpar_get_option( 'label_apply_discounts' ) . '</a>';
					$message .= '<div class="ywpar_apply_discounts_container"></div>';
				}
			}
			return $message;
		}

		/**
		 * Add block category
		 *
		 * @param array   $categories Array block categories array.
		 * @param WP_Post $post Current post.
		 *
		 * @return array block categories
		 */
		public function block_category( $categories, $post ) {

			$found_key = array_search( 'yith-blocks', array_column( $categories, 'slug' ), true );

			if ( ! $found_key ) {
				$categories[] = array(
					'slug'  => 'yith-blocks',
					'title' => _x( 'YITH', '[gutenberg]: Category Name', 'yith-plugin-fw' ),
				);
			}

			return $categories;
		}
	}
	
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards_Blocks class
	 *
	 * @return YITH_WC_Points_Rewards_Blocks
	 */
	function YITH_WC_Points_Rewards_Blocks() {
		return YITH_WC_Points_Rewards_Blocks::get_instance();
	}
}