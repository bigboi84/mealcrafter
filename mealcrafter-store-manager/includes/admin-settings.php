<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'mc_register_plugin_submenus', function($parent_slug) {
    add_submenu_page($parent_slug, 'Store Manager', 'Store Manager', 'manage_options', 'mc-store-manager', 'mc_render_manager_settings_page');
});

function mc_render_manager_settings_page() {
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
    if (isset($_GET['flush_perms'])) { flush_rewrite_rules(); echo '<div class="updated"><p>Permalinks Flushed!</p></div>'; }
    ?>
    <div class="wrap">
        <h1 style="font-weight:900;">Store <span style="font-weight:100; color:#999;">Manager Configuration</span></h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=mc-store-manager&tab=general" class="nav-tab <?php echo $tab == 'general' ? 'nav-tab-active' : ''; ?>">General & Access</a>
            <a href="?page=mc-store-manager&tab=design" class="nav-tab <?php echo $tab == 'design' ? 'nav-tab-active' : ''; ?>">Design & Colors</a>
            <a href="?page=mc-store-manager&tab=ui" class="nav-tab <?php echo $tab == 'ui' ? 'nav-tab-active' : ''; ?>">Order Details UI</a>
        </h2>
        
        <form method="post" action="options.php">
            <?php 
            if ($tab == 'general') { settings_fields('mc_manager_general_group'); } 
            elseif ($tab == 'design') { settings_fields('mc_manager_design_group'); }
            else { settings_fields('mc_manager_ui_group'); }
            ?>
            <div style="background:#fff; padding:30px; border:1px solid #ddd; border-top:none; border-radius:0 0 12px 12px;">
                
                <?php if ($tab == 'general') : ?>
                    <h3>Deployment & Utilities</h3>
                    <table class="form-table">
                        <tr>
                            <th>Portal Page</th>
                            <td>
                                <select name="mc_manager_page_id">
                                    <option value="">-- Select --</option>
                                    <?php foreach(get_pages() as $p) echo "<option value='{$p->ID}' ".selected(get_option('mc_manager_page_id'),$p->ID,false).">{$p->post_title}</option>"; ?>
                                </select>
                            </td>
                        </tr>
                        <tr><th>Permalinks</th><td><a href="<?php echo admin_url('admin.php?page=mc-store-manager&flush_perms=1'); ?>" class="button button-secondary">Flush Permalinks</a></td></tr>
                        <tr>
                            <th>Lookback Period</th>
                            <td>
                                <select name="mc_order_lookback">
                                    <option value="30" <?php selected(get_option('mc_order_lookback'),'30');?>>Last 1 Month (30 Days)</option>
                                    <option value="90" <?php selected(get_option('mc_order_lookback'),'90');?>>Last 3 Months (90 Days)</option>
                                    <option value="180" <?php selected(get_option('mc_order_lookback'),'180');?>>Last 6 Months (180 Days)</option>
                                    <option value="365" <?php selected(get_option('mc_order_lookback'),'365');?>>Last 1 Year (365 Days)</option>
                                    <option value="730" <?php selected(get_option('mc_order_lookback'),'730');?>>Last 2 Years (730 Days)</option>
                                    <option value="1095" <?php selected(get_option('mc_order_lookback'),'1095');?>>Last 3 Years (1095 Days)</option>
                                </select>
                            </td>
                        </tr>
                        <tr><th>Orders Per Page</th><td><input type="number" name="mc_orders_per_page" value="<?php echo esc_attr(get_option('mc_orders_per_page', '20')); ?>" style="width:70px;"></td></tr>
                    </table>
                    <hr>
                    <h3>Live Notifications Engine</h3>
                    <table class="form-table">
                        <tr>
                            <th>Enable Notifications</th>
                            <td><label><input type="checkbox" name="mc_frontend_notifications" value="yes" <?php checked(get_option('mc_frontend_notifications', 'yes'),'yes');?>> Enable Center-Screen Popups and Audio loop.</label></td>
                        </tr>
                        <tr>
                            <th>Check Interval</th>
                            <td><input type="number" name="mc_frontend_interval" value="<?php echo esc_attr(get_option('mc_frontend_interval', '15')); ?>" style="width:70px;"> <p class="description" style="display:inline-block; margin-left:10px;">Seconds</p></td>
                        </tr>
                    </table>
                    <hr>
                    <h3>Security & Restrictions</h3>
                    <table class="form-table">
                        <tr><th>Access Denied Text</th><td><input type="text" name="mc_auth_title" value="<?php echo esc_attr(get_option('mc_auth_title','Restricted Area')); ?>" class="regular-text"><br><br><textarea name="mc_auth_msg" rows="2" style="width:100%; max-width:400px;"><?php echo esc_textarea(get_option('mc_auth_msg','Access Denied.')); ?></textarea></td></tr>
                        <tr><th>Staff Control</th><td><label><input type="checkbox" name="mc_hide_admin_bar" value="yes" <?php checked(get_option('mc_hide_admin_bar'),'yes');?>> Hide WP Admin Bar on Portal.</label><br><br><label><input type="checkbox" name="mc_prevent_backend_access" value="yes" <?php checked(get_option('mc_prevent_backend_access'),'yes');?>> Redirect Shop Managers away from WP-Admin to Portal.</label></td></tr>
                    </table>

                <?php elseif ($tab == 'design') : ?>
                    <h3>White-Label Branding</h3>
                    <table class="form-table">
                        <tr><th>Brand Name</th><td><input type="text" name="mc_brand_name" value="<?php echo esc_attr(get_option('mc_brand_name', get_bloginfo('name'))); ?>" class="regular-text"></td></tr>
                        <tr><th>Brand Logo</th><td><input type="text" id="mc_brand_logo" name="mc_brand_logo" value="<?php echo esc_attr(get_option('mc_brand_logo')); ?>" class="regular-text"> <button type="button" class="button mc-img-upload" data-target="#mc_brand_logo">Select Image</button></td></tr>
                        <tr><th>Sidebar Background</th><td><input type="text" id="mc_bg_sidebar" name="mc_bg_sidebar" value="<?php echo esc_attr(get_option('mc_bg_sidebar')); ?>" class="regular-text"> <button type="button" class="button mc-img-upload" data-target="#mc_bg_sidebar">Select Image</button></td></tr>
                        <tr><th>Sidebar Username Color</th><td><input type="color" name="mc_color_username" value="<?php echo esc_attr(get_option('mc_color_username', '#ffffff')); ?>"></td></tr>
                    </table>
                    <hr>
                    <h3>Colors & Statuses</h3>
                    <table class="form-table">
                        <tr><th>Sidebar Base Color</th><td><input type="color" name="mc_color_sidebar" value="<?php echo esc_attr(get_option('mc_color_sidebar', '#2c3e50')); ?>"></td></tr>
                        <tr><th>Primary Accent (Icons)</th><td><input type="color" name="mc_color_primary" value="<?php echo esc_attr(get_option('mc_color_primary', '#3498db')); ?>"></td></tr>
                        <tr><th>Pickup Badge</th><td><input type="color" name="mc_color_pickup" value="<?php echo esc_attr(get_option('mc_color_pickup', '#2ecc71')); ?>"></td></tr>
                        <tr><th>Delivery Badge</th><td><input type="color" name="mc_color_delivery" value="<?php echo esc_attr(get_option('mc_color_delivery', '#e74c3c')); ?>"></td></tr>
                        <tr><th>Paid Online Badge</th><td><input type="color" name="mc_color_paid" value="<?php echo esc_attr(get_option('mc_color_paid', '#2ecc71')); ?>"></td></tr>
                        <tr><th>Pay In Store Badge</th><td><input type="color" name="mc_color_unpaid" value="<?php echo esc_attr(get_option('mc_color_unpaid', '#f39c12')); ?>"></td></tr>
                        <tr>
                            <th>Order Status Colors</th>
                            <td>
                                <div style="display:flex; flex-wrap:wrap; gap:15px; margin-bottom:10px; max-width:800px;">
                                    <label>Pending: <input type="color" name="mc_color_status_pending" value="<?php echo esc_attr(get_option('mc_color_status_pending', '#e67e22')); ?>"></label>
                                    <label>Processing: <input type="color" name="mc_color_status_processing" value="<?php echo esc_attr(get_option('mc_color_status_processing', '#f1c40f')); ?>"></label>
                                    <label>Completed: <input type="color" name="mc_color_status_completed" value="<?php echo esc_attr(get_option('mc_color_status_completed', '#95a5a6')); ?>"></label>
                                    <label>Cancelled: <input type="color" name="mc_color_status_cancelled" value="<?php echo esc_attr(get_option('mc_color_status_cancelled', '#e74c3c')); ?>"></label>
                                    <label>Prep (Pickup): <input type="color" name="mc_color_status_prep_pickup" value="<?php echo esc_attr(get_option('mc_color_status_prep_pickup', '#3498db')); ?>"></label>
                                    <label>Prep (Delivery): <input type="color" name="mc_color_status_prep_deliv" value="<?php echo esc_attr(get_option('mc_color_status_prep_deliv', '#9b59b6')); ?>"></label>
                                    <label>Out for Delivery: <input type="color" name="mc_color_status_out_deliv" value="<?php echo esc_attr(get_option('mc_color_status_out_deliv', '#1abc9c')); ?>"></label>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <script>
                        jQuery(document).ready(function($){
                            $('.mc-img-upload').click(function(e){
                                e.preventDefault(); let target = $($(this).data('target'));
                                let image_frame = wp.media({title: 'Select Image', multiple: false}).on('select', function(){
                                    target.val(image_frame.state().get('selection').first().toJSON().url);
                                }).open();
                            });
                        });
                    </script>
                
                <?php else : ?>
                    <h3>Dashboard Global Theme</h3>
                    <table class="form-table">
                        <tr><th>Default Theme</th><td><select name="mc_dashboard_theme"><option value="light" <?php selected(get_option('mc_dashboard_theme', 'light'), 'light'); ?>>Light Mode</option><option value="dark" <?php selected(get_option('mc_dashboard_theme'), 'dark'); ?>>Dark Mode</option></select></td></tr>
                    </table>
                    <hr>
                    <h3>Order Editing Rules & Toggles</h3>
                    <table class="form-table">
                        <tr>
                            <th>Staff Permissions</th>
                            <td>
                                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#e74c3c;"><input type="checkbox" name="mc_enable_create_order" value="yes" <?php checked(get_option('mc_enable_create_order', 'yes'), 'yes'); ?>> Enable "Create New Order" Button</label>
                                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#e74c3c;"><input type="checkbox" name="mc_enable_item_editing" value="yes" <?php checked(get_option('mc_enable_item_editing', 'yes'), 'yes'); ?>> Enable Line Item Editing (Add/Remove/Edit Products)</label>
                                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#e74c3c;"><input type="checkbox" name="mc_enable_fulfill_editing" value="yes" <?php checked(get_option('mc_enable_fulfill_editing', 'yes'), 'yes'); ?>> Enable Fulfillment Editing (Change Store, Date, Time, Method)</label>
                            </td>
                        </tr>
                        
                        <tr>
                            <th>Quick Add Filter Mode</th>
                            <td>
                                <select name="mc_qadd_mode" id="mc_qadd_mode">
                                    <option value="all" <?php selected(get_option('mc_qadd_mode', 'products'), 'all'); ?>>Show All Products</option>
                                    <option value="categories" <?php selected(get_option('mc_qadd_mode'), 'categories'); ?>>Filter by Categories</option>
                                    <option value="products" <?php selected(get_option('mc_qadd_mode'), 'products'); ?>>Filter by Specific Products Only</option>
                                    <option value="both" <?php selected(get_option('mc_qadd_mode'), 'both'); ?>>Filter by Both</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="row_qadd_cats">
                            <th>Select Categories</th>
                            <td>
                                <?php 
                                $cats = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);
                                $saved_cats = get_option('mc_quick_add_cats', []);
                                echo '<select name="mc_quick_add_cats[]" multiple style="width:100%; max-width:400px; height:100px;">';
                                foreach($cats as $cat) { echo '<option value="'.$cat->term_id.'" '.(in_array($cat->term_id, (array)$saved_cats) ? 'selected' : '').'>'.$cat->name.'</option>'; }
                                echo '</select>';
                                ?>
                            </td>
                        </tr>
                        <tr id="row_qadd_prods">
                            <th>Select Specific Products</th>
                            <td>
                                <?php 
                                $all_prods = get_posts(['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC']);
                                $saved_prods = get_option('mc_quick_add_products', []);
                                echo '<select name="mc_quick_add_products[]" multiple style="width:100%; max-width:400px; height:150px;">';
                                foreach($all_prods as $p) { echo '<option value="'.$p->ID.'" '.(in_array($p->ID, (array)$saved_prods) ? 'selected' : '').'>'.$p->post_title.'</option>'; }
                                echo '</select>';
                                ?>
                                <p class="description">Explicitly allow specific products (like your Grouped Menus or Combos).</p>
                            </td>
                        </tr>
                    </table>
                    
                    <script>
                        jQuery(document).ready(function($){
                            function toggleQAdd() {
                                var mode = $('#mc_qadd_mode').val();
                                if (mode === 'all') { $('#row_qadd_cats, #row_qadd_prods').hide(); } 
                                else if (mode === 'categories') { $('#row_qadd_cats').show(); $('#row_qadd_prods').hide(); } 
                                else if (mode === 'products') { $('#row_qadd_cats').hide(); $('#row_qadd_prods').show(); } 
                                else { $('#row_qadd_cats, #row_qadd_prods').show(); }
                            }
                            $('#mc_qadd_mode').on('change', toggleQAdd);
                            toggleQAdd();
                        });
                    </script>

                    <hr>
                    <h3>Layout Toggles</h3>
                    <table class="form-table">
                        <tr><th>View Method</th><td><select name="mc_order_view_type"><option value="modal" <?php selected(get_option('mc_order_view_type'),'modal');?>>Popup Modal</option><option value="full" <?php selected(get_option('mc_order_view_type'),'full');?>>New Page View</option></select></td></tr>
                        <tr><th>Status Badge Style</th><td><select name="mc_status_pill_style"><option value="text" <?php selected(get_option('mc_status_pill_style', 'text'), 'text'); ?>>Full Text Pill</option><option value="dot" <?php selected(get_option('mc_status_pill_style'), 'dot'); ?>>Minimal Dot Only</option></select></td></tr>
                        
                        <tr><th>Show Item Images</th><td><label><input type="checkbox" name="mc_show_item_images" value="yes" <?php checked(get_option('mc_show_item_images', 'yes'), 'yes'); ?>> Display main product thumbnail next to item name.</label></td></tr>
                        
                        <tr>
                            <th>Allowed Statuses in Dropdown</th>
                            <td>
                                <?php 
                                $all_statuses = wc_get_order_statuses();
                                $all_statuses['wc-prep-pickup'] = 'Preparing (Pickup)';
                                $all_statuses['wc-prep-deliv'] = 'Preparing (Delivery)';
                                $all_statuses['wc-out-deliv'] = 'Out for Delivery';

                                $allowed_status = get_option('mc_manager_allowed_statuses', array_keys($all_statuses));
                                foreach($all_statuses as $k=>$v) {
                                    echo "<label style='display:block; margin-bottom:5px;'><input type='checkbox' name='mc_manager_allowed_statuses[]' value='$k' ".checked(in_array($k,$allowed_status),true,false)."> $v</label>";
                                }
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Active Table Columns</th>
                            <td>
                                <?php 
                                $enabled = get_option('mc_manager_columns', ['status','order_id','customer','purchased','total','actions']);
                                foreach(['status'=>'Status','order_id'=>'Order #','customer'=>'Customer Name','purchased'=>'Items','ship'=>'Ship/Type','date'=>'Date','total'=>'Total','actions'=>'Actions'] as $k=>$v)
                                    echo "<label style='margin-right:20px; display:inline-block; margin-bottom:10px;'><input type='checkbox' name='mc_manager_columns[]' value='$k' ".checked(in_array($k,$enabled),true,false)."> $v</label>";
                                ?>
                            </td>
                        </tr>
                    </table>
                    <hr>
                    <h3>Order Data Visibility (Checklist)</h3>
                    <table class="form-table">
                        <tr>
                            <th>Customer & Fulfillment Data</th>
                            <td>
                                <?php $current_vis = get_option('mc_details_visibility', ['email', 'phone', 'address', 'meta', 'fulfillment', 'badge_store', 'badge_time']); ?>
                                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#e74c3c;"><input type="checkbox" name="mc_details_visibility[]" value="badge_store" <?php checked(in_array('badge_store', $current_vis)); ?>> Header Badge: Show Store Name</label>
                                <label style="display:block; margin-bottom:5px; font-weight:bold; color:#e74c3c;"><input type="checkbox" name="mc_details_visibility[]" value="badge_time" <?php checked(in_array('badge_time', $current_vis)); ?>> Header Badge: Show Date & Time</label>
                                <hr style="margin:10px 0;">
                                <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="mc_details_visibility[]" value="fulfillment" <?php checked(in_array('fulfillment', $current_vis)); ?>> Card: Show Fulfillment Details</label>
                                <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="mc_details_visibility[]" value="email" <?php checked(in_array('email', $current_vis)); ?>> Card: Show Email Address</label>
                                <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="mc_details_visibility[]" value="phone" <?php checked(in_array('phone', $current_vis)); ?>> Card: Show Phone Number</label>
                                <label style="display:block; margin-bottom:5px;"><input type="checkbox" name="mc_details_visibility[]" value="address" <?php checked(in_array('address', $current_vis)); ?>> Card: Show Full Address</label>
                            </td>
                        </tr>
                        <tr>
                            <th>Product Meta (Combos)</th>
                            <td><label><input type="checkbox" name="mc_details_visibility[]" value="meta" <?php checked(in_array('meta', $current_vis)); ?>> Show product add-ons and Combo Builder selections.</label></td>
                        </tr>
                    </table>

                <?php endif; ?>
                <?php submit_button('Update Settings'); ?>
            </div>
        </form>
    </div>
    <?php
}

add_action('admin_init', function() {
    register_setting('mc_manager_general_group', 'mc_manager_page_id');
    register_setting('mc_manager_general_group', 'mc_order_lookback');
    register_setting('mc_manager_general_group', 'mc_orders_per_page');
    register_setting('mc_manager_general_group', 'mc_frontend_notifications');
    register_setting('mc_manager_general_group', 'mc_frontend_interval');
    register_setting('mc_manager_general_group', 'mc_auth_title');
    register_setting('mc_manager_general_group', 'mc_auth_msg');
    register_setting('mc_manager_general_group', 'mc_hide_admin_bar');
    register_setting('mc_manager_general_group', 'mc_prevent_backend_access');
    
    register_setting('mc_manager_design_group', 'mc_brand_name');
    register_setting('mc_manager_design_group', 'mc_brand_logo');
    register_setting('mc_manager_design_group', 'mc_bg_sidebar');
    register_setting('mc_manager_design_group', 'mc_color_username');
    register_setting('mc_manager_design_group', 'mc_color_sidebar');
    register_setting('mc_manager_design_group', 'mc_color_primary');
    register_setting('mc_manager_design_group', 'mc_color_pickup');
    register_setting('mc_manager_design_group', 'mc_color_delivery');
    register_setting('mc_manager_design_group', 'mc_color_paid');
    register_setting('mc_manager_design_group', 'mc_color_unpaid');
    register_setting('mc_manager_design_group', 'mc_color_status_pending');
    register_setting('mc_manager_design_group', 'mc_color_status_processing');
    register_setting('mc_manager_design_group', 'mc_color_status_completed');
    register_setting('mc_manager_design_group', 'mc_color_status_cancelled');
    register_setting('mc_manager_design_group', 'mc_color_status_prep_pickup');
    register_setting('mc_manager_design_group', 'mc_color_status_prep_deliv');
    register_setting('mc_manager_design_group', 'mc_color_status_out_deliv');

    register_setting('mc_manager_ui_group', 'mc_dashboard_theme');
    register_setting('mc_manager_ui_group', 'mc_order_view_type');
    register_setting('mc_manager_ui_group', 'mc_status_pill_style');
    // THE FIX: Register the new toggle option
    register_setting('mc_manager_ui_group', 'mc_show_item_images');
    register_setting('mc_manager_ui_group', 'mc_manager_columns');
    register_setting('mc_manager_ui_group', 'mc_details_visibility');
    register_setting('mc_manager_ui_group', 'mc_manager_allowed_statuses');
    register_setting('mc_manager_ui_group', 'mc_qadd_mode'); 
    register_setting('mc_manager_ui_group', 'mc_quick_add_cats');
    register_setting('mc_manager_ui_group', 'mc_quick_add_products'); 
    register_setting('mc_manager_ui_group', 'mc_enable_create_order');
    register_setting('mc_manager_ui_group', 'mc_enable_item_editing');
    register_setting('mc_manager_ui_group', 'mc_enable_fulfill_editing');
});