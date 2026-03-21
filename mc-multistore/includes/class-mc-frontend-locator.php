<?php
/**
 * MealCrafter: Frontend Locator UI & Drawer System
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Frontend_Locator {

    public function __construct() {
        add_shortcode('mc_active_store_btn', [$this, 'render_header_button']);
        add_shortcode('mc_store_locator', [$this, 'render_main_locator']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
    }

    public function enqueue_frontend_scripts() {
        global $post;
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'mc_store_locator') ) {
            $api_key = get_option('mc_gmaps_api_key');
            if ($api_key) wp_enqueue_script('google-maps-frontend', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=geometry,places', [], null, true);
        }
    }

    private function get_safe_session_data($key) {
        $val = '';
        try {
            if ( function_exists('WC') && is_object(WC()) && isset(WC()->session) && is_object(WC()->session) && method_exists(WC()->session, 'get') ) {
                $val = WC()->session->get($key);
            }
        } catch (\Exception $e) {} catch (\Error $e) {}

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
            }
        }
        return $val;
    }

    public function render_header_button($atts) {
        $dynamic_url = get_option('mc_redirect_page_id') ? get_permalink(get_option('mc_redirect_page_id')) : '#';
        $atts = shortcode_atts(['locator_url' => $dynamic_url], $atts);
        
        $primary = get_option('mc_design_primary_color', '#e74c3c');
        $btn_pad = get_option('mc_btn_padding', '8px 20px 8px 10px');
        $btn_rad = get_option('mc_btn_radius', '50');
        $icon_url = get_option('mc_btn_icon_url');
        $icon_size = get_option('mc_btn_icon_size', '20');
        
        // NEW CUSTOMIZATION OPTIONS
        $btn_bg = get_option('mc_btn_bg_color', '#ffffff');
        $btn_text_top = get_option('mc_btn_text_top_color', '#666666');
        $btn_text_bot = get_option('mc_btn_text_bot_color', $primary); 
        $btn_icon_bg = get_option('mc_btn_icon_bg_color', $primary); 
        
        $btn_font = get_option('mc_btn_font_family', 'inherit');
        $top_weight = get_option('mc_btn_text_top_weight', '700');
        $bot_weight = get_option('mc_btn_text_bot_weight', '900');
        
        $top_text = get_option('mc_btn_default_top', 'SELECT STORE');
        $bot_text = get_option('mc_btn_default_bottom', 'FIND YOUR LOCATION');
        
        $loc_id = $this->get_safe_session_data('mc_active_location');
        $method = $this->get_safe_session_data('mc_active_method');
        
        if ( $loc_id && get_option('mc_btn_show_location', 'yes') === 'yes' ) {
            $top_text = get_option('mc_btn_show_method', 'yes') === 'yes' && !empty($method) ? strtoupper((string)$method) . ' FROM' : 'SELECTED STORE';
            $bot_text = strtoupper((string)get_the_title($loc_id));
        }

        ob_start();
        ?>
        <style>
            <?php if ($btn_font !== 'inherit'): ?>
            @import url('https://fonts.googleapis.com/css2?family=<?php echo urlencode($btn_font); ?>:wght@400;500;600;700;800;900&display=swap');
            <?php endif; ?>
            
            .mc-header-btn { 
                font-family: <?php echo $btn_font !== 'inherit' ? "'" . esc_attr($btn_font) . "', sans-serif" : 'inherit'; ?>; 
                display: inline-flex; 
                align-items: center; 
                gap: 12px; 
                background: <?php echo esc_attr($btn_bg); ?>; 
                border-radius: <?php echo esc_attr($btn_rad); ?>px; 
                padding: <?php echo esc_attr($btn_pad); ?>; 
                text-decoration: none; 
                box-shadow: 0 4px 10px rgba(0,0,0,0.08); 
                cursor: pointer; 
            }
            .mc-header-icon-wrap { 
                background: <?php echo esc_attr($btn_icon_bg); ?>; 
                color: #fff; 
                width: 36px; 
                height: 36px; 
                border-radius: <?php echo esc_attr($btn_rad > 18 ? 50 : $btn_rad); ?>px; 
                display: flex; 
                align-items: center; 
                justify-content: center; 
                overflow: hidden; 
            }
            .mc-header-icon-wrap img {
                width: <?php echo esc_attr($icon_size); ?>px;
                height: <?php echo esc_attr($icon_size); ?>px;
                object-fit: contain; 
                display: block;
            }
            .mc-header-text-wrap { display: flex; flex-direction: column; line-height: 1.2; }
            .mc-header-top { font-size: 11px; color: <?php echo esc_attr($btn_text_top); ?>; font-weight: <?php echo esc_attr($top_weight); ?>; text-transform: uppercase; }
            .mc-header-bot { font-size: 14px; color: <?php echo esc_attr($btn_text_bot); ?>; font-weight: <?php echo esc_attr($bot_weight); ?>; text-transform: uppercase; }
        </style>
        <a href="<?php echo esc_url($atts['locator_url']); ?>" class="mc-header-btn mc-locator-trigger">
            <div class="mc-header-icon-wrap">
                <?php if($icon_url): ?>
                    <img src="<?php echo esc_url($icon_url); ?>" alt="Store Icon">
                <?php else: ?>
                    <span class="dashicons dashicons-location-alt" style="font-size:<?php echo esc_attr($icon_size); ?>px; width:<?php echo esc_attr($icon_size); ?>px; height:<?php echo esc_attr($icon_size); ?>px;"></span>
                <?php endif; ?>
            </div>
            <div class="mc-header-text-wrap"><span class="mc-header-top"><?php echo esc_html($top_text); ?></span><span class="mc-header-bot mc-dyn-loc-name"><?php echo esc_html($bot_text); ?></span></div>
        </a>
        <?php
        return ob_get_clean();
    }

    public function render_main_locator() {
        $primary = get_option('mc_design_primary_color', '#e74c3c');
        $success = get_option('mc_design_success_color', '#2ecc71');
        
        $bg_hex = get_option('mc_design_bg_color', '#fafafa');
        $bg_opc = get_option('mc_design_bg_opacity', '100') / 100;
        list($r, $g, $b) = sscanf($bg_hex, "#%02x%02x%02x");
        $bg_rgba = "rgba($r, $g, $b, $bg_opc)";

        $rad = get_option('mc_design_radius', '12');
        $card_pad = get_option('mc_design_card_padding', '20');
        $card_mar = get_option('mc_design_card_margin', '15');
        
        $step_1_title = get_option('mc_step_1_title', 'When do you want your food?');
        $show_map = get_option('mc_feature_show_map', 'yes');
        $map_mode = get_option('mc_map_center_mode', 'store_only'); 
        $map_zoom = get_option('mc_map_zoom', '13');
        $map_user_title = get_option('mc_map_user_title', 'My Location');
        $map_store_title = get_option('mc_map_store_title', 'Closest Store');
        
        $show_pickup_addr = get_option('mc_feature_pickup_address', 'no');
        $interaction = get_option('mc_ui_interaction', 'inline');
        $method_style = get_option('mc_method_style', 'toggle');
        $method_align = get_option('mc_method_align', 'center');
        $fallback_url = get_option('mc_fallback_url', '');

        $show_addr = get_option('mc_card_show_address', 'yes');
        $show_phone = get_option('mc_card_show_phone', 'yes');
        $show_dir = get_option('mc_card_show_directions', 'yes');
        $show_contact = get_option('mc_card_show_contact', 'yes');

        $global_prep = intval(get_option('mc_global_pickup_interval', '15'));
        $current_day = date('l', current_time('timestamp'));
        $current_time = current_time('timestamp');
        $close_warn_buffer = intval(get_option('mc_closes_soon_buffer', '60')) * 60;
        $last_order_buffer = intval(get_option('mc_last_order_buffer', '30')) * 60;

        $locations = get_posts(['post_type' => 'mc_location', 'posts_per_page' => -1]);
        $has_delivery = false;

        ob_start();
        ?>
        <style>
            .mc-combo-card img, .mc-hub-card img, .woocommerce-cart-form__cart-item img {
                width: 100% !important; height: auto !important; max-height: 150px; object-fit: contain !important;
            }

            .mc-loc-app { font-family: 'Inter', sans-serif; background: <?php echo $bg_rgba; ?>; border-radius: <?php echo esc_attr($rad); ?>px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); width: 100%; max-width: 1200px; margin: 0 auto; box-sizing: border-box; }
            .mc-step-title { font-size: 20px; font-weight: 900; color: #222; text-align: center; margin-bottom: 20px; }
            
            .mc-loc-method-wrap { display: flex; justify-content: <?php echo esc_attr($method_align); ?>; margin-bottom: 20px; }
            .mc-loc-toggle { display: inline-flex; background: #eee; border-radius: 50px; padding: 5px; }
            .mc-loc-tab { padding: 12px 30px; border-radius: 50px; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; color: #666; text-transform: uppercase; }
            .mc-loc-tab.active { background: <?php echo esc_attr($primary); ?>; color: #fff; }
            .mc-loc-select { padding: 12px 20px; font-size: 16px; border-radius: 8px; border: 2px solid #ddd; outline: none; font-weight: bold; color: #333; margin-bottom: 20px; }
            
            .mc-search-grid { display: grid; gap: 15px; margin-bottom: 30px; }
            .mc-loc-search-box { background: #fff; border: 2px solid #ddd; border-radius: 8px; padding: 10px 20px; display: flex; align-items: center; }
            .mc-loc-search-box input { border: none; outline: none; flex: 1; font-size: 16px; padding: 5px; }
            .mc-loc-geo-btn { color: <?php echo esc_attr($primary); ?>; cursor: pointer; font-weight: bold; font-size: 13px; display: flex; align-items: center; gap: 5px; }
            
            .mc-datetime-bar { display: grid; grid-template-columns: 1fr 1.5fr; gap: 15px; align-items: center; }
            .mc-datetime-bar input, .mc-datetime-bar select { padding: 12px 20px; border-radius: 8px; border: 2px solid #ddd; font-weight: bold; font-family: inherit; font-size: 15px; outline: none; width: 100%; box-sizing: border-box; }
            .mc-time-ui-wrapper { display: flex; gap: 10px; align-items: center; }
            .mc-time-toggle-btn { padding: 12px 20px; border-radius: 8px; border: 2px solid #ddd; background: #fff; font-weight: bold; cursor: pointer; transition: 0.2s; white-space: nowrap; }
            .mc-time-toggle-btn.active { border-color: <?php echo esc_attr($primary); ?>; color: <?php echo esc_attr($primary); ?>; background: <?php echo esc_attr($primary); ?>10; }
            
            @media(max-width: 768px) {
                .mc-datetime-bar { grid-template-columns: 1fr; gap: 10px; }
                .mc-time-ui-wrapper { display: flex; flex-direction: row; width: 100%; gap: 5px; }
                .mc-time-toggle-btn { flex: 1; text-align: center; }
                .mc-loc-grid { grid-template-columns: 1fr; }
            }

            .mc-submit-search { background: <?php echo esc_attr($primary); ?>; color: #fff; padding: 15px; border: none; border-radius: 8px; font-weight: 900; cursor: pointer; transition: 0.2s; text-transform: uppercase; width: 100%; font-size: 16px; margin-top: 15px; }
            
            .mc-loc-grid { display: grid; gap: 30px; grid-template-columns: <?php echo $show_map === 'yes' ? '1fr 1.5fr' : '1fr'; ?>; }
            
            .mc-store-card { background: #fff; border: 2px solid #eee; border-radius: <?php echo esc_attr($rad); ?>px; padding: <?php echo esc_attr($card_pad); ?>px; margin-bottom: <?php echo esc_attr($card_mar); ?>px; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
            .mc-store-card:hover { border-color: <?php echo esc_attr($primary); ?>; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
            .mc-store-name { font-size: 18px; font-weight: 900; color: #222; }
            .mc-store-meta { font-size: 13px; color: #888; margin-top: 5px; line-height: 1.4; }
            .mc-store-status { font-size: 11px; font-weight: 900; padding: 4px 8px; border-radius: 4px; display: inline-block; margin-top: 8px; text-transform: uppercase; }
            
            .mc-card-links { display: flex; gap: 15px; margin-top: 10px; font-size: 12px; font-weight: bold; }
            .mc-card-links a { color: <?php echo esc_attr($primary); ?>; text-decoration: none; display: flex; align-items: center; gap: 3px; }
            
            .mc-store-select-btn { background: <?php echo esc_attr($primary); ?>; color: #fff; border: none; padding: 12px 25px; border-radius: 50px; font-weight: 900; cursor: pointer; transition: 0.2s; text-transform: uppercase; font-size: 13px; white-space: nowrap; min-width: 100px; text-align: center; }
            
            .mc-map-wrapper { position: relative; }
            #mc-frontend-map { background: #e0e0e0; border-radius: <?php echo esc_attr($rad); ?>px; min-height: 500px; width: 100%; position: sticky; top: 20px; }
            .mc-map-focus-toggle { position: absolute; top: 15px; right: 15px; z-index: 5; background: #fff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); display: flex; overflow: hidden; }
            .mc-map-focus-btn { padding: 8px 15px; font-size: 12px; font-weight: bold; border: none; background: #fff; cursor: pointer; transition: 0.2s; }
            .mc-map-focus-btn.active { background: <?php echo esc_attr($primary); ?>; color: #fff; }

            <?php if ($interaction !== 'inline'): ?>
            .mc-loc-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 99990; display: none; opacity: 0; transition: 0.3s; }
            .mc-loc-overlay.active { display: block; opacity: 1; }
            .mc-loc-container { position: fixed; background: #fff; z-index: 99999; transition: 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); box-shadow: 0 0 40px rgba(0,0,0,0.2); overflow-y: auto; }
            .mc-style-slide_right .mc-loc-container { top: 0; right: -600px; width: 600px; max-width: 100%; height: 100vh; }
            .mc-style-slide_right.active .mc-loc-container { right: 0; }
            .mc-style-slide_left .mc-loc-container { top: 0; left: -600px; width: 600px; max-width: 100%; height: 100vh; }
            .mc-style-slide_left.active .mc-loc-container { left: 0; }
            .mc-style-modal .mc-loc-container { top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9); width: 90%; max-width: 1000px; border-radius: 15px; opacity: 0; visibility: hidden; }
            .mc-style-modal.active .mc-loc-container { transform: translate(-50%, -50%) scale(1); opacity: 1; visibility: visible; }
            .mc-close-drawer { position: absolute; top: 20px; right: 20px; font-size: 30px; cursor: pointer; color: #999; line-height: 1; z-index: 100; }
            <?php endif; ?>
        </style>

        <?php if ($interaction !== 'inline'): ?>
        <div class="mc-locator-wrapper mc-style-<?php echo esc_attr($interaction); ?>" id="mc-locator-wrapper"><div class="mc-loc-overlay"></div><div class="mc-loc-container"><span class="mc-close-drawer">×</span>
        <?php endif; ?>

        <div class="mc-loc-app" id="mc-locator-app">
            
            <div id="mc-step-1">
                <div class="mc-step-title"><?php echo esc_html($step_1_title); ?></div>
                
                <?php foreach($locations as $loc) { if (in_array('delivery', get_post_meta($loc->ID, '_mc_loc_services', true) ?: [])) { $has_delivery = true; break; } } ?>
                <?php if ($has_delivery): ?>
                <div class="mc-loc-method-wrap">
                    <?php if ($method_style === 'toggle'): ?>
                        <div class="mc-loc-toggle"><div class="mc-loc-tab active" data-method="pickup">Pickup</div><div class="mc-loc-tab" data-method="delivery">Delivery</div></div>
                    <?php else: ?>
                        <select class="mc-loc-select" id="mc-method-dropdown"><option value="pickup">Order for Pickup</option><option value="delivery">Order for Delivery</option></select>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                    <input type="hidden" id="mc-hidden-method" value="pickup">
                    <div style="text-align: center; margin-bottom: 20px; font-weight: 800; color: <?php echo esc_attr($primary); ?>;">ALL STORES - PICKUP ONLY</div>
                <?php endif; ?>

                <div class="mc-search-grid">
                    <div class="mc-loc-search-box" id="mc-address-search-container">
                        <span class="dashicons dashicons-search" style="color:#aaa; margin-right:10px;"></span>
                        <input type="text" id="mc-customer-address" placeholder="Enter address or city...">
                        <div class="mc-loc-geo-btn" id="mc-trigger-geo"><span class="dashicons dashicons-location"></span> Use Current</div>
                    </div>
                    
                    <div class="mc-datetime-bar">
                        <input type="date" id="mc-order-date" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                        <div class="mc-time-ui-wrapper">
                            <button class="mc-time-toggle-btn active" id="mc-btn-asap">ASAP</button>
                            <button class="mc-time-toggle-btn" id="mc-btn-later">Later</button>
                            <select id="mc-order-time-select" style="display:none;"></select>
                            <input type="hidden" id="mc-final-time" value="ASAP">
                        </div>
                    </div>
                </div>
                <button class="mc-submit-search" id="mc-trigger-search">Find Stores</button>
            </div>

            <div id="mc-step-2" style="display:none; border-top: 2px solid #eee; padding-top: 30px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                    <div class="mc-step-title" style="margin:0;">Select a Location</div>
                    <a href="#" id="mc-back-btn" style="color:#999; text-decoration:none; font-weight:bold; font-size:13px;">← Modify Search</a>
                </div>

                <div class="mc-loc-grid">
                    <div class="mc-loc-list-col">
                        <div class="mc-store-list" id="mc-render-list">
                            <?php 
                            foreach($locations as $loc): 
                                $services = get_post_meta($loc->ID, '_mc_loc_services', true) ?: [];
                                $address = get_post_meta($loc->ID, '_mc_loc_address', true);
                                $phone = get_post_meta($loc->ID, '_mc_loc_phone', true);
                                $contact_pg = get_post_meta($loc->ID, '_mc_loc_contact_page', true);
                                $lat = get_post_meta($loc->ID, '_mc_loc_lat', true);
                                $lng = get_post_meta($loc->ID, '_mc_loc_lng', true);

                                $hours = get_post_meta($loc->ID, '_mc_loc_hours', true) ?: [];
                                $today = $hours[$current_day] ?? [];
                                $status_msg = ""; $status_color = ""; $is_open = false;

                                if (isset($today['closed']) && $today['closed'] === 'yes') {
                                    $status_msg = "Closed Today"; $status_color = "#e74c3c";
                                } else {
                                    $open_time = strtotime($today['open'] ?? '09:00', $current_time);
                                    $close_time = strtotime($today['close'] ?? '21:00', $current_time);
                                    
                                    if ($close_time <= $open_time) {
                                        $close_time += DAY_IN_SECONDS;
                                    }

                                    if ($current_time < $open_time) { $status_msg = "Opens at " . date('g:i A', $open_time); $status_color = "#f39c12"; } 
                                    else if ($current_time >= ($close_time - $last_order_buffer)) { $status_msg = "Closed for the day"; $status_color = "#e74c3c"; } 
                                    else if ($current_time >= ($close_time - $close_warn_buffer)) { $status_msg = "Closes soon (" . date('g:i A', $close_time) . ")"; $status_color = "#e67e22"; $is_open = true; } 
                                    else { $status_msg = "Open until " . date('g:i A', $close_time); $status_color = "#2ecc71"; $is_open = true; }
                                }
                            ?>
                            <div class="mc-store-card mc-card-<?php echo esc_attr($loc->ID); ?>" data-services='<?php echo json_encode($services); ?>' data-lat="<?php echo esc_attr($lat); ?>" data-lng="<?php echo esc_attr($lng); ?>" data-name="<?php echo esc_attr($loc->post_title); ?>" style="opacity: <?php echo $is_open ? '1' : '0.7'; ?>;">
                                <div>
                                    <div class="mc-store-name"><?php echo esc_html($loc->post_title); ?></div>
                                    <div class="mc-store-meta">
                                        <?php if($show_addr === 'yes' && $address) echo esc_html($address) . '<br>'; ?>
                                        <?php if($show_phone === 'yes' && $phone) echo '☎ ' . esc_html($phone); ?>
                                    </div>
                                    <div class="mc-store-status" style="background:<?php echo $status_color; ?>20; color:<?php echo $status_color; ?>;"><?php echo $status_msg; ?></div>
                                    <?php if($show_dir === 'yes' || $show_contact === 'yes'): ?>
                                    <div class="mc-card-links">
                                        <?php if($show_dir === 'yes' && $lat && $lng): ?><a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank"><span class="dashicons dashicons-location"></span> Map</a><?php endif; ?>
                                        <?php if($show_contact === 'yes' && $contact_pg): ?><a href="<?php echo get_permalink($contact_pg); ?>"><span class="dashicons dashicons-email-alt"></span> Contact</a><?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if($is_open): ?>
                                    <button class="mc-store-select-btn" data-id="<?php echo esc_attr($loc->ID); ?>" data-name="<?php echo esc_attr($loc->post_title); ?>">Select</button>
                                <?php else: ?>
                                    <button class="mc-store-select-btn mc-closed-btn" data-id="<?php echo esc_attr($loc->ID); ?>" data-name="<?php echo esc_attr($loc->post_title); ?>" style="background:#7f8c8d;">Pre-Order</button>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($show_map === 'yes'): ?>
                    <div class="mc-loc-map-col">
                        <div class="mc-map-wrapper">
                            <?php if ($map_mode === 'toggle'): ?>
                            <div class="mc-map-focus-toggle" id="mc-map-focus-controls" style="display:none;">
                                <button class="mc-map-focus-btn active" data-focus="store">Store Location</button>
                                <button class="mc-map-focus-btn" data-focus="user">My Location</button>
                            </div>
                            <?php endif; ?>
                            <div id="mc-frontend-map"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($interaction !== 'inline'): ?></div></div><?php endif; ?>

        <script>
        jQuery(document).ready(function($) {
            let activeMethod = 'pickup';
            let showPickupAddress = '<?php echo esc_js($show_pickup_addr); ?>';
            let fallbackUrl = '<?php echo esc_js($fallback_url); ?>';
            let globalPrep = parseInt('<?php echo esc_js($global_prep); ?>') || 15;
            let successColor = '<?php echo esc_js($success); ?>';
            
            let mapMode = '<?php echo esc_js($map_mode); ?>';
            let zoomLvl = parseInt('<?php echo esc_js($map_zoom); ?>') || 13;
            let userPinTitle = '<?php echo esc_js($map_user_title); ?>';
            let storePinTitle = '<?php echo esc_js($map_store_title); ?>';
            let map; let markers = [];
            let userLatLng = null;
            let firstStoreLatLng = null;

            $('.mc-locator-trigger').on('click', function(e) { e.preventDefault(); $('#mc-locator-wrapper').addClass('active'); });
            $('.mc-close-drawer, .mc-loc-overlay').on('click', function() { $('#mc-locator-wrapper').removeClass('active'); });

            function setMethod(method) {
                activeMethod = method;
                if (method === 'pickup' && showPickupAddress !== 'yes') { $('#mc-address-search-container').hide(); } 
                else { $('#mc-address-search-container').css('display', 'flex'); }
            }
            $('.mc-loc-tab').on('click', function() { $('.mc-loc-tab').removeClass('active'); $(this).addClass('active'); setMethod($(this).data('method')); });
            $('#mc-method-dropdown').on('change', function() { setMethod($(this).val()); });
            setMethod('pickup');

            function populateTimeDropdown() {
                let now = new Date();
                let selectedDate = new Date($('#mc-order-date').val() + 'T00:00:00');
                let $select = $('#mc-order-time-select');
                $select.empty();

                let startTime = new Date(selectedDate);
                if (selectedDate.toDateString() === now.toDateString()) {
                    now.setMinutes(now.getMinutes() + globalPrep);
                    let rem = now.getMinutes() % 15;
                    if (rem > 0) now.setMinutes(now.getMinutes() + (15 - rem));
                    startTime = now;
                } else {
                    startTime.setHours(0, 0, 0, 0);
                }

                let endTime = new Date(selectedDate);
                endTime.setHours(23, 45, 0, 0);

                if (startTime > endTime) {
                    $select.append('<option value="">No times available</option>');
                    return;
                }

                while (startTime <= endTime) {
                    let hrs = startTime.getHours().toString().padStart(2, '0');
                    let mins = startTime.getMinutes().toString().padStart(2, '0');
                    let ampm = startTime.getHours() >= 12 ? 'PM' : 'AM';
                    let displayHrs = startTime.getHours() % 12 || 12;
                    let displayTime = displayHrs + ':' + mins + ' ' + ampm;
                    $select.append('<option value="'+hrs+':'+mins+'">' + displayTime + '</option>');
                    startTime.setMinutes(startTime.getMinutes() + 15);
                }
                
                $('#mc-final-time').val($select.val());
            }

            $('#mc-order-date').on('change', function() {
                populateTimeDropdown();
                if(!$('#mc-btn-asap').hasClass('active') && $('#mc-order-date').val() !== new Date().toISOString().split('T')[0]) {
                    $('#mc-btn-asap').removeClass('active'); $('#mc-btn-later').addClass('active');
                    $('#mc-order-time-select').show();
                    $('#mc-final-time').val($('#mc-order-time-select').val());
                } else if ($('#mc-order-date').val() === new Date().toISOString().split('T')[0] && !$('#mc-btn-later').hasClass('active')) {
                    $('#mc-final-time').val('ASAP');
                }
            });

            $('#mc-btn-asap').on('click', function() {
                $('.mc-time-toggle-btn').removeClass('active'); $(this).addClass('active');
                $('#mc-order-time-select').hide();
                $('#mc-order-date').val(new Date().toISOString().split('T')[0]);
                $('#mc-final-time').val('ASAP');
            });

            $('#mc-btn-later').on('click', function() {
                $('.mc-time-toggle-btn').removeClass('active'); $(this).addClass('active');
                $('#mc-order-time-select').show();
                populateTimeDropdown();
            });

            $('#mc-order-time-select').on('change', function() {
                $('#mc-final-time').val($(this).val());
            });

            populateTimeDropdown(); 

            function initMap() {
                if (typeof google === 'undefined' || !document.getElementById('mc-frontend-map')) return;
                map = new google.maps.Map(document.getElementById('mc-frontend-map'), { center: {lat: 10.6416, lng: -61.3995}, zoom: zoomLvl, mapTypeControl: false, streetViewControl: false });
            }

            $('.mc-map-focus-btn').on('click', function() {
                $('.mc-map-focus-btn').removeClass('active'); $(this).addClass('active');
                let focusTarget = $(this).data('focus');
                if (map) {
                    if (focusTarget === 'user' && userLatLng) { map.setCenter(userLatLng); map.setZoom(zoomLvl); } 
                    else if (focusTarget === 'store' && firstStoreLatLng) { map.setCenter(firstStoreLatLng); map.setZoom(zoomLvl); }
                }
            });

            $('#mc-trigger-search').on('click', function() {
                let methodToSearch = $('#mc-hidden-method').length ? 'pickup' : activeMethod;
                if(markers.length > 0) { markers.forEach(m => m.setMap(null)); markers = []; }

                if (mapMode === 'user_only' || mapMode === 'toggle') {
                    if (userLatLng && map) {
                        let userMarker = new google.maps.Marker({ position: userLatLng, map: map, title: userPinTitle, icon: 'http://googleusercontent.com/maps.google.com/2' });
                        markers.push(userMarker);
                        $('#mc-map-focus-controls').show();
                    }
                }

                let isFirstStore = true;
                $('.mc-store-card').each(function() {
                    let services = $(this).data('services');
                    if (services && services.includes(methodToSearch)) {
                        $(this).show();
                        let lat = $(this).data('lat'); let lng = $(this).data('lng'); let name = $(this).data('name');
                        if (map && lat && lng) {
                            let pos = {lat: parseFloat(lat), lng: parseFloat(lng)};
                            if (isFirstStore) { firstStoreLatLng = pos; isFirstStore = false; }
                            let markerTitle = (mapMode === 'store_only' || mapMode === 'toggle') ? storePinTitle + ": " + name : name;
                            let marker = new google.maps.Marker({ position: pos, map: map, title: markerTitle });
                            markers.push(marker);
                        }
                    } else { $(this).hide(); }
                });

                $('#mc-step-1').slideUp();
                $('#mc-step-2').slideDown(function() {
                    if (map) { 
                        google.maps.event.trigger(map, "resize"); 
                        if ((mapMode === 'user_only' || mapMode === 'toggle') && userLatLng) {
                            map.setCenter(userLatLng); map.setZoom(zoomLvl);
                            if(mapMode === 'toggle') { $('.mc-map-focus-btn').removeClass('active'); $('.mc-map-focus-btn[data-focus="user"]').addClass('active'); }
                        } else if (firstStoreLatLng) {
                            map.setCenter(firstStoreLatLng); map.setZoom(zoomLvl);
                            if(mapMode === 'toggle') { $('.mc-map-focus-btn').removeClass('active'); $('.mc-map-focus-btn[data-focus="store"]').addClass('active'); }
                        }
                    }
                });
            });

            $('#mc-back-btn').on('click', function(e) { e.preventDefault(); $('#mc-step-2').slideUp(); $('#mc-step-1').slideDown(); });

            $(document).on('click', '.mc-store-select-btn', function() {
                
                let isClosedNow = $(this).hasClass('mc-closed-btn');
                let selectedTime = $('#mc-final-time').val();
                let selectedDate = $('#mc-order-date').val();
                let todayStr = new Date().toISOString().split('T')[0];
                
                if (isClosedNow && selectedTime === 'ASAP' && selectedDate === todayStr) {
                    alert("This store is currently closed. Please select a time for later or tomorrow.");
                    return;
                }

                let $btn = $(this); let locId = $btn.data('id'); let locName = $btn.data('name');
                let methodToSave = $('#mc-hidden-method').length ? 'pickup' : activeMethod;
                
                $btn.html('<span class="dashicons dashicons-update spin"></span>');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'mc_save_location', location_id: locId, method: methodToSave,
                    date: $('#mc-order-date').val(), time: $('#mc-final-time').val(), address: $('#mc-customer-address').val()
                }, function(res) {
                    if (res.success) {
                        $btn.html('SAVED ✓').css('background', successColor);
                        $('.mc-dyn-loc-name').text(locName.toUpperCase());
                        setTimeout(() => { if (fallbackUrl) { window.location.href = fallbackUrl; } else { location.reload(); } }, 800);
                    }
                });
            });

            $('#mc-trigger-geo').on('click', function() {
                if (navigator.geolocation) {
                    $(this).html('<span class="dashicons dashicons-update spin"></span> Locating...');
                    navigator.geolocation.getCurrentPosition(function(position) {
                        $('#mc-customer-address').val('Using GPS Location...');
                        $('#mc-trigger-geo').html('<span class="dashicons dashicons-yes-alt"></span> Found');
                        userLatLng = {lat: position.coords.latitude, lng: position.coords.longitude};
                    }, function() { alert("Geolocation failed."); $('#mc-trigger-geo').html('<span class="dashicons dashicons-location"></span> Use Current'); });
                }
            });

            if ('<?php echo $show_map; ?>' === 'yes') { setTimeout(initMap, 500); }
        });
        </script>
        <?php
        return ob_get_clean();
    }
}
new MC_Frontend_Locator();