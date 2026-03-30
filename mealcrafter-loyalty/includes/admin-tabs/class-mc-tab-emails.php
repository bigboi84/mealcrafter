<?php
/**
 * MealCrafter: Email Notifications Settings Tab
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Emails {

    public function render() {
        if ( isset($_POST['mc_save_emails']) && isset($_POST['mc_emails_nonce']) && wp_verify_nonce($_POST['mc_emails_nonce'], 'mc_save_em') && current_user_can('manage_options') ) {
            $fields = [
                'mc_email_woo_inject_enable', 'mc_email_woo_inject_body',
                'mc_email_earned_enable', 'mc_email_earned_subject', 'mc_email_earned_body',
                'mc_email_reward_enable', 'mc_email_reward_subject', 'mc_email_reward_body',
                'mc_email_updated_enable', 'mc_email_updated_subject', 'mc_email_updated_body',
                'mc_email_expiring_enable', 'mc_email_expiring_subject', 'mc_email_expiring_body',
                'mc_email_expiring_days_1', 'mc_email_expiring_days_2'
            ];
            foreach ($fields as $field) {
                $val = isset($_POST[$field]) ? ( strpos($field, '_body') !== false ? wp_kses_post(wp_unslash($_POST[$field])) : sanitize_text_field($_POST[$field]) ) : '';
                if ( strpos($field, '_enable') !== false && empty($_POST[$field]) ) $val = 'no';
                update_option($field, $val);
            }
            echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-top:20px; border-radius:4px; font-weight:600;">✅ Email Settings saved successfully!</div>';
        }

        // Woo Inject Defaults
        $woo_inject_enable = get_option('mc_email_woo_inject_enable', 'yes');
        $woo_inject_body = get_option('mc_email_woo_inject_body', "<div style='background:#fef8ee; padding:15px; border:2px dashed #f39c12; text-align:center; font-weight:bold; color:#d35400;'>🎉 You earned {points_earned} points on this order! Your new balance is {total_points} points.</div>");

        // Earned Defaults
        $earned_enable = get_option('mc_email_earned_enable', 'yes');
        $earned_sub = get_option('mc_email_earned_subject', 'You just earned {points_earned} points!');
        $earned_body = get_option('mc_email_earned_body', "Hi {first_name},\n\nGreat news! You just earned {points_earned} MealCrafter points. Your new total balance is {total_points} points.\n\nKeep earning to unlock exclusive rewards!");

        // Reward Unlocked Defaults
        $reward_enable = get_option('mc_email_reward_enable', 'yes');
        $reward_sub = get_option('mc_email_reward_subject', 'Here is your MealCrafter Reward!');
        $reward_body = get_option('mc_email_reward_body', "Hi {first_name},\n\nYou successfully redeemed {points_spent} points for a reward!\n\nYour Coupon Code: {coupon_code}\n\nEnter this code at checkout to claim your reward. Enjoy!");

        // Admin Updated Defaults
        $updated_enable = get_option('mc_email_updated_enable', 'yes');
        $updated_sub = get_option('mc_email_updated_subject', 'Your point balance has been updated');
        $updated_body = get_option('mc_email_updated_body', "Hi {first_name},\n\nYour MealCrafter point balance has been manually adjusted by our team.\n\nAdjustment: {point_difference} points\nReason: {update_reason}\n\nYour new total balance is {total_points} points.");

        // Expiring Defaults
        $expiring_enable = get_option('mc_email_expiring_enable', 'yes');
        $expiring_sub = get_option('mc_email_expiring_subject', 'Action Required: Your points are expiring soon!');
        $expiring_body = get_option('mc_email_expiring_body', "Hi {first_name},\n\nDon't lose your rewards! You have {expiring_points} points that will expire in {days_left} days.\n\nVisit our store today to turn your points into discounts before they disappear!");
        $expiring_days_1 = get_option('mc_email_expiring_days_1', '30');
        $expiring_days_2 = get_option('mc_email_expiring_days_2', '7');

        ?>
        <div class="mc-main-content" style="width: 100%; max-width: 900px; margin-top: 20px;">
            <div style="margin-bottom: 25px;">
                <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;">Automated Email Alerts</h2>
                <p style="margin:0; font-size:13px; color:#646970;">Keep your customers engaged by automatically emailing them about their loyalty account status.</p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('mc_save_em', 'mc_emails_nonce'); ?>
                
                <div class="mc-rule-card" style="padding:25px; border-left:4px solid #9b59b6; margin-bottom: 30px;">
                    <div class="mc-toggle-row" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label" style="font-weight:bold; font-size:16px; color:#8e44ad;">WooCommerce Receipt Injection</span>
                            <span class="mc-form-desc">Automatically add a points summary to the standard WooCommerce "Order Complete" email.</span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_email_woo_inject_enable" value="no">
                            <input type="checkbox" name="mc_email_woo_inject_enable" id="mc_email_woo_inject_toggle" value="yes" <?php checked($woo_inject_enable, 'yes'); ?>>
                            <span class="mc-slider"></span>
                        </label>
                    </div>
                    
                    <div id="mc_email_woo_inject_wrapper" style="<?php echo $woo_inject_enable === 'yes' ? '' : 'display:none;'; ?>">
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Injected HTML Block</span>
                            <textarea name="mc_email_woo_inject_body" rows="3" style="width:100%; padding:8px;"><?php echo esc_textarea($woo_inject_body); ?></textarea>
                            <p style="font-size:12px; color:#666; margin-top:5px;"><strong>Available Tags:</strong> <code>{points_earned}</code>, <code>{total_points}</code></p>
                        </div>

                        <div style="background:#f9f9f9; border:1px solid #ddd; padding:15px; border-radius:6px; margin-top:20px;">
                            <h4 style="margin:0 0 10px 0; color:#222;">🎨 Using a Custom Email Builder? (Shortcodes)</h4>
                            <p style="margin:0 0 10px 0; font-size:13px; color:#555;">If you are using YayMail, Kadence, or another visual email editor, you can turn OFF the auto-injector above and design the layout yourself by pasting these shortcodes directly into your custom email templates:</p>
                            <code style="background:#fff; padding:4px 8px; border:1px solid #ccc; display:inline-block; margin-right:10px;">[mc_points_earned_this_order]</code>
                            <code style="background:#fff; padding:4px 8px; border:1px solid #ccc; display:inline-block;">[mc_points_total_balance]</code>
                        </div>
                    </div>
                </div>

                <div class="mc-rule-card" style="padding:25px; border-left:4px solid #2ecc71; margin-bottom: 30px;">
                    <div class="mc-toggle-row" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label" style="font-weight:bold; font-size:16px; color:#27ae60;">Standalone Points Earned Email</span>
                            <span class="mc-form-desc">Send a separate email when they earn points. <em>(Recommendation: Turn this OFF if you use the WooCommerce Injector above, to avoid double-emailing).</em></span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_email_earned_enable" value="no">
                            <input type="checkbox" name="mc_email_earned_enable" id="mc_email_earned_toggle" value="yes" <?php checked($earned_enable, 'yes'); ?>>
                            <span class="mc-slider"></span>
                        </label>
                    </div>
                    
                    <div id="mc_email_earned_wrapper" style="<?php echo $earned_enable === 'yes' ? '' : 'display:none;'; ?>">
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Subject</span>
                            <input type="text" name="mc_email_earned_subject" value="<?php echo esc_attr($earned_sub); ?>" style="width:100%; padding:8px;">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Message</span>
                            <textarea name="mc_email_earned_body" rows="6" style="width:100%; padding:8px;"><?php echo esc_textarea($earned_body); ?></textarea>
                            <p style="font-size:12px; color:#666; margin-top:5px;"><strong>Available Tags:</strong> <code>{first_name}</code>, <code>{points_earned}</code>, <code>{total_points}</code></p>
                        </div>
                    </div>
                </div>

                <div class="mc-rule-card" style="padding:25px; border-left:4px solid #f39c12; margin-bottom: 30px;">
                    <div class="mc-toggle-row" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label" style="font-weight:bold; font-size:16px; color:#d35400;">Reward Unlocked Email</span>
                            <span class="mc-form-desc">Sent instantly when a user spends their points to unlock a custom coupon.</span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_email_reward_enable" value="no">
                            <input type="checkbox" name="mc_email_reward_enable" id="mc_email_reward_toggle" value="yes" <?php checked($reward_enable, 'yes'); ?>>
                            <span class="mc-slider"></span>
                        </label>
                    </div>
                    
                    <div id="mc_email_reward_wrapper" style="<?php echo $reward_enable === 'yes' ? '' : 'display:none;'; ?>">
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Subject</span>
                            <input type="text" name="mc_email_reward_subject" value="<?php echo esc_attr($reward_sub); ?>" style="width:100%; padding:8px;">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Message</span>
                            <textarea name="mc_email_reward_body" rows="6" style="width:100%; padding:8px;"><?php echo esc_textarea($reward_body); ?></textarea>
                            <p style="font-size:12px; color:#666; margin-top:5px;"><strong>Available Tags:</strong> <code>{first_name}</code>, <code>{points_spent}</code>, <code>{coupon_code}</code>, <code>{total_points}</code></p>
                        </div>
                    </div>
                </div>

                <div class="mc-rule-card" style="padding:25px; border-left:4px solid #3498db; margin-bottom: 30px;">
                    <div class="mc-toggle-row" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label" style="font-weight:bold; font-size:16px; color:#2980b9;">Admin Balance Update Email</span>
                            <span class="mc-form-desc">Sent when an admin manually adds or deducts points from a user's account.</span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_email_updated_enable" value="no">
                            <input type="checkbox" name="mc_email_updated_enable" id="mc_email_updated_toggle" value="yes" <?php checked($updated_enable, 'yes'); ?>>
                            <span class="mc-slider"></span>
                        </label>
                    </div>
                    
                    <div id="mc_email_updated_wrapper" style="<?php echo $updated_enable === 'yes' ? '' : 'display:none;'; ?>">
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Subject</span>
                            <input type="text" name="mc_email_updated_subject" value="<?php echo esc_attr($updated_sub); ?>" style="width:100%; padding:8px;">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Message</span>
                            <textarea name="mc_email_updated_body" rows="6" style="width:100%; padding:8px;"><?php echo esc_textarea($updated_body); ?></textarea>
                            <p style="font-size:12px; color:#666; margin-top:5px;"><strong>Available Tags:</strong> <code>{first_name}</code>, <code>{point_difference}</code> (e.g. +50 or -10), <code>{update_reason}</code>, <code>{total_points}</code></p>
                        </div>
                    </div>
                </div>

                <div class="mc-rule-card" style="padding:25px; border-left:4px solid #e74c3c; margin-bottom: 30px;">
                    <div class="mc-toggle-row" style="margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label" style="font-weight:bold; font-size:16px; color:#c0392b;">Points Expiring Email</span>
                            <span class="mc-form-desc">Sent automatically before a user's points expire to drive them back to the store.</span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_email_expiring_enable" value="no">
                            <input type="checkbox" name="mc_email_expiring_enable" id="mc_email_expiring_toggle" value="yes" <?php checked($expiring_enable, 'yes'); ?>>
                            <span class="mc-slider"></span>
                        </label>
                    </div>

                    <div id="mc_email_expiring_wrapper" style="<?php echo $expiring_enable === 'yes' ? '' : 'display:none;'; ?>">
                        <div style="display:flex; gap:20px; margin-bottom:20px;">
                            <div style="flex:1;">
                                <span style="display:block; font-weight:bold; margin-bottom:5px;">First Warning (Days Before)</span>
                                <input type="number" name="mc_email_expiring_days_1" value="<?php echo esc_attr($expiring_days_1); ?>" style="width:100%; padding:8px;">
                            </div>
                            <div style="flex:1;">
                                <span style="display:block; font-weight:bold; margin-bottom:5px;">Final Warning (Days Before)</span>
                                <input type="number" name="mc_email_expiring_days_2" value="<?php echo esc_attr($expiring_days_2); ?>" style="width:100%; padding:8px;">
                                <span style="font-size:11px; color:#888;">(Leave blank to only send one warning)</span>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Subject</span>
                            <input type="text" name="mc_email_expiring_subject" value="<?php echo esc_attr($expiring_sub); ?>" style="width:100%; padding:8px;">
                        </div>
                        
                        <div style="margin-bottom: 15px;">
                            <span style="display:block; font-weight:bold; margin-bottom:5px;">Email Message</span>
                            <textarea name="mc_email_expiring_body" rows="6" style="width:100%; padding:8px;"><?php echo esc_textarea($expiring_body); ?></textarea>
                            <p style="font-size:12px; color:#666; margin-top:5px;"><strong>Available Tags:</strong> <code>{first_name}</code>, <code>{expiring_points}</code>, <code>{days_left}</code>, <code>{total_points}</code></p>
                        </div>
                    </div>
                </div>

                <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                    <button type="submit" name="mc_save_emails" style="background:#2271b1; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer; font-size:14px;">Save Email Settings</button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Function to handle the slide toggle
            function setupToggle(toggleId, wrapperId) {
                $(toggleId).on('change', function() {
                    if($(this).is(':checked')) {
                        $(wrapperId).slideDown();
                    } else {
                        $(wrapperId).slideUp();
                    }
                });
            }

            // Apply to all 5 sections
            setupToggle('#mc_email_woo_inject_toggle', '#mc_email_woo_inject_wrapper');
            setupToggle('#mc_email_earned_toggle', '#mc_email_earned_wrapper');
            setupToggle('#mc_email_reward_toggle', '#mc_email_reward_wrapper');
            setupToggle('#mc_email_updated_toggle', '#mc_email_updated_wrapper');
            setupToggle('#mc_email_expiring_toggle', '#mc_email_expiring_wrapper');
        });
        </script>
        <?php
    }
}