<?php
/**
 * MealCrafter: Premium Combo Builder Frontend Engine
 * Fix: Restored Fixed Bottom Bar & Added Custom Badge Injection
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_shortcode('mc_combo_product', 'mc_display_combo_builder');

function mc_display_combo_builder() {
    global $product;
    if (!$product) $product = wc_get_product(get_the_ID());
    if ( $product->get_type() !== 'mc_combo' ) return ''; 

    $combo_data = get_post_meta( $product->get_id(), '_mc_combo_meta', true );
    if ( empty( $combo_data ) ) return '<p style="text-align:center; padding:30px;">Combo steps not yet configured.</p>';

    $prefill_data = []; $prefill_qty  = 1; $edit_key     = '';
    if ( isset($_GET['edit_combo']) && !empty($_GET['edit_combo']) && WC()->cart ) {
        $cart_item = WC()->cart->get_cart_item( sanitize_text_field($_GET['edit_combo']) );
        if ( $cart_item && isset($cart_item['mc_combo_selections']) ) {
            $prefill_data = $cart_item['mc_combo_selections'];
            $prefill_qty  = $cart_item['quantity'];
            $edit_key     = sanitize_text_field($_GET['edit_combo']);
        }
    }

    $font_family  = get_option( 'mc_font_family', 'inherit' );
    $brand_color  = get_option( 'mc_brand_color', '#e74c3c' );
    $summary_box  = get_option( 'mc_cb_summary_box', 'on' );
    $summary_price= get_option( 'mc_cb_summary_price', 'on' );
    $summary_pad  = get_option( 'mc_cb_summary_pad', '40' );
    $summary_size = get_option( 'mc_cb_summary_size', '105' );
    $summary_sticky = get_option( 'mc_cb_summary_sticky', 'on' );
    $slot_pos     = get_option( 'mc_cb_slot_pos', 'top' );
    $math_logic   = get_option( 'mc_combo_math_logic', 'on' );
    $grid_cols    = get_option( 'mc_cb_grid_cols', '4' );
    $cart_text    = get_option( 'mc_cb_cart_text', 'ADD COMBO TO ORDER' );
    $redirect_cart= get_option( 'mc_cb_redirect_cart', 'off' );
    $cart_url     = wc_get_cart_url();
    $base_price   = (float)$product->get_price();
    $combo_title  = strtoupper($product->get_name());

    // LOYALTY PLUGIN SYNC: Get Cost & Custom Badge Settings
    $point_cost = false;
    $badge_html = '';
    if ( class_exists('MC_Points_Account') && method_exists('MC_Points_Account', 'get_product_point_cost') ) {
        $point_cost = MC_Points_Account::get_product_point_cost($product);
        if ($point_cost !== false && $point_cost > 0) {
            $cat_settings = get_option('mc_pts_catalog_settings', []);
            $bg = $cat_settings['badge_bg'] ?? '#fef8ee';
            $border = $cat_settings['badge_border'] ?? '#f6c064';
            $color = $cat_settings['badge_text_color'] ?? '#d35400';
            $icon = $cat_settings['badge_icon'] ?? '🎁';
            $format = $cat_settings['badge_format'] ?? '{icon} Redeem for {points} Pts';

            $icon_html = (strpos($icon, 'http') === 0) ? '<img src="'.esc_url($icon).'" style="width:16px; height:16px; vertical-align:middle; margin-right:6px; border-radius:4px;">' : '<span style="margin-right:6px;">'.esc_html($icon).'</span>';
            $final_text = str_replace(['{icon}', '{points}'], [$icon_html, number_format($point_cost)], $format);

            $badge_html = '<span style="font-size:13px; background:'.esc_attr($bg).'; border:1px solid '.esc_attr($border).'; color:'.esc_attr($color).'; padding:4px 12px; border-radius:20px; margin-left:15px; vertical-align:middle; display:inline-flex; align-items:center; font-weight:800;">' . wp_kses_post($final_text) . '</span>';
        }
    }

    $grid_css = 'repeat(auto-fill, minmax(220px, 1fr))';
    if ( is_numeric($grid_cols) ) $grid_css = 'repeat(' . intval($grid_cols) . ', 1fr)';

    ob_start();
    ?>
    <style>
        .mc-combo-app { font-family: <?php echo esc_attr($font_family); ?>; position: relative; padding-bottom: 120px; background: transparent; padding-top: 40px; }
        .mc-layout-wrapper { display: flex; gap: 40px; max-width: 1400px; margin: 0 auto; padding: 0 20px; }
        .mc-layout-wrapper.mc-pos-top { flex-direction: column; }
        .mc-layout-wrapper.mc-pos-bottom { flex-direction: column-reverse; }
        .mc-layout-wrapper.mc-pos-left { flex-direction: row; }
        .mc-layout-wrapper.mc-pos-right { flex-direction: row-reverse; }

        .mc-summary-sidebar { flex-shrink: 0; }
        .mc-steps-main { flex: 1; min-width: 0; }

        <?php if ($summary_sticky === 'on') : ?>
        .mc-pos-left .mc-summary-sidebar, .mc-pos-right .mc-summary-sidebar { position: sticky; top: 100px; height: max-content; z-index: 900; }
        .mc-pos-top .mc-summary-sidebar { position: sticky; top: 20px; height: max-content; z-index: 900; }
        <?php endif; ?>

        .mc-pos-left .mc-summary-slots-wrapper, .mc-pos-right .mc-summary-slots-wrapper { flex-direction: column; }
        .mc-pos-left .mc-top-summary-box, .mc-pos-right .mc-top-summary-box { width: auto; min-width: <?php echo intval($summary_size) + 60; ?>px; }

        .mc-top-summary-box { background: var(--mc-bg-card, #fff); margin: 0 auto 30px auto; padding: <?php echo intval($summary_pad); ?>px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: flex; flex-direction: column; align-items: center; gap: 20px; transition: all 0.3s ease-in-out; }
        .mc-pos-top .mc-top-summary-box, .mc-pos-bottom .mc-top-summary-box { max-width: 900px; width: 100%; }
        .mc-summary-slots-wrapper { display: flex; justify-content: center; gap: 15px; flex-wrap: wrap; }
        
        .mc-summary-slot { width: <?php echo intval($summary_size); ?>px; height: <?php echo intval($summary_size); ?>px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: calc(<?php echo intval($summary_size); ?>px * 0.4); font-weight: 900; background: var(--mc-bg-alt, #fff); transition: all 0.3s ease-in-out; }
        .mc-summary-slot.empty { border: 2px dashed; opacity: 0.4; }
        .mc-summary-slot.filled { border: 2px solid; padding: 4px; opacity: 1; }
        .mc-summary-slot img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
        
        .mc-top-price-pill { font-size: 16px; font-weight: 800; color: var(--mc-text-muted, #555); background: var(--mc-bg-alt, #f8f9fa); padding: 8px 25px; border-radius: 50px; border: 1px solid var(--mc-border-color, #eee); display: inline-block; text-align: center; transition: all 0.3s ease-in-out; }
        .mc-top-price-pill span { font-size: 18px; font-weight: 900; color: var(--mc-text-main, #333); transition: all 0.3s ease-in-out; }

        .mc-top-summary-box.mc-scrolled { padding: 15px 25px; flex-direction: row; justify-content: space-between; border-radius: 50px; }
        .mc-top-summary-box.mc-scrolled .mc-summary-slot { width: 45px; height: 45px; font-size: 18px; border-radius: 8px; }
        .mc-top-summary-box.mc-scrolled .mc-top-price-pill { font-size: 14px; padding: 6px 15px; }
        .mc-top-summary-box.mc-scrolled .mc-top-price-pill span { font-size: 15px; }

        .mc-step-container { margin-bottom: 50px; }
        .mc-step-header { display: flex; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px; }
        .mc-step-badge { color: #fff; padding: 8px 16px; border-radius: 6px; font-weight: 900; font-size: 16px; text-transform: uppercase; letter-spacing: 1px; background: var(--step-color); }
        .mc-step-title-text { font-size: 28px; font-weight: 900; color: var(--mc-text-main, #222); }
        .mc-req-asterisk { color: #e74c3c; font-size: 28px; font-weight: 900; margin-left: -5px; }
        .mc-change-sel { font-size: 16px; text-decoration: underline; color: #e74c3c; font-weight: 700; cursor: pointer; margin-left: 10px; }
        .mc-step-desc { color: var(--mc-text-muted, #888); font-size: 15px; margin-top: -15px; margin-bottom: 25px; }

        .mc-combo-grid { display: grid; grid-template-columns: <?php echo $grid_css; ?>; gap: 20px; }
        .mc-combo-card { background: var(--mc-bg-card, #fff); border: 2px solid transparent; border-radius: 12px; padding: 25px 15px; text-align: center; cursor: pointer; transition: 0.2s; position: relative !important; box-shadow: 0 4px 15px rgba(0,0,0,0.04); display: flex; flex-direction: column; align-items: center; }
        .mc-combo-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.08); }
        .mc-combo-card.has-qty { border-color: var(--step-color); }
        
        .mc-image-badge-anchor { position: relative; display: inline-block; margin: 0 auto 15px auto; max-width: 100%; }
        .mc-image-badge-anchor img { height: 140px; width: auto; max-width: 100%; object-fit: contain; display: block; margin: 0; transition: 0.2s; }
        .mc-combo-card.has-qty .mc-image-badge-anchor img { transform: scale(0.95); }
        
        .mc-card-text { margin-top: auto; }
        .mc-combo-name { font-weight: 900; font-size: 17px; color: var(--mc-text-main, #222); line-height: 1.2; }
        .mc-combo-price-tag { font-size: 15px; color: var(--mc-text-muted, #888); font-weight: 700; margin-top: 8px; }

        .mc-qty-pill { position: absolute; top: 15px; right: 15px; color: #fff; border-radius: 20px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 15px; padding: 5px 15px; opacity: 0; transform: scale(0.8); transition: 0.2s; z-index: 10; cursor: pointer; background: var(--step-color); }
        .mc-combo-card.has-qty .mc-qty-pill { opacity: 1; transform: scale(1); }
        .mc-pill-minus { margin-right: 8px; font-size: 18px; line-height: 1; display: inline-block; }
        .mc-step-container.mc-collapsed .mc-combo-card:not(.has-qty) { display: none; }

        .mc-bottom-sticky-bar { position: fixed; bottom: 0; left: 0; width: 100%; background: var(--mc-bg-card, #fff); padding: 20px 40px; box-shadow: 0 -5px 30px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; z-index: 9999; border-top: 1px solid var(--mc-border-color, #eee); box-sizing: border-box; }
        .mc-bb-title { font-weight: 900; font-size: 20px; color: var(--mc-text-main, #222); display: flex; align-items: center; gap: 15px; }
        .mc-bb-actions { display: flex; align-items: center; gap: 20px; }
        .mc-combo-qty-picker { display: flex; align-items: center; gap: 15px; background: var(--mc-bg-alt, #f5f5f5); padding: 8px 20px; border-radius: 50px; font-weight: 900; font-size: 18px; color: var(--mc-text-main, #333); }
        .mc-qty-btn { cursor: pointer; font-size: 22px; color: var(--mc-text-muted, #555); user-select: none; }
        
        .mc-submit-btn { background: #dcdcdc; color: #fff; border: none; padding: 18px 40px; border-radius: 50px; font-weight: 900; font-size: 16px; cursor: not-allowed; transition: 0.3s; text-transform: uppercase; }
        .mc-submit-btn.mc-ready { background: <?php echo esc_attr($brand_color); ?> !important; cursor: pointer; box-shadow: 0 5px 15px rgba(0,0,0,0.15); }
        .mc-submit-btn.mc-ready:hover { filter: brightness(1.1); transform: translateY(-2px); }
        .mc-error-toast { display:none; position:fixed; bottom:100px; left:50%; transform:translateX(-50%); background:#e74c3c; color:#fff; padding:15px 30px; border-radius:30px; z-index:10001; font-weight:900; box-shadow: 0 5px 15px rgba(231,76,60,0.3); text-align:center; }

        @media (max-width: 768px) {
            .mc-combo-app { padding-bottom: 90px; }
            .mc-layout-wrapper.mc-pos-top, .mc-layout-wrapper.mc-pos-left, .mc-layout-wrapper.mc-pos-right { flex-direction: column; }
            .mc-layout-wrapper.mc-pos-bottom { flex-direction: column-reverse; }
            .mc-layout-wrapper { padding: 0; gap: 0; }
            .mc-step-container { padding: 0; margin-bottom: 20px; }
            .mc-step-header { padding: 0 15px; margin-bottom: 15px; }

            <?php if ($summary_sticky === 'on') : ?>.mc-summary-sidebar { position: sticky !important; top: 0px; z-index: 900; }<?php else : ?>.mc-summary-sidebar { position: static !important; width: 100%; }<?php endif; ?>
            
            .mc-pos-left .mc-top-summary-box, .mc-pos-right .mc-top-summary-box { width: 100%; }
            .mc-top-summary-box { padding: 15px !important; border-radius: 0; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border-bottom: 2px solid var(--mc-border-color, #eee); margin-bottom: 20px;}
            .mc-top-summary-box.mc-scrolled { border-radius:0; flex-direction: column; }
            .mc-summary-slots-wrapper { flex-direction: row !important; }
            .mc-summary-slot { width: 65px !important; height: 65px !important; font-size: 25px !important; border-radius: 8px;}

            .mc-combo-grid { grid-template-columns: 1fr; gap: 0; }
            .mc-combo-card { flex-direction: row; align-items: center; justify-content: flex-start; padding: 15px; border-radius: 0; border: none; border-bottom: 1px solid var(--mc-border-color, #eee); box-shadow: none; text-align: left; }
            .mc-combo-card:hover { transform: none; box-shadow: none; }
            .mc-combo-card.has-qty { border-color: transparent; border-bottom: 1px solid var(--mc-border-color, #eee); background: var(--mc-bg-alt, #fafafa); }
            
            .mc-image-badge-anchor { margin: 0 15px 0 0; }
            .mc-image-badge-anchor img { height: 75px; width: 75px; margin: 0; object-fit: contain; }
            .mc-card-text { flex: 1; margin: 0; display: flex; flex-direction: column; justify-content: center; }
            .mc-qty-pill { position: static; margin-left: 10px; opacity: 1; transform: scale(1) !important; display: none; padding: 8px 15px; font-size: 16px; }
            .mc-combo-card.has-qty .mc-qty-pill { display: flex; }

            .mc-bb-title { display: none; }
            .mc-bottom-sticky-bar { padding: 12px 15px; flex-direction: row; }
            .mc-bb-actions { width: 100%; justify-content: space-between; gap: 10px; }
            .mc-combo-qty-picker { padding: 10px 15px; font-size: 16px; gap: 10px; }
            .mc-qty-btn { font-size: 20px; }
            .mc-submit-btn { padding: 12px 15px; flex: 1; font-size: 14px; display: flex; justify-content: center; }
        }
    </style>

    <div class="mc-combo-app" id="mc-combo-app" data-base-price="<?php echo $base_price; ?>" data-prefill="<?php echo esc_attr(json_encode($prefill_data)); ?>" data-prefill-qty="<?php echo esc_attr($prefill_qty); ?>" data-edit-key="<?php echo esc_attr($edit_key); ?>">
        
        <div class="mc-layout-wrapper mc-pos-<?php echo esc_attr($slot_pos); ?>">
            <?php if ($summary_box === 'on') : ?>
                <div class="mc-summary-sidebar">
                    <div class="mc-top-summary-box" id="mc-summary-box">
                        <div class="mc-summary-slots-wrapper">
                            <?php foreach ( $combo_data as $i => $step ) : 
                                $color = !empty($step['color']) ? $step['color'] : $brand_color;
                                for($j = 0; $j < $step['limit']; $j++) : ?>
                                    <div class="mc-summary-slot empty" data-step-id="<?php echo $i; ?>" style="border-color: <?php echo esc_attr($color); ?>; color: <?php echo esc_attr($color); ?>;">?</div>
                            <?php endfor; endforeach; ?>
                        </div>
                        <?php if ($summary_price === 'on') : ?>
                            <div class="mc-top-price-pill">Total: <span id="mc-top-price-val">$<?php echo number_format($base_price, 2); ?></span></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mc-steps-main">
                <?php foreach ( $combo_data as $index => $step ) : 
                    $step_color = !empty($step['color']) ? $step['color'] : $brand_color;
                ?>
                    <div class="mc-step-container" id="mc-step-<?php echo $index; ?>" data-step-index="<?php echo $index; ?>" data-limit="<?php echo $step['limit']; ?>" data-required="<?php echo $step['required']; ?>" style="--step-color: <?php echo esc_attr($step_color); ?>;">
                        
                        <div class="mc-step-header">
                            <span class="mc-step-badge">STEP <?php echo $index + 1; ?></span>
                            <span class="mc-step-title-text"><?php echo esc_html($step['name']); ?></span>
                            <?php if ($step['required']) echo '<span class="mc-req-asterisk">*</span>'; ?>
                            <span class="mc-change-sel">(Clear Section)</span>
                        </div>
                        
                        <?php if(!empty($step['description'])) : ?>
                            <div class="mc-step-desc" style="padding: 0 15px;"><?php echo esc_html($step['description']); ?></div>
                        <?php endif; ?>

                        <div class="mc-combo-grid">
                            <?php 
                            $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish'];
                            if ($step['type'] === 'category') { $args['tax_query'] = [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $step['items']]]; } 
                            else { $args['post__in'] = $step['items']; }

                            $sort_by = $step['sort_by'] ?? 'name_asc';
                            switch ( $sort_by ) {
                                case 'name_desc': $args['orderby'] = 'title'; $args['order'] = 'DESC'; break;
                                case 'date_desc': $args['orderby'] = 'date'; $args['order'] = 'DESC'; break;
                                case 'menu_order': if ($step['type'] === 'products') { $args['orderby'] = 'post__in'; } else { $args['orderby'] = 'menu_order title'; $args['order'] = 'ASC'; } break;
                                case 'rand': $args['orderby'] = 'rand'; break;
                                case 'name_asc': default: $args['orderby'] = 'title'; $args['order'] = 'ASC'; break;
                            }

                            $products = get_posts($args);
                            foreach ($products as $p_post) : 
                                $prod = wc_get_product($p_post->ID);
                                $p_price = (float)$prod->get_price();
                            ?>
                                <div class="mc-combo-card" data-id="<?php echo $p_post->ID; ?>" data-price="<?php echo $p_price; ?>" data-img="<?php echo get_the_post_thumbnail_url($p_post->ID, 'thumbnail'); ?>">
                                    <div class="mc-image-badge-anchor">
                                        <?php if(class_exists('MC_Badge_Engine')) echo MC_Badge_Engine::get_badge_by_id($p_post->ID); ?>
                                        <?php echo $prod->get_image('medium', ['class' => 'mc-card-img-target']); ?>
                                    </div>
                                    <div class="mc-card-text">
                                        <div class="mc-combo-name"><?php echo esc_html($prod->get_name()); ?></div>
                                        <?php if ($p_price > 0) : ?>
                                            <div class="mc-combo-price-tag">+$<?php echo number_format($p_price, 2); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mc-qty-pill"><span class="mc-pill-minus">−</span><span class="mc-qty-num">0</span></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="mc-bottom-sticky-bar">
            <div class="mc-bb-title">
                <?php echo esc_html($combo_title); ?>
                <?php echo $badge_html; ?>
            </div>
            
            <div class="mc-bb-actions">
                <div class="mc-combo-qty-picker"><span class="mc-qty-btn" id="mc-global-minus">−</span><span id="mc-global-qty">1</span><span class="mc-qty-btn" id="mc-global-plus">+</span></div>
                <button id="mc-submit-combo" class="mc-submit-btn"><?php echo esc_html($cart_text); ?> $<span id="mc-btn-price"><?php echo number_format($base_price, 2); ?></span></button>
            </div>
        </div>
        <div class="mc-error-toast" id="mc-error-msg">Please complete all required steps!</div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const basePrice = parseFloat($('#mc-combo-app').data('base-price')) || 0;
        const mathEnabled = '<?php echo $math_logic; ?>' === 'on';
        let globalQty = 1;
        window.mcFirstMissingStep = null; 

        <?php if ($summary_sticky === 'on') : ?>
        $(window).on('scroll', function() {
            if ($(this).scrollTop() > 80) { $('#mc-summary-box').addClass('mc-scrolled'); } 
            else { $('#mc-summary-box').removeClass('mc-scrolled'); }
        });
        $('#mc-combo-build-content').on('scroll', function() {
            if ($(this).scrollTop() > 80) { $('#mc-summary-box').addClass('mc-scrolled'); } 
            else { $('#mc-summary-box').removeClass('mc-scrolled'); }
        });
        <?php endif; ?>
        
        function updateUI() {
            let highestExtraPrice = 0; let allRequiredMet = true; window.mcFirstMissingStep = null; 
            $('.mc-summary-slot').addClass('empty').removeClass('filled').html('?');

            $('.mc-step-container').each(function() {
                let stepIndex = $(this).data('step-index'); let stepHumanNumber = stepIndex + 1;
                let limit = parseInt($(this).data('limit')); let isReq = parseInt($(this).data('required'));
                let stepQty = 0; let stepImages = [];

                $(this).find('.mc-combo-card').each(function() {
                    let qty = parseInt($(this).find('.mc-qty-num').text());
                    if (qty > 0) {
                        stepQty += qty;
                        let price = parseFloat($(this).data('price')) || 0; let img = $(this).data('img');
                        for (let i = 0; i < qty; i++) {
                            if (price > highestExtraPrice) { highestExtraPrice = price; }
                            stepImages.push(img);
                        }
                    }
                });

                let $slots = $('.mc-summary-slot[data-step-id="'+stepIndex+'"]');
                stepImages.forEach((img, idx) => { $slots.eq(idx).removeClass('empty').addClass('filled').html('<img src="'+img+'">'); });

                if (isReq === 1 && stepQty < limit) {
                    allRequiredMet = false;
                    if (window.mcFirstMissingStep === null) { window.mcFirstMissingStep = stepHumanNumber; }
                }

                if (stepQty === limit) { $(this).addClass('mc-collapsed'); } else { $(this).removeClass('mc-collapsed'); }
            });

            let unitPrice = basePrice;
            if (mathEnabled) { unitPrice = basePrice + highestExtraPrice; }
            let finalTotal = unitPrice * globalQty;
            let formattedTotal = finalTotal.toFixed(2);
            
            $('#mc-btn-price').text(formattedTotal);
            if ($('#mc-top-price-val').length) $('#mc-top-price-val').text('$' + formattedTotal);
            if (allRequiredMet) { $('#mc-submit-combo').addClass('mc-ready'); } else { $('#mc-submit-combo').removeClass('mc-ready'); }
        }

        const prefillData = $('#mc-combo-app').data('prefill') || [];
        const editKey = $('#mc-combo-app').data('edit-key') || '';
        const prefillQty = parseInt($('#mc-combo-app').data('prefill-qty')) || 1;

        if (prefillData.length > 0) {
            prefillData.forEach(item => {
                let id = typeof item === 'object' ? item.id : item; 
                let added = false;
                $('.mc-step-container:not(.mc-collapsed)').each(function() {
                    if (added) return;
                    let $card = $(this).find('.mc-combo-card[data-id="'+id+'"]');
                    if ($card.length > 0) {
                        let limit = parseInt($(this).data('limit'));
                        let currentTotal = 0;
                        $(this).find('.mc-qty-num').each(function() { currentTotal += parseInt($(this).text()); });
                        if (currentTotal < limit) {
                            let myQty = parseInt($card.find('.mc-qty-num').text());
                            $card.find('.mc-qty-num').text(myQty + 1);
                            $card.addClass('has-qty');
                            added = true;
                            if (currentTotal + 1 >= limit) $(this).addClass('mc-collapsed');
                        }
                    }
                });
            });
            if (prefillQty > 1) { globalQty = prefillQty; $('#mc-global-qty').text(globalQty); }
            if (editKey) { $('#mc-submit-combo').contents().filter(function(){ return this.nodeType == 3; }).first().replaceWith('UPDATE ORDER '); }
        }

        updateUI();

        $(document).off('click', '.mc-combo-card').on('click', '.mc-combo-card', function(e) {
            if ($(e.target).closest('.mc-qty-pill').length) return;
            let $step = $(this).closest('.mc-step-container');
            if ($step.hasClass('mc-collapsed')) return; 
            let limit = parseInt($step.data('limit')); let currentTotal = 0;
            $step.find('.mc-qty-num').each(function() { currentTotal += parseInt($(this).text()); });
            if (currentTotal < limit) {
                let myQty = parseInt($(this).find('.mc-qty-num').text());
                $(this).find('.mc-qty-num').text(myQty + 1); $(this).addClass('has-qty'); updateUI();
            }
        });

        $(document).off('click', '.mc-qty-pill').on('click', '.mc-qty-pill', function(e) {
            e.stopPropagation(); 
            let $card = $(this).closest('.mc-combo-card'); let $step = $card.closest('.mc-step-container');
            let myQty = parseInt($card.find('.mc-qty-num').text());
            if (myQty > 0) {
                myQty--; $card.find('.mc-qty-num').text(myQty);
                if (myQty === 0) $card.removeClass('has-qty');
                $step.removeClass('mc-collapsed'); updateUI();
            }
        });

        $(document).off('click', '.mc-change-sel').on('click', '.mc-change-sel', function(e) {
            e.preventDefault(); let $step = $(this).closest('.mc-step-container');
            $step.find('.mc-combo-card').removeClass('has-qty').find('.mc-qty-num').text('0');
            $step.removeClass('mc-collapsed'); updateUI();
        });

        $('#mc-global-plus').off('click').on('click', function() { globalQty++; $('#mc-global-qty').text(globalQty); updateUI(); });
        $('#mc-global-minus').off('click').on('click', function() { if(globalQty > 1) { globalQty--; $('#mc-global-qty').text(globalQty); updateUI(); } });

        $('#mc-submit-combo').off('click').on('click', function(e) {
            e.preventDefault();
            if (!$(this).hasClass('mc-ready')) {
                if (window.mcFirstMissingStep !== null) { $('#mc-error-msg').text('Please complete Step ' + window.mcFirstMissingStep + ' before submitting.'); } 
                else { $('#mc-error-msg').text('Please complete all required steps!'); }
                $('#mc-error-msg').fadeIn().delay(3000).fadeOut(); return;
            }

            const $btn = $(this);
            $btn.html('UPDATING...').css('pointer-events', 'none');

            let selectedData = [];
            $('.mc-combo-card.has-qty').each(function() {
                let id = $(this).data('id');
                let qty = parseInt($(this).find('.mc-qty-num').text());
                let slotName = $(this).closest('.mc-step-container').find('.mc-step-title-text').text();
                for(let i=0; i<qty; i++) { selectedData.push({ id: id, slot: slotName }); }
            });

            $.ajax({
                type: 'POST',
                url: '<?php echo esc_url( \WC_AJAX::get_endpoint( "add_to_cart" ) ); ?>',
                data: {
                    product_id: <?php echo $product->get_id(); ?>,
                    quantity: globalQty,
                    mc_combo_items: selectedData,
                    mc_edit_key: editKey
                },
                success: function(response) {
                    if (response.error && response.product_url) {
                        $('#mc-error-msg').text('Security Error. Please refresh.').fadeIn().delay(4000).fadeOut();
                        $btn.html('<?php echo esc_html($cart_text); ?>').css('pointer-events', 'auto'); return;
                    }
                    $btn.html(editKey ? 'ORDER UPDATED!' : 'ADDED TO ORDER!');
                    $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash]);
                    setTimeout(() => { 
                        <?php if ($redirect_cart === 'on') : ?> window.location.href = '<?php echo esc_url($cart_url); ?>'; <?php else : ?> location.reload(); <?php endif; ?>
                    }, 800);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean(); 
}