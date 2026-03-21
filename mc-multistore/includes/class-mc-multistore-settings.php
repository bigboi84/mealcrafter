<?php
/**
 * MealCrafter: Multi-Store Global Settings & Design
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Multistore_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook === 'mc_location_page_mc-multistore-settings') { wp_enqueue_media(); }
    }

    public function add_settings_page() { add_submenu_page('edit.php?post_type=mc_location', 'Locator Settings', 'Settings', 'manage_options', 'mc-multistore-settings', [$this, 'render_settings_page']); }

    public function register_settings() {
        register_setting('mc_ms_general', 'mc_gmaps_api_key');
        register_setting('mc_ms_general', 'mc_global_pickup_interval');
        register_setting('mc_ms_general', 'mc_global_delivery_interval');
        register_setting('mc_ms_general', 'mc_last_order_buffer');
        register_setting('mc_ms_general', 'mc_closes_soon_buffer');
        
        register_setting('mc_ms_features', 'mc_step_1_title'); 
        register_setting('mc_ms_features', 'mc_feature_show_map');
        register_setting('mc_ms_features', 'mc_map_center_mode'); 
        register_setting('mc_ms_features', 'mc_map_zoom'); 
        register_setting('mc_ms_features', 'mc_map_user_title'); 
        register_setting('mc_ms_features', 'mc_map_store_title'); 
        register_setting('mc_ms_features', 'mc_feature_pickup_address');
        register_setting('mc_ms_features', 'mc_feature_geolocate');
        register_setting('mc_ms_features', 'mc_feature_force_select');
        register_setting('mc_ms_features', 'mc_redirect_page_id'); 
        register_setting('mc_ms_features', 'mc_fallback_url'); 
        register_setting('mc_ms_features', 'mc_ui_interaction');
        register_setting('mc_ms_features', 'mc_method_style');
        register_setting('mc_ms_features', 'mc_method_align');
        
        register_setting('mc_ms_features', 'mc_card_show_address');
        register_setting('mc_ms_features', 'mc_card_show_phone');
        register_setting('mc_ms_features', 'mc_card_show_directions');
        register_setting('mc_ms_features', 'mc_card_show_contact');

        register_setting('mc_ms_design', 'mc_design_primary_color');
        register_setting('mc_ms_design', 'mc_design_success_color'); 
        register_setting('mc_ms_design', 'mc_design_bg_color');
        register_setting('mc_ms_design', 'mc_design_bg_opacity'); 
        register_setting('mc_ms_design', 'mc_design_radius');
        register_setting('mc_ms_design', 'mc_design_card_padding');
        register_setting('mc_ms_design', 'mc_design_card_margin');
        
        register_setting('mc_ms_design', 'mc_btn_bg_color'); 
        register_setting('mc_ms_design', 'mc_btn_icon_bg_color'); 
        register_setting('mc_ms_design', 'mc_btn_text_top_color'); 
        register_setting('mc_ms_design', 'mc_btn_text_bot_color'); 
        
        // NEW TYPOGRAPHY SETTINGS
        register_setting('mc_ms_design', 'mc_btn_font_family'); 
        register_setting('mc_ms_design', 'mc_btn_text_top_weight'); 
        register_setting('mc_ms_design', 'mc_btn_text_bot_weight'); 
        
        register_setting('mc_ms_design', 'mc_btn_icon_url'); 
        register_setting('mc_ms_design', 'mc_btn_icon_size'); 
        register_setting('mc_ms_design', 'mc_btn_padding'); 
        register_setting('mc_ms_design', 'mc_btn_radius'); 
        register_setting('mc_ms_design', 'mc_btn_default_top');
        register_setting('mc_ms_design', 'mc_btn_default_bottom');
        register_setting('mc_ms_design', 'mc_btn_show_method');
        register_setting('mc_ms_design', 'mc_btn_show_location');

        register_setting('mc_ms_checkout', 'mc_chk_hide_company');
        register_setting('mc_ms_checkout', 'mc_chk_hide_address_2');
        register_setting('mc_ms_checkout', 'mc_chk_hide_city');
        register_setting('mc_ms_checkout', 'mc_chk_hide_postcode');
        register_setting('mc_ms_checkout', 'mc_chk_hide_country');

        register_setting('mc_ms_emails', 'mc_email_prep_pickup_subj');
        register_setting('mc_ms_emails', 'mc_email_prep_pickup_msg');
        register_setting('mc_ms_emails', 'mc_email_prep_deliv_subj');
        register_setting('mc_ms_emails', 'mc_email_prep_deliv_msg');
        register_setting('mc_ms_emails', 'mc_email_out_deliv_subj');
        register_setting('mc_ms_emails', 'mc_email_out_deliv_msg');
    }

    public function render_settings_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <div style="background: #1e2d3b; color: #fff; padding: 25px 30px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div><h1 style="color: #fff; margin: 0; font-weight: 900; font-size: 28px;">Meal Crafter <span style="font-weight: 300; opacity: 0.8;">| Multi-Store Engine</span></h1></div>
                <div style="display:flex; gap:15px;">
                    <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; text-align: center;"><span style="display: block; font-size: 11px; text-transform: uppercase;">Locator App</span><code style="background: transparent; color: #2ecc71; font-weight: bold; padding: 0;">[mc_store_locator]</code></div>
                    <div style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; text-align: center;"><span style="display: block; font-size: 11px; text-transform: uppercase;">Header Button</span><code style="background: transparent; color: #3498db; font-weight: bold; padding: 0;">[mc_active_store_btn]</code></div>
                </div>
            </div>
            
            <h2 class="nav-tab-wrapper">
                <a href="?post_type=mc_location&page=mc-multistore-settings&tab=general" class="nav-tab <?php echo $tab == 'general' ? 'nav-tab-active' : ''; ?>">General & Time</a>
                <a href="?post_type=mc_location&page=mc-multistore-settings&tab=features" class="nav-tab <?php echo $tab == 'features' ? 'nav-tab-active' : ''; ?>">UX & Layout</a>
                <a href="?post_type=mc_location&page=mc-multistore-settings&tab=design" class="nav-tab <?php echo $tab == 'design' ? 'nav-tab-active' : ''; ?>">Frontend Design</a>
                <a href="?post_type=mc_location&page=mc-multistore-settings&tab=checkout" class="nav-tab <?php echo $tab == 'checkout' ? 'nav-tab-active' : ''; ?>">Checkout Fields</a>
                <a href="?post_type=mc_location&page=mc-multistore-settings&tab=emails" class="nav-tab <?php echo $tab == 'emails' ? 'nav-tab-active' : ''; ?>">Status Emails</a>
            </h2>
            
            <form method="post" action="options.php" style="background:#fff; padding:30px; border:1px solid #ddd; border-top:none; border-radius:0 0 12px 12px;">
                
                <?php if ($tab == 'general') : ?>
                    <?php settings_fields('mc_ms_general'); ?>
                    <table class="form-table"><tr><th>Google Maps API Key</th><td><input type="password" name="mc_gmaps_api_key" value="<?php echo esc_attr(get_option('mc_gmaps_api_key')); ?>" class="regular-text"></td></tr></table><hr>
                    <table class="form-table">
                        <tr><th>Global Pickup Prep (Mins)</th><td><input type="number" name="mc_global_pickup_interval" value="<?php echo esc_attr(get_option('mc_global_pickup_interval', '15')); ?>" style="width:80px;"></td></tr>
                        <tr><th>Global Delivery Prep (Mins)</th><td><input type="number" name="mc_global_delivery_interval" value="<?php echo esc_attr(get_option('mc_global_delivery_interval', '45')); ?>" style="width:80px;"></td></tr>
                        <tr><th>Last Order Buffer (Mins)</th><td><input type="number" name="mc_last_order_buffer" value="<?php echo esc_attr(get_option('mc_last_order_buffer', '30')); ?>" style="width:80px;"></td></tr>
                        <tr><th>"Closes Soon" Warning (Mins)</th><td><input type="number" name="mc_closes_soon_buffer" value="<?php echo esc_attr(get_option('mc_closes_soon_buffer', '60')); ?>" style="width:80px;"></td></tr>
                    </table>

                <?php elseif ($tab == 'features') : ?>
                    <?php settings_fields('mc_ms_features'); ?>
                    <h3>App Content & Map Configuration</h3>
                    <table class="form-table">
                        <tr><th>Step 1 Title</th><td><input type="text" name="mc_step_1_title" value="<?php echo esc_attr(get_option('mc_step_1_title', 'When do you want your food?')); ?>" class="large-text"></td></tr>
                        <tr><th>Show Visual Map</th><td><label><input type="checkbox" name="mc_feature_show_map" value="yes" <?php checked(get_option('mc_feature_show_map', 'yes'), 'yes'); ?>> Enable Map</label></td></tr>
                        <tr><th>Map Focus Mode</th><td><select name="mc_map_center_mode"><option value="store_only" <?php selected(get_option('mc_map_center_mode', 'store_only'), 'store_only'); ?>>Force Store Focus</option><option value="user_only" <?php selected(get_option('mc_map_center_mode'), 'user_only'); ?>>Force User Focus</option><option value="toggle" <?php selected(get_option('mc_map_center_mode'), 'toggle'); ?>>Show Frontend Toggle Switch</option></select></td></tr>
                        <tr><th>Map Default Zoom</th><td><input type="number" name="mc_map_zoom" value="<?php echo esc_attr(get_option('mc_map_zoom', '13')); ?>" style="width:80px;"></td></tr>
                        <tr><th>User Pin Title</th><td><input type="text" name="mc_map_user_title" value="<?php echo esc_attr(get_option('mc_map_user_title', 'My Location')); ?>" class="regular-text"></td></tr>
                        <tr><th>Store Pin Title</th><td><input type="text" name="mc_map_store_title" value="<?php echo esc_attr(get_option('mc_map_store_title', 'Closest Store')); ?>" class="regular-text"></td></tr>
                    </table><hr>
                    <table class="form-table">
                        <tr><th>Display Style</th><td><select name="mc_ui_interaction"><option value="inline" <?php selected(get_option('mc_ui_interaction', 'inline'), 'inline'); ?>>Standard Inline Display</option><option value="slide_right" <?php selected(get_option('mc_ui_interaction'), 'slide_right'); ?>>Slide-out Drawer (Right)</option><option value="slide_left" <?php selected(get_option('mc_ui_interaction'), 'slide_left'); ?>>Slide-out Drawer (Left)</option><option value="modal" <?php selected(get_option('mc_ui_interaction'), 'modal'); ?>>Center Popup Modal</option></select></td></tr>
                        <tr><th>Method UI Style</th><td><select name="mc_method_style"><option value="toggle" <?php selected(get_option('mc_method_style', 'toggle'), 'toggle'); ?>>Pill Toggle Buttons</option><option value="dropdown" <?php selected(get_option('mc_method_style'), 'dropdown'); ?>>Dropdown Select</option></select></td></tr>
                        <tr><th>Method Alignment</th><td><select name="mc_method_align"><option value="center" <?php selected(get_option('mc_method_align', 'center'), 'center'); ?>>Center</option><option value="flex-start" <?php selected(get_option('mc_method_align'), 'flex-start'); ?>>Left</option><option value="flex-end" <?php selected(get_option('mc_method_align'), 'flex-end'); ?>>Right</option></select></td></tr>
                    </table><hr>
                    <table class="form-table">
                        <tr><th>Pickup Address Search</th><td><label><input type="checkbox" name="mc_feature_pickup_address" value="yes" <?php checked(get_option('mc_feature_pickup_address', 'no'), 'yes'); ?>> Show manual address field for Pickup</label></td></tr>
                        <tr><th>Auto-Geolocate</th><td><label><input type="checkbox" name="mc_feature_geolocate" value="yes" <?php checked(get_option('mc_feature_geolocate', 'yes'), 'yes'); ?>> Ping user GPS on load</label></td></tr>
                        <tr><th>Force Selection Mode</th><td><select name="mc_feature_force_select"><option value="off" <?php selected(get_option('mc_feature_force_select', 'off'), 'off'); ?>>Off (Don't Force)</option><option value="overlay" <?php selected(get_option('mc_feature_force_select'), 'overlay'); ?>>Popup Overlay on Product Page</option><option value="redirect" <?php selected(get_option('mc_feature_force_select'), 'redirect'); ?>>Redirect to Dedicated Page</option></select></td></tr>
                        <tr><th>Dedicated Locator Page</th><td><?php wp_dropdown_pages(['name' => 'mc_redirect_page_id', 'selected' => get_option('mc_redirect_page_id'), 'show_option_none' => '-- Select Page --']); ?></td></tr>
                        <tr><th>Fallback URL (After Save)</th><td><input type="text" name="mc_fallback_url" value="<?php echo esc_attr(get_option('mc_fallback_url')); ?>" class="regular-text" placeholder="e.g., /menu/"></td></tr>
                    </table><hr>
                    <table class="form-table">
                        <tr><th>Hide/Show Details</th><td><label style="display:block;"><input type="checkbox" name="mc_card_show_address" value="yes" <?php checked(get_option('mc_card_show_address', 'yes'), 'yes'); ?>> Show Address</label><label style="display:block;"><input type="checkbox" name="mc_card_show_phone" value="yes" <?php checked(get_option('mc_card_show_phone', 'yes'), 'yes'); ?>> Show Phone Number</label><label style="display:block;"><input type="checkbox" name="mc_card_show_directions" value="yes" <?php checked(get_option('mc_card_show_directions', 'yes'), 'yes'); ?>> Show 'Find Us' Link</label><label style="display:block;"><input type="checkbox" name="mc_card_show_contact" value="yes" <?php checked(get_option('mc_card_show_contact', 'yes'), 'yes'); ?>> Show 'Contact Us' Link</label></td></tr>
                    </table>

                <?php elseif ($tab == 'checkout') : ?>
                    <?php settings_fields('mc_ms_checkout'); ?>
                    <table class="form-table">
                        <tr><th>Hide Company Name</th><td><label><input type="checkbox" name="mc_chk_hide_company" value="yes" <?php checked(get_option('mc_chk_hide_company', 'no'), 'yes'); ?>> Hide</label></td></tr>
                        <tr><th>Hide Address Line 2</th><td><label><input type="checkbox" name="mc_chk_hide_address_2" value="yes" <?php checked(get_option('mc_chk_hide_address_2', 'no'), 'yes'); ?>> Hide</label></td></tr>
                        <tr><th>Hide City/Town</th><td><label><input type="checkbox" name="mc_chk_hide_city" value="yes" <?php checked(get_option('mc_chk_hide_city', 'no'), 'yes'); ?>> Hide</label></td></tr>
                        <tr><th>Hide Zip/Postcode</th><td><label><input type="checkbox" name="mc_chk_hide_postcode" value="yes" <?php checked(get_option('mc_chk_hide_postcode', 'no'), 'yes'); ?>> Hide</label></td></tr>
                        <tr><th>Hide Country</th><td><label><input type="checkbox" name="mc_chk_hide_country" value="yes" <?php checked(get_option('mc_chk_hide_country', 'no'), 'yes'); ?>> Hide</label></td></tr>
                    </table>

                <?php elseif ($tab == 'emails') : ?>
                    <?php settings_fields('mc_ms_emails'); ?>
                    <p class="description">Available wildcards for messages: <code>{customer_name}</code>, <code>{order_number}</code>, <code>{store_name}</code></p>
                    <hr>
                    <h3>Order Status: Preparing (Pickup)</h3>
                    <table class="form-table">
                        <tr><th>Email Subject</th><td><input type="text" name="mc_email_prep_pickup_subj" value="<?php echo esc_attr(get_option('mc_email_prep_pickup_subj', 'Your order #{order_number} is being prepared!')); ?>" class="large-text"></td></tr>
                        <tr><th>Email Message</th><td><textarea name="mc_email_prep_pickup_msg" rows="4" class="large-text"><?php echo esc_textarea(get_option('mc_email_prep_pickup_msg', "Hi {customer_name},\n\nGreat news! The kitchen at {store_name} has started preparing your order. We will let you know as soon as it is ready for pickup.")); ?></textarea></td></tr>
                    </table>
                    <hr>
                    <h3>Order Status: Preparing (Delivery)</h3>
                    <table class="form-table">
                        <tr><th>Email Subject</th><td><input type="text" name="mc_email_prep_deliv_subj" value="<?php echo esc_attr(get_option('mc_email_prep_deliv_subj', 'Your order #{order_number} is being prepared!')); ?>" class="large-text"></td></tr>
                        <tr><th>Email Message</th><td><textarea name="mc_email_prep_deliv_msg" rows="4" class="large-text"><?php echo esc_textarea(get_option('mc_email_prep_deliv_msg', "Hi {customer_name},\n\nGreat news! The kitchen at {store_name} has started preparing your order. We will notify you once it goes out for delivery.")); ?></textarea></td></tr>
                    </table>
                    <hr>
                    <h3>Order Status: Out for Delivery</h3>
                    <table class="form-table">
                        <tr><th>Email Subject</th><td><input type="text" name="mc_email_out_deliv_subj" value="<?php echo esc_attr(get_option('mc_email_out_deliv_subj', 'Your order #{order_number} is on the way!')); ?>" class="large-text"></td></tr>
                        <tr><th>Email Message</th><td><textarea name="mc_email_out_deliv_msg" rows="4" class="large-text"><?php echo esc_textarea(get_option('mc_email_out_deliv_msg', "Hi {customer_name},\n\nYour order from {store_name} has left the kitchen and is currently out for delivery. Keep an eye out!")); ?></textarea></td></tr>
                    </table>

                <?php else : ?>
                    <?php settings_fields('mc_ms_design'); ?>
                    <table class="form-table">
                        <tr><th>Primary Brand Color</th><td><input type="color" name="mc_design_primary_color" value="<?php echo esc_attr(get_option('mc_design_primary_color', '#e74c3c')); ?>"></td></tr>
                        <tr><th>Success (Saved) Color</th><td><input type="color" name="mc_design_success_color" value="<?php echo esc_attr(get_option('mc_design_success_color', '#2ecc71')); ?>"></td></tr>
                        <tr><th>App Background Color</th><td><input type="color" name="mc_design_bg_color" value="<?php echo esc_attr(get_option('mc_design_bg_color', '#fafafa')); ?>"></td></tr>
                        <tr><th>Background Opacity</th><td><input type="number" name="mc_design_bg_opacity" value="<?php echo esc_attr(get_option('mc_design_bg_opacity', '100')); ?>" style="width:80px;" min="0" max="100"> %</td></tr>
                        <tr><th>Global Border Radius</th><td><input type="number" name="mc_design_radius" value="<?php echo esc_attr(get_option('mc_design_radius', '12')); ?>" style="width:80px;"> px</td></tr>
                        <tr><th>Card Padding</th><td><input type="number" name="mc_design_card_padding" value="<?php echo esc_attr(get_option('mc_design_card_padding', '20')); ?>" style="width:80px;"> px</td></tr>
                        <tr><th>Card Margin (Gap)</th><td><input type="number" name="mc_design_card_margin" value="<?php echo esc_attr(get_option('mc_design_card_margin', '15')); ?>" style="width:80px;"> px</td></tr>
                    </table><hr>
                    
                    <h3>Header Button Customization</h3>
                    <p class="description">Customize the colors, fonts, and icon for the button that appears in your site's header.</p>
                    <table class="form-table">
                        <tr><th>Button Background Color</th><td><input type="color" name="mc_btn_bg_color" value="<?php echo esc_attr(get_option('mc_btn_bg_color', '#ffffff')); ?>"></td></tr>
                        <tr><th>Icon Background Color</th><td><input type="color" name="mc_btn_icon_bg_color" value="<?php echo esc_attr(get_option('mc_btn_icon_bg_color', '#e74c3c')); ?>"></td></tr>
                        <tr>
                            <th>Custom Icon Image</th>
                            <td>
                                <input type="text" name="mc_btn_icon_url" id="mc_btn_icon_url" value="<?php echo esc_attr(get_option('mc_btn_icon_url')); ?>" class="regular-text">
                                <button type="button" class="button mc-upload-btn">Upload/Select Image</button>
                                <p class="description">If uploading a PNG/SVG, it will sit directly on top of your chosen Icon Background Color.</p>
                            </td>
                        </tr>
                        <tr><th>Custom Icon Size</th><td><input type="number" name="mc_btn_icon_size" value="<?php echo esc_attr(get_option('mc_btn_icon_size', '20')); ?>" style="width:80px;"> px</td></tr>
                        <tr><th>Button Padding</th><td><input type="text" name="mc_btn_padding" value="<?php echo esc_attr(get_option('mc_btn_padding', '8px 20px 8px 10px')); ?>" class="regular-text"></td></tr>
                        <tr><th>Button Radius</th><td><input type="number" name="mc_btn_radius" value="<?php echo esc_attr(get_option('mc_btn_radius', '50')); ?>" style="width:80px;"> px</td></tr>
                        
                        <tr><th colspan="2" style="padding-top:20px;"><strong>Typography Settings</strong></th></tr>
                        
                        <tr>
                            <th>Font Family (Google Fonts)</th>
                            <td>
                                <?php $selected_font = get_option('mc_btn_font_family', 'inherit'); ?>
                                <select name="mc_btn_font_family">
                                    <option value="inherit" <?php selected($selected_font, 'inherit'); ?>>Inherit Theme Font</option>
                                    <optgroup label="Popular Google Fonts">
                                        <option value="Montserrat" <?php selected($selected_font, 'Montserrat'); ?>>Montserrat</option>
                                        <option value="Poppins" <?php selected($selected_font, 'Poppins'); ?>>Poppins</option>
                                        <option value="Inter" <?php selected($selected_font, 'Inter'); ?>>Inter</option>
                                        <option value="Roboto" <?php selected($selected_font, 'Roboto'); ?>>Roboto</option>
                                        <option value="Open Sans" <?php selected($selected_font, 'Open Sans'); ?>>Open Sans</option>
                                        <option value="Lato" <?php selected($selected_font, 'Lato'); ?>>Lato</option>
                                        <option value="Oswald" <?php selected($selected_font, 'Oswald'); ?>>Oswald</option>
                                        <option value="Raleway" <?php selected($selected_font, 'Raleway'); ?>>Raleway</option>
                                        <option value="Nunito" <?php selected($selected_font, 'Nunito'); ?>>Nunito</option>
                                        <option value="Ubuntu" <?php selected($selected_font, 'Ubuntu'); ?>>Ubuntu</option>
                                        <option value="Playfair Display" <?php selected($selected_font, 'Playfair Display'); ?>>Playfair Display</option>
                                        <option value="Rubik" <?php selected($selected_font, 'Rubik'); ?>>Rubik</option>
                                        <option value="Work Sans" <?php selected($selected_font, 'Work Sans'); ?>>Work Sans</option>
                                    </optgroup>
                                </select>
                            </td>
                        </tr>
                        
                        <tr><th>Top Text</th><td><input type="text" name="mc_btn_default_top" value="<?php echo esc_attr(get_option('mc_btn_default_top', 'SELECT STORE')); ?>" class="regular-text"></td></tr>
                        <tr><th>Top Text Color</th><td><input type="color" name="mc_btn_text_top_color" value="<?php echo esc_attr(get_option('mc_btn_text_top_color', '#666666')); ?>"></td></tr>
                        <tr>
                            <th>Top Text Weight</th>
                            <td>
                                <?php $top_w = get_option('mc_btn_text_top_weight', '700'); ?>
                                <select name="mc_btn_text_top_weight">
                                    <option value="400" <?php selected($top_w, '400'); ?>>Normal (400)</option>
                                    <option value="500" <?php selected($top_w, '500'); ?>>Medium (500)</option>
                                    <option value="600" <?php selected($top_w, '600'); ?>>Semi-Bold (600)</option>
                                    <option value="700" <?php selected($top_w, '700'); ?>>Bold (700)</option>
                                    <option value="800" <?php selected($top_w, '800'); ?>>Extra Bold (800)</option>
                                    <option value="900" <?php selected($top_w, '900'); ?>>Black (900)</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr><th>Bottom Text</th><td><input type="text" name="mc_btn_default_bottom" value="<?php echo esc_attr(get_option('mc_btn_default_bottom', 'FIND YOUR LOCATION')); ?>" class="regular-text"></td></tr>
                        <tr><th>Bottom Text Color</th><td><input type="color" name="mc_btn_text_bot_color" value="<?php echo esc_attr(get_option('mc_btn_text_bot_color', '#e74c3c')); ?>"></td></tr>
                        <tr>
                            <th>Bottom Text Weight</th>
                            <td>
                                <?php $bot_w = get_option('mc_btn_text_bot_weight', '900'); ?>
                                <select name="mc_btn_text_bot_weight">
                                    <option value="400" <?php selected($bot_w, '400'); ?>>Normal (400)</option>
                                    <option value="500" <?php selected($bot_w, '500'); ?>>Medium (500)</option>
                                    <option value="600" <?php selected($bot_w, '600'); ?>>Semi-Bold (600)</option>
                                    <option value="700" <?php selected($bot_w, '700'); ?>>Bold (700)</option>
                                    <option value="800" <?php selected($bot_w, '800'); ?>>Extra Bold (800)</option>
                                    <option value="900" <?php selected($bot_w, '900'); ?>>Black (900)</option>
                                </select>
                            </td>
                        </tr>
                        
                        <tr><th>Active State Display</th><td><label style="display:block;"><input type="checkbox" name="mc_btn_show_method" value="yes" <?php checked(get_option('mc_btn_show_method', 'yes'), 'yes'); ?>> Show Method</label><label style="display:block; margin-top:5px;"><input type="checkbox" name="mc_btn_show_location" value="yes" <?php checked(get_option('mc_btn_show_location', 'yes'), 'yes'); ?>> Show Location Name</label></td></tr>
                    </table>
                    <script>
                    jQuery(document).ready(function($){
                        var custom_uploader;
                        $('.mc-upload-btn').click(function(e) {
                            e.preventDefault();
                            if (custom_uploader) { custom_uploader.open(); return; }
                            custom_uploader = wp.media.frames.file_frame = wp.media({ title: 'Choose Custom Icon', button: { text: 'Choose Icon' }, multiple: false });
                            custom_uploader.on('select', function() {
                                attachment = custom_uploader.state().get('selection').first().toJSON();
                                $('#mc_btn_icon_url').val(attachment.url);
                            });
                            custom_uploader.open();
                        });
                    });
                    </script>
                <?php endif; ?>
                <p class="submit"><?php submit_button('Save Configuration', 'primary', 'submit', false); ?></p>
            </form>
        </div>
        <?php
    }
}
new MC_Multistore_Settings();