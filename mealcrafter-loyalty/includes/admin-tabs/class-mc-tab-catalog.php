<?php
/**
 * MealCrafter: Tab - Reward Catalog Builder
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Catalog {

    public function render() {
        $catalog_settings = get_option('mc_pts_catalog_settings', []);
        if (!is_array($catalog_settings)) $catalog_settings = [];

        // Catalog Settings
        $enabled = $catalog_settings['enabled'] ?? 'yes';
        $title = $catalog_settings['title'] ?? 'Unlock Secret Rewards';
        $description = $catalog_settings['description'] ?? '';
        $terms_url = $catalog_settings['terms_url'] ?? '';
        
        $layout = $catalog_settings['layout'] ?? 'grid';
        $hide_image = $catalog_settings['hide_image'] ?? 'no';
        $img_height = $catalog_settings['img_height'] ?? '180';
        $hover_card = $catalog_settings['hover_card'] ?? 'yes';
        
        $btn_ready = $catalog_settings['btn_ready'] ?? 'Redeem for {points} Pts';
        $btn_short = $catalog_settings['btn_short'] ?? 'Need {points} more Pts';
        $primary_color = $catalog_settings['primary_color'] ?? '#e74c3c';
        $btn_hover_color = $catalog_settings['btn_hover_color'] ?? '#c0392b';
        
        $included_cats = $catalog_settings['included_categories'] ?? [];
        $excluded_prods = $catalog_settings['excluded_products'] ?? []; 

        // Badge Designer Settings
        $badge_bg = $catalog_settings['badge_bg'] ?? '#fef8ee';
        $badge_border = $catalog_settings['badge_border'] ?? '#f6c064';
        $badge_text = $catalog_settings['badge_text_color'] ?? '#d35400';
        $badge_icon_type = $catalog_settings['badge_icon_type'] ?? 'custom'; // NEW: Icon type
        $badge_font_icon = $catalog_settings['badge_font_icon'] ?? 'dashicons-awards'; // NEW: Font icon
        $badge_icon_color = $catalog_settings['badge_icon_color'] ?? '#d35400'; // NEW: Font icon color
        $badge_icon = $catalog_settings['badge_icon'] ?? '🎁';
        $badge_format = $catalog_settings['badge_format'] ?? '{icon} Redeem for {points} Pts';

        // Progress Tracker Settings
        $prog_style = $catalog_settings['prog_style'] ?? 'linear';
        $prog_overlay = $catalog_settings['prog_overlay'] ?? 'no';
        $prog_color_active = $catalog_settings['prog_color_active'] ?? '#f39c12';
        $prog_color_ready = $catalog_settings['prog_color_ready'] ?? '#2ecc71';
        $prog_bg_color = $catalog_settings['prog_bg_color'] ?? '#f0f0f0';
        ?>

        <style>
            .mc-rule-card { overflow: visible !important; }
            .mc-color-picker-small { height: 35px; width: 60px !important; padding: 0; cursor: pointer; border: 1px solid #ccc; border-radius: 4px; margin-top: 5px; }
        </style>

        <div class="mc-main-content" style="margin-top:20px;">
            
            <div style="background:#eaf2fa; border:1px solid #b6d4ea; border-left:4px solid #2271b1; padding:20px; border-radius:4px; margin-bottom:25px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h3 style="margin:0 0 5px 0; font-size:16px; color:#1d2327;">Your Reward Catalog Shortcode</h3>
                    <p style="margin:0; font-size:14px; color:#50575e;">Copy and paste this shortcode onto any page or inside your app to display the dynamic rewards menu.</p>
                </div>
                <div style="background:#fff; border:1px solid #ccc; padding:10px 20px; border-radius:6px; font-family:monospace; font-size:16px; font-weight:bold; color:#d35400; box-shadow:inset 0 1px 3px rgba(0,0,0,0.05); user-select:all;">
                    [mc_reward_catalog]
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'mc_prod_catalog_group' ); ?>

                <div class="mc-rule-card" style="padding:25px; margin-bottom:20px;">
                    <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Catalog Header & Content</h3>

                    <div class="mc-toggle-row" style="margin-bottom:20px;">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label">Enable Catalog Shortcode</span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_pts_catalog_settings[enabled]" value="no">
                            <input type="checkbox" name="mc_pts_catalog_settings[enabled]" value="yes" <?php checked($enabled, 'yes'); ?>>
                            <span class="mc-slider"></span>
                        </label>
                    </div>

                    <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                        <div style="flex:1;">
                            <span class="mc-form-label">Catalog Header Title</span>
                            <input type="text" name="mc_pts_catalog_settings[title]" value="<?php echo esc_attr($title); ?>" style="width:100%;">
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">Terms & Conditions URL</span>
                            <span class="mc-form-desc">Paste the link to your loyalty rules. Displays under the title.</span>
                            <input type="url" name="mc_pts_catalog_settings[terms_url]" value="<?php echo esc_attr($terms_url); ?>" style="width:100%;" placeholder="https://yoursite.com/terms">
                        </div>
                    </div>

                    <div class="mc-form-row" style="margin-bottom:20px;">
                        <span class="mc-form-label">Catalog Description / Rules</span>
                        <span class="mc-form-desc">Add a short sentence explaining how points work.</span>
                        <textarea name="mc_pts_catalog_settings[description]" style="width:100%; height:60px; padding:8px;"><?php echo esc_textarea($description); ?></textarea>
                    </div>
                </div>

                <div class="mc-rule-card" style="padding:25px; margin-bottom:20px;">
                    <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Layout & Content Rules</h3>

                    <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                        <div style="flex:1;">
                            <span class="mc-form-label">Grid Layout</span>
                            <select name="mc_pts_catalog_settings[layout]" style="width:100%;">
                                <option value="grid" <?php selected($layout, 'grid'); ?>>Card Grid (Photos)</option>
                                <option value="list" <?php selected($layout, 'list'); ?>>List View (Compact)</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">Card Hover Animation</span>
                            <select name="mc_pts_catalog_settings[hover_card]" style="width:100%;">
                                <option value="yes" <?php selected($hover_card, 'yes'); ?>>Enable (Lift & Shadow)</option>
                                <option value="no" <?php selected($hover_card, 'no'); ?>>Disable (Static Cards)</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">Hide Product Images</span>
                            <select name="mc_pts_catalog_settings[hide_image]" style="width:100%;">
                                <option value="no" <?php selected($hide_image, 'no'); ?>>Show Images</option>
                                <option value="yes" <?php selected($hide_image, 'yes'); ?>>Hide Images (Text Only)</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">Max Image Height (px)</span>
                            <span class="mc-form-desc">Cards shrink to fit images.</span>
                            <input type="number" name="mc_pts_catalog_settings[img_height]" value="<?php echo esc_attr($img_height); ?>" style="width:100%;">
                        </div>
                    </div>

                    <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                        <div style="flex:1;">
                            <span class="mc-form-label">Limit to Specific Categories (Optional)</span>
                            <span class="mc-form-desc">Leave empty to auto-display ALL items that have a point cost.</span>
                            <?php 
                            if (!is_array($included_cats)) { $included_cats = []; }
                            echo '<select name="mc_pts_catalog_settings[included_categories][]" class="mc-select2 mc-ajax-category-search" multiple="multiple" style="width:100%; max-width:600px;" data-placeholder="Search categories...">';
                            foreach($included_cats as $cat_id) {
                                $term = get_term_by('id', $cat_id, 'product_cat');
                                if($term) echo '<option value="'.esc_attr($cat_id).'" selected>'.esc_html($term->name).'</option>';
                            }
                            echo '</select>';
                            ?>
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">Exclude Specific Products (Optional)</span>
                            <span class="mc-form-desc">Hide individual items from the catalog, even if their category is allowed.</span>
                            <?php 
                            if (!is_array($excluded_prods)) { $excluded_prods = []; }
                            echo '<select name="mc_pts_catalog_settings[excluded_products][]" class="mc-select2 mc-ajax-product-search" multiple="multiple" style="width:100%; max-width:600px;" data-placeholder="Search products to hide...">';
                            foreach($excluded_prods as $prod_id) {
                                $prod = wc_get_product($prod_id);
                                if($prod) echo '<option value="'.esc_attr($prod_id).'" selected>'.wp_kses_post($prod->get_formatted_name()).'</option>';
                            }
                            echo '</select>';
                            ?>
                        </div>
                    </div>

                    <div class="mc-form-row" style="display:flex; gap:40px; margin-bottom:20px;">
                        <div>
                            <span class="mc-form-label">Button Main Color</span>
                            <span class="mc-form-desc">Standard 'Redeem' color.</span>
                            <input type="color" name="mc_pts_catalog_settings[primary_color]" value="<?php echo esc_attr($primary_color); ?>" class="mc-color-picker-small">
                        </div>
                        <div>
                            <span class="mc-form-label">Button Hover Color</span>
                            <span class="mc-form-desc">When hovered over.</span>
                            <input type="color" name="mc_pts_catalog_settings[btn_hover_color]" value="<?php echo esc_attr($btn_hover_color); ?>" class="mc-color-picker-small">
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">"Can Afford" Button Text</span>
                            <span class="mc-form-desc">Use <code>{points}</code> variable.</span>
                            <input type="text" name="mc_pts_catalog_settings[btn_ready]" value="<?php echo esc_attr($btn_ready); ?>" style="width:100%; margin-top:5px;">
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">"Cannot Afford" Button Text</span>
                            <span class="mc-form-desc">Use <code>{points}</code> variable.</span>
                            <input type="text" name="mc_pts_catalog_settings[btn_short]" value="<?php echo esc_attr($btn_short); ?>" style="width:100%; margin-top:5px;">
                        </div>
                    </div>
                </div>

                <div class="mc-rule-card" style="padding:25px; margin-bottom:20px; border-left:4px solid #2ecc71;">
                    <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Progress Tracker Visuals</h3>

                    <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                        <div style="flex:1;">
                            <span class="mc-form-label">Progress Bar Style</span>
                            <select name="mc_pts_catalog_settings[prog_style]" style="width:100%;">
                                <option value="linear" <?php selected($prog_style, 'linear'); ?>>Sleek Linear Bar (with markers)</option>
                                <option value="circular" <?php selected($prog_style, 'circular'); ?>>Modern Circular Ring (with lock icon)</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <span class="mc-form-label">Circular Overlay Position</span>
                            <span class="mc-form-desc">Only works if 'Circular' is selected above.</span>
                            <select name="mc_pts_catalog_settings[prog_overlay]" style="width:100%;">
                                <option value="no" <?php selected($prog_overlay, 'no'); ?>>Standard (Below Title)</option>
                                <option value="yes" <?php selected($prog_overlay, 'yes'); ?>>Overlay on Product Image</option>
                            </select>
                        </div>
                    </div>

                    <div class="mc-form-row" style="display:flex; gap:40px;">
                        <div>
                            <span class="mc-form-label">Progress Background</span>
                            <span class="mc-form-desc">The empty part.</span>
                            <input type="color" name="mc_pts_catalog_settings[prog_bg_color]" value="<?php echo esc_attr($prog_bg_color); ?>" class="mc-color-picker-small">
                        </div>
                        <div>
                            <span class="mc-form-label">Filling Up Color</span>
                            <span class="mc-form-desc">Not enough points yet.</span>
                            <input type="color" name="mc_pts_catalog_settings[prog_color_active]" value="<?php echo esc_attr($prog_color_active); ?>" class="mc-color-picker-small">
                        </div>
                        <div>
                            <span class="mc-form-label">Goal Reached Color</span>
                            <span class="mc-form-desc">Ready to afford item.</span>
                            <input type="color" name="mc_pts_catalog_settings[prog_color_ready]" value="<?php echo esc_attr($prog_color_ready); ?>" class="mc-color-picker-small">
                        </div>
                    </div>
                </div>

                <div class="mc-rule-card" style="padding:25px; margin-bottom:20px; border-left:4px solid #f39c12;">
                    <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Product Page Badge Designer</h3>
                    
                    <div class="mc-form-row" style="display:flex; gap:40px; margin-bottom:20px;">
                        <div>
                            <span class="mc-form-label">Background Color</span>
                            <input type="color" name="mc_pts_catalog_settings[badge_bg]" value="<?php echo esc_attr($badge_bg); ?>" class="mc-color-picker-small">
                        </div>
                        <div>
                            <span class="mc-form-label">Border Color</span>
                            <input type="color" name="mc_pts_catalog_settings[badge_border]" value="<?php echo esc_attr($badge_border); ?>" class="mc-color-picker-small">
                        </div>
                        <div>
                            <span class="mc-form-label">Text Color</span>
                            <input type="color" name="mc_pts_catalog_settings[badge_text_color]" value="<?php echo esc_attr($badge_text); ?>" class="mc-color-picker-small">
                        </div>
                    </div>

                    <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                        <div style="flex:1;">
                            <span class="mc-form-label">Icon Source</span>
                            <select name="mc_pts_catalog_settings[badge_icon_type]" id="mc-badge-icon-type" style="width:100%;">
                                <option value="custom" <?php selected($badge_icon_type, 'custom'); ?>>Emoji or Image URL</option>
                                <option value="font_icon" <?php selected($badge_icon_type, 'font_icon'); ?>>Built-in Font Icon</option>
                            </select>
                        </div>

                        <div style="flex:1;" class="mc-icon-mode-custom">
                            <span class="mc-form-label">Emoji or Image URL</span>
                            <input type="text" name="mc_pts_catalog_settings[badge_icon]" value="<?php echo esc_attr($badge_icon); ?>" style="width:100%;">
                        </div>

                        <div style="flex:1; display:none;" class="mc-icon-mode-font">
                            <span class="mc-form-label">Select Font Icon</span>
                            <select name="mc_pts_catalog_settings[badge_font_icon]" style="width:100%;">
                                <option value="dashicons-awards" <?php selected($badge_font_icon, 'dashicons-awards'); ?>>🏆 Award / Ribbon</option>
                                <option value="dashicons-star-filled" <?php selected($badge_font_icon, 'dashicons-star-filled'); ?>>⭐ Solid Star</option>
                                <option value="dashicons-heart" <?php selected($badge_font_icon, 'dashicons-heart'); ?>>❤️ Heart</option>
                                <option value="dashicons-yes-alt" <?php selected($badge_font_icon, 'dashicons-yes-alt'); ?>>✅ Checkmark</option>
                                <option value="dashicons-tag" <?php selected($badge_font_icon, 'dashicons-tag'); ?>>🏷️ Tag</option>
                            </select>
                        </div>
                        <div style="flex:0.5; display:none;" class="mc-icon-mode-font">
                            <span class="mc-form-label">Icon Color</span>
                            <input type="color" name="mc_pts_catalog_settings[badge_icon_color]" value="<?php echo esc_attr($badge_icon_color); ?>" class="mc-color-picker-small">
                        </div>
                    </div>

                    <div class="mc-form-row">
                        <span class="mc-form-label">Badge Text Format</span>
                        <span class="mc-form-desc" style="color:#2271b1; font-weight:600; margin-bottom:8px;">
                            Available Variables: <code>{icon}</code> and <code>{points}</code>
                        </span>
                        <input type="text" name="mc_pts_catalog_settings[badge_format]" value="<?php echo esc_attr($badge_format); ?>" style="width:100%; max-width:600px; padding:8px;">
                    </div>
                </div>

                <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                    <?php submit_button('Save Catalog Settings', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
                </p>
            </form>

            <script>
            jQuery(document).ready(function($) {
                // Icon Type Switcher Logic
                function toggleIconMode() {
                    if ($('#mc-badge-icon-type').val() === 'font_icon') {
                        $('.mc-icon-mode-custom').hide();
                        $('.mc-icon-mode-font').show();
                    } else {
                        $('.mc-icon-mode-font').hide();
                        $('.mc-icon-mode-custom').show();
                    }
                }
                $('#mc-badge-icon-type').on('change', toggleIconMode);
                toggleIconMode(); // Run on load

                // Select2 Initialization
                try {
                    if($.fn.select2) {
                        let wc_nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_products_nonce : '';
                        let cat_nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_categories_nonce : '';
                        
                        $('.mc-ajax-category-search:not(.select2-hidden-accessible)').select2({
                            allowClear: true, minimumInputLength: 2,
                            ajax: { url: ajaxurl, dataType: 'json', delay: 250, data: function(p) { return { term: p.term, action: 'woocommerce_json_search_categories', security: cat_nonce }; }, processResults: function(d) { var t = []; if (d) { $.each(d, function(id, text) { t.push({ id: id, text: text }); }); } return { results: t }; }, cache: true }
                        });

                        $('.mc-ajax-product-search:not(.select2-hidden-accessible)').select2({
                            allowClear: true, minimumInputLength: 3,
                            ajax: { url: ajaxurl, dataType: 'json', delay: 250, data: function(p) { return { term: p.term, action: 'woocommerce_json_search_products_and_variations', security: wc_nonce }; }, processResults: function(d) { var t = []; if (d) { $.each(d, function(id, text) { t.push({ id: id, text: text }); }); } return { results: t }; }, cache: true }
                        });
                    }
                } catch(err) {}
            });
            </script>
        </div>
        <?php
    }
}