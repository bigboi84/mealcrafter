<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
	/**
	 * Birthday field integration to WooCommerce checkout block
	 *
	 * @class       YWPAR_Checkout_Blocks_Integration
	 * @package     YITH WooCommerce Points and Rewards
	 * @author      YITH <plugins@yithemes.com>
	 * @since       3.24.0
	 */
class YWPAR_Checkout_Blocks_Integration implements IntegrationInterface {

	public function get_name() {
		return 'ywpar/birthdate-block';
	}

	public function initialize() {
		$this->register_birthday_field_scripts();
		if ( ! is_admin() ) {
			$this->register_birthday_field_frontend_scripts();
		}
		
	}

	/**
	 * Register the ui library
	 *
	 * @return void
	 */
	public function register_yith_ui_libray_scripts() {
		$library_packages = array( 'components', 'date', 'styles' );

		foreach ( $library_packages as $package ) {
			$ui_asset_file = YITH_YWPAR_DIR . "assets/js/ui-library/{$package}/index.asset.php";
			if ( file_exists( $ui_asset_file ) ) {
				$asset_info                     = include $ui_asset_file;
				$handle                         = 'ywpar-ui-' . $package;
				$script_asset['dependencies'][] = $handle;
				$src                            = YITH_YWPAR_ASSETS_URL . "/js/ui-library/{$package}/index.js";
				$deps                           = $asset_info['dependencies'] ?? array();
				$version                        = $asset_info['version'] ?? '1.0.0';
				wp_register_script( $handle, $src, $deps, $version, true );
			}
		}

		/* format date from plugin settings */
		$date_format      = get_option( 'ywpar_birthday_date_format', 'yyyy-mm-dd' );
		$date_formats     = ywpar_get_date_formats();

		$date_formats   = array(
			'year'         => 'Y',
			'month'        => 'F',
			'dayOfMonth'   => 'j',
			'monthShort'   => 'M',
			'weekday'      => 'l',
			'weekdayShort' => 'D',
			'fullDate'     => $date_formats[ $date_format ],
			'inputDate'    => $date_formats[ $date_format ],
			'monthAndDate' => 'F j',
			'monthAndYear' => 'F Y',
		);
		$locale_options = array(
			'options' => array(
				'weekStartsOn' => 1,
			),
		);
		wp_add_inline_script(
			'ywpar-ui-date',
			'ywparUI.date.setLocale( ' . wp_json_encode( $locale_options ) . ' );
            ywparUI.date.setDateFormats( ' . wp_json_encode( $date_formats ) . ' );
            ywparUI.date.setFormatDate( wp.date.format );'
		);
	}

	public function register_birthday_field_scripts() {
		$asset_file = include YITH_YWPAR_DIR . 'assets/js/blocks/checkout_blocks.asset.php';
		$deps       = array_merge( $asset_file['dependencies'] );

		$this->register_yith_ui_libray_scripts();

		wp_register_script( 'ywpar-checkout-blocks', YITH_YWPAR_ASSETS_URL . '/js/blocks/checkout_blocks.js', $deps, YITH_YWPAR_VERSION, false );
		wp_register_style( 'ywpar-blocks-style-editor', YITH_YWPAR_ASSETS_URL . '/js/blocks/editor.css', array(), YITH_YWPAR_VERSION );

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'ywpar-checkout-blocks', 'yith-woocommerce-points-and-rewards', YITH_YWPAR_DIR . 'languages' );
		}
	}

	public function register_birthday_field_frontend_scripts() {
		$asset_file = include YITH_YWPAR_DIR . 'assets/js/blocks/checkout_blocks_frontend.asset.php';
		$deps       = array_merge( $asset_file['dependencies'] );

		$this->register_yith_ui_libray_scripts();

		wp_register_script( 'ywpar-checkout-blocks-fronted', YITH_YWPAR_ASSETS_URL . '/js/blocks/checkout_blocks_frontend.js', $deps, YITH_YWPAR_VERSION, true );
		wp_register_style( 'ywpar-blocks-style', YITH_YWPAR_ASSETS_URL . '/js/blocks/style.css', array(), YITH_YWPAR_VERSION );
		wp_enqueue_style( 'ywpar-blocks-style', YITH_YWPAR_ASSETS_URL . '/js/blocks/style.css', array(), YITH_YWPAR_VERSION );
		wp_localize_script(
			'ywpar-checkout-blocks-fronted',
			'ywpar_birthdate_settings',
			array(
				'date_format'      => get_option( 'ywpar_birthday_date_format', 'YYYY-MM-DD' ),
				'date_formats'     => ywpar_get_date_formats(),
				'date_placeholder' => ywpar_date_placeholders(),
			)
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'ywpar-checkout-blocks', 'yith-woocommerce-points-and-rewards', YITH_YWPAR_DIR . 'languages' );
		}
	}

	public function get_script_handles() {
		return array( 'ywpar-checkout-blocks-fronted' );
	}

	public function get_editor_script_handles() {
		return array( 'ywpar-checkout-blocks' );
	}

	public function get_script_data() {
		return array();
	}
}