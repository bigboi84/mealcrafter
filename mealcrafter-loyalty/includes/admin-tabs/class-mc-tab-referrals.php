<?php
/**
 * MealCrafter: Referral Program Settings Tab
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Referrals {

    public function render() {
        if ( isset($_POST['mc_save_referrals']) && isset($_POST['mc_referral_nonce']) && wp_verify_nonce($_POST['mc_referral_nonce'], 'mc_save_ref') && current_user_can('manage_options') ) {
            $fields = [
                'mc_ref_enable', 'mc_ref_prefix', 'mc_ref_referrer_pts', 'mc_ref_referee_pts', 
                'mc_ref_fraud_ip', 'mc_ref_require_approval', 'mc_ref_admin_email',
                'mc_ref_custom_role_enable', 'mc_ref_custom_role_name'
            ];
            foreach ($fields as $field) {
                $val = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : 'no';
                update_option($field, $val);
            }
            
            // Force role creation instantly upon saving if enabled
            if ( get_option('mc_ref_custom_role_enable', 'no') === 'yes' ) {
                $role_name = get_option('mc_ref_custom_role_name', '');
                if ( !empty($role_name) ) {
                    $role_slug = sanitize_title($role_name);
                    if ( ! wp_roles()->is_role( $role_slug ) ) {
                        $customer_role = get_role( 'customer' );
                        $caps = $customer_role ? $customer_role->capabilities : [];
                        add_role( $role_slug, sanitize_text_field($role_name), $caps );
                    }
                }
            }

            echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-top:20px; border-radius:4px; font-weight:600;">✅ Referral Settings saved successfully!</div>';
        }

        $enable = get_option('mc_ref_enable', 'no');
        $prefix = get_option('mc_ref_prefix', 'MC'); // The new prefix variable!
        $referrer_pts = get_option('mc_ref_referrer_pts', '50');
        $referee_pts = get_option('mc_ref_referee_pts', '50');
        $fraud = get_option('mc_ref_fraud_ip', 'yes');
        $approval = get_option('mc_ref_require_approval', 'no');
        $email = get_option('mc_ref_admin_email', get_option('admin_email'));
        $custom_role_enable = get_option('mc_ref_custom_role_enable', 'no');
        $custom_role_name = get_option('mc_ref_custom_role_name', '');
        ?>
        
        <div class="mc-main-content" style="width: 100%; max-width: 900px; margin-top: 20px;">
            <div style="margin-bottom: 25px;">
                <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;">Referral Program</h2>
                <p style="margin:0; font-size:13px; color:#646970;">Turn your loyal customers into ambassadors. Generate codes, track referrals, and award double-sided bonuses.</p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('mc_save_ref', 'mc_referral_nonce'); ?>
                
                <div class="mc-rule-card" style="padding:25px; border-left:4px solid #9b59b6; margin-bottom: 30px;">
                    <div class="mc-toggle-row">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label" style="font-weight:bold; font-size:16px;">Enable Referral Program</span>
                            <span class="mc-form-desc">Activates the "Refer & Earn" tab in My Account and begins tracking referral codes.</span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_ref_enable" value="no">
                            <input type="checkbox" name="mc_ref_enable" value="yes" <?php checked($enable, 'yes'); ?> id="mc_ref_enable_toggle">
                            <span class="mc-slider"></span>
                        </label>
                    </div>
                </div>

                <div id="mc_ref_settings_wrapper" style="<?php echo $enable === 'yes' ? '' : 'display:none;'; ?>">
                    
                    <div class="mc-rule-card" style="padding:25px; border-left:4px solid #2ecc71; margin-bottom: 30px;">
                        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Reward Settings</h3>
                        
                        <div class="mc-form-row" style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px dashed #eee;">
                            <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Referral Code Prefix</span>
                            <span class="mc-form-desc" style="display: block; margin-bottom: 8px; font-size: 12px; color: #666;">Customize the text that appears before the random code (e.g., if you type "BURGER", codes will look like BURGER-X82B9).</span>
                            <input type="text" name="mc_ref_prefix" value="<?php echo esc_attr($prefix); ?>" style="width:100%; max-width: 300px; padding: 8px; font-weight:bold; text-transform: uppercase;">
                        </div>

                        <div class="mc-form-row" style="display:flex; gap:30px; margin-bottom:20px;">
                            <div style="flex:1;">
                                <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">The Referrer Bonus (The Ambassador)</span>
                                <span class="mc-form-desc" style="display: block; margin-bottom: 8px; font-size: 12px; color: #666;">Points awarded to the referrer when their friend completes their FIRST purchase.</span>
                                <input type="number" name="mc_ref_referrer_pts" value="<?php echo esc_attr($referrer_pts); ?>" style="width:100%; padding: 8px; font-weight:bold;">
                            </div>
                            <div style="flex:1;">
                                <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">The Friend Bonus (The Referee)</span>
                                <span class="mc-form-desc" style="display: block; margin-bottom: 8px; font-size: 12px; color: #666;">Points awarded to the NEW user instantly upon registration using a referral code.</span>
                                <input type="number" name="mc_ref_referee_pts" value="<?php echo esc_attr($referee_pts); ?>" style="width:100%; padding: 8px; font-weight:bold;">
                            </div>
                        </div>
                    </div>

                    <div class="mc-rule-card" style="padding:25px; border-left:4px solid #e74c3c; margin-bottom: 30px;">
                        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Security & Access</h3>
                        
                        <div class="mc-toggle-row" style="margin-bottom:20px;">
                            <div class="mc-form-info" style="margin:0;">
                                <span class="mc-form-label">Enable IP Address Fraud Prevention</span>
                                <span class="mc-form-desc">Blocks points if the new user signing up shares the exact same IP address as the referrer.</span>
                            </div>
                            <label class="mc-toggle-switch">
                                <input type="hidden" name="mc_ref_fraud_ip" value="no">
                                <input type="checkbox" name="mc_ref_fraud_ip" value="yes" <?php checked($fraud, 'yes'); ?>>
                                <span class="mc-slider"></span>
                            </label>
                        </div>

                        <div class="mc-toggle-row" style="margin-bottom:20px; padding-top:20px; border-top:1px solid #eee;">
                            <div class="mc-form-info" style="margin:0;">
                                <span class="mc-form-label">Require Admin Approval</span>
                                <span class="mc-form-desc">If enabled, users must click "Apply" to get a referral code. You must approve them via their user profile.</span>
                            </div>
                            <label class="mc-toggle-switch">
                                <input type="hidden" name="mc_ref_require_approval" value="no">
                                <input type="checkbox" name="mc_ref_require_approval" value="yes" <?php checked($approval, 'yes'); ?> id="mc_ref_approval_toggle">
                                <span class="mc-slider"></span>
                            </label>
                        </div>

                        <div id="mc_ref_approval_settings_wrapper" style="margin-top: 15px; <?php echo $approval === 'yes' ? '' : 'display:none;'; ?>">
                            <div class="mc-form-row" style="margin-bottom: 20px;">
                                <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Admin Notification Email</span>
                                <span class="mc-form-desc" style="display: block; margin-bottom: 8px; font-size: 12px; color: #666;">Send application notifications here.</span>
                                <input type="email" name="mc_ref_admin_email" value="<?php echo esc_attr($email); ?>" style="width:100%; max-width:400px; padding: 6px;">
                            </div>

                            <div class="mc-toggle-row" style="margin-bottom:20px; padding-top:15px; border-top:1px dashed #eee;">
                                <div class="mc-form-info" style="margin:0;">
                                    <span class="mc-form-label">Create Custom User Role</span>
                                    <span class="mc-form-desc">Automatically create a new WordPress user role with standard customer capabilities and assign it to approved ambassadors.</span>
                                </div>
                                <label class="mc-toggle-switch">
                                    <input type="hidden" name="mc_ref_custom_role_enable" value="no">
                                    <input type="checkbox" name="mc_ref_custom_role_enable" value="yes" <?php checked($custom_role_enable, 'yes'); ?> id="mc_ref_custom_role_toggle">
                                    <span class="mc-slider"></span>
                                </label>
                            </div>

                            <div class="mc-form-row" id="mc_ref_role_name_wrapper" style="margin-bottom: 20px; <?php echo $custom_role_enable === 'yes' ? '' : 'display:none;'; ?>">
                                <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Custom Role Name</span>
                                <span class="mc-form-desc" style="display: block; margin-bottom: 8px; font-size: 12px; color: #666;">Enter the name of the role you want assigned upon approval.</span>
                                <input type="text" name="mc_ref_custom_role_name" value="<?php echo esc_attr($custom_role_name); ?>" placeholder="e.g. MealCrafter Fan" style="width:100%; max-width:400px; padding: 6px;">
                            </div>
                        </div>
                    </div>
                </div>

                <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                    <button type="submit" name="mc_save_referrals" style="background:#2271b1; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer; font-size:14px;">Save Referral Settings</button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#mc_ref_enable_toggle').on('change', function() {
                if($(this).is(':checked')) { $('#mc_ref_settings_wrapper').slideDown(); } else { $('#mc_ref_settings_wrapper').slideUp(); }
            });
            $('#mc_ref_approval_toggle').on('change', function() {
                if($(this).is(':checked')) { $('#mc_ref_approval_settings_wrapper').slideDown(); } else { $('#mc_ref_approval_settings_wrapper').slideUp(); }
            });
            $('#mc_ref_custom_role_toggle').on('change', function() {
                if($(this).is(':checked')) { $('#mc_ref_role_name_wrapper').slideDown(); } else { $('#mc_ref_role_name_wrapper').slideUp(); }
            });
        });
        </script>
        <?php
    }
}