<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form method="post" action="options.php">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div style="margin-bottom:25px;">
        <p class="description" style="font-size:14px;">Gamify your store by assigning extra points for specific customer actions, milestones, and referrals.</p>
    </div>

    <div class="mc-form-section">
        <h3>Account & Profile Actions</h3>
        
        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">User Registration</span>
                <span class="mc-form-desc">Give a one-time point bonus to every user who registers an account.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-reg-wrap" name="mc_pts_extra_registration" value="yes" <?php checked(get_option('mc_pts_extra_registration', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-reg-wrap" style="<?php echo get_option('mc_pts_extra_registration') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_registration_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_registration_pts', '50')); ?>" style="width:80px;">
                <span style="font-weight:600;">points upon registration</span>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">First Daily Login</span>
                <span class="mc-form-desc">Reward users with points the first time they log in each day.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-login-wrap" name="mc_pts_extra_login" value="yes" <?php checked(get_option('mc_pts_extra_login', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-login-wrap" style="<?php echo get_option('mc_pts_extra_login') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_login_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_login_pts', '5')); ?>" style="width:80px;">
                <span style="font-weight:600;">points daily</span>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Completed Profile</span>
                <span class="mc-form-desc">Incentivize users to fill out all billing/shipping details in their account.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-profile-wrap" name="mc_pts_extra_profile" value="yes" <?php checked(get_option('mc_pts_extra_profile', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-profile-wrap" style="<?php echo get_option('mc_pts_extra_profile') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_profile_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_profile_pts', '20')); ?>" style="width:80px;">
                <span style="font-weight:600;">points when profile is 100% complete</span>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">User's Birthday</span>
                <span class="mc-form-desc">Automatically assign a gift of points on the user's birthday.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-bday-wrap" name="mc_pts_extra_birthday" value="yes" <?php checked(get_option('mc_pts_extra_birthday', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-bday-wrap" style="<?php echo get_option('mc_pts_extra_birthday') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_birthday_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_birthday_pts', '100')); ?>" style="width:80px;">
                <span style="font-weight:600;">points annually</span>
            </div>
        </div>
    </div>

    <div class="mc-form-section">
        <h3>Referral Program</h3>
        
        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Referrals (New User Registration)</span>
                <span class="mc-form-desc">Reward users with points when a friend registers using their referral link.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-ref-wrap" name="mc_pts_extra_referral" value="yes" <?php checked(get_option('mc_pts_extra_referral', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-ref-wrap" style="<?php echo get_option('mc_pts_extra_referral') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0; margin-bottom:15px;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_referral_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_referral_pts', '50')); ?>" style="width:80px;">
                <span style="font-weight:600;">points per successful signup</span>
            </div>
            <div>
                <label><input type="checkbox" name="mc_pts_extra_referral_revoke" value="yes" <?php checked(get_option('mc_pts_extra_referral_revoke', 'yes'), 'yes'); ?>> Revoke points if the referred account is deleted (Fraud Prevention)</label>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Referral Purchases</span>
                <span class="mc-form-desc">Reward users when their referred friend makes their first purchase.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-ref-pur-wrap" name="mc_pts_extra_ref_purchase" value="yes" <?php checked(get_option('mc_pts_extra_ref_purchase', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-ref-pur-wrap" style="<?php echo get_option('mc_pts_extra_ref_purchase') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_ref_purchase_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_ref_purchase_pts', '100')); ?>" style="width:80px;">
                <span style="font-weight:600;">points per first purchase</span>
            </div>
        </div>
    </div>

    <div class="mc-form-section">
        <h3>Engagement & Milestones</h3>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Product Reviews</span>
                <span class="mc-form-desc">Assign points when a user leaves a review on a product they purchased.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-rev-wrap" name="mc_pts_extra_reviews" value="yes" <?php checked(get_option('mc_pts_extra_reviews', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-rev-wrap" style="<?php echo get_option('mc_pts_extra_reviews') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_reviews_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_reviews_pts', '10')); ?>" style="width:80px;">
                <span style="font-weight:600;">points per approved review</span>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Order Milestones</span>
                <span class="mc-form-desc">Assign extra points when a user places an order.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-ord-wrap" name="mc_pts_extra_orders" value="yes" <?php checked(get_option('mc_pts_extra_orders', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-ord-wrap" style="<?php echo get_option('mc_pts_extra_orders') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0; margin-bottom:15px;">
                <span style="font-weight:600;">Assign</span>
                <input type="number" name="mc_pts_extra_orders_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_orders_pts', '25')); ?>" style="width:80px;">
                <span style="font-weight:600;">points on order completion</span>
            </div>
            <div>
                <label><input type="checkbox" name="mc_pts_extra_orders_repeat" value="yes" <?php checked(get_option('mc_pts_extra_orders_repeat', 'yes'), 'yes'); ?>> Repeat for every order (uncheck to only reward the first order)</label>
            </div>
        </div>

        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Cart Total Threshold</span>
                <span class="mc-form-desc">Incentivize larger checkouts by offering bonus points if the cart hits a specific value.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-extra-cart-wrap" name="mc_pts_extra_cart" value="yes" <?php checked(get_option('mc_pts_extra_cart', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
        <div class="mc-form-row" id="mc-extra-cart-wrap" style="<?php echo get_option('mc_pts_extra_cart') === 'yes' ? '' : 'display:none;'; ?> padding:15px; background:#f9f9f9; border-radius:6px; margin-top:10px;">
            <div class="mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">If cart is over $</span>
                <input type="number" step="0.01" name="mc_pts_extra_cart_threshold" value="<?php echo esc_attr(get_option('mc_pts_extra_cart_threshold', '100')); ?>" style="width:80px;">
                <span style="font-weight:600;">, assign</span>
                <input type="number" name="mc_pts_extra_cart_pts" value="<?php echo esc_attr(get_option('mc_pts_extra_cart_pts', '50')); ?>" style="width:80px;">
                <span style="font-weight:600;">bonus points</span>
            </div>
        </div>

    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Extra Points', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    // Elegant slide toggle for revealing inputs when switches are turned on
    $(document).on('change', '.mc-reveal-toggle', function() {
        let target = $(this).data('target');
        if($(this).is(':checked')) {
            $(target).hide().slideDown(250);
        } else {
            $(target).slideUp(250);
        }
    });
});
</script>