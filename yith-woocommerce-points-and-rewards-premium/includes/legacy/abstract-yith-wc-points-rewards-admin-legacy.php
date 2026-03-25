<?php //phpcs:ignore phpcs: WordPress.Files.FileName.InvalidClassFileName.
/**
 * YITH_WC_Points_Rewards_Admin_Legacy Legacy Abstract Class.
 *
 * @class   YITH_WC_Points_Rewards_Admin_Legacy
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Admin_Legacy' ) ) {
	/**
	 * Class YITH_WC_Points_Rewards_Admin_Legacy
	 */
	abstract class YITH_WC_Points_Rewards_Admin_Legacy {

		/**
		 * Register plugins for activation tab
		 *
		 * @return void
		 * @since  1.0.0
		 * @deprecated 3.0.0
		 */
		public function register_plugin_for_activation() {
			_deprecated_function( __FUNCTION__, '3.0.0', 'yith_points()->register_plugin_for_activation()' );
			yith_points()->register_plugin_for_activation();
		}

		/**
		 * Register plugins for update tab
		 *
		 * @return void
		 * @deprecated 3.0.0
		 * @since  1.0.0
		 */
		public function register_plugin_for_updates() {
			_deprecated_function( __FUNCTION__, '3.0.0', 'yith_points()->register_plugin_for_updates()' );
			yith_points()->register_plugin_for_updates();
		}

		/**
		 * Add customer birthday field
		 *
		 * @since   1.1.3
		 * @param   WP_user $user User.
		 *
		 * @deprecated 3.0.0
		 * @return  void
		 * @throws Exception Throws Exception.
		 */
		public function add_birthday_field_admin( $user ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::add_birthday_field_admin', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points_Birthdate::add_birthday_field_admin' );
			YITH_WC_Points_Rewards_Extra_Points_Birthdate::add_birthday_field_admin( $user );
		}

		/**
		 * Save customer birthdate from admin page
		 *
		 * @param  int $customer_id Customer id.
		 * @deprecated 3.0.0
		 * @return  void
		 */
		public function save_birthday_field_admin( $customer_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::save_birthday_field_admin', '3.0.0', 'YITH_WC_Points_Rewards_Extra_Points_Birthdate::save_birthdate' );
			YITH_WC_Points_Rewards_Extra_Points_Birthdate::save_birthdate( $customer_id );
		}

		/**
		 * Clean Points Transient by product ID on Product Saved
		 *
		 * @param int $id .
		 *
		 * @since   2.0.1
		 * @deprecated 3.0.0
		 */
		public function delete_transient_on_product_saved( $id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::delete_transient_on_product_saved', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Clean Points Transient by product ID
		 *
		 * @param int $id Product id.
		 * @since   2.0.1
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function clean_points_transient( $id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::clean_points_transient', '3.0.0', 'This method should not be called manually.' );
		}


		/**
		 * Save custom category fields
		 *
		 * @since   1.0.0
		 *
		 * @param int    $term_id Term id.
		 * @param string $tt_id .
		 * @param string $taxonomy Taxonomy.
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function save_category_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::save_category_fields', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Add additional fields in product_cat edit form
		 *
		 * @since   1.0.0
		 *
		 * @param WP_Term $term Term.
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function edit_category_fields( $term ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::edit_category_fields', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Add additional fields in product_cat in add form
		 *
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 */
		public function product_cat_add_form_fields() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::product_cat_add_form_fields', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Add custom fields for single product
		 *
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 * @return  void
		 */
		public function add_custom_fields_for_single_products() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::add_custom_fields_for_single_products', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Save custom fields for single product
		 *
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 *
		 * @param int     $post_id Post id.
		 * @param WP_Post $post Post.
		 *
		 * @return void
		 */
		public function save_custom_fields_for_single_products( $post_id, $post ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::save_custom_fields_for_single_products', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Add custom fields for variation products
		 *
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 *
		 * @param mixed $loop .
		 * @param array $variation_data .
		 * @param array $variations .
		 *
		 * @return void
		 */
		public function add_custom_fields_for_variation_products( $loop, $variation_data, $variations ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::add_custom_fields_for_variation_products', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Save custom fields for variation products
		 *
		 * @since   1.0.0
		 * @deprecated 3.0.0
		 *
		 * @param int $variation_id Variation id.
		 */
		public function save_custom_fields_for_variation_products( $variation_id ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::save_custom_fields_for_variation_products', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Reset points from administrator points
		 *
		 * @return void
		 * @since 1.1.1
		 * @deprecated 3.0.0
		 */
		public function reset_points() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::reset_points', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Enqueue styles and scripts
		 *
		 * @deprecated 3.0.0
		 * @return void
		 * @since 1.0.0
		 */
		public function enqueue_styles_scripts() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::enqueue_styles_scripts', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Manage Bulk Actions
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function bulk_action() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::bulk_action', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Manage Bulk Actions
		 *
		 * @return void
		 * @deprecated 3.0.0
		 */
		public function get_premium_landing_uri() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::get_premium_landing_uri', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Create Menu Items
		 *
		 * Print admin menu items
		 *
		 * @since  1.0.0
		 * @deprecated 3.0.0
		 */
		private function create_menu_items() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::create_menu_items', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Add csv to mime file type
		 *
		 * @since 1.1.3
		 *
		 * @param array $mime_types Mime types.
		 *
		 * @return mixed
		 * @deprecated 3.0.0
		 */
		public function add_mime_types( $mime_types ) {
			$mime_types['csv'] = 'text/csv';
			$mime_types['txt'] = 'text/plain';
			return $mime_types;
		}

		/**
		 * Add a message in my admin if expiration points is enabled
		 *
		 * @since 1.3.0
		 * deprecated 3.0.0
		 */
		public function new_expiration_mode_message() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::new_expiration_mode_message', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Clean Points Transient on Points Tab Settings Updated
		 *
		 * @since   2.0.1
		 * @return void
		 * deprecated 3.0.0
		 */
		public function delete_transient_on_settings_updated() {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::delete_transient_on_settings_updated', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Set the settings tabs enabled to shop manager
		 *
		 * @param array $panel_options Panel options.
		 *
		 * @since 1.1.3
		 * deprecated 3.0.0
		 */
		public function admin_panel_options_for_shop_manager( $panel_options ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::admin_panel_options_for_shop_manager', '3.0.0', 'This method should not be called manually.' );
		}

		/**
		 * Get the value of the custom type field
		 *
		 * @param mixed  $value Value.
		 * @param string $field String.
		 *
		 * @return mixed|void
		 * deprecated 3.0.0
		 */
		public function get_value_of_custom_type_field( $value, $field ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::get_value_of_custom_type_field', '3.0.0', 'This method should not be called manually.' );
			$custom_option_types = array(
				'options-conversion',
				'options-role-conversion',
				'options-percentage-conversion',
				'options-role-percentage-conversion',
				'options-extrapoints',
				'options-bulk-form',
				'options-import-form',
				'points-previous-order',
			);

			if ( isset( $field['type'] ) && in_array( $field['type'], $custom_option_types, true ) ) {
				$value = get_option( $field['id'], $field['default'] );
			}

			return $value;
		}

		/**
		 * Apply Points to Previous Orders
		 *
		 * @param string $from From.
		 * @return  string
		 * deprecated 3.0.0
		 * @since   1.0.0
		 */
		public function apply_previous_order( $from = '' ) {
			_deprecated_function( 'YITH_WC_Points_Rewards_Admin::apply_previous_order', '3.0.0', 'YITH_WC_Points_Rewards_Orders::add_points_to_previous_orders' );
			return yith_points()->redeeming->order->add_points_to_previous_orders( $from );
		}
	}
}


if ( ! function_exists( 'YITH_WC_Points_Rewards_Admin' ) ) {
	/**
	 * Unique access to instance of YITH_WC_Points_Rewards_Admin class
	 *
	 * @deprecated 3.0.0
	 * @since      1.0.0
	 */
	function YITH_WC_Points_Rewards_Admin() {
		_deprecated_function( __FUNCTION__, '3.0.0', 'yith_points()->admin' );
		return yith_points()->admin;
	}
}
