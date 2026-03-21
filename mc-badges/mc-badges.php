<?php
/**
 * Plugin Name: MealCrafter - Badge Management
 * Description: Custom badges for WooCommerce products. Text, Image, and CSS shapes.
 * Version: 1.0.0
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define as an active MealCrafter Module
define( 'MC_BADGES_ACTIVE', true );

require_once plugin_dir_path( __FILE__ ) . 'core/class-badge-engine.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-badge-admin.php';