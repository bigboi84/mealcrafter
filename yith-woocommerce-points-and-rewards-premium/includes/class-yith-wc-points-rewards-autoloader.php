<?php
/**
 * Autoloader Class. This is used to decrease memory consumption
 *
 * @class   YITH_WC_Points_Rewards_Autoloader
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Autoloader' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Autoloader
	 */
	class YITH_WC_Points_Rewards_Autoloader {


		/**
		 * Constructor
		 *
		 * @since 2.2.0
		 */
		public function __construct() {
			if ( function_exists( '__autoload' ) ) {
				spl_autoload_register( '__autoload' );
			}

			spl_autoload_register( array( $this, 'autoload' ) );

		}


		/**
		 * Autoload callback
		 *
		 * @param string $class Load the class.
		 * @since 2.2.0
		 */
		public function autoload( $class ) {
			$class        = strtolower( $class );
			$file         = 'class-' . str_replace( '_', '-', $class ) . '.php';
			$admin_files  = array(
				'class-yith-wc-points-rewards-customer-bulk-actions.php',
				'class-yith-wc-points-rewards-editor-earning-rules.php',
				'class-yith-wc-points-rewards-editor-levels-badges.php',
				'class-yith-wc-points-rewards-editor-banners.php',
				'class-yith-wc-points-rewards-editor-redeeming-rules.php',
			);
			$object_files = array(
				'class-yith-wc-points-rewards-banner.php',
				'class-yith-wc-points-rewards-earning-rule.php',
				'class-yith-wc-points-rewards-levels-badge.php',
				'class-yith-wc-points-rewards-redeeming-rule.php',
			);
			$path         = YITH_YWPAR_INC;

			if ( in_array( $file, $admin_files, true ) ) {
				if ( file_exists( $path . 'admin/' . $file ) ) {
					$path .= 'admin/';
				} elseif ( file_exists( $path . 'admin/cpt/' . $file ) ) {
					$path .= 'admin/cpt/';
				}
			}

			if ( in_array( $file, $object_files, true ) ) {
					$path .= 'objects/';
			}

			if ( file_exists( $path . $file ) && is_readable( $path . $file ) ) {
				include_once $path . $file;
			}

		}

	}

}

new YITH_WC_Points_Rewards_Autoloader();
