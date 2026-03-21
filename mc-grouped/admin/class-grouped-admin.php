<?php
/**
 * MealCrafter: Grouped Product Admin Panel (Slots Builder & Backend Parity)
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'woocommerce_product_data_tabs', 'mc_grouped_admin_tabs' );
function mc_grouped_admin_tabs( $tabs ) {
    $tabs['mc_grouped_settings'] = array(
        'label'    => 'Grouped Settings',
        'target'   => 'mc_grouped_data_panel',
        'class'    => array( 'show_if_mc_grouped' ),
    );
    return $tabs;
}

add_action( 'woocommerce_product_data_panels', 'mc_grouped_admin_panels' );
function mc_grouped_admin_panels() {
    global $post;
    
    // Global Settings
    $grid_columns = get_post_meta( $post->ID, '_mc_grid_columns', true ) ?: 'auto';
    $animation    = get_post_meta( $post->ID, '_mc_animation', true ) ?: 'popup';
    $popup_image  = get_post_meta( $post->ID, '_mc_popup_image', true ) ?: 'show';
    $fly_anim     = get_post_meta( $post->ID, '_mc_fly_animation', true ) ?: 'on';
    
    // Slots Data
    $slots = get_post_meta( $post->ID, '_mc_grouped_slots', true ) ?: [];
    
    // Get all categories for the dropdowns
    $categories = get_terms('product_cat', array('hide_empty' => false));
    ?>
    <div id="mc_grouped_data_panel" class="panel woocommerce_options_panel">
        
        <?php wp_nonce_field( 'mc_save_grouped_meta_action', 'mc_grouped_meta_nonce' ); ?>

        <div class="options_group" style="background: #f9f9f9; border-bottom: 2px solid #eee;">
            <h4 style="padding: 0 12px; margin-bottom: 0;">Global Display Settings</h4>
            <p class="form-field">
                <label for="mc_grid_columns"><strong>Desktop Grid Columns</strong></label>
                <select id="mc_grid_columns" name="mc_grid_columns" style="width: 50%;">
                    <option value="auto" <?php selected($grid_columns, 'auto'); ?>>Auto-fit (Responsive)</option>
                    <option value="2" <?php selected($grid_columns, '2'); ?>>2 Columns</option>
                    <option value="3" <?php selected($grid_columns, '3'); ?>>3 Columns</option>
                    <option value="4" <?php selected($grid_columns, '4'); ?>>4 Columns</option>
                    <option value="5" <?php selected($grid_columns, '5'); ?>>5 Columns</option>
                    <option value="6" <?php selected($grid_columns, '6'); ?>>6 Columns</option>
                </select>
            </p>
            <p class="form-field">
                <label for="mc_animation"><strong>Panel Animation</strong></label>
                <select id="mc_animation" name="mc_animation" style="width: 50%;">
                    <option value="popup" <?php selected($animation, 'popup'); ?>>Center Popup</option>
                    <option value="slide_right" <?php selected($animation, 'slide_right'); ?>>Slide from Right</option>
                    <option value="slide_bottom" <?php selected($animation, 'slide_bottom'); ?>>Slide from Bottom</option>
                </select>
            </p>
            <p class="form-field">
                <label for="mc_popup_image"><strong>Popup Image</strong></label>
                <select id="mc_popup_image" name="mc_popup_image" style="width: 50%;">
                    <option value="show" <?php selected($popup_image, 'show'); ?>>Show Image</option>
                    <option value="hide" <?php selected($popup_image, 'hide'); ?>>Hide Image</option>
                </select>
            </p>
            <p class="form-field">
                <label for="mc_fly_animation"><strong>Fly to Cart Effect</strong></label>
                <select id="mc_fly_animation" name="mc_fly_animation" style="width: 50%;">
                    <option value="on" <?php selected($fly_anim, 'on'); ?>>Enable</option>
                    <option value="off" <?php selected($fly_anim, 'off'); ?>>Disable</option>
                </select>
            </p>
        </div>

        <div class="options_group">
            <h4 style="padding: 0 12px; margin-bottom: 10px;">Menu Sections (Slots)</h4>
            <div id="mc-slots-container" style="padding: 0 12px;">
                <?php 
                if (!empty($slots)) {
                    foreach ($slots as $index => $slot) {
                        mc_render_admin_slot($index, $slot, $categories);
                    }
                }
                ?>
            </div>
            <div style="padding: 15px 12px;">
                <button type="button" class="button button-primary" id="mc-add-slot-btn">+ Add New Section</button>
            </div>
        </div>

    </div>

    <script type="text/html" id="mc-slot-template">
        <?php mc_render_admin_slot('__INDEX__', ['title'=>'', 'source_type'=>'category', 'category'=>'', 'products'=>[], 'sort_by'=>'name_asc'], $categories); ?>
    </script>

    <style>
        .mc-admin-slot { background: #fff; border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 4px; position: relative; }
        .mc-remove-slot { position: absolute; top: 15px; right: 15px; color: #a00; text-decoration: none; font-weight: bold; }
        .mc-remove-slot:hover { color: #f00; }
        .mc-admin-slot .form-field { padding: 5px 0 !important; margin: 0 !important; }
    </style>

    <script>
    jQuery(document).ready(function($){
        let slotIndex = <?php echo count($slots); ?>;

        // Add Slot
        $('#mc-add-slot-btn').on('click', function(e){
            e.preventDefault();
            let html = $('#mc-slot-template').html().replace(/__INDEX__/g, slotIndex);
            $('#mc-slots-container').append(html);
            slotIndex++;
            $(document.body).trigger('wc-enhanced-select-init');
        });

        // Remove Slot
        $(document).on('click', '.mc-remove-slot', function(e){
            e.preventDefault();
            if(confirm('Remove this section?')) { $(this).closest('.mc-admin-slot').remove(); }
        });

        // Toggle Source Fields
        $(document).on('change', '.mc-source-type-select', function(){
            let $slot = $(this).closest('.mc-admin-slot');
            $slot.find('.mc-wrap-category, .mc-wrap-products').hide();
            if($(this).val() === 'category') { $slot.find('.mc-wrap-category').show(); } else { $slot.find('.mc-wrap-products').show(); }
        });
    });
    </script>
    <?php
}

// Helper function to render a single slot box in the admin
function mc_render_admin_slot($index, $data, $categories) {
    ?>
    <div class="mc-admin-slot">
        <a href="#" class="mc-remove-slot">Remove &times;</a>
        
        <p class="form-field">
            <label><strong>Section Title</strong></label>
            <input type="text" name="mc_slots[<?php echo $index; ?>][title]" value="<?php echo esc_attr($data['title']); ?>" placeholder="e.g. Wings, Snakkas, Drinks" style="width: 50%;">
        </p>

        <p class="form-field">
            <label>Data Source</label>
            <select name="mc_slots[<?php echo $index; ?>][source_type]" class="mc-source-type-select" style="width: 50%;">
                <option value="category" <?php selected($data['source_type'], 'category'); ?>>Category</option>
                <option value="products" <?php selected($data['source_type'], 'products'); ?>>Specific Products</option>
            </select>
        </p>

        <p class="form-field mc-wrap-category" style="<?php echo $data['source_type'] === 'category' ? '' : 'display:none;'; ?>">
            <label>Select Category</label>
            <select name="mc_slots[<?php echo $index; ?>][category]" style="width: 50%;">
                <option value="">-- Choose Category --</option>
                <?php foreach($categories as $cat) : ?>
                    <option value="<?php echo $cat->term_id; ?>" <?php selected($data['category'], $cat->term_id); ?>><?php echo $cat->name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>

        <p class="form-field mc-wrap-products" style="<?php echo $data['source_type'] === 'products' ? '' : 'display:none;'; ?>">
            <label>Search Products</label>
            <select name="mc_slots[<?php echo $index; ?>][products][]" class="wc-product-search" multiple="multiple" style="width: 50%;">
                <?php
                if (!empty($data['products'])) {
                    foreach($data['products'] as $p_id) {
                        $p = wc_get_product($p_id);
                        if($p) echo '<option value="'.$p_id.'" selected="selected">'.$p->get_name().'</option>';
                    }
                }
                ?>
            </select>
        </p>
        
        <p class="form-field">
            <label>Sort By</label>
            <select name="mc_slots[<?php echo $index; ?>][sort_by]" style="width: 50%;">
                <option value="name_asc" <?php selected($data['sort_by'] ?? 'name_asc', 'name_asc'); ?>>Name (A-Z)</option>
                <option value="name_desc" <?php selected($data['sort_by'] ?? 'name_asc', 'name_desc'); ?>>Name (Z-A)</option>
                <option value="date_desc" <?php selected($data['sort_by'] ?? 'name_asc', 'date_desc'); ?>>Newest First</option>
                <option value="menu_order" <?php selected($data['sort_by'] ?? 'name_asc', 'menu_order'); ?>>Custom Menu Order</option>
            </select>
        </p>
    </div>
    <?php
}

add_action( 'woocommerce_process_product_meta', 'mc_save_grouped_product_meta' );
function mc_save_grouped_product_meta( $post_id ) {
    if ( ! isset( $_POST['mc_grouped_meta_nonce'] ) || ! wp_verify_nonce( $_POST['mc_grouped_meta_nonce'], 'mc_save_grouped_meta_action' ) ) return;

    // Save Global Settings
    update_post_meta( $post_id, '_mc_grid_columns', sanitize_text_field($_POST['mc_grid_columns'] ?? 'auto') );
    update_post_meta( $post_id, '_mc_animation', sanitize_text_field($_POST['mc_animation'] ?? 'popup') );
    update_post_meta( $post_id, '_mc_popup_image', sanitize_text_field($_POST['mc_popup_image'] ?? 'show') );
    update_post_meta( $post_id, '_mc_fly_animation', sanitize_text_field($_POST['mc_fly_animation'] ?? 'on') );

    // Save Slots Data
    $slots = [];
    if (isset($_POST['mc_slots']) && is_array($_POST['mc_slots'])) {
        foreach ($_POST['mc_slots'] as $slot) {
            $slots[] = [
                'title'       => sanitize_text_field($slot['title'] ?? ''),
                'source_type' => sanitize_text_field($slot['source_type'] ?? 'category'),
                'category'    => intval($slot['category'] ?? 0),
                'products'    => isset($slot['products']) ? array_map('intval', $slot['products']) : [],
                'sort_by'     => sanitize_text_field($slot['sort_by'] ?? 'name_asc'),
            ];
        }
    }
    update_post_meta( $post_id, '_mc_grouped_slots', $slots );
}

// =====================================================================
// FALLBACK: WP ADMIN BACKEND PARITY (If Combo plugin isn't active)
// =====================================================================

if ( ! function_exists('mc_backend_combo_adder_metabox') ) {

    add_action( 'woocommerce_after_order_itemmeta', 'mc_backend_edit_combo_button', 10, 3 );
    function mc_backend_edit_combo_button( $item_id, $item, $product ) {
        if ( $product && ( $product->get_type() === 'mc_combo' || $product->get_type() === 'mc_grouped' ) ) {
            echo '<br><button type="button" class="button mc-backend-edit-item" data-item_id="' . esc_attr($item_id) . '" data-order_id="' . esc_attr($item->get_order_id()) . '" style="margin-top: 8px;">Edit Selections</button>';
        }
    }

    add_action( 'add_meta_boxes', 'mc_backend_combo_adder_metabox' );
    function mc_backend_combo_adder_metabox() {
        $screen = class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') && wc_get_container()->get(\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled() ? wc_get_page_screen_id('shop-order') : 'shop_order';
        add_meta_box( 'mc_backend_adder', 'MealCrafter Builder', 'mc_render_backend_adder', $screen, 'side', 'high' );
    }

    function mc_render_backend_adder( $post_or_order ) {
        $order_id = 0;
        if ( is_numeric($post_or_order) ) { $order_id = $post_or_order; }
        elseif ( is_object($post_or_order) && method_exists($post_or_order, 'get_id') ) { $order_id = $post_or_order->get_id(); }
        elseif ( is_object($post_or_order) && isset($post_or_order->ID) ) { $order_id = $post_or_order->ID; }

        $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'tax_query' => [['taxonomy' => 'product_type', 'field' => 'slug', 'terms' => ['mc_combo', 'mc_grouped']]]];
        $products = get_posts($args);
        
        echo '<div style="margin-bottom:10px;"><select id="mc-backend-add-select" style="width:100%;"><option value="">-- Add Combo / Grouped --</option>';
        foreach($products as $p) { echo '<option value="'.$p->ID.'">'.$p->post_title.'</option>'; }
        echo '</select></div><button type="button" class="button button-primary" id="mc-backend-add-btn" data-order_id="'.esc_attr($order_id).'" style="width:100%;">Build & Add to Order</button>';
    }

    add_action( 'admin_footer', 'mc_backend_combo_js' );
    function mc_backend_combo_js() {
        $screen = get_current_screen();
        if ( ! $screen || ! in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders']) ) return;
        ?>
        <script>
        jQuery(document).ready(function($) {
            window.mc_portal_nonce = '<?php echo wp_create_nonce('mc_portal_secure'); ?>';
            let mc_nonce = window.mc_portal_nonce;

            if ($('#mc-combo-build-modal').length === 0) {
                $('body').append(`<div id="mc-combo-build-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999999; align-items:center; justify-content:center;"><div id="mc-combo-build-content" style="background:#f0f0f1; width:95%; max-width:1300px; height:90vh; border-radius:15px; overflow-y:auto; position:relative; box-shadow:0 15px 50px rgba(0,0,0,0.5);"><span id="mc-combo-build-close" style="position:absolute; top:20px; right:20px; font-size:30px; cursor:pointer; color:#999; z-index:1000;">×</span><div id="mc-combo-render-area"></div></div></div>`);
            }

            $('#mc-combo-build-close').on('click', function() { $('#mc-combo-build-modal').hide(); $('#mc-combo-render-area').empty(); });

            function smartAdminReload(orderId) {
                let adminUrl = '<?php echo admin_url(); ?>';
                let url = window.location.href;
                if (url.includes('post-new.php')) { window.location.href = adminUrl + 'post.php?post=' + orderId + '&action=edit'; } 
                else if (url.includes('page=wc-orders') && url.includes('action=new')) { window.location.href = adminUrl + 'admin.php?page=wc-orders&action=edit&id=' + orderId; } 
                else { location.reload(); }
            }

            function isolateBackendPopup() {
                $('#mc-combo-render-area form').on('submit', function(e) { e.preventDefault(); });
                let comboBtn = $('#mc-submit-combo'); if (comboBtn.length) { comboBtn.attr('id', 'mc-backend-submit-combo').attr('type', 'button'); }
                let groupedBtn = $('#mc-grouped-hub-confirm'); if (groupedBtn.length) { groupedBtn.attr('id', 'mc-backend-submit-grouped').attr('type', 'button'); }
            }

            $(document).on('click', '.mc-backend-edit-item', function(e) {
                e.preventDefault(); let btn = $(this); let orderId = btn.data('order_id'); let itemId = btn.data('item_id');
                let originalText = btn.text(); btn.text('Loading...').prop('disabled', true);

                $.post(ajaxurl, { action: 'mc_portal_edit_item_ui', order_id: orderId, item_id: itemId, security: mc_nonce }, function(res) {
                    if(res.success) {
                        $('#mc-combo-render-area').html(res.data.html); isolateBackendPopup();
                        $('#mc-combo-build-modal').css('display', 'flex'); $('#mc-combo-render-area').attr('data-active-order', orderId);

                        setTimeout(() => {
                            let prefillStr = $('#mc-edit-combo-injector').data('prefill'); let prefillData = [];
                            try { if (typeof prefillStr === 'string' && prefillStr.trim() !== '') { prefillData = JSON.parse(prefillStr); } else if (typeof prefillStr === 'object') { prefillData = prefillStr; } } catch(e) { }
                            
                            $('#mc-backend-submit-combo, #mc-backend-submit-grouped').off('click').on('click', function(e2) {
                                e2.preventDefault(); e2.stopImmediatePropagation();
                                let isCombo = $(this).attr('id') === 'mc-backend-submit-combo';
                                if (isCombo && !$(this).hasClass('mc-ready')) { alert("Please complete required steps."); return; }
                                $(this).html('SAVING...').css('pointer-events', 'none'); 
                                
                                let postData = { action: 'mc_portal_save_combo_to_order', security: mc_nonce, order_id: orderId, product_id: res.data.product_id, replace_item_id: itemId };
                                if (isCombo) {
                                    let items = []; $('.mc-combo-card.has-qty').each(function() {
                                        let id = $(this).data('id'); let q = parseInt($(this).find('.mc-qty-num').text()); let slotName = $(this).closest('.mc-step-container').find('.mc-step-title-text').text();
                                        for(let i=0; i<q; i++) { items.push({ id: id, slot: slotName }); }
                                    }); postData.mc_combo_items = items;
                                } else {
                                    let items = []; $('.mc-hub-card.mc-active-sel').each(function() { items.push($(this).data('id')); });
                                    if(items.length === 0 && $('#mc-popup-img').is(':visible')) { let fallbackId = $('.mc-hub-card.mc-active-sel').data('id'); if(fallbackId) items.push(fallbackId); }
                                    postData.mc_grouped_items = items;
                                }
                                $.post(ajaxurl, postData, function(cr) { if(cr.success) { smartAdminReload(orderId); } else { alert("Failed to update."); } });
                            });
                            
                            if (prefillData && prefillData.length > 0 && $('#mc-edit-combo-injector').data('type') === 'mc_combo') {
                                $('#mc-combo-app').attr('data-prefill', JSON.stringify(prefillData)); $('#mc-combo-app').attr('data-prefill-qty', $('#mc-edit-combo-injector').data('qty'));
                                setTimeout(() => { $('.mc-change-sel').first().trigger('click'); }, 100);
                            }
                        }, 500);
                    } else { alert("Unable to load editor."); }
                    btn.text(originalText).prop('disabled', false);
                });
            });

            $('#mc-backend-add-btn').on('click', function(e) {
                e.preventDefault(); let btn = $(this); let orderId = btn.data('order_id'); let productId = $('#mc-backend-add-select').val();
                if(!productId) { alert('Select a product first.'); return; }
                btn.text('Loading...').prop('disabled', true);

                $.post(ajaxurl, { action: 'mc_portal_add_item', order_id: orderId, product_id: productId, security: mc_nonce }, function(res) {
                    if(res.success && (res.data.is_combo || res.data.is_grouped)) {
                        $('#mc-combo-render-area').html(res.data.html); isolateBackendPopup();
                        $('#mc-combo-build-modal').css('display', 'flex'); $('#mc-combo-render-area').attr('data-active-order', orderId);

                        setTimeout(() => {
                            $('#mc-backend-submit-combo, #mc-backend-submit-grouped').off('click').on('click', function(e2) {
                                e2.preventDefault(); e2.stopImmediatePropagation();
                                let isCombo = $(this).attr('id') === 'mc-backend-submit-combo';
                                if (isCombo && !$(this).hasClass('mc-ready')) { alert("Please complete required steps."); return; }
                                $(this).html('SAVING...').css('pointer-events', 'none'); 
                                
                                let postData = { action: 'mc_portal_save_combo_to_order', security: mc_nonce, order_id: orderId, product_id: productId };
                                if (isCombo) {
                                    let items = []; $('.mc-combo-card.has-qty').each(function() {
                                        let id = $(this).data('id'); let q = parseInt($(this).find('.mc-qty-num').text()); let slotName = $(this).closest('.mc-step-container').find('.mc-step-title-text').text();
                                        for(let i=0; i<q; i++) { items.push({ id: id, slot: slotName }); }
                                    }); postData.mc_combo_items = items;
                                } else {
                                    let items = []; $('.mc-hub-card.mc-active-sel').each(function() { items.push($(this).data('id')); });
                                    if(items.length === 0 && $('#mc-popup-img').is(':visible')) { let fallbackId = $('.mc-hub-card.mc-active-sel').data('id'); if(fallbackId) items.push(fallbackId); }
                                    postData.mc_grouped_items = items;
                                }
                                $.post(ajaxurl, postData, function(cr) { if(cr.success) { smartAdminReload(orderId); } else { alert("Failed to add item."); } });
                            });
                        }, 500);
                    } else if (res.success) { smartAdminReload(orderId); } 
                    btn.text('Build & Add to Order').prop('disabled', false);
                });
            });
        });
        </script>
        <?php
    }
}