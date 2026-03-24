<?php
/**
 * MealCrafter: Tab - Customization & Frontend Styling
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Customization {

    public function render() {
        if ( isset($_POST['mc_save_customization']) && isset($_POST['mc_custom_nonce']) && wp_verify_nonce($_POST['mc_custom_nonce'], 'mc_save_custom') && current_user_can('manage_options') ) {
            $current_settings = get_option('mc_customization_settings', []);
            $new_settings = isset($_POST['mc_custom']) ? wc_clean($_POST['mc_custom']) : []; 
            
            $merged = wp_parse_args($new_settings, $current_settings);
            update_option('mc_customization_settings', $merged);
            
            echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-top:20px; border-radius:4px; font-weight:600;">✅ Customization settings saved successfully!</div>';
        }

        $settings = get_option('mc_customization_settings', []);
        $current_sub = isset($_GET['sub']) ? sanitize_text_field($_GET['sub']) : 'general';
        
        // ADDED: The new Checkout UI & Popups Sub-Tab
        $subtabs = [
            'general'       => 'General',
            'shop'          => 'Points in Shop Pages',
            'product'       => 'Points in Product Page',
            'account'       => 'Points in My Account',
            'cart_checkout' => 'Points in Cart & Checkout',
            'checkout_ui'   => 'Checkout UI & Popups', 
            'giveaways'     => 'Auto-Giveaways UI',
            'labels'        => 'Labels'
        ];

        ?>
        <style>
            .mc-color-picker-small { height: 35px; width: 60px !important; padding: 0; cursor: pointer; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; }
        </style>

        <div class="mc-layout-wrapper">
            <div class="mc-sidebar-nav">
                <?php foreach($subtabs as $sub_key => $sub_name): ?>
                    <a href="?page=mc-loyalty-settings&tab=customization&sub=<?php echo esc_attr($sub_key); ?>" class="mc-subtab-link <?php echo $current_sub === $sub_key ? 'active' : ''; ?>">
                        <?php echo esc_html($sub_name); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mc-main-content">
                <div style="margin-bottom: 25px;">
                    <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;"><?php echo esc_html($subtabs[$current_sub]); ?></h2>
                    <p style="margin:0; font-size:13px; color:#646970;">Customize the texts, colors, and layout of the loyalty program on your frontend.</p>
                </div>

                <form method="post" action="">
                    <?php wp_nonce_field('mc_save_custom', 'mc_custom_nonce'); ?>
                    
                    <?php 
                    // ==========================================
                    // GENERAL TAB
                    // ==========================================
                    if ($current_sub === 'general'): 
                        $hide_guest = $settings['hide_guest'] ?? 'no';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #2271b1;">
                            <div class="mc-toggle-row">
                                <div class="mc-form-info" style="margin:0;">
                                    <span class="mc-form-label">Hide all messages to guest users</span>
                                    <span class="mc-form-desc">Enable this if you want to set up gamification strategies ONLY for registered users. Unregistered guests will not see earning prompts.</span>
                                </div>
                                <label class="mc-toggle-switch">
                                    <input type="hidden" name="mc_custom[hide_guest]" value="no">
                                    <input type="checkbox" name="mc_custom[hide_guest]" value="yes" <?php checked($hide_guest, 'yes'); ?>>
                                    <span class="mc-slider"></span>
                                </label>
                            </div>
                        </div>

                    <?php 
                    // ==========================================
                    // SHOP PAGES TAB
                    // ==========================================
                    elseif ($current_sub === 'shop'): 
                        $shop_show = $settings['shop_show'] ?? 'yes';
                        $shop_msg = $settings['shop_msg'] ?? 'Earn {points} {points_label}';
                        $shop_color_text = $settings['shop_color_text'] ?? '#d35400';
                        $shop_color_bg = $settings['shop_color_bg'] ?? '#fef8ee';
                        $shop_color_border = $settings['shop_color_border'] ?? '#f6c064';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #2ecc71;">
                            <div class="mc-toggle-row" style="margin-bottom:20px;">
                                <div class="mc-form-info" style="margin:0;">
                                    <span class="mc-form-label">Show points messages in shop pages (loop)</span>
                                    <span class="mc-form-desc">Shows prompt on Shop, Category, and Tag archive pages.</span>
                                </div>
                                <label class="mc-toggle-switch">
                                    <input type="hidden" name="mc_custom[shop_show]" value="no">
                                    <input type="checkbox" name="mc_custom[shop_show]" value="yes" <?php checked($shop_show, 'yes'); ?>>
                                    <span class="mc-slider"></span>
                                </label>
                            </div>

                            <div class="mc-form-row">
                                <span class="mc-form-label">Loop message text</span>
                                <span class="mc-form-desc" style="color:#2271b1; font-weight:600; margin-bottom:8px;">Placeholders: <code>{points}</code>, <code>{points_label}</code>, <code>{price_discount_fixed_conversion}</code></span>
                                <?php wp_editor($shop_msg, 'mc_shop_msg_editor', ['textarea_name' => 'mc_custom[shop_msg]', 'textarea_rows' => 5, 'media_buttons' => true]); ?>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:30px; margin-top:20px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                                <div><span class="mc-form-label">Text Color</span><input type="color" name="mc_custom[shop_color_text]" value="<?php echo esc_attr($shop_color_text); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Background Color</span><input type="color" name="mc_custom[shop_color_bg]" value="<?php echo esc_attr($shop_color_bg); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Border Color</span><input type="color" name="mc_custom[shop_color_border]" value="<?php echo esc_attr($shop_color_border); ?>" class="mc-color-picker-small"></div>
                            </div>
                        </div>

                    <?php 
                    // ==========================================
                    // PRODUCT PAGE TAB
                    // ==========================================
                    elseif ($current_sub === 'product'): 
                        $prod_show = $settings['prod_show'] ?? 'yes';
                        $prod_update_qty = $settings['prod_update_qty'] ?? 'yes';
                        $prod_pos = $settings['prod_pos'] ?? 'before_cart';
                        $prod_msg = $settings['prod_msg'] ?? 'Buy this product and earn {points} {points_label}!';
                        $prod_color_text = $settings['prod_color_text'] ?? '#2271b1';
                        $prod_color_bg = $settings['prod_color_bg'] ?? '#eaf2fa';

                        $badge_bg = $settings['badge_bg'] ?? '#fef8ee';
                        $badge_border = $settings['badge_border'] ?? '#f6c064';
                        $badge_text = $settings['badge_text_color'] ?? '#d35400';
                        $badge_icon_type = $settings['badge_icon_type'] ?? 'custom'; 
                        $badge_font_icon = $settings['badge_font_icon'] ?? 'dashicons-awards'; 
                        $badge_icon_color = $settings['badge_icon_color'] ?? '#d35400'; 
                        $badge_icon = $settings['badge_icon'] ?? '🎁';
                        $badge_format = $settings['badge_format'] ?? '{icon} Redeem for {points} Pts';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #f39c12; margin-bottom:25px;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Earning Points Prompts</h3>
                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">Show points messages in product page</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[prod_show]" value="no"><input type="checkbox" name="mc_custom[prod_show]" value="yes" <?php checked($prod_show, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                            <div class="mc-toggle-row" style="margin-bottom:20px;">
                                <div class="mc-form-info"><span class="mc-form-label">Update the message when product quantity changes</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[prod_update_qty]" value="no"><input type="checkbox" name="mc_custom[prod_update_qty]" value="yes" <?php checked($prod_update_qty, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>

                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Message Position</span>
                                <select name="mc_custom[prod_pos]" style="width:100%; max-width:400px;">
                                    <option value="before_cart" <?php selected($prod_pos, 'before_cart'); ?>>Before "Add to Cart" Button</option>
                                    <option value="after_cart" <?php selected($prod_pos, 'after_cart'); ?>>After "Add to Cart" Button</option>
                                    <option value="before_excerpt" <?php selected($prod_pos, 'before_excerpt'); ?>>Before excerpt</option>
                                    <option value="after_excerpt" <?php selected($prod_pos, 'after_excerpt'); ?>>After excerpt</option>
                                    <option value="after_meta" <?php selected($prod_pos, 'after_meta'); ?>>After product meta</option>
                                </select>
                            </div>

                            <div class="mc-form-row">
                                <span class="mc-form-label">Single product page message</span>
                                <?php wp_editor($prod_msg, 'mc_prod_msg_editor', ['textarea_name' => 'mc_custom[prod_msg]', 'textarea_rows' => 5, 'media_buttons' => true]); ?>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:30px; margin-top:20px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                                <div><span class="mc-form-label">Text Color</span><input type="color" name="mc_custom[prod_color_text]" value="<?php echo esc_attr($prod_color_text); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Background Color</span><input type="color" name="mc_custom[prod_color_bg]" value="<?php echo esc_attr($prod_color_bg); ?>" class="mc-color-picker-small"></div>
                            </div>
                        </div>

                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #8e44ad;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Redemption Badges</h3>
                            
                            <div class="mc-form-row" style="display:flex; gap:40px; margin-bottom:20px;">
                                <div><span class="mc-form-label">Background Color</span><input type="color" name="mc_custom[badge_bg]" value="<?php echo esc_attr($badge_bg); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Border Color</span><input type="color" name="mc_custom[badge_border]" value="<?php echo esc_attr($badge_border); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Text Color</span><input type="color" name="mc_custom[badge_text_color]" value="<?php echo esc_attr($badge_text); ?>" class="mc-color-picker-small"></div>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                                <div style="flex:1;">
                                    <span class="mc-form-label">Icon Source</span>
                                    <select name="mc_custom[badge_icon_type]" id="mc-badge-icon-type" style="width:100%;">
                                        <option value="custom" <?php selected($badge_icon_type, 'custom'); ?>>Emoji or Image URL</option>
                                        <option value="font_icon" <?php selected($badge_icon_type, 'font_icon'); ?>>Built-in Font Icon</option>
                                    </select>
                                </div>
                                <div style="flex:1;" class="mc-icon-mode-custom">
                                    <span class="mc-form-label">Emoji or Image URL</span>
                                    <input type="text" name="mc_custom[badge_icon]" value="<?php echo esc_attr($badge_icon); ?>" style="width:100%;">
                                </div>
                                <div style="flex:1; display:none;" class="mc-icon-mode-font">
                                    <span class="mc-form-label">Select Font Icon</span>
                                    <select name="mc_custom[badge_font_icon]" style="width:100%;">
                                        <option value="dashicons-awards" <?php selected($badge_font_icon, 'dashicons-awards'); ?>>🏆 Award / Ribbon</option>
                                        <option value="dashicons-star-filled" <?php selected($badge_font_icon, 'dashicons-star-filled'); ?>>⭐ Solid Star</option>
                                        <option value="dashicons-heart" <?php selected($badge_font_icon, 'dashicons-heart'); ?>>❤️ Heart</option>
                                    </select>
                                </div>
                                <div style="flex:0.5; display:none;" class="mc-icon-mode-font">
                                    <span class="mc-form-label">Icon Color</span>
                                    <input type="color" name="mc_custom[badge_icon_color]" value="<?php echo esc_attr($badge_icon_color); ?>" class="mc-color-picker-small">
                                </div>
                            </div>

                            <div class="mc-form-row">
                                <span class="mc-form-label">Badge Text Format</span>
                                <span class="mc-form-desc" style="color:#2271b1;">Variables: <code>{icon}</code>, <code>{points}</code></span>
                                <input type="text" name="mc_custom[badge_format]" value="<?php echo esc_attr($badge_format); ?>" style="width:100%; max-width:600px; padding:8px;">
                            </div>
                        </div>

                    <?php 
                    // ==========================================
                    // MY ACCOUNT TAB
                    // ==========================================
                    elseif ($current_sub === 'account'): 
                        $acc_show = $settings['account_show'] ?? 'yes';
                        $acc_label = $settings['account_label'] ?? 'Points & Rewards';
                        $acc_endpoint = $settings['account_endpoint'] ?? 'mc-rewards';
                        $acc_show_value = $settings['account_show_value'] ?? 'yes';
                        $acc_show_orders = $settings['account_show_orders'] ?? 'yes';
                        $acc_show_email = $settings['account_show_email'] ?? 'yes';
                        
                        $default_tab = $settings['default_tab'] ?? 'catalog';
                        $dash_title_size = $settings['dash_title_size'] ?? '28';
                        $dash_val_size = $settings['dash_val_size'] ?? '36';
                        $dash_card_bg = $settings['dash_card_bg'] ?? '#f9f9f9';
                        $prog_style = $settings['prog_style'] ?? 'linear';
                        $prog_overlay = $settings['prog_overlay'] ?? 'no';
                        $prog_color_active = $settings['prog_color_active'] ?? '#f39c12';
                        $prog_color_ready = $settings['prog_color_ready'] ?? '#2ecc71';
                        $prog_bg_color = $settings['prog_bg_color'] ?? '#f0f0f0';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #16a085; margin-bottom:25px;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">My Account Integration</h3>
                            <div class="mc-toggle-row" style="margin-bottom:20px;">
                                <div class="mc-form-info"><span class="mc-form-label">Show points on My Account menu</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[account_show]" value="no"><input type="checkbox" name="mc_custom[account_show]" value="yes" <?php checked($acc_show, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                                <div style="flex:1;">
                                    <span class="mc-form-label">Label for points section</span>
                                    <input type="text" name="mc_custom[account_label]" value="<?php echo esc_attr($acc_label); ?>" style="width:100%;">
                                </div>
                                <div style="flex:1;">
                                    <span class="mc-form-label">Endpoint for points section</span>
                                    <input type="text" name="mc_custom[account_endpoint]" value="<?php echo esc_attr($acc_endpoint); ?>" style="width:100%;">
                                </div>
                            </div>

                            <div class="mc-toggle-row" style="margin-bottom:15px; border-top:1px solid #eee; padding-top:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">Show points value (Money Worth)</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[account_show_value]" value="no"><input type="checkbox" name="mc_custom[account_show_value]" value="yes" <?php checked($acc_show_value, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">Show points in Order Details</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[account_show_orders]" value="no"><input type="checkbox" name="mc_custom[account_show_orders]" value="yes" <?php checked($acc_show_orders, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                        </div>

                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #2980b9;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Reward Dashboard & Catalog UI</h3>
                            
                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                                <div style="flex:1;">
                                    <span class="mc-form-label">Default Active Tab</span>
                                    <select name="mc_custom[default_tab]" style="width:100%;">
                                        <option value="catalog" <?php selected($default_tab, 'catalog'); ?>>Reward Catalog</option>
                                        <option value="history" <?php selected($default_tab, 'history'); ?>>Points History</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <span class="mc-form-label">Top Metrics Card Background</span>
                                    <input type="color" name="mc_custom[dash_card_bg]" value="<?php echo esc_attr($dash_card_bg); ?>" class="mc-color-picker-small">
                                </div>
                            </div>
                            
                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:30px;">
                                <div style="flex:1;"><span class="mc-form-label">Dashboard Main Title Size (px)</span><input type="number" name="mc_custom[dash_title_size]" value="<?php echo esc_attr($dash_title_size); ?>" style="width:100%;"></div>
                                <div style="flex:1;"><span class="mc-form-label">Metrics Value Text Size (px)</span><input type="number" name="mc_custom[dash_val_size]" value="<?php echo esc_attr($dash_val_size); ?>" style="width:100%;"></div>
                            </div>

                            <h4 style="margin:0 0 15px 0;">Progress Tracker Visuals</h4>
                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                                <div style="flex:1;">
                                    <span class="mc-form-label">Progress Bar Style</span>
                                    <select name="mc_custom[prog_style]" style="width:100%;">
                                        <option value="linear" <?php selected($prog_style, 'linear'); ?>>Sleek Linear Bar</option>
                                        <option value="circular" <?php selected($prog_style, 'circular'); ?>>Modern Circular Ring</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <span class="mc-form-label">Circular Overlay Position</span>
                                    <select name="mc_custom[prog_overlay]" style="width:100%;">
                                        <option value="no" <?php selected($prog_overlay, 'no'); ?>>Standard (Below Title)</option>
                                        <option value="yes" <?php selected($prog_overlay, 'yes'); ?>>Overlay on Product Image</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:40px;">
                                <div><span class="mc-form-label">Progress Background</span><input type="color" name="mc_custom[prog_bg_color]" value="<?php echo esc_attr($prog_bg_color); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Filling Up Color</span><input type="color" name="mc_custom[prog_color_active]" value="<?php echo esc_attr($prog_color_active); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Goal Reached Color</span><input type="color" name="mc_custom[prog_color_ready]" value="<?php echo esc_attr($prog_color_ready); ?>" class="mc-color-picker-small"></div>
                            </div>
                        </div>

                    <?php 
                    // ==========================================
                    // CART & CHECKOUT TAB
                    // ==========================================
                    elseif ($current_sub === 'cart_checkout'): 
                        $cart_show = $settings['cart_show'] ?? 'yes';
                        $cart_msg = $settings['cart_msg'] ?? 'Complete this order to earn {points} {points_label}!';
                        $checkout_show = $settings['checkout_show'] ?? 'yes';
                        $checkout_msg = $settings['checkout_msg'] ?? 'Complete this order to earn {points} {points_label}!';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #e74c3c;">
                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">Show points in Cart page</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[cart_show]" value="no"><input type="checkbox" name="mc_custom[cart_show]" value="yes" <?php checked($cart_show, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                            <div class="mc-form-row" style="margin-bottom:30px;">
                                <span class="mc-form-label">Message text in cart</span>
                                <?php wp_editor($cart_msg, 'mc_cart_msg_editor', ['textarea_name' => 'mc_custom[cart_msg]', 'textarea_rows' => 4, 'media_buttons' => false]); ?>
                            </div>

                            <div class="mc-toggle-row" style="margin-bottom:15px; border-top:1px solid #eee; padding-top:20px;">
                                <div class="mc-form-info"><span class="mc-form-label">Show points in Checkout page</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[checkout_show]" value="no"><input type="checkbox" name="mc_custom[checkout_show]" value="yes" <?php checked($checkout_show, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                            <div class="mc-form-row">
                                <span class="mc-form-label">Message text in checkout</span>
                                <?php wp_editor($checkout_msg, 'mc_check_msg_editor', ['textarea_name' => 'mc_custom[checkout_msg]', 'textarea_rows' => 4, 'media_buttons' => false]); ?>
                            </div>
                        </div>

                    <?php 
                    // ==========================================
                    // CHECKOUT UI & PRODUCT POPUPS (NEW!)
                    // ==========================================
                    elseif ($current_sub === 'checkout_ui'): 
                        // Checkout Box UI
                        $box_style = $settings['box_style'] ?? 'slider';
                        $box_pos = $settings['box_pos'] ?? 'before_checkout_form';
                        $box_title = $settings['box_title'] ?? 'Use your Loyalty Points';
                        $box_bg = $settings['box_bg'] ?? '#fef8ee';
                        $box_border = $settings['box_border'] ?? '#f6c064';
                        $btn_bg = $settings['btn_bg'] ?? '#d35400';
                        $btn_text = $settings['btn_text'] ?? '#ffffff';

                        // Product Popup UI
                        $pop_enable = $settings['pop_enable'] ?? 'yes';
                        $pop_title = $settings['pop_title'] ?? 'Unlock this Reward?';
                        $pop_desc = $settings['pop_desc'] ?? 'Are you sure you want to spend {points} points to get this item for free?';
                        $pop_btn_yes = $settings['pop_btn_yes'] ?? 'Yes, Unlock It!';
                        $pop_btn_no = $settings['pop_btn_no'] ?? 'Not right now';
                        $pop_success = $settings['pop_success'] ?? 'Reward successfully added to your cart!';
                        
                        $pop_bg = $settings['pop_bg'] ?? '#ffffff';
                        $pop_btn_color = $settings['pop_btn_color'] ?? '#2ecc71';
                        $pop_text_color = $settings['pop_text_color'] ?? '#111111';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #3498db; margin-bottom:30px;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">"Pay with Points" Checkout Box Designer</h3>
                            <p style="margin-top:-10px; margin-bottom:20px; color:#666; font-size:13px;">Design the box that appears on the Cart/Checkout pages allowing users to apply a points discount.</p>
                            
                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                                <div style="flex:1;">
                                    <span class="mc-form-label">Redemption UI Style</span>
                                    <select name="mc_custom[box_style]" style="width:100%;">
                                        <option value="slider" <?php selected($box_style, 'slider'); ?>>Modern Range Slider (Recommended)</option>
                                        <option value="input" <?php selected($box_style, 'input'); ?>>Standard Input Box</option>
                                        <option value="toggle" <?php selected($box_style, 'toggle'); ?>>"Use All Points" Toggle Switch</option>
                                        <option value="woo_link" <?php selected($box_style, 'woo_link'); ?>>Native WooCommerce Coupon Link</option>
                                    </select>
                                </div>
                                <div style="flex:1;">
                                    <span class="mc-form-label">Position on Checkout Page</span>
                                    <select name="mc_custom[box_pos]" style="width:100%;">
                                        <option value="before_checkout_form" <?php selected($box_pos, 'before_checkout_form'); ?>>Before Checkout Form</option>
                                        <option value="after_checkout_form" <?php selected($box_pos, 'after_checkout_form'); ?>>After Checkout Form</option>
                                        <option value="review_order_before_submit" <?php selected($box_pos, 'review_order_before_submit'); ?>>Above Payment Button</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mc-form-row">
                                <span class="mc-form-label">Redemption Box Title</span>
                                <input type="text" name="mc_custom[box_title]" value="<?php echo esc_attr($box_title); ?>" style="width:100%;">
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:40px; margin-top:20px; background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                                <div><span class="mc-form-label">Box Background</span><input type="color" name="mc_custom[box_bg]" value="<?php echo esc_attr($box_bg); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Box Border</span><input type="color" name="mc_custom[box_border]" value="<?php echo esc_attr($box_border); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Apply Button Color</span><input type="color" name="mc_custom[btn_bg]" value="<?php echo esc_attr($btn_bg); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Apply Button Text</span><input type="color" name="mc_custom[btn_text]" value="<?php echo esc_attr($btn_text); ?>" class="mc-color-picker-small"></div>
                            </div>
                        </div>

                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #9b59b6;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Product-Level Reward Confirmation Modal</h3>
                            <p style="margin-top:-10px; margin-bottom:20px; color:#666; font-size:13px;">Design the popup that appears when a user clicks the "Redeem" button on a specific catalog item.</p>

                            <div class="mc-toggle-row" style="margin-bottom:20px;">
                                <div class="mc-form-info"><span class="mc-form-label">Enable Product Redemption Modal</span><span class="mc-form-desc">If disabled, clicking the redeem button adds the item to cart immediately without asking for confirmation.</span></div>
                                <label class="mc-toggle-switch">
                                    <input type="hidden" name="mc_custom[pop_enable]" value="no">
                                    <input type="checkbox" name="mc_custom[pop_enable]" value="yes" <?php checked($pop_enable, 'yes'); ?>>
                                    <span class="mc-slider"></span>
                                </label>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                                <div style="flex:1;">
                                    <span class="mc-form-label">Modal Title</span>
                                    <input type="text" name="mc_custom[pop_title]" value="<?php echo esc_attr($pop_title); ?>" style="width:100%; font-weight:bold;">
                                </div>
                                <div style="flex:2;">
                                    <span class="mc-form-label">Confirmation Description</span>
                                    <span class="mc-form-desc" style="color:#2271b1; font-size:12px;">Variables: <code>{points}</code>, <code>{product}</code></span>
                                    <input type="text" name="mc_custom[pop_desc]" value="<?php echo esc_attr($pop_desc); ?>" style="width:100%;">
                                </div>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                                <div style="flex:1;">
                                    <span class="mc-form-label">Confirm Button Text</span>
                                    <input type="text" name="mc_custom[pop_btn_yes]" value="<?php echo esc_attr($pop_btn_yes); ?>" style="width:100%;">
                                </div>
                                <div style="flex:1;">
                                    <span class="mc-form-label">Cancel Button Text</span>
                                    <input type="text" name="mc_custom[pop_btn_no]" value="<?php echo esc_attr($pop_btn_no); ?>" style="width:100%;">
                                </div>
                            </div>

                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Success Message (After Adding to Cart)</span>
                                <input type="text" name="mc_custom[pop_success]" value="<?php echo esc_attr($pop_success); ?>" style="width:100%; border-color:#2ecc71;">
                            </div>

                            <h4 style="margin:30px 0 15px 0;">Modal Styling</h4>
                            <div class="mc-form-row" style="display:flex; gap:40px; background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                                <div><span class="mc-form-label">Modal Background</span><input type="color" name="mc_custom[pop_bg]" value="<?php echo esc_attr($pop_bg); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Text Color</span><input type="color" name="mc_custom[pop_text_color]" value="<?php echo esc_attr($pop_text_color); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Confirm Button Color</span><input type="color" name="mc_custom[pop_btn_color]" value="<?php echo esc_attr($pop_btn_color); ?>" class="mc-color-picker-small"></div>
                            </div>
                        </div>

                    <?php 
                    // ==========================================
                    // AUTO-GIVEAWAYS TAB 
                    // ==========================================
                    elseif ($current_sub === 'giveaways'): 
                        $ga_title = $settings['ga_title'] ?? '🎉 Free Reward Unlocked!';
                        $ga_msg = $settings['ga_msg'] ?? 'Congratulations! You have earned a free {product_name} with your purchase.';
                        $ga_bg = $settings['ga_bg'] ?? '#2ecc71';
                        $ga_text = $settings['ga_text'] ?? '#ffffff';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #1abc9c;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Auto-Giveaway Notifications</h3>
                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Giveaway Notification Title</span>
                                <input type="text" name="mc_custom[ga_title]" value="<?php echo esc_attr($ga_title); ?>" style="width:100%;">
                            </div>
                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Giveaway Message</span>
                                <span class="mc-form-desc" style="color:#2271b1;">Available Variables: <code>{product_name}</code></span>
                                <input type="text" name="mc_custom[ga_msg]" value="<?php echo esc_attr($ga_msg); ?>" style="width:100%;">
                            </div>
                            <div class="mc-form-row" style="display:flex; gap:30px; margin-top:20px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                                <div><span class="mc-form-label">Notification Background Color</span><input type="color" name="mc_custom[ga_bg]" value="<?php echo esc_attr($ga_bg); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Notification Text Color</span><input type="color" name="mc_custom[ga_text]" value="<?php echo esc_attr($ga_text); ?>" class="mc-color-picker-small"></div>
                            </div>
                        </div>

                    <?php 
                    // ==========================================
                    // LABELS TAB
                    // ==========================================
                    elseif ($current_sub === 'labels'): 
                        $defaults = [
                            'lbl_singular' => 'Point', 'lbl_plural' => 'Points',
                            'lbl_order_comp' => 'Order Completed', 'lbl_order_proc' => 'Order Processing',
                            'lbl_order_canc' => 'Order Cancelled', 'lbl_admin_act' => 'Admin Action',
                            'lbl_reviews' => 'Reviews', 'lbl_reg' => 'Registration',
                            'lbl_t_pts' => 'Target achieved - Points collected', 'lbl_t_amt' => 'Target achieved - Total spend amount',
                            'lbl_t_ord' => 'Target achieved - Total Orders', 'lbl_bday' => 'Target achieved - Birthday',
                            'lbl_login' => 'Target achieved - Daily Login', 'lbl_ref_reg' => 'User registration by referral',
                            'lbl_ref_pur' => 'Purchase by referral', 'lbl_lvl' => 'Target achieved - Level',
                            'lbl_expired' => 'Expired Points', 'lbl_refund' => 'Order Refund',
                            'lbl_redeemed' => 'Redeemed Points for order', 'lbl_btn_apply' => 'Apply discount',
                            'lbl_btn_redeem' => 'Redeem points'
                        ];
                        foreach($defaults as $k => $default_val) { $$k = $settings[$k] ?? $default_val; }
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #34495e;">
                            <div style="display:flex; gap:20px; margin-bottom:30px; background:#f9f9f9; padding:20px; border-radius:6px; border:1px solid #eee;">
                                <div style="flex:1;"><span class="mc-form-label">Singular label replacing 'point'</span><input type="text" name="mc_custom[lbl_singular]" value="<?php echo esc_attr($lbl_singular); ?>" style="width:100%;"></div>
                                <div style="flex:1;"><span class="mc-form-label">Plural label replacing 'points'</span><input type="text" name="mc_custom[lbl_plural]" value="<?php echo esc_attr($lbl_plural); ?>" style="width:100%;"></div>
                            </div>
                            <h3 style="font-size:16px; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">History Log & Frontend Labels</h3>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px 30px;">
                                <div><span class="mc-form-label">Order Completed</span><input type="text" name="mc_custom[lbl_order_comp]" value="<?php echo esc_attr($lbl_order_comp); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Order Processing</span><input type="text" name="mc_custom[lbl_order_proc]" value="<?php echo esc_attr($lbl_order_proc); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Admin Action</span><input type="text" name="mc_custom[lbl_admin_act]" value="<?php echo esc_attr($lbl_admin_act); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Registration</span><input type="text" name="mc_custom[lbl_reg]" value="<?php echo esc_attr($lbl_reg); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Target - Total Points</span><input type="text" name="mc_custom[lbl_t_pts]" value="<?php echo esc_attr($lbl_t_pts); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Birthday</span><input type="text" name="mc_custom[lbl_bday]" value="<?php echo esc_attr($lbl_bday); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Level Achieved</span><input type="text" name="mc_custom[lbl_lvl]" value="<?php echo esc_attr($lbl_lvl); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Order Refund</span><input type="text" name="mc_custom[lbl_refund]" value="<?php echo esc_attr($lbl_refund); ?>" style="width:100%;"></div>
                                <div><span class="mc-form-label">Redeemed Points (Order)</span><input type="text" name="mc_custom[lbl_redeemed]" value="<?php echo esc_attr($lbl_redeemed); ?>" style="width:100%;"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                        <button type="submit" name="mc_save_customization" style="background:#2271b1; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer; font-size:14px;">Save Customizations</button>
                    </p>
                </form>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            function toggleIconMode() {
                if ($('#mc-badge-icon-type').val() === 'font_icon') {
                    $('.mc-icon-mode-custom').hide(); $('.mc-icon-mode-font').show();
                } else {
                    $('.mc-icon-mode-font').hide(); $('.mc-icon-mode-custom').show();
                }
            }
            $('#mc-badge-icon-type').on('change', toggleIconMode); toggleIconMode();
        });
        </script>
        <?php
    }
}