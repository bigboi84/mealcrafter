<?php
/**
 * MealCrafter: Loyalty Checkout & Cart Logic
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Checkout {

    public function __construct() {
        // 1. SMART TRIGGERS & AUTO-GIVEAWAYS
        add_action( 'template_redirect', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_add_to_cart', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_cart_item_removed', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_before_calculate_totals', [$this, 'set_giveaway_prices_to_zero'], 9999 );
        add_filter( 'woocommerce_cart_item_name', [$this, 'add_giveaway_badge_to_cart_name'], 10, 3 );
        add_action( 'woocommerce_checkout_order_processed', [$this, 'consume_app_task_flags'], 10, 1 );

        // 2. POINTS REDEMPTION (DISCOUNTS & PRODUCT LEVEL)
        $custom = get_option('mc_customization_settings', []);
        $box_pos = !empty($custom['box_pos']) ? $custom['box_pos'] : 'woocommerce_before_checkout_form';
        
        add_action( $box_pos, [$this, 'render_redemption_box'] );
        add_action( 'woocommerce_before_cart', [$this, 'render_redemption_box'] );

        add_action( 'wp_ajax_mc_apply_checkout_points', [$this, 'ajax_apply_points'] );
        add_action( 'wp_ajax_mc_remove_checkout_points', [$this, 'ajax_remove_points'] );
        add_action( 'wp_ajax_mc_apply_product_redemption_cart', [$this, 'ajax_apply_product_cart'] );
        add_action( 'wp_ajax_mc_remove_product_redemption_cart', [$this, 'ajax_remove_product_cart'] );
        
        add_action( 'woocommerce_cart_calculate_fees', [$this, 'apply_points_fee'] );
        add_action( 'woocommerce_checkout_order_processed', [$this, 'process_order_points_deduction'], 10, 1 );

        add_action( 'wp_footer', [$this, 'render_redemption_modal_and_js'] );
    }

    private function get_accurate_user_points($user_id) {
        if (!$user_id) return 0;
        $pts_n = get_user_meta($user_id, '_mc_user_points', true);
        $pts_o = get_user_meta($user_id, 'mc_points', true);
        return ($pts_n !== '') ? (int)$pts_n : (int)$pts_o;
    }

    // -----------------------------------------------------------------------------------
    // PART 1: AUTO-GIVEAWAYS LOGIC (UNTOUCHED)
    // -----------------------------------------------------------------------------------
    public function process_smart_triggers() {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( ! WC()->cart || WC()->cart->is_empty() ) return;

        static $is_processing = false;
        if ( $is_processing ) return;
        $is_processing = true;

        $giveaways = get_option('mc_pts_auto_giveaways', []);
        if ( empty($giveaways) ) { $is_processing = false; return; }

        $cart_total = 0; $gift_ids_in_cart = []; $bogo_products_in_cart = [];
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            if ( isset($cart_item['_mc_is_giveaway']) ) { $gift_ids_in_cart[$product_id] = $cart_item_key; } 
            else { $cart_total += ($cart_item['data']->get_price() * $cart_item['quantity']); $bogo_products_in_cart[] = $product_id; }
        }

        $eligible_gifts = []; $user_id = get_current_user_id();
        $order_count = $user_id ? wc_get_customer_order_count( $user_id ) : 0;
        $highest_spend_threshold = -1; $spend_gift = null;

        foreach ($giveaways as $g) {
            if (!isset($g['active']) || $g['active'] !== 'yes' || empty($g['gift_id'])) continue;
            $type = $g['trigger_type'] ?? 'spend'; $gift_id = (int) $g['gift_id'];
            if ($type === 'spend') {
                $threshold = (float) $g['threshold'];
                if ($cart_total >= $threshold && $threshold > $highest_spend_threshold) { $highest_spend_threshold = $threshold; $spend_gift = $g; }
            } elseif ($type === 'first_order') {
                if ($order_count === 0 && is_user_logged_in()) { $eligible_gifts[$gift_id] = $g; }
            } elseif ($type === 'user_meta') {
                if (is_user_logged_in() && !empty($g['meta_key'])) {
                    if (get_user_meta($user_id, sanitize_text_field($g['meta_key']), true)) { $eligible_gifts[$gift_id] = $g; }
                }
            } elseif ($type === 'bogo') {
                if (in_array((int) $g['req_product'], $bogo_products_in_cart)) { $eligible_gifts[$gift_id] = $g; }
            }
        }
        if ($spend_gift) { $eligible_gifts[(int)$spend_gift['gift_id']] = $spend_gift; }

        foreach ($eligible_gifts as $gift_id => $g) {
            if (!isset($gift_ids_in_cart[$gift_id])) {
                WC()->cart->add_to_cart($gift_id, 1, 0, [], ['_mc_is_giveaway' => true, '_mc_giveaway_msg' => $g['msg'], '_mc_trigger_meta_key' => ($g['trigger_type'] === 'user_meta') ? sanitize_text_field($g['meta_key']) : '']);
                if (!defined('DOING_AJAX')) { wc_add_notice( esc_html(!empty($g['msg']) ? $g['msg'] : 'Congrats! You unlocked a free gift!'), 'success' ); }
            }
        }
        foreach ($gift_ids_in_cart as $gift_id => $cart_key) {
            if (!isset($eligible_gifts[$gift_id])) {
                WC()->cart->remove_cart_item($cart_key);
                if (!defined('DOING_AJAX')) { wc_add_notice( 'A free gift was removed from your cart as the requirements are no longer met.', 'notice' ); }
            }
        }
        $is_processing = false;
    }

    public function set_giveaway_prices_to_zero( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;
        foreach ( $cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['_mc_is_giveaway'] ) && $cart_item['_mc_is_giveaway'] === true ) { $cart_item['data']->set_price( 0 ); }
        }
    }

    public function consume_app_task_flags( $order_id ) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        $customer_id = $order->get_customer_id();
        if (!$customer_id) return;
        foreach ($order->get_items() as $item) {
            $meta_key = $item->get_meta('_mc_trigger_meta_key');
            if (!empty($meta_key)) { delete_user_meta($customer_id, $meta_key); }
        }
    }

    // -----------------------------------------------------------------------------------
    // PART 2: UI INJECTIONS (CART ITEM TEXT & GLOBAL SLIDER)
    // -----------------------------------------------------------------------------------
    public function add_giveaway_badge_to_cart_name( $item_name, $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['_mc_is_giveaway'] ) && $cart_item['_mc_is_giveaway'] === true ) {
            $badge = '<span style="background:#e74c3c; color:#fff; font-size:10px; padding:3px 8px; border-radius:12px; margin-left:8px; vertical-align:middle; font-weight:bold; letter-spacing:0.5px;">FREE GIFT</span>';
            $item_name .= $badge;
        }

        $is_product_mode = get_option('mc_pts_prod_enable', 'no') === 'yes';

        if ( $is_product_mode && is_user_logged_in() && class_exists('MC_Points_Account') ) {
            $product = $cart_item['data'];
            $cost = MC_Points_Account::get_product_point_cost($product);
            
            if ($cost && $cost > 0) {
                $user_id = get_current_user_id();
                $balance = $this->get_accurate_user_points($user_id);
                $redeemed_key = WC()->session->get('mc_redeemed_cart_item');

                if ($redeemed_key === $cart_item_key) {
                    $item_name .= '<div style="margin-top:5px; font-size:12px; color:#2ecc71; font-weight:bold;">✅ Redeemed (-'.number_format($cost).' Points) <a href="#" class="mc-remove-product-redemption mc-remove-hover" style="color:#e74c3c; text-decoration:underline; margin-left:10px; cursor:pointer;">Remove</a></div>';
                } elseif ($balance >= $cost) {
                    $item_name .= '<div style="margin-top:5px;"><a href="#" class="mc-trigger-cart-redemption" data-key="'.esc_attr($cart_item_key).'" data-product="'.esc_attr($product->get_name()).'" data-cost="'.esc_attr($cost).'" style="font-size:12px; color:#3498db; font-weight:bold; text-decoration:underline; cursor:pointer; transition: opacity 0.2s;">🎁 Redeem for '.number_format($cost).' Points</a></div>';
                } else {
                    $item_name .= '<div style="margin-top:5px; font-size:12px; color:#999;">🔒 Requires '.number_format($cost).' Points</div>';
                }
            }
        }
        return $item_name;
    }

    public function render_redemption_box() {
        if ( ! is_user_logged_in() || WC()->cart->is_empty() ) return;

        $is_product_mode = get_option('mc_pts_prod_enable', 'no') === 'yes';
        if ( $is_product_mode ) {
            return; 
        }

        $rules = get_option('mc_redeem_ui_settings', []); 
        if ( ($rules['allow_redeem'] ?? 'yes') !== 'yes' ) return;

        $user_id = get_current_user_id();
        $balance = $this->get_accurate_user_points($user_id);
        if ( $balance <= 0 ) return;

        $min_cart = (float)($rules['min_cart'] ?? 0);
        if ( $min_cart > 0 && WC()->cart->get_subtotal() < $min_cart ) return;

        $pts_ratio = (float)($rules['pts_ratio'] ?? 100);
        $cur_ratio = (float)($rules['cur_ratio'] ?? 1);
        if ($pts_ratio <= 0 || $cur_ratio <= 0) return;

        $cart_total = WC()->cart->get_subtotal();
        $max_discount_pct = (float)($rules['max_discount'] ?? 100);
        if ($max_discount_pct <= 0 || $max_discount_pct > 100) $max_discount_pct = 100;

        $max_discount_val = $cart_total * ($max_discount_pct / 100);
        $max_pts_allowed = floor(($max_discount_val / $cur_ratio) * $pts_ratio);
        $usable_points = min($balance, $max_pts_allowed);
        if ($usable_points <= 0) return;

        $applied = WC()->session->get('mc_points_applied') ? (int)WC()->session->get('mc_points_applied') : 0;
        $custom = get_option('mc_customization_settings', []); 
        $style  = $custom['box_style'] ?? 'slider';

        if ($style === 'hidden') return;

        ?>
        <div class="mc-redemption-box" style="background:<?php echo esc_attr($custom['box_bg'] ?? '#fef8ee'); ?>; border:2px solid <?php echo esc_attr($custom['box_border'] ?? '#f6c064'); ?>; padding:25px; border-radius:12px; margin-bottom:30px; box-shadow:0 4px 15px rgba(0,0,0,0.03);">
            <h3 style="margin:0 0 15px 0; font-size:18px; color:#111; font-weight:800;"><?php echo esc_html($custom['box_title'] ?? 'Use your Loyalty Points'); ?></h3>
            
            <?php if ( $applied > 0 ): ?>
                <div style="background:#fff; border:1px solid #eee; padding:15px; border-radius:8px; display:flex; justify-content:space-between; align-items:center;">
                    <span style="color:#2ecc71; font-weight:bold;">✅ <?php echo number_format($applied); ?> Points Applied for a <?php echo wc_price(($applied / $pts_ratio) * $cur_ratio); ?> discount!</span>
                    <button type="button" id="mc-remove-pts" style="background:transparent; border:none; color:#e74c3c; cursor:pointer; text-decoration:underline; font-weight:bold;">Remove</button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:15px;">
                    <p style="margin:0; font-size:14px; color:#666;">You have <strong><?php echo number_format($balance); ?></strong> points available.</p>
                    <div style="display:flex; gap:15px; align-items:center;">
                        <?php if ($style === 'slider'): ?>
                            <div style="flex:1;">
                                <input type="range" id="mc-pts-range" min="0" max="<?php echo esc_attr($usable_points); ?>" value="0" style="width:100%;">
                                <div style="text-align:center; font-weight:800; margin-top:10px;"><span id="mc-pts-val">0</span> Points</div>
                            </div>
                        <?php elseif ($style === 'input'): ?>
                            <input type="number" id="mc-pts-input" placeholder="Points to use..." style="flex:1; padding:10px; border-radius:6px; border:1px solid #ccc;">
                        <?php elseif ($style === 'toggle'): ?>
                            <div style="flex:1;">
                                <label style="display:flex; align-items:center; gap:10px; font-weight:bold; cursor:pointer;">
                                    <input type="checkbox" id="mc-pts-toggle" value="<?php echo esc_attr($usable_points); ?>">
                                    Use maximum available points
                                </label>
                            </div>
                        <?php endif; ?>
                        <button type="button" id="mc-apply-pts" style="background:<?php echo esc_attr($custom['btn_bg'] ?? '#d35400'); ?>; color:<?php echo esc_attr($custom['btn_text'] ?? '#ffffff'); ?>; border:none; padding:12px 25px; border-radius:6px; font-weight:bold; cursor:pointer;">Apply Discount</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    // -----------------------------------------------------------------------------------
    // PART 3: APPLYING DISCOUNTS & POINT DEDUCTIONS
    // -----------------------------------------------------------------------------------
    public function apply_points_fee( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        
        $is_product_mode = get_option('mc_pts_prod_enable', 'no') === 'yes';

        if ( $is_product_mode ) {
            $redeemed_key = WC()->session->get('mc_redeemed_cart_item');
            if ( $redeemed_key && isset($cart->cart_contents[$redeemed_key]) ) {
                $cart_item = $cart->cart_contents[$redeemed_key];
                
                $base_only = get_option('mc_pts_prod_base_price_only', 'no') === 'yes';
                $customer_pays_tax = get_option('mc_pts_prod_tax_override', 'no') === 'yes';
                
                $price = $base_only ? $cart_item['data']->get_regular_price() : $cart_item['data']->get_price();
                $is_taxable = !$customer_pays_tax;

                if ($price > 0) {
                    $cart->add_fee( 'Reward: ' . $cart_item['data']->get_name(), -$price, $is_taxable );
                }
            }
        } else {
            $applied = WC()->session->get('mc_points_applied');
            if ( $applied ) {
                $rules = get_option('mc_redeem_ui_settings', []);
                $pts_ratio = (float)($rules['pts_ratio'] ?? 100);
                $cur_ratio = (float)($rules['cur_ratio'] ?? 1);
                if ($pts_ratio > 0) {
                    $discount = ( $applied / $pts_ratio ) * $cur_ratio;
                    $custom = get_option('mc_customization_settings', []);
                    $label = !empty($custom['lbl_btn_redeem']) ? $custom['lbl_btn_redeem'] : 'Points Discount';
                    if ( $discount > 0 ) {
                        $cart->add_fee( $label, -$discount );
                    }
                }
            }
        }
    }

    public function process_order_points_deduction( $order_id ) {
        $user_id = get_current_user_id();
        if (!$user_id) return;

        $points_to_deduct = 0;
        $reason = '';
        $is_product_mode = get_option('mc_pts_prod_enable', 'no') === 'yes';

        if ( $is_product_mode ) {
            $redeemed_key = WC()->session->get('mc_redeemed_cart_item');
            if ( $redeemed_key && isset(WC()->cart->cart_contents[$redeemed_key]) ) {
                $cart_item = WC()->cart->cart_contents[$redeemed_key];
                if (class_exists('MC_Points_Account')) {
                    $cost = MC_Points_Account::get_product_point_cost($cart_item['data']);
                    if ($cost) {
                        $points_to_deduct += $cost;
                        $reason = 'Redeemed Product: ' . $cart_item['data']->get_name();
                    }
                }
                WC()->session->__unset('mc_redeemed_cart_item');
            }
        } else {
            $applied = WC()->session->get('mc_points_applied');
            if ( $applied ) {
                $points_to_deduct += $applied;
                $reason = 'Cart Discount Applied';
                WC()->session->__unset('mc_points_applied');
            }
        }

        if ($points_to_deduct > 0) {
            $current_pts = $this->get_accurate_user_points($user_id);
            $new_pts = max(0, $current_pts - $points_to_deduct);
            
            update_user_meta($user_id, '_mc_user_points', $new_pts);
            update_user_meta($user_id, 'mc_points', $new_pts);

            $history = get_user_meta($user_id, '_mc_points_history', true);
            if (!is_array($history)) $history = [];
            array_unshift($history, [
                'id'      => uniqid(),
                'date'    => current_time('timestamp'),
                'reason'  => $reason,
                'order'   => $order_id,
                'diff'    => -$points_to_deduct,
                'balance' => $new_pts
            ]);
            update_user_meta($user_id, '_mc_points_history', array_slice($history, 0, 200));
        }
    }

    public function ajax_apply_points() {
        $pts = intval($_POST['points']);
        if ($pts > 0) WC()->session->set('mc_points_applied', $pts);
        wp_send_json_success();
    }
    public function ajax_remove_points() {
        WC()->session->__unset('mc_points_applied');
        wp_send_json_success();
    }
    public function ajax_apply_product_cart() {
        $key = sanitize_text_field($_POST['cart_item_key']);
        WC()->session->set('mc_redeemed_cart_item', $key);
        wp_send_json_success();
    }
    public function ajax_remove_product_cart() {
        WC()->session->__unset('mc_redeemed_cart_item');
        wp_send_json_success();
    }

    // -----------------------------------------------------------------------------------
    // PART 4: PRODUCT REDEMPTION POPUP UI & BULLETPROOF JS
    // -----------------------------------------------------------------------------------
    public function render_redemption_modal_and_js() {
        if (!is_user_logged_in()) return;
        
        $custom = get_option('mc_customization_settings', []);
        if ( ($custom['pop_enable'] ?? 'yes') !== 'yes' ) return;

        $user_id = get_current_user_id();
        $balance = $this->get_accurate_user_points($user_id);

        $bg = $custom['pop_bg'] ?? '#ffffff';
        $txt = $custom['pop_text_color'] ?? '#111111';
        $btn = $custom['pop_btn_color'] ?? '#2ecc71';
        
        $cat_settings = get_option('mc_pts_catalog_settings', []);
        $terms_url = $cat_settings['terms_url'] ?? '';
        $max_per_cart = get_option('mc_pts_prod_max_per_cart', '1');
        
        $desc_template = esc_js($custom['pop_desc'] ?? 'Are you sure you want to spend {points} points to get {product} for free?');
        $ajax_url = esc_url(admin_url('admin-ajax.php'));

        ?>
        <style>
            .mc-modal-btns { display: flex; gap: 15px; margin-top: 25px; }
            .mc-btn-hover { transition: all 0.2s ease-in-out !important; }
            .mc-btn-hover:hover { transform: translateY(-2px); filter: brightness(0.9); }
            @media (max-width: 480px) { .mc-modal-btns { flex-direction: column; gap: 10px; } }
        </style>

        <div id="mc-redeem-modal" data-balance="<?php echo esc_attr($balance); ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:999999; align-items:center; justify-content:center;">
            <div style="background:<?php echo esc_attr($bg); ?>; padding:40px; border-radius:15px; max-width:450px; width:90%; text-align:center; color:<?php echo esc_attr($txt); ?>; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                <h2 id="mc-pop-title" style="margin-top:0; font-weight:900; color:inherit;"><?php echo esc_html($custom['pop_title'] ?? 'Unlock this Reward?'); ?></h2>
                <p id="mc-pop-desc" style="font-size:15px; line-height:1.5; margin-bottom:20px; color:inherit;"></p>
                
                <p style="font-size:13px; color:#e74c3c; font-weight:bold; margin-top:-10px; margin-bottom:20px;">Limit: <?php echo esc_html($max_per_cart); ?> reward redemption(s) per order.</p>

                <div style="background:#f9f9f9; padding:15px; border-radius:8px; text-align:left; margin-bottom:20px; font-size:14px; color:#333; border:1px solid #eee;">
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px;">
                        <span>Current Balance:</span><strong id="mc-pop-current-bal">0</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; margin-bottom:8px; color:#e74c3c;">
                        <span>Points to Use:</span><strong id="mc-pop-cost">- 0</strong>
                    </div>
                    <div style="display:flex; justify-content:space-between; border-top:1px solid #ddd; padding-top:8px; font-weight:bold;">
                        <span>Remaining Balance:</span><strong id="mc-pop-remaining" style="color:#2ecc71;">0</strong>
                    </div>
                </div>

                <?php if (!empty($terms_url)): ?>
                    <a href="<?php echo esc_url($terms_url); ?>" target="_blank" style="font-size:12px; color:#888; text-decoration:underline;">See Rules & Terms</a>
                <?php endif; ?>

                <div class="mc-modal-btns">
                    <button id="mc-confirm-redemption" class="mc-btn-hover" style="flex:1; background:<?php echo esc_attr($btn); ?>; color:#fff; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;"><?php echo esc_html($custom['pop_btn_yes'] ?? 'Yes, Unlock It!'); ?></button>
                    <button class="mc-btn-hover" onclick="jQuery('#mc-redeem-modal').css('display', 'none');" style="flex:1; background:#eee; color:#333; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;"><?php echo esc_html($custom['pop_btn_no'] ?? 'Cancel'); ?></button>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var mcAjaxUrl = "<?php echo $ajax_url; ?>";
            var mcDescTemplate = "<?php echo $desc_template; ?>";

            $('#mc-pts-range').on('input', function() { $('#mc-pts-val').text(parseInt($(this).val()).toLocaleString()); });
            $(document).on('click', '#mc-apply-pts', function(e) {
                e.preventDefault();
                let pts = $('#mc-pts-range').length ? $('#mc-pts-range').val() : $('#mc-pts-input').val();
                if (pts > 0) {
                    $(this).text('Applying...').css('opacity', '0.7');
                    $.post(mcAjaxUrl, { action: 'mc_apply_checkout_points', points: pts }, function() {
                        $('body').trigger('update_checkout');
                        location.reload();
                    });
                }
            });
            $(document).on('click', '#mc-remove-pts', function(e) {
                e.preventDefault();
                $(this).text('Removing...').css('opacity', '0.7');
                $.post(mcAjaxUrl, { action: 'mc_remove_checkout_points' }, function() {
                    $('body').trigger('update_checkout');
                    location.reload();
                });
            });

            $(document).on('click', '.mc-trigger-redemption, .mc-trigger-cart-redemption', function(e) {
                e.preventDefault();
                let prodName = $(this).data('product');
                let pts = parseInt($(this).data('points')) || parseInt($(this).data('cost'));
                let key = $(this).data('key') || ''; 
                let url = $(this).attr('href');
                
                let balance = parseInt($('#mc-redeem-modal').data('balance')) || 0;
                let remaining = balance - pts;

                let finalDesc = mcDescTemplate.replace('{points}', pts.toLocaleString()).replace('{product}', prodName);
                
                $('#mc-pop-desc').text(finalDesc);
                $('#mc-pop-current-bal').text(balance.toLocaleString() + ' Pts');
                $('#mc-pop-cost').text('- ' + pts.toLocaleString() + ' Pts');
                $('#mc-pop-remaining').text(remaining.toLocaleString() + ' Pts');
                
                $('#mc-confirm-redemption').off('click').on('click', function() {
                    $(this).text('Processing...').css('opacity', '0.7');
                    
                    if (key !== '') {
                        $.post(mcAjaxUrl, { action: 'mc_apply_product_redemption_cart', cart_item_key: key }, function() {
                            $('body').trigger('update_checkout');
                            location.reload();
                        });
                    } else {
                        window.location.href = url; 
                    }
                });
                
                $('#mc-redeem-modal').css('display', 'flex');
            });

            $(document).on('click', '.mc-remove-product-redemption', function(e) {
                e.preventDefault();
                $(this).text('Removing...').css('opacity', '0.7');
                $.post(mcAjaxUrl, { action: 'mc_remove_product_redemption_cart' }, function() {
                    $('body').trigger('update_checkout');
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }
}
new MC_Points_Checkout();