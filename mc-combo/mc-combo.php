<?php
/**
 * Plugin Name: MealCrafter - Combo Builder
 * Description: Premium multi-step combo builder for the MealCrafter Ecosystem.
 * Version: 1.0.3
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'plugins_loaded', function() {
    if ( ! defined( 'MC_CORE_ACTIVE' ) ) return;

    require_once plugin_dir_path( __FILE__ ) . 'core/class-combo-type.php';
    require_once plugin_dir_path( __FILE__ ) . 'core/class-combo-cart.php'; 
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-combo-admin.php';
    require_once plugin_dir_path( __FILE__ ) . 'builders/class-combo-engine.php';
});