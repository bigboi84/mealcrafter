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

        $title = $settings['title'] ?? 'Unlock Secret Rewards';
        $layout = $settings['layout'] ?? 'grid';
        $btn_ready = $settings['btn_ready'] ?? 'Redeem for {points} Pts';
        $btn_short = $settings['btn_short'] ?? 'Need {points} more Pts';
        $primary_color = $settings['primary_color'] ?? '#e74c3c';
        $included_cats = $settings['included_categories'] ?? [];

        $user_id = get_current_user_id();
        $balance = mc_get_user_points($user_id);

        // FIX: Switch to wc_get_products to guarantee custom types are fetched
        $all_products = wc_get_products([
            'status' => 'publish',
            'limit'  => -1,
        ]);
        
        $catalog_items = [];

        foreach ($all_products as $product) {
            // Manual category intersection to avoid missing WP tax bounds
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
        <div class="mc-reward-catalog-wrapper" style="font-family:inherit; max-width:1200px; margin:0 auto; padding:20px 0;">
            
            <?php if ( is_user_logged_in() ): ?>
            <div class="mc-wallet-card" style="text-align:center; margin-bottom:40px; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.06); border:1px solid #f0f0f0;">
                <span style="font-size:13px; color:#888; text-transform:uppercase; font-weight:800; letter-spacing:1px;">Your Wallet</span>
                <div style="font-size:52px; font-weight:900; color:<?php echo esc_attr($primary_color); ?>; line-height:1; margin-top:5px;">
                    <?php echo number_format($balance); ?> <span style="font-size:16px; color:#666; vertical-align:middle;">PTS</span>
                </div>
            </div>
            <?php else: ?>
            <div class="mc-wallet-card" style="text-align:center; margin-bottom:40px; background:#fcfcfc; padding:25px; border-radius:12px; border:1px dashed #ccc;">
                <p style="margin:0; font-weight:600; color:#555;">Please log in to your account to view your point balance and unlock items.</p>
            </div>
            <?php endif; ?>

            <h3 style="margin-bottom:25px; font-weight:800; font-size:24px; text-align:center;"><?php echo esc_html($title); ?></h3>
            
            <?php if(empty($catalog_items)): ?>
                <div style="text-align:center; padding:40px; background:#f9f9f9; border-radius:8px;">
                    <p style="margin:0; color:#777;">No rewards are currently active in the catalog. Check back soon!</p>
                </div>
            <?php else: ?>
                <div class="mc-catalog-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(<?php echo $layout === 'list' ? '100%' : '260px'; ?>, 1fr)); gap:25px;">
                    <?php 
                    foreach($catalog_items as $item): 
                        $p = $item['product'];
                        $cost = $item['cost'];
                        $percent = $balance > 0 ? min(100, ($balance / $cost) * 100) : 0;
                        $points_away = max(0, $cost - $balance);
                        $can_afford = $balance >= $cost;
                        
                        $image = $p->get_image('woocommerce_thumbnail', ['style' => 'width:100%; height:auto; max-height:200px; object-fit:cover; border-radius:8px; margin-bottom:15px;']);
                        $btn_text = $can_afford ? str_replace('{points}', number_format($cost), $btn_ready) : str_replace('{points}', number_format($points_away), $btn_short);
                    ?>
                        <div class="mc-catalog-item" style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.04); border:1px solid #eee; display:flex; <?php echo $layout === 'list' ? 'flex-direction:row; align-items:center; gap:20px;' : 'flex-direction:column;'; ?>">
                            <div style="<?php echo $layout === 'list' ? 'width:120px; flex-shrink:0;' : ''; ?>"><?php echo $image; ?></div>
                            <div style="flex-grow:1; display:flex; flex-direction:column; justify-content:space-between; height:100%;">
                                <div>
                                    <div style="font-weight:800; font-size:18px; margin-bottom:5px; line-height:1.2; color:#111;"><?php echo esc_html($p->get_name()); ?></div>
                                    <div style="font-size:14px; font-weight:700; color:#666; margin-bottom:15px;">Cost: <?php echo number_format($cost); ?> PTS</div>
                                </div>
                                <div style="margin-top:auto;">
                                    <?php if ( is_user_logged_in() ): ?>
                                        <div style="background:#f0f0f0; height:8px; border-radius:10px; overflow:hidden; position:relative; margin-bottom:12px;">
                                            <div style="background:<?php echo $can_afford ? $primary_color : '#aaa'; ?>; width:<?php echo $percent; ?>%; height:100%;"></div>
                                        </div>
                                    <?php endif; ?>
                                    <a href="<?php echo esc_url($p->get_permalink()); ?>" style="display:block; text-align:center; padding:10px 15px; border-radius:6px; text-decoration:none; font-weight:700; font-size:13px; text-transform:uppercase; transition:all 0.2s; <?php echo $can_afford ? 'background:'.esc_attr($primary_color).'; color:#fff;' : 'background:#f5f5f5; color:#888; border:1px solid #ddd; pointer-events:none;'; ?>">
                                        <?php echo esc_html($btn_text); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
            $icon = $settings['badge_icon'] ?? '🎁';
            $format = $settings['badge_format'] ?? '{icon} Redeem for {points} Pts';

            if (strpos($icon, 'http') === 0) {
                $icon_html = '<img src="'.esc_url($icon).'" style="width:18px; height:18px; vertical-align:middle; margin-right:6px; border-radius:4px;">';
            } else {
                $icon_html = '<span style="margin-right:6px;">'.esc_html($icon).'</span>';
            }
            $final_text = str_replace(['{icon}', '{points}'], [$icon_html, number_format($point_cost)], $format);

            echo '<div style="margin-top:10px; margin-bottom:20px; padding:8px 14px; background:'.esc_attr($bg).'; border:1px solid '.esc_attr($border).'; border-radius:6px; display:inline-flex; align-items:center;">';
            echo '<span style="font-weight:800; color:'.esc_attr($color).'; display:flex; align-items:center; font-size:14px;">' . wp_kses_post($final_text) . '</span>';
            echo '</div>';
        }
    }

    public function render_debug() {
        if (!current_user_can('manage_options')) return 'Admins only.';
        $bulk_rules = get_option('mc_pts_bulk_costs', []);
        ob_start();
        echo '<div style="background:#222; color:#0f0; padding:20px; font-family:monospace; border-radius:8px;">';
        echo '<h3>🔍 Loyalty System Diagnostic</h3>';
        echo '<strong>Bulk Rules Found:</strong> ' . count($bulk_rules) . '<br>';
        echo '<pre>' . print_r($bulk_rules, true) . '</pre>';
        echo '</div>';
        return ob_get_clean();
    }
}

new MC_Points_Account();