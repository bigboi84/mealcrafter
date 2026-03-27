<?php
/**
 * MealCrafter: Loyalty Checkout & Cart Logic
 * Updated: Thursday, March 26, 2026
 * Logic: Single Base Wipe + Elegant Cart Row UI + Settings Dashboard
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Checkout {

    public function __construct() {
        // ADMIN SETTINGS DASHBOARD FOR UI CUSTOMIZATION
        add_action('admin_menu', [$this, 'add_ui_settings_page']);

        // 1. SMART TRIGGERS & AUTO-GIVEAWAYS
        add_action( 'template_redirect', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_add_to_cart', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_cart_item_removed', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_before_calculate_totals', [$this, 'set_giveaway_prices_to_zero'], 9999 );
        add_action( 'woocommerce_checkout_order_processed', [$this, 'consume_app_task_flags'], 10, 1 );

        // CAR ROW UI HIGHLIGHT & CUSTOM BREAKDOWN (Priority 20 to wrap the Combo HTML)
        add_filter( 'woocommerce_cart_item_name', [$this, 'inject_reward_custom_ui'], 20, 3 );
        add_filter( 'woocommerce_cart_item_class', [$this, 'highlight_reward_cart_row'], 10, 3 );
        add_action( 'wp_head', [$this, 'inject_reward_styles'] );

        // 2. POINTS REDEMPTION
        $custom = get_option('mc_customization_settings', []);
        $box_pos = !empty($custom['box_pos']) ? $custom['box_pos'] : 'woocommerce_before_checkout_form';
        
        add_action( $box_pos, [$this, 'render_redemption_box'] );
        add_action( 'woocommerce_before_cart', [$this, 'render_redemption_box'] );

        // AJAX Handlers
        add_action( 'wp_ajax_mc_apply_checkout_points', [$this, 'ajax_apply_points'] );
        add_action( 'wp_ajax_mc_remove_checkout_points', [$this, 'ajax_remove_points'] );
        add_action( 'wp_ajax_mc_apply_product_redemption_cart', [$this, 'ajax_apply_product_cart'] );
        add_action( 'wp_ajax_mc_remove_product_redemption_cart', [$this, 'ajax_remove_product_cart'] );
        
        add_action( 'woocommerce_cart_calculate_fees', [$this, 'apply_points_fee'], 9999 );
        add_action( 'woocommerce_checkout_order_processed', [$this, 'process_order_points_deduction'], 10, 1 );

        add_action( 'wp_footer', [$this, 'render_redemption_modal_and_js'] );
    }

    // -----------------------------------------------------------------------------------
    // PART 0: BACKEND CUSTOMIZATION SETTINGS (Added strictly for Cart UI Design)
    // -----------------------------------------------------------------------------------
    public function add_ui_settings_page() {
        add_submenu_page(
            'woocommerce',
            'Reward Cart UI',
            'Reward Cart UI',
            'manage_woocommerce',
            'mc-reward-cart-ui',
            [$this, 'render_ui_settings_page']
        );
    }

    public function render_ui_settings_page() {
        if ( isset($_POST['mc_cart_ui_nonce']) && wp_verify_nonce($_POST['mc_cart_ui_nonce'], 'mc_save_cart_ui') ) {
            update_option('mc_cart_ui_congrats', sanitize_text_field($_POST['mc_cart_ui_congrats']));
            update_option('mc_cart_ui_free', sanitize_text_field($_POST['mc_cart_ui_free']));
            update_option('mc_cart_ui_note', sanitize_text_field($_POST['mc_cart_ui_note']));
            update_option('mc_cart_ui_pts_label', sanitize_text_field($_POST['mc_cart_ui_pts_label']));
            update_option('mc_cart_ui_remove', sanitize_text_field($_POST['mc_cart_ui_remove']));
            echo '<div class="updated"><p>Cart UI Settings successfully saved.</p></div>';
        }
        
        $congrats = get_option('mc_cart_ui_congrats', '🎉 Congratulations!');
        $free = get_option('mc_cart_ui_free', 'FREE');
        $note = get_option('mc_cart_ui_note', '* Note: Customer pays for premium upgrades.');
        $pts = get_option('mc_cart_ui_pts_label', 'pts');
        $remove = get_option('mc_cart_ui_remove', 'Remove Reward');
        
        ?>
        <div class="wrap">
            <h1>Reward Cart UI Customization</h1>
            <p>Edit the labels and warnings displayed inside the Cart row when a user redeems a product.</p>
            <form method="POST">
                <?php wp_nonce_field('mc_save_cart_ui', 'mc_cart_ui_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label>Congratulations Message</label></th>
                        <td><input type="text" name="mc_cart_ui_congrats" value="<?php echo esc_attr($congrats); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Free Badge Text</label></th>
                        <td><input type="text" name="mc_cart_ui_free" value="<?php echo esc_attr($free); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Premium Upgrade Warning Note</label></th>
                        <td><input type="text" name="mc_cart_ui_note" value="<?php echo esc_attr($note); ?>" class="regular-text" style="width:100%;max-width:500px;"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Points Abbreviation (e.g. 'pts')</label></th>
                        <td><input type="text" name="mc_cart_ui_pts_label" value="<?php echo esc_attr($pts); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Remove Button Text</label></th>
                        <td><input type="text" name="mc_cart_ui_remove" value="<?php echo esc_attr($remove); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" class="button-primary" value="Save UI Settings"></p>
            </form>
        </div>
        <?php
    }

    private function get_accurate_user_points($user_id) {
        if (!$user_id) return 0;
        $pts_n = get_user_meta($user_id, '_mc_user_points', true);
        $pts_o = get_user_meta($user_id, 'mc_points', true);
        return ($pts_n !== '') ? (int)$pts_n : (int)$pts_o;
    }

    // -----------------------------------------------------------------------------------
    // PART 1: AUTO-GIVEAWAYS LOGIC
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
    // PART 2: THE ELEGANT CART ROW UI & HIGHLIGHT ENGINE
    // -----------------------------------------------------------------------------------
    public function highlight_reward_cart_row( $class, $cart_item, $cart_item_key ) {
        if ( WC()->session->get('mc_redeemed_cart_item') === $cart_item_key ) {
            $class .= ' mc-reward-active-row';
        }
        return $class;
    }

    public function inject_reward_styles() {
        $custom = get_option('mc_customization_settings', []);
        $bg_color = $custom['cart_ui_bg_color'] ?? '#fdfbf7';
        $border_color = $custom['cart_ui_border_color'] ?? '#f6c064';
        ?>
        <style>
            tr.mc-reward-active-row {
                background-color: <?php echo esc_attr($bg_color); ?> !important;
                border: 2px dashed <?php echo esc_attr($border_color); ?> !important;
                box-shadow: inset 0 0 10px rgba(246, 192, 100, 0.1) !important;
            }
            tr.mc-reward-active-row td {
                background-color: transparent !important;
                border-bottom: none !important;
            }
        </style>
        <?php
    }

    public function inject_reward_custom_ui( $item_name, $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['_mc_is_giveaway'] ) && $cart_item['_mc_is_giveaway'] === true ) {
            $badge = '<span style="background:#e74c3c; color:#fff; font-size:10px; padding:3px 8px; border-radius:12px; margin-left:8px; vertical-align:middle; font-weight:bold; letter-spacing:0.5px; box-shadow:0 2px 4px rgba(231,76,60,0.3);">FREE GIFT</span>';
            return $item_name . $badge;
        }

        $pm_val = get_option('mc_pts_prod_enable', 'no');
        $is_product_mode = in_array( strtolower( (string) $pm_val ), ['yes', 'on', '1', 'true'], true );

        if ( $is_product_mode && is_user_logged_in() && class_exists('MC_Points_Account') ) {
            $product = $cart_item['data'];
            $cost = MC_Points_Account::get_product_point_cost($product);
            
            if ($cost && $cost > 0) {
                $user_id = get_current_user_id();
                $balance = $this->get_accurate_user_points($user_id);
                $redeemed_key = WC()->session->get('mc_redeemed_cart_item');

                $dwc_val = get_option('mc_pts_prod_disable_with_coupons', 'no');
                $disable_with_coupons = in_array( strtolower( (string) $dwc_val ), ['yes', 'on', '1', 'true'], true );
                $has_coupons = !empty(WC()->cart->get_applied_coupons());

                $base_val = get_option('mc_pts_prod_base_price_only', 'yes');
                $base_only = in_array( strtolower( (string) $base_val ), ['yes', 'on', '1', 'true'], true );
                
                // Fetch dynamic labels & colors from the Customization Dashboard
                $custom = get_option('mc_customization_settings', []);
                $congrats_text = $custom['cart_ui_congrats'] ?? '🎉 Congratulations!';
                $free_text = $custom['cart_ui_free'] ?? 'FREE';
                $note_text = $custom['cart_ui_note'] ?? '* Note: Customer pays for premium upgrades.';
                $pts_label = $custom['cart_ui_pts_label'] ?? 'pts';
                $remove_text = $custom['cart_ui_remove'] ?? 'Remove Reward';
                $border_weight = $custom['cart_ui_border_weight'] ?? '2';
                $border_color = $custom['cart_ui_border_color'] ?? '#e67e22';
                $congrats_color = $custom['cart_ui_congrats_color'] ?? '#e67e22';
                $free_color = $custom['cart_ui_free_color'] ?? '#2ecc71';
                $note_color = $custom['cart_ui_note_color'] ?? '#d35400';

                $qty = isset($cart_item['quantity']) ? (int) $cart_item['quantity'] : 1;

                if ($disable_with_coupons && $has_coupons) {
                    $item_name .= '<div style="margin-top:5px; font-size:12px; color:#e74c3c; font-weight:bold;">🔒 Cannot be combined with coupons.</div>';
                } else {
                    if ($redeemed_key === $cart_item_key) {
                        
                        // 1. Extract upgrades to dynamically inject them into the display
                        $upgrade_cost = 0;
                        $upgrade_names = [];
                        if ( isset($cart_item['mc_combo_selections']) && is_array($cart_item['mc_combo_selections']) ) {
                            $math_logic = get_option( 'mc_combo_math_logic', 'on' );
                            $highest_extra = 0;
                            $highest_name = '';

                            if ( $math_logic === 'on' ) {
                                foreach ( $cart_item['mc_combo_selections'] as $sel ) {
                                    $id = is_array($sel) ? $sel['id'] : $sel;
                                    $p = wc_get_product( $id );
                                    if ( $p && (float)$p->get_price() > $highest_extra ) {
                                        $highest_extra = (float)$p->get_price();
                                        $clean_name = preg_replace('/\s*\(\+?\s*\$?[0-9.]+\)/', '', $p->get_name());
                                        $highest_name = trim(strip_tags($clean_name));
                                    }
                                }
                                if ($highest_extra > 0) {
                                    $upgrade_cost = $highest_extra;
                                    $upgrade_names[] = $highest_name;
                                }
                            } else {
                                foreach ( $cart_item['mc_combo_selections'] as $sel ) {
                                    $id = is_array($sel) ? $sel['id'] : $sel;
                                    $p = wc_get_product( $id );
                                    if ( $p && (float)$p->get_price() > 0 ) {
                                        $upgrade_cost += (float)$p->get_price();
                                        $clean_name = preg_replace('/\s*\(\+?\s*\$?[0-9.]+\)/', '', $p->get_name());
                                        $upgrade_names[] = trim(strip_tags($clean_name));
                                    }
                                }
                            }
                        }

                        // 2. Safely capture the Combo selections generated by mc-combo
                        $combo_html = '';
                        if ( strpos( $item_name, '<div class="mc-cart-combo-summary"' ) !== false ) {
                            $parts = explode( '<div class="mc-cart-combo-summary"', $item_name );
                            if ( isset($parts[1]) ) {
                                $combo_html = '<div class="mc-cart-combo-summary"' . $parts[1];
                            }
                        }

                        // 3. Build the beautiful nested box inside the table row column
                        $html = '<div style="margin-top: 12px; padding: 12px 15px; border: ' . esc_attr($border_weight) . 'px ' . esc_attr($border_style) . ' ' . esc_attr($border_color) . '; border-radius: 8px;">';
                        $html .= '<div style="font-size: 11px; text-transform: uppercase; font-weight: 800; color: ' . esc_attr($congrats_color) . '; margin-bottom: 4px; letter-spacing: 0.5px;">' . esc_html($congrats_text) . '</div>';

                        // Base Item, Quantity (Injected Inline), and Free/Pts Block
                        $qty_html = '<span style="font-weight:900; color:#222; margin-right:4px;">(' . $qty . ')</span>';
                        
                        $html .= '<div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #333; margin-bottom: 6px;">';
                        $html .= '<span>Loyalty Reward: ' . $qty_html . '<strong><a href="' . esc_url( $product->get_permalink( $cart_item ) ) . '" style="color:#333; text-decoration:none;">' . esc_html($product->get_name()) . '</a></strong></span>';
                        
                        // Right side wrapper: FREE text FIRST, Points badge to the RIGHT, nudged up 1px!
                        $html .= '<div style="display: flex; align-items: center; gap: 8px;">';
                        $html .= '<strong style="color: ' . esc_attr($free_color) . ';">' . esc_html($free_text) . '</strong>';
                        $html .= '<span style="background: #e74c3c; color: #fff; font-size: 11px; font-weight: bold; padding: 2px 8px; border-radius: 12px; box-shadow: 0 2px 4px rgba(231,76,60,0.3); white-space: nowrap; transform: translateY(-1px);">- ' . number_format($cost) . ' ' . esc_html($pts_label) . '</span>';
                        $html .= '</div>';
                        $html .= '</div>';

                        // Premium Upgrades Breakdown
                        if ( $base_only ) {
                            if ( $upgrade_cost > 0 ) {
                                $upg_label = !empty($upgrade_names) ? implode(', ', $upgrade_names) : 'Premium Upgrades';
                                $html .= '<div style="display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #333; margin-bottom: 6px;">';
                                $html .= '<span>Premium Upgrade: <strong>' . esc_html($upg_label) . '</strong></span>';
                                $html .= '<strong>' . wc_price($upgrade_cost) . '</strong>';
                                $html .= '</div>';
                            }
                            $html .= '<div style="font-size: 11px; color: ' . esc_attr($note_color) . '; font-style: italic; margin-top: 4px; font-weight: 600;">' . esc_html($note_text) . '</div>';
                        } else {
                            // The 100% Free UI
                            $html .= '<div style="font-size: 11px; color: ' . esc_attr($free_color) . '; font-style: italic; margin-top: 4px; font-weight: 600;">Entire item and all premium upgrades are free.</div>';
                        }

                        // PERFECT WRAPPING: The Combo selections are injected beautifully!
                        if ( !empty($combo_html) ) {
                            $html .= '<div style="margin-top: 10px; padding-top: 10px; border-top: 1px dashed #eedcc9;">';
                            $html .= $combo_html;
                            $html .= '</div>';
                        }

                        // Removal Button
                        $html .= '<div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eedcc9; text-align: right;">';
                        $html .= '<a href="#" class="mc-remove-product-redemption" style="font-size: 11px; color: #e74c3c; font-weight: bold; text-decoration: underline;">' . esc_html($remove_text) . '</a>';
                        $html .= '</div>';
                        
                        $html .= '</div>';

                        return $html;

                    } elseif ($balance >= $cost) {
                        $disclaimer_html = '';
                        if ($base_only) {
                            $disclaimer_html = '<div style="font-size:11px; color:#d35400; font-style:italic; margin-top:4px; font-weight:600;">' . esc_html($note_text) . '</div>';
                        }
                        $item_name .= '<div style="margin-top:5px;"><a href="#" class="mc-trigger-cart-redemption" data-key="'.esc_attr($cart_item_key).'" data-product="'.esc_attr($product->get_name()).'" data-cost="'.esc_attr($cost).'" style="font-size:12px; color:#3498db; font-weight:bold; text-decoration:underline; cursor:pointer; transition: opacity 0.2s;">🎁 Redeem for '.number_format($cost).' ' . esc_html($pts_label) . '</a></div>' . $disclaimer_html;
                    } else {
                        $item_name .= '<div style="margin-top:5px; font-size:12px; color:#999;">🔒 Requires '.number_format($cost).' ' . esc_html($pts_label) . '</div>';
                    }
                }
            }
        }
        return $item_name;
    }

    public function render_redemption_box() {
        if ( ! is_user_logged_in() || WC()->cart->is_empty() ) return;

        $pm_val = get_option('mc_pts_prod_enable', 'no');
        $is_product_mode = in_array( strtolower( (string) $pm_val ), ['yes', 'on', '1', 'true'], true );
        if ( $is_product_mode ) {
            return; 
        }

        $dwc_val = get_option('mc_pts_prod_disable_with_coupons', 'no');
        $disable_with_coupons = in_array( strtolower( (string) $dwc_val ), ['yes', 'on', '1', 'true'], true );
        if ( $disable_with_coupons && !empty(WC()->cart->get_applied_coupons()) ) {
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
        <style>
            .mc-btn-hover { transition: all 0.2s ease-in-out !important; }
            .mc-btn-hover:hover { transform: translateY(-2px); filter: brightness(0.9); }
            .mc-remove-hover { transition: color 0.2s ease; }
            .mc-remove-hover:hover { color: #c0392b !important; text-decoration: none !important; }
            .mc-flex-row-mobile { display: flex; gap: 15px; align-items: center; }
            @media (max-width: 480px) {
                .mc-flex-row-mobile { flex-direction: column; align-items: stretch; }
                .mc-flex-row-mobile button { width: 100%; }
            }
        </style>
        <div class="mc-redemption-box" style="background:<?php echo esc_attr($custom['box_bg'] ?? '#fef8ee'); ?>; border:2px solid <?php echo esc_attr($custom['box_border'] ?? '#f6c064'); ?>; padding:25px; border-radius:12px; margin-bottom:30px; box-shadow:0 4px 15px rgba(0,0,0,0.03);">
            <h3 style="margin:0 0 15px 0; font-size:18px; color:#111; font-weight:800;"><?php echo esc_html($custom['box_title'] ?? 'Use your Loyalty Points'); ?></h3>
            
            <?php if ( $applied > 0 ): ?>
                <div style="background:#fff; border:1px solid #eee; padding:15px; border-radius:8px; display:flex; justify-content:space-between; align-items:center; flex-wrap: wrap; gap: 10px;">
                    <span style="color:#2ecc71; font-weight:bold;">✅ <?php echo number_format($applied); ?> Points Applied for a <?php echo wc_price(($applied / $pts_ratio) * $cur_ratio); ?> discount!</span>
                    <button type="button" id="mc-remove-pts" class="mc-remove-hover" style="background:transparent; border:none; color:#e74c3c; cursor:pointer; text-decoration:underline; font-weight:bold;">Remove</button>
                </div>
            <?php else: ?>
                <div style="display:flex; flex-direction:column; gap:15px;">
                    <p style="margin:0; font-size:14px; color:#666;">You have <strong><?php echo number_format($balance); ?></strong> points available.</p>
                    <div class="mc-flex-row-mobile">
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
                        <button type="button" id="mc-apply-pts" class="mc-btn-hover" style="background:<?php echo esc_attr($custom['btn_bg'] ?? '#d35400'); ?>; color:<?php echo esc_attr($custom['btn_text'] ?? '#ffffff'); ?>; border:none; padding:12px 25px; border-radius:6px; font-weight:bold; cursor:pointer;">Apply Discount</button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function apply_points_fee( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        
        $dwc_val = get_option('mc_pts_prod_disable_with_coupons', 'no');
        $disable_with_coupons = in_array( strtolower( (string) $dwc_val ), ['yes', 'on', '1', 'true'], true );

        if ( $disable_with_coupons && !empty($cart->get_applied_coupons()) ) {
            WC()->session->__unset('mc_redeemed_cart_item');
            WC()->session->__unset('mc_points_applied');
            return;
        }

        $pm_val = get_option('mc_pts_prod_enable', 'no');
        $is_product_mode = in_array( strtolower( (string) $pm_val ), ['yes', 'on', '1', 'true'], true );

        if ( $is_product_mode ) {
            $redeemed_key = WC()->session->get('mc_redeemed_cart_item');
            if ( $redeemed_key && isset($cart->cart_contents[$redeemed_key]) ) {
                $cart_item = $cart->cart_contents[$redeemed_key];
                $qty = isset($cart_item['quantity']) && $cart_item['quantity'] > 0 ? (int) $cart_item['quantity'] : 1;
                
                $orig_product = wc_get_product( $cart_item['product_id'] );
                if ( ! $orig_product ) return;

                $base_price_incl = (float) $orig_product->get_price(); 
                $is_taxable = $orig_product->is_taxable();
                $tax_class = $orig_product->get_tax_class();

                $tax_divisor = 1;
                if ( wc_prices_include_tax() && $is_taxable && class_exists('WC_Tax') ) {
                    $rates = WC_Tax::get_rates( $tax_class );
                    if ( !empty($rates) ) {
                        $tax_divisor += ( (float) reset($rates)['rate'] / 100 );
                    }
                }

                $base_val = get_option('mc_pts_prod_base_price_only', 'yes');
                $base_only = in_array( strtolower( (string) $base_val ), ['yes', 'on', '1', 'true'], true );

                $upgrade_cost = 0;
                if ( ! $base_only && isset($cart_item['mc_combo_selections']) && is_array($cart_item['mc_combo_selections']) ) {
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

                if ( $base_only ) {
                    $wipe_inclusive = $base_price_incl * $qty;
                    $reward_label = 'Loyalty Reward: ' . $orig_product->get_name();
                } else {
                    $wipe_inclusive = ($base_price_incl + $upgrade_cost) * $qty;
                    $reward_label = 'Loyalty Reward: ' . $orig_product->get_name();
                }

                $pre_tax_wipe = $wipe_inclusive / $tax_divisor; 
                $cart->add_fee( $reward_label, -$pre_tax_wipe, $is_taxable, $tax_class );
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
                        $cart->add_fee( $label, -round($discount, 2), false );
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
    // PART 4: PRODUCT REDEMPTION POPUP UI (PULLED FROM NEW OPTIONS)
    // -----------------------------------------------------------------------------------
    public function render_redemption_modal_and_js() {
        if (!is_user_logged_in()) return;
        
        if ( get_option('mc_pts_pop_enable', 'yes') !== 'yes' ) return;

        $user_id = get_current_user_id();
        $balance = $this->get_accurate_user_points($user_id);

        $bg = get_option('mc_pts_pop_bg', '#ffffff');
        $txt = get_option('mc_pts_pop_text_color', '#111111');
        $btn = get_option('mc_pts_pop_btn_color', '#2ecc71');

        $cat_settings = get_option('mc_pts_catalog_settings', []);
        $terms_url = $cat_settings['terms_url'] ?? '';
        $max_per_cart = get_option('mc_pts_prod_max_per_cart', '1');

        // Build Popup Disclaimer
        $base_val = get_option('mc_pts_prod_base_price_only', 'yes');
        $base_only = in_array( strtolower( (string) $base_val ), ['yes', 'on', '1', 'true'], true );

        $disclaimers = [];
        if ($base_only) $disclaimers[] = 'premium upgrades';

        $disclaimer_html = '';
        if (!empty($disclaimers)) {
            $disclaimer_html = '<div style="font-size:12px; color:#d35400; font-style:italic; margin-bottom:20px; font-weight:bold;">* Note: You are responsible for paying ' . implode(' and ', $disclaimers) . '.</div>';
        }

        $pop_title = get_option('mc_pts_pop_title', 'Unlock this Reward?');
        $pop_desc  = get_option('mc_pts_pop_desc', 'Are you sure you want to spend {points} points to get this item for free?');
        $pop_btn_yes = get_option('mc_pts_pop_btn_yes', 'Yes, Unlock It!');
        $pop_btn_no = get_option('mc_pts_pop_btn_no', 'Not right now');

        $desc_template = esc_js($pop_desc);
        $ajax_url = esc_url(admin_url('admin-ajax.php'));

        ?>
        <style>
            .mc-modal-btns { display: flex; gap: 15px; margin-top: 25px; }
            @media (max-width: 480px) { .mc-modal-btns { flex-direction: column; gap: 10px; } }
        </style>

        <div id="mc-redeem-modal" data-balance="<?php echo esc_attr($balance); ?>" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:999999; align-items:center; justify-content:center;">
            <div style="background:<?php echo esc_attr($bg); ?>; padding:40px; border-radius:15px; max-width:450px; width:90%; text-align:center; color:<?php echo esc_attr($txt); ?>; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                <h2 id="mc-pop-title" style="margin-top:0; font-weight:900; color:inherit;"><?php echo esc_html($pop_title); ?></h2>
                <p id="mc-pop-desc" style="font-size:15px; line-height:1.5; margin-bottom:20px; color:inherit;"></p>
                
                <p style="font-size:13px; color:#e74c3c; font-weight:bold; margin-top:-10px; margin-bottom:15px;">Limit: <?php echo esc_html($max_per_cart); ?> reward redemption(s) per order.</p>

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
                    <button id="mc-confirm-redemption" class="mc-btn-hover" style="flex:1; background:<?php echo esc_attr($btn); ?>; color:#fff; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;"><?php echo esc_html($pop_btn_yes); ?></button>
                    <button class="mc-btn-hover" onclick="jQuery('#mc-redeem-modal').css('display', 'none');" style="flex:1; background:#eee; color:#333; border:none; padding:12px; border-radius:8px; font-weight:bold; cursor:pointer;"><?php echo esc_html($pop_btn_no); ?></button>
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