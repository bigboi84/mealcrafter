<?php
/**
 * Plugin Name: MealCrafter - Multi-Store Pickup & Delivery
 * Description: Manages multiple store locations, checkout firewall validations, and cache-proof sessions.
 * Version: 4.8.2
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action('woocommerce_init', function() {
    if ( isset($_COOKIE['mc_loc_set']) && function_exists('WC') && isset(WC()->session) && ! WC()->session->has_session() ) {
        WC()->session->set_customer_session_cookie(true);
    }
});

function mc_get_safe_session($key) {
    $val = '';
    if ( function_exists('WC') && is_object(WC()) && isset(WC()->session) && is_object(WC()->session) && method_exists(WC()->session, 'get') ) {
        $val = WC()->session->get($key);
    }
    if ( empty($val) ) {
        $cookie_map = [
            'mc_active_location'  => 'mc_loc_id',
            'mc_active_method'    => 'mc_loc_method',
            'mc_active_date'      => 'mc_loc_date',
            'mc_active_time'      => 'mc_loc_time',
            'mc_customer_address' => 'mc_loc_address',
        ];
        if ( isset($cookie_map[$key]) && isset($_COOKIE[$cookie_map[$key]]) ) {
            $val = sanitize_text_field(stripslashes($_COOKIE[$cookie_map[$key]]));
            if ( function_exists('WC') && isset(WC()->session) && !empty($val) ) { WC()->session->set($key, $val); }
        }
    }
    return $val;
}

add_action('wp_ajax_mc_save_location', 'mc_save_active_location');
add_action('wp_ajax_nopriv_mc_save_location', 'mc_save_active_location');
function mc_save_active_location() {
    if ( ! isset($_POST['location_id']) ) { wp_send_json_error('Missing location ID'); }

    $loc_id = intval($_POST['location_id']);
    $method = sanitize_text_field($_POST['method']);
    $date   = sanitize_text_field($_POST['date']);
    $time   = sanitize_text_field($_POST['time']);
    $addr   = isset($_POST['address']) ? sanitize_text_field($_POST['address']) : '';

    if ( function_exists('WC') && isset(WC()->session) ) {
        if ( ! WC()->session->has_session() ) { WC()->session->set_customer_session_cookie(true); }
        WC()->session->set('mc_active_location', $loc_id);
        WC()->session->set('mc_active_method', $method);
        WC()->session->set('mc_active_date', $date);
        WC()->session->set('mc_active_time', $time);
        if ( $addr ) WC()->session->set('mc_customer_address', $addr);
        WC()->session->save_data();
    }

    setcookie('mc_loc_id', $loc_id, time() + 86400, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
    setcookie('mc_loc_method', $method, time() + 86400, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
    setcookie('mc_loc_date', $date, time() + 86400, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
    setcookie('mc_loc_time', $time, time() + 86400, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
    if ( $addr ) setcookie('mc_loc_address', $addr, time() + 86400, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
    setcookie('mc_loc_set', 'yes', time() + 86400, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);

    wp_send_json_success(['message' => 'Saved securely to cookies.']);
}

add_action('woocommerce_thankyou', function($order_id) {
    if ( function_exists('WC') && isset(WC()->session) ) {
        WC()->session->set('mc_active_time', '');
        WC()->session->set('mc_active_date', '');
        WC()->session->save_data();
    }
    setcookie('mc_loc_time', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
    setcookie('mc_loc_date', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
    setcookie('mc_loc_set', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN);
});

add_action('woocommerce_after_checkout_validation', function($data, $errors) {
    $loc_id = mc_get_safe_session('mc_active_location');
    $date = mc_get_safe_session('mc_active_date');
    $time = mc_get_safe_session('mc_active_time');

    if (empty($loc_id) || empty($date) || empty($time)) {
        $errors->add('mc_loc_missing', '<strong>Missing Store Data:</strong> Please select a Store Location and Pickup/Delivery time.');
        return;
    }

    $hours = get_post_meta($loc_id, '_mc_loc_hours', true) ?: [];
    $day_of_week = date('l', strtotime($date)); 
    $today_hours = $hours[$day_of_week] ?? [];

    if (isset($today_hours['closed']) && $today_hours['closed'] === 'yes') {
        $errors->add('mc_loc_closed', '<strong>Store Closed:</strong> ' . get_the_title($loc_id) . ' is closed on ' . $day_of_week . '. Please select a different date.');
        return;
    }

    $buffer = intval(get_option('mc_last_order_buffer', '30')) * 60;
    $current_time = current_time('timestamp');

    if (!empty($today_hours['open']) && !empty($today_hours['close'])) {
        $open_time = strtotime($date . ' ' . $today_hours['open']);
        $close_time = strtotime($date . ' ' . $today_hours['close']);
        
        if ($close_time <= $open_time) {
            $close_time += DAY_IN_SECONDS;
        }

        if ($time === 'ASAP') {
            if (date('Y-m-d', strtotime($date)) !== date('Y-m-d', $current_time)) {
                $errors->add('mc_time_asap_future', '<strong>Invalid Time:</strong> ASAP is only available for today. Please select a specific time for future dates.');
            } elseif ($current_time < $open_time) {
                $errors->add('mc_time_early', '<strong>Store Closed:</strong> We are not open yet. Please select a later time.');
            } elseif ($current_time > ($close_time - $buffer)) {
                $errors->add('mc_time_late', '<strong>Store Closed:</strong> We are past our ordering hours for today. Please select a time for tomorrow.');
            }
        } else {
            $selected_time = strtotime($date . ' ' . $time);
            if ($selected_time < $current_time) {
                $errors->add('mc_time_past', '<strong>Time Expired:</strong> The time you originally selected (' . date('g:i A', $selected_time) . ') has passed. Please click EDIT on your shipping details to pick a new time.');
            }
            elseif ($selected_time < $open_time || $selected_time > ($close_time - $buffer)) {
                $errors->add('mc_time_invalid', '<strong>Invalid Time:</strong> The selected time is outside our operating hours for that day. Please click EDIT to pick a valid time.');
            }
        }
    }
}, 10, 2);

add_filter('woocommerce_add_to_cart_validation', function($passed, $product_id, $quantity) {
    if ( get_option('mc_feature_force_select', 'off') !== 'off' ) {
        if ( empty(mc_get_safe_session('mc_active_location')) ) {
            wc_add_notice('Please select a Store Location and Time before adding items to your cart.', 'error');
            return false;
        }
    }
    return $passed;
}, 10, 3);

add_action('wp_footer', function() {
    if ( is_product() ) {
        $mode = get_option('mc_feature_force_select', 'off');
        if ( $mode !== 'off' ) {
            $url = get_option('mc_redirect_page_id') ? get_permalink(get_option('mc_redirect_page_id')) : '';
            ?>
            <script>
            jQuery(document).ready(function($){
                if (document.cookie.indexOf('mc_loc_set=') === -1) {
                    <?php if ($mode === 'redirect' && $url): ?>
                        window.location.href = '<?php echo esc_url($url); ?>';
                    <?php elseif ($mode === 'overlay'): ?>
                        if($('#mc-locator-wrapper').length) { $('#mc-locator-wrapper').addClass('active'); }
                    <?php endif; ?>
                }
            });
            </script>
            <?php
        }
    }
});

add_filter('woocommerce_checkout_fields', function($fields) {
    $method = mc_get_safe_session('mc_active_method');
    if ( $method === 'pickup' ) {
        if (isset($fields['billing'])) {
            unset($fields['billing']['billing_company'], $fields['billing']['billing_address_1'], $fields['billing']['billing_address_2']);
            unset($fields['billing']['billing_city'], $fields['billing']['billing_postcode'], $fields['billing']['billing_country'], $fields['billing']['billing_state']);
        }
        if (isset($fields['shipping'])) { unset($fields['shipping']); }
    } else {
        if (get_option('mc_chk_hide_company', 'no') === 'yes' && isset($fields['billing'])) { unset($fields['billing']['billing_company']); if(isset($fields['shipping'])) unset($fields['shipping']['shipping_company']); }
        if (get_option('mc_chk_hide_address_2', 'no') === 'yes' && isset($fields['billing'])) { unset($fields['billing']['billing_address_2']); if(isset($fields['shipping'])) unset($fields['shipping']['shipping_address_2']); }
        if (get_option('mc_chk_hide_city', 'no') === 'yes' && isset($fields['billing'])) { unset($fields['billing']['billing_city']); if(isset($fields['shipping'])) unset($fields['shipping']['shipping_city']); }
        if (get_option('mc_chk_hide_postcode', 'no') === 'yes' && isset($fields['billing'])) { unset($fields['billing']['billing_postcode']); if(isset($fields['shipping'])) unset($fields['shipping']['shipping_postcode']); }
        if (get_option('mc_chk_hide_country', 'no') === 'yes' && isset($fields['billing'])) { unset($fields['billing']['billing_country'], $fields['billing']['billing_state']); if(isset($fields['shipping'])) unset($fields['shipping']['shipping_country'], $fields['shipping']['shipping_state']); }
    }
    return $fields;
}, 999);

add_filter('woocommerce_checkout_get_value', function($value, $input) {
    $method = mc_get_safe_session('mc_active_method');
    $address = mc_get_safe_session('mc_customer_address');
    if ( $method === 'delivery' && !empty($address) && in_array($input, ['billing_address_1', 'shipping_address_1']) ) { return $address; }
    return $value;
}, 10, 2);

function mc_hide_admin_checkout_fields($fields) {
    if (get_option('mc_chk_hide_company', 'no') === 'yes') unset($fields['company']);
    if (get_option('mc_chk_hide_address_2', 'no') === 'yes') unset($fields['address_2']);
    if (get_option('mc_chk_hide_city', 'no') === 'yes') unset($fields['city']);
    if (get_option('mc_chk_hide_postcode', 'no') === 'yes') unset($fields['postcode']);
    if (get_option('mc_chk_hide_country', 'no') === 'yes') { unset($fields['country'], $fields['state']); }
    return $fields;
}
add_filter('woocommerce_admin_billing_fields', 'mc_hide_admin_checkout_fields');
add_filter('woocommerce_admin_shipping_fields', 'mc_hide_admin_checkout_fields');

add_action('woocommerce_checkout_create_order', function($order, $data) {
    if (!$order->get_shipping_first_name()) $order->set_shipping_first_name($order->get_billing_first_name());
    if (!$order->get_shipping_last_name()) $order->set_shipping_last_name($order->get_billing_last_name());
    if (!$order->get_shipping_address_1()) $order->set_shipping_address_1($order->get_billing_address_1());
}, 10, 2);

add_action('woocommerce_after_checkout_billing_form', function() {
    $loc_id = mc_get_safe_session('mc_active_location');
    $method = mc_get_safe_session('mc_active_method');
    $date = (string) mc_get_safe_session('mc_active_date');
    $time = (string) mc_get_safe_session('mc_active_time');
    
    $primary = get_option('mc_design_primary_color', '#e74c3c');
    $edit_url = get_option('mc_redirect_page_id') ? get_permalink(get_option('mc_redirect_page_id')) : '#';

    if ( ! $loc_id ) {
        echo '<div style="background:#fff3cd; color:#856404; padding:15px; border-radius:5px; margin-top:20px;"><strong>Missing Location Data:</strong> You must select a store and time. <a href="'.esc_url($edit_url).'" style="font-weight:bold; color:#856404; text-decoration:underline;">Select Now</a></div>';
        remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
    } else {
        $display_date = $date ? date('F j, Y', strtotime($date)) : 'TBD';
        $display_time = (strpos($time, 'ASAP') !== false) ? 'ASAP' : ($time ? date('g:i A', strtotime($time)) : 'TBD');

        echo '<div style="background:#f8f9fa; border:1px solid #ddd; border-left:4px solid '.$primary.'; padding:20px; border-radius:4px; margin-top:20px;">';
        echo '<h3 style="margin-top:0; font-size:18px;">Shipping Details <a href="'.esc_url($edit_url).'" class="mc-locator-trigger" style="font-size:12px; float:right; background:'.$primary.'; color:#fff; padding:5px 15px; border-radius:50px; text-decoration:none;">EDIT</a></h3>';
        echo '<p style="margin:0 0 5px;"><strong>Store:</strong> ' . get_the_title($loc_id) . '</p>';
        echo '<p style="margin:0 0 5px;"><strong>Method:</strong> ' . strtoupper($method) . '</p>';
        echo '<p style="margin:0;"><strong>Scheduled For:</strong> ' . $display_date . ' at ' . $display_time . '</p>';
        echo '</div>';
    }
});

add_action('woocommerce_checkout_create_order', function($order, $data) {
    if ( mc_get_safe_session('mc_active_location') ) {
        $order->update_meta_data('_mc_assigned_location', mc_get_safe_session('mc_active_location'));
        $order->update_meta_data('_mc_order_method', mc_get_safe_session('mc_active_method'));
        $order->update_meta_data('_mc_order_date', mc_get_safe_session('mc_active_date'));
        $order->update_meta_data('_mc_order_time', mc_get_safe_session('mc_active_time'));
    }
}, 10, 2);


// =====================================================================
// WP ADMIN BACKEND FULFILLMENT PANEL (WHITE-LABELED)
// =====================================================================

// 1. Hide the confusing default WooCommerce Shipping Box
add_action('admin_head', function() {
    $screen = get_current_screen();
    if ( $screen && in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders']) ) {
        echo '<style>#woocommerce-order-shipping { display: none !important; }</style>';
    }
});

// 2. Register the New HPOS-Compatible Meta Box
add_action('add_meta_boxes', function() {
    $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id('shop-order') : 'shop_order';
    
    // Generic title for white-labeling
    add_meta_box('mc_fulfillment_details', 'Fulfillment Details', 'mc_render_fulfillment_metabox', $screen, 'side', 'high');
});

// 3. Render the Editable Fields
function mc_render_fulfillment_metabox($post_or_order_object) {
    $order = ($post_or_order_object instanceof WC_Order) ? $post_or_order_object : wc_get_order($post_or_order_object->ID);
    if (!$order) return;

    // Get current saved values (or defaults for new orders)
    $loc_id = $order->get_meta('_mc_assigned_location') ?: '';
    $method = $order->get_meta('_mc_order_method') ?: 'pickup';
    $date   = $order->get_meta('_mc_order_date') ?: date('Y-m-d');
    $time   = $order->get_meta('_mc_order_time') ?: 'ASAP';

    // Get all Store Locations for the dropdown
    $locations = get_posts(['post_type' => 'mc_location', 'posts_per_page' => -1]);
    
    // Nonce for security
    wp_nonce_field('mc_save_fulfillment_action', 'mc_fulfillment_nonce');
    ?>
    <div style="padding: 5px 0;">
        <p style="margin-top: 0;">
            <label for="_mc_assigned_location" style="font-weight: bold; display:block; margin-bottom:5px;">Store Location:</label>
            <select name="_mc_assigned_location" id="_mc_assigned_location" style="width: 100%;">
                <option value="">-- Select Store --</option>
                <?php foreach($locations as $l): ?>
                    <option value="<?php echo esc_attr($l->ID); ?>" <?php selected($loc_id, $l->ID); ?>><?php echo esc_html($l->post_title); ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="_mc_order_method" style="font-weight: bold; display:block; margin-bottom:5px;">Method:</label>
            <select name="_mc_order_method" id="_mc_order_method" style="width: 100%;">
                <option value="pickup" <?php selected($method, 'pickup'); ?>>Pickup</option>
                <option value="delivery" <?php selected($method, 'delivery'); ?>>Delivery</option>
            </select>
        </p>

        <p>
            <label for="_mc_order_date" style="font-weight: bold; display:block; margin-bottom:5px;">Scheduled Date:</label>
            <input type="date" name="_mc_order_date" id="_mc_order_date" value="<?php echo esc_attr($date); ?>" style="width: 100%;">
        </p>

        <p style="margin-bottom: 0;">
            <label for="_mc_order_time" style="font-weight: bold; display:block; margin-bottom:5px;">Scheduled Time:</label>
            <input type="text" name="_mc_order_time" id="_mc_order_time" value="<?php echo esc_attr($time); ?>" placeholder="e.g. 14:30 or ASAP" style="width: 100%;">
            <small style="color: #666; display:block; margin-top:3px;">Use 24hr format or type "ASAP".</small>
        </p>
    </div>
    <?php
}

// 4. Save the Editable Fields to the Order (HPOS Compatible)
add_action('woocommerce_process_shop_order_meta', 'mc_save_fulfillment_metabox', 10, 2);
function mc_save_fulfillment_metabox($order_id, $post) {
    if (!isset($_POST['mc_fulfillment_nonce']) || !wp_verify_nonce($_POST['mc_fulfillment_nonce'], 'mc_save_fulfillment_action')) { return; }
    
    $order = wc_get_order($order_id);
    if (!$order) return;

    $needs_save = false;

    if (isset($_POST['_mc_assigned_location'])) { $order->update_meta_data('_mc_assigned_location', sanitize_text_field($_POST['_mc_assigned_location'])); $needs_save = true; }
    if (isset($_POST['_mc_order_method'])) { $order->update_meta_data('_mc_order_method', sanitize_text_field($_POST['_mc_order_method'])); $needs_save = true; }
    if (isset($_POST['_mc_order_date'])) { $order->update_meta_data('_mc_order_date', sanitize_text_field($_POST['_mc_order_date'])); $needs_save = true; }
    if (isset($_POST['_mc_order_time'])) { $order->update_meta_data('_mc_order_time', sanitize_text_field($_POST['_mc_order_time'])); $needs_save = true; }
    
    // Lock it into the database!
    if ($needs_save) {
        $order->save();
    }
}


// Adds the details to the emails and the frontend thank you page
add_filter('woocommerce_order_get_formatted_shipping_address', function($address, $raw_address, $order) {
    $loc_id = $order->get_meta('_mc_assigned_location');
    if ($loc_id) {
        $method = strtoupper($order->get_meta('_mc_order_method'));
        $date = $order->get_meta('_mc_order_date');
        $time = (string) $order->get_meta('_mc_order_time');
        $display_time = (strpos($time, 'ASAP') !== false) ? 'ASAP' : ($time ? date('g:i A', strtotime($time)) : 'TBD');
        $store = get_the_title($loc_id);
        
        $append = "\n\n--- SHIPPING DETAILS ---\nMethod: $method\nStore: $store\nScheduled: " . ($date ?: 'TBD') . " @ $display_time";
        return $address . $append;
    }
    return $address;
}, 10, 3);


add_action( 'init', function() {
    register_post_status( 'wc-prep-pickup', ['label' => 'Preparing (Pickup)', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Preparing (Pickup) <span class="count">(%s)</span>', 'Preparing (Pickup) <span class="count">(%s)</span>' )]);
    register_post_status( 'wc-prep-deliv', ['label' => 'Preparing (Delivery)', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Preparing (Delivery) <span class="count">(%s)</span>', 'Preparing (Delivery) <span class="count">(%s)</span>' )]);
    register_post_status( 'wc-out-deliv', ['label' => 'Out for Delivery', 'public' => true, 'show_in_admin_all_list' => true, 'show_in_admin_status_list' => true, 'label_count' => _n_noop( 'Out for Delivery <span class="count">(%s)</span>', 'Out for Delivery <span class="count">(%s)</span>' )]);
});
add_filter( 'wc_order_statuses', function( $order_statuses ) {
    $new_statuses = [];
    foreach ( $order_statuses as $key => $status ) {
        $new_statuses[ $key ] = $status;
        if ( 'wc-processing' === $key ) {
            $new_statuses['wc-prep-pickup'] = 'Preparing (Pickup)';
            $new_statuses['wc-prep-deliv'] = 'Preparing (Delivery)';
            $new_statuses['wc-out-deliv'] = 'Out for Delivery';
        }
    }
    return $new_statuses;
});

add_action('woocommerce_order_status_changed', function($order_id, $from_status, $to_status, $order) {
    $to_status = str_replace('wc-', '', $to_status); 
    if (in_array($to_status, ['prep-pickup', 'prep-deliv', 'out-deliv'])) {
        $opt_key = str_replace('-', '_', $to_status);
        $default_subj = "Update on your order #{order_number}";
        $default_msg = "Your order status has been updated.";
        if ($to_status === 'prep-pickup') { $default_subj = "Your order #{order_number} is being prepared!"; $default_msg = "Hi {customer_name},\n\nGreat news! The kitchen at {store_name} has started preparing your order. We will let you know as soon as it is ready for pickup."; } 
        elseif ($to_status === 'prep-deliv') { $default_subj = "Your order #{order_number} is being prepared!"; $default_msg = "Hi {customer_name},\n\nGreat news! The kitchen at {store_name} has started preparing your order. We will notify you once it goes out for delivery."; } 
        elseif ($to_status === 'out-deliv') { $default_subj = "Your order #{order_number} is on the way!"; $default_msg = "Hi {customer_name},\n\nYour order from {store_name} has left the kitchen and is currently out for delivery. Keep an eye out!"; }
        $subj = get_option("mc_email_{$opt_key}_subj", $default_subj);
        $msg = get_option("mc_email_{$opt_key}_msg", $default_msg);
        $store_id = $order->get_meta('_mc_assigned_location');
        $store_name = $store_id ? get_the_title($store_id) : 'our store';
        $subj = str_replace('{order_number}', $order->get_order_number(), $subj);
        $msg = str_replace('{order_number}', $order->get_order_number(), $msg);
        $msg = str_replace('{customer_name}', $order->get_billing_first_name() ?: 'Customer', $msg);
        $msg = str_replace('{store_name}', $store_name, $msg);
        $mailer = WC()->mailer();
        $message = $mailer->wrap_message($subj, wpautop($msg));
        $mailer->send($order->get_billing_email(), $subj, $message);
    }
}, 10, 4);

require_once plugin_dir_path( __FILE__ ) . 'includes/class-mc-multistore-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-mc-locations.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-mc-frontend-locator.php';