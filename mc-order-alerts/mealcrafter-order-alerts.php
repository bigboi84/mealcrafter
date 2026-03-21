<?php
/**
 * Plugin Name: MealCrafter Order Notifications
 * Description: Dedicated Audio and Popup Alert engine. Integrates seamlessly with the Store Manager.
 * Version: 2.1.0
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Standalone_Alerts_Engine {

    public function __construct() {
        add_action( 'init', [ $this, 'register_cpt' ] );
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_action( 'all_admin_notices', [ $this, 'inject_tabs' ] );

        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_metaboxes' ] );
        add_action( 'save_post', [ $this, 'save_meta' ] );
        
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'admin_footer', [ $this, 'backend_listener' ] );
        
        add_action( 'wp_ajax_mc_standalone_backend_check', [ $this, 'ajax_backend_check' ] );
    }

    public function register_cpt() {
        register_post_type( 'mc_alert_rule', [
            'labels' => [ 'name' => 'Notifications', 'singular_name' => 'Notification' ],
            'public' => false, 'show_ui' => true, 'show_in_menu' => false, 'supports' => [ 'title' ],
        ]);
    }

    public function register_menu() {
        add_submenu_page( 'mc-hub', 'Order Notifications', 'Order Notifications', 'manage_options', 'mc-alerts', [ $this, 'render_settings' ] );
    }

    public function inject_tabs() {
        $screen = get_current_screen();
        if ( ! $screen ) return;
        $is_settings = (isset($_GET['page']) && $_GET['page'] === 'mc-alerts');
        $is_all = ($screen->id === 'edit-mc_alert_rule');
        $is_new = ($screen->id === 'mc_alert_rule');
        if ( $is_settings || $is_all || $is_new ) {
            ?>
            <div class="wrap" style="margin-bottom: 20px;">
                <h1 style="font-weight:900;">Order <span style="font-weight:100; color:#999;">Notifications</span></h1>
                <h2 class="nav-tab-wrapper">
                    <a href="admin.php?page=mc-alerts" class="nav-tab <?php echo $is_settings ? 'nav-tab-active' : ''; ?>">Settings</a>
                    <a href="edit.php?post_type=mc_alert_rule" class="nav-tab <?php echo $is_all ? 'nav-tab-active' : ''; ?>">All Notifications</a>
                    <a href="post-new.php?post_type=mc_alert_rule" class="nav-tab <?php echo $is_new ? 'nav-tab-active' : ''; ?>">Create New Notification</a>
                </h2>
            </div>
            <style>.wrap > h1.wp-heading-inline, .page-title-action { display: none !important; }</style>
            <?php
        }
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <div style="background:#fff; padding:30px; border:1px solid #ddd; margin-top:-1px;">
                <form method="post" action="options.php">
                    <?php settings_fields( 'mc_alert_group' ); ?>
                    <h3>Global Notification Engine Behavior</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Notification Target</th>
                            <td>
                                <select name="mc_alert_target">
                                    <option value="both" <?php selected(get_option('mc_alert_target', 'both'), 'both'); ?>>Enable for Both (Frontend & Backend)</option>
                                    <option value="frontend" <?php selected(get_option('mc_alert_target', 'both'), 'frontend'); ?>>Enable ONLY for Frontend Store Manager</option>
                                    <option value="backend" <?php selected(get_option('mc_alert_target', 'both'), 'backend'); ?>>Enable ONLY for WP Admin Backend</option>
                                    <option value="disabled" <?php selected(get_option('mc_alert_target', 'both'), 'disabled'); ?>>Completely Disable All Notifications</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Loop Audio Alerts</th>
                            <td>
                                <select name="mc_alert_loop">
                                    <option value="on" <?php selected(get_option('mc_alert_loop', 'on'), 'on'); ?>>On</option>
                                    <option value="off" <?php selected(get_option('mc_alert_loop', 'on'), 'off'); ?>>Off</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Check Interval (Seconds)</th>
                            <td><input type="number" name="mc_alert_interval" value="<?php echo esc_attr(get_option('mc_alert_interval', '15')); ?>" style="width:70px;"></td>
                        </tr>
                    </table>
                    <?php submit_button('Save Notification Settings'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting( 'mc_alert_group', 'mc_alert_target' );
        register_setting( 'mc_alert_group', 'mc_alert_loop' );
        register_setting( 'mc_alert_group', 'mc_alert_interval' );
    }

    public function add_metaboxes() {
        add_meta_box( 'mc_alert_settings', 'Rule Configuration', [ $this, 'render_metabox' ], 'mc_alert_rule', 'normal', 'high' );
    }

    public function render_metabox( $post ) {
        wp_nonce_field( 'mc_save_alert_meta', 'mc_alert_meta_nonce' );
        
        // THE FIX: Restored ORIGINAL meta keys so your existing settings aren't lost!
        $order_type    = get_post_meta( $post->ID, '_mc_order_type', true ) ?: 'any';
        $order_status  = get_post_meta( $post->ID, '_mc_order_status', true ) ?: 'processing';
        $sound_url     = get_post_meta( $post->ID, '_mc_notification_sound', true );
        $enable_popup  = get_post_meta( $post->ID, '_mc_enable_popup', true ) ?: 'yes';
        $popup_title   = get_post_meta( $post->ID, '_mc_popup_title', true ) ?: 'New Order #[order_id]';
        $popup_msg     = get_post_meta( $post->ID, '_mc_popup_message', true ) ?: 'A new order totaling [order_total] has arrived.';
        $popup_icon    = get_post_meta( $post->ID, '_mc_popup_icon', true );
        
        $bg_color      = get_post_meta( $post->ID, '_mc_popup_bg_color', true ) ?: '#ffffff';
        $text_color    = get_post_meta( $post->ID, '_mc_popup_text_color', true ) ?: '#333333';
        $btn_color     = get_post_meta( $post->ID, '_mc_popup_btn_color', true ) ?: '#2ecc71';

        $wc_statuses = function_exists('wc_get_order_statuses') ? wc_get_order_statuses() : [];
        ?>
        <div class="mc-section-title" style="background:#f0f0f1; padding:10px; font-weight:bold; border-left:4px solid #2271b1;">Trigger & Audio</div>
        <table class="form-table">
            <tr><th>Order Status</th><td><select name="mc_order_status"><option value="any">Any</option><?php foreach($wc_statuses as $k=>$l) echo "<option value='".str_replace('wc-','',$k)."' ".selected($order_status,str_replace('wc-','',$k),false).">$l</option>";?></select></td></tr>
            <tr><th>Order Type</th><td><select name="mc_order_type"><option value="any">Any</option><option value="delivery" <?php selected($order_type,'delivery');?>>Delivery</option><option value="pickup" <?php selected($order_type,'pickup');?>>Pickup</option></select></td></tr>
            <tr><th>Sound URL</th><td><input type="text" name="mc_notification_sound" value="<?php echo esc_attr($sound_url);?>" class="regular-text mc-upload-target"><button type="button" class="button mc-upload-btn" data-type="audio">Upload MP3</button></td></tr>
        </table>
        
        <div class="mc-section-title" style="background:#f0f0f1; padding:10px; font-weight:bold; border-left:4px solid #2271b1; margin-top:20px;">Popup Customization</div>
        <table class="form-table">
            <tr><th>Enable Popup</th><td><input type="checkbox" name="mc_enable_popup" value="yes" <?php checked($enable_popup,'yes');?>></td></tr>
            <tr><th>Title</th><td><input type="text" name="mc_popup_title" value="<?php echo esc_attr($popup_title);?>" class="regular-text"> <small>(Use [order_id] and [order_total])</small></td></tr>
            <tr><th>Message</th><td><textarea name="mc_popup_message" rows="2" class="regular-text" style="width: 60%;"><?php echo esc_textarea($popup_msg);?></textarea></td></tr>
            <tr><th>Icon Image</th><td><input type="text" name="mc_popup_icon" value="<?php echo esc_attr($popup_icon);?>" class="regular-text mc-upload-target"><button type="button" class="button mc-upload-btn" data-type="image">Upload Image</button></td></tr>
            <tr><th>Background Color</th><td><input type="text" name="mc_popup_bg_color" value="<?php echo esc_attr($bg_color);?>" class="mc-color-picker"></td></tr>
            <tr><th>Text Color</th><td><input type="text" name="mc_popup_text_color" value="<?php echo esc_attr($text_color);?>" class="mc-color-picker"></td></tr>
            <tr><th>Button Color</th><td><input type="text" name="mc_popup_btn_color" value="<?php echo esc_attr($btn_color);?>" class="mc-color-picker"></td></tr>
        </table>
        <script>
        jQuery(document).ready(function($){
            $('.mc-upload-btn').click(function(e) {
                e.preventDefault(); var btn = $(this);
                var media = wp.media({ title: 'Choose file', multiple: false, library: { type: btn.data('type') } });
                media.on('select', function() { btn.siblings('.mc-upload-target').val(media.state().get('selection').first().toJSON().url); });
                media.open();
            });
            if(typeof $.fn.wpColorPicker !== 'undefined') { $('.mc-color-picker').wpColorPicker(); }
        });
        </script>
        <?php
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['mc_alert_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mc_alert_meta_nonce'], 'mc_save_alert_meta' ) ) return;
        update_post_meta( $post_id, '_mc_order_type', sanitize_text_field($_POST['mc_order_type']) );
        update_post_meta( $post_id, '_mc_order_status', sanitize_text_field($_POST['mc_order_status']) );
        update_post_meta( $post_id, '_mc_notification_sound', esc_url_raw($_POST['mc_notification_sound']) );
        update_post_meta( $post_id, '_mc_enable_popup', isset($_POST['mc_enable_popup']) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_mc_popup_title', sanitize_text_field($_POST['mc_popup_title']) );
        update_post_meta( $post_id, '_mc_popup_message', sanitize_textarea_field($_POST['mc_popup_message']) );
        update_post_meta( $post_id, '_mc_popup_icon', esc_url_raw($_POST['mc_popup_icon']) );
        update_post_meta( $post_id, '_mc_popup_bg_color', sanitize_hex_color($_POST['mc_popup_bg_color']) );
        update_post_meta( $post_id, '_mc_popup_text_color', sanitize_hex_color($_POST['mc_popup_text_color']) );
        update_post_meta( $post_id, '_mc_popup_btn_color', sanitize_hex_color($_POST['mc_popup_btn_color']) );
    }

    public function enqueue_scripts( $hook ) {
        global $post;
        if ( ($hook == 'post-new.php' || $hook == 'post.php') && isset($post) && 'mc_alert_rule' === $post->post_type ) { 
            wp_enqueue_media(); wp_enqueue_style('wp-color-picker'); wp_enqueue_script('wp-color-picker');
        }
    }

    public function ajax_backend_check() {
        $last_id = isset($_POST['last_order_id']) ? intval($_POST['last_order_id']) : 0;
        $query_args = [ 'limit' => 5, 'status' => ['processing', 'pending', 'on-hold', 'wc-prep-pickup', 'wc-prep-deliv'], 'orderby' => 'id', 'order' => 'DESC' ];

        if ( ! current_user_can('administrator') ) {
            $assigned = get_user_meta(get_current_user_id(), '_mc_assigned_stores', true) ?: [];
            if ( ! empty($assigned) ) { $query_args['meta_key'] = '_mc_assigned_location'; $query_args['meta_value'] = $assigned; $query_args['meta_compare'] = 'IN'; }
        }

        $orders = wc_get_orders( $query_args );
        $rules = get_posts(['post_type' => 'mc_alert_rule', 'numberposts' => -1]);
        $rule_data = [];
        
        foreach($rules as $r) {
            $rule_data[] = [
                'status' => get_post_meta($r->ID, '_mc_order_status', true),
                'type'   => get_post_meta($r->ID, '_mc_order_type', true),
                'sound'  => get_post_meta($r->ID, '_mc_notification_sound', true),
                'popup'  => get_post_meta($r->ID, '_mc_enable_popup', true),
                'title'  => get_post_meta($r->ID, '_mc_popup_title', true),
                'msg'    => get_post_meta($r->ID, '_mc_popup_message', true),
                'loop'   => get_option('mc_alert_loop', 'on')
            ];
        }
        
        $new_orders = [];
        foreach ( $orders as $order ) {
            if ($order->get_id() > $last_id) { $new_orders[] = [ 'id' => $order->get_id(), 'status' => $order->get_status() ]; }
        }
        wp_send_json_success([ 'orders' => array_reverse($new_orders), 'rules' => $rule_data ]);
    }

    public function backend_listener() {
        $target = get_option( 'mc_alert_target', 'both' );
        if ( $target === 'frontend' || $target === 'disabled' ) return;
        $interval_ms = intval(get_option('mc_alert_interval', '15')) * 1000;
        ?>
        <script>
        jQuery(document).ready(function($) {
            let lastOrderId = 0; let audioPlayer = new Audio(); let adminUnlocked = false;
            $('body').one('click keydown touchstart', function() {
                if(!adminUnlocked) { audioPlayer.src = 'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA'; audioPlayer.play().then(() => { audioPlayer.pause(); audioPlayer.currentTime = 0; }).catch(() => {}); adminUnlocked = true; }
            });
            function checkOrders() {
                $.post(ajaxurl, { action: 'mc_standalone_backend_check', last_order_id: lastOrderId }, function(res) {
                    if(res.success && res.data.orders.length > 0) {
                        let newMax = lastOrderId;
                        res.data.orders.forEach(order => {
                            if (order.id > lastOrderId) {
                                newMax = Math.max(newMax, order.id);
                                let rule = res.data.rules.find(r => (r.status === 'any' || r.status === order.status));
                                if(rule) {
                                    if(rule.sound) { audioPlayer.src = rule.sound; audioPlayer.loop = (rule.loop === 'on'); audioPlayer.volume = 1.0; audioPlayer.play().catch(e => {}); }
                                    if(rule.popup === 'yes') { alert(rule.title.replace('[order_id]', order.id) + "\n" + rule.msg.replace('[order_id]', order.id)); }
                                }
                            }
                        });
                        lastOrderId = newMax;
                    }
                });
            }
            setInterval(checkOrders, <?php echo $interval_ms; ?>);
        });
        </script>
        <?php
    }
}
new MC_Standalone_Alerts_Engine();