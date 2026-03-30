<?php
/**
 * MealCrafter: Offers & Coupons Settings Tab
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Offers {

    public function render() {
        if ( isset($_POST['mc_save_offers']) && isset($_POST['mc_offers_nonce']) && wp_verify_nonce($_POST['mc_offers_nonce'], 'mc_save_off') && current_user_can('manage_options') ) {
            $fields = [
                'mc_offers_enable',
                'mc_offers_elementor_template',
                'mc_offers_design_bg',
                'mc_offers_design_title',
                'mc_offers_design_sub',
                'mc_offers_design_btn',
                'mc_offers_design_promo',
                'mc_offers_design_fallback_icon'
            ];
            foreach ($fields as $field) {
                $val = isset($_POST[$field]) ? sanitize_text_field($_POST[$field]) : '';
                update_option($field, $val);
            }
            
            echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-top:20px; border-radius:4px; font-weight:600;">✅ Offers & Design Settings saved successfully!</div>';
        }

        $enable = get_option('mc_offers_enable', 'no');
        $template_id = get_option('mc_offers_elementor_template', '');
        
        // Design Defaults
        $bg = get_option('mc_offers_design_bg', '#ffffff');
        $title = get_option('mc_offers_design_title', '#222222');
        $sub = get_option('mc_offers_design_sub', '#666666');
        $btn = get_option('mc_offers_design_btn', '#2ecc71');
        $promo = get_option('mc_offers_design_promo', '#d35400');
        $icon = get_option('mc_offers_design_fallback_icon', '🎁');
        ?>
        
        <div class="mc-main-content" style="width: 100%; max-width: 900px; margin-top: 20px;">
            <div style="margin-bottom: 25px;">
                <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;">Offers & Coupons</h2>
                <p style="margin:0; font-size:13px; color:#646970;">Manage how your loyalty rewards and promo codes look globally across your website.</p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('mc_save_off', 'mc_offers_nonce'); ?>
                
                <div class="mc-rule-card" style="padding:25px; border-left:4px solid #f39c12; margin-bottom: 30px;">
                    <div class="mc-toggle-row">
                        <div class="mc-form-info" style="margin:0;">
                            <span class="mc-form-label" style="font-weight:bold; font-size:16px;">Enable Loyalty Offers</span>
                            <span class="mc-form-desc">Activates the "Offers" tab in the My Account area and enables the Elementor widgets.</span>
                        </div>
                        <label class="mc-toggle-switch">
                            <input type="hidden" name="mc_offers_enable" value="no">
                            <input type="checkbox" name="mc_offers_enable" value="yes" <?php checked($enable, 'yes'); ?> id="mc_offers_enable_toggle">
                            <span class="mc-slider"></span>
                        </label>
                    </div>
                </div>

                <div id="mc_offers_settings_wrapper" style="<?php echo $enable === 'yes' ? '' : 'display:none;'; ?>">
                    
                    <div class="mc-rule-card" style="padding:25px; border-left:4px solid #e74c3c; margin-bottom: 30px;">
                        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Global Widget Design Builder</h3>
                        <p style="font-size:13px; color:#666; margin-bottom:20px;">Customize the colors and styling of the MealCrafter Elementor Offer blocks here. Changes will update instantly across your entire site.</p>
                        
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom: 20px;">
                            <div>
                                <span class="mc-form-label">Card Background Color</span>
                                <input type="color" name="mc_offers_design_bg" value="<?php echo esc_attr($bg); ?>" style="width:100%; height:40px; border-radius:4px; border:1px solid #ccc; cursor:pointer;">
                            </div>
                            <div>
                                <span class="mc-form-label">Title Text Color</span>
                                <input type="color" name="mc_offers_design_title" value="<?php echo esc_attr($title); ?>" style="width:100%; height:40px; border-radius:4px; border:1px solid #ccc; cursor:pointer;">
                            </div>
                            <div>
                                <span class="mc-form-label">Subtitle Text Color</span>
                                <input type="color" name="mc_offers_design_sub" value="<?php echo esc_attr($sub); ?>" style="width:100%; height:40px; border-radius:4px; border:1px solid #ccc; cursor:pointer;">
                            </div>
                            <div>
                                <span class="mc-form-label">Unlock Button (Points) Color</span>
                                <input type="color" name="mc_offers_design_btn" value="<?php echo esc_attr($btn); ?>" style="width:100%; height:40px; border-radius:4px; border:1px solid #ccc; cursor:pointer;">
                            </div>
                            <div>
                                <span class="mc-form-label">Free Promo Highlight Color</span>
                                <input type="color" name="mc_offers_design_promo" value="<?php echo esc_attr($promo); ?>" style="width:100%; height:40px; border-radius:4px; border:1px solid #ccc; cursor:pointer;">
                            </div>
                            <div>
                                <span class="mc-form-label">Fallback Icon (If no image uploaded)</span>
                                <input type="text" name="mc_offers_design_fallback_icon" value="<?php echo esc_attr($icon); ?>" placeholder="e.g. 🎁, ⭐, 🎟️" style="width:100%; max-width:100%; padding: 8px;">
                            </div>
                        </div>
                    </div>

                    <div class="mc-rule-card" style="padding:25px; border-left:4px solid #9b59b6; margin-bottom: 30px;">
                        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">My Account Tab Integration</h3>
                        
                        <div class="mc-form-row" style="margin-bottom: 20px;">
                            <span class="mc-form-label" style="font-weight: 600; display: block; margin-bottom: 5px;">Elementor Template ID (or Shortcode)</span>
                            <span class="mc-form-desc" style="display: block; margin-bottom: 8px; font-size: 12px; color: #666;">
                                Create a custom layout using Elementor Blocks. Paste the Template ID here (e.g., <strong>1234</strong>) or the full shortcode (e.g., <strong>[elementor-template id="1234"]</strong>) to display it in the 'My Account' Offers tab.
                            </span>
                            <input type="text" name="mc_offers_elementor_template" value="<?php echo esc_attr($template_id); ?>" placeholder="e.g. 1234" style="width:100%; max-width:400px; padding: 8px;">
                        </div>
                        
                        <div style="background:#fef8ee; border:1px solid #f6c064; padding:15px; border-radius:6px;">
                            <strong style="color:#d35400;">Standalone Shortcode:</strong><br>
                            You can display your custom Offers block anywhere on your website by using the shortcode: <br>
                            <code style="background:#fff; padding:4px 8px; border-radius:4px; font-size:14px; margin-top:5px; display:inline-block; color:#222;">[mc_rewards_offers]</code>
                        </div>
                    </div>

                    <div class="mc-rule-card" style="padding:25px; border-left:4px solid #3498db; margin-bottom: 30px;">
                        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">How to Create a Reward Offer</h3>
                        <p style="font-size:14px; color:#555; line-height:1.6;">
                            1. Go to <a href="<?php echo admin_url('edit.php?post_type=shop_coupon'); ?>" style="font-weight:bold; color:#2271b1; text-decoration:underline;">Marketing > Coupons &rarr;</a> in your WordPress dashboard.<br><br>
                            2. Click <strong>Add Coupon</strong> (or edit an existing one).<br><br>
                            3. Under the <strong>General</strong> tab, look for the orange checkbox labeled <strong>[x] Enable as Loyalty Reward Offer</strong>.<br><br>
                            4. Check the box to reveal the Point Cost, Expiration, and Elementor display settings!
                        </p>
                    </div>

                </div>

                <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
                    <button type="submit" name="mc_save_offers" style="background:#2271b1; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer; font-size:14px;">Save Settings & Colors</button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#mc_offers_enable_toggle').on('change', function() {
                if($(this).is(':checked')) { $('#mc_offers_settings_wrapper').slideDown(); } else { $('#mc_offers_settings_wrapper').slideUp(); }
            });
        });
        </script>
        <?php
    }
}