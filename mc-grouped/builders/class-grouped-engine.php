<?php
/**
 * MealCrafter: Grouped Product Frontend UI (Modular Dashboard Integration)
 * Fix: Forced WooCommerce Cart Fragment Refresh on Add & Portal Isolation
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('mc_grouped_product', 'mc_display_grouped_product_hub');

function mc_display_grouped_product_hub() {
    $hub_id = get_the_ID();
    
    // Get Local Settings
    $grid_columns = get_post_meta( $hub_id, '_mc_grid_columns', true ) ?: 'auto';
    $animation     = get_post_meta( $hub_id, '_mc_animation', true ) ?: 'popup';
    $popup_image   = get_post_meta( $hub_id, '_mc_popup_image', true ) ?: 'show';
    $fly_anim      = get_post_meta( $hub_id, '_mc_fly_animation', true ) ?: 'on';
    $slots         = get_post_meta( $hub_id, '_mc_grouped_slots', true ) ?: [];
    
    // FETCH SETTINGS FROM THE "GROUPED" TAB IN MASTER HUB
    $font_family  = get_option( 'mc_font_family', 'inherit' );
    $brand_color  = get_option( 'mc_brand_color', '#e74c3c' );
    $gp_title_sz  = get_option( 'mc_gp_title_size', '16' ) . 'px';
    $gp_price_sz  = get_option( 'mc_gp_price_size', '15' ) . 'px';
    $gp_padding   = get_option( 'mc_gp_card_pad', '20' ) . 'px';
    
    if (empty($slots)) return '<p style="text-align:center; padding:30px;">Please configure your menu sections in the product settings.</p>';

    $grid_css = 'repeat(auto-fill, minmax(220px, 1fr))';
    if ( is_numeric($grid_columns) ) {
        $grid_css = 'repeat(' . intval($grid_columns) . ', 1fr)';
    }

    ob_start();
    ?>
    <style>
        /* Apply Global Font */
        .mc-grouped-hub-wrapper { font-family: <?php echo esc_html($font_family); ?>; }

        .mc-slot-heading { font-size: 26px; font-weight: 600; color: #333; margin: 40px 0 20px 0; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }

        .mc-grouped-hub { display: grid; grid-template-columns: <?php echo $grid_css; ?>; gap: 20px; padding-bottom: 40px; }
        
        /* Apply Grouped-Specific Card Padding */
        .mc-hub-card { 
            background: #fff; border: 1px solid #eaeaea; border-radius: 12px; 
            padding: <?php echo esc_html($gp_padding); ?>; 
            text-align: center; cursor: pointer; transition: 0.3s; position: relative; 
            overflow: visible; 
        }
        .mc-hub-card:hover:not(.mc-out-of-stock) { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.06); border-color: #ddd; }
        
        /* Active State for JS Detection */
        .mc-hub-card.mc-active-sel { border-color: <?php echo esc_html($brand_color); ?>; box-shadow: 0 0 0 2px <?php echo esc_html($brand_color); ?>33; }

        /* Shrink-Wrap Container for Perfect Badge Alignment */
        .mc-image-badge-anchor {
            position: relative;
            display: inline-block; 
            margin: 0 auto 20px auto;
            max-width: 100%;
        }
        .mc-image-badge-anchor img { 
            height: 160px; width: auto; max-width: 100%; object-fit: contain; 
            display: block; margin: 0; 
        }
        
        /* Apply Grouped-Specific Font Sizes and Colors */
        .mc-hub-title { font-weight: 500; font-size: <?php echo esc_html($gp_title_sz); ?>; margin-bottom: 8px; color: #333; line-height: 1.3; }
        .mc-hub-price { font-weight: 700; color: <?php echo esc_html($brand_color); ?>; font-size: <?php echo esc_html($gp_price_sz); ?>; }

        .mc-out-of-stock { opacity: 0.5; cursor: not-allowed; filter: grayscale(100%); }
        .mc-oos-badge { position: absolute; top: -10px; right: -10px; background: #222; color: #fff; font-size: 11px; font-weight: bold; padding: 5px 15px; border-radius: 20px; text-transform: uppercase; letter-spacing: 1px; z-index: 60; }

        #mc-hub-overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:999990; }
        .mc-anim-panel { font-family: <?php echo esc_html($font_family); ?>; position: fixed; background: #fff; z-index: 999999; box-shadow: 0 0 40px rgba(0,0,0,0.3); transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1); padding: 40px; text-align: center; }

        <?php if ($animation === 'popup'): ?>
            .mc-anim-panel { top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9); opacity: 0; width: 90%; max-width: 400px; border-radius: 20px; visibility: hidden; }
            .mc-anim-panel.mc-active { transform: translate(-50%, -50%) scale(1); opacity: 1; visibility: visible; }
        <?php elseif ($animation === 'slide_right'): ?>
            .mc-anim-panel { top: 0; right: -100%; width: 100%; max-width: 450px; height: 100%; border-radius: 20px 0 0 20px; }
            .mc-anim-panel.mc-active { right: 0; }
        <?php elseif ($animation === 'slide_left'): ?>
            .mc-anim-panel { top: 0; left: -100%; width: 100%; max-width: 450px; height: 100%; border-radius: 0 20px 20px 0; }
            .mc-anim-panel.mc-active { left: 0; }
        <?php elseif ($animation === 'slide_bottom'): ?>
            .mc-anim-panel { bottom: -100%; left: 0; width: 100%; height: auto; min-height: 40vh; border-radius: 30px 30px 0 0; }
            .mc-anim-panel.mc-active { bottom: 0; }
        <?php endif; ?>

        .mc-modal-close { position:absolute; top:20px; right:25px; font-size: 30px; cursor:pointer; color: #aaa; line-height: 1; }
        #mc-popup-img { height: 160px; width: auto; max-width: 100%; margin: 0 auto 20px; border-radius: 12px; display: none; object-fit: contain; }
        
        .mc-qty-wrap { display:flex; align-items:center; justify-content:center; gap:20px; margin: 20px 0; }
        .mc-qty-btn { width:50px; height:50px; border-radius:50%; border:2px solid #f0f0f0; background:#fff; font-size:24px; cursor:pointer; font-weight:bold; color: #333; }
        #mc-qty-display { font-size: 28px; font-weight: 700; min-width: 30px; color: #333; }
        
        .mc-var-row { margin-bottom: 15px; text-align: left; }
        .mc-var-row label { display: block; font-weight: 600; margin-bottom: 5px; color: #444; font-size: 13px; text-transform: uppercase; }
        .mc-var-row select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 15px; }

        .mc-hub-add-btn { background: <?php echo esc_html($brand_color); ?>; color:#fff; border:none; width:100%; padding: 18px; border-radius: 40px; font-weight:700; text-transform:uppercase; cursor:pointer; font-size: 16px; margin-top: 10px; }
        .mc-hub-add-btn:disabled { background: #ccc; cursor: not-allowed; }
        
        #mc-hub-toast { display:none; position:fixed; bottom:40px; left:50%; transform:translateX(-50%); background:#222; color:#fff; padding:15px 35px; border-radius:40px; z-index:1000000; font-weight:bold; }
        .mc-flying-img { position: fixed; z-index: 9999999; transition: all 0.8s cubic-bezier(0.25, 0.8, 0.25, 1); border-radius: 50%; object-fit: cover; box-shadow: 0 10px 20px rgba(0,0,0,0.2); }

        @media (max-width: 768px) {
            .mc-grouped-hub { grid-template-columns: 1fr 1fr; gap: 15px; }
            .mc-hub-card { padding: 15px 10px; }
            .mc-image-badge-anchor img { height: 100px; } 
            .mc-anim-panel { padding: 30px 20px; }
        }
    </style>

    <div class="mc-grouped-hub-wrapper">
    <?php
    foreach ($slots as $slot) {
        $source_type = $slot['source_type'] ?? 'category';
        $sort_by     = $slot['sort_by'] ?? 'name_asc';
        
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => ['publish', 'private'],
        ];

        if ($source_type === 'category' && !empty($slot['category'])) {
            $args['tax_query'] = [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $slot['category']]];
        } elseif ($source_type === 'products' && !empty($slot['products'])) {
            $args['post__in'] = $slot['products'];
        } else {
            continue; 
        }

        switch ( $sort_by ) {
            case 'name_desc': $args['orderby'] = 'title'; $args['order'] = 'DESC'; break;
            case 'date_desc': $args['orderby'] = 'date'; $args['order'] = 'DESC'; break;
            case 'menu_order': $args['orderby'] = 'menu_order title'; $args['order'] = 'ASC'; break;
            case 'name_asc':
            default: $args['orderby'] = 'title'; $args['order'] = 'ASC'; break;
        }

        $items = get_posts($args);
        if (empty($items)) continue;

        if (!empty($slot['title'])) {
            echo '<h3 class="mc-slot-heading">' . esc_html($slot['title']) . '</h3>';
        }
        
        echo '<div class="mc-grouped-hub">';
        
        foreach ($items as $item) {
            $product = wc_get_product($item->ID);
            $is_variable = $product->is_type('variable');
            $is_in_stock = $product->is_in_stock(); 
            
            $img_url = get_the_post_thumbnail_url($item->ID, 'medium');
            if (!$img_url) $img_url = wc_placeholder_img_src('medium');
            
            $card_classes = 'mc-hub-card ' . ($is_in_stock ? 'mc-in-stock' : 'mc-out-of-stock');
            ?>
            <div class="<?php echo esc_attr($card_classes); ?>" 
                 data-id="<?php echo $item->ID; ?>" 
                 data-name="<?php echo esc_attr($product->get_name()); ?>"
                 data-img="<?php echo esc_url($img_url); ?>"
                 data-type="<?php echo $is_variable ? 'variable' : 'simple'; ?>">
                
                <div class="mc-image-badge-anchor">
                    <?php if(class_exists('MC_Badge_Engine')) echo MC_Badge_Engine::get_badge_by_id($item->ID); ?>
                    <?php if ( ! $is_in_stock ) : ?>
                        <div class="mc-oos-badge">Sold Out</div>
                    <?php endif; ?>
                    <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" class="mc-card-img-target">
                </div>

                <div class="mc-hub-title"><?php echo esc_html($product->get_name()); ?></div>
                <div class="mc-hub-price"><?php echo $product->get_price_html(); ?></div>

                <?php if ( $is_variable && $is_in_stock ) : 
                    $attributes = $product->get_variation_attributes();
                    $variations = $product->get_available_variations();
                ?>
                    <div class="mc-hidden-variations" style="display:none;">
                        <script type="application/json" class="mc-var-json"><?php echo wp_json_encode($variations); ?></script>
                        <?php foreach ( $attributes as $attr_name => $options ) : ?>
                            <div class="mc-var-row">
                                <label><?php echo wc_attribute_label($attr_name); ?></label>
                                <select class="mc-variation-select" data-attribute="attribute_<?php echo sanitize_title($attr_name); ?>">
                                    <option value="">Choose an option...</option>
                                    <?php foreach ( $options as $option ) : ?>
                                        <option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
        echo '</div>'; 
    } 
    ?>
    </div>
    
    <div id="mc-hub-overlay"></div>
    <div id="mc-anim-container" class="mc-anim-panel">
        <span class="mc-modal-close">&times;</span>
        
        <?php if ($popup_image === 'show') : ?>
            <img id="mc-popup-img" src="" alt="Item">
        <?php endif; ?>

        <h2 id="mc-modal-name" style="margin-bottom:5px; font-weight:600; font-size:22px; color:#333;"></h2>
        <p id="mc-modal-price" style="color:<?php echo esc_html($brand_color); ?>; font-weight:700; margin-bottom: 15px; font-size:18px;"></p>
        
        <div id="mc-custom-features-area"></div>

        <div class="mc-qty-wrap">
            <button class="mc-qty-btn" id="mc-qty-dec">-</button>
            <span id="mc-qty-display">1</span>
            <button class="mc-qty-btn" id="mc-qty-inc">+</button>
        </div>
        <button class="mc-hub-add-btn" id="mc-grouped-hub-confirm">Add to Order</button>
    </div>

    <div id="mc-hub-toast">Added to Order!</div>

    <script>
    jQuery(document).ready(function($) {
        let activeProduct = { id: null, type: 'simple', variation_id: null, variationsData: [], selectedAttributes: {} };
        let qty = 1;

        function closePanel() {
            $('#mc-anim-container').removeClass('mc-active');
            $('#mc-hub-overlay').fadeOut(300);
            setTimeout(() => { $('#mc-custom-features-area').empty(); }, 300);
            
            if ($('#mc-combo-build-modal').length) {
                setTimeout(() => {
                    $('#mc-combo-build-modal').hide();
                    $('#mc-combo-render-area').empty();
                }, 300);
            }
        }

        function triggerFlyAnimation() {
            if ('<?php echo $fly_anim; ?>' !== 'on') return;
            let $targetImg = $('#mc-popup-img').is(':visible') ? $('#mc-popup-img') : $('.mc-hub-card[data-id="'+activeProduct.id+'"] .mc-card-img-target');
            if (!$targetImg.length) return;

            let $clone = $targetImg.clone().addClass('mc-flying-img').appendTo('body');
            let startOffset = $targetImg.offset();
            
            let $cart = $('.cart-contents, .site-header-cart, .menu-item-cart, .elementor-widget-woocommerce-menu-cart').first();
            let endPos = { top: 20, left: $(window).width() - 50 };
            if ($cart.length) {
                endPos = { top: $cart.offset().top - $(window).scrollTop(), left: $cart.offset().left };
            }

            $clone.css({ top: startOffset.top - $(window).scrollTop(), left: startOffset.left, width: $targetImg.width(), height: $targetImg.height() });

            setTimeout(function() {
                $clone.css({ top: endPos.top, left: endPos.left, width: '30px', height: '30px', opacity: 0.2 });
            }, 10);

            setTimeout(function() { $clone.remove(); }, 800);
        }

        function checkVariations() {
            if(activeProduct.type !== 'variable') return;
            let allSelected = true;
            activeProduct.selectedAttributes = {};
            
            $('#mc-custom-features-area select').each(function() {
                let val = $(this).val();
                let attr = $(this).data('attribute');
                if(!val) allSelected = false;
                activeProduct.selectedAttributes[attr] = val;
            });

            if(allSelected) {
                let match = activeProduct.variationsData.find(v => {
                    return Object.keys(activeProduct.selectedAttributes).every(key => {
                        return v.attributes[key] === "" || v.attributes[key] === activeProduct.selectedAttributes[key];
                    });
                });
                
                if(match) {
                    activeProduct.variation_id = match.variation_id;
                    $('#mc-modal-price').html(match.price_html);
                    $('#mc-grouped-hub-confirm').prop('disabled', false).text('Add to Order');
                } else {
                    activeProduct.variation_id = null;
                    $('#mc-grouped-hub-confirm').prop('disabled', true).text('Unavailable');
                }
            } else {
                activeProduct.variation_id = null;
                $('#mc-grouped-hub-confirm').prop('disabled', true).text('Select Options');
            }
        }

        $(document).on('click', '.mc-in-stock', function() {
            // THE FIX: Mark this card as active for easier data parsing
            $('.mc-hub-card').removeClass('mc-active-sel');
            $(this).addClass('mc-active-sel');

            activeProduct.id = $(this).data('id');
            activeProduct.type = $(this).data('type');
            activeProduct.variation_id = null;
            activeProduct.selectedAttributes = {};
            
            $('#mc-modal-name').text($(this).data('name'));
            $('#mc-modal-price').html($(this).find('.mc-hub-price').html());
            
            if ($('#mc-popup-img').length) {
                $('#mc-popup-img').attr('src', $(this).data('img')).show();
            }

            qty = 1;
            $('#mc-qty-display').text(qty);
            $('#mc-custom-features-area').empty();
            $('#mc-grouped-hub-confirm').prop('disabled', false).text('Add to Order');

            if(activeProduct.type === 'variable') {
                $('#mc-grouped-hub-confirm').prop('disabled', true).text('Select Options');
                $('#mc-custom-features-area').html($(this).find('.mc-hidden-variations').html());
                activeProduct.variationsData = JSON.parse($(this).find('.mc-var-json').text());
                $('#mc-custom-features-area select').on('change', checkVariations);
            }
            
            $('#mc-hub-overlay').fadeIn(200);
            setTimeout(function() { $('#mc-anim-container').addClass('mc-active'); }, 10);
        });

        $('.mc-modal-close, #mc-hub-overlay').on('click', closePanel);
        $('#mc-qty-inc').on('click', function() { qty++; $('#mc-qty-display').text(qty); });
        $('#mc-qty-dec').on('click', function() { if(qty > 1) { qty--; $('#mc-qty-display').text(qty); } });

        // THE FIX: Listening to the newly renamed confirm button
        $('#mc-grouped-hub-confirm').on('click', function() {
            if($(this).prop('disabled')) return;
            const $btn = $(this);
            $btn.text('Adding...').prop('disabled', true);

            let ajaxData = { product_id: activeProduct.id, quantity: qty };

            if(activeProduct.type === 'variable') {
                ajaxData.variation_id = activeProduct.variation_id;
                Object.assign(ajaxData, activeProduct.selectedAttributes);
            }

            if (typeof mc_portal_nonce !== 'undefined') {
                ajaxData.action = 'mc_portal_add_advanced_item';
                ajaxData.order_id = $('#mc-combo-render-area').attr('data-active-order') || new URLSearchParams(window.location.search).get('order');
                ajaxData.security = mc_portal_nonce;

                $.post('<?php echo admin_url("admin-ajax.php"); ?>', ajaxData, function(res) {
                    $btn.text('Add to Order').prop('disabled', false);
                    if(res.success) {
                        triggerFlyAnimation();
                        closePanel(); 
                        $('#mc-hub-toast').text('Added to Order!').fadeIn().delay(2000).fadeOut();
                        
                        // Force a refresh of the order details window!
                        if (typeof loadOrderDetails === 'function') { loadOrderDetails(ajaxData.order_id); }
                    } else {
                        alert("Failed to add to order.");
                    }
                });
            } else {
                $.ajax({
                    type: 'POST',
                    url: '<?php echo esc_url( \WC_AJAX::get_endpoint( "add_to_cart" ) ); ?>',
                    data: ajaxData,
                    success: function(response) {
                        $btn.text('Add to Order').prop('disabled', false);
                        triggerFlyAnimation();
                        closePanel();
                        $('#mc-hub-toast').fadeIn().delay(2000).fadeOut();
                        
                        if (response && response.fragments) {
                            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $btn]);
                        }
                        $(document.body).trigger('wc_fragment_refresh');
                    },
                    error: function() {
                        $btn.text('Error').prop('disabled', false);
                    }
                });
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}