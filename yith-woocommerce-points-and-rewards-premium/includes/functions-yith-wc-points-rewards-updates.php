<?php
/**
 * Implements helper functions for YITH WooCommerce Points and Rewards
 *
 * @package YITH WooCommerce Points and Rewards
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
defined( 'ABSPATH' ) || exit;

global $yith_ywpar_db_version;
$yith_ywpar_db_version = '2.3.0';

if ( ! function_exists( 'yith_ywpar_update_db_check' ) ) {


	/**
	 * Check if the function yith_ywpar_db_install must be installed or updated
	 *
	 * @return void
	 * @since  1.0.0
	 */
	function yith_ywpar_update_db_check() {
		global $yith_ywpar_db_version;

		if ( get_site_option( 'yith_ywpar_db_version' ) !== $yith_ywpar_db_version ) {
			yith_ywpar_db_install();
		}

	}
}

if ( ! function_exists( 'yith_ywpar_db_install' ) ) {


	/**
	 * Install the table yith_ywpar_points_log
	 *
	 * @return void
	 * @since  1.0.0
	 */
	function yith_ywpar_db_install() {
		global $wpdb;
		global $yith_ywpar_db_version;

		$installed_ver = get_option( 'yith_ywpar_db_version' );

		$table_name = $wpdb->prefix . 'yith_ywpar_points_log';

		$charset_collate = $wpdb->get_charset_collate();

		if ( ! $installed_ver || version_compare( $installed_ver, $yith_ywpar_db_version, '<' ) ) {
			$sql = "CREATE TABLE $table_name (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `action` VARCHAR (255) NOT NULL,
            `order_id` int(11),
            `amount` int(11) NOT NULL,
            `date_earning` datetime NOT NULL,
            `cancelled` datetime,
            `description` TEXT,
            `info` LONGTEXT,
            PRIMARY KEY (id)
            ) $charset_collate;";

			include_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );

			update_option( 'yith_ywpar_db_version', $yith_ywpar_db_version );
		}

		/* if ( version_compare( $installed_ver, '1.0.2', '<=' ) ) {
			$sql  = "SELECT COLUMN_NAME from INFORMATION_SCHEMA.COLUMNS where TABLE_NAME='$table_name'";
			$cols = $wpdb->get_col( $sql );

			if ( is_array( $cols ) && ! in_array( 'cancelled', $cols, true ) && version_compare( $installed_ver, '1.0.0', '=' ) ) {
				$sql = "ALTER TABLE $table_name ADD `cancelled` datetime";
				$wpdb->query( $sql );
			}

			if ( is_array( $cols ) && ! in_array( 'description', $cols, true ) && version_compare( $installed_ver, '1.0.1', '=' ) ) {
				$sql = "ALTER TABLE $table_name ADD `description` TEXT";
				$wpdb->query( $sql );
			}

			update_option( 'yith_ywpar_db_version', $yith_ywpar_db_version );
		}*/

	}
}
