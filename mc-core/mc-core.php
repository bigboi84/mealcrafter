<?php
/**
 * Plugin Name: MealCrafter Core
 * Description: The Master Hub for the MealCrafter Ecosystem. Developed by Sling.
 * Version: 1.0.1
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Constants to allow add-ons to communicate with core
define( 'MC_CORE_ACTIVE', true );
define( 'MC_CORE_PATH', plugin_dir_path( __FILE__ ) );

// 1. Load the Master Dashboard & Licensing
require_once MC_CORE_PATH . 'admin/class-mc-dashboard.php';



/**
 * 2. The Master Shortcode [mealcrafter]
 * STRICT OUTPUT BUFFERING ADDED: Prevents code from jumping to the header.
 */
add_shortcode( 'mealcrafter', 'mc_master_shortcode_router' );

function mc_master_shortcode_router() {
    global $product;
    
    // Make sure we have the product object
    if ( ! is_object( $product ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    // If no product is found, bail out safely
    if ( ! $product ) {
        return '';
    }

    $type = $product->get_type();

    // START TRAP: Catch all output so it doesn't jump to the header
    ob_start();

    // 1. If it's a Combo
    if ( $type === 'mc_combo' && shortcode_exists('mc_combo_product') ) {
        echo do_shortcode('[mc_combo_product]');
    } 
    // 2. If it's a Grouped Product (Hub)
    elseif ( $type === 'mc_grouped' && shortcode_exists('mc_grouped_product') ) {
        echo do_shortcode('[mc_grouped_product]');
    }
    // 3. Fallback: Normal Product
    else {
        woocommerce_template_single_add_to_cart();
    }

    // END TRAP: Return the trapped HTML to the page builder
    return ob_get_clean();
}

/**
 * 3. Smart Template Switcher
 * (Acts as a backup if a standard WooCommerce theme is used instead of a page builder)
 */
add_filter( 'the_content', 'mc_master_template_switcher', 20 );

function mc_master_template_switcher( $content ) {
    if ( is_product() && in_the_loop() && is_main_query() ) {
        global $product;
        
        if ( ! is_object( $product ) ) {
            $product = wc_get_product( get_the_ID() );
        }

        if ( $product ) {
            $type = $product->get_type();

            if ( $type === 'mc_combo' && shortcode_exists('mc_combo_product') ) {
                return do_shortcode('[mc_combo_product]');
            } 
            elseif ( $type === 'mc_grouped' && shortcode_exists('mc_grouped_product') ) {
                return do_shortcode('[mc_grouped_product]');
            }
        }
    }
    return $content;
}