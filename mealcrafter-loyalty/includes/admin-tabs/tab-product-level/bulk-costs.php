<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div style="margin-bottom:20px; display:flex; justify-content:flex-end;">
    <button type="button" id="mc-add-new-bulk-cost" style="background:#2271b1; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">+ Add Bulk Cost Rule</button>
</div>

<form method="post" action="options.php" id="mc-loyalty-bulk-costs-form">
    <?php settings_fields( 'mc_prod_bulk_group' ); ?>
    
    <div style="display:none;">
        <input type="hidden" name="mc_pts_bulk_costs[__empty__][id]" value="__empty__">
    </div>
    
    <div id="mc-bulk-costs-container">
        <p class="description" style="margin-bottom:25px; font-size:14px;">Mass-assign point redemption costs to entire categories, tags, or specific products without editing them individually.</p>
        
        <?php 
        $all_rules = get_option('mc_pts_bulk_costs', []); 
        if (!is_array($all_rules)) $all_rules = [];

        $rules = array_filter($all_rules, function($r) {
            return !empty($r['id']) && $r['id'] !== '__empty__' && $r['id'] !== '{id}';
        });

        // Sort rules by Priority (lowest number first)
        usort($rules, function($a, $b) { return ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10); });

        if(empty($rules)) {
            echo '<div class="mc-rule-card" id="mc-no-bulk-costs-msg" style="padding:40px; text-align:center; background:#f9f9f9;"><p style="margin:0; color:#777; font-size:15px;">No bulk cost rules created yet. Click "Add Bulk Cost Rule" above to get started.</p></div>';
        } else {
            foreach($rules as $index => $rule) {
                $id = esc_attr($rule['id']);
                ?>
                <div class="mc-rule-card mc-existing-rule" style="padding:0; overflow:hidden;">
                    <input type="hidden" class="mc-rule-id" name="mc_pts_bulk_costs[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                    
                    <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
                        <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                            <label class="mc-toggle-switch" title="Toggle Active/Inactive">
                                <input type="hidden" name="mc_pts_bulk_costs[<?php echo $id; ?>][active]" value="no">
                                <input type="checkbox" name="mc_pts_bulk_costs[<?php echo $id; ?>][active]" value="yes" <?php checked($rule['active'] ?? 'yes', 'yes'); ?>>
                                <span class="mc-slider"></span>
                            </label>
                            <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display"><?php echo esc_html($rule['name'] ?: 'Unnamed Bulk Rule'); ?></h3>
                            <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-rule-type-badge">
                                <?php echo esc_html(str_replace('_', ' ', $rule['target_type'] ?? 'categories')); ?>
                            </span>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <button type="button" class="mc-remove-bulk-cost" style="background:transparent; border:none; color:#d63638; text-decoration:none; font-weight:600; font-size:13px; cursor:pointer;">Delete Rule</button>
                            <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▼</span>
                        </div>
                    </div>

                    <div class="mc-rule-card-body" style="display:none; padding:20px;">
                        
                        <div class="mc-form-row mc-inline-inputs" style="background:transparent; border:none; padding:0; margin-bottom:20px;">
                            <span style="font-weight:600; color:#1d2327;">Rule Name:</span>
                            <input type="text" class="mc-rule-name-input" name="mc_pts_bulk_costs[<?php echo $id; ?>][name]" value="<?php echo esc_attr($rule['name'] ?? ''); ?>" style="width:300px;" placeholder="e.g., All Drinks 500 Pts">
                            <span style="font-weight:600; color:#1d2327; margin-left:15px;">Priority:</span>
                            <input type="number" name="mc_pts_bulk_costs[<?php echo $id; ?>][priority]" value="<?php echo esc_attr($rule['priority'] ?? '10'); ?>" style="width:60px;">
                        </div>

                        <div class="mc-form-row" style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                            <div class="mc-form-info"><span class="mc-form-label">Redemption Cost</span></div>
                            <div class="mc-form-control mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                                <span style="font-weight:600;">Require</span>
                                <input type="number" name="mc_pts_bulk_costs[<?php echo $id; ?>][point_cost]" value="<?php echo esc_attr($rule['point_cost'] ?? '100'); ?>" style="width:100px;">
                                <span style="font-weight:600;">Points to redeem items matching this rule.</span>
                            </div>
                        </div>

                        <div class="mc-toggle-row">
                            <div class="mc-form-info" style="margin:0;">
                                <span class="mc-form-label" style="color:#d63638;">Force Override Individual Product Settings</span>
                                <span class="mc-form-desc">
                                    If <strong>disabled</strong>, any point cost typed directly on an individual product's edit page will "win" and override this bulk rule.<br>
                                    If <strong>enabled</strong>, this rule crushes everything. Great for weekend specials.
                                </span>
                            </div>
                            <label class="mc-toggle-switch">
                                <input type="hidden" name="mc_pts_bulk_costs[<?php echo $id; ?>][force_override]" value="no">
                                <input type="checkbox" name="mc_pts_bulk_costs[<?php echo $id; ?>][force_override]" value="yes" <?php checked($rule['force_override'] ?? 'no', 'yes'); ?>>
                                <span class="mc-slider"></span>
                            </label>
                        </div>

                        <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Apply this cost to:</span></div>
                            <div class="mc-form-control">
                                <select class="mc-bulk-target-select" name="mc_pts_bulk_costs[<?php echo $id; ?>][target_type]" style="width:100%; max-width:400px; padding:6px; border-radius:4px; margin-bottom:10px;">
                                    <?php $tgt_type = $rule['target_type'] ?? 'categories'; ?>
                                    <option value="categories" <?php selected($tgt_type, 'categories'); ?>>Specific Categories</option>
                                    <option value="tags" <?php selected($tgt_type, 'tags'); ?>>Specific Tags</option>
                                    <option value="specific_products" <?php selected($tgt_type, 'specific_products'); ?>>Specific Products</option>
                                </select>
                                
                                <div class="mc-target-categories mc-target-wrap" style="<?php echo $tgt_type === 'categories' ? 'display:block;' : 'display:none;'; ?>">
                                    <?php mc_render_select2_field('mc_pts_bulk_costs['.$id.'][target_categories][]', $rule['target_categories'] ?? [], 'wc-category-search', 'Search and select categories...'); ?>
                                </div>
                                <div class="mc-target-tags mc-target-wrap" style="<?php echo $tgt_type === 'tags' ? 'display:block;' : 'display:none;'; ?>">
                                    <?php mc_render_select2_field('mc_pts_bulk_costs['.$id.'][target_tags][]', $rule['target_tags'] ?? [], 'wc-tag-search', 'Search and select tags...'); ?>
                                </div>
                                <div class="mc-target-specific_products mc-target-wrap" style="<?php echo $tgt_type === 'specific_products' ? 'display:block;' : 'display:none;'; ?>">
                                    <?php mc_render_select2_field('mc_pts_bulk_costs['.$id.'][target_products_list][]', $rule['target_products_list'] ?? [], 'wc-product-search', 'Search and select products...'); ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <?php
            }
        }

        if (!function_exists('mc_render_select2_field')) {
            function mc_render_select2_field($name, $saved_values, $class, $placeholder) {
                if(!is_array($saved_values)) $saved_values = [];
                echo '<select name="' . esc_attr($name) . '" class="mc-select2 ' . esc_attr($class) . '" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="' . esc_attr($placeholder) . '">';
                if(!empty($saved_values)) {
                    if($class === 'wc-product-search') {
                        foreach($saved_values as $item_id) {
                            $product = wc_get_product($item_id);
                            if($product) echo '<option value="'.esc_attr($item_id).'" selected="selected">'.wp_kses_post($product->get_formatted_name()).'</option>';
                        }
                    } elseif($class === 'wc-category-search') {
                        foreach($saved_values as $item_id) {
                            $term = get_term_by('id', $item_id, 'product_cat');
                            if($term) echo '<option value="'.esc_attr($item_id).'" selected="selected">'.esc_html($term->name).'</option>';
                        }
                    } elseif($class === 'wc-tag-search') {
                        foreach($saved_values as $item_id) {
                            $term = get_term_by('id', $item_id, 'product_tag');
                            if($term) echo '<option value="'.esc_attr($item_id).'" selected="selected">'.esc_html($term->name).'</option>';
                        }
                    }
                }
                echo '</select>';
            }
        }
        ?>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Bulk Rules', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script type="text/template" id="mc-bulk-cost-template">
    <div class="mc-rule-card mc-existing-rule" style="padding:0; overflow:hidden;">
        <input type="hidden" class="mc-rule-id" name="mc_pts_bulk_costs[{id}][id]" value="{id}">
        <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
            <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                <label class="mc-toggle-switch">
                    <input type="hidden" name="mc_pts_bulk_costs[{id}][active]" value="no">
                    <input type="checkbox" name="mc_pts_bulk_costs[{id}][active]" value="yes" checked>
                    <span class="mc-slider"></span>
                </label>
                <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display">New Bulk Rule</h3>
                <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-rule-type-badge">CATEGORIES</span>
            </div>
            <div style="display:flex; align-items:center; gap:15px;">
                <button type="button" class="mc-remove-bulk-cost" style="background:transparent; border:none; color:#d63638; text-decoration:none; font-weight:600; font-size:13px; cursor:pointer;">Delete Rule</button>
                <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▲</span>
            </div>
        </div>

        <div class="mc-rule-card-body" style="padding:20px;">
            <div class="mc-form-row mc-inline-inputs" style="background:transparent; border:none; padding:0; margin-bottom:20px;">
                <span style="font-weight:600; color:#1d2327;">Rule Name:</span>
                <input type="text" class="mc-rule-name-input" name="mc_pts_bulk_costs[{id}][name]" value="" style="width:300px;" placeholder="e.g., All Drinks 500 Pts">
                <span style="font-weight:600; color:#1d2327; margin-left:15px;">Priority:</span>
                <input type="number" name="mc_pts_bulk_costs[{id}][priority]" value="10" style="width:60px;">
            </div>

            <div class="mc-form-row" style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                <div class="mc-form-info"><span class="mc-form-label">Redemption Cost</span></div>
                <div class="mc-form-control mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                    <span style="font-weight:600;">Require</span>
                    <input type="number" name="mc_pts_bulk_costs[{id}][point_cost]" value="100" style="width:100px;">
                    <span style="font-weight:600;">Points to redeem items matching this rule.</span>
                </div>
            </div>

            <div class="mc-toggle-row">
                <div class="mc-form-info" style="margin:0;">
                    <span class="mc-form-label" style="color:#d63638;">Force Override Individual Product Settings</span>
                    <span class="mc-form-desc">
                        If <strong>disabled</strong>, any point cost typed directly on an individual product's edit page will "win" and override this bulk rule.<br>
                        If <strong>enabled</strong>, this rule crushes everything. Great for weekend specials.
                    </span>
                </div>
                <label class="mc-toggle-switch">
                    <input type="hidden" name="mc_pts_bulk_costs[{id}][force_override]" value="no">
                    <input type="checkbox" name="mc_pts_bulk_costs[{id}][force_override]" value="yes">
                    <span class="mc-slider"></span>
                </label>
            </div>

            <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Apply this cost to:</span></div>
                <div class="mc-form-control">
                    <select class="mc-bulk-target-select" name="mc_pts_bulk_costs[{id}][target_type]" style="width:100%; max-width:400px; padding:6px; border-radius:4px; margin-bottom:10px;">
                        <option value="categories" selected>Specific Categories</option>
                        <option value="tags">Specific Tags</option>
                        <option value="specific_products">Specific Products</option>
                    </select>
                    <div class="mc-target-categories mc-target-wrap" style="display:block;">
                        <select name="mc_pts_bulk_costs[{id}][target_categories][]" class="mc-select2 wc-category-search" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Search and select categories..."></select>
                    </div>
                    <div class="mc-target-tags mc-target-wrap" style="display:none;">
                        <select name="mc_pts_bulk_costs[{id}][target_tags][]" class="mc-select2 wc-tag-search" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Search and select tags..."></select>
                    </div>
                    <div class="mc-target-specific_products mc-target-wrap" style="display:none;">
                        <select name="mc_pts_bulk_costs[{id}][target_products_list][]" class="mc-select2 wc-product-search" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Search and select products..."></select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</script>

<script>
jQuery(document).ready(function($) {
    
    // 1. BULLETPROOF UI BUTTONS (These execute instantly and safely)
    $(document).on('click', '.mc-rule-card-header', function(e) {
        if($(e.target).closest('.mc-remove-bulk-cost, .mc-toggle-switch, input').length) return;
        var $body = $(this).siblings('.mc-rule-card-body');
        var $indicator = $(this).find('.mc-toggle-indicator');
        $body.slideToggle(200, function() {
            if($body.is(':visible')) { $indicator.text('▲'); } else { $indicator.text('▼'); }
        });
    });

    $(document).on('input', '.mc-rule-name-input', function() {
        var val = $(this).val();
        $(this).closest('.mc-rule-card').find('.mc-rule-title-display').text(val ? val : 'Unnamed Bulk Rule');
    });

    $(document).on('change', '.mc-bulk-target-select', function() {
        var $card = $(this).closest('.mc-rule-card');
        var val = $(this).val();
        $card.find('.mc-rule-type-badge').text(val.replace('_', ' '));
        $card.find('.mc-target-wrap').hide();
        $card.find('.mc-target-' + val).show();
    });

    $(document).on('click', '.mc-remove-bulk-cost', function(e) {
        e.preventDefault();
        if(confirm('Delete this rule? Click "Save Bulk Rules" below to permanently remove it.')) {
            var $card = $(this).closest('.mc-rule-card');
            // Remove inputs so WP doesn't save them
            $card.find('input, select, textarea').remove(); 
            $card.slideUp(300, function() { $(this).remove(); });
        }
    });

    // 2. SAFE ADD BUTTON AND SELECT2 INITIALIZATION
    window.initMcSelect2 = function($container) {
        try {
            if(typeof $.fn.select2 === 'undefined') return;

            var wc_nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_products_nonce : '';
            var cat_nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_categories_nonce : '';
            var tag_nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_tags_nonce : '';

            $container.find('.wc-product-search:not(.select2-hidden-accessible)').select2({
                allowClear: true, minimumInputLength: 3,
                ajax: { url: ajaxurl, dataType: 'json', delay: 250, data: function(p) { return { term: p.term, action: 'woocommerce_json_search_products_and_variations', security: wc_nonce }; }, processResults: function(d) { var t = []; if (d) { $.each(d, function(id, text) { t.push({ id: id, text: text }); }); } return { results: t }; }, cache: true }
            });

            $container.find('.wc-category-search:not(.select2-hidden-accessible)').select2({
                allowClear: true, minimumInputLength: 2,
                ajax: { url: ajaxurl, dataType: 'json', delay: 250, data: function(p) { return { term: p.term, action: 'woocommerce_json_search_categories', security: cat_nonce }; }, processResults: function(d) { var t = []; if (d) { $.each(d, function(id, text) { t.push({ id: id, text: text }); }); } return { results: t }; }, cache: true }
            });

            $container.find('.wc-tag-search:not(.select2-hidden-accessible)').select2({
                allowClear: true, minimumInputLength: 2,
                ajax: { url: ajaxurl, dataType: 'json', delay: 250, data: function(p) { return { term: p.term, action: 'woocommerce_json_search_tags', security: tag_nonce }; }, processResults: function(d) { var t = []; if (d) { $.each(d, function(id, text) { t.push({ id: id, text: text }); }); } return { results: t }; }, cache: true }
            });
        } catch(err) {
            console.warn("MealCrafter: Select2 search failed, but UI is protected.");
        }
    };

    // Initialize the boxes currently on the page
    window.initMcSelect2($('#mc-bulk-costs-container'));

    // The Add Button logic
    $('#mc-add-new-bulk-cost').on('click', function(e) {
        e.preventDefault();
        $('#mc-no-bulk-costs-msg').hide();
        var uniqueId = 'bulk_' + Date.now();
        var template = $('#mc-bulk-cost-template').html().replace(/{id}/g, uniqueId);
        
        $('#mc-bulk-costs-container').append(template);
        var $newCard = $('#mc-bulk-costs-container .mc-existing-rule').last();
        
        $newCard.find('.mc-rule-card-body').show();
        $newCard.find('.mc-toggle-indicator').text('▲');

        // Safely init the search bars on the new card
        window.initMcSelect2($newCard);
    });

});
</script>