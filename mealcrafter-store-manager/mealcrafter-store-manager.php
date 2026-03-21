<?php
/**
 * Plugin Name: MealCrafter Store Manager
 * Description: Enterprise Kitchen Portal. Multi-Store Staff Routing, Combo Extraction, Order Editing, and Field Manager.
 * Version: 8.0.0
 * Author: Sling
 */

if ( ! defined( 'ABSPATH' ) ) exit;

require_once plugin_dir_path( __FILE__ ) . 'includes/admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend-dashboard.php';

add_action( 'admin_enqueue_scripts', function($hook) {
    if ( strpos($hook, 'mc-store-manager') !== false ) wp_enqueue_media();
});

add_action('wp_ajax_mc_portal_lookup_token', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $token = sanitize_text_field($_POST['token']);
    $query = new WP_Query(['post_type' => 'shop_order', 'post_status' => 'any', 'meta_query' => [['key' => '_mc_pickup_token', 'value' => $token]]]);
    if ($query->have_posts()) { wp_send_json_success(['order_id' => $query->posts[0]->ID]); }
    wp_send_json_error(['msg' => 'Invalid or expired token.']);
});

add_action('wp_ajax_mc_portal_update_status', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    if($order) { $order->update_status(sanitize_text_field($_POST['status'])); wp_send_json_success(); }
    wp_send_json_error();
});

add_action('wp_ajax_mc_portal_discard_order', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    if($order && in_array($order->get_status(), ['draft', 'pending']) || $order->get_item_count() == 0) { wp_trash_post($order->get_id()); wp_send_json_success(); }
    wp_send_json_error(['msg' => 'Cannot discard an active order.']);
});

add_action('wp_ajax_mc_portal_create_order', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $current_user_id = get_current_user_id();
    $order = wc_create_order(['status' => 'draft']);
    $assigned_locations = get_user_meta($current_user_id, '_mc_assigned_stores', true) ?: [];
    if (!empty($assigned_locations)) { $order->update_meta_data('_mc_assigned_location', $assigned_locations[0]); }
    $order->set_customer_id($current_user_id);
    $order->set_created_via('store_manager_portal');
    $order->calculate_totals();
    $order->save();
    wp_send_json_success(['order_id' => $order->get_id()]);
});

add_action('wp_ajax_mc_frontend_heartbeat', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $last_order_id = isset($_POST['last_order_id']) ? intval($_POST['last_order_id']) : 0;
    
    $query_args = [ 'limit' => 10, 'status' => ['processing', 'pending', 'on-hold', 'wc-prep-pickup', 'wc-prep-deliv'], 'orderby' => 'id', 'order' => 'DESC' ];

    if ( ! current_user_can('administrator') ) {
        $assigned = get_user_meta(get_current_user_id(), '_mc_assigned_stores', true) ?: [];
        if ( ! empty($assigned) ) { $query_args['meta_key'] = '_mc_assigned_location'; $query_args['meta_value'] = $assigned; $query_args['meta_compare'] = 'IN'; }
    }

    $orders = wc_get_orders($query_args);
    $rule_data = [];
    
    if ( class_exists('MC_Standalone_Alerts_Engine') ) {
        $target = get_option('mc_alert_target', 'both');
        if (in_array($target, ['both', 'frontend'])) {
            $rules = get_posts(['post_type' => 'mc_alert_rule', 'numberposts' => -1]);
            foreach($rules as $r) {
                $rule_data[] = [
                    'status' => get_post_meta($r->ID, '_mc_order_status', true) ?: 'any',
                    'type'   => get_post_meta($r->ID, '_mc_order_type', true) ?: 'any',
                    'sound'  => get_post_meta($r->ID, '_mc_notification_sound', true),
                    'popup'  => get_post_meta($r->ID, '_mc_enable_popup', true) ?: 'yes',
                    'title'  => get_post_meta($r->ID, '_mc_popup_title', true) ?: 'New Order!',
                    'msg'    => get_post_meta($r->ID, '_mc_popup_message', true) ?: 'Order #[order_id] has arrived.',
                    'icon'   => get_post_meta($r->ID, '_mc_popup_icon', true),
                    'bg'     => get_post_meta($r->ID, '_mc_popup_bg_color', true) ?: '#ffffff',
                    'text'   => get_post_meta($r->ID, '_mc_popup_text_color', true) ?: '#333333',
                    'btn'    => get_post_meta($r->ID, '_mc_popup_btn_color', true) ?: '#2ecc71',
                    'loop'   => get_option('mc_alert_loop', 'on')
                ];
            }
        }
    }

    $new_orders = [];
    $pill_style = get_option('mc_status_pill_style', 'text');

    foreach($orders as $o) {
        if ($o->get_id() > $last_order_id) {
            $ship_method = $o->get_meta('_mc_order_method') ?: ($o->get_shipping_method() ?: 'Pickup');
            $new_orders[] = [
                'id' => $o->get_id(), 'status' => $o->get_status(), 
                'status_display' => ($pill_style === 'text') ? wc_get_order_status_name($o->get_status()) : '',
                'customer' => $o->get_billing_first_name().' '.$o->get_billing_last_name(),
                'items' => $o->get_item_count(), 'ship' => strtoupper($ship_method), 'city' => $o->get_shipping_city(),
                'date' => $o->get_date_created() ? $o->get_date_created()->date('M j') : 'TBD',
                'total' => $o->get_formatted_order_total()
            ];
        }
    }
    wp_send_json_success(['orders' => array_reverse($new_orders), 'rules' => $rule_data]);
});

add_action('wp_ajax_mc_portal_update_item_qty', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    $item_id = intval($_POST['item_id']); $qty = intval($_POST['qty']);
    if($order && $item_id && $qty > 0) { 
        $item = $order->get_item($item_id); 
        if ($item) { 
            $current_qty = $item->get_quantity();
            $current_total = $item->get_total();
            $unit_price = $current_qty > 0 ? ($current_total / $current_qty) : 0;
            
            $item->set_quantity($qty); 
            $item->set_subtotal($unit_price * $qty);
            $item->set_total($unit_price * $qty);
            $item->save();
            
            $order->calculate_totals(); 
            $order->save(); 
            wp_send_json_success(); 
        } 
    }
    wp_send_json_error();
});

add_action('wp_ajax_mc_portal_remove_item', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    $item_id = intval($_POST['item_id']);
    if($order && $item_id) { $order->remove_item($item_id); $order->calculate_totals(); $order->save(); wp_send_json_success(); }
    wp_send_json_error();
});

add_action('wp_ajax_mc_portal_add_item', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    $product_id = intval($_POST['product_id']);
    if($order && $product_id) {
        $product = wc_get_product($product_id);
        if ($product->get_type() === 'mc_combo' || $product->get_type() === 'mc_grouped') {
            if ( $product->get_type() === 'mc_combo' && function_exists('mc_display_combo_builder') ) {
                global $post; $post = get_post($product_id); setup_postdata($post);
                wp_send_json_success(['is_combo' => true, 'is_grouped' => false, 'html' => mc_display_combo_builder()]);
            } elseif ( $product->get_type() === 'mc_grouped' && function_exists('mc_display_grouped_product_hub') ) {
                global $post; $post = get_post($product_id); setup_postdata($post);
                wp_send_json_success(['is_combo' => false, 'is_grouped' => true, 'html' => mc_display_grouped_product_hub()]); 
            } else { wp_send_json_error(['msg' => 'Builder plugin not active.']); }
        } else { $order->add_product($product, 1); $order->calculate_totals(); $order->save(); wp_send_json_success(['is_combo' => false, 'is_grouped' => false]); }
    }
    wp_send_json_error();
});
add_action('wp_ajax_mc_portal_add_advanced_item', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    $product_id = intval($_POST['product_id']); $qty = intval($_POST['quantity']) ?: 1;
    $variation_id = isset($_POST['variation_id']) ? intval($_POST['variation_id']) : 0;
    if($order && $product_id) {
        $product = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
        $args = [];
        if ($variation_id) { $args['variation'] = []; foreach($_POST as $k => $v) { if (strpos($k, 'attribute_') === 0) { $args['variation'][$k] = sanitize_text_field($v); } } }
        $order->add_product($product, $qty, $args); $order->calculate_totals(); $order->save(); wp_send_json_success();
    }
    wp_send_json_error();
});

add_action('wp_ajax_mc_portal_edit_item_ui', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id'])); $item_id = intval($_POST['item_id']);
    if($order && $item_id) {
        $item = $order->get_item($item_id); $product_id = $item->get_product_id(); $product = wc_get_product($product_id);
        if ($product && ($product->get_type() === 'mc_combo' || $product->get_type() === 'mc_grouped')) {
            
            if ( function_exists('mc_display_combo_builder') && $product->get_type() === 'mc_combo' ) {
                global $post; $post = get_post($product_id); setup_postdata($post);
                ob_start(); echo "<div id='mc-edit-combo-injector' data-type='mc_combo' data-prefill='".esc_attr($item->get_meta('_mc_raw_combo_data'))."' data-qty='".esc_attr($item->get_quantity())."'>"; echo mc_display_combo_builder(); echo "</div>";
                wp_send_json_success(['html' => ob_get_clean(), 'product_id' => $product_id, 'is_grouped' => false]);
                
            } elseif ( function_exists('mc_display_grouped_product_hub') && $product->get_type() === 'mc_grouped' ) {
                global $post; $post = get_post($product_id); setup_postdata($post);
                ob_start(); echo "<div id='mc-edit-combo-injector' data-type='mc_grouped' data-prefill='".esc_attr($item->get_meta('_mc_raw_combo_data'))."' data-qty='".esc_attr($item->get_quantity())."'>"; echo mc_display_grouped_product_hub(); echo "</div>";
                wp_send_json_success(['html' => ob_get_clean(), 'product_id' => $product_id, 'is_grouped' => true]);
            }
        }
    }
    wp_send_json_error();
});

add_action('wp_ajax_mc_portal_save_combo_to_order', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id'])); 
    $product_id = intval($_POST['product_id']);
    
    // Grab both potential arrays
    $combo_data = isset($_POST['mc_combo_items']) ? $_POST['mc_combo_items'] : []; 
    $grouped_data = isset($_POST['mc_grouped_items']) ? $_POST['mc_grouped_items'] : [];
    $replace_item_id = isset($_POST['replace_item_id']) ? intval($_POST['replace_item_id']) : 0;
    
    if($order && $product_id) {
        $product = wc_get_product($product_id); 
        $is_grouped = $product->get_type() === 'mc_grouped';
        $is_combo = $product->get_type() === 'mc_combo';
        
        if ( ($is_combo && empty($combo_data)) || ($is_grouped && empty($grouped_data)) ) {
            wp_send_json_error(['msg' => 'Missing selection data.']);
        }
        
        if ($replace_item_id > 0) { $order->remove_item($replace_item_id); }
        
        $final_price = (float)$product->get_price();
        $slots = []; 
        $raw_data = [];
        
        if ($is_combo) {
            if (class_exists('MC_Combo_Cart_Security') && get_option('mc_combo_math_logic', 'on') === 'on') {
                $highest = 0; foreach($combo_data as $sel) { $p = wc_get_product(is_array($sel) ? $sel['id'] : $sel); if ($p && (float)$p->get_price() > $highest) { $highest = (float)$p->get_price(); } }
                $final_price += $highest;
            }
            $product->set_price($final_price);
            $item_id = $order->add_product($product, 1); $item = $order->get_item($item_id);
            $charged_highest = false;
            foreach ($combo_data as $sel) {
                $id = is_array($sel) ? $sel['id'] : $sel; $slot_name = (is_array($sel) && isset($sel['slot'])) ? $sel['slot'] : 'Selections'; $p = wc_get_product($id);
                if ($p) {
                    $price = (float) $p->get_price(); $price_text = '';
                    if ($price > 0) {
                        if (get_option('mc_combo_math_logic', 'on') === 'on') { if ($price == $highest && !$charged_highest) { $price_text = ' <strong style="color:#e74c3c;">(+$' . number_format($price, 2) . ')</strong>'; $charged_highest = true; } else { $price_text = ' <span style="color:#aaa;"><del>(+$' . number_format($price, 2) . ')</del> <small>Waived</small></span>'; } } else { $price_text = ' (+$' . number_format($price, 2) . ')'; }
                    }
                    $img = wp_get_attachment_image_url($p->get_image_id(), 'thumbnail'); $name = esc_html($p->get_name()) . $price_text;
                    if ($img) { $name = '<span class="mc-combo-hover-item">' . $name . '<img src="'.esc_url($img).'" class="mc-combo-hover-img" style="max-width:50px; max-height:50px;"></span>'; }
                    $slots[$slot_name][] = $name;
                }
            }
            foreach ($slots as $slot_name => $items) { $item->add_meta_data(sanitize_text_field($slot_name), implode(', ', $items), true); }
            $item->add_meta_data('_mc_raw_combo_data', wp_json_encode($combo_data), true);
            
        } elseif ($is_grouped) {
            $product->set_price($final_price);
            $item_id = $order->add_product($product, 1); $item = $order->get_item($item_id);
            foreach ($grouped_data as $id) {
                $p = wc_get_product($id);
                if ($p) {
                    $raw_data[] = $id;
                    $price = (float) $p->get_price();
                    $price_text = $price > 0 ? ' (+$' . number_format($price, 2) . ')' : '';
                    $img_url = wp_get_attachment_image_url($p->get_image_id(), 'thumbnail'); 
                    $name = esc_html($p->get_name()) . $price_text;
                    if ($img_url) { $name = '<span class="mc-combo-hover-item">' . $name . '<img src="'.esc_url($img_url).'" class="mc-combo-hover-img" style="max-width:50px; max-height:50px;"></span>'; }
                    $slots['Included Items'][] = $name;
                }
            }
            foreach ($slots as $slot_name => $items) { $item->add_meta_data($slot_name, implode(', ', $items), true); }
            $item->add_meta_data('_mc_raw_combo_data', wp_json_encode($raw_data), true);
        }
        
        if (isset($item)) {
            $item->save(); $order->calculate_totals(); $order->save(); wp_send_json_success();
        }
    }
    wp_send_json_error();
});

add_action('wp_ajax_mc_portal_update_fulfillment', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    if($order) {
        if(isset($_POST['loc_id'])) $order->update_meta_data('_mc_assigned_location', intval($_POST['loc_id']));
        if(isset($_POST['method'])) $order->update_meta_data('_mc_order_method', sanitize_text_field($_POST['method']));
        if(isset($_POST['date'])) $order->update_meta_data('_mc_order_date', sanitize_text_field($_POST['date']));
        if(isset($_POST['time'])) $order->update_meta_data('_mc_order_time', sanitize_text_field($_POST['time']));
        $order->save(); wp_send_json_success();
    }
    wp_send_json_error();
});

add_action('wp_ajax_mc_get_order_details', function() {
    check_ajax_referer('mc_portal_secure', 'security');
    $order = wc_get_order(intval($_POST['order_id']));
    if(!$order) wp_send_json_error();
    
    $can_edit_items = get_option('mc_enable_item_editing', 'yes') === 'yes';
    $can_edit_fulfill = get_option('mc_enable_fulfill_editing', 'yes') === 'yes';

    $show_item_images = get_option('mc_show_item_images', 'yes');

    $all_statuses = wc_get_order_statuses();
    $all_statuses['wc-prep-pickup'] = 'Preparing (Pickup)';
    $all_statuses['wc-prep-deliv'] = 'Preparing (Delivery)';
    $all_statuses['wc-out-deliv'] = 'Out for Delivery';
    
    $allowed_keys = get_option('mc_manager_allowed_statuses', array_keys($all_statuses));
    $statuses = []; foreach($allowed_keys as $k) { if(isset($all_statuses[$k])) $statuses[$k] = $all_statuses[$k]; }

    $ship_method = $order->get_meta('_mc_order_method') ?: ($order->get_shipping_method() ?: 'Pickup');
    $is_delivery = (stripos($ship_method, 'delivery') !== false);
    $ship_color = $is_delivery ? get_option('mc_color_delivery', '#e74c3c') : get_option('mc_color_pickup', '#2ecc71');

    $is_paid_online = $order->get_date_paid() ? true : false;
    if ($order->get_payment_method() === 'cod') $is_paid_online = false;
    $pay_text = $is_paid_online ? 'PAID ONLINE' : 'PAY AT STORE';
    $pay_color = $is_paid_online ? get_option('mc_color_paid', '#2ecc71') : get_option('mc_color_unpaid', '#f39c12');

    $show_fields = get_option('mc_details_visibility', ['email', 'phone', 'address', 'meta', 'fulfillment', 'badge_store', 'badge_time']);
    
    $loc_id = $order->get_meta('_mc_assigned_location');
    $store_name = $loc_id ? get_the_title($loc_id) : 'Unassigned Store';
    $loc_date = $order->get_meta('_mc_order_date');
    $loc_time = (string) $order->get_meta('_mc_order_time');
    
    $display_date = $loc_date ? date('M j', strtotime($loc_date)) : 'TBD';
    $display_time = (strpos($loc_time, 'ASAP') !== false) ? 'ASAP' : ($loc_time ? date('g:i A', strtotime($loc_time)) : 'TBD');

    ob_start(); ?>
    <div class="mc-order-details-render" style="position:relative;">
        <div id="mc-success-overlay" style="display:none; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.98); z-index:200; flex-direction:column; align-items:center; justify-content:center; text-align:center;">
            <div style="background:#2ecc71; color:#fff; width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:40px; margin-bottom:20px;">✓</div>
            <h2 style="color:var(--mc-text-main); margin:0; font-weight:900;">Order Updated!</h2>
            <button class="mc-back-to-dash" style="margin-top:20px; background:var(--mc-primary); color:#fff; border:none; padding:15px 40px; border-radius:10px; font-weight:900; cursor:pointer;">BACK TO DASHBOARD</button>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:2px solid var(--mc-border-color); padding-bottom:15px; margin-bottom:25px;">
            <h2 style="margin:0; font-weight:900; font-size:24px; color:var(--mc-text-main);">Order #<?php echo $order->get_id(); ?></h2>
            <div style="display:flex; gap:10px; align-items:center;">
                <?php if (in_array($order->get_status(), ['draft', 'pending']) || $order->get_item_count() == 0): ?>
                    <button class="mc-btn-discard" data-id="<?php echo $order->get_id(); ?>" style="background:transparent; color:#e74c3c; border:2px solid #e74c3c; padding:10px 15px; border-radius:8px; font-weight:900; cursor:pointer; transition:0.2s;">DISCARD DRAFT</button>
                <?php endif; ?>
                <div class="mc-status-dropdown-wrap">
                    <select id="mc-new-status-val">
                        <?php foreach($statuses as $slug => $name) echo "<option value='".str_replace('wc-', '', $slug)."' ".selected($order->get_status(), str_replace('wc-', '', $slug), false).">$name</option>"; ?>
                    </select>
                </div>
                <button class="mc-btn-update" data-id="<?php echo $order->get_id(); ?>" style="background:#2ecc71; color:#fff; border:none; padding:12px 25px; border-radius:8px; font-weight:900; cursor:pointer;">UPDATE STATUS</button>
            </div>
        </div>
        
        <div style="display:grid; grid-template-columns: 1.8fr 1fr; gap:30px;">
            <div class="mc-items-panel mc-theme-card">
                <h3 style="margin-top:0; border-bottom:2px solid var(--mc-border-dark); padding-bottom:10px; color:var(--mc-text-main);">Order Items</h3>
                <table style="width:100%; border-collapse:collapse; text-align:left; color:var(--mc-text-main);">
                    <tbody>
                        <?php foreach($order->get_items() as $item_id => $item): 
                            $prod = $item->get_product(); 
                            $is_combo = ($prod && ($prod->get_type() === 'mc_combo'));
                            $is_grouped = ($prod && ($prod->get_type() === 'mc_grouped'));
                            $has_raw_data = $item->get_meta('_mc_raw_combo_data'); 
                            
                            $main_img_html = '';
                            if ( $show_item_images === 'yes' && $prod ) {
                                $img_id = $prod->get_image_id();
                                if ( $img_id ) {
                                    $img_url = wp_get_attachment_image_url($img_id, 'thumbnail');
                                    if ( $img_url ) {
                                        $main_img_html = '<img src="'.esc_url($img_url).'" style="width:45px; height:45px; object-fit:cover; border-radius:6px; border:1px solid var(--mc-border-color); margin-right:15px; flex-shrink:0; background:#fff;">';
                                    }
                                }
                            }
                        ?>
                            <tr style="border-bottom:1px solid var(--mc-border-color);">
                                <td style="padding:20px 0;">
                                    <div style="display:flex; align-items:center;">
                                        <?php echo $main_img_html; ?>
                                        <div style="flex:1;">
                                            <strong style="font-size:16px; display:block;"><?php echo $item->get_name(); ?></strong>
                                            <?php 
                                            if(in_array('meta', $show_fields)): 
                                                $meta_html = wc_display_item_meta( $item, ['echo' => false] );
                                                $meta_html = preg_replace('/<li.*_mc_raw_combo_data.*<\/li>/isU', '', $meta_html);
                                                if(trim($meta_html) !== '' && trim(strip_tags($meta_html)) !== ''): 
                                                    echo '<div class="mc-combo-meta-wrapper" style="margin-top:8px; background:var(--mc-bg-alt); padding:12px; border-radius:8px; border-left:4px solid var(--mc-primary); color:var(--mc-text-muted);">';
                                                    echo $meta_html; 
                                                    echo '</div>'; 
                                                endif;
                                            endif; ?>
                                        </div>
                                    </div>
                                </td>
                                
                                <td style="font-size:15px; font-weight:800; text-align:center; white-space:nowrap;">
                                    <?php if ($can_edit_items): ?>
                                        <div style="display:flex; align-items:center; gap:5px; justify-content:center;">
                                            <input type="number" class="mc-qty-input" value="<?php echo $item->get_quantity(); ?>" style="width:50px; padding:5px; text-align:center; border:1px solid var(--mc-border-color); border-radius:4px; background:var(--mc-bg-card); color:var(--mc-text-main);" min="1">
                                            <span class="dashicons dashicons-saved mc-save-qty-btn" data-order="<?php echo $order->get_id(); ?>" data-item="<?php echo $item_id; ?>" style="color:#2ecc71; cursor:pointer;" title="Update Quantity"></span>
                                        </div>
                                    <?php else: ?>
                                        x<?php echo $item->get_quantity(); ?>
                                    <?php endif; ?>
                                </td>
                                
                                <td style="text-align:right; font-weight:700; padding-right:15px;"><?php echo wc_price($item->get_total()); ?></td>
                                <?php if ($can_edit_items): ?>
                                <td style="text-align:right; width:60px; white-space:nowrap;">
                                    <?php if(($is_combo || $is_grouped) && $has_raw_data): ?>
                                        <span class="dashicons dashicons-edit mc-edit-item" data-order="<?php echo $order->get_id(); ?>" data-item="<?php echo $item_id; ?>" style="color:#3498db; cursor:pointer; margin-right:5px;" title="Edit Selections"></span>
                                    <?php endif; ?>
                                    <span class="dashicons dashicons-trash mc-remove-item" data-order="<?php echo $order->get_id(); ?>" data-item="<?php echo $item_id; ?>" style="color:#e74c3c; cursor:pointer;" title="Remove Item"></span>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($can_edit_items): ?>
                <div style="margin-top:20px; display:flex; gap:10px; background:var(--mc-bg-alt); padding:15px; border-radius:8px; align-items:center;">
                    <span class="dashicons dashicons-plus-alt2" style="color:var(--mc-primary);"></span>
                    <select id="mc-add-product-select" style="flex:1; padding:8px; border-radius:4px; border:1px solid var(--mc-border-color); background:var(--mc-bg-card); color:var(--mc-text-main);">
                        <option value="" data-type="simple">-- Quick Add Product / Combo --</option>
                        <?php 
                        $qadd_mode = get_option('mc_qadd_mode', 'products');
                        $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish'];
                        
                        if ($qadd_mode !== 'all') {
                            $product_ids = [];
                            if (in_array($qadd_mode, ['categories', 'both'])) {
                                $allowed_cats = get_option('mc_quick_add_cats', []);
                                if (!empty($allowed_cats)) {
                                    $cat_prods = get_posts(['post_type'=>'product', 'posts_per_page'=>-1, 'fields'=>'ids', 'tax_query'=>[['taxonomy'=>'product_cat', 'field'=>'term_id', 'terms'=>$allowed_cats]]]);
                                    $product_ids = array_merge($product_ids, $cat_prods);
                                }
                            }
                            if (in_array($qadd_mode, ['products', 'both'])) {
                                $allowed_prods = get_option('mc_quick_add_products', []);
                                if (!empty($allowed_prods)) { $product_ids = array_merge($product_ids, $allowed_prods); }
                            }
                            if (empty($product_ids)) { $product_ids = [0]; }
                            $args['post__in'] = $product_ids;
                        }

                        $products = get_posts($args);
                        foreach($products as $p) {
                            $prod = wc_get_product($p->ID);
                            echo '<option value="'.$p->ID.'" data-type="'.$prod->get_type().'">'.$p->post_title.'</option>';
                        }
                        ?>
                    </select>
                    <button class="mc-add-item-btn" data-order="<?php echo $order->get_id(); ?>" style="background:var(--mc-primary); color:#fff; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; font-weight:bold;">ADD</button>
                </div>
                <?php endif; ?>

                <div style="margin-top:30px; border-top:2px solid var(--mc-border-color); padding-top:15px; width:100%;">
                    <table style="width:250px; margin-left:auto; text-align:right; font-size:15px; color:var(--mc-text-muted);">
                        <tr><td style="padding:5px 0;">Subtotal:</td><td style="color:var(--mc-text-main);"><?php echo wc_price($order->get_subtotal()); ?></td></tr>
                        <?php if($order->get_total_tax() > 0): ?><tr><td style="padding:5px 0;">Tax:</td><td style="color:var(--mc-text-main);"><?php echo wc_price($order->get_total_tax()); ?></td></tr><?php endif; ?>
                        <?php foreach( $order->get_items('fee') as $fee_id => $fee ): ?>
                            <tr><td style="padding:5px 0;"><?php echo esc_html($fee->get_name()); ?>:</td><td style="color:var(--mc-text-main);"><?php echo wc_price($fee->get_total()); ?></td></tr>
                        <?php endforeach; ?>
                        <?php if($order->get_shipping_total() > 0): ?><tr><td style="padding:5px 0;">Shipping:</td><td style="color:var(--mc-text-main); font-weight:bold;"><?php echo wc_price($order->get_shipping_total()); ?></td></tr><?php endif; ?>
                        <tr style="font-size:22px; color:var(--mc-text-main);"><td style="padding:10px 0;"><strong>TOTAL:</strong></td><td><strong><?php echo $order->get_formatted_order_total(); ?></strong></td></tr>
                    </table>
                </div>
            </div>

            <div class="mc-info-panel" style="display:flex; flex-direction:column; gap:20px;">
                <div style="display:flex; gap:10px;">
                    <div style="flex:1; background:<?php echo $ship_color; ?>; color:#fff; text-align:center; padding:15px 10px; border-radius:10px; box-shadow:0 4px 10px <?php echo $ship_color; ?>40; display:flex; flex-direction:column; justify-content:center;">
                        <?php if(in_array('badge_store', $show_fields)): ?>
                            <span style="font-size:11px; text-transform:uppercase; opacity:0.9; margin-bottom:3px;"><?php echo esc_html($store_name); ?></span>
                        <?php endif; ?>
                        <strong style="font-size:16px; font-weight:900; letter-spacing:1px;"><?php echo strtoupper($ship_method); ?></strong>
                        <?php if(in_array('badge_time', $show_fields)): ?>
                            <div style="font-size:12px; font-weight:bold; margin-top:6px; opacity:0.95; display:flex; align-items:center; justify-content:center; gap:5px;"><i class="dashicons dashicons-calendar-alt" style="font-size:14px; width:14px; height:14px;"></i> <?php echo esc_html($display_date); ?></div>
                            <div style="font-size:12px; font-weight:bold; margin-top:2px; opacity:0.95; display:flex; align-items:center; justify-content:center; gap:5px;"><i class="dashicons dashicons-clock" style="font-size:14px; width:14px; height:14px;"></i> <?php echo esc_html($display_time); ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1; background:<?php echo $pay_color; ?>; color:#fff; text-align:center; padding:10px; border-radius:10px; font-weight:900; font-size:13px; box-shadow:0 4px 10px <?php echo $pay_color; ?>40; display:flex; flex-direction:column; justify-content:center;">
                        <?php echo $pay_text; ?>
                        <span style="font-weight:normal; font-size:11px; opacity:0.9; margin-top:3px;"><?php echo $order->get_payment_method_title(); ?></span>
                    </div>
                </div>

                <?php if(in_array('fulfillment', $show_fields)): ?>
                <div class="mc-theme-card" style="position:relative;">
                    <h4 style="margin:0 0 15px; text-transform:uppercase; font-size:12px; color:var(--mc-text-muted); letter-spacing:1px; border-bottom:1px solid var(--mc-border-color); padding-bottom:10px;">
                        Fulfillment Details
                        <?php if ($can_edit_fulfill): ?>
                            <span id="mc-toggle-edit-fulfill" style="float:right; color:var(--mc-primary); cursor:pointer;">Edit</span>
                        <?php endif; ?>
                    </h4>
                    <div id="mc-fulfill-static" style="color:var(--mc-text-main);">
                        <p style="margin:0 0 8px;"><strong>Store:</strong> <?php echo $store_name; ?></p>
                        <p style="margin:0 0 8px;"><strong>Method:</strong> <?php echo strtoupper($ship_method); ?></p>
                        <p style="margin:0 0 8px;"><strong>Date:</strong> <?php echo $display_date; ?></p>
                        <p style="margin:0;"><strong>Time:</strong> <?php echo $display_time; ?></p>
                    </div>
                    
                    <?php if ($can_edit_fulfill): ?>
                    <div id="mc-fulfill-edit" style="display:none; background:var(--mc-bg-alt); padding:15px; border-radius:8px; margin-top:10px;">
                        <label style="font-size:12px; font-weight:bold; color:var(--mc-text-main);">Store Location:</label>
                        <select id="mc-edit-loc-id" style="width:100%; margin-bottom:10px; padding:8px; background:var(--mc-bg-card); color:var(--mc-text-main); border:1px solid var(--mc-border-color);">
                            <?php $stores = get_posts(['post_type'=>'mc_location','posts_per_page'=>-1]);
                            foreach($stores as $s) echo '<option value="'.$s->ID.'" '.selected($loc_id, $s->ID, false).'>'.$s->post_title.'</option>'; ?>
                        </select>
                        <label style="font-size:12px; font-weight:bold; color:var(--mc-text-main);">Method:</label>
                        <select id="mc-edit-loc-method" style="width:100%; margin-bottom:10px; padding:8px; background:var(--mc-bg-card); color:var(--mc-text-main); border:1px solid var(--mc-border-color);">
                            <option value="pickup" <?php selected($ship_method, 'pickup'); ?>>Pickup</option>
                            <option value="delivery" <?php selected($ship_method, 'delivery'); ?>>Delivery</option>
                        </select>
                        <label style="font-size:12px; font-weight:bold; color:var(--mc-text-main);">Date:</label>
                        <input type="date" id="mc-edit-loc-date" value="<?php echo esc_attr($loc_date); ?>" style="width:100%; margin-bottom:10px; padding:8px; background:var(--mc-bg-card); color:var(--mc-text-main); border:1px solid var(--mc-border-color);">
                        <label style="font-size:12px; font-weight:bold; color:var(--mc-text-main);">Time:</label>
                        <input type="text" id="mc-edit-loc-time" value="<?php echo esc_attr($loc_time); ?>" style="width:100%; margin-bottom:15px; padding:8px; background:var(--mc-bg-card); color:var(--mc-text-main); border:1px solid var(--mc-border-color);" placeholder="e.g. 14:30 or ASAP">
                        <button class="mc-save-fulfill-btn" data-order="<?php echo $order->get_id(); ?>" style="background:#2ecc71; color:#fff; border:none; padding:8px 15px; border-radius:4px; cursor:pointer; width:100%; font-weight:bold;">SAVE DETAILS</button>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="mc-theme-card">
                    <h4 style="margin:0 0 10px; text-transform:uppercase; font-size:12px; color:var(--mc-text-muted); letter-spacing:1px;">Customer Info</h4>
                    <p style="font-size:18px; font-weight:700; margin:0; color:var(--mc-text-main);"><?php echo $order->get_billing_first_name().' '.$order->get_billing_last_name(); ?></p>
                    <?php if(in_array('email', $show_fields)) echo "<p style='margin:5px 0; color:var(--mc-text-muted);'>{$order->get_billing_email()}</p>"; ?>
                    <?php if(in_array('phone', $show_fields)) echo "<p style='margin:5px 0; font-weight:bold; color:var(--mc-primary);'><a href='tel:{$order->get_billing_phone()}' style='color:inherit; text-decoration:none;'>{$order->get_billing_phone()}</a></p>"; ?>
                    <?php if(in_array('address', $show_fields) && $order->get_billing_address_1()): ?>
                        <h4 style="margin:20px 0 10px; text-transform:uppercase; font-size:12px; color:var(--mc-text-muted); letter-spacing:1px;">Destination</h4>
                        <p style="font-size:14px; font-weight:600; line-height:1.4; margin:0; color:var(--mc-text-main);"><?php echo $order->get_billing_address_1(); ?><br><?php if($order->get_billing_address_2()) echo $order->get_billing_address_2().'<br>'; ?><?php echo $order->get_billing_city(); ?></p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
    <?php wp_send_json_success(ob_get_clean());
});
