<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form method="post" action="options.php">
    <?php settings_fields( 'mc_prod_general_group' ); ?>
    
    <div style="margin-bottom:25px;">
        <p class="description" style="font-size:14px;">Configure the global rules for allowing customers to trade points for specific free items instead of just a generic cash discount.</p>
    </div>

    <div class="mc-form-section">
        <h3>Core Product Redemption</h3>
        
        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label" style="color:#d63638;">Enable Product-Level Redemption</span>
                <span class="mc-form-desc">Turn this on to allow customers to buy specific items with points inline at checkout.</span>
            </div>
            <label class="mc-toggle-switch">
                <input type="checkbox" class="mc-reveal-toggle" data-target="#mc-prod-general-wrap" name="mc_pts_prod_enable" value="yes" <?php checked(get_option('mc_pts_prod_enable', 'no'), 'yes'); ?>>
                <span class="mc-slider"></span>
            </label>
        </div>

        <div id="mc-prod-general-wrap" style="<?php echo get_option('mc_pts_prod_enable') === 'yes' ? '' : 'display:none;'; ?>">
            
            <div class="mc-form-row" style="margin-top:20px;">
                <div class="mc-form-info">
                    <span class="mc-form-label">Maximum Redemptions Per Cart</span>
                    <span class="mc-form-desc">Limit how many free items a customer can claim in a single transaction (Leave blank for unlimited).</span>
                </div>
                <div class="mc-form-control">
                    <input type="number" name="mc_pts_prod_max_per_cart" value="<?php echo esc_attr(get_option('mc_pts_prod_max_per_cart', '1')); ?>" style="width:100px;">
                    <span style="font-weight:600; color:#555; margin-left:10px;">Items</span>
                </div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info">
                    <span class="mc-form-label">Minimum Cart Total Required</span>
                    <span class="mc-form-desc">Require the customer to spend a certain amount of cash before they can claim a free item.</span>
                </div>
                <div class="mc-form-control">
                    <span style="font-weight:600; color:#555; margin-right:10px;">$</span>
                    <input type="number" step="0.01" name="mc_pts_prod_min_cart_total" value="<?php echo esc_attr(get_option('mc_pts_prod_min_cart_total', '0.00')); ?>" style="width:100px;">
                </div>
            </div>

            <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">

            <div class="mc-toggle-row" style="border-bottom:none;">
                <div class="mc-form-info" style="margin:0;">
                    <span class="mc-form-label" style="color:#2271b1;">Limit Redemption to Base Price Only</span>
                    <span class="mc-form-desc">
                        <strong>Crucial for Variable / Combo / Grouped products.</strong><br> 
                        If enabled, the points only cover the base price of the item. The customer must pay cash for any premium add-ons (e.g., extra cheese, upgrading to premium sides). If disabled, the entire configured item becomes free.
                    </span>
                </div>
                <label class="mc-toggle-switch">
                    <input type="checkbox" name="mc_pts_prod_base_price_only" value="yes" <?php checked(get_option('mc_pts_prod_base_price_only', 'yes'), 'yes'); ?>>
                    <span class="mc-slider"></span>
                </label>
            </div>

            <div class="mc-toggle-row">
                <div class="mc-form-info" style="margin:0;">
                    <span class="mc-form-label">Customer Must Pay Taxes</span>
                    <span class="mc-form-desc">If enabled, the 100% discount applies to the subtotal, but standard taxes are still calculated and charged at checkout.</span>
                </div>
                <label class="mc-toggle-switch">
                    <input type="checkbox" name="mc_pts_prod_tax_override" value="yes" <?php checked(get_option('mc_pts_prod_tax_override', 'yes'), 'yes'); ?>>
                    <span class="mc-slider"></span>
                </label>
            </div>

        </div>
    </div>

    <div class="mc-form-section" id="mc-prod-audience-wrap" style="<?php echo get_option('mc_pts_prod_enable') === 'yes' ? '' : 'display:none;'; ?>">
        <h3>Target Audience</h3>
        
        <div class="mc-form-row">
            <div class="mc-form-info"><span class="mc-form-label">Who can use Product-Level redemptions?</span></div>
            <div class="mc-form-control mc-radio-group">
                <?php $tgt_user = get_option('mc_pts_prod_target_users', 'all'); ?>
                <label style="display:block; margin-bottom:8px;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_prod_target_users" value="all" <?php checked($tgt_user, 'all'); ?>> All users</label>
                <label style="display:block; margin-bottom:8px;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_prod_target_users" value="roles" <?php checked($tgt_user, 'roles'); ?>> Specific user roles</label>
                <label style="display:block;"><input type="radio" class="mc-user-target-toggle" name="mc_pts_prod_target_users" value="levels" <?php checked($tgt_user, 'levels'); ?>> Users with a specific points level</label>
            </div>
            
            <div class="mc-user-target-roles mc-user-wrap" style="<?php echo $tgt_user === 'roles' ? 'display:block;' : 'display:none;'; ?> margin-top:10px;">
                <?php 
                global $wp_roles;
                $saved_roles = get_option('mc_pts_prod_target_roles', []);
                if(!is_array($saved_roles)) $saved_roles = [];
                echo '<select name="mc_pts_prod_target_roles[]" class="mc-select2" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Select user roles...">';
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
                $saved_levels = get_option('mc_pts_prod_target_levels', []);
                if(!is_array($saved_levels)) $saved_levels = [];
                echo '<select name="mc_pts_prod_target_levels[]" class="mc-select2" multiple="multiple" style="width:100%; max-width:400px;" data-placeholder="Select levels...">';
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

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save General Settings', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    // Initialize Select2
    if($.fn.select2) {
        $('.mc-select2').select2({ allowClear: true });
    }

    // Toggle Master Container
    $(document).on('change', '.mc-reveal-toggle', function() {
        let target = $(this).data('target');
        if($(this).is(':checked')) { 
            $(target).hide().slideDown(250); 
            $('#mc-prod-audience-wrap').hide().slideDown(250); 
        } 
        else { 
            $(target).slideUp(250); 
            $('#mc-prod-audience-wrap').slideUp(250);
        }
    });

    // Toggle Target Users (All vs Roles vs Levels)
    $(document).on('change', '.mc-user-target-toggle', function() {
        $('.mc-user-wrap').hide();
        let val = $(this).val();
        if(val !== 'all') { $('.mc-user-target-' + val).show(); }
    });
});
</script>