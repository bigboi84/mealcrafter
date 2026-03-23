<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php 
// Fetch saved settings
$catalog_settings = get_option('mc_pts_catalog_settings', []);
if (!is_array($catalog_settings)) $catalog_settings = [];

// Catalog Settings
$enabled = $catalog_settings['enabled'] ?? 'yes';
$title = $catalog_settings['title'] ?? 'Unlock Secret Rewards';
$layout = $catalog_settings['layout'] ?? 'grid';
$btn_ready = $catalog_settings['btn_ready'] ?? 'Redeem for {points} Pts';
$btn_short = $catalog_settings['btn_short'] ?? 'Need {points} more Pts';
$primary_color = $catalog_settings['primary_color'] ?? '#e74c3c';
$included_cats = $catalog_settings['included_categories'] ?? [];

// BRAND NEW: Badge Designer Settings
$badge_bg = $catalog_settings['badge_bg'] ?? '#fef8ee';
$badge_border = $catalog_settings['badge_border'] ?? '#f6c064';
$badge_text = $catalog_settings['badge_text_color'] ?? '#d35400';
$badge_icon = $catalog_settings['badge_icon'] ?? '🎁';
$badge_format = $catalog_settings['badge_format'] ?? '{icon} Redeem for {points} Pts';
?>

<div style="background:#fff; border:1px solid #c3c4c7; border-left:4px solid #2271b1; padding:15px 20px; border-radius:4px; margin-bottom:25px; box-shadow:0 1px 1px rgba(0,0,0,0.04);">
    <h3 style="margin-top:0; font-size:16px;">The Reward Catalog & Badges</h3>
    <p style="margin:0; font-size:14px; color:#50575e;">
        Paste <code>[mc_reward_catalog]</code> onto any page to display the visual rewards menu. Customize your frontend elements below.
    </p>
</div>

<form method="post" action="options.php">
    <?php settings_fields( 'mc_prod_catalog_group' ); ?>

    <div class="mc-rule-card" style="padding:25px; margin-bottom:20px; border-left:4px solid #f39c12;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Product Page Badge Designer</h3>
        <p style="margin-top:-10px; margin-bottom:20px; color:#666; font-size:13px;">Design the small badge that appears on individual products and Combo builders indicating the item can be bought with points.</p>

        <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
            <div style="flex:1;">
                <span class="mc-form-label">Background Color</span>
                <input type="color" name="mc_pts_catalog_settings[badge_bg]" value="<?php echo esc_attr($badge_bg); ?>" style="height:35px; width:100px; padding:0; cursor:pointer;">
            </div>
            <div style="flex:1;">
                <span class="mc-form-label">Border Color</span>
                <input type="color" name="mc_pts_catalog_settings[badge_border]" value="<?php echo esc_attr($badge_border); ?>" style="height:35px; width:100px; padding:0; cursor:pointer;">
            </div>
            <div style="flex:1;">
                <span class="mc-form-label">Text Color</span>
                <input type="color" name="mc_pts_catalog_settings[badge_text_color]" value="<?php echo esc_attr($badge_text); ?>" style="height:35px; width:100px; padding:0; cursor:pointer;">
            </div>
        </div>

        <div class="mc-form-row" style="display:flex; gap:20px;">
            <div style="flex:1;">
                <span class="mc-form-label">Icon (Emoji or Image URL)</span>
                <span class="mc-form-desc">Paste an emoji (like 🍔) or a direct image URL (https://...).</span>
                <input type="text" name="mc_pts_catalog_settings[badge_icon]" value="<?php echo esc_attr($badge_icon); ?>" style="width:100%;">
            </div>
            <div style="flex:2;">
                <span class="mc-form-label">Badge Text Format</span>
                <span class="mc-form-desc">Use <code>{icon}</code> and <code>{points}</code></span>
                <input type="text" name="mc_pts_catalog_settings[badge_format]" value="<?php echo esc_attr($badge_format); ?>" style="width:100%;">
            </div>
        </div>
    </div>

    <div class="mc-rule-card" style="padding:25px; margin-bottom:20px;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Catalog Display Settings</h3>

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
            <div style="flex:2;">
                <span class="mc-form-label">Catalog Header Title</span>
                <input type="text" name="mc_pts_catalog_settings[title]" value="<?php echo esc_attr($title); ?>" style="width:100%;">
            </div>
            <div style="flex:1;">
                <span class="mc-form-label">Grid Layout</span>
                <select name="mc_pts_catalog_settings[layout]" style="width:100%;">
                    <option value="grid" <?php selected($layout, 'grid'); ?>>Card Grid (Photos)</option>
                    <option value="list" <?php selected($layout, 'list'); ?>>List View (Compact)</option>
                </select>
            </div>
        </div>

        <div class="mc-form-row" style="margin-bottom:20px;">
            <span class="mc-form-label">Limit to Specific Categories (Optional)</span>
            <span class="mc-form-desc">Leave empty to auto-display ALL items that have a point cost.</span>
            <?php 
            if (!is_array($included_cats)) { $included_cats = []; }
            echo '<select name="mc_pts_catalog_settings[included_categories][]" class="mc-select2 mc-ajax-category-search" multiple="multiple" style="width:100%; max-width:600px;" data-placeholder="Search for categories...">';
            foreach($included_cats as $cat_id) {
                $term = get_term_by('id', $cat_id, 'product_cat');
                if($term) echo '<option value="'.esc_attr($cat_id).'" selected>'.esc_html($term->name).'</option>';
            }
            echo '</select>';
            ?>
        </div>

        <div class="mc-form-row" style="margin-bottom:20px;">
            <span class="mc-form-label">Primary Brand Color</span>
            <input type="color" name="mc_pts_catalog_settings[primary_color]" value="<?php echo esc_attr($primary_color); ?>" style="height:35px; width:100px; padding:0; cursor:pointer;">
        </div>

        <div class="mc-form-row" style="display:flex; gap:20px;">
            <div style="flex:1;">
                <span class="mc-form-label">"Can Afford" Button Text</span>
                <input type="text" name="mc_pts_catalog_settings[btn_ready]" value="<?php echo esc_attr($btn_ready); ?>" style="width:100%;">
            </div>
            <div style="flex:1;">
                <span class="mc-form-label">"Cannot Afford" Button Text</span>
                <input type="text" name="mc_pts_catalog_settings[btn_short]" value="<?php echo esc_attr($btn_short); ?>" style="width:100%;">
            </div>
        </div>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Catalog Settings', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    try {
        if($.fn.select2) {
            let cat_nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_categories_nonce : '';
            $('.mc-ajax-category-search:not(.select2-hidden-accessible)').select2({
                allowClear: true, minimumInputLength: 2,
                ajax: { url: ajaxurl, dataType: 'json', delay: 250, data: function(p) { return { term: p.term, action: 'woocommerce_json_search_categories', security: cat_nonce }; }, processResults: function(d) { var t = []; if (d) { $.each(d, function(id, text) { t.push({ id: id, text: text }); }); } return { results: t }; }, cache: true }
            });
        }
    } catch(err) {}
});
</script>