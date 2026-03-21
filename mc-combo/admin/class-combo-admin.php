<?php
/**
 * MealCrafter: Combo Builder Backend Admin Settings & WP Admin Order Editor Parity
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_filter( 'woocommerce_product_data_tabs', function( $tabs ) {
    $tabs['mc_combo_settings'] = array(
        'label'    => 'Combo Settings',
        'target'   => 'mc_combo_data_panel',
        'class'    => array( 'show_if_mc_combo' ),
    );
    return $tabs;
});

add_action( 'woocommerce_product_data_panels', 'mc_combo_admin_panels' );
function mc_combo_admin_panels() {
    global $post;
    $combo_data = get_post_meta( $post->ID, '_mc_combo_meta', true ) ?: [];
    $categories = get_terms('product_cat', ['hide_empty' => false]);
    wp_nonce_field( 'mc_save_combo_action', 'mc_combo_nonce' );
    ?>
    <style>
        .mc-admin-step { background: #fff; border: 1px solid #ddd; padding: 20px; margin-bottom: 20px; border-radius: 8px; position: relative; border-left: 5px solid #ccc; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .mc-remove-step { position: absolute; top: 15px; right: 15px; color: #a00; text-decoration: none; font-weight: bold; font-size: 18px; }
        .mc-flex-row { display: flex; gap: 20px; margin-bottom: 15px; align-items: flex-end; flex-wrap: wrap; }
        .mc-field { flex: 1; min-width: 150px; display: flex; flex-direction: column; }
        .mc-hidden { display: none !important; }
        
        /* ALIGNMENT FIX */
        .mc-admin-step label { float: none !important; width: auto !important; margin: 0 0 5px 0 !important; display: block !important; font-weight: 700 !important; text-align: left !important; color: #333 !important; }
        .mc-admin-step input[type="text"], .mc-admin-step input[type="number"], .mc-admin-step select { width: 100% !important; float: none !important; margin: 0 !important; height: 35px !important; }
        .mc-admin-step input[type="checkbox"] { margin: 0 5px 0 0 !important; float: none !important; vertical-align: middle; }
    </style>

    <div id="mc_combo_data_panel" class="panel woocommerce_options_panel">
        <div id="mc-combo-steps-container" style="padding: 15px;">
            <?php if (empty($combo_data)) $combo_data = [[]];
            foreach ( $combo_data as $index => $slot ) : 
                mc_render_combo_step_row($index, $slot, $categories);
            endforeach; ?>
        </div>
        <div style="padding: 0 15px 15px;">
            <button type="button" class="button button-primary" id="mc-add-step">+ Add Combo Step</button>
        </div>
    </div>

    <script type="text/html" id="mc-step-template">
        <?php mc_render_combo_step_row('__INDEX__', [], $categories); ?>
    </script>

    <script>
    jQuery(document).ready(function($) {
        $('.general_options, .options_group.pricing').addClass('show_if_mc_combo');
        
        $('#mc-add-step').on('click', function() {
            let index = $('.mc-admin-step').length;
            let html = $('#mc-step-template').html().replace(/__INDEX__/g, index);
            $('#mc-combo-steps-container').append(html);
            $(document.body).trigger('wc-enhanced-select-init');
        });

        $(document).on('click', '.mc-remove-step', function() {
            if(confirm('Remove step?')) $(this).closest('.mc-admin-step').remove();
        });

        $(document).on('change', '.mc-source-type', function() {
            let row = $(this).closest('.mc-admin-step');
            row.find('.mc-source-cat, .mc-source-prod').addClass('mc-hidden');
            if($(this).val() === 'category') row.find('.mc-source-cat').removeClass('mc-hidden');
            else row.find('.mc-source-prod').removeClass('mc-hidden');
        });
        
        $(document).on('input', '.mc-color-picker', function() {
            $(this).closest('.mc-admin-step').css('border-left-color', $(this).val());
        });
        $('.mc-color-picker').trigger('input');
    });
    </script>
    <?php
}

function mc_render_combo_step_row($index, $slot, $categories) {
    $type = $slot['type'] ?? 'category';
    $items = $slot['items'] ?? [];
    $sort_by = $slot['sort_by'] ?? 'name_asc';
    ?>
    <div class="mc-admin-step">
        <a href="#" class="mc-remove-step">&times;</a>
        <div class="mc-flex-row">
            <div class="mc-field" style="flex:2;">
                <label>Step Name</label>
                <input type="text" name="mc_combo[<?php echo $index; ?>][name]" value="<?php echo esc_attr($slot['name'] ?? ''); ?>">
            </div>
            <div class="mc-field">
                <label>Theme Color</label>
                <input type="color" name="mc_combo[<?php echo $index; ?>][color]" class="mc-color-picker" value="<?php echo esc_attr($slot['color'] ?? '#e74c3c'); ?>">
            </div>
            <div class="mc-field">
                <label>Limit</label>
                <input type="number" name="mc_combo[<?php echo $index; ?>][limit]" value="<?php echo esc_attr($slot['limit'] ?? 1); ?>">
            </div>
            <div class="mc-field" style="flex-direction: row; gap: 8px; min-width: 100px;">
                <input type="checkbox" name="mc_combo[<?php echo $index; ?>][required]" value="1" <?php checked($slot['required'] ?? 0, 1); ?>>
                <label>Required?</label>
            </div>
        </div>
        <div class="mc-field" style="margin-bottom:15px;">
            <label>Description</label>
            <input type="text" name="mc_combo[<?php echo $index; ?>][description]" value="<?php echo esc_attr($slot['description'] ?? ''); ?>" placeholder="Instructions for the user...">
        </div>
        <div class="mc-flex-row" style="background:#f9f9f9; padding:15px; border-radius:6px;">
            <div class="mc-field">
                <label>Source</label>
                <select class="mc-source-type" name="mc_combo[<?php echo $index; ?>][type]">
                    <option value="category" <?php selected($type, 'category'); ?>>Category</option>
                    <option value="products" <?php selected($type, 'products'); ?>>Specific Products</option>
                </select>
            </div>
            <div class="mc-field mc-source-cat <?php echo $type === 'products' ? 'mc-hidden' : ''; ?>">
                <label>Category</label>
                <select name="mc_combo[<?php echo $index; ?>][category_id]">
                    <?php foreach($categories as $cat) echo '<option value="'.$cat->term_id.'" '.selected($type === 'category' ? $items : '', $cat->term_id, false).'>'.$cat->name.'</option>'; ?>
                </select>
            </div>
            <div class="mc-field mc-source-prod <?php echo $type === 'category' ? 'mc-hidden' : ''; ?>">
                <label>Search Products</label>
                <select name="mc_combo[<?php echo $index; ?>][product_ids][]" class="wc-product-search" multiple="multiple">
                    <?php if($type === 'products') foreach($items as $pid) { $p = wc_get_product($pid); if($p) echo '<option value="'.$pid.'" selected>'.$p->get_name().'</option>'; } ?>
                </select>
            </div>
            
            <div class="mc-field">
                <label>Sort By</label>
                <select name="mc_combo[<?php echo $index; ?>][sort_by]">
                    <option value="name_asc" <?php selected($sort_by, 'name_asc'); ?>>Name (A-Z)</option>
                    <option value="name_desc" <?php selected($sort_by, 'name_desc'); ?>>Name (Z-A)</option>
                    <option value="date_desc" <?php selected($sort_by, 'date_desc'); ?>>Newest First</option>
                    <option value="menu_order" <?php selected($sort_by, 'menu_order'); ?>>Custom Order</option>
                    <option value="rand" <?php selected($sort_by, 'rand'); ?>>Random</option>
                </select>
            </div>
            
        </div>
    </div>
    <?php
}

add_action( 'woocommerce_process_product_meta', function( $post_id ) {
    if ( ! isset( $_POST['mc_combo_nonce'] ) || ! wp_verify_nonce( $_POST['mc_combo_nonce'], 'mc_save_combo_action' ) ) return;
    if ( isset( $_POST['mc_combo'] ) ) {
        $data = [];
        foreach($_POST['mc_combo'] as $slot) {
            $type = $slot['type'];
            $items = ($type === 'category') ? intval($slot['category_id']) : ($slot['product_ids'] ?? []);
            $data[] = [
                'name' => sanitize_text_field($slot['name']),
                'description' => sanitize_text_field($slot['description']),
                'color' => sanitize_hex_color($slot['color']),
                'limit' => intval($slot['limit']),
                'required' => isset($slot['required']) ? 1 : 0,
                'type' => $type,
                'items' => $items,
                'sort_by' => sanitize_text_field($slot['sort_by'] ?? 'name_asc')
            ];
        }
        update_post_meta( $post_id, '_mc_combo_meta', $data );
    }
});


// =====================================================================
// THE FIX: WP ADMIN BACKEND COMBO PARITY
// =====================================================================

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
    $order_id = is_numeric($post_or_order) ? $post_or_order : $post_or_order->ID;
    $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish', 'tax_query' => [['taxonomy' => 'product_type', 'field' => 'slug', 'terms' => ['mc_combo', 'mc_grouped']]]];
    $products = get_posts($args);
    
    echo '<div style="margin-bottom:10px;"><select id="mc-backend-add-select" style="width:100%;">';
    echo '<option value="">-- Add Combo / Grouped --</option>';
    foreach($products as $p) { echo '<option value="'.$p->ID.'">'.$p->post_title.'</option>'; }
    echo '</select></div>';
    echo '<button type="button" class="button button-primary" id="mc-backend-add-btn" data-order_id="'.esc_attr($order_id).'" style="width:100%;">Build & Add to Order</button>';
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
            $('body').append(`
                <div id="mc-combo-build-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999999; align-items:center; justify-content:center;">
                    <div id="mc-combo-build-content" style="background:#f0f0f1; width:95%; max-width:1300px; height:90vh; border-radius:15px; overflow-y:auto; position:relative; box-shadow:0 15px 50px rgba(0,0,0,0.5);">
                        <span id="mc-combo-build-close" style="position:absolute; top:20px; right:20px; font-size:30px; cursor:pointer; color:#999; z-index:1000;">×</span>
                        <div id="mc-combo-render-area"></div>
                    </div>
                </div>
            `);
        }

        $('#mc-combo-build-close').on('click', function() { 
            $('#mc-combo-build-modal').hide(); 
            $('#mc-combo-render-area').empty(); 
        });

        function smartAdminReload(orderId) {
            let adminUrl = '<?php echo admin_url(); ?>';
            let url = window.location.href;
            if (url.includes('post-new.php')) { window.location.href = adminUrl + 'post.php?post=' + orderId + '&action=edit'; } 
            else if (url.includes('page=wc-orders') && url.includes('action=new')) { window.location.href = adminUrl + 'admin.php?page=wc-orders&action=edit&id=' + orderId; } 
            else { location.reload(); }
        }

        // Make it accessible for Grouped Hub
        window.loadOrderDetails = function(orderId) { smartAdminReload(orderId); };

        // ==========================================
        // ONLY APPLY THE DUMMY BUTTON TO COMBOS
        // Grouped products handle themselves!
        // ==========================================
        function injectSafeBackendButtons(orderId, productId, itemId = 0) {
            let $nativeCombo = $('#mc-submit-combo');
            
            // If it's a Combo, we hide the real button and create a safe dummy button
            if ($nativeCombo.length) {
                $nativeCombo.css('display', 'none'); 
                $('#mc-safe-backend-combo').remove(); // Clean up any old ones
                
                // Insert our dummy button
                $nativeCombo.after('<button type="button" id="mc-safe-backend-combo" class="mc-hub-add-btn" style="width:100%; padding:18px; border-radius:40px; background:#e74c3c; color:#fff; font-weight:700; text-transform:uppercase; cursor:pointer; font-size:16px; margin-top:10px; border:none; opacity:0.5;">Add to Order</button>');
                
                // Sync the dummy button to the native button's validation state!
                setInterval(() => {
                    if ($nativeCombo.length) {
                        $('#mc-safe-backend-combo').text($nativeCombo.text());
                        if ($nativeCombo.hasClass('mc-ready')) {
                            $('#mc-safe-backend-combo').css('opacity', '1').css('cursor', 'pointer').addClass('mc-ready');
                        } else {
                            $('#mc-safe-backend-combo').css('opacity', '0.5').css('cursor', 'not-allowed').removeClass('mc-ready');
                        }
                    }
                }, 100);

                // Bind the save action ONLY to our Safe Button
                $('#mc-safe-backend-combo').off('click').on('click', function(e2) {
                    e2.preventDefault(); e2.stopImmediatePropagation();
                    
                    if (!$(this).hasClass('mc-ready')) { return; }
                    $(this).html('SAVING...').css('pointer-events', 'none'); 
                    
                    let postData = { action: 'mc_portal_save_combo_to_order', security: mc_nonce, order_id: orderId, product_id: productId };
                    if (itemId > 0) { postData.replace_item_id = itemId; }

                    let items = [];
                    $('.mc-combo-card.has-qty').each(function() {
                        let id = $(this).data('id'); let q = parseInt($(this).find('.mc-qty-num').text()); let slotName = $(this).closest('.mc-step-container').find('.mc-step-title-text').text();
                        for(let i=0; i<q; i++) { items.push({ id: id, slot: slotName }); }
                    });
                    postData.mc_combo_items = items;

                    $.post(ajaxurl, postData, function(cr) {
                        if(cr.success) { smartAdminReload(orderId); } else { alert("Failed to add/update item."); }
                    }).fail(function(){ alert("Server Error. Check console."); });
                });
            }
        }


        // EDIT EXISTING ITEM
        $(document).on('click', '.mc-backend-edit-item', function(e) {
            e.preventDefault(); 
            let btn = $(this); let orderId = btn.data('order_id'); let itemId = btn.data('item_id');
            let originalText = btn.text();
            btn.text('Loading...').prop('disabled', true);

            $.post(ajaxurl, { action: 'mc_portal_edit_item_ui', order_id: orderId, item_id: itemId, security: mc_nonce }, function(res) {
                if(res.success) {
                    $('#mc-combo-render-area').html(res.data.html); 
                    $('#mc-combo-build-modal').css('display', 'flex');
                    $('#mc-combo-render-area').attr('data-active-order', orderId);

                    setTimeout(() => {
                        let prefillStr = $('#mc-edit-combo-injector').data('prefill'); 
                        let prefillData = [];
                        try {
                            if (typeof prefillStr === 'string' && prefillStr.trim() !== '') { prefillData = JSON.parse(prefillStr); } 
                            else if (typeof prefillStr === 'object') { prefillData = prefillStr; }
                        } catch(e) { console.log('Prefill parse error'); }
                        
                        injectSafeBackendButtons(orderId, res.data.product_id, itemId);
                        
                        if (prefillData && prefillData.length > 0 && $('#mc-edit-combo-injector').data('type') === 'mc_combo') {
                            $('#mc-combo-app').attr('data-prefill', JSON.stringify(prefillData)); 
                            $('#mc-combo-app').attr('data-prefill-qty', $('#mc-edit-combo-injector').data('qty'));
                            setTimeout(() => { $('.mc-change-sel').first().trigger('click'); }, 100);
                        }
                    }, 500);
                } else { alert("Unable to load editor. Make sure product exists."); }
                btn.text(originalText).prop('disabled', false);
            }).fail(function() { alert("AJAX Error."); btn.text(originalText).prop('disabled', false); });
        });

        // ADD NEW ITEM VIA METABOX
        $('#mc-backend-add-btn').on('click', function(e) {
            e.preventDefault(); let btn = $(this); let orderId = btn.data('order_id'); let productId = $('#mc-backend-add-select').val();
            if(!productId) { alert('Select a product first.'); return; }
            btn.text('Loading...').prop('disabled', true);

            $.post(ajaxurl, { action: 'mc_portal_add_item', order_id: orderId, product_id: productId, security: mc_nonce }, function(res) {
                if(res.success && (res.data.is_combo || res.data.is_grouped)) {
                    $('#mc-combo-render-area').html(res.data.html); 
                    $('#mc-combo-build-modal').css('display', 'flex');
                    $('#mc-combo-render-area').attr('data-active-order', orderId);

                    setTimeout(() => {
                        injectSafeBackendButtons(orderId, productId);
                    }, 500);
                } else if (res.success) { smartAdminReload(orderId); } 
                else { alert("Error adding item."); }
                btn.text('Build & Add to Order').prop('disabled', false);
            }).fail(function() { alert("AJAX Error."); btn.text('Build & Add to Order').prop('disabled', false); });
        });
    });
    </script>
    <?php
}

// Hide the raw JSON combo data from the WooCommerce Admin Order Screen
add_filter( 'woocommerce_hidden_order_itemmeta', function( $hidden_meta ) {
    $hidden_meta[] = '_mc_raw_combo_data';
    return $hidden_meta;
});