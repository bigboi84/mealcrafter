<?php
/**
 * Plugin Name: MealCrafter - Points & Rewards
 * Description: Hybrid loyalty system with cash conversion, inline checkout redemption, and account progress tracking.
 * Version: 1.0.4
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MC_LOYALTY_PATH', plugin_dir_path( __FILE__ ) );
define( 'MC_LOYALTY_URL', plugin_dir_url( __FILE__ ) );

// =====================================================================
// 1. DATABASE INSTALLATION
// =====================================================================
register_activation_hook( __FILE__, 'mc_loyalty_install_db' );
function mc_loyalty_install_db() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mc_points_transactions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        points float NOT NULL,
        type varchar(20) NOT NULL DEFAULT 'earned',
        order_id bigint(20) DEFAULT NULL,
        description text NOT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// =====================================================================
// 2. GLOBAL POINT CALCULATOR FUNCTIONS
// =====================================================================
function mc_get_user_points($user_id) {
    if (!$user_id) return 0;
    global $wpdb;
    $table = $wpdb->prefix . 'mc_points_transactions';
    $balance = $wpdb->get_var($wpdb->prepare("SELECT SUM(points) FROM $table WHERE user_id = %d", $user_id));
    return (float) $balance ?: 0;
}

function mc_update_user_points($user_id, $points, $type = 'earned', $description = '', $order_id = null) {
    if (!$user_id || $points == 0) return false;
    global $wpdb;
    $table = $wpdb->prefix . 'mc_points_transactions';
    
    return $wpdb->insert($table, [
        'user_id'     => $user_id,
        'points'      => $points,
        'type'        => $type,
        'order_id'    => $order_id,
        'description' => $description,
        'created_at'  => current_time('mysql')
    ]);
}

// =====================================================================
// 3. SMART FILE LOADER (Modular Architecture)
// =====================================================================

// Load Core Logic Files
$core_files = [
    'class-mc-points-admin.php',
    'class-mc-points-earning.php',
    'class-mc-points-checkout.php',
    'class-mc-points-account.php'
];
foreach ( $core_files as $file ) {
    if ( file_exists( MC_LOYALTY_PATH . 'includes/' . $file ) ) { require_once MC_LOYALTY_PATH . 'includes/' . $file; }
}

// Load Admin Tab Modules
$tab_files = [
    'class-mc-tab-customers.php',
    'class-mc-tab-options.php',
    'class-mc-tab-redeeming.php',
    'class-mc-tab-product-level.php',
    'class-mc-tab-catalog.php',
    'class-mc-tab-customization.php'
];
foreach ( $tab_files as $file ) {
    if ( file_exists( MC_LOYALTY_PATH . 'includes/admin-tabs/' . $file ) ) { require_once MC_LOYALTY_PATH . 'includes/admin-tabs/' . $file; }
}