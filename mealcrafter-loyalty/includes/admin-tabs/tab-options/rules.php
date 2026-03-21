<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div style="margin-bottom:20px; display:flex; justify-content:flex-end;">
    <button type="button" id="mc-add-new-rule" style="background:#2271b1; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">+ Add New Rule</button>
</div>

<form method="post" action="options.php" id="mc-loyalty-rules-form">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div id="mc-rules-container">
        <p class="description" style="margin-bottom:25px; font-size:14px;">Create advanced rules to automatically assign points to your customers. Rules override the default assignment ratios. Priority 1 is the highest priority.</p>
        
        <?php 
        $rules = get_option('mc_pts_rules', []); 
        if (!is_array($rules)) $rules = [];
        usort($rules, function($a, $b) { return ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99); });

        if(empty($rules)) {
            echo '<div class="mc-rule-card" id="mc-no-rules-msg" style="padding:40px; text-align:center; background:#f9f9f9;"><p style="margin:0; color:#777; font-size:15px;">No custom rules created yet. Click "Add New Rule" above to get started.</p></div>';
        } else {
            foreach($rules as $index => $rule) {
                $id = esc_attr($rule['id'] ?? 'rule_' . uniqid());
                ?>
                <div class="mc-rule-card mc-existing-rule" style="padding:0; overflow:hidden;">
                    <input type="hidden" name="mc_pts_rules[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                    
                    <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer; transition:background 0.2s;">
                        <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                            <label class="mc-toggle-switch" title="Toggle Rule Active/Inactive">
                                <input type="checkbox" name="mc_pts_rules[<?php echo $id; ?>][active]" value="yes" <?php checked($rule['active'] ?? 'yes', 'yes'); ?>>
                                <span class="mc-slider"></span>
                            </label>
                            <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display"><?php echo esc_html($rule['name'] ?: 'Unnamed Rule'); ?></h3>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <a href="#" class="mc-remove-rule" style="color:#d63638; text-decoration:none; font-weight:600; font-size:13px;">Delete Rule</a>
                            <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▼</span>
                        </div>
                    </div>

                    <div class="mc-rule-card-body" style="display:none; padding:20px;">
                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Rule name</span><span class="mc-form-desc">Enter a name to identify this rule.</span></div>
                            <div class="mc-form-control"><input type="text" class="mc-rule-name-input" name="mc_pts_rules[<?php echo $id; ?>][name]" value="<?php echo esc_attr($rule['name'] ?? ''); ?>" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;" placeholder="e.g. Weekend Promo"></div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Priority</span><span class="mc-form-desc">Set the priority. 1 is highest priority.</span></div>
                            <div class="mc-form-control"><input type="number" name="mc_pts_rules[<?php echo $id; ?>][priority]" value="<?php echo esc_attr($rule['priority'] ?? 1); ?>" style="width:80px; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Points type</span><span class="mc-form-desc">Choose how to calculate points.</span></div>
                            <div class="mc-form-control mc-radio-group">
                                <?php $type = $rule['type'] ?? 'fixed'; ?>
                                <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[<?php echo $id; ?>][type]" value="fixed" <?php checked($type, 'fixed'); ?>> Assign a fixed amount of points</label>
                                <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[<?php echo $id; ?>][type]" value="percent" <?php checked($type, 'percent'); ?>> Set a % multiplier based on global points rules</label>
                                <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[<?php echo $id; ?>][type]" value="fixed_ratio" <?php checked($type, 'fixed_ratio'); ?>> Set a fixed amount of points based on product prices</label>
                                <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[<?php echo $id; ?>][type]" value="none" <?php checked($type, 'none'); ?>> Don't assign points</label>
                            </div>
                        </div>

                        <div class="mc-form-row mc-rule-value-wrap" style="<?php echo ($type === 'fixed' || $type === 'percent') ? 'display:block;' : 'display:none;'; ?>">
                            <div class="mc-form-info"><span class="mc-form-label">Points to assign</span></div>
                            <div class="mc-form-control"><input type="number" step="0.01" name="mc_pts_rules[<?php echo $id; ?>][value]" value="<?php echo esc_attr($rule['value'] ?? 0); ?>" style="width:100px; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
                        </div>

                        <div class="mc-form-row mc-rule-ratio-wrap" style="<?php echo $type === 'fixed_ratio' ? 'display:block;' : 'display:none;'; ?>">
                            <div class="mc-form-info"><span class="mc-form-label">Custom Ratio</span></div>
                            <div class="mc-form-control mc-inline-inputs">
                                <span style="font-weight:600;">For each</span>
                                <input type="number" step="0.01" name="mc_pts_rules[<?php echo $id; ?>][currency_ratio]" value="<?php echo esc_attr($rule['currency_ratio'] ?? 100); ?>" style="width:80px;">
                                <span style="font-weight:600;">$ spent, assign</span>
                                <input type="number" step="0.01" name="mc_pts_rules[<?php echo $id; ?>][points_ratio]" value="<?php echo esc_attr($rule['points_ratio'] ?? 20); ?>" style="width:80px;">
                                <span style="font-weight:600;">Points</span>
                            </div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Rule will be valid</span></div>
                            <div class="mc-form-control mc-radio-group">
                                <?php $validity = $rule['validity'] ?? 'always'; ?>
                                <label><input type="radio" class="mc-validity-toggle" name="mc_pts_rules[<?php echo $id; ?>][validity]" value="always" <?php checked($validity, 'always'); ?>> From now, until ended manually</label>
                                <label><input type="radio" class="mc-validity-toggle" name="mc_pts_rules[<?php echo $id; ?>][validity]" value="scheduled" <?php checked($validity, 'scheduled'); ?>> Schedule a start and end date</label>
                            </div>
                        </div>

                        <div class="mc-form-row mc-scheduled-wrap" style="<?php echo $validity === 'scheduled' ? 'display:block;' : 'display:none;'; ?>">
                            <div class="mc-form-info"><span class="mc-form-label">Dates</span></div>
                            <div class="mc-form-control mc-inline-inputs">
                                <input type="date" name="mc_pts_rules[<?php echo $id; ?>][start_date]" value="<?php echo esc_attr($rule['start_date'] ?? ''); ?>">
                                <span>to</span>
                                <input type="date" name="mc_pts_rules[<?php echo $id; ?>][end_date]" value="<?php echo esc_attr($rule['end_date'] ?? ''); ?>">
                            </div>
                        </div>

                        <div class="mc-form-row" style="margin-bottom:0;">
                            <div class="mc-form-info"><span class="mc-form-label">Apply rule to categories</span><span class="mc-form-desc">Leave blank to apply to ALL products.</span></div>
                            <div class="mc-form-control">
                                <select name="mc_pts_rules[<?php echo $id; ?>][target_cats][]" class="wc-enhanced-select" multiple="multiple" style="width:100%;">
                                    <?php 
                                    $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
                                    $saved_cats = $rule['target_cats'] ?? [];
                                    foreach($terms as $term): ?>
                                        <option value="<?php echo esc_attr($term->term_id); ?>" <?php echo in_array($term->term_id, (array)$saved_cats) ? 'selected' : ''; ?>><?php echo esc_html($term->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <div id="mc-rule-template" style="display:none;">
        <div class="mc-rule-card" style="padding:0; overflow:hidden;">
            <input type="hidden" name="mc_pts_rules[{id}][id]" value="{id}">
            
            <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
                <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                    <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_rules[{id}][active]" value="yes" checked><span class="mc-slider"></span></label>
                    <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display">New Rule</h3>
                </div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <a href="#" class="mc-remove-rule" style="color:#d63638; text-decoration:none; font-weight:600; font-size:13px;">Delete Rule</a>
                    <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▲</span>
                </div>
            </div>

            <div class="mc-rule-card-body" style="padding:20px;">
                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Rule name</span></div>
                    <div class="mc-form-control"><input type="text" class="mc-rule-name-input" name="mc_pts_rules[{id}][name]" value="" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;" placeholder="e.g. Weekend Promo"></div>
                </div>

                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Priority</span></div>
                    <div class="mc-form-control"><input type="number" name="mc_pts_rules[{id}][priority]" value="1" style="width:80px; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
                </div>

                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Points type</span></div>
                    <div class="mc-form-control mc-radio-group">
                        <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[{id}][type]" value="fixed" checked> Assign a fixed amount of points</label>
                        <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[{id}][type]" value="percent"> Set a % multiplier</label>
                        <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[{id}][type]" value="fixed_ratio"> Set a fixed amount based on price</label>
                        <label><input type="radio" class="mc-type-toggle" name="mc_pts_rules[{id}][type]" value="none"> Don't assign points</label>
                    </div>
                </div>

                <div class="mc-form-row mc-rule-value-wrap" style="display:block;">
                    <div class="mc-form-info"><span class="mc-form-label">Points to assign</span></div>
                    <div class="mc-form-control"><input type="number" step="0.01" name="mc_pts_rules[{id}][value]" value="0" style="width:100px; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
                </div>

                <div class="mc-form-row mc-rule-ratio-wrap" style="display:none;">
                    <div class="mc-form-info"><span class="mc-form-label">Custom Ratio</span></div>
                    <div class="mc-form-control mc-inline-inputs">
                        <span style="font-weight:600;">For each</span> <input type="number" step="0.01" name="mc_pts_rules[{id}][currency_ratio]" value="100" style="width:80px;">
                        <span style="font-weight:600;">$ spent, assign</span> <input type="number" step="0.01" name="mc_pts_rules[{id}][points_ratio]" value="20" style="width:80px;">
                        <span style="font-weight:600;">Points</span>
                    </div>
                </div>

                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Rule will be valid</span></div>
                    <div class="mc-form-control mc-radio-group">
                        <label><input type="radio" class="mc-validity-toggle" name="mc_pts_rules[{id}][validity]" value="always" checked> Always</label>
                        <label><input type="radio" class="mc-validity-toggle" name="mc_pts_rules[{id}][validity]" value="scheduled"> Scheduled</label>
                    </div>
                </div>

                <div class="mc-form-row mc-scheduled-wrap" style="display:none;">
                    <div class="mc-form-info"><span class="mc-form-label">Dates</span></div>
                    <div class="mc-form-control mc-inline-inputs">
                        <input type="date" name="mc_pts_rules[{id}][start_date]"> <span>to</span> <input type="date" name="mc_pts_rules[{id}][end_date]">
                    </div>
                </div>

                <div class="mc-form-row" style="margin-bottom:0;">
                    <div class="mc-form-info"><span class="mc-form-label">Apply rule to categories</span></div>
                    <div class="mc-form-control">
                        <select name="mc_pts_rules[{id}][target_cats][]" class="wc-enhanced-select-dynamic" multiple="multiple" style="width:100%;">
                            <?php 
                            $terms = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
                            foreach($terms as $term): ?>
                                <option value="<?php echo esc_attr($term->term_id); ?>"><?php echo esc_html($term->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Options', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    if ($.fn.selectWoo) { $('.wc-enhanced-select').selectWoo(); }

    // Hover effect on headers
    $(document).on('mouseenter', '.mc-rule-card-header', function() { $(this).css('background', '#f5f5f5'); })
               .on('mouseleave', '.mc-rule-card-header', function() { $(this).css('background', '#fcfcfc'); });

    // Accordion Toggle
    $(document).on('click', '.mc-rule-card-header', function(e) {
        // Don't trigger accordion if they clicked the switch or the delete button
        if($(e.target).closest('.mc-toggle-switch').length || $(e.target).hasClass('mc-remove-rule')) {
            return;
        }
        
        let $body = $(this).siblings('.mc-rule-card-body');
        let $indicator = $(this).find('.mc-toggle-indicator');
        
        $body.slideToggle(200, function() {
            if($body.is(':visible')) {
                $indicator.text('▲');
            } else {
                $indicator.text('▼');
            }
        });
    });

    // Live Title Update
    $(document).on('input', '.mc-rule-name-input', function() {
        let val = $(this).val();
        $(this).closest('.mc-rule-card').find('.mc-rule-title-display').text(val ? val : 'Unnamed Rule');
    });

    // Add New Rule
    $('#mc-add-new-rule').on('click', function(e) {
        e.preventDefault();
        $('#mc-no-rules-msg').hide();
        let uniqueId = 'rule_' + Date.now();
        let template = $('#mc-rule-template').html().replace(/{id}/g, uniqueId);
        let $newRule = $(template);
        
        $('#mc-rules-container').prepend($newRule);
        if ($.fn.selectWoo) { $newRule.find('.wc-enhanced-select-dynamic').selectWoo(); }
    });

    // Remove Rule
    $(document).on('click', '.mc-remove-rule', function(e) {
        e.preventDefault();
        if(confirm('Are you sure you want to delete this rule? Make sure to click Save Options to permanently remove it.')) {
            $(this).closest('.mc-rule-card').slideUp(300, function() { $(this).remove(); });
        }
    });

    // Toggle Dates & Rule Types
    $(document).on('change', '.mc-validity-toggle', function() {
        let $wrap = $(this).closest('.mc-rule-card').find('.mc-scheduled-wrap');
        if($(this).val() === 'scheduled') { $wrap.hide().slideDown(); } else { $wrap.slideUp(); }
    });

    $(document).on('change', '.mc-type-toggle', function() {
        let $card = $(this).closest('.mc-rule-card');
        let val = $(this).val();
        $card.find('.mc-rule-value-wrap, .mc-rule-ratio-wrap').hide();
        if(val === 'fixed' || val === 'percent') { $card.find('.mc-rule-value-wrap').hide().slideDown(); } 
        else if(val === 'fixed_ratio') { $card.find('.mc-rule-ratio-wrap').hide().slideDown(); }
    });
});
</script>