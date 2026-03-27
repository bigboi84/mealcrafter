<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="mc-main-content" style="width: 100%; max-width: 900px;">
    <div style="margin-bottom: 25px;">
        <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;">Product Level General Options</h2>
        <p style="margin:0; font-size:13px; color:#646970;">Manage global logic and UI features when users redeem points directly for specific products.</p>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields( 'mc_prod_general_group' ); ?>
        
        <div class="mc-form-section mc-rule-card" style="padding:25px; border-left:4px solid #f39c12; margin-bottom: 30px;">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Redemption Mechanics</h3>
            
            <div class="mc-toggle-row" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                <div class="mc-form-info" style="margin:0; max-width: 80%;">
                    <span class="mc-form-label" style="font-weight:600;">Enable Product Points Redemption</span>
                    <span class="mc-form-desc" style="display:block; margin-top:4px; font-size:12px; color:#666;">Allow users to redeem points directly for specific products.</span>
                </div>
                <label class="mc-toggle-switch">
                    <input type="checkbox" name="mc_pts_prod_enable" value="yes" <?php checked(get_option('mc_pts_prod_enable', 'no'), 'yes'); ?>>
                    <span class="mc-slider"></span>
                </label>
            </div>

            <div class="mc-toggle-row" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                <div class="mc-form-info" style="margin:0; max-width: 80%;">
                    <span class="mc-form-label" style="font-weight:600;">Deduct from Base Price Only</span>
                    <span class="mc-form-desc" style="display:block; margin-top:4px; font-size:12px; color:#666;">If checked, points will only cover the base price, and the customer pays for premium upgrades (like Combo Extras).</span>
                </div>
                <label class="mc-toggle-switch">
                    <input type="checkbox" name="mc_pts_prod_base_price_only" value="yes" <?php checked(get_option('mc_pts_prod_base_price_only', 'yes'), 'yes'); ?>>
                    <span class="mc-slider"></span>
                </label>
            </div>

            <div class="mc-form-row" style="margin-bottom: 20px;">
                <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Max Redemptions Per Cart</span>
                <input type="number" name="mc_pts_prod_max_per_cart" value="<?php echo esc_attr(get_option('mc_pts_prod_max_per_cart', '1')); ?>" style="width:100px; padding: 6px;">
            </div>
        </div>

        <div class="mc-form-section mc-rule-card" style="padding:25px; border-left:4px solid #8e44ad; margin-bottom: 30px;">
            <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Product Redemption Modal Design</h3>
            <p style="color:#666; font-size:13px; margin-bottom:20px;">Customize the confirmation popup that appears when a user clicks the "Redeem" button on a specific catalog item.</p>

            <div class="mc-toggle-row" style="margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
                <div class="mc-form-info" style="margin:0; max-width: 80%;">
                    <span class="mc-form-label" style="font-weight:600;">Enable Product Redemption Modal</span>
                    <span class="mc-form-desc" style="display:block; margin-top:4px; font-size:12px; color:#666;">If disabled, clicking the redeem button adds the item to cart immediately without asking for confirmation.</span>
                </div>
                <label class="mc-toggle-switch">
                    <input type="checkbox" name="mc_pts_pop_enable" value="yes" <?php checked(get_option('mc_pts_pop_enable', 'yes'), 'yes'); ?>>
                    <span class="mc-slider"></span>
                </label>
            </div>

            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                <div style="flex:1;">
                    <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Modal Title</span>
                    <input type="text" name="mc_pts_pop_title" value="<?php echo esc_attr(get_option('mc_pts_pop_title', 'Unlock this Reward?')); ?>" style="width:100%; font-weight:bold; padding: 6px;">
                </div>
                <div style="flex:2;">
                    <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Confirmation Description</span>
                    <input type="text" name="mc_pts_pop_desc" value="<?php echo esc_attr(get_option('mc_pts_pop_desc', 'Are you sure you want to spend {points} points to get this item for free?')); ?>" style="width:100%; padding: 6px;">
                    <span class="mc-form-desc" style="color:#2271b1; font-size:12px; margin-top: 5px; display: block;">Variables: <code>{points}</code>, <code>{product}</code></span>
                </div>
            </div>

            <div class="mc-form-row" style="display:flex; gap:20px; margin-bottom:20px;">
                <div style="flex:1;">
                    <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Confirm Button Text</span>
                    <input type="text" name="mc_pts_pop_btn_yes" value="<?php echo esc_attr(get_option('mc_pts_pop_btn_yes', 'Yes, Unlock It!')); ?>" style="width:100%; padding: 6px;">
                </div>
                <div style="flex:1;">
                    <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Cancel Button Text</span>
                    <input type="text" name="mc_pts_pop_btn_no" value="<?php echo esc_attr(get_option('mc_pts_pop_btn_no', 'Not right now')); ?>" style="width:100%; padding: 6px;">
                </div>
            </div>

            <div class="mc-form-row" style="margin-bottom:20px;">
                <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Success Message</span>
                <input type="text" name="mc_pts_pop_success" value="<?php echo esc_attr(get_option('mc_pts_pop_success', 'Reward successfully added to your cart!')); ?>" style="width:100%; border-color:#2ecc71; padding: 6px;">
            </div>

            <h4 style="margin:30px 0 15px 0;">Modal Colors</h4>
            <div class="mc-form-row" style="display:flex; gap:40px; background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #eee;">
                <div>
                    <span class="mc-form-label" style="display: block; margin-bottom: 5px;">Background</span>
                    <input type="color" name="mc_pts_pop_bg" value="<?php echo esc_attr(get_option('mc_pts_pop_bg', '#ffffff')); ?>" style="height: 35px; width: 60px; padding: 0; cursor: pointer; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <span class="mc-form-label" style="display: block; margin-bottom: 5px;">Text Color</span>
                    <input type="color" name="mc_pts_pop_text_color" value="<?php echo esc_attr(get_option('mc_pts_pop_text_color', '#111111')); ?>" style="height: 35px; width: 60px; padding: 0; cursor: pointer; border: 1px solid #ccc; border-radius: 4px;">
                </div>
                <div>
                    <span class="mc-form-label" style="display: block; margin-bottom: 5px;">Button Color</span>
                    <input type="color" name="mc_pts_pop_btn_color" value="<?php echo esc_attr(get_option('mc_pts_pop_btn_color', '#2ecc71')); ?>" style="height: 35px; width: 60px; padding: 0; cursor: pointer; border: 1px solid #ccc; border-radius: 4px;">
                </div>
            </div>
        </div>

        <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
            <?php submit_button('Save Product Settings', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
        </p>
    </form>
</div>