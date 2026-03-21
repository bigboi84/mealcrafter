<?php
/**
 * MealCrafter: Advanced Badge Designer (v6.1 Pro)
 * Integrated with Professional Hub Sidebar
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ==============================================================================
// 1. PROFESSIONAL HUB INTEGRATION (Replaces old Sidebar logic)
// ==============================================================================

/**
 * Hooks the Badge Management into the Master MealCrafter Sidebar
 */
add_action( 'mc_register_plugin_submenus', 'mc_badges_register_hub_submenu' );

function mc_badges_register_hub_submenu( $parent_slug ) {
    add_submenu_page(
        $parent_slug,                     // 'mc-hub' passed from Core
        __( 'Badge Management', 'mc-badges' ),
        __( 'Badge Management', 'mc-badges' ),
        'manage_options',
        'mc-badges',                      // Unique slug for the badges page
        'mc_render_badges_hub_view'       // The function that renders the tabbed interface
    );
}

/**
 * Renders the Main View for Badges (Includes the Create New button and List)
 */
function mc_render_badges_hub_view() {
    $badges = get_posts(['post_type' => 'mc_badge', 'post_status' => 'any', 'numberposts' => -1]);
    ?>
    <div class="wrap">
        <h1 style="font-weight:900;">Badge <span style="font-weight:100; color:#999;">Management</span></h1>
        
        <div style="background:#fff; padding:30px; border:1px solid #ddd; margin-top:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                <h2 style="margin:0;">Product Badges</h2>
                <a href="<?php echo admin_url('post-new.php?post_type=mc_badge'); ?>" class="button button-primary">+ Create New Badge</a>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Badge Name</th>
                        <th>Anchor Point</th>
                        <th>Priority</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($badges) : foreach ($badges as $badge) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($badge->post_title); ?></strong></td>
                            <td><?php echo strtoupper(str_replace('-',' ',get_post_meta($badge->ID,'_mc_badge_grid',true) ?: 'top-right')); ?></td>
                            <td><?php echo get_post_meta($badge->ID, '_mc_badge_priority', true) ?: '0'; ?></td>
                            <td><a href="<?php echo get_edit_post_link($badge->ID); ?>">Edit Designer</a></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="4" style="text-align:center; padding:30px;">No badges created yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}

// ==============================================================================
// 2. PRODUCT PAGE: MULTI-SELECT BADGES
// ==============================================================================
add_action( 'add_meta_boxes', function() {
    add_meta_box('mc_product_badge_selector', 'MealCrafter Badges', 'mc_render_product_badge_picker', 'product', 'side', 'high');
});

function mc_render_product_badge_picker( $post ) {
    $badges = get_posts(['post_type' => 'mc_badge', 'numberposts' => -1, 'post_status' => 'publish']);
    $selected = get_post_meta($post->ID, '_mc_product_badge_ids', true) ?: [];
    if (!is_array($selected)) { $selected = [$selected]; }

    echo '<div style="max-height:200px; overflow-y:auto; border:1px solid #ddd; padding:10px; background:#fff; border-radius:4px;">';
    if ($badges) {
        foreach($badges as $b) {
            $checked = in_array($b->ID, $selected) ? 'checked' : '';
            echo '<label style="display:block; margin-bottom:8px; cursor:pointer;"><input type="checkbox" name="mc_product_badge_ids[]" value="'.$b->ID.'" '.$checked.'> '.esc_html($b->post_title).'</label>';
        }
    } else {
        echo '<p style="color:#999; margin:0;">No badges found.</p>';
    }
    echo '</div><p class="description">Select multiple badges to display.</p>';
}

add_action( 'save_post_product', function($post_id) {
    if (isset($_POST['mc_product_badge_ids'])) {
        update_post_meta($post_id, '_mc_product_badge_ids', array_map('intval', $_POST['mc_product_badge_ids']));
    } else {
        delete_post_meta($post_id, '_mc_product_badge_ids');
    }
});

// ==============================================================================
// 3. BADGE DESIGNER (Arrow Grid & High-Contrast Canvas)
// ==============================================================================
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'mc_badge_visual', 'Badge Designer', 'mc_badge_visual_html', 'mc_badge', 'normal', 'high' );
    add_meta_box( 'mc_badge_rules', 'Targeting & Priority', 'mc_badge_rules_html', 'mc_badge', 'side', 'default' );
});

function mc_badge_visual_html( $post ) {
    $anchor  = get_post_meta($post->ID, '_mc_badge_grid', true) ?: 'top-right';
    $off_x   = get_post_meta($post->ID, '_mc_badge_off_x', true) ?: '0';
    $off_y   = get_post_meta($post->ID, '_mc_badge_off_y', true) ?: '0';
    $m_off_x = get_post_meta($post->ID, '_mc_badge_m_off_x', true) ?: '0';
    $m_off_y = get_post_meta($post->ID, '_mc_badge_m_off_y', true) ?: '0';
    $w       = get_post_meta($post->ID, '_mc_badge_width', true) ?: '60';
    $img     = get_post_meta($post->ID, '_mc_badge_image', true);
    
    $grids = [
        'top-left' => '&#8598;', 'top-center' => '&#8593;', 'top-right' => '&#8599;',
        'middle-left' => '&#8592;', 'middle-center' => '&#9679;', 'middle-right' => '&#8594;',
        'bottom-left' => '&#8601;', 'bottom-center' => '&#8595;', 'bottom-right' => '&#8600;'
    ];
    ?>
    <style>
        .mc-layout-split { display: flex; gap: 40px; align-items: flex-start; padding: 10px; }
        .mc-controls-col { flex: 1; }
        .mc-grid-matrix { display: grid; grid-template-columns: repeat(3, 45px); gap: 8px; }
        .mc-grid-matrix label { cursor: pointer; position: relative; }
        .mc-grid-matrix input { display: none; }
        .mc-grid-matrix span.mc-grid-icon { display: flex; align-items: center; justify-content: center; font-size: 20px; color: #646970; width: 45px; height: 45px; border: 2px solid #dcdcde; background: #fff; border-radius: 6px; transition: 0.2s; }
        .mc-grid-matrix label:hover span.mc-grid-icon { border-color: #2271b1; color: #2271b1; }
        .mc-grid-matrix input:checked + span.mc-grid-icon { background: #2271b1; border-color: #2271b1; color: #fff; }

        .mc-preview-col { width: 340px; background: #f6f7f7; padding: 25px; border: 1px solid #c3c4c7; border-radius: 8px; text-align: center; }
        .mc-canvas { position: relative; width: 260px; height: 260px; background: #fff; border: 2px dashed #a7aaad; margin: 0 auto; overflow: hidden; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .mc-canvas::before { content: "PRODUCT BOUNDARY"; color: #dcdcde; font-weight: 800; font-size: 16px; letter-spacing: 1px; text-align: center; line-height: 1.2; padding: 20px; }
        .mc-live-badge { position: absolute; z-index: 10; pointer-events: none; transition: all 0.2s ease; display: flex; align-items: center; justify-content: center; }
    </style>

    <div class="mc-layout-split">
        <div class="mc-controls-col">
            <table class="form-table">
                <tr>
                    <th>Sticker Image</th>
                    <td>
                        <input type="hidden" name="mc_badge_image" id="mc_badge_image_val" value="<?php echo esc_attr($img); ?>">
                        <div id="mc_badge_img_thumb" style="margin-bottom:12px;">
                            <?php if($img): ?><img src="<?php echo $img; ?>" style="max-width:80px; border:1px solid #dcdcde; padding:5px; background:#fff; border-radius:4px;"><?php endif; ?>
                        </div>
                        <button type="button" class="button button-secondary" id="mc_badge_upload_btn">Select PNG Sticker</button>
                    </td>
                </tr>
                <tr>
                    <th>Anchor Point</th>
                    <td>
                        <div class="mc-grid-matrix">
                            <?php foreach($grids as $val => $icon): ?>
                                <label title="<?php echo $val; ?>"><input type="radio" name="mc_badge_grid" value="<?php echo $val; ?>" <?php checked($anchor,$val); ?> class="mc-update-js"><span class="mc-grid-icon"><?php echo $icon; ?></span></label>
                            <?php endforeach; ?>
                        </div>
                    </td>
                </tr>
                <tr><th>Desktop Nudge</th><td>X: <input type="number" name="mc_badge_off_x" id="mc_badge_off_x" value="<?php echo $off_x; ?>" style="width:70px;" class="mc-update-js"> px &nbsp; Y: <input type="number" name="mc_badge_off_y" id="mc_badge_off_y" value="<?php echo $off_y; ?>" style="width:70px;" class="mc-update-js"> px</td></tr>
                <tr style="background:#f0f6fc; border-left: 4px solid #2271b1;">
                    <th style="padding-left: 15px;">Mobile Nudge</th>
                    <td>X: <input type="number" name="mc_badge_m_off_x" value="<?php echo $m_off_x; ?>" style="width:70px;"> px &nbsp; Y: <input type="number" name="mc_badge_m_off_y" value="<?php echo $m_off_y; ?>" style="width:70px;"> px</td>
                </tr>
                <tr><th>Width</th><td><input type="number" name="mc_badge_width" id="mc_badge_width" value="<?php echo $w; ?>" style="width:75px;" class="mc-update-js"> px</td></tr>
            </table>
        </div>

        <div class="mc-preview-col">
            <strong style="display:block; margin-bottom:15px; font-size:14px; color:#1d2327;">Live Visual Preview</strong>
            <div class="mc-canvas"><div id="mc-live-badge" class="mc-live-badge"></div></div>
            <p class="description" style="margin-top:20px;">The dashed box represents the edges of your product image.</p>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        function calculatePosition() {
            const anchor = $('input[name="mc_badge_grid"]:checked').val();
            const offX = parseInt($('#mc_badge_off_x').val()) || 0;
            const offY = parseInt($('#mc_badge_off_y').val()) || 0;
            const width = $('#mc_badge_width').val();
            const imgUrl = $('#mc_badge_image_val').val();
            const $badge = $('#mc-live-badge');
            
            if(imgUrl) {
                $badge.html('<img src="'+imgUrl+'" style="width:'+width+'px; height:auto; display:block;">');
                $('#mc_badge_img_thumb').html('<img src="'+imgUrl+'" style="max-width:80px; border:1px solid #ddd; padding:5px; background:#fff; border-radius:4px;">');
            } else {
                $badge.html('<div style="background:#d63638; color:#fff; padding:6px 12px; border-radius:4px; font-weight:bold; font-size:12px;">PREVIEW</div>');
            }

            $badge.css({top:'auto', bottom:'auto', left:'auto', right:'auto', transform:'none'});
            if(anchor === 'top-left') { $badge.css({top: offY+'px', left: offX+'px'}); }
            else if(anchor === 'top-center') { $badge.css({top: offY+'px', left: '50%', transform: 'translateX(calc(-50% + '+offX+'px))'}); }
            else if(anchor === 'top-right') { $badge.css({top: offY+'px', right: offX+'px'}); }
            else if(anchor === 'middle-left') { $badge.css({top: '50%', left: offX+'px', transform: 'translateY(calc(-50% + '+offY+'px))'}); }
            else if(anchor === 'middle-center') { $badge.css({top: '50%', left: '50%', transform: 'translate(calc(-50% + '+offX+'px), calc(-50% + '+offY+'px))'}); }
            else if(anchor === 'middle-right') { $badge.css({top: '50%', right: offX+'px', transform: 'translateY(calc(-50% + '+offY+'px))'}); }
            else if(anchor === 'bottom-left') { $badge.css({bottom: offY+'px', left: offX+'px'}); }
            else if(anchor === 'bottom-center') { $badge.css({bottom: offY+'px', left: '50%', transform: 'translateX(calc(-50% + '+offX+'px))'}); }
            else if(anchor === 'bottom-right') { $badge.css({bottom: offY+'px', right: offX+'px'}); }
        }

        $('.mc-update-js').on('change input', calculatePosition);
        
        $('#mc_badge_upload_btn').on('click', function(e){
            e.preventDefault();
            var frame = wp.media({ multiple: false }).on('select', function(){
                $('#mc_badge_image_val').val(frame.state().get('selection').first().toJSON().url);
                calculatePosition();
            }).open();
        });

        calculatePosition();
    });
    </script>
    <?php
}

// ==============================================================================
// 4. CUSTOM MODERN SEARCH OVERLAY
// ==============================================================================
function mc_badge_rules_html( $post ) {
    $selected_prods = get_post_meta($post->ID, '_mc_badge_prods', true) ?: [];
    $priority = get_post_meta($post->ID, '_mc_badge_priority', true) ?: '0';
    
    $init_data = [];
    foreach($selected_prods as $p_id) { $init_data[] = ['id' => $p_id, 'text' => get_the_title($p_id)]; }
    ?>
    <p><strong>Overlap Priority</strong></p>
    <input type="number" name="mc_badge_priority" value="<?php echo $priority; ?>" style="width:100%;">
    <hr>
    <p><strong>Apply to Specific Products</strong></p>
    
    <div class="mc-modern-search-wrap" id="mc_custom_search" data-initial='<?php echo esc_attr(json_encode($init_data)); ?>'>
        <div class="mc-pill-box" id="mc_pill_box"></div>
        <div style="position:relative;">
            <input type="text" id="mc_search_input" class="mc-search-input" placeholder="Search for a product..." autocomplete="off">
            <div id="mc_search_dropdown" class="mc-search-dropdown"></div>
        </div>
        <select name="mc_badge_prods[]" id="mc_hidden_save_select" multiple style="display:none;"></select>
    </div>

    <style>
        .mc-modern-search-wrap { border: 1px solid #8c8f94; border-radius: 4px; background: #fff; padding: 5px; position: relative; margin-top: 10px; }
        .mc-modern-search-wrap:focus-within { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; }
        .mc-pill-box { display: flex; flex-wrap: wrap; gap: 6px; }
        .mc-pill { background: #2271b1; color: #fff; padding: 4px 8px; border-radius: 4px; font-size: 13px; display: flex; align-items: center; gap: 6px; }
        .mc-pill-remove { cursor: pointer; background: rgba(0,0,0,0.2); border-radius: 50%; width: 16px; height: 16px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 10px; transition: 0.2s; }
        .mc-pill-remove:hover { background: rgba(0,0,0,0.5); }
        .mc-search-input { width: 100%; border: none !important; box-shadow: none !important; padding: 8px 4px !important; outline: none !important; background: transparent !important; }
        .mc-search-dropdown { position: absolute; top: 100%; left: -6px; width: calc(100% + 12px); background: #fff; border: 1px solid #2271b1; border-top: none; z-index: 999999; box-shadow: 0 10px 20px rgba(0,0,0,0.15); border-radius: 0 0 6px 6px; max-height: 250px; overflow-y: auto; display: none; margin-top: 6px; }
        .mc-drop-item { padding: 10px 15px; cursor: pointer; border-bottom: 1px solid #f0f0f1; font-size: 14px; color: #1d2327; }
        .mc-drop-item:last-child { border-bottom: none; }
        .mc-drop-item:hover { background: #2271b1; color: #fff; }
        .mc-drop-loading { padding: 10px 15px; font-size: 13px; color: #888; text-align: center; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        const $wrap = $('#mc_custom_search');
        const $input = $('#mc_search_input');
        const $dropdown = $('#mc_search_dropdown');
        const $pillBox = $('#mc_pill_box');
        const $hiddenSelect = $('#mc_hidden_save_select');
        let selectedItems = $wrap.data('initial') || [];
        let searchTimer;

        function renderPills() {
            $pillBox.empty();
            $hiddenSelect.empty();
            selectedItems.forEach((item, index) => {
                $pillBox.append(`<div class="mc-pill">${item.text} <span class="mc-pill-remove" data-index="${index}">✕</span></div>`);
                $hiddenSelect.append(`<option value="${item.id}" selected>${item.text}</option>`);
            });
        }

        function performSearch(query) {
            $dropdown.show().html('<div class="mc-drop-loading">Searching...</div>');
            $.ajax({
                url: ajaxurl, data: { action: 'mc_badge_search_products_hub', q: query },
                success: function(res) {
                    $dropdown.empty();
                    if(res.length === 0) { $dropdown.html('<div class="mc-drop-loading">No products found.</div>'); return; }
                    res.forEach(item => { $dropdown.append(`<div class="mc-drop-item" data-id="${item.id}" data-text="${item.text}">${item.text}</div>`); });
                }
            });
        }

        $input.on('input', function() {
            const val = $(this).val();
            clearTimeout(searchTimer);
            if(val.length < 2) { $dropdown.hide(); return; }
            searchTimer = setTimeout(() => performSearch(val), 300);
        });

        $dropdown.on('click', '.mc-drop-item', function() {
            const id = $(this).data('id'); const text = $(this).data('text');
            if(!selectedItems.find(i => i.id == id)) { selectedItems.push({id, text}); renderPills(); }
            $input.val(''); $dropdown.hide();
        });

        $pillBox.on('click', '.mc-pill-remove', function() {
            selectedItems.splice($(this).data('index'), 1); renderPills();
        });

        $(document).on('click', function(e) { if(!$(e.target).closest('#mc_custom_search').length) { $dropdown.hide(); } });
        renderPills();
    });
    </script>
    <?php
}

// ==============================================================================
// 5. AJAX HANDLER & SAVE ROUTINE
// ==============================================================================
add_action('wp_ajax_mc_badge_search_products_hub', function() {
    global $wpdb;
    $s = sanitize_text_field($_GET['q']);
    $ps = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM $wpdb->posts WHERE post_type='product' AND post_status='publish' AND post_title LIKE %s LIMIT 10", '%'.$wpdb->esc_like($s).'%'));
    $r = []; foreach($ps as $p) { $r[] = ['id'=>$p->ID, 'text'=>$p->post_title]; }
    wp_send_json($r);
});

add_action( 'save_post_mc_badge', function( $post_id ) {
    $fields = ['mc_badge_grid','mc_badge_off_x','mc_badge_off_y','mc_badge_m_off_x','mc_badge_m_off_y','mc_badge_width','mc_badge_image','mc_badge_priority'];
    foreach($fields as $f) { if(isset($_POST[$f])) update_post_meta($post_id, '_'.$f, sanitize_text_field($_POST[$f])); }
    update_post_meta($post_id, '_mc_badge_prods', isset($_POST['mc_badge_prods']) ? array_map('intval', $_POST['mc_badge_prods']) : []);
});