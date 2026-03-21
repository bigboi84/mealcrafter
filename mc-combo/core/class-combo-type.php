<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'product_type_selector', function( $types ) {
    $types['mc_combo'] = 'MealCrafter - Combo';
    return $types;
});

add_action( 'init', function() {
    if ( class_exists( 'WC_Product' ) && ! class_exists( 'WC_Product_MC_Combo' ) ) {
        class WC_Product_MC_Combo extends WC_Product {
            public function get_type() { return 'mc_combo'; }
        }
    }
});

add_filter( 'woocommerce_product_class', function( $classname, $product_type ) {
    if ( $product_type === 'mc_combo' ) { $classname = 'WC_Product_MC_Combo'; }
    return $classname;
}, 10, 2 );