<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form method="post" action="options.php">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div style="margin-bottom:25px;">
        <p class="description" style="font-size:14px;">Set how to handle the redemption of points collected by customers.</p>
    </div>

    <div class="mc-form-section">
        <h3>Points redeeming</h3>
        
        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Allow users to redeem points</span>
                <span class="mc-form-desc">Choose if users can redeem points automatically or if you want to manage points redeeming manually.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-redeem-core-wrap" name="mc_pts_allow_redeem" value="yes" <?php checked(get_option('mc_pts_allow_redeem', 'yes'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div id="mc-redeem-core-wrap" style="<?php echo get_option('mc_pts_allow_redeem', 'yes') === 'yes' ? '' : 'display:none;'; ?>">
            
            <div class="mc-form-row" style="margin-top:20px;">
                <div class="mc-form-info"><span class="mc-form-label">User that can redeem points</span></div>
                <div class="mc-form-control mc-radio-group">
                    <?php $user_type = get_option('mc_pts_redeem_user_type', 'all'); ?>
                    <label style="display:block; margin-bottom:8px;"><input type="radio" class="mc-reveal-radio" data-target="#mc-redeem-roles-wrap" data-show-value="specific" name="mc_pts_redeem_user_type" value="all" <?php checked($user_type, 'all'); ?>> All</label>
                    <label style="display:block;"><input type="radio" class="mc-reveal-radio" data-target="#mc-redeem-roles-wrap" data-show-value="specific" name="mc_pts_redeem_user_type" value="specific" <?php checked($user_type, 'specific'); ?>> Only specified user roles</label>
                </div>
            </div>

            <div class="mc-form-row" id="mc-redeem-roles-wrap" style="<?php echo $user_type === 'specific' ? '' : 'display:none;'; ?>">
                <div class="mc-form-info"><span class="mc-form-label">User roles</span><span class="mc-form-desc">Choose which user roles can redeem points.</span></div>
                <div class="mc-form-control">
                    <?php
                    $saved_roles = get_option('mc_pts_redeem_specific_roles', []);
                    if(!is_array($saved_roles)) $saved_roles = [];
                    global $wp_roles;
                    echo '<select name="mc_pts_redeem_specific_roles[]" class="mc-select2" multiple="multiple" style="width:100%; max-width:400px;">';
                    foreach ( $wp_roles->roles as $key => $role ) {
                        $selected = in_array($key, $saved_roles) ? 'selected' : '';
                        echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($role['name']) . '</option>';
                    }
                    echo '</select>';
                    ?>
                </div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Reward conversion method</span><span class="mc-form-desc">Choose how to apply the discount. The discount can either be a percent or a fixed amount.</span></div>
                <div class="mc-form-control mc-radio-group">
                    <?php $conv_method = get_option('mc_pts_redeem_method', 'fixed'); ?>
                    <label style="display:block; margin-bottom:8px;"><input type="radio" name="mc_pts_redeem_method" value="fixed" <?php checked($conv_method, 'fixed'); ?>> Fixed Price Discount</label>
                    <label style="display:block;"><input type="radio" name="mc_pts_redeem_method" value="percentage" <?php checked($conv_method, 'percentage'); ?>> Percentage Discount</label>
                </div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Reward conversion rate</span><span class="mc-form-desc">Choose how to calculate the discount when customers use their available points.</span></div>
                <div class="mc-form-control">
                    <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                        <input type="number" name="mc_pts_redeem_points_ratio" value="<?php echo esc_attr(get_option('mc_pts_redeem_points_ratio', '100')); ?>" style="width:80px;">
                        <span style="font-weight:600; margin:0 10px;">Points = </span>
                        <input type="number" step="0.01" name="mc_pts_redeem_currency_ratio" value="<?php echo esc_attr(get_option('mc_pts_redeem_currency_ratio', '1')); ?>" style="width:80px;">
                        <span style="font-weight:600; margin-left:10px;">$ discount / %</span>
                    </div>
                </div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">When redeeming, calculate the discount on the product price with:</span><span class="mc-form-desc">Choose whether to calculate the redeeming discount on prices with or without taxes.</span></div>
                <div class="mc-form-control mc-radio-group">
                    <?php $tax_calc = get_option('mc_pts_redeem_tax_calc', 'included'); ?>
                    <label style="display:block; margin-bottom:8px;"><input type="radio" name="mc_pts_redeem_tax_calc" value="included" <?php checked($tax_calc, 'included'); ?>> taxes included</label>
                    <label style="display:block;"><input type="radio" name="mc_pts_redeem_tax_calc" value="excluded" <?php checked($tax_calc, 'excluded'); ?>> taxes excluded</label>
                </div>
            </div>

            <div class="mc-toggle-row" style="border-bottom:none;">
                <div class="mc-form-info" style="margin:0;">
                    <span class="mc-form-label">Exclude on-sale products from the discount amount calculation</span>
                    <span class="mc-form-desc">If enabled, sale products will not be used to redeem points.</span>
                </div>
                <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_redeem_exclude_sale" value="yes" <?php checked(get_option('mc_pts_redeem_exclude_sale', 'yes'), 'yes'); ?>><span class="mc-slider"></span></label>
            </div>

            <div class="mc-toggle-row" style="border-top:1px solid #eee; padding-top:15px; margin-top:5px; border-bottom:none;">
                <div class="mc-form-info" style="margin:0;">
                    <span class="mc-form-label" style="color:#2271b1;">Exclude items assigned a Product-Level Redemption Cost</span>
                    <span class="mc-form-desc">If enabled, items configured for "Free Product Redemption" will be completely excluded from this Global Cash Discount math. This prevents "Double Dipping."</span>
                </div>
                <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_redeem_exclude_product_level" value="yes" <?php checked(get_option('mc_pts_redeem_exclude_product_level', 'yes'), 'yes'); ?>><span class="mc-slider" style="background-color:#cbd5e1;"></span></label>
            </div>
            
            <hr style="margin:20px 0; border:0; border-bottom:1px solid #eee;">

            <div class="mc-toggle-row" style="border-bottom:none;">
                <div class="mc-form-info" style="margin:0;">
                    <span class="mc-form-label">Automatically redeem points on Cart and Checkout pages</span>
                    <span class="mc-form-desc">Enable to automatically apply points on the Cart and Checkout pages.</span>
                </div>
                <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_redeem_auto_apply" value="yes" <?php checked(get_option('mc_pts_redeem_auto_apply', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
            </div>

            <div class="mc-form-row" style="margin-top:20px;">
                <div class="mc-form-info"><span class="mc-form-label">Redeem box style</span><span class="mc-form-desc">Choose the style for the redeem points section.</span></div>
                <div class="mc-form-control mc-radio-group">
                    <?php $box_style = get_option('mc_pts_redeem_box_style', 'custom'); ?>
                    <label style="display:block; margin-bottom:8px;"><input type="radio" name="mc_pts_redeem_box_style" value="default" <?php checked($box_style, 'default'); ?>> Default</label>
                    <label style="display:block;"><input type="radio" name="mc_pts_redeem_box_style" value="custom" <?php checked($box_style, 'custom'); ?>> Custom</label>
                </div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info">
                    <span class="mc-form-label">Redeem message in Cart and Checkout</span>
                    <span class="mc-form-desc">Use placeholders: <strong>{points}</strong>, <strong>{points_label}</strong>, <strong>{max_discount}</strong>.</span>
                </div>
                <div class="mc-form-control">
                    <?php 
                    $message = get_option('mc_pts_redeem_message', 'Use {points} {points_label} for a {max_discount} discount on this order!');
                    wp_editor($message, 'mc_pts_redeem_message', ['textarea_name' => 'mc_pts_redeem_message', 'media_buttons' => false, 'textarea_rows' => 4]); 
                    ?>
                </div>
            </div>

            <div class="mc-toggle-row" style="border-bottom:none;">
                <div class="mc-form-info" style="margin:0;">
                    <span class="mc-form-label">Offer free shipping when users redeem points</span>
                    <span class="mc-form-desc">Enable to offer free shipping to users that redeem their points.</span>
                </div>
                <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_redeem_free_shipping" value="yes" <?php checked(get_option('mc_pts_redeem_free_shipping', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
            </div>
            
            <div class="mc-form-row" style="margin-top:20px;">
                <div class="mc-form-info"><span class="mc-form-label">Coupons allowed</span><span class="mc-form-desc">Select if you want to allow the use of point-redemption coupons, WooCommerce coupons or both.</span></div>
                <div class="mc-form-control mc-radio-group">
                    <?php $coupon_allowed = get_option('mc_pts_redeem_coupon_type', 'woo_only'); ?>
                    <label style="display:block; margin-bottom:8px;"><input type="radio" name="mc_pts_redeem_coupon_type" value="woo_only" <?php checked($coupon_allowed, 'woo_only'); ?>> Use only WooCommerce coupons</label>
                    <label style="display:block; margin-bottom:8px;"><input type="radio" name="mc_pts_redeem_coupon_type" value="points_only" <?php checked($coupon_allowed, 'points_only'); ?>> Use only points-redemption coupons</label>
                    <label style="display:block;"><input type="radio" name="mc_pts_redeem_coupon_type" value="both" <?php checked($coupon_allowed, 'both'); ?>> Use both coupons</label>
                </div>
            </div>

        </div>
    </div>

    <div class="mc-form-section">
        <h3>Redeeming restrictions</h3>
        
        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Apply redeeming restrictions</span>
                <span class="mc-form-desc">Enable to set up redeeming restrictions for your users based on cart totals.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-redeem-restrictions-wrap" name="mc_pts_redeem_restrictions_enable" value="yes" <?php checked(get_option('mc_pts_redeem_restrictions_enable', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div id="mc-redeem-restrictions-wrap" style="<?php echo get_option('mc_pts_redeem_restrictions_enable') === 'yes' ? '' : 'display:none;'; ?> background:#fcfcfc; padding:20px; border-radius:6px; border:1px solid #eee; margin-top:10px;">
            <div class="mc-form-row">
                <span class="mc-form-label">Maximum discount users can get</span>
                <span class="mc-form-desc" style="margin-bottom:8px;">Set the maximum discount amount that your users can get when they redeem points.</span>
                <input type="number" step="0.01" name="mc_pts_redeem_max_discount" value="<?php echo esc_attr(get_option('mc_pts_redeem_max_discount', '')); ?>" style="width:120px;"> <strong>$</strong>
            </div>

            <div class="mc-form-row">
                <span class="mc-form-label">Minimum cart amount to redeem points</span>
                <span class="mc-form-desc" style="margin-bottom:8px;">Set the minimum cart amount required to redeem points.</span>
                <input type="number" step="0.01" name="mc_pts_redeem_min_cart" value="<?php echo esc_attr(get_option('mc_pts_redeem_min_cart', '')); ?>" style="width:120px;"> <strong>$</strong>
            </div>

            <div class="mc-form-row" style="margin-bottom:0;">
                <span class="mc-form-label">Minimum discount required to redeem</span>
                <span class="mc-form-desc" style="margin-bottom:8px;">Set the minimum discount amount a user must reach before the redemption box appears.</span>
                <input type="number" step="0.01" name="mc_pts_redeem_min_discount" value="<?php echo esc_attr(get_option('mc_pts_redeem_min_discount', '')); ?>" style="width:120px;"> <strong>$</strong>
            </div>
        </div>

        <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Allow users to convert points into a coupon to share</span>
                <span class="mc-form-desc">Enable to allow customers to convert their points into a WooCommerce coupon code on their My Account page.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_allow_coupon_generation" value="yes" <?php checked(get_option('mc_pts_allow_coupon_generation', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Apply limits to points coupons</span>
                <span class="mc-form-desc">Enable to set a minimum or maximum of points that can be converted into a coupon code.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-coupon-limits-wrap" name="mc_pts_coupon_limits_enable" value="yes" <?php checked(get_option('mc_pts_coupon_limits_enable', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-coupon-limits-wrap" style="<?php echo get_option('mc_pts_coupon_limits_enable') === 'yes' ? '' : 'display:none;'; ?> background:#f9f9f9; padding:15px; border-radius:6px; margin-top:5px; border:1px solid #eee;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600; color:#555;">Min Points:</span>
                <input type="number" name="mc_pts_coupon_min" value="<?php echo esc_attr(get_option('mc_pts_coupon_min', '100')); ?>" style="width:80px;">
                <span style="font-weight:600; color:#555; margin-left:20px;">Max Points:</span>
                <input type="number" name="mc_pts_coupon_max" value="<?php echo esc_attr(get_option('mc_pts_coupon_max', '10000')); ?>" style="width:80px;">
            </div>
        </div>

        <div class="mc-toggle-row" style="border-bottom:none;">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Set the expiration for points coupon codes</span>
                <span class="mc-form-desc">Enable to set an expiry date for points coupon codes to be used.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-coupon-expiry-wrap" name="mc_pts_coupon_expiry_enable" value="yes" <?php checked(get_option('mc_pts_coupon_expiry_enable', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-coupon-expiry-wrap" style="<?php echo get_option('mc_pts_coupon_expiry_enable') === 'yes' ? '' : 'display:none;'; ?> background:#f9f9f9; padding:15px; border-radius:6px; margin-top:5px; border:1px solid #eee;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600; color:#555;">Coupons expire after:</span>
                <input type="number" name="mc_pts_coupon_expiry_days" value="<?php echo esc_attr(get_option('mc_pts_coupon_expiry_days', '30')); ?>" style="width:80px;">
                <span style="font-weight:600; color:#555;">days</span>
            </div>
        </div>

    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Options', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    // Initialize Select2
    if($.fn.select2) {
        $('.mc-select2').select2({ placeholder: "Select user roles...", allowClear: true });
    }

    // Toggle Checkboxes
    $(document).on('change', '.mc-reveal-toggle', function() {
        let target = $(this).data('target');
        if($(this).is(':checked')) { $(target).hide().slideDown(250); } 
        else { $(target).slideUp(250); }
    });

    // Toggle Radio Buttons (For "Specific Roles" logic)
    $(document).on('change', '.mc-reveal-radio', function() {
        let target = $(this).data('target');
        let showValue = $(this).data('show-value');
        if($(this).val() === showValue) { $(target).hide().slideDown(250); } 
        else { $(target).slideUp(250); }
    });
});
</script>