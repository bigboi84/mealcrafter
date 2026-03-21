<?php
/**
 * Plugin Name: MealCrafter QR & Barcode Scanner
 * Description: Generates pickup QR codes with Secure Token Hashing for receipts and emails.
 * Version: 2.4.0
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_QR_Scanner {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

        add_action('woocommerce_email_order_meta', [$this, 'add_qr_to_emails'], 10, 3);
        add_action('woocommerce_thankyou', [$this, 'add_qr_to_thank_you_page'], 10, 1);
        add_action('woocommerce_view_order', [$this, 'add_qr_to_thank_you_page'], 10, 1);
        
        // NEW: Custom Shortcode for page builders and custom emails
        add_shortcode('mc_pickup_qr', [$this, 'qr_shortcode']);
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'mc-qr-designer') !== false) { wp_enqueue_style('wp-color-picker'); wp_enqueue_script('wp-color-picker'); }
    }

    public function add_admin_menu() {
        add_submenu_page('mc-hub', 'QR Designer', 'QR Designer', 'manage_options', 'mc-qr-designer', [$this, 'render_settings_page']);
    }

    public function register_settings() {
        $settings = ['mc_qr_enable_emails', 'mc_qr_enable_thankyou', 'mc_qr_title', 'mc_qr_title_color', 'mc_qr_text', 'mc_qr_text_color', 'mc_qr_bg_color', 'mc_qr_border_color', 'mc_qr_border_style', 'mc_qr_size'];
        foreach($settings as $setting) { register_setting('mc_qr_settings_group', $setting); }
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1 style="font-weight:900;">QR Code <span style="font-weight:100; color:#999;">Designer</span></h1>
            <div style="display:flex; gap:30px; margin-top:20px;">
                <div style="flex:1; background:#fff; padding:30px; border:1px solid #ddd; border-radius:12px;">
                    
                    <div style="background:#e3f2fd; color:#0c5460; padding:15px; border-radius:8px; margin-bottom:20px;">
                        <h4 style="margin-top:0;">Shortcode Integration:</h4>
                        <p style="margin:0;">Use <code>[mc_pickup_qr]</code> to place the QR code anywhere on the Order View pages. If using a custom email builder, use <code>[mc_pickup_qr order_id="123"]</code> to force a specific order.</p>
                    </div>

                    <form method="post" action="options.php">
                        <?php settings_fields('mc_qr_settings_group'); ?>
                        <h3 style="margin-top:0;">Display Locations</h3>
                        <label style="display:block; margin-bottom:10px;"><input type="checkbox" name="mc_qr_enable_emails" value="yes" <?php checked(get_option('mc_qr_enable_emails', 'yes'), 'yes'); ?>> Auto-Inject in Emails</label>
                        <label style="display:block; margin-bottom:20px;"><input type="checkbox" name="mc_qr_enable_thankyou" value="yes" <?php checked(get_option('mc_qr_enable_thankyou', 'yes'), 'yes'); ?>> Auto-Inject on Checkout Pages</label>
                        <hr>
                        <h3>Text & Content</h3>
                        <table class="form-table">
                            <tr><th>Heading Text</th><td><input type="text" name="mc_qr_title" value="<?php echo esc_attr(get_option('mc_qr_title', 'Your Pickup Code')); ?>" class="regular-text"></td></tr>
                            <tr><th>Heading Color</th><td><input type="text" name="mc_qr_title_color" value="<?php echo esc_attr(get_option('mc_qr_title_color', '#333333')); ?>" class="mc-color-picker"></td></tr>
                            <tr><th>Instruction Text</th><td><input type="text" name="mc_qr_text" value="<?php echo esc_attr(get_option('mc_qr_text', 'Please present this QR code to our staff at the counter.')); ?>" class="regular-text" style="width:100%;"></td></tr>
                            <tr><th>Text Color</th><td><input type="text" name="mc_qr_text_color" value="<?php echo esc_attr(get_option('mc_qr_text_color', '#666666')); ?>" class="mc-color-picker"></td></tr>
                        </table>
                        <hr>
                        <h3>Box Design</h3>
                        <table class="form-table">
                            <tr><th>Background Color</th><td><input type="text" name="mc_qr_bg_color" value="<?php echo esc_attr(get_option('mc_qr_bg_color', '#fafafa')); ?>" class="mc-color-picker"></td></tr>
                            <tr><th>Border Color</th><td><input type="text" name="mc_qr_border_color" value="<?php echo esc_attr(get_option('mc_qr_border_color', '#dddddd')); ?>" class="mc-color-picker"></td></tr>
                            <tr>
                                <th>Border Style</th>
                                <td>
                                    <select name="mc_qr_border_style">
                                        <option value="solid" <?php selected(get_option('mc_qr_border_style', 'dashed'), 'solid'); ?>>Solid Line</option>
                                        <option value="dashed" <?php selected(get_option('mc_qr_border_style', 'dashed'), 'dashed'); ?>>Dashed Line</option>
                                        <option value="dotted" <?php selected(get_option('mc_qr_border_style', 'dashed'), 'dotted'); ?>>Dotted Line</option>
                                    </select>
                                </td>
                            </tr>
                            <tr><th>QR Code Size (px)</th><td><input type="number" name="mc_qr_size" value="<?php echo esc_attr(get_option('mc_qr_size', '200')); ?>" style="width:80px;"></td></tr>
                        </table>
                        <?php submit_button('Save Design'); ?>
                    </form>
                </div>
                <div style="flex:1;">
                    <div style="position:sticky; top:40px;">
                        <h3 style="margin-top:0;">Live Preview</h3>
                        <?php 
                        $bg = get_option('mc_qr_bg_color', '#fafafa');
                        $bc = get_option('mc_qr_border_color', '#dddddd');
                        $bs = get_option('mc_qr_border_style', 'dashed');
                        $tc = get_option('mc_qr_title_color', '#333333');
                        $pc = get_option('mc_qr_text_color', '#666666');
                        $sz = get_option('mc_qr_size', '200');
                        ?>
                        <div style="text-align:center; padding:30px; border:2px <?php echo $bs; ?> <?php echo $bc; ?>; border-radius:15px; background:<?php echo $bg; ?>; max-width:400px;">
                            <h3 style="margin-top:0; color:<?php echo $tc; ?>; text-transform:uppercase; font-size:18px; letter-spacing:1px; font-weight:900; font-family:sans-serif;"><?php echo esc_html(get_option('mc_qr_title', 'Your Pickup Code')); ?></h3>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=<?php echo $sz; ?>x<?php echo $sz; ?>&data=PREVIEW" style="width:<?php echo $sz; ?>px; height:auto; display:block; margin:20px auto;">
                            <p style="color:<?php echo $pc; ?>; font-size:15px; font-weight:600; line-height:1.4; font-family:sans-serif; margin-bottom:0;"><?php echo esc_html(get_option('mc_qr_text', 'Please present this QR code to our staff at the counter.')); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>jQuery(document).ready(function($){ $('.mc-color-picker').wpColorPicker(); });</script>
        <?php
    }

    private function get_qr_url($order_id) {
        $size = get_option('mc_qr_size', '200');
        $manager_page_id = get_option('mc_manager_page_id');
        $base_url = $manager_page_id ? get_permalink($manager_page_id) : home_url();

        $token = get_post_meta($order_id, '_mc_pickup_token', true);
        if (!$token) {
            $token = wp_generate_password(12, false);
            update_post_meta($order_id, '_mc_pickup_token', $token);
        }
        
        $scan_url = add_query_arg(['mc_auth' => $token], $base_url);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($scan_url);
    }

    private function render_qr_html($order_id, $is_email = false) {
        $qr_image = $this->get_qr_url($order_id);
        $bg = get_option('mc_qr_bg_color', '#fafafa');
        $bc = get_option('mc_qr_border_color', '#dddddd');
        $bs = get_option('mc_qr_border_style', 'dashed');
        $tc = get_option('mc_qr_title_color', '#333333');
        $pc = get_option('mc_qr_text_color', '#666666');
        $sz = get_option('mc_qr_size', '200');

        // THE FIX: Explicitly forced center alignment using old-school tables for Email Clients
        if ($is_email) { echo '<table width="100%" border="0" cellspacing="0" cellpadding="0"><tr><td align="center">'; }

        echo '<div style="text-align: center; margin: 30px auto; padding: 25px; border: 2px '.esc_attr($bs).' '.esc_attr($bc).'; border-radius: 12px; background: '.esc_attr($bg).'; max-width:400px; width:100%; box-sizing:border-box;">';
        echo '<h3 style="margin-top:0; color:'.esc_attr($tc).'; text-transform:uppercase; font-size:18px; letter-spacing:1px; font-family:sans-serif; font-weight:900;">' . esc_html(get_option('mc_qr_title', 'Your Pickup Code')) . '</h3>';
        echo '<img src="' . esc_url($qr_image) . '" alt="Pickup QR Code" style="width: ' . esc_attr($sz) . 'px; height: auto; display: block; margin: 15px auto;">';
        echo '<p style="color:'.esc_attr($pc).'; font-size:15px; margin-bottom:0; font-weight:600; font-family:sans-serif;">' . esc_html(get_option('mc_qr_text', 'Scan at counter.')) . '</p>';
        echo '</div>';

        if ($is_email) { echo '</td></tr></table>'; }
    }

    public function add_qr_to_emails($order, $sent_to_admin, $plain_text) { 
        if (!$sent_to_admin && !$plain_text && get_option('mc_qr_enable_emails', 'yes') === 'yes') { $this->render_qr_html($order->get_id(), true); }
    }

    public function add_qr_to_thank_you_page($order_id) { 
        if (get_option('mc_qr_enable_thankyou', 'yes') === 'yes' && $order_id) { $this->render_qr_html($order_id, false); }
    }

    public function qr_shortcode($atts) {
        $atts = shortcode_atts(['order_id' => ''], $atts);
        if (empty($atts['order_id'])) {
            global $wp;
            if (isset($wp->query_vars['order-received'])) $atts['order_id'] = $wp->query_vars['order-received'];
            elseif (isset($wp->query_vars['view-order'])) $atts['order_id'] = $wp->query_vars['view-order'];
            else return '';
        }
        ob_start();
        $this->render_qr_html($atts['order_id'], false);
        return ob_get_clean();
    }
}
new MC_QR_Scanner();