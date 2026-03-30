<?php
/**
 * MealCrafter: Loyalty Points Earning Engine
 * Handles backend awarding logic + Frontend UI Auto-Injections + Extra Points
 * Fixed: All 100% Extra Earning Triggers (Referrals, Birthdays, Profiles, Milestones) activated!
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Earning {

    public function __construct() {
        // Backend Awarding & Deductions
        add_action( 'woocommerce_order_status_changed', [$this, 'handle_order_status_changes'], 10, 4 );

        // --- THE EXTRA POINTS SYSTEM HOOKS ---
        
        // 1. Referral Link Tracker
        add_action( 'init', [$this, 'track_referral_cookie'] );
        
        // 2. Registration & Referral Signup Bonus
        add_action( 'user_register', [$this, 'award_registration_points'] );
        
        // 3. Daily Login Bonus
        add_action( 'wp_login', [$this, 'award_login_points'], 10, 2 );
        
        // 4. Product Review Bonus
        add_action( 'comment_post', [$this, 'award_review_points'], 10, 3 );
        
        // 5. Complete Profile Bonus
        add_action( 'profile_update', [$this, 'check_profile_completion_points'], 10, 1 );
        add_action( 'woocommerce_customer_save_address', [$this, 'check_profile_completion_points'], 10, 1 );

        // 6. Birthday Cron Job
        add_action( 'init', [$this, 'schedule_daily_events'] );
        add_action( 'mc_loyalty_daily_events', [$this, 'process_birthdays'] );


        // Frontend Auto-Injections
        $custom = get_option('mc_customization_settings', []);
        $prod_pos = !empty($custom['prod_pos']) ? $custom['prod_pos'] : 'before_cart';
        $hook = ($prod_pos === 'after_cart') ? 'woocommerce_after_add_to_cart_button' : 'woocommerce_before_add_to_cart_button';
        
        add_action( $hook, [$this, 'display_single_product_earning'], 20 );
        add_action( 'woocommerce_after_cart_totals', [$this, 'display_cart_earning'], 20 );
        add_action( 'woocommerce_review_order_after_order_total', [$this, 'display_checkout_earning'], 20 );

        // Dynamic Quantity Updater JS
        add_action( 'wp_footer', [$this, 'dynamic_points_updater_js'] );
    }

    /**
     * CORE SYNC ENGINE
     */
    private function sync_user_balance( $user_id, $points, $reason, $order_id = '-' ) {
        $pts_n = get_user_meta($user_id, '_mc_user_points', true);
        $pts_o = get_user_meta($user_id, 'mc_points', true);
        $current_balance = ($pts_n !== '') ? (int)$pts_n : (int)$pts_o;
        $new_balance = max(0, $current_balance + $points);

        update_user_meta( $user_id, '_mc_user_points', $new_balance );
        update_user_meta( $user_id, 'mc_points', $new_balance );

        if ($points > 0) {
            $lifetime = (int) get_user_meta( $user_id, '_mc_lifetime_points', true );
            update_user_meta( $user_id, '_mc_lifetime_points', $lifetime + $points );
        }

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
    // ALL EXTRA POINTS LOGIC (Referrals, Profiles, Birthdays, etc.)
    // -----------------------------------------------------------------------------------

    public function track_referral_cookie() {
        if ( isset($_GET['ref']) && !is_user_logged_in() ) {
            $ref_id = intval(sanitize_text_field($_GET['ref']));
            if ( $ref_id > 0 ) {
                setcookie( 'mc_referral_id', $ref_id, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN );
            }
        }
    }

    public function award_registration_points( $user_id ) {
        // Standard Sign up Bonus
        if ( get_option('mc_pts_extra_registration', 'no') === 'yes' ) {
            $pts = (int) get_option('mc_pts_extra_registration_pts', 0);
            if ( $pts > 0 ) {
                if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $user_id, $pts, 'earned', 'Sign Up Bonus' ); }
                $this->sync_user_balance( $user_id, $pts, 'Sign Up Bonus' );
            }
        }

        // Referral Signup Bonus (For the Referrer)
        if ( isset($_COOKIE['mc_referral_id']) ) {
            $referrer_id = intval($_COOKIE['mc_referral_id']);
            if ( $referrer_id > 0 && $referrer_id !== $user_id ) {
                
                // Stamp the referred user so we can track their first purchase later
                update_user_meta( $user_id, '_mc_referred_by', $referrer_id );
                
                if ( get_option('mc_pts_extra_referral', 'no') === 'yes' ) {
                    $ref_pts = (int) get_option('mc_pts_extra_referral_pts', 0);
                    if ( $ref_pts > 0 ) {
                        if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $referrer_id, $ref_pts, 'earned', 'Referral Sign Up Bonus' ); }
                        $this->sync_user_balance( $referrer_id, $ref_pts, 'Referral Sign Up Bonus' );
                    }
                }
            }
        }
    }

    public function check_profile_completion_points( $user_id ) {
        if ( get_option('mc_pts_extra_profile', 'no') !== 'yes' ) return;
        
        // Prevent double rewarding
        $has_bonus = get_user_meta( $user_id, '_mc_profile_completed_bonus', true );
        if ( $has_bonus === 'yes' ) return;
        
        $customer = new WC_Customer( $user_id );
        if ( $customer ) {
            $fname = $customer->get_first_name();
            $lname = $customer->get_last_name();
            $phone = $customer->get_billing_phone();
            $addr1 = $customer->get_billing_address_1();
            $city  = $customer->get_billing_city();
            
            // If the core details are filled, they earned it!
            if ( !empty($fname) && !empty($lname) && !empty($phone) && !empty($addr1) && !empty($city) ) {
                $pts = (int) get_option('mc_pts_extra_profile_pts', 0);
                if ( $pts > 0 ) {
                    if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $user_id, $pts, 'earned', 'Completed Profile Bonus' ); }
                    $this->sync_user_balance( $user_id, $pts, 'Completed Profile Bonus' );
                    update_user_meta( $user_id, '_mc_profile_completed_bonus', 'yes' );
                }
            }
        }
    }

    public function schedule_daily_events() {
        if ( ! wp_next_scheduled( 'mc_loyalty_daily_events' ) ) {
            wp_schedule_event( time(), 'daily', 'mc_loyalty_daily_events' );
        }
    }

    public function process_birthdays() {
        if ( get_option('mc_pts_extra_birthday', 'no') !== 'yes' ) return;
        
        $pts = (int) get_option('mc_pts_extra_birthday_pts', 0);
        if ( $pts <= 0 ) return;

        global $wpdb;
        $today_md = date('m-d');
        $current_year = date('Y');
        
        // Find users with _mc_birthdate (YYYY-MM-DD) matching today
        $users = $wpdb->get_col( $wpdb->prepare("
            SELECT user_id FROM {$wpdb->usermeta} 
            WHERE meta_key = '_mc_birthdate' AND meta_value LIKE %s
        ", '%-' . $today_md) );
        
        foreach ( $users as $uid ) {
            $rewarded_year = get_user_meta( $uid, '_mc_birthday_rewarded_year', true );
            if ( $rewarded_year !== $current_year ) {
                if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $uid, $pts, 'earned', 'Happy Birthday Bonus!' ); }
                $this->sync_user_balance( $uid, $pts, 'Happy Birthday Bonus!' );
                update_user_meta( $uid, '_mc_birthday_rewarded_year', $current_year );
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
                    if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $user->ID, $pts, 'earned', 'Daily Login Bonus' ); }
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
                        if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $commentdata['user_id'], $pts, 'earned', 'Product Review Bonus' ); }
                        $this->sync_user_balance( $commentdata['user_id'], $pts, 'Product Review Bonus' );
                        update_user_meta( $commentdata['user_id'], '_mc_reviewed_' . $post->ID, 'yes' );
                    }
                }
            }
        }
    }

    // -----------------------------------------------------------------------------------
    // SETTINGS FETCHERS & MATH ENGINE
    // -----------------------------------------------------------------------------------
    private function get_setting( $key, $default ) {
        return get_option( $key, $default );
    }

    private function apply_rounding( $value ) {
        $rounding = $this->get_setting('mc_pts_rounding', 'down');
        return $rounding === 'up' ? ceil( $value ) : floor( $value );
    }

    private function get_conversion_rate() {
        $spent = (float) $this->get_setting( 'mc_pts_earn_currency', 100 );
        $earn = (float) $this->get_setting( 'mc_pts_earn_points', 20 );
        return $spent > 0 ? ($earn / $spent) : 0;
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
        
        if ( ($custom['prod_show'] ?? 'yes') !== 'yes' ) return;

        $type = $product->get_type();
        if ( $type === 'mc_combo' && ($custom['earn_show_combo'] ?? 'yes') === 'no' ) return;
        if ( $type === 'mc_grouped' && ($custom['earn_show_grouped'] ?? 'yes') === 'no' ) return;
        if ( ! in_array($type, ['mc_combo', 'mc_grouped']) && ($custom['earn_show_single'] ?? 'yes') === 'no' ) return;

        if ( get_post_meta( $product->get_id(), '_mc_points_exempt_earn', true ) === 'yes' ) return;
        if ( $this->get_setting('mc_pts_exclude_sale', 'no') === 'yes' && $product->is_on_sale() ) return;

        $basis = $this->get_setting('mc_earn_basis', 'subtotal_pre');
        if ( $basis === 'subtotal_post' || $basis === 'grand_total' ) {
            $price = wc_get_price_including_tax( $product );
        } else {
            $price = wc_get_price_excluding_tax( $product );
        }

        $conv = $this->get_conversion_rate();
        $points = $this->apply_rounding( $price * $conv );
        
        if ( $points > 0 ) {
            $msg_format = !empty($custom['prod_msg']) ? $custom['prod_msg'] : 'Buy this product and earn {points} Points!';
            $msg = str_replace( '{points}', number_format($points), $msg_format );
            
            $lbl = !empty($custom['lbl_plural']) ? $custom['lbl_plural'] : 'Points';
            $msg = str_replace( '{points_label}', $lbl, $msg );

            $color = esc_attr( $custom['prod_color_text'] ?? '#2271b1' );
            $bg = esc_attr( $custom['prod_color_bg'] ?? '#eaf2fa' );
            
            echo '<div class="mc-earning-msg mc-earning-product" data-base-points="'.esc_attr($points).'" data-format="'.esc_attr($msg_format).'" style="display:inline-block; margin-top: 10px; margin-bottom: 10px; padding: 8px 15px; border-radius: 4px; background:'. $bg .'; font-size: 14px; font-weight: 800; color: ' . $color . ';">' . wp_kses_post( $msg ) . '</div>';
        }
    }

    public function display_cart_earning() {
        $custom = get_option('mc_customization_settings', []);
        if ( ($custom['cart_show'] ?? 'yes') !== 'yes' ) return;
        $this->render_checkout_cart_earning_msg( $custom, 'cart_msg', 'mc-earning-cart' );
    }

    public function display_checkout_earning() {
        $custom = get_option('mc_customization_settings', []);
        if ( ($custom['checkout_show'] ?? 'yes') !== 'yes' ) return;
        $this->render_checkout_cart_earning_msg( $custom, 'checkout_msg', 'mc-earning-checkout' );
    }

    private function render_checkout_cart_earning_msg( $custom, $msg_key, $css_class ) {
        if ( ! WC()->cart || WC()->cart->is_empty() ) return;

        $disable_earn_on_redeem = get_option('mc_pts_disable_earn_on_redeem', 'no') === 'yes';
        $redeemed_key = WC()->session->get('mc_redeemed_cart_item');
        $pts_applied = WC()->session->get('mc_points_applied');

        if ( $disable_earn_on_redeem && ( $redeemed_key || $pts_applied ) ) {
            return; 
        }

        $basis = $this->get_setting('mc_earn_basis', 'subtotal_pre');
        $exclude_sale = $this->get_setting('mc_pts_exclude_sale', 'no') === 'yes';
        $deduct_coupons = $this->get_setting('mc_pts_exclude_coupons', 'yes') === 'yes';
        $earn_on_paid_upgrades = get_option('mc_pts_extra_earn_on_paid_upgrades', 'no') === 'yes';
        
        $base_val = get_option('mc_pts_prod_base_price_only', 'yes');
        $base_only = in_array( strtolower( (string) $base_val ), ['yes', 'on', '1', 'true'], true );

        $redeem_label = !empty($custom['lbl_btn_redeem']) ? strtolower($custom['lbl_btn_redeem']) : 'points discount';

        $conv = $this->get_conversion_rate();
        $raw_points = 0;
        $grand_val = 0;

        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];
            if ( ! $product ) continue;

            $qty = isset($cart_item['quantity']) && $cart_item['quantity'] > 0 ? (int) $cart_item['quantity'] : 1;
            
            if ( $redeemed_key === $cart_item_key ) {
                if ( $earn_on_paid_upgrades && $base_only ) {
                    $upgrade_cost_incl = $this->calculate_dynamic_upgrade_cost( $cart_item );
                    if ( $upgrade_cost_incl > 0 ) {
                        $earnable_val = $this->get_earnable_upgrade_value( $product, $upgrade_cost_incl );
                        if ( $basis === 'grand_total' ) {
                            $grand_val += ($earnable_val * $qty);
                        } else {
                            $raw_points += ($earnable_val * $qty * $conv);
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
                $raw_points += ($item_val * $qty * $conv);
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
                $raw_points += ($item_val * $conv);
            }
        }

        if ( $basis === 'grand_total' ) {
            $shipping = WC()->cart->get_shipping_total() + WC()->cart->get_shipping_tax();
            $grand_val += $shipping;
            foreach ( WC()->cart->get_fees() as $fee ) {
                $fee_name = strtolower($fee->name);
                if ( strpos( $fee_name, 'loyalty reward:' ) !== false || strpos( $fee_name, $redeem_label ) !== false ) continue;
                
                $grand_val += $fee->total + $fee->tax;
            }
            if ($grand_val > 0) {
                $raw_points += ($grand_val * $conv);
            }
        } else {
            foreach ( WC()->cart->get_fees() as $fee ) {
                $fee_name = strtolower($fee->name);
                if ( strpos( $fee_name, 'loyalty reward:' ) !== false || strpos( $fee_name, $redeem_label ) !== false ) continue;
                
                $fee_val = $fee->total;
                if ( $basis === 'subtotal_post' ) $fee_val += $fee->tax;
                $raw_points += ($fee_val * $conv); 
            }
        }

        $total_points = $this->apply_rounding($raw_points);

        if ( get_option('mc_pts_extra_cart', 'no') === 'yes' ) {
            $threshold = (float) get_option('mc_pts_extra_cart_threshold', 0);
            $bonus_pts = (int) get_option('mc_pts_extra_cart_pts', 0);
            $test_val = ($basis === 'grand_total') ? $grand_val : WC()->cart->get_subtotal();
            if ( $threshold > 0 && $bonus_pts > 0 && $test_val >= $threshold ) {
                $total_points += $bonus_pts;
            }
        }

        if ( $total_points <= 0 ) return;

        $fallback_msg = 'Complete this order to earn {points} Points!';
        $msg = !empty($custom[$msg_key]) ? $custom[$msg_key] : $fallback_msg;
        $msg = str_replace( '{points}', number_format($total_points), $msg );
        
        $lbl = !empty($custom['lbl_plural']) ? $custom['lbl_plural'] : 'Points';
        $msg = str_replace( '{points_label}', $lbl, $msg );

        $color = esc_attr( $custom['earn_color'] ?? '#2ecc71' );

        if ( $css_class === 'mc-earning-checkout' ) {
            echo '<tr class="' . esc_attr($css_class) . '"><th>Loyalty Points</th><td data-title="Loyalty Points"><strong style="color: ' . $color . ';">' . wp_kses_post( $msg ) . '</strong></td></tr>';
        } else {
            echo '<div class="' . esc_attr($css_class) . '" style="margin-top: 15px; padding: 15px; background: #fdfbf7; border: 2px dashed ' . $color . '; border-radius: 8px; text-align: center; font-weight: bold; color: #333;">' . wp_kses_post( $msg ) . '</div>';
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

        // --- TRIGGER EXTRA POINTS EVEN IF CART IS $0 ---
        if ( $order->get_meta('_mc_extra_points_checked') !== 'yes' ) {
            
            // 1. Order Milestones (First Order / Every Order Bonus)
            if ( get_option('mc_pts_extra_orders', 'no') === 'yes' ) {
                $pts = (int) get_option('mc_pts_extra_orders_pts', 0);
                $repeat = get_option('mc_pts_extra_orders_repeat', 'yes'); 
                $order_count = wc_get_customer_order_count( $user_id );
                
                if ( $pts > 0 ) {
                    if ( $repeat === 'yes' || $order_count === 1 ) {
                        $reason = ($order_count === 1) ? 'First Order Bonus' : 'Order Milestone Bonus';
                        if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $user_id, $pts, 'earned', $reason, $order->get_id() ); }
                        $this->sync_user_balance( $user_id, $pts, $reason, $order->get_id() );
                        $order->add_order_note( sprintf( 'MealCrafter Loyalty: Awarded %s %s points.', $pts, $reason ) );
                    }
                }
            }

            // 2. Referral Purchases (Award original referrer on referred user's FIRST purchase)
            $referrer_id = get_user_meta( $user_id, '_mc_referred_by', true );
            $referral_rewarded = get_user_meta( $user_id, '_mc_referral_purchase_rewarded', true );
            
            if ( $referrer_id && !$referral_rewarded && get_option('mc_pts_extra_ref_purchase', 'no') === 'yes' ) {
                $ref_pts = (int) get_option('mc_pts_extra_ref_purchase_pts', 0);
                if ( $ref_pts > 0 ) {
                    if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $referrer_id, $ref_pts, 'earned', 'Referral First Purchase Bonus' ); }
                    $this->sync_user_balance( $referrer_id, $ref_pts, 'Referral First Purchase Bonus' );
                    update_user_meta( $user_id, '_mc_referral_purchase_rewarded', 'yes' );
                }
            }

            $order->update_meta_data('_mc_extra_points_checked', 'yes');
            $order->save();
        }


        // --- STANDARD CART MATH ---
        $custom = get_option('mc_customization_settings', []);
        $redeem_label = !empty($custom['lbl_btn_redeem']) ? strtolower($custom['lbl_btn_redeem']) : 'points discount';

        $disable_earn = get_option('mc_pts_disable_earn_on_redeem', 'no') === 'yes';
        $is_redeeming = false;
        foreach ( $order->get_items() as $item ) {
            if ( $item->get_meta( '_mc_is_redeemed' ) === 'yes' ) { $is_redeeming = true; break; }
        }
        foreach ( $order->get_fees() as $fee ) {
            $fee_name = strtolower($fee->get_name());
            if ( $fee_name === $redeem_label || strpos( $fee_name, 'loyalty reward:' ) !== false ) {
                $is_redeeming = true; break;
            }
        }
        if ( $disable_earn && $is_redeeming ) return;

        $basis = $this->get_setting('mc_earn_basis', 'subtotal_pre');
        $exclude_sale = $this->get_setting('mc_pts_exclude_sale', 'no') === 'yes';
        $deduct_coupons = $this->get_setting('mc_pts_exclude_coupons', 'yes') === 'yes';
        $earn_on_paid_upgrades = get_option('mc_pts_extra_earn_on_paid_upgrades', 'no') === 'yes';
        
        $base_val = get_option('mc_pts_prod_base_price_only', 'yes');
        $base_only = in_array( strtolower( (string) $base_val ), ['yes', 'on', '1', 'true'], true );

        $conv = $this->get_conversion_rate();
        $raw_points = 0;
        $grand_val = 0;

        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            if ( ! $product ) continue;

            $qty = (int) $item->get_quantity();

            if ( $item->get_meta( '_mc_is_redeemed' ) === 'yes' ) {
                if ( $earn_on_paid_upgrades && $base_only ) {
                    $upgrade_cost_incl = $this->calculate_dynamic_upgrade_cost_for_order_item( $item ); 
                    if ( $upgrade_cost_incl > 0 ) {
                        $earnable_val = $this->get_earnable_upgrade_value( $product, $upgrade_cost_incl );
                        if ( $basis === 'grand_total' ) {
                            $grand_val += ($earnable_val * $qty);
                        } else {
                            $raw_points += ($earnable_val * $qty * $conv);
                        }
                    }
                }
                continue;
            }
            
            if ( get_post_meta( $product->get_id(), '_mc_points_exempt_earn', true ) === 'yes' ) continue;
            if ( $exclude_sale && $product->is_on_sale() ) continue;

            if ( $basis === 'unit_pre' ) {
                $price = wc_get_price_excluding_tax( $product );
                $raw_points += ($price * $qty * $conv);
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
                $raw_points += ($item_val * $conv);
            }
        }

        if ( $basis === 'grand_total' ) {
            $shipping_total = (float) $order->get_shipping_total() + (float) $order->get_shipping_tax();
            $grand_val += $shipping_total;
            foreach( $order->get_fees() as $fee ) {
                $fee_name = strtolower($fee->get_name());
                if ( strpos( $fee_name, 'loyalty reward:' ) !== false || strpos( $fee_name, $redeem_label ) !== false ) continue;
                
                $grand_val += ((float)$fee->get_total() + (float)$fee->get_total_tax());
            }
            if ($grand_val > 0) {
                $raw_points += ($grand_val * $conv);
            }
        } else {
            foreach( $order->get_fees() as $fee ) {
                $fee_name = strtolower($fee->get_name());
                if ( strpos( $fee_name, 'loyalty reward:' ) !== false || strpos( $fee_name, $redeem_label ) !== false ) continue;
                
                $fee_val = (float) $fee->get_total();
                if ( $basis === 'subtotal_post' ) $fee_val += (float) $fee->get_total_tax();
                $raw_points += ($fee_val * $conv); 
            }
        }

        $total_points = $this->apply_rounding($raw_points);

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

        $reason = sprintf( 'Earned from Order #%s', $order->get_order_number() );
        if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $user_id, $total_points, 'earned', $reason, $order->get_id() ); }
        $this->sync_user_balance( $user_id, $total_points, $reason, $order->get_id() );

        $order->update_meta_data( '_mc_points_awarded', 'yes' );
        $order->update_meta_data( '_mc_points_earned_amount', $total_points );
        $order->add_order_note( sprintf( 'MealCrafter Loyalty: Awarded %s points.', number_format( $total_points ) ) );
        $order->save();
    }

    private function deduct_points_for_refund_or_cancel( $order, $context ) {
        $user_id = $order->get_user_id();
        if ( ! $user_id || $order->get_meta( '_mc_points_awarded' ) !== 'yes' ) return;

        $points_to_deduct = (int) $order->get_meta( '_mc_points_earned_amount' );
        
        if ( $points_to_deduct > 0 ) {
            $reason = sprintf( 'Points reversed due to %s on Order #%s', $context, $order->get_order_number() );
            if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $user_id, -$points_to_deduct, 'adjusted', $reason, $order->get_id() ); }
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
            if ( function_exists('mc_update_user_points') ) { mc_update_user_points( $user_id, $points_spent, 'adjusted', $reason, $order->get_id() ); }
            $this->sync_user_balance( $user_id, $points_spent, $reason, $order->get_id() );

            $order->update_meta_data( '_mc_points_restored', 'yes' );
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Restored %s spent points due to refund.', number_format( $points_spent ) ) );
            $order->save();
        }
    }
}
new MC_Points_Earning();