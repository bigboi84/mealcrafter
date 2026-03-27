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
        
        $subtabs = [
            'general'       => 'General',
            'shop'          => 'Points in Shop Pages',
            'product'       => 'Points in Product Page',
            'account'       => 'Points in My Account',
            'cart_checkout' => 'Points in Cart & Checkout',
            'checkout_ui'   => 'Checkout UI & Popups', 
            'giveaways'     => 'Auto-Giveaways UI',
            'reward_design' => 'Reward Cart Design', 
            'earning_auto'  => 'Auto Earning Displays', 
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
                    // AUTO EARNING DISPLAYS (NEW TAB!)
                    // ==========================================
                    elseif ($current_sub === 'earning_auto'): 
                        $earn_show_single = $settings['earn_show_single'] ?? 'yes';
                        $earn_show_combo = $settings['earn_show_combo'] ?? 'yes';
                        $earn_show_grouped = $settings['earn_show_grouped'] ?? 'yes';
                        $earn_show_cart = $settings['earn_show_cart'] ?? 'yes';
                        $earn_show_checkout = $settings['earn_show_checkout'] ?? 'yes';
                        
                        $earn_msg_product = $settings['earn_msg_product'] ?? 'Earn {points} Points!';
                        $earn_msg_cart = $settings['earn_msg_cart'] ?? 'Complete this order to earn {points} Points!';
                        $earn_msg_checkout = $settings['earn_msg_checkout'] ?? 'Earn {points} Points!';
                        
                        $earn_color = $settings['earn_color'] ?? '#2ecc71';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #f1c40f; margin-bottom:30px;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Auto Earning Displays</h3>
                            <p style="margin-top:-10px; margin-bottom:20px; color:#666; font-size:13px;">Automatically inject earning messages into standard hooks without needing shortcodes.</p>

                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Earning Text Color</span>
                                <span class="mc-form-desc">Color for the text displaying earned points.</span>
                                <input type="color" name="mc_custom[earn_color]" value="<?php echo esc_attr($earn_color); ?>" class="mc-color-picker-small" style="margin-top: 8px;">
                            </div>

                            <h4 style="margin: 30px 0 15px 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Display Locations</h4>

                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">Single Product Page</span><span class="mc-form-desc">Show under the Add to Cart button on standard products.</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[earn_show_single]" value="no"><input type="checkbox" name="mc_custom[earn_show_single]" value="yes" <?php checked($earn_show_single, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                            
                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">MealCrafter Combo</span><span class="mc-form-desc">Show next to the Add to Cart button on Combo products.</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[earn_show_combo]" value="no"><input type="checkbox" name="mc_custom[earn_show_combo]" value="yes" <?php checked($earn_show_combo, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>

                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">MealCrafter Grouped</span><span class="mc-form-desc">Show when a grouped product modal/page appears.</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[earn_show_grouped]" value="no"><input type="checkbox" name="mc_custom[earn_show_grouped]" value="yes" <?php checked($earn_show_grouped, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>

                            <div class="mc-form-row" style="margin-bottom:30px;">
                                <span class="mc-form-label">Product Page Text</span>
                                <input type="text" name="mc_custom[earn_msg_product]" value="<?php echo esc_attr($earn_msg_product); ?>" style="width:100%; max-width: 500px;">
                            </div>

                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">Cart Page</span><span class="mc-form-desc">Show below the cart totals.</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[earn_show_cart]" value="no"><input type="checkbox" name="mc_custom[earn_show_cart]" value="yes" <?php checked($earn_show_cart, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                            <div class="mc-form-row" style="margin-bottom:30px;">
                                <span class="mc-form-label">Cart Page Text</span>
                                <input type="text" name="mc_custom[earn_msg_cart]" value="<?php echo esc_attr($earn_msg_cart); ?>" style="width:100%; max-width: 500px;">
                            </div>

                            <div class="mc-toggle-row" style="margin-bottom:15px;">
                                <div class="mc-form-info"><span class="mc-form-label">Checkout Page</span><span class="mc-form-desc">Show right after the total to pay.</span></div>
                                <label class="mc-toggle-switch"><input type="hidden" name="mc_custom[earn_show_checkout]" value="no"><input type="checkbox" name="mc_custom[earn_show_checkout]" value="yes" <?php checked($earn_show_checkout, 'yes'); ?>><span class="mc-slider"></span></label>
                            </div>
                            <div class="mc-form-row" style="margin-bottom:30px;">
                                <span class="mc-form-label">Checkout Page Text</span>
                                <input type="text" name="mc_custom[earn_msg_checkout]" value="<?php echo esc_attr($earn_msg_checkout); ?>" style="width:100%; max-width: 500px;">
                            </div>

                        </div>

                    <?php 
                    // ==========================================
                    // REWARD CART DESIGN TAB
                    // ==========================================
                    elseif ($current_sub === 'reward_design'): 
                        $congrats = $settings['cart_ui_congrats'] ?? '🎉 Congratulations!';
                        $free = $settings['cart_ui_free'] ?? 'FREE';
                        $note = $settings['cart_ui_note'] ?? '* Note: Customer pays for premium upgrades.';
                        $pts = $settings['cart_ui_pts_label'] ?? 'pts';
                        $remove = $settings['cart_ui_remove'] ?? 'Remove Reward';

                        $border_style = $settings['cart_ui_border_style'] ?? 'dashed';
                        $border_weight = $settings['cart_ui_border_weight'] ?? '2';
                        $border_color = $settings['cart_ui_border_color'] ?? '#f6c064';
                        $congrats_color = $settings['cart_ui_congrats_color'] ?? '#e67e22';
                        $free_color = $settings['cart_ui_free_color'] ?? '#2ecc71';
                        $note_color = $settings['cart_ui_note_color'] ?? '#d35400';
                        $bg_color = $settings['cart_ui_bg_color'] ?? '#fdfbf7';
                    ?>
                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #e67e22; margin-bottom:30px;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Reward Cart Row Texts</h3>
                            <p style="margin-top:-10px; margin-bottom:20px; color:#666; font-size:13px;">Edit the labels and warnings displayed inside the Cart table box when a user redeems a product.</p>
                            
                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Congratulations Message</span>
                                <input type="text" name="mc_custom[cart_ui_congrats]" value="<?php echo esc_attr($congrats); ?>" style="width:100%; max-width: 400px;">
                            </div>
                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Free Badge Text</span>
                                <input type="text" name="mc_custom[cart_ui_free]" value="<?php echo esc_attr($free); ?>" style="width:100%; max-width: 400px;">
                            </div>
                            <div class="mc-form-row" style="margin-bottom:20px;">
                                <span class="mc-form-label">Premium Upgrade Warning Note</span>
                                <input type="text" name="mc_custom[cart_ui_note]" value="<?php echo esc_attr($note); ?>" style="width:100%; max-width: 600px;">
                            </div>
                            <div class="mc-form-row" style="margin-bottom:20px; display:flex; gap:20px;">
                                <div style="flex:1; max-width: 200px;">
                                    <span class="mc-form-label">Points Abbreviation</span>
                                    <input type="text" name="mc_custom[cart_ui_pts_label]" value="<?php echo esc_attr($pts); ?>" style="width:100%;" placeholder="e.g. pts">
                                </div>
                                <div style="flex:1; max-width: 200px;">
                                    <span class="mc-form-label">Remove Button Text</span>
                                    <input type="text" name="mc_custom[cart_ui_remove]" value="<?php echo esc_attr($remove); ?>" style="width:100%;">
                                </div>
                            </div>
                        </div>

                        <div class="mc-rule-card" style="padding:25px; border-left:4px solid #f39c12; margin-bottom:30px;">
                            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Reward Cart Colors & Styling</h3>
                            
                            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px; flex-wrap: wrap;">
                                <div style="min-width: 150px;">
                                    <span class="mc-form-label">Border Style</span>
                                    <select name="mc_custom[cart_ui_border_style]" style="width:100%;">
                                        <option value="dashed" <?php selected($border_style, 'dashed'); ?>>Dashed</option>
                                        <option value="solid" <?php selected($border_style, 'solid'); ?>>Solid</option>
                                        <option value="dotted" <?php selected($border_style, 'dotted'); ?>>Dotted</option>
                                    </select>
                                </div>
                                <div style="min-width: 150px;">
                                    <span class="mc-form-label">Border Weight (px)</span>
                                    <input type="number" name="mc_custom[cart_ui_border_weight]" value="<?php echo esc_attr($border_weight); ?>" style="width:100%;">
                                </div>
                                <div style="min-width: 150px;">
                                    <span class="mc-form-label">Border Color</span>
                                    <input type="color" name="mc_custom[cart_ui_border_color]" value="<?php echo esc_attr($border_color); ?>" class="mc-color-picker-small">
                                </div>
                                <div style="min-width: 150px;">
                                    <span class="mc-form-label">Row Background Color</span>
                                    <input type="color" name="mc_custom[cart_ui_bg_color]" value="<?php echo esc_attr($bg_color); ?>" class="mc-color-picker-small">
                                </div>
                            </div>

                            <div class="mc-form-row" style="display:flex; gap:30px; background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee; flex-wrap: wrap;">
                                <div><span class="mc-form-label">Congrats Text Color</span><input type="color" name="mc_custom[cart_ui_congrats_color]" value="<?php echo esc_attr($congrats_color); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">FREE Text Color</span><input type="color" name="mc_custom[cart_ui_free_color]" value="<?php echo esc_attr($free_color); ?>" class="mc-color-picker-small"></div>
                                <div><span class="mc-form-label">Warning Note Color</span><input type="color" name="mc_custom[cart_ui_note_color]" value="<?php echo esc_attr($note_color); ?>" class="mc-color-picker-small"></div>
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