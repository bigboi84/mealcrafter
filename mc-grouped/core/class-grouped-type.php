<?php
/**
 * MealCrafter: Register Grouped Product Type
 * Safely wrapped to prevent crashes.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Add the type to the WooCommerce dropdown
add_filter( 'product_type_selector', 'mc_add_grouped_product_type' );
function mc_add_grouped_product_type( $types ){
    $types[ 'mc_grouped' ] = 'MealCrafter - Grouped Product';
    return $types;
}

// 2. Define the Product Class
add_action( 'init', 'mc_create_grouped_product_class' );
function mc_create_grouped_product_class(){
    if ( class_exists( 'WC_Product' ) && ! class_exists( 'WC_Product_MC_Grouped' ) ) {
        class WC_Product_MC_Grouped extends WC_Product {
            public function get_type() {
                return 'mc_grouped';
            }
        }
    }
}

// 3. Force WC to use our custom class for this type
add_filter( 'woocommerce_product_class', 'mc_force_grouped_product_class', 10, 2 );
function mc_force_grouped_product_class( $classname, $product_type ) {
    if ( $product_type === 'mc_grouped' ) {
        $classname = 'WC_Product_MC_Grouped';
    }
    return $classname;
}

// 4. Disable the main "Add to Cart" for the Hub itself
add_filter( 'woocommerce_is_purchasable', 'mc_grouped_hub_is_purchasable', 10, 2 );
function mc_grouped_hub_is_purchasable( $purchasable, $product ) {
    if( is_object($product) && $product->get_type() === 'mc_grouped' ) {
        return false;
    }
    return $purchasable;
}