<?php
/**
 * MealCrafter: Loyalty Points Earning Engine
 * Handles backend awarding logic + Frontend UI Auto-Injections
 * Fully integrated with the Assignment Settings Tab
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Earning {

    public function __construct() {
        // Backend Awarding & Deductions (Hooked dynamically to statuses)
        add_action( 'woocommerce_order_status_changed', [$this, 'handle_order_status_changes'], 10, 4 );

        // Frontend Auto-Injections
        add_action( 'woocommerce_after_add_to_cart_button', [$this, 'display_single_product_earning'], 20 );
        add_action( 'woocommerce_after_cart_totals', [$this, 'display_cart_earning'], 20 );
        add_action( 'woocommerce_review_order_after_order_total', [$this, 'display_checkout_earning'], 20 );

        // Dynamic Quantity Updater JS
        add_action( 'wp_footer', [$this, 'dynamic_points_updater_js'] );
    }

    // -----------------------------------------------------------------------------------
    // SETTINGS FETCHERS
    // -----------------------------------------------------------------------------------
    private function get_setting( $key, $default ) {
        return get_option( $key, $default );
    }

    private function apply_rounding( $value ) {
        $rounding = $this->get_setting('mc_pts_rounding', 'down');
        return $rounding === 'up' ? ceil( $value ) : floor( $value );
    }

    // Calculates points mathematically using all rules
    private function calculate_raw_points( $value ) {
        $spent = (float) $this->get_setting( 'mc_pts_earn_currency', 100 );
        $earn = (float) $this->get_setting( 'mc_pts_earn_points', 20 );
        $conversion_rate = $spent > 0 ? ($earn / $spent) : 0;
        
        return $this->apply_rounding( $value * $conversion_rate );
    }

    // -----------------------------------------------------------------------------------
    // FRONTEND DISPLAY ENGINE
    // -----------------------------------------------------------------------------------
    public function display_single_product_earning() {
        global $product;
        if ( ! $product ) return;

        $custom = get_option('mc_customization_settings', []);
        
        $type = $product->get_type();
        if ( $type === 'mc_combo' && ($custom['earn_show_combo'] ?? 'yes') !== 'yes' ) return;
        if ( $type === 'mc_grouped' && ($custom['earn_show_grouped'] ?? 'yes') !== 'yes' ) return;
        if ( ! in_array($type, ['mc_combo', 'mc_grouped']) && ($custom['earn_show_single'] ?? 'yes') !== 'yes' ) return;

        // Exemptions
        if ( get_post_meta( $product->get_id(), '_mc_points_exempt_earn', true ) === 'yes' ) return;
        if ( $this->get_setting('mc_pts_exclude_sale', 'no') === 'yes' && $product->is_on_sale() ) return;

        // Base price calculation
        $basis = $this->get_setting('mc_earn_basis', 'subtotal_pre');
        if ( $basis === 'subtotal_post' || $basis === 'grand_total' ) {
            $price = wc_get_price_including_tax( $product );
        } else {
            $price = wc_get_price_excluding_tax( $product );
        }

        $points = $this->calculate_raw_points( $price );
        
        if ( $points > 0 ) {
            $msg_format = $custom['earn_msg_product'] ?? 'Earn {points} Points!';
            $msg = str_replace( '{points}', number_format($points), $msg_format );
            $color = esc_attr( $custom['earn_color'] ?? '#2ecc71' );
            
            echo '<div class="mc-earning-msg mc-earning-product" data-base-points="'.esc_attr($points).'" data-format="'.esc_attr($msg_format).'" style="display:inline-block; margin-left: 15px; font-size: 14px; font-weight: 800; color: ' . $color . ';">' . wp_kses_post( $msg ) . '</div>';
        }
    }

    public function display_cart_earning() {
        $custom = get_option('mc_customization_settings', []);
        if ( ($custom['earn_show_cart'] ?? 'yes') !== 'yes' ) return;
        $this->render_checkout_cart_earning_msg( $custom, 'earn_msg_cart', 'mc-earning-cart' );
    }

    public function display_checkout_earning() {
        $custom = get_option('mc_customization_settings', []);
        if ( ($custom['earn_show_checkout'] ?? 'yes') !== 'yes' ) return;
        $this->render_checkout_cart_earning_msg( $custom, 'earn_msg_checkout', 'mc-earning-checkout' );
    }

    private function render_checkout_cart_earning_msg( $custom, $msg_key, $css_class ) {
        if ( ! WC()->cart || WC()->cart->is_empty() ) return;

        $basis = $this->get_setting('mc_earn_basis', 'subtotal_pre');
        $exclude_sale = $this->get_setting('mc_pts_exclude_sale', 'no') === 'yes';
        $deduct_coupons = $this->get_setting('mc_pts_exclude_coupons', 'yes') === 'yes';
        
        $total_points = 0;
        $grand_val = 0;

        $redeemed_key = WC()->session->get('mc_redeemed_cart_item');

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            // NEVER award points directly on the item they are getting for free
            if ( $redeemed_key === $cart_item_key ) continue; 
            
            if ( get_post_meta( $cart_item['product_id'], '_mc_points_exempt_earn', true ) === 'yes' ) continue;
            
            $product = $cart_item['data'];
            if ( $exclude_sale && $product->is_on_sale() ) continue;

            $item_val = 0;

            if ( $basis === 'unit_pre' ) {
                $item_val = wc_get_price_excluding_tax( $product );
                $pts = $this->calculate_raw_points( $item_val );
                $total_points += ($pts * $cart_item['quantity']);
                continue; // Skip the rest, unit logic handles it individually
            }

            // Subtotals & Grand Totals
            if ( $deduct_coupons ) {
                $item_val = ( $basis === 'subtotal_post' || $basis === 'grand_total' ) ? ($cart_item['line_total'] + $cart_item['line_tax']) : $cart_item['line_total'];
            } else {
                $item_val = ( $basis === 'subtotal_post' || $basis === 'grand_total' ) ? ($cart_item['line_subtotal'] + $cart_item['line_subtotal_tax']) : $cart_item['line_subtotal'];
            }

            if ( $basis === 'grand_total' ) {
                $grand_val += $item_val;
            } else {
                $total_points += $this->calculate_raw_points( $item_val );
            }
        }

        if ( $basis === 'grand_total' ) {
            // Add shipping and remaining fees (which includes native discounts as negatives)
            $shipping = WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();
            $grand_val += $shipping;
            foreach ( WC()->cart->get_fees() as $fee ) {
                $grand_val += $fee->total + $fee->tax;
            }
            if ($grand_val > 0) {
                $total_points += $this->calculate_raw_points( $grand_val );
            }
        } else {
            // If they are using subtotal, we still need to deduct cart discounts/fees from their points 
            // so they don't get points for money they didn't spend.
            foreach ( WC()->cart->get_fees() as $fee ) {
                $fee_val = $fee->total;
                if ( $basis === 'subtotal_post' ) $fee_val += $fee->tax;
                $total_points += $this->calculate_raw_points( $fee_val ); // fee_val is negative, so this naturally deducts!
            }
        }

        // Prevent negative points displays
        if ( $total_points <= 0 ) return;

        if ( $total_points > 0 ) {
            $msg = $custom[$msg_key] ?? 'Complete this order to earn {points} Points!';
            $msg = str_replace( '{points}', number_format($total_points), $msg );
            $color = esc_attr( $custom['earn_color'] ?? '#2ecc71' );

            if ( $css_class === 'mc-earning-checkout' ) {
                echo '<tr class="' . esc_attr($css_class) . '"><th>Loyalty Points</th><td data-title="Loyalty Points"><strong style="color: ' . $color . ';">' . wp_kses_post( $msg ) . '</strong></td></tr>';
            } else {
                echo '<div class="' . esc_attr($css_class) . '" style="margin-top: 15px; padding: 15px; background: #fdfbf7; border: 2px dashed ' . $color . '; border-radius: 8px; text-align: center; font-weight: bold; color: #333;">' . wp_kses_post( $msg ) . '</div>';
            }
        }
    }

    public function dynamic_points_updater_js() {
        ?>
        <script>
        jQuery(document).ready(function($) {
            function updatePoints() {
                var qty = parseInt($('form.cart input[name="quantity"]').val()) || 1;
                $('.mc-earning-product').each(function() {
                    var basePts = parseInt($(this).data('base-points')) || 0;
                    var format = $(this).data('format');
                    if (basePts > 0 && format) {
                        var newPts = basePts * qty;
                        var newText = format.replace('{points}', newPts.toLocaleString());
                        $(this).html(newText);
                    }
                });
            }
            $(document).on('change input', 'form.cart input[name="quantity"]', updatePoints);
            updatePoints(); 
        });
        </script>
        <?php
    }

    // -----------------------------------------------------------------------------------
    // BACKEND AWARDING ENGINE (Triggered via Status Change Hook)
    // -----------------------------------------------------------------------------------
    public function handle_order_status_changes( $order_id, $old_status, $new_status, $order ) {
        
        // 1. Awarding Points
        $award_statuses = $this->get_setting('mc_pts_order_status', ['wc-completed']);
        $award_statuses = array_map(function($s) { return str_replace('wc-', '', $s); }, $award_statuses);
        
        if ( in_array( $new_status, $award_statuses ) ) {
            $this->award_points_for_order( $order );
        }

        // 2. Cancellations
        if ( $new_status === 'cancelled' && $this->get_setting('mc_pts_remove_cancelled', 'yes') === 'yes' ) {
            $this->deduct_points_for_refund_or_cancel( $order, 'cancelled' );
        }

        // 3. Refunds
        if ( $new_status === 'refunded' ) {
            if ( $this->get_setting('mc_pts_remove_refunded', 'yes') === 'yes' ) {
                $this->deduct_points_for_refund_or_cancel( $order, 'refunded' );
            }
            if ( $this->get_setting('mc_pts_reassign_refunded', 'no') === 'yes' ) {
                $this->restore_redeemed_points( $order );
            }
        }
    }

    private function award_points_for_order( $order ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id || $order->get_meta( '_mc_points_awarded' ) === 'yes' ) return;

        $basis = $this->get_setting('mc_earn_basis', 'subtotal_pre');
        $exclude_sale = $this->get_setting('mc_pts_exclude_sale', 'no') === 'yes';
        $deduct_coupons = $this->get_setting('mc_pts_exclude_coupons', 'yes') === 'yes';
        
        $total_points = 0;
        $grand_val = 0;

        foreach ( $order->get_items() as $item_id => $item ) {
            // NEVER award points on the exact item they got for free
            if ( $item->get_meta( '_mc_is_redeemed' ) === 'yes' ) continue;
            
            $product = $item->get_product();
            if ( $product && get_post_meta( $product->get_id(), '_mc_points_exempt_earn', true ) === 'yes' ) continue;
            if ( $product && $exclude_sale && $product->is_on_sale() ) continue;

            if ( $basis === 'unit_pre' ) {
                $price = $product ? wc_get_price_excluding_tax( $product ) : 0;
                $pts = $this->calculate_raw_points( $price );
                $total_points += ($pts * $item->get_quantity());
                continue;
            }

            if ( $deduct_coupons ) {
                $item_val = ( $basis === 'subtotal_post' || $basis === 'grand_total' ) ? ((float)$item->get_total() + (float)$item->get_total_tax()) : (float)$item->get_total();
            } else {
                $item_val = ( $basis === 'subtotal_post' || $basis === 'grand_total' ) ? ((float)$item->get_subtotal() + (float)$item->get_subtotal_tax()) : (float)$item->get_subtotal();
            }

            if ( $basis === 'grand_total' ) {
                $grand_val += $item_val;
            } else {
                $total_points += $this->calculate_raw_points( $item_val );
            }
        }

        if ( $basis === 'grand_total' ) {
            $shipping_total = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
            $grand_val += $shipping_total;
            foreach( $order->get_fees() as $fee ) {
                $grand_val += ((float)$fee->get_total() + (float)$fee->get_total_tax());
            }
            if ($grand_val > 0) {
                $total_points += $this->calculate_raw_points( $grand_val );
            }
        } else {
            // Deduct point equivalent of fees (discounts) for subtotal users
            foreach( $order->get_fees() as $fee ) {
                $fee_val = (float) $fee->get_total();
                if ( $basis === 'subtotal_post' ) $fee_val += (float) $fee->get_total_tax();
                $total_points += $this->calculate_raw_points( $fee_val ); 
            }
        }

        if ( $total_points <= 0 ) return;

        if ( $total_points > 0 ) {
            mc_update_user_points( 
                $user_id, 
                $total_points, 
                'earned', 
                sprintf( 'Earned from Order #%s', $order->get_order_number() ), 
                $order->get_id() 
            );
            $order->update_meta_data( '_mc_points_awarded', 'yes' );
            $order->update_meta_data( '_mc_points_earned_amount', $total_points );
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Awarded %s points.', number_format( $total_points ) ) );
            $order->save();
        }
    }

    private function deduct_points_for_refund_or_cancel( $order, $context ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id || $order->get_meta( '_mc_points_awarded' ) !== 'yes' ) return;

        $points_to_deduct = (int) $order->get_meta( '_mc_points_earned_amount' );
        
        if ( $points_to_deduct > 0 ) {
            mc_update_user_points( 
                $user_id, 
                -$points_to_deduct, 
                'adjusted', 
                sprintf( 'Points reversed due to %s on Order #%s', $context, $order->get_order_number() ), 
                $order->get_id() 
            );
            $order->update_meta_data( '_mc_points_awarded', $context );
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Deducted %s earned points due to order %s.', number_format( $points_to_deduct ), $context ) );
            $order->save();
        }
    }

    private function restore_redeemed_points( $order ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id || $order->get_meta( '_mc_points_restored' ) === 'yes' ) return;

        // Did they spend points on this order? We check the user meta history log.
        $history = get_user_meta($user_id, '_mc_points_history', true);
        if ( !is_array($history) ) return;

        $points_spent = 0;
        foreach ( $history as $log ) {
            if ( $log['order'] == $order->get_id() && $log['diff'] < 0 ) {
                $points_spent += abs($log['diff']); // Convert the negative deduction to a positive restoration
            }
        }

        if ( $points_spent > 0 ) {
            mc_update_user_points( 
                $user_id, 
                $points_spent, 
                'adjusted', 
                sprintf( 'Points restored due to refunded Order #%s', $order->get_order_number() ), 
                $order->get_id() 
            );
            $order->update_meta_data( '_mc_points_restored', 'yes' );
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Restored %s spent points due to refund.', number_format( $points_spent ) ) );
            $order->save();
        }
    }
}
new MC_Points_Earning();