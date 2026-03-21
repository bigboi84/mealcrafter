<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form method="post" action="options.php">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div class="mc-form-section">
        <div class="mc-form-row">
            <div class="mc-form-info">
                <span class="mc-form-label">Assign points to users</span>
                <span class="mc-form-desc">Choose whether to award points to users automatically or manually.</span>
            </div>
            <div class="mc-form-control mc-radio-group">
                <?php $assign_type = get_option('mc_pts_assign_type', 'auto'); ?>
                <label><input type="radio" name="mc_pts_assign_type" value="auto" <?php checked($assign_type, 'auto'); ?>> Automatically - Points will be assigned automatically for each purchase</label>
                <label><input type="radio" name="mc_pts_assign_type" value="manual" <?php checked($assign_type, 'manual'); ?>> Manually - You can assign points manually in 'Customers Points' tab</label>
            </div>
        </div>

        <div id="mc-auto-assign-wrapper">
            <div class="mc-form-row">
                <div class="mc-form-info">
                    <span class="mc-form-label">Assign points to</span>
                    <span class="mc-form-desc">Choose whether to assign points to all users or only to specified user roles.</span>
                </div>
                <div class="mc-form-control mc-radio-group">
                    <?php $assign_roles = get_option('mc_pts_assign_roles', 'all'); ?>
                    <label><input type="radio" name="mc_pts_assign_roles" value="all" <?php checked($assign_roles, 'all'); ?>> All users</label>
                    <label><input type="radio" name="mc_pts_assign_roles" value="specific" <?php checked($assign_roles, 'specific'); ?>> Only specified user roles</label>
                </div>
            </div>

            <div class="mc-form-row" id="mc-specific-roles-wrapper">
                <div class="mc-form-info">
                    <span class="mc-form-label">Assign points to roles</span>
                </div>
                <div class="mc-form-control">
                    <select name="mc_pts_specific_roles[]" class="wc-enhanced-select" multiple="multiple" style="width:100%;">
                        <?php 
                        global $wp_roles;
                        $saved_roles = get_option('mc_pts_specific_roles', ['customer']);
                        foreach($wp_roles->get_names() as $role_key => $role_name): ?>
                            <option value="<?php echo esc_attr($role_key); ?>" <?php echo in_array($role_key, (array)$saved_roles) ? 'selected' : ''; ?>><?php echo esc_html($role_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info">
                    <span class="mc-form-label">Default points assigned</span>
                    <span class="mc-form-desc">Set how many points per product will be earned based on the product value.</span>
                </div>
                <div class="mc-form-control">
                    <div class="mc-inline-inputs">
                        <span>For each</span>
                        <input type="number" step="0.01" name="mc_pts_earn_currency" value="<?php echo esc_attr(get_option('mc_pts_earn_currency', '100')); ?>" style="width:80px;">
                        <span>$ spent, assign</span>
                        <input type="number" step="0.01" name="mc_pts_earn_points" value="<?php echo esc_attr(get_option('mc_pts_earn_points', '20')); ?>" style="width:80px;">
                        <span>Points</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mc-form-section" id="mc-calculation-rules">
        <div class="mc-form-row">
            <div class="mc-form-info">
                <span class="mc-form-label">Calculate points considering:</span>
                <span class="mc-form-desc">Choose whether to calculate points considering unit prices or product subtotal.</span>
            </div>
            <div class="mc-form-control mc-radio-group">
                <?php $calc_basis = get_option('mc_pts_calc_basis', 'subtotal'); ?>
                <label><input type="radio" name="mc_pts_calc_basis" value="unit" <?php checked($calc_basis, 'unit'); ?>> Unit price</label>
                <label><input type="radio" name="mc_pts_calc_basis" value="subtotal" <?php checked($calc_basis, 'subtotal'); ?>> Product subtotal</label>
                
                <div style="background:#f1f5f9; padding:15px; border-radius:6px; margin-top:10px; font-size:13px; color:#475569; border-left:3px solid #007cba;">
                    <strong>Example:</strong> A rule gives 1 point for every $10 spent. If a customer adds 10 products priced at $8 each to their cart:<br><br>
                    - Calculating points on the <strong>unit price</strong> won't award any points (since $8 is less than the $10 requirement).<br>
                    - Calculating points on the <strong>product subtotal</strong> will award 8 points (since $80 total / 10).
                </div>
            </div>
        </div>

        <div class="mc-form-row">
            <div class="mc-form-info">
                <span class="mc-form-label">Calculate points considering product price with:</span>
            </div>
            <div class="mc-form-control mc-radio-group">
                <?php $tax_incl = get_option('mc_pts_tax_incl', 'exc'); ?>
                <label><input type="radio" name="mc_pts_tax_incl" value="inc" <?php checked($tax_incl, 'inc'); ?>> Taxes included</label>
                <label><input type="radio" name="mc_pts_tax_incl" value="exc" <?php checked($tax_incl, 'exc'); ?>> Taxes excluded</label>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Assign points to guests if the billing email is registered</span>
                <span class="mc-form-desc">Enable to assign points to guest users if the billing email matches a registered account.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_assign_guests" value="yes" <?php checked(get_option('mc_pts_assign_guests', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Assign points to newly registered users if the billing email is registered</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_assign_new_registered" value="yes" <?php checked(get_option('mc_pts_assign_new_registered', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
    </div>

    <div class="mc-form-section" id="mc-exclusion-rules">
        <h3>Points removal and exclusion</h3>
        <div class="mc-form-row">
            <div class="mc-form-info">
                <span class="mc-form-label">Assign points when the order has status</span>
            </div>
            <div class="mc-form-control">
                <?php $statuses = wc_get_order_statuses(); ?>
                <select name="mc_pts_order_status[]" class="wc-enhanced-select" multiple="multiple" style="width:100%;">
                    <?php $selected_statuses = get_option('mc_pts_order_status', ['wc-completed']); ?>
                    <?php foreach($statuses as $key => $status): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php echo in_array($key, (array)$selected_statuses) ? 'selected' : ''; ?>><?php echo esc_html($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Exclude on-sale products from points collection</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_exclude_sale" value="yes" <?php checked(get_option('mc_pts_exclude_sale', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Remove earned points if order is cancelled</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_remove_cancelled" value="yes" <?php checked(get_option('mc_pts_remove_cancelled', 'yes'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Reassign points when an order is refunded</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_reassign_refunded" value="yes" <?php checked(get_option('mc_pts_reassign_refunded', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Remove earned points if order is refunded</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_remove_refunded" value="yes" <?php checked(get_option('mc_pts_remove_refunded', 'yes'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Do not assign points to the full order amount if a coupon is used</span>
                <span class="mc-form-desc">Users earn points only on the amount minus the coupon discount.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_exclude_coupons" value="yes" <?php checked(get_option('mc_pts_exclude_coupons', 'yes'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Do not assign points to orders in which the user is redeeming points</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_disable_earn_on_redeem" value="yes" <?php checked(get_option('mc_pts_disable_earn_on_redeem', 'yes'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
    </div>

    <div class="mc-form-section">
        <h3>Other options</h3>
        
        <div class="mc-toggle-row" style="margin-bottom: 20px;">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Allow shop manager to manage this plugin</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_allow_shop_manager" value="yes" <?php checked(get_option('mc_pts_allow_shop_manager', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-form-row">
            <div class="mc-form-info">
                <span class="mc-form-label">Points rounding</span>
            </div>
            <div class="mc-form-control mc-radio-group">
                <?php $rounding = get_option('mc_pts_rounding', 'down'); ?>
                <label><input type="radio" name="mc_pts_rounding" value="up" <?php checked($rounding, 'up'); ?>> Round Up</label>
                <label><input type="radio" name="mc_pts_rounding" value="down" <?php checked($rounding, 'down'); ?>> Round Down</label>
            </div>
        </div>

        <div class="mc-toggle-row" style="margin-bottom: 10px;">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Set an expiration time for points</span>
                <span class="mc-form-desc">Points will expire based on the date they were earned.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" id="mc-toggle-expiration" name="mc_pts_expiration_enabled" value="yes" <?php checked(get_option('mc_pts_expiration_enabled', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-form-row" id="mc-expiration-wrap" style="<?php echo get_option('mc_pts_expiration_enabled') === 'yes' ? '' : 'display:none;'; ?>">
            <div class="mc-form-info">
                <span class="mc-form-label">Points will expire after</span>
            </div>
            <div class="mc-form-control">
                <div class="mc-inline-inputs">
                    <input type="number" name="mc_pts_expiration_time" value="<?php echo esc_attr(get_option('mc_pts_expiration_time', '365')); ?>" style="width:80px;">
                    <select name="mc_pts_expiration_type">
                        <?php $exp_type = get_option('mc_pts_expiration_type', 'days'); ?>
                        <option value="days" <?php selected($exp_type, 'days'); ?>>Days</option>
                        <option value="months" <?php selected($exp_type, 'months'); ?>>Months</option>
                        <option value="years" <?php selected($exp_type, 'years'); ?>>Years</option>
                    </select>
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

    $('input[name="mc_pts_assign_type"]').on('change', function() {
        if($(this).val() === 'auto') { $('#mc-auto-assign-wrapper, #mc-calculation-rules, #mc-exclusion-rules').slideDown(); } 
        else { $('#mc-auto-assign-wrapper, #mc-calculation-rules, #mc-exclusion-rules').slideUp(); }
    });
    if($('input[name="mc_pts_assign_type"]:checked').val() === 'manual') { $('#mc-auto-assign-wrapper, #mc-calculation-rules, #mc-exclusion-rules').hide(); }

    $('input[name="mc_pts_assign_roles"]').on('change', function() {
        if($(this).val() === 'specific') { $('#mc-specific-roles-wrapper').slideDown(); } 
        else { $('#mc-specific-roles-wrapper').slideUp(); }
    });
    if($('input[name="mc_pts_assign_roles"]:checked').val() === 'all') { $('#mc-specific-roles-wrapper').hide(); }

    $('#mc-toggle-expiration').on('change', function() {
        if($(this).is(':checked')) { $('#mc-expiration-wrap').hide().slideDown(); } 
        else { $('#mc-expiration-wrap').slideUp(); }
    });
});
</script>