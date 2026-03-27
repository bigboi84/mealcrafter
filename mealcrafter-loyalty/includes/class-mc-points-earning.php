<?php
/**
 * MealCrafter: Loyalty Points Earning Engine
 * Handles backend awarding logic + Frontend UI Auto-Injections + Extra Points
 * Fixed: Synchronizes table data perfectly with visual meta & Lifetime Tiers!
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Earning {

    public function __construct() {
        // Backend Awarding & Deductions (Hooked dynamically to statuses)
        add_action( 'woocommerce_order_status_changed', [$this, 'handle_order_status_changes'], 10, 4 );

        // Extra Points System Hooks!
        add_action( 'user_register', [$this, 'award_registration_points'] );
        add_action( 'wp_login', [$this, 'award_login_points'], 10, 2 );
        add_action( 'comment_post', [$this, 'award_review_points'], 10, 3 );

        // Frontend Auto-Injections
        add_action( 'woocommerce_after_add_to_cart_button', [$this, 'display_single_product_earning'], 20 );
        add_action( 'woocommerce_after_cart_totals', [$this, 'display_cart_earning'], 20 );
        add_action( 'woocommerce_review_order_after_order_total', [$this, 'display_checkout_earning'], 20 );

        // Dynamic Quantity Updater JS
        add_action( 'wp_footer', [$this, 'dynamic_points_updater_js'] );
    }

    /**
     * CORE SYNC ENGINE: Ensures visual meta, history logs, and lifetime odometers match the database!
     */
    private function sync_user_balance( $user_id, $points, $reason, $order_id = '-' ) {
        $pts_n = get_user_meta($user_id, '_mc_user_points', true);
        $pts_o = get_user_meta($user_id, 'mc_points', true);
        $current_balance = ($pts_n !== '') ? (int)$pts_n : (int)$pts_o;
        $new_balance = max(0, $current_balance + $points);

        update_user_meta( $user_id, '_mc_user_points', $new_balance );
        update_user_meta( $user_id, 'mc_points', $new_balance );

        // Add to Lifetime Odometer (For VIP Tiers!) if points are positive
        if ($points > 0) {
            $lifetime = (int) get_user_meta( $user_id, '_mc_lifetime_points', true );
            update_user_meta( $user_id, '_mc_lifetime_points', $lifetime + $points );
        }

        // Add to Frontend History Shortcode
        $history = get_user_meta($user_id, '_mc_points_history', true);
        if (!is_array($history)) $history = [];
        array_unshift($history, [
            'id'      => uniqid(),
            'date'    => current_time('timestamp'),
            'reason'  => $reason,
            'order'   => $order_id,
            'diff'    => $points,
            'balance' => $new_balance
        ]);
        update_user_meta($user_id, '_mc_points_history', array_slice($history, 0, 200));
    }

    // -----------------------------------------------------------------------------------
    // EXTRA POINTS ENGINE
    // -----------------------------------------------------------------------------------
    public function award_registration_points( $user_id ) {
        if ( get_option('mc_pts_extra_registration', 'no') === 'yes' ) {
            $pts = (int) get_option('mc_pts_extra_registration_pts', 0);
            if ( $pts > 0 ) {
                mc_update_user_points( $user_id, $pts, 'earned', 'Sign Up Bonus' );
                $this->sync_user_balance( $user_id, $pts, 'Sign Up Bonus' );
            }
        }
    }

    public function award_login_points( $user_login, $user ) {
        if ( get_option('mc_pts_extra_login', 'no') === 'yes' ) {
            $pts = (int) get_option('mc_pts_extra_login_pts', 0);
            if ( $pts > 0 ) {
                $last_login = get_user_meta( $user->ID, '_mc_last_login_points', true );
                $today = current_time('Ymd');
                if ( $last_login !== $today ) {
                    mc_update_user_points( $user->ID, $pts, 'earned', 'Daily Login Bonus' );
                    $this->sync_user_balance( $user->ID, $pts, 'Daily Login Bonus' );
                    update_user_meta( $user->ID, '_mc_last_login_points', $today );
                }
            }
        }
    }

    public function award_review_points( $comment_id, $comment_approved, $commentdata ) {
        if ( get_option('mc_pts_extra_reviews', 'no') === 'yes' && $comment_approved === 1 ) {
            $post = get_post( $commentdata['comment_post_ID'] );
            if ( $post && $post->post_type === 'product' && !empty($commentdata['user_id']) ) {
                $pts = (int) get_option('mc_pts_extra_reviews_pts', 0);
                if ( $pts > 0 ) {
                    $has_reviewed = get_user_meta( $commentdata['user_id'], '_mc_reviewed_' . $post->ID, true );
                    if ( !$has_reviewed ) {
                        mc_update_user_points( $commentdata['user_id'], $pts, 'earned', 'Product Review Bonus' );
                        $this->sync_user_balance( $commentdata['user_id'], $pts, 'Product Review Bonus' );
                        update_user_meta( $commentdata['user_id'], '_mc_reviewed_' . $post->ID, 'yes' );
                    }
                }
            }
        }
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

    private function calculate_raw_points( $value ) {
        $spent = (float) $this->get_setting( 'mc_pts_earn_currency', 100 );
        $earn = (float) $this->get_setting( 'mc_pts_earn_points', 20 );
        $conversion_rate = $spent > 0 ? ($earn / $spent) : 0;
        return $this->apply_rounding( $value * $conversion_rate );
    }

    private function is_user_currently_redeeming() {
        if ( ! WC()->session ) return false;
        return WC()->session->get('mc_redeemed_cart_item') || WC()->session->get('mc_points_applied');
    }

    private function calculate_dynamic_upgrade_cost( $cart_item ) {
        $upgrade_cost = 0;
        if ( isset($cart_item['mc_combo_selections']) && is_array($cart_item['mc_combo_selections']) ) {
            $math_logic = get_option( 'mc_combo_math_logic', 'on' );
            $highest_extra = 0;

            if ( $math_logic === 'on' ) {
                foreach ( $cart_item['mc_combo_selections'] as $sel ) {
                    $id = is_array($sel) ? $sel['id'] : $sel;
                    $p = wc_get_product( $id );
                    if ( $p && (float)$p->get_price() > $highest_extra ) {
                        $highest_extra = (float)$p->get_price();
                    }
                }
                if ($highest_extra > 0) $upgrade_cost = $highest_extra;
            } else {
                foreach ( $cart_item['mc_combo_selections'] as $sel ) {
                    $id = is_array($sel) ? $sel['id'] : $sel;
                    $p = wc_get_product( $id );
                    if ( $p && (float)$p->get_price() > 0 ) {
                        $upgrade_cost += (float)$p->get_price();
                    }
                }
            }
        }
        return (float) $upgrade_cost;
    }

    private function calculate_dynamic_upgrade_cost_for_order_item( $item ) {
        $upgrade_cost = 0;
        $raw = $item->get_meta('_mc_raw_combo_data');
        if ( !empty($raw) ) {
            $selections = json_decode($raw, true);
            if ( is_array($selections) ) {
                $math_logic = get_option( 'mc_combo_math_logic', 'on' );
                $highest_extra = 0;
                if ( $math_logic === 'on' ) {
                    foreach ( $selections as $sel ) {
                        $id = is_array($sel) ? $sel['id'] : $sel;
                        $p = wc_get_product( $id );
                        if ( $p && (float)$p->get_price() > $highest_extra ) {
                            $highest_extra = (float)$p->get_price();
                        }
                    }
                    if ($highest_extra > 0) $upgrade_cost = $highest_extra;
                } else {
                    foreach ( $selections as $sel ) {
                        $id = is_array($sel) ? $sel['id'] : $sel;
                        $p = wc_get_product( $id );
                        if ( $p && (float)$p->get_price() > 0 ) {
                            $upgrade_cost += (float)$p->get_price();
                        }
                    }
                }
            }
        }
        return (float) $upgrade_cost;
    }

    private function get_earnable_upgrade_value( $product, $upgrade_cost_inclusive ) {
        $basis = $this->get_setting('mc_earn_basis', 'subtotal_pre');
        $is_taxable = $product->is_taxable();
        $tax_class = $product->get_tax_class();

        if ( $basis === 'subtotal_post' || $basis === 'grand_total' ) {
            return $upgrade_cost_inclusive;
        } 
        
        $tax_divisor = 1;
        if ( wc_prices_include_tax() && $is_taxable && class_exists('WC_Tax') ) {
            $rates = WC_Tax::get_rates( $tax_class );
            if ( !empty($rates) ) {
                $tax_divisor += ( (float) reset($rates)['rate'] / 100 );
            }
        }
        
        return $upgrade_cost_inclusive / $tax_divisor;
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

        if ( get_post_meta( $product->get_id(), '_mc_points_exempt_earn', true ) === 'yes' ) return;
        if ( $this->get_setting('mc_pts_exclude_sale', 'no') === 'yes' && $product->is_on_sale() ) return;

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
        $earn_on_paid_upgrades = get_option('mc_pts_extra_earn_on_paid_upgrades', 'no') === 'yes';
        
        $total_points = 0;
        $grand_val = 0;
        $redeemed_key = WC()->session->get('mc_redeemed_cart_item');

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];
            if ( ! $product ) continue;

            $qty = isset($cart_item['quantity']) && $cart_item['quantity'] > 0 ? (int) $cart_item['quantity'] : 1;
            
            if ( $redeemed_key === $cart_item_key ) {
                if ( $earn_on_paid_upgrades ) {
                    $upgrade_cost_incl = $this->calculate_dynamic_upgrade_cost( $cart_item );
                    if ( $upgrade_cost_incl > 0 ) {
                        $earnable_val = $this->get_earnable_upgrade_value( $product, $upgrade_cost_incl );
                        if ( $basis === 'grand_total' ) {
                            $grand_val += ($earnable_val * $qty);
                        } else {
                            $pts_on_upgrade = $this->calculate_raw_points( $earnable_val );
                            $total_points += ($pts_on_upgrade * $qty);
                        }
                    }
                }
                continue; 
            }
            
            if ( get_post_meta( $cart_item['product_id'], '_mc_points_exempt_earn', true ) === 'yes' ) continue;
            if ( $exclude_sale && $product->is_on_sale() ) continue;

            $item_val = 0;

            if ( $basis === 'unit_pre' ) {
                $item_val = wc_get_price_excluding_tax( $product );
                $pts = $this->calculate_raw_points( $item_val );
                $total_points += ($pts * $qty);
                continue; 
            }

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
            $shipping = WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();
            $grand_val += $shipping;
            foreach ( WC()->cart->get_fees() as $fee ) {
                if ( strpos( strtolower($fee->name), 'loyalty reward:' ) !== false ) continue;
                $grand_val += $fee->total + $fee->tax;
            }
            if ($grand_val > 0) {
                $total_points += $this->calculate_raw_points( $grand_val );
            }
        } else {
            foreach ( WC()->cart->get_fees() as $fee ) {
                if ( strpos( strtolower($fee->name), 'loyalty reward:' ) !== false ) continue;
                $fee_val = $fee->total;
                if ( $basis === 'subtotal_post' ) $fee_val += $fee->tax;
                $total_points += $this->calculate_raw_points( $fee_val ); 
            }
        }

        if ( get_option('mc_pts_extra_cart', 'no') === 'yes' ) {
            $threshold = (float) get_option('mc_pts_extra_cart_threshold', 0);
            $bonus_pts = (int) get_option('mc_pts_extra_cart_pts', 0);
            $test_val = ($basis === 'grand_total') ? $grand_val : WC()->cart->get_subtotal();
            if ( $threshold > 0 && $bonus_pts > 0 && $test_val >= $threshold ) {
                $total_points += $bonus_pts;
            }
        }

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
        
        $award_statuses = $this->get_setting('mc_pts_order_status', ['wc-completed']);
        $award_statuses = array_map(function($s) { return str_replace('wc-', '', $s); }, $award_statuses);
        
        if ( in_array( $new_status, $award_statuses ) ) {
            $this->award_points_for_order( $order );
        }

        if ( $new_status === 'cancelled' && $this->get_setting('mc_pts_remove_cancelled', 'yes') === 'yes' ) {
            $this->deduct_points_for_refund_or_cancel( $order, 'cancelled' );
        }

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
        $earn_on_paid_upgrades = get_option('mc_pts_extra_earn_on_paid_upgrades', 'no') === 'yes';
        
        $total_points = 0;
        $grand_val = 0;

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            if ( ! $product ) continue;

            $qty = (int) $item->get_quantity();

            if ( $item->get_meta( '_mc_is_redeemed' ) === 'yes' ) {
                if ( $earn_on_paid_upgrades ) {
                    $upgrade_cost_incl = $this->calculate_dynamic_upgrade_cost_for_order_item( $item ); 
                    if ( $upgrade_cost_incl > 0 ) {
                        $earnable_val = $this->get_earnable_upgrade_value( $product, $upgrade_cost_incl );
                        if ( $basis === 'grand_total' ) {
                            $grand_val += ($earnable_val * $qty);
                        } else {
                            $pts_on_upgrade = $this->calculate_raw_points( $earnable_val );
                            $total_points += ($pts_on_upgrade * $qty);
                        }
                    }
                }
                continue;
            }
            
            if ( get_post_meta( $product->get_id(), '_mc_points_exempt_earn', true ) === 'yes' ) continue;
            if ( $exclude_sale && $product->is_on_sale() ) continue;

            if ( $basis === 'unit_pre' ) {
                $price = wc_get_price_excluding_tax( $product );
                $pts = $this->calculate_raw_points( $price );
                $total_points += ($pts * $qty);
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
                if ( strpos( strtolower($fee->get_name()), 'loyalty reward:' ) !== false ) continue;
                $grand_val += ((float)$fee->get_total() + (float)$fee->get_total_tax());
            }
            if ($grand_val > 0) {
                $total_points += $this->calculate_raw_points( $grand_val );
            }
        } else {
            foreach( $order->get_fees() as $fee ) {
                if ( strpos( strtolower($fee->get_name()), 'loyalty reward:' ) !== false ) continue;
                $fee_val = (float) $fee->get_total();
                if ( $basis === 'subtotal_post' ) $fee_val += (float) $fee->get_total_tax();
                $total_points += $this->calculate_raw_points( $fee_val ); 
            }
        }

        if ( get_option('mc_pts_extra_cart', 'no') === 'yes' ) {
            $threshold = (float) get_option('mc_pts_extra_cart_threshold', 0);
            $bonus_pts = (int) get_option('mc_pts_extra_cart_pts', 0);
            
            $test_val = ($basis === 'grand_total') ? $grand_val : $order->get_subtotal();
            if ( $threshold > 0 && $bonus_pts > 0 && $test_val >= $threshold ) {
                $total_points += $bonus_pts;
                $order->add_order_note( sprintf( 'MealCrafter Loyalty: Cart minimum reached. Added %s bonus points.', $bonus_pts ) );
            }
        }

        if ( $total_points <= 0 ) return;

        if ( $total_points > 0 ) {
            $reason = sprintf( 'Earned from Order #%s', $order->get_order_number() );
            mc_update_user_points( $user_id, $total_points, 'earned', $reason, $order->get_id() );
            $this->sync_user_balance( $user_id, $total_points, $reason, $order->get_id() );

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
            $reason = sprintf( 'Points reversed due to %s on Order #%s', $context, $order->get_order_number() );
            mc_update_user_points( $user_id, -$points_to_deduct, 'adjusted', $reason, $order->get_id() );
            $this->sync_user_balance( $user_id, -$points_to_deduct, $reason, $order->get_id() );

            $order->update_meta_data( '_mc_points_awarded', $context );
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Deducted %s earned points due to order %s.', number_format( $points_to_deduct ), $context ) );
            $order->save();
        }
    }

    private function restore_redeemed_points( $order ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id || $order->get_meta( '_mc_points_restored' ) === 'yes' ) return;

        $history = get_user_meta($user_id, '_mc_points_history', true);
        if ( !is_array($history) ) return;

        $points_spent = 0;
        foreach ( $history as $log ) {
            if ( $log['order'] == $order->get_id() && $log['diff'] < 0 ) {
                $points_spent += abs($log['diff']);
            }
        }

        if ( $points_spent > 0 ) {
            $reason = sprintf( 'Points restored due to refunded Order #%s', $order->get_order_number() );
            mc_update_user_points( $user_id, $points_spent, 'adjusted', $reason, $order->get_id() );
            $this->sync_user_balance( $user_id, $points_spent, $reason, $order->get_id() );

            $order->update_meta_data( '_mc_points_restored', 'yes' );
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Restored %s spent points due to refund.', number_format( $points_spent ) ) );
            $order->save();
        }
    }
}
new MC_Points_Earning();