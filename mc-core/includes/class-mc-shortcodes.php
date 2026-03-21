<?php
/**
 * MealCrafter Smart Router: [mealcrafter]
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('mealcrafter', 'mc_smart_router');

function mc_smart_router() {
    global $product;

    // Ensure we are on a product page
    if ( ! is_object( $product ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    if ( ! $product ) return '';

    $product_type = $product->get_type();

    // 1. ROUTE TO COMBO BUILDER
    if ( $product_type === 'mc_combo' ) {
        if ( shortcode_exists( 'mc_combo_product' ) ) {
            return do_shortcode( '[mc_combo_product]' );
        }
        return '<p style="text-align:center; padding:20px;">Combo Module is not active.</p>';
    }

    // 2. ROUTE TO GROUPED HUB
    if ( $product_type === 'mc_grouped' || $product_type === 'mc_grouped_product' ) {
        if ( shortcode_exists( 'mc_grouped_product' ) ) {
            return do_shortcode( '[mc_grouped_product]' );
        }
        return '<p style="text-align:center; padding:20px;">Grouped Module is not active.</p>';
    }

    // 3. FALLBACK: Standard Product UI
    // If it's a simple or variable product, we show the standard add-to-cart form
    ob_start();
    ?>
    <div class="mc-standard-product-wrap">
        <?php woocommerce_template_single_add_to_cart(); ?>
    </div>
    <?php
    return ob_get_clean();
}