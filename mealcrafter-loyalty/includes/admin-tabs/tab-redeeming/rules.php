<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div style="margin-bottom:20px; display:flex; justify-content:flex-end;">
    <button type="button" id="mc-add-new-redeem-rule" style="background:#2271b1; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">+ Add New Rule</button>
</div>

<form method="post" action="options.php" id="mc-loyalty-redeem-rules-form">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div style="display:none;">
        <input type="hidden" name="mc_pts_redeem_rules[__empty__][id]" value="__empty__">
    </div>
    
    <div id="mc-redeem-rules-container">
        <p class="description" style="margin-bottom:25px; font-size:14px;">Create custom rules that override the global redeeming options based on products, categories, or user roles.</p>
        
        <?php 
        $all_rules = get_option('mc_pts_redeem_rules', []); 
        if (!is_array($all_rules)) $all_rules = [];

        $rules = array_filter($all_rules, function($r) {
            return !empty($r['id']) && $r['id'] !== '__empty__' && $r['id'] !== '{id}';
        });

        usort($rules, function($a, $b) { return ($a['priority'] ?? 10) <=> ($b['priority'] ?? 10); });

        if(empty($rules)) {
            echo '<div class="mc-rule-card" id="mc-no-redeem-rules-msg" style="padding:40px; text-align:center; background:#f9f9f9;"><p style="margin:0; color:#777; font-size:15px;">No custom redeeming rules created yet. Click "Add New Rule" above to get started.</p></div>';
        } else {
            foreach($rules as $index => $rule) {
                $id = esc_attr($rule['id']);
                $type = $rule['type'] ?? 'conversion';
                ?>
                <div class="mc-rule-card mc-existing-rule" style="padding:0; overflow:hidden;">
                    <input type="hidden" class="mc-rule-id" name="mc_pts_redeem_rules[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                    
                    <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <label class="mc-toggle-switch" title="Toggle Active/Inactive">
                                <input type="checkbox" name="mc_pts_redeem_rules[<?php echo $id; ?>][active]" value="yes" <?php checked($rule['active'] ?? 'yes', 'yes'); ?>>
                                <span class="mc-slider"></span>
                            </label>
                            <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display"><?php echo esc_html($rule['name'] ?: 'Unnamed Rule'); ?></h3>
                            <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-rule-type-badge"><?php echo $type === 'conversion' ? 'CONVERSION RATE' : 'MAX DISCOUNT'; ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <button type="button" class="mc-remove-redeem-rule" style="background:transparent; border:none; color:#d63638; text-decoration:none; font-weight:600; font-size:13px; cursor:pointer;">Delete Rule</button>
                            <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▼</span>
                        </div>
                    </div>

                    <div class="mc-rule-card-body" style="display:none; padding:20px;">
                        
                        <div class="mc-form-row mc-inline-inputs" style="background:transparent; border:none; padding:0; margin-bottom:20px;">
                            <span style="font-weight:600; color:#1d2327;">Rule name:</span>
                            <input type="text" class="mc-rule-name-input" name="mc_pts_redeem_rules[<?php echo $id; ?>][name]" value="<?php echo esc_attr($rule['name'] ?? ''); ?>" style="width:300px;">
                            <span style="font-weight:600; color:#1d2327; margin-left:15px;">Priority:</span>
                            <input type="number" name="mc_pts_redeem_rules[<?php echo $id; ?>][priority]" value="<?php echo esc_attr($rule['priority'] ?? '10'); ?>" style="width:60px;">
                        </div>

                        <div class="mc-form-row" style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                            <div class="mc-form-info"><span class="mc-form-label">Rule type</span></div>
                            <div class="mc-form-control mc-radio-group">
                                <label><input type="radio" class="mc-redeem-type-toggle" name="mc_pts_redeem_rules[<?php echo $id; ?>][type]" value="conversion" <?php checked($type, 'conversion'); ?>> Redeem conversion rate</label>
                                <label><input type="radio" class="mc-redeem-type-toggle" name="mc_pts_redeem_rules[<?php echo $id; ?>][type]" value="max_discount" <?php checked($type, 'max_discount'); ?>> Redeem max discount rate</label>
                            </div>
                        </div>

                        <div class="mc-type-section mc-type-conversion" style="<?php echo $type === 'conversion' ? 'display:block;' : 'display:none;'; ?>">
                            <div class="mc-form-row">
                                <div class="mc-form-info"><span class="mc-form-label">Reward conversion rate</span></div>
                                <div class="mc-form-control mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                                    <input type="number" name="mc_pts_redeem_rules[<?php echo $id; ?>][conv_pts]" value="<?php echo esc_attr($rule['conv_pts'] ?? '100'); ?>" style="width:80px;">
                                    <span style="font-weight:600; margin:0 10px;">Points = </span>
                                    <input type="number" step="0.01" name="mc_pts_redeem_rules[<?php echo $id; ?>][conv_currency]" value="<?php echo esc_attr($rule['conv_currency'] ?? '1'); ?>" style="width:80px;">
                                    <span style="font-weight:600; margin-left:10px;">$ discount</span>
                                </div>
                            </div>
                        </div>

                        <div class="mc-type-section mc-type-max_discount" style="<?php echo $type === 'max_discount' ? 'display:block;' : 'display:none;'; ?>">
                            <div class="mc-form-row" style="display:flex; gap:20px; align-items:flex-start;">
                                <div class="mc-form-info" style="flex:0 0 200px;"><span class="mc-form-label">Max discount type</span></div>
                                <div class="mc-form-control mc-radio-group" style="flex:1;">
                                    <?php $m_type = $rule['max_type'] ?? 'fixed'; ?>
                                    
                                    <div style="margin-bottom:15px;">
                                        <label style="display:inline-block; font-weight:600;"><input type="radio" class="mc-max-type-radio" data-target="percent-val-<?php echo $id; ?>" name="mc_pts_redeem_rules[<?php echo $id; ?>][max_type]" value="percent" <?php checked($m_type, 'percent'); ?>> Set a % max discount based on the global max discount</label>
                                        <div style="margin:5px 0 0 25px; color:#646970; font-size:13px;">(for example: with a global max discount of $50, if you set a max discount of 10% for this product, the user will get a max discount of $5)</div>
                                    </div>
                                    
                                    <div>
                                        <label style="display:inline-block; font-weight:600;"><input type="radio" class="mc-max-type-radio" data-target="fixed-val-<?php echo $id; ?>" name="mc_pts_redeem_rules[<?php echo $id; ?>][max_type]" value="fixed" <?php checked($m_type, 'fixed'); ?>> Set a fixed max discount value</label>
                                        <div style="margin:5px 0 0 25px; color:#646970; font-size:13px;">(for example: a max discount of $5 for this product)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mc-form-row" style="display:flex; gap:20px; align-items:center;">
                                <div class="mc-form-info" style="flex:0 0 200px;"><span class="mc-form-label">Amount</span></div>
                                <div class="mc-form-control mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                                    <input type="number" step="0.01" name="mc_pts_redeem_rules[<?php echo $id; ?>][max_val]" value="<?php echo esc_attr($rule['max_val'] ?? '5'); ?>" style="width:100px;">
                                    <strong id="percent-val-<?php echo $id; ?>" style="<?php echo $m_type === 'percent' ? 'display:inline;' : 'display:none;'; ?>">%</strong>
                                    <strong id="fixed-val-<?php echo $id; ?>" style="<?php echo $m_type === 'fixed' ? 'display:inline;' : 'display:none;'; ?>">$</strong>
                                </div>
                            </div>
                        </div>

                        <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">
                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Apply rule to these products</span></div>
                            <div class="mc-form-control">
                                <select class="mc-product-target-select" name="mc_pts_redeem_rules[<?php echo $id; ?>][target_products]" style="width:100%; max-width:400px; padding:6px; border-radius:4px; margin-bottom:10px;">
                                    <?php $tgt_prod = $rule['target_products'] ?? 'all'; ?>
                                    <option value="all" <?php selected($tgt_prod, 'all'); ?>>All products</option>
                                    <option value="specific" <?php selected($tgt_prod, 'specific'); ?>>Specific products</option>
                                    <option value="categories" <?php selected($tgt_prod, 'categories'); ?>>Products of specific categories</option>
                                    <option value="tags" <?php selected($tgt_prod, 'tags'); ?>>Products of specific tags</option>
                                </select>
                                
                                <div class="mc-target-specific mc-target-wrap" style="<?php echo $tgt_prod === 'specific' ? 'display:block;' : 'display:none;'; ?>">
                                    <?php mc_render_select2_field('mc_pts_redeem_rules['.$id.'][spec_products][]', $rule['spec_products'] ?? [], 'wc-product-search', 'Select specific products...'); ?>
                                </div>
                                <div class="mc-target-categories mc-target-wrap" style="<?php echo $tgt_prod === 'categories' ? 'display:block;' : 'display:none;'; ?>">
                                    <?php mc_render_select2_field('mc_pts_redeem_rules['.$id.'][spec_categories][]', $rule['spec_categories'] ?? [], 'wc-category-search', 'Select product categories...'); ?>
                                </div>
                                <div class="mc-target-tags mc-target-wrap" style="<?php echo $tgt_prod === 'tags' ? 'display:block;' : 'display:none;'; ?>">
                                    <?php mc_render_select2_field('mc_pts_redeem_rules['.$id.'][spec_tags][]', $rule['spec_tags'] ?? [], 'wc-tag-search', 'Select product tags...'); ?>
                                </div>
                            </div>
                        </div>

                        <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">
                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Apply rule to</span></div>
                            <div class="mc-form-control mc-radio-group">
                                <?php $tgt_user = $rule['target_users'] ?? 'all'; ?>
                                <label style="display:block; margin-bottom:8px;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_redeem_rules[<?php echo $id; ?>][target_users]" value="all" <?php checked($tgt_user, 'all'); ?>> All users</label>
                                <label style="display:block; margin-bottom:8px;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_redeem_rules[<?php echo $id; ?>][target_users]" value="roles" <?php checked($tgt_user, 'roles'); ?>> Specific user roles</label>
                                <label style="display:block;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_redeem_rules[<?php echo $id; ?>][target_users]" value="levels" <?php checked($tgt_user, 'levels'); ?>> Users with a specific points level</label>
                            </div>
                            
                            <div class="mc-user-target-roles mc-user-wrap" style="<?php echo $tgt_user === 'roles' ? 'display:block;' : 'display:none;'; ?> margin-top:10px;">
                                <?php 
                                global $wp_roles;
                                $saved_roles = is_array($rule['spec_roles'] ?? null) ? $rule['spec_roles'] : [];
                                echo '<select name="mc_pts_redeem_rules['.$id.'][spec_roles][]" class="mc-select2" multiple="multiple" style="width:100%; max-width:400px;">';
                                foreach ( $wp_roles->roles as $key => $r ) {
                                    $selected = in_array($key, $saved_roles) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($r['name']) . '</option>';
                                }
                                echo '</select>';
                                ?>
                            </div>

                            <div class="mc-user-target-levels mc-user-wrap" style="<?php echo $tgt_user === 'levels' ? 'display:block;' : 'display:none;'; ?> margin-top:10px;">
                                <?php 
                                $all_levels = get_option('mc_pts_levels', []);
                                $saved_levels = is_array($rule['spec_levels'] ?? null) ? $rule['spec_levels'] : [];
                                echo '<select name="mc_pts_redeem_rules['.$id.'][spec_levels][]" class="mc-select2" multiple="multiple" style="width:100%; max-width:400px;">';
                                if(is_array($all_levels) && !empty($all_levels)) {
                                    foreach ( $all_levels as $lvl ) {
                                        if(empty($lvl['id'])) continue;
                                        $selected = in_array($lvl['id'], $saved_levels) ? 'selected' : '';
                                        echo '<option value="' . esc_attr($lvl['id']) . '" ' . $selected . '>' . esc_html($lvl['name']) . '</option>';
                                    }
                                } else {
                                    echo '<option disabled>No Levels created yet.</option>';
                                }
                                echo '</select>';
                                ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php
            }
        }

        // Helper function for Select2 Fields
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
        ?>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Rules', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script type="text/template" id="mc-rule-template">
    <div class="mc-rule-card mc-existing-rule" style="padding:0; overflow:hidden;">
        <input type="hidden" class="mc-rule-id" name="mc_pts_redeem_rules[{id}][id]" value="{id}">
        
        <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
            <div style="display:flex; align-items:center; gap:15px;">
                <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_redeem_rules[{id}][active]" value="yes" checked><span class="mc-slider"></span></label>
                <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display">New Rule</h3>
                <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-rule-type-badge">CONVERSION RATE</span>
            </div>
            <div style="display:flex; align-items:center; gap:15px;">
                <button type="button" class="mc-remove-redeem-rule" style="background:transparent; border:none; color:#d63638; text-decoration:none; font-weight:600; font-size:13px; cursor:pointer;">Delete Rule</button>
                <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▲</span>
            </div>
        </div>

        <div class="mc-rule-card-body" style="padding:20px;">
            
            <div class="mc-form-row mc-inline-inputs" style="background:transparent; border:none; padding:0; margin-bottom:20px;">
                <span style="font-weight:600; color:#1d2327;">Rule name:</span>
                <input type="text" class="mc-rule-name-input" name="mc_pts_redeem_rules[{id}][name]" value="" style="width:300px;">
                <span style="font-weight:600; color:#1d2327; margin-left:15px;">Priority:</span>
                <input type="number" name="mc_pts_redeem_rules[{id}][priority]" value="10" style="width:60px;">
            </div>

            <div class="mc-form-row" style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                <div class="mc-form-info"><span class="mc-form-label">Rule type</span></div>
                <div class="mc-form-control mc-radio-group">
                    <label><input type="radio" class="mc-redeem-type-toggle" name="mc_pts_redeem_rules[{id}][type]" value="conversion" checked> Redeem conversion rate</label>
                    <label><input type="radio" class="mc-redeem-type-toggle" name="mc_pts_redeem_rules[{id}][type]" value="max_discount"> Redeem max discount rate</label>
                </div>
            </div>

            <div class="mc-type-section mc-type-conversion">
                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Reward conversion rate</span></div>
                    <div class="mc-form-control mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                        <input type="number" name="mc_pts_redeem_rules[{id}][conv_pts]" value="100" style="width:80px;">
                        <span style="font-weight:600; margin:0 10px;">Points = </span>
                        <input type="number" step="0.01" name="mc_pts_redeem_rules[{id}][conv_currency]" value="1" style="width:80px;">
                        <span style="font-weight:600; margin-left:10px;">$ discount</span>
                    </div>
                </div>
            </div>

            <div class="mc-type-section mc-type-max_discount" style="display:none;">
                <div class="mc-form-row" style="display:flex; gap:20px; align-items:flex-start;">
                    <div class="mc-form-info" style="flex:0 0 200px;">
                        <span class="mc-form-label">Max discount type</span>
                    </div>
                    <div class="mc-form-control mc-radio-group" style="flex:1;">
                        <div style="margin-bottom:15px;">
                            <label style="display:inline-block; font-weight:600;"><input type="radio" class="mc-max-type-radio" data-target="percent-val-{id}" name="mc_pts_redeem_rules[{id}][max_type]" value="percent"> Set a % max discount based on the global max discount</label>
                            <div style="margin:5px 0 0 25px; color:#646970; font-size:13px;">(for example: with a global max discount of $50, if you set a max discount of 10% for this product, the user will get a max discount of $5)</div>
                        </div>
                        
                        <div>
                            <label style="display:inline-block; font-weight:600;"><input type="radio" class="mc-max-type-radio" data-target="fixed-val-{id}" name="mc_pts_redeem_rules[{id}][max_type]" value="fixed" checked> Set a fixed max discount value</label>
                            <div style="margin:5px 0 0 25px; color:#646970; font-size:13px;">(for example: a max discount of $5 for this product)</div>
                        </div>
                    </div>
                </div>
                
                <div class="mc-form-row" style="display:flex; gap:20px; align-items:center;">
                    <div class="mc-form-info" style="flex:0 0 200px;"><span class="mc-form-label">Amount</span></div>
                    <div class="mc-form-control mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                        <input type="number" step="0.01" name="mc_pts_redeem_rules[{id}][max_val]" value="5" style="width:100px;">
                        <strong id="percent-val-{id}" style="display:none;">%</strong>
                        <strong id="fixed-val-{id}">$</strong>
                    </div>
                </div>
            </div>

            <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">
            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Apply rule to these products</span></div>
                <div class="mc-form-control">
                    <select class="mc-product-target-select" name="mc_pts_redeem_rules[{id}][target_products]" style="width:100%; max-width:400px; padding:6px; border-radius:4px; margin-bottom:10px;">
                        <option value="all">All products</option>
                        <option value="specific">Specific products</option>
                        <option value="categories">Products of specific categories</option>
                        <option value="tags">Products of specific tags</option>
                    </select>
                    <div class="mc-target-specific mc-target-wrap" style="display:none;">
                        <select name="mc_pts_redeem_rules[{id}][spec_products][]" class="mc-select2 wc-product-search" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Search for a product..."></select>
                    </div>
                    <div class="mc-target-categories mc-target-wrap" style="display:none;">
                        <select name="mc_pts_redeem_rules[{id}][spec_categories][]" class="mc-select2 wc-category-search" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Search for a category..."></select>
                    </div>
                    <div class="mc-target-tags mc-target-wrap" style="display:none;">
                        <select name="mc_pts_redeem_rules[{id}][spec_tags][]" class="mc-select2 wc-tag-search" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Search for a tag..."></select>
                    </div>
                </div>
            </div>

            <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Apply rule to</span></div>
                <div class="mc-form-control mc-radio-group">
                    <label style="display:block; margin-bottom:8px;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_redeem_rules[{id}][target_users]" value="all" checked> All users</label>
                    <label style="display:block; margin-bottom:8px;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_redeem_rules[{id}][target_users]" value="roles"> Specific user roles</label>
                    <label style="display:block;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_redeem_rules[{id}][target_users]" value="levels"> Users with a specific points level</label>
                </div>
                
                <div class="mc-user-target-roles mc-user-wrap" style="display:none; margin-top:10px;">
                    <?php 
                    global $wp_roles;
                    echo '<select name="mc_pts_redeem_rules[{id}][spec_roles][]" class="mc-select2" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Select user roles...">';
                    foreach ( $wp_roles->roles as $key => $r ) echo '<option value="' . esc_attr($key) . '">' . esc_html($r['name']) . '</option>';
                    echo '</select>';
                    ?>
                </div>

                <div class="mc-user-target-levels mc-user-wrap" style="display:none; margin-top:10px;">
                    <?php 
                    $all_levels = get_option('mc_pts_levels', []);
                    echo '<select name="mc_pts_redeem_rules[{id}][spec_levels][]" class="mc-select2" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Select levels...">';
                    if(is_array($all_levels) && !empty($all_levels)) {
                        foreach ( $all_levels as $lvl ) {
                            if(!empty($lvl['id'])) echo '<option value="' . esc_attr($lvl['id']) . '">' . esc_html($lvl['name']) . '</option>';
                        }
                    }
                    echo '</select>';
                    ?>
                </div>
            </div>

        </div>
    </div>
</script>

<script>
jQuery(document).ready(function($) {
    
    function initSelect2Fields(container) {
        if(!$.fn.select2) return;

        container.find('.mc-select2:not(.wc-product-search):not(.wc-category-search):not(.wc-tag-search)').select2({ allowClear: true });

        container.find('.wc-product-search').filter(':not(.select2-hidden-accessible)').select2({
            allowClear: true,
            minimumInputLength: 3,
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { term: params.term, action: 'woocommerce_json_search_products_and_variations', security: wc_enhanced_select_params.search_products_nonce };
                },
                processResults: function(data) {
                    var terms = [];
                    if (data) { $.each(data, function(id, text) { terms.push({ id: id, text: text }); }); }
                    return { results: terms };
                },
                cache: true
            }
        });

        container.find('.wc-category-search').filter(':not(.select2-hidden-accessible)').select2({
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { term: params.term, action: 'woocommerce_json_search_categories', security: wc_enhanced_select_params.search_categories_nonce };
                },
                processResults: function(data) {
                    var terms = [];
                    if (data) { $.each(data, function(id, text) { terms.push({ id: id, text: text }); }); }
                    return { results: terms };
                },
                cache: true
            }
        });

        container.find('.wc-tag-search').filter(':not(.select2-hidden-accessible)').select2({
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: ajaxurl,
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return { term: params.term, action: 'woocommerce_json_search_tags', security: wc_enhanced_select_params.search_tags_nonce };
                },
                processResults: function(data) {
                    var terms = [];
                    if (data) { $.each(data, function(id, text) { terms.push({ id: id, text: text }); }); }
                    return { results: terms };
                },
                cache: true
            }
        });
    }

    initSelect2Fields($('#mc-redeem-rules-container'));

    $(document).on('click', '.mc-rule-card-header', function(e) {
        if($(e.target).closest('.mc-remove-redeem-rule, .mc-toggle-switch').length) return;
        let $body = $(this).siblings('.mc-rule-card-body');
        let $indicator = $(this).find('.mc-toggle-indicator');
        $body.slideToggle(200, function() {
            if($body.is(':visible')) { $indicator.text('▲'); } else { $indicator.text('▼'); }
        });
    });

    $(document).on('input', '.mc-rule-name-input', function() {
        let val = $(this).val();
        $(this).closest('.mc-rule-card').find('.mc-rule-title-display').text(val ? val : 'Unnamed Rule');
    });

    $(document).on('change', '.mc-redeem-type-toggle', function() {
        let $card = $(this).closest('.mc-rule-card');
        let val = $(this).val();
        $card.find('.mc-rule-type-badge').text(val === 'conversion' ? 'CONVERSION RATE' : 'MAX DISCOUNT');
        $card.find('.mc-type-section').hide();
        $card.find('.mc-type-' + val).show();
    });

    // Toggle between Percentage (%) and Fixed ($) indicators based on Radio Button
    $(document).on('change', '.mc-max-type-radio', function() {
        let $card = $(this).closest('.mc-rule-card');
        let targetId = $(this).data('target');
        
        $card.find('.mc-inline-inputs strong').hide(); // Hide both $ and %
        $card.find('#' + targetId).css('display', 'inline'); // Show the correct one
    });

    $(document).on('change', '.mc-product-target-select', function() {
        let $card = $(this).closest('.mc-rule-card');
        let val = $(this).val();
        $card.find('.mc-target-wrap').hide();
        if(val !== 'all') { $card.find('.mc-target-' + val).show(); }
    });

    $(document).on('change', '.mc-user-target-toggle', function() {
        let $card = $(this).closest('.mc-rule-card');
        let val = $(this).val();
        $card.find('.mc-user-wrap').hide();
        if(val !== 'all') { $card.find('.mc-user-target-' + val).show(); }
    });

    $('#mc-add-new-redeem-rule').on('click', function(e) {
        e.preventDefault();
        $('#mc-no-redeem-rules-msg').hide();
        let uniqueId = 'rule_' + Date.now();
        let template = $('#mc-rule-template').html().replace(/{id}/g, uniqueId);
        
        $('#mc-redeem-rules-container').append(template);
        let $newCard = $('#mc-redeem-rules-container .mc-existing-rule').last();
        
        initSelect2Fields($newCard);
        
        $newCard.find('.mc-rule-card-body').slideDown();
        $newCard.find('.mc-toggle-indicator').text('▲');
    });

    $(document).on('click', '.mc-remove-redeem-rule', function(e) {
        e.preventDefault();
        if(confirm('Delete this rule? Click Save Rules below to permanently remove it.')) {
            let $card = $(this).closest('.mc-rule-card');
            $card.find('.mc-rule-id').val(''); 
            $card.slideUp(300);
        }
    });

});
</script>