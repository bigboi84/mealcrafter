<?php
/**
 * Plugin Name: MealCrafter - Grouped Product
 * Description: Individual item menu hub with AJAX popups. Add-on for MealCrafter.
 * Version: 1.0.0
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// LOAD THIS IMMEDIATELY - Don't wait for plugins_loaded
require_once plugin_dir_path( __FILE__ ) . 'core/class-grouped-cart.php';

add_action( 'plugins_loaded', 'mc_grouped_init' );

function mc_grouped_init() {
    if ( ! defined( 'MC_CORE_ACTIVE' ) ) return; 

    require_once plugin_dir_path( __FILE__ ) . 'core/class-grouped-type.php';
    require_once plugin_dir_path( __FILE__ ) . 'builders/class-grouped-engine.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-grouped-admin.php';
}