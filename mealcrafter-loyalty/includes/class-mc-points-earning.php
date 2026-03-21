<?php
/**
 * MealCrafter: Loyalty Points Earning Engine
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Earning {

    public function __construct() {
        // Hook into order completion to award points
        add_action( 'woocommerce_order_status_completed', [$this, 'award_points_for_order'] );
        
        // Handle order refunds to reverse points and protect margins
        add_action( 'woocommerce_order_status_refunded', [$this, 'deduct_points_for_refund'] );
    }

    public function award_points_for_order( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $user_id = $order->get_user_id();
        
        // Guests don't earn points, they need an account!
        if ( ! $user_id ) return;

        // Prevent double-awarding if the admin accidentally clicks "Complete" twice
        if ( $order->get_meta( '_mc_points_awarded' ) === 'yes' ) return;

        $total_earned = 0;
        
        // We'll grab the global conversion rate. Default: 1 point per $1 spent.
        $conversion_rate = (float) get_option( 'mc_points_earn_rate', 1 );

        foreach ( $order->get_items() as $item_id => $item ) {
            $product_id = $item->get_product_id();

            // 1. Anti-Infinite Loop: Did they get this item for free using points?
            if ( $item->get_meta( '_mc_is_redeemed' ) === 'yes' ) {
                continue; 
            }

            // 2. Exclusion Check: Did the merchant disable earning for this specific product?
            if ( get_post_meta( $product_id, '_mc_points_exempt_earn', true ) === 'yes' ) {
                continue;
            }

            // 3. Calculate points based on the actual line total (after discounts/coupons are applied)
            $line_total = (float) $item->get_total();
            $total_earned += ( $line_total * $conversion_rate );
        }

        // Round points to the nearest whole number for cleaner UI
        $total_earned = round( $total_earned );

        if ( $total_earned > 0 ) {
            // Add points to the database
            mc_update_user_points( 
                $user_id, 
                $total_earned, 
                'earned', 
                sprintf( 'Earned from Order #%s', $order->get_order_number() ), 
                $order_id 
            );

            // Mark order so we don't award twice, and store the exact amount earned
            $order->update_meta_data( '_mc_points_awarded', 'yes' );
            $order->update_meta_data( '_mc_points_earned_amount', $total_earned );
            
            // Add an order note so the restaurant admin can see it happen natively in WooCommerce!
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Awarded %s points to customer.', number_format( $total_earned ) ) );
            
            $order->save();
        }
    }

    public function deduct_points_for_refund( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $user_id = $order->get_user_id();
        if ( ! $user_id ) return;

        // Did this order actually award points previously?
        if ( $order->get_meta( '_mc_points_awarded' ) === 'yes' ) {
            $points_to_deduct = (int) $order->get_meta( '_mc_points_earned_amount' );
            
            if ( $points_to_deduct > 0 ) {
                // We pass a NEGATIVE number to deduct the points back out
                mc_update_user_points( 
                    $user_id, 
                    -$points_to_deduct, 
                    'adjusted', 
                    sprintf( 'Points reversed due to refund on Order #%s', $order->get_order_number() ), 
                    $order_id 
                );

                $order->update_meta_data( '_mc_points_awarded', 'refunded' );
                $order->add_order_note( sprintf( 'MealCrafter Loyalty: Deducted %s points due to order refund.', number_format( $points_to_deduct ) ) );
                $order->save();
            }
        }
    }
}
new MC_Points_Earning();