<?php
/**
 * MealCrafter: Multi-Store Locations Manager
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Locations_Manager {

    public function __construct() {
        add_action('init', [$this, 'register_locations_cpt']);
        add_action('add_meta_boxes', [$this, 'add_location_meta_boxes']);
        add_action('save_post', [$this, 'save_location_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_maps_script']);
    }

    public function register_locations_cpt() {
        register_post_type('mc_location', [
            'labels' => ['name' => 'Store Locations', 'singular_name' => 'Location', 'add_new_item' => 'Add New Store Location', 'edit_item' => 'Edit Location'],
            'public' => true, 'publicly_queryable' => false, 'show_ui' => true, 'show_in_menu' => true,
            'supports' => ['title'], 'menu_icon' => 'dashicons-location-alt', 'menu_position' => 51,
        ]);
    }

    public function enqueue_maps_script($hook) {
        global $post;
        if (in_array($hook, ['post-new.php', 'post.php']) && 'mc_location' === $post->post_type) {
            $api_key = get_option('mc_gmaps_api_key');
            if ($api_key) wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $api_key . '&libraries=places', [], null, true);
        }
    }

    public function add_location_meta_boxes() {
        add_meta_box('mc_location_details', 'Location Address & API', [$this, 'render_details_box'], 'mc_location', 'normal', 'high');
        add_meta_box('mc_location_seo', 'SEO & Contact Links', [$this, 'render_seo_box'], 'mc_location', 'side', 'default');
        add_meta_box('mc_location_intervals', 'Preparation Time Intervals', [$this, 'render_intervals_box'], 'mc_location', 'normal', 'default');
        add_meta_box('mc_location_staff', 'Store Manager Assignment', [$this, 'render_staff_box'], 'mc_location', 'side', 'default');
        add_meta_box('mc_location_hours', 'Operating Hours & Schedule', [$this, 'render_hours_box'], 'mc_location', 'normal', 'default');
    }

    public function render_details_box($post) {
        wp_nonce_field('mc_save_loc', 'mc_loc_nonce');
        $phone = get_post_meta($post->ID, '_mc_loc_phone', true);
        $address = get_post_meta($post->ID, '_mc_loc_address', true);
        $lat = get_post_meta($post->ID, '_mc_loc_lat', true);
        $lng = get_post_meta($post->ID, '_mc_loc_lng', true);
        $branch_id = get_post_meta($post->ID, '_mc_loc_branch_id', true); 
        $services = get_post_meta($post->ID, '_mc_loc_services', true) ?: []; 
        $api_key = get_option('mc_gmaps_api_key');
        ?>
        <table class="form-table">
            <tr>
                <th>Available Services</th>
                <td>
                    <label style="margin-right:15px;"><input type="checkbox" name="mc_loc_services[]" value="pickup" <?php checked(in_array('pickup', $services) || empty($services)); ?>> Pickup Allowed</label>
                    <label><input type="checkbox" name="mc_loc_services[]" value="delivery" <?php checked(in_array('delivery', $services)); ?>> Delivery Allowed</label>
                </td>
            </tr>
            <tr><th>Phone Number</th><td><input type="text" name="mc_loc_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td></tr>
            <tr><th>API Branch ID</th><td><input type="text" name="mc_loc_branch_id" value="<?php echo esc_attr($branch_id); ?>" class="regular-text"></td></tr>
            <tr>
                <th><strong style="color:#e74c3c;">* Full Address</strong></th>
                <td>
                    <?php if(!$api_key): ?><div style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:10px;">Missing Google Maps API Key in settings.</div><?php endif; ?>
                    <input type="text" id="mc_loc_address" name="mc_loc_address" value="<?php echo esc_attr($address); ?>" class="large-text" required placeholder="Search Google Places...">
                    <div id="mc-map-canvas" style="width:100%; height:300px; background:#eee; margin-top:15px;"></div>
                    <div style="margin-top:10px; display:flex; gap:15px;">
                        <div><small>Lat:</small><input type="text" id="mc_loc_lat" name="mc_loc_lat" value="<?php echo esc_attr($lat); ?>" readonly></div>
                        <div><small>Lng:</small><input type="text" id="mc_loc_lng" name="mc_loc_lng" value="<?php echo esc_attr($lng); ?>" readonly></div>
                    </div>
                </td>
            </tr>
        </table>
        <?php if($api_key): ?>
        <script>
            jQuery(document).ready(function($) {
                let startLat = parseFloat($('#mc_loc_lat').val()) || 10.6416; let startLng = parseFloat($('#mc_loc_lng').val()) || -61.3995;
                let map = new google.maps.Map(document.getElementById('mc-map-canvas'), { center: {lat: startLat, lng: startLng}, zoom: $('#mc_loc_lat').val() ? 16 : 9 });
                let marker = new google.maps.Marker({ position: {lat: startLat, lng: startLng}, map: map, draggable: true });
                let autocomplete = new google.maps.places.Autocomplete(document.getElementById('mc_loc_address'));
                autocomplete.bindTo('bounds', map);
                autocomplete.addListener('place_changed', function() {
                    let place = autocomplete.getPlace(); if (!place.geometry) return;
                    if (place.geometry.viewport) { map.fitBounds(place.geometry.viewport); } else { map.setCenter(place.geometry.location); map.setZoom(17); }
                    marker.setPosition(place.geometry.location);
                    $('#mc_loc_lat').val(place.geometry.location.lat()); $('#mc_loc_lng').val(place.geometry.location.lng());
                });
                marker.addListener('dragend', function() { let pos = marker.getPosition(); $('#mc_loc_lat').val(pos.lat()); $('#mc_loc_lng').val(pos.lng()); });
                $('#mc_loc_address').keydown(function(e) { if(e.keyCode == 13) e.preventDefault(); });
            });
        </script>
        <?php endif; 
    }

    public function render_seo_box($post) {
        $contact_page = get_post_meta($post->ID, '_mc_loc_contact_page', true);
        ?>
        <p class="description">Select a specific page to link for the "Contact Us" button on this location's card.</p>
        <?php wp_dropdown_pages(['name' => 'mc_loc_contact_page', 'selected' => $contact_page, 'show_option_none' => '-- No Page Assigned --']); ?>
        <?php
    }

    public function render_intervals_box($post) {
        $loc_pickup = get_post_meta($post->ID, '_mc_loc_pickup_interval', true);
        $loc_delivery = get_post_meta($post->ID, '_mc_loc_delivery_interval', true);
        ?>
        <table class="form-table">
            <tr><th>Pickup Prep (mins)</th><td><input type="number" name="mc_loc_pickup_interval" value="<?php echo esc_attr($loc_pickup); ?>"></td></tr>
            <tr><th>Delivery Prep (mins)</th><td><input type="number" name="mc_loc_delivery_interval" value="<?php echo esc_attr($loc_delivery); ?>"></td></tr>
        </table>
        <?php
    }

    public function render_staff_box($post) {
        // Now handles multiple users via user meta
        $users = get_users(['role__in' => ['administrator', 'shop_manager']]);
        echo '<p>Managers currently assigned to this store via their User Profile:</p><ul style="background:#f8f9fa; padding:10px; border:1px solid #ddd;">';
        $found = false;
        foreach($users as $user) {
            $assigned = get_user_meta($user->ID, '_mc_assigned_stores', true) ?: [];
            if (in_array($post->ID, $assigned)) {
                echo '<li><span class="dashicons dashicons-admin-users" style="color:#2ecc71;"></span> ' . esc_html($user->display_name) . '</li>';
                $found = true;
            }
        }
        if (!$found) echo '<li><em>No managers assigned.</em></li>';
        echo '</ul><p class="description">To assign managers, go to <strong>Users -> Profile</strong> and select the stores for that user.</p>';
    }

    public function render_hours_box($post) {
        $hours = get_post_meta($post->ID, '_mc_loc_hours', true) ?: [];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        echo '<table class="form-table"><thead><tr><th style="text-align:left;">Day</th><th style="text-align:left;">Status</th><th style="text-align:left;">Open</th><th style="text-align:left;">Close</th></tr></thead><tbody>';
        foreach($days as $day) {
            $is_closed = isset($hours[$day]['closed']) ? 'yes' : 'no';
            $open = $hours[$day]['open'] ?? '09:00'; $close = $hours[$day]['close'] ?? '21:00';
            echo '<tr><td><strong>'.$day.'</strong></td>';
            echo '<td><label><input type="checkbox" name="mc_loc_hours['.$day.'][closed]" value="yes" '.checked($is_closed, 'yes', false).'> Closed</label></td>';
            echo '<td><input type="time" name="mc_loc_hours['.$day.'][open]" value="'.esc_attr($open).'"></td>';
            echo '<td><input type="time" name="mc_loc_hours['.$day.'][close]" value="'.esc_attr($close).'"></td></tr>';
        }
        echo '</tbody></table>';
    }

    public function save_location_meta($post_id) {
        if (!isset($_POST['mc_loc_nonce']) || !wp_verify_nonce($_POST['mc_loc_nonce'], 'mc_save_loc')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (empty($_POST['mc_loc_address'])) return;

        update_post_meta($post_id, '_mc_loc_phone', sanitize_text_field($_POST['mc_loc_phone']));
        update_post_meta($post_id, '_mc_loc_address', sanitize_text_field($_POST['mc_loc_address']));
        update_post_meta($post_id, '_mc_loc_lat', sanitize_text_field($_POST['mc_loc_lat']));
        update_post_meta($post_id, '_mc_loc_lng', sanitize_text_field($_POST['mc_loc_lng']));
        update_post_meta($post_id, '_mc_loc_branch_id', sanitize_text_field($_POST['mc_loc_branch_id']));
        update_post_meta($post_id, '_mc_loc_pickup_interval', sanitize_text_field($_POST['mc_loc_pickup_interval']));
        update_post_meta($post_id, '_mc_loc_delivery_interval', sanitize_text_field($_POST['mc_loc_delivery_interval']));
        update_post_meta($post_id, '_mc_loc_contact_page', sanitize_text_field($_POST['mc_loc_contact_page']));
        
        $services = isset($_POST['mc_loc_services']) ? array_map('sanitize_text_field', $_POST['mc_loc_services']) : [];
        update_post_meta($post_id, '_mc_loc_services', $services);
        if (isset($_POST['mc_loc_hours'])) update_post_meta($post_id, '_mc_loc_hours', $_POST['mc_loc_hours']);
    }
}
new MC_Locations_Manager();