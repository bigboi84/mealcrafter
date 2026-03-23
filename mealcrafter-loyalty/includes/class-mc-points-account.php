<?php
/**
 * MealCrafter: Loyalty My Account Dashboard & Reward Catalog
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Account {

    public function __construct() {
        add_shortcode( 'mc_reward_catalog', [$this, 'render_catalog'] );
        add_shortcode( 'mc_rewards_dashboard', [$this, 'render_catalog'] ); 
        add_shortcode( 'mc_loyalty_debug', [$this, 'render_debug'] ); 

        add_action( 'init', [$this, 'add_my_account_endpoint'] );
        add_filter( 'woocommerce_account_menu_items', [$this, 'add_my_account_menu_item'] );
        add_action( 'woocommerce_account_mc-rewards_endpoint', [$this, 'my_account_content'] );
        add_action( 'woocommerce_single_product_summary', [$this, 'display_points_on_product_page'], 15 );
    }

    public function add_my_account_endpoint() { add_rewrite_endpoint( 'mc-rewards', EP_ROOT | EP_PAGES ); }

    public function add_my_account_menu_item( $items ) {
        $new_items = [];
        foreach ( $items as $key => $value ) {
            $new_items[$key] = $value;
            if ( $key === 'orders' ) $new_items['mc-rewards'] = 'Points & Rewards';
        }
        return $new_items;
    }

    public function my_account_content() { echo $this->render_catalog(); }

    public static function get_product_point_cost( $product ) {
        if ( ! $product ) return false;
        
        $product_id = $product->get_id();
        if (get_post_meta($product_id, '_mc_points_exempt_redeem', true) === 'yes') return false;

        $cat_ids = $product->get_category_ids();
        $tag_ids = $product->get_tag_ids();
        
        $ind_cost = get_post_meta($product_id, '_mc_points_redeem_price', true);
        $final_cost = (is_numeric($ind_cost) && $ind_cost > 0) ? (int)$ind_cost : false;

        $bulk_rules = get_option('mc_pts_bulk_costs', []);
        if (is_array($bulk_rules)) {
            $rules = array_filter($bulk_rules, function($r) { return is_array($r) && !empty($r['id']) && $r['id'] !== '__empty__'; });
            usort($rules, function($a, $b) { return ((int)($a['priority'] ?? 10)) <=> ((int)($b['priority'] ?? 10)); });
            
            foreach ($rules as $rule) {
                if (($rule['active'] ?? 'yes') !== 'yes') continue;
                $match = false;
                $type = $rule['target_type'] ?? 'categories';
                
                if ($type === 'categories' && !empty($rule['target_categories'])) {
                    foreach($cat_ids as $cid) { if(in_array($cid, $rule['target_categories'])) $match = true; }
                } elseif ($type === 'tags' && !empty($rule['target_tags'])) {
                    foreach($tag_ids as $tid) { if(in_array($tid, $rule['target_tags'])) $match = true; }
                } elseif ($type === 'specific_products' && !empty($rule['target_products_list'])) {
                    if (in_array($product_id, $rule['target_products_list'])) $match = true;
                }
                
                if ($match) {
                    $rule_cost = (int)($rule['point_cost'] ?? 0);
                    $force = ($rule['force_override'] ?? 'no') === 'yes';
                    if ($rule_cost > 0 && ($force || $final_cost === false)) {
                        $final_cost = $rule_cost;
                        break;
                    }
                }
            }
        }
        return $final_cost;
    }

    public function render_catalog( $atts = [] ) {
        $settings = get_option('mc_pts_catalog_settings', []);
        if (($settings['enabled'] ?? 'yes') !== 'yes') return '';

        // Dashboard Settings
        $default_tab = $settings['default_tab'] ?? 'catalog';
        $dash_title_size = $settings['dash_title_size'] ?? '28';
        $dash_val_size = $settings['dash_val_size'] ?? '36';
        $dash_card_bg = $settings['dash_card_bg'] ?? '#f9f9f9';

        $title = $settings['title'] ?? 'Unlock Secret Rewards';
        $description = $settings['description'] ?? '';
        $terms_url = $settings['terms_url'] ?? '';

        $layout = $settings['layout'] ?? 'grid';
        $hide_image = $settings['hide_image'] ?? 'no';
        $img_height = $settings['img_height'] ?? '180';
        $hover_card = $settings['hover_card'] ?? 'yes';
        
        $btn_ready = $settings['btn_ready'] ?? 'Redeem for {points} Pts';
        $btn_short = $settings['btn_short'] ?? 'Need {points} more Pts';
        $primary_color = $settings['primary_color'] ?? '#e74c3c';
        $btn_hover_color = $settings['btn_hover_color'] ?? '#c0392b';
        $included_cats = $settings['included_categories'] ?? [];
        $excluded_prods = $settings['excluded_products'] ?? [];

        $prog_style = $settings['prog_style'] ?? 'linear';
        $prog_overlay = $settings['prog_overlay'] ?? 'no';
        $prog_color_active = $settings['prog_color_active'] ?? '#f39c12';
        $prog_color_ready = $settings['prog_color_ready'] ?? '#2ecc71';
        $prog_bg_color = $settings['prog_bg_color'] ?? '#f0f0f0';

        $user_id = get_current_user_id();
        
        // STRICT DATABASE ENGINE: Match backend perfectly
        $pts_n = (int)get_user_meta($user_id, '_mc_user_points', true);
        $pts_o = (int)get_user_meta($user_id, 'mc_points', true);
        $balance = max($pts_n, $pts_o);
        
        $lifetime = (int)get_user_meta($user_id, '_mc_lifetime_points', true);
        if ($lifetime < $balance) $lifetime = $balance; 
        
        $history = get_user_meta($user_id, '_mc_points_history', true);
        if (!is_array($history)) $history = [];

        // Tab Logic 
        $is_hist_active = ($default_tab === 'history' && is_user_logged_in());
        $is_cat_active = ($default_tab === 'catalog' || !is_user_logged_in());

        $fetch_args = ['status' => 'publish', 'limit' => -1];
        if (!empty($excluded_prods) && is_array($excluded_prods)) { $fetch_args['exclude'] = $excluded_prods; }
        $all_products = wc_get_products($fetch_args);
        
        $catalog_items = [];
        foreach ($all_products as $product) {
            if (!empty($included_cats) && is_array($included_cats)) {
                $intersect = array_intersect($product->get_category_ids(), $included_cats);
                if (empty($intersect)) continue;
            }
            $cost = self::get_product_point_cost($product);
            if ($cost !== false && $cost > 0) {
                $catalog_items[] = ['product' => $product, 'cost' => $cost];
            }
        }
        usort($catalog_items, function($a, $b) { return $a['cost'] <=> $b['cost']; });

        ob_start();
        ?>
        <style>
            .mc-dash-wrap { font-family:inherit; max-width:1200px; margin:0 auto; padding:20px 0; }
            .mc-dash-header { margin-bottom:30px; }
            .mc-dash-header h2 { margin:0 0 15px 0; font-weight:800; font-size:<?php echo esc_attr($dash_title_size); ?>px; color:#222; }
            
            /* Top Cards */
            .mc-top-cards { display:flex; gap:15px; margin-bottom:30px; flex-wrap:wrap; }
            .mc-tcard { flex:1; min-width:200px; background:<?php echo esc_attr($dash_card_bg); ?>; padding:25px; border-radius:8px; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.03); border:1px solid rgba(0,0,0,0.05); }
            .mc-tcard-title { font-size:12px; color:#888; text-transform:uppercase; font-weight:700; letter-spacing:1px; margin-bottom:10px; display:block; }
            .mc-tcard-val { font-size:<?php echo esc_attr($dash_val_size); ?>px; font-weight:900; color:#111; line-height:1; }
            .mc-tcard-sub { font-size:11px; color:#999; margin-top:8px; display:block; }
            
            /* Tabs */
            .mc-tabs-nav { display:flex; gap:30px; border-bottom:2px solid #eee; margin-bottom:30px; }
            .mc-tab-btn { background:transparent; border:none; padding:10px 0; font-size:15px; font-weight:700; color:#888; cursor:pointer; border-bottom:3px solid transparent; margin-bottom:-2px; transition:0.2s; }
            .mc-tab-btn.active { color:#222; border-color:<?php echo esc_attr($primary_color); ?>; }
            .mc-tab-btn:hover { color:<?php echo esc_attr($primary_color); ?>; }
            .mc-tab-content { display:none; animation:fadeIn 0.3s ease; }
            .mc-tab-content.active { display:block; }
            
            /* History Table */
            .mc-f-hist-table { width:100%; border-collapse:collapse; font-size:14px; }
            .mc-f-hist-table th { padding:12px 10px; border-bottom:2px solid #eee; text-align:left; color:#111; font-weight:800; }
            .mc-f-hist-table td { padding:15px 10px; border-bottom:1px solid #eee; color:#555; }
            .mc-f-pos { color:#2ecc71; font-weight:800; font-size:16px; }
            .mc-f-neg { color:#e74c3c; font-weight:800; font-size:16px; }
            
            /* Catalog Overrides */
            .mc-catalog-item { transition: transform 0.3s ease, box-shadow 0.3s ease; }
            <?php if ($hover_card === 'yes'): ?>.mc-catalog-item:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important; }<?php endif; ?>
            .mc-reward-btn { transition: all 0.2s ease; }
            .mc-reward-btn.mc-ready:hover { background-color: <?php echo esc_attr($btn_hover_color); ?> !important; transform: translateY(-2px); box-shadow: 0 6px 15px <?php echo esc_attr($btn_hover_color); ?>50 !important; }
            @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
        </style>

        <div class="mc-dash-wrap">
            
            <?php if ( is_user_logged_in() ): ?>
                <div class="mc-dash-header"><h2>My Points</h2></div>
                
                <div class="mc-top-cards">
                    <div class="mc-tcard">
                        <span class="mc-tcard-title">Points To Redeem</span>
                        <div class="mc-tcard-val" style="color:<?php echo esc_attr($primary_color); ?>;"><?php echo number_format($balance); ?></div>
                        <span class="mc-tcard-sub">Total Collected: <?php echo number_format($lifetime); ?></span>
                    </div>
                    <div class="mc-tcard">
                        <span class="mc-tcard-title">My Level</span>
                        <div style="width:40px; height:40px; background:#222; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:10px; font-weight:bold; margin:0 auto 10px auto;">PRO</div>
                        <div class="mc-tcard-val" style="font-size:calc(<?php echo esc_attr($dash_val_size); ?>px * 0.65);">Pro Member</div>
                    </div>
                    <div class="mc-tcard">
                        <span class="mc-tcard-title">My Rank</span>
                        <div class="mc-tcard-val" style="font-size:calc(<?php echo esc_attr($dash_val_size); ?>px * 0.8);">#1</div>
                    </div>
                </div>

                <div class="mc-tabs-nav">
                    <button class="mc-tab-btn <?php echo $is_hist_active ? 'active' : ''; ?>" onclick="mcSwitchTab('history')">Points History</button>
                    <button class="mc-tab-btn <?php echo $is_cat_active ? 'active' : ''; ?>" onclick="mcSwitchTab('catalog')">Reward Catalog</button>
                </div>
                
                <div id="mc-tab-history" class="mc-tab-content <?php echo $is_hist_active ? 'active' : ''; ?>">
                    <?php if (empty($history)): ?>
                        <div style="padding:40px; text-align:center; background:#f9f9f9; border-radius:8px; color:#888;">You don't have any point history yet.</div>
                    <?php else: ?>
                        <table class="mc-f-hist-table">
                            <tr><th>Date</th><th>Reason</th><th>Order No.</th><th style="text-align:right;">Points</th></tr>
                            <?php foreach($history as $log): 
                                $diff = (int)$log['diff'];
                                $class = $diff > 0 ? 'mc-f-pos' : ($diff < 0 ? 'mc-f-neg' : '');
                                $sign = $diff > 0 ? '+' : '';
                            ?>
                            <tr>
                                <td><?php echo date('F j, Y', $log['date']); ?></td>
                                <td><?php echo esc_html($log['reason']); ?></td>
                                <td><?php echo esc_html($log['order']); ?></td>
                                <td style="text-align:right;">
                                    <div class="<?php echo $class; ?>"><?php echo $sign . $diff; ?></div>
                                    <div style="font-size:11px; color:#999;"><?php echo number_format((int)$log['balance']); ?> Points</div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div style="text-align:center; margin-bottom:40px; background:#fcfcfc; padding:25px; border-radius:12px; border:1px dashed #ccc;">
                    <p style="margin:0; font-weight:600; color:#555;">Please log in to your account to view your dashboard and unlock items.</p>
                </div>
            <?php endif; ?>

            <div id="mc-tab-catalog" class="mc-tab-content <?php echo $is_cat_active ? 'active' : ''; ?>">
                <div style="text-align:center; margin-bottom:30px;">
                    <h3 style="margin:0 0 10px 0; font-weight:800; font-size:<?php echo esc_attr($dash_title_size); ?>px; color:#111;"><?php echo esc_html($title); ?></h3>
                    <?php if (!empty($description)): ?><p style="margin:0 auto 10px auto; color:#555; max-width:600px; font-size:15px; line-height:1.5;"><?php echo wp_kses_post($description); ?></p><?php endif; ?>
                    <?php if (!empty($terms_url)): ?><a href="<?php echo esc_url($terms_url); ?>" target="_blank" style="font-size:13px; font-weight:600; color:#888; text-decoration:underline;">Read Loyalty Terms & Conditions</a><?php endif; ?>
                </div>
                
                <?php if(empty($catalog_items)): ?>
                    <div style="text-align:center; padding:40px; background:#f9f9f9; border-radius:8px; color:#777;">No rewards are currently active in the catalog.</div>
                <?php else: ?>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(<?php echo $layout === 'list' ? '100%' : '260px'; ?>, 1fr)); gap:25px;">
                        <?php 
                        foreach($catalog_items as $item): 
                            $p = $item['product']; $cost = $item['cost'];
                            $percent = $balance > 0 ? min(100, ($balance / $cost) * 100) : 0;
                            $points_away = max(0, $cost - $balance);
                            $can_afford = $balance >= $cost;
                            $active_bar_color = $can_afford ? $prog_color_ready : $prog_color_active;
                            $btn_text = $can_afford ? str_replace('{points}', number_format($cost), $btn_ready) : str_replace('{points}', number_format($points_away), $btn_short);
                            
                            $media_html = '';
                            $is_overlay_active = ($hide_image === 'no' && $prog_style === 'circular' && $prog_overlay === 'yes' && is_user_logged_in());

                            if ($hide_image === 'no') {
                                $img_tag = $p->get_image('woocommerce_thumbnail', ['style' => "max-height:".esc_attr($img_height)."px; width:100%; object-fit:contain; border-radius:8px; display:block; margin:0 auto;"]);
                                $overlay_html = '';
                                if ($is_overlay_active) {
                                    $overlay_html .= '<div style="position:absolute; top:8px; right:8px; background:rgba(255,255,255,0.95); padding:6px; border-radius:30px; box-shadow:0 4px 12px rgba(0,0,0,0.15); display:flex; gap:8px; align-items:center;"><div style="width:36px; height:36px; border-radius:50%; background:conic-gradient('.esc_attr($active_bar_color).' '.$percent.'%, '.esc_attr($prog_bg_color).' 0); display:flex; align-items:center; justify-content:center; flex-shrink:0;"><div style="width:26px; height:26px; background:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; line-height:1; font-size:12px;">'.($can_afford ? '🔓' : '🔒').'</div></div><div style="padding-right:6px; line-height:1.1; text-align:left;"><div style="font-size:12px; font-weight:800; color:#222;">'.number_format($balance).'</div><div style="font-size:9px; font-weight:700; color:#888;">/ '.number_format($cost).'</div></div></div>';
                                }
                                $media_html = '<div style="position:relative; width:100%; margin-bottom:15px; text-align:center;">' . $img_tag . $overlay_html . '</div>';
                            }
                        ?>
                            <div class="mc-catalog-item" style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.04); border:1px solid #eee; display:flex; <?php echo $layout === 'list' ? 'flex-direction:row; align-items:center; gap:20px;' : 'flex-direction:column;'; ?>">
                                <?php if ($hide_image === 'no') echo ($layout === 'list' ? '<div style="width:120px; flex-shrink:0;">'.$media_html.'</div>' : $media_html); ?>
                                <div style="flex-grow:1; display:flex; flex-direction:column; justify-content:space-between; height:100%;">
                                    <div>
                                        <div style="font-weight:800; font-size:18px; margin-bottom:5px; line-height:1.2; color:#111;"><?php echo esc_html($p->get_name()); ?></div>
                                        <div style="font-size:14px; font-weight:700; color:#666; margin-bottom:15px;">Cost: <?php echo number_format($cost); ?> PTS</div>
                                    </div>
                                    <div style="margin-top:auto;">
                                        <?php if ( is_user_logged_in() ): ?>
                                            <?php if ($prog_style === 'circular' && !$is_overlay_active): ?>
                                                <div style="display:flex; align-items:center; gap:12px; margin-bottom:15px; padding:10px; background:#fcfcfc; border-radius:8px; border:1px solid #eee;"><div style="width:46px; height:46px; border-radius:50%; background:conic-gradient(<?php echo esc_attr($active_bar_color); ?> <?php echo $percent; ?>%, <?php echo esc_attr($prog_bg_color); ?> 0); display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow:0 2px 5px rgba(0,0,0,0.05);"><div style="width:36px; height:36px; background:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; line-height:1;"><?php echo $can_afford ? '<span style="font-size:16px;">🔓</span>' : '<span style="font-size:13px; color:#666; font-weight:800;">🔒</span>'; ?></div></div><div style="flex-grow:1;"><div style="font-size:11px; color:#888; font-weight:700; text-transform:uppercase; margin-bottom:2px;">Progress</div><div style="font-size:14px; font-weight:800; color:#222;"><?php echo number_format($balance); ?> / <?php echo number_format($cost); ?> <span style="font-size:11px; color:#999;">PTS</span></div></div></div>
                                            <?php elseif ($prog_style === 'linear'): ?>
                                                <div style="display:flex; justify-content:space-between; font-size:11px; color:#888; font-weight:700; margin-bottom:6px; text-transform:uppercase;"><span><?php echo number_format($balance); ?> Pts</span><span><?php echo number_format($cost); ?> Pts Goal</span></div><div style="background:<?php echo esc_attr($prog_bg_color); ?>; height:12px; border-radius:10px; overflow:hidden; position:relative; margin-bottom:15px; box-shadow:inset 0 1px 3px rgba(0,0,0,0.05);"><div style="background:<?php echo esc_attr($active_bar_color); ?>; width:<?php echo $percent; ?>%; height:100%; transition:width 0.8s ease-out; position:relative;"><?php if(!$can_afford && $percent > 0): ?><div style="position:absolute; right:0; top:0; bottom:0; width:4px; background:rgba(255,255,255,0.7); box-shadow:-2px 0 4px rgba(0,0,0,0.15);"></div><?php endif; ?></div></div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url($p->get_permalink()); ?>" class="mc-reward-btn <?php echo $can_afford ? 'mc-ready' : ''; ?>" style="display:block; text-align:center; padding:10px 15px; border-radius:6px; text-decoration:none; font-weight:700; font-size:13px; text-transform:uppercase; <?php echo $can_afford ? 'background:'.esc_attr($primary_color).'; color:#fff;' : 'background:#f5f5f5; color:#888; border:1px solid #ddd; pointer-events:none;'; ?>"><?php echo esc_html($btn_text); ?></a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <script>
                function mcSwitchTab(tab) {
                    jQuery('.mc-tab-content').removeClass('active');
                    jQuery('.mc-tab-btn').removeClass('active');
                    jQuery('#mc-tab-' + tab).addClass('active');
                    jQuery(event.target).addClass('active');
                }
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

    public function display_points_on_product_page() {
        global $product;
        $point_cost = self::get_product_point_cost($product);
        if ( $point_cost !== false && $point_cost > 0 ) {
            $settings = get_option('mc_pts_catalog_settings', []);
            $bg = $settings['badge_bg'] ?? '#fef8ee';
            $border = $settings['badge_border'] ?? '#f6c064';
            $color = $settings['badge_text_color'] ?? '#d35400';
            $format = $settings['badge_format'] ?? '{icon} Redeem for {points} Pts';
            
            $icon_type = $settings['badge_icon_type'] ?? 'custom';
            $font_icon = $settings['badge_font_icon'] ?? 'dashicons-awards';
            $icon_color = $settings['badge_icon_color'] ?? '#d35400';
            $custom_icon = $settings['badge_icon'] ?? '🎁';
            
            $icon_html = '';
            if ($icon_type === 'font_icon') {
                wp_enqueue_style('dashicons');
                $icon_html = '<span class="dashicons '.esc_attr($font_icon).'" style="color:'.esc_attr($icon_color).'; font-size:18px; width:18px; height:18px; margin-right:6px; display:inline-flex; align-items:center; justify-content:center;"></span>';
            } else {
                if (strpos($custom_icon, 'http') === 0) {
                    $icon_html = '<img src="'.esc_url($custom_icon).'" style="width:18px; height:18px; vertical-align:middle; margin-right:6px; border-radius:4px;">';
                } else {
                    $icon_html = '<span style="margin-right:6px;">'.esc_html($custom_icon).'</span>';
                }
            }

            $final_text = str_replace(['{icon}', '{points}'], [$icon_html, number_format($point_cost)], $format);

            echo '<div style="margin-top:10px; margin-bottom:20px; padding:8px 14px; background:'.esc_attr($bg).'; border:1px solid '.esc_attr($border).'; border-radius:6px; display:inline-flex; align-items:center;">';
            echo '<span style="font-weight:800; color:'.esc_attr($color).'; display:flex; align-items:center; font-size:14px;">' . wp_kses_post($final_text) . '</span>';
            echo '</div>';
        }
    }
}
new MC_Points_Account();