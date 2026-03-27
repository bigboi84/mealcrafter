<?php
/**
 * MealCrafter: Loyalty Points Account & Shortcodes
 * Handles "My Account" endpoint, Points Dashboard, Earn/Redeem shortcodes, & VIP Levels
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Account {

    public function __construct() {
        // Register WooCommerce My Account Endpoint
        add_action( 'init', [$this, 'add_endpoints'] );
        add_filter( 'query_vars', [$this, 'add_query_vars'], 0 );
        add_filter( 'woocommerce_account_menu_items', [$this, 'add_menu_items'] );
        
        // Render Endpoint Content
        $custom = get_option('mc_customization_settings', []);
        $endpoint = !empty($custom['account_endpoint']) ? $custom['account_endpoint'] : 'mc-rewards';
        add_action( 'woocommerce_account_' . $endpoint . '_endpoint', [$this, 'render_endpoint_content'] );
        
        // Show points in Order Details
        add_action( 'woocommerce_order_details_after_order_table', [$this, 'show_points_in_order_details'], 10, 1 );

        // Register Shortcodes for Page Builders
        add_shortcode( 'mc_rewards_dashboard', [$this, 'shortcode_dashboard'] );
        add_shortcode( 'mc_rewards_earn', [$this, 'shortcode_earn'] );
        add_shortcode( 'mc_rewards_catalog', [$this, 'shortcode_catalog'] );
        add_shortcode( 'mc_rewards_history', [$this, 'shortcode_history'] );
        add_shortcode( 'mc_rewards_offers', [$this, 'shortcode_offers'] ); 
    }

    /**
     * GLOBAL HELPER: Used globally to find the cost of a product for redemption.
     */
    public static function get_product_point_cost( $product ) {
        if ( ! $product ) return false;
        $cost = $product->get_meta( '_mc_points_redeem_price', true );
        return is_numeric($cost) && $cost > 0 ? (int) $cost : false;
    }

    /**
     * GLOBAL HELPER: Securely fetches a user's exact balance.
     */
    public static function get_accurate_user_points( $user_id = null ) {
        if ( ! $user_id ) $user_id = get_current_user_id();
        if ( ! $user_id ) return 0;
        $pts_n = get_user_meta($user_id, '_mc_user_points', true);
        $pts_o = get_user_meta($user_id, 'mc_points', true);
        return ($pts_n !== '') ? (int)$pts_n : (int)$pts_o;
    }

    /**
     * GLOBAL HELPER: Evaluates the user's lifetime points to determine their VIP Tier/Badge
     */
    public static function get_user_level( $user_id = null ) {
        if ( ! $user_id ) $user_id = get_current_user_id();
        
        $lifetime = (int) get_user_meta($user_id, '_mc_lifetime_points', true);
        
        // Failsafe: Sync lifetime points if zero but they have an active balance
        if ( $lifetime === 0 ) {
            global $wpdb;
            $table = $wpdb->prefix . 'mc_points_transactions';
            $db_lifetime = (int) $wpdb->get_var($wpdb->prepare("SELECT SUM(points) FROM $table WHERE user_id = %d AND points > 0", $user_id));
            $active_balance = self::get_accurate_user_points($user_id);
            $lifetime = max($db_lifetime, $active_balance);
            update_user_meta($user_id, '_mc_lifetime_points', $lifetime);
        }

        $levels = get_option('mc_pts_levels', []);
        if ( !is_array($levels) || empty($levels) ) return false;

        // Sort descending by min_points so they get the highest tier they qualify for
        usort($levels, function($a, $b) { return ($b['min_points'] ?? 0) <=> ($a['min_points'] ?? 0); });

        foreach ($levels as $level) {
            if ($lifetime >= (int)($level['min_points'] ?? 0)) {
                return $level;
            }
        }
        return false;
    }

    /**
     * DYNAMIC HELPER: Finds the closest reward price to act as the Progress Bar Goal
     */
    private function get_next_goal_points( $current_points ) {
        global $wpdb;
        $next_goal = $wpdb->get_var($wpdb->prepare("
            SELECT min(meta_value + 0) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_mc_points_redeem_price' AND meta_value > 0 AND (meta_value + 0) > %d
        ", $current_points));
        
        if (!$next_goal) {
            $next_goal = $wpdb->get_var("SELECT max(meta_value + 0) FROM {$wpdb->postmeta} WHERE meta_key = '_mc_points_redeem_price'");
        }
        return $next_goal ? (int)$next_goal : 1000; 
    }

    // -----------------------------------------------------------------------------------
    // MY ACCOUNT WOOCOMMERCE ENDPOINTS
    // -----------------------------------------------------------------------------------
    public function add_endpoints() {
        $custom = get_option('mc_customization_settings', []);
        $endpoint = !empty($custom['account_endpoint']) ? $custom['account_endpoint'] : 'mc-rewards';
        add_rewrite_endpoint( $endpoint, EP_ROOT | EP_PAGES );
    }

    public function add_query_vars( $vars ) {
        $custom = get_option('mc_customization_settings', []);
        $endpoint = !empty($custom['account_endpoint']) ? $custom['account_endpoint'] : 'mc-rewards';
        $vars[] = $endpoint;
        return $vars;
    }

    public function add_menu_items( $items ) {
        $custom = get_option('mc_customization_settings', []);
        if ( ($custom['account_show'] ?? 'yes') === 'yes' ) {
            $endpoint = !empty($custom['account_endpoint']) ? $custom['account_endpoint'] : 'mc-rewards';
            $label = !empty($custom['account_label']) ? $custom['account_label'] : 'Points & Rewards';
            
            $logout = $items['customer-logout'] ?? 'Logout';
            unset($items['customer-logout']);
            $items[$endpoint] = $label;
            $items['customer-logout'] = $logout;
        }
        return $items;
    }

    public function show_points_in_order_details( $order ) {
        $custom = get_option('mc_customization_settings', []);
        if ( ($custom['account_show_orders'] ?? 'yes') !== 'yes' ) return;

        $earned = (int) $order->get_meta( '_mc_points_earned_amount' );
        if ( $earned > 0 ) {
            echo '<div style="margin-top:20px; padding:15px; background:#eaf2fa; border-left:4px solid #2271b1; border-radius:4px;">';
            echo '<h3 style="margin:0 0 5px 0; font-size:16px; color:#2271b1;">Loyalty Points</h3>';
            echo '<p style="margin:0; font-size:14px; font-weight:600;">You earned <strong>' . number_format($earned) . ' points</strong> for this order!</p>';
            echo '</div>';
        }
    }

    public function render_endpoint_content() {
        echo '<div style="margin-bottom: 30px;">';
        echo $this->shortcode_dashboard();
        echo '</div>';

        $custom = get_option('mc_customization_settings', []);
        $default_tab = $custom['default_tab'] ?? 'catalog';
        
        ?>
        <style>
            .mc-account-tab {
                text-decoration: none !important;
                padding: 10px 24px;
                font-weight: bold;
                font-size: 15px;
                color: #888;
                background: #f5f5f5;
                border-radius: 30px;
                transition: all 0.2s ease-in-out;
                display: inline-block;
            }
            .mc-account-tab:hover { background: #ebebeb; color: #555; }
            .mc-account-tab.active { background: #d35400 !important; color: #ffffff !important; box-shadow: 0 4px 10px rgba(211, 84, 0, 0.2); }
            .mc-nav-scroll-wrapper {
                display: flex; gap: 12px; margin-bottom: 25px; overflow-x: auto; padding-bottom: 10px;
                -webkit-overflow-scrolling: touch; scrollbar-width: none;
            }
            .mc-nav-scroll-wrapper::-webkit-scrollbar { display: none; }
        </style>

        <div>
            <div class="mc-nav-scroll-wrapper">
                <a href="#mc-tab-earn" class="mc-account-tab <?php echo $default_tab === 'earn' ? 'active' : ''; ?>" onclick="mcSwitchLoyaltyTab('earn'); return false;">Earn</a>
                <a href="#mc-tab-catalog" class="mc-account-tab <?php echo $default_tab === 'catalog' ? 'active' : ''; ?>" onclick="mcSwitchLoyaltyTab('catalog'); return false;">Redeem</a>
                <a href="#mc-tab-offers" class="mc-account-tab <?php echo $default_tab === 'offers' ? 'active' : ''; ?>" onclick="mcSwitchLoyaltyTab('offers'); return false;">Offers</a>
                <a href="#mc-tab-history" class="mc-account-tab <?php echo $default_tab === 'history' ? 'active' : ''; ?>" onclick="mcSwitchLoyaltyTab('history'); return false;">History</a>
            </div>

            <div id="mc-content-earn" style="display: <?php echo $default_tab === 'earn' ? 'block' : 'none'; ?>;">
                <?php echo $this->shortcode_earn(); ?>
            </div>
            <div id="mc-content-catalog" style="display: <?php echo $default_tab === 'catalog' ? 'block' : 'none'; ?>;">
                <?php echo $this->shortcode_catalog(); ?>
            </div>
            <div id="mc-content-offers" style="display: <?php echo $default_tab === 'offers' ? 'block' : 'none'; ?>;">
                <?php echo $this->shortcode_offers(); ?>
            </div>
            <div id="mc-content-history" style="display: <?php echo $default_tab === 'history' ? 'block' : 'none'; ?>;">
                <?php echo $this->shortcode_history(); ?>
            </div>
        </div>

        <script>
            function mcSwitchLoyaltyTab(tab) {
                jQuery('.mc-account-tab').removeClass('active');
                jQuery('a[href="#mc-tab-' + tab + '"]').addClass('active');
                jQuery('#mc-content-earn, #mc-content-catalog, #mc-content-offers, #mc-content-history').hide();
                jQuery('#mc-content-' + tab).fadeIn(300);
            }
        </script>
        <?php
    }

    // -----------------------------------------------------------------------------------
    // SHORTCODES
    // -----------------------------------------------------------------------------------
    public function shortcode_dashboard() {
        if ( ! is_user_logged_in() ) return '<p>Please log in to view your points dashboard.</p>';
        
        $custom = get_option('mc_customization_settings', []);
        $balance = self::get_accurate_user_points();
        $goal = $this->get_next_goal_points($balance);
        $level = self::get_user_level();
        
        $percent = min(100, round( ($balance / $goal) * 100 ));
        $remaining = max(0, $goal - $balance);
        
        $show_value = ($custom['account_show_value'] ?? 'yes') === 'yes';
        $card_bg = esc_attr( $custom['dash_card_bg'] ?? '#f9f9f9' );
        $title_size = esc_attr( $custom['dash_title_size'] ?? '28' );
        $val_size = esc_attr( $custom['dash_val_size'] ?? '36' );
        
        $prog_style = $custom['prog_style'] ?? 'linear';
        $prog_bg = esc_attr( $custom['prog_bg_color'] ?? '#f0f0f0' );
        $prog_active = esc_attr( $custom['prog_color_active'] ?? '#f39c12' );
        $prog_ready = esc_attr( $custom['prog_color_ready'] ?? '#2ecc71' );

        $pts_ratio = (float) get_option('mc_pts_redeem_points_ratio', '100');
        $cur_ratio = (float) get_option('mc_pts_redeem_currency_ratio', '1');
        $worth = ($pts_ratio > 0) ? ($balance / $pts_ratio) * $cur_ratio : 0;

        ob_start();
        ?>
        <div style="background: <?php echo $card_bg; ?>; padding: 30px; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); display: flex; flex-wrap: wrap; gap: 30px; align-items: center; justify-content: space-between;">
            
            <div style="flex: 1; min-width: 250px;">
                
                <?php if ($level): ?>
                    <div style="display:flex; align-items:center; gap: 12px; margin-bottom: 20px; padding: 8px 15px; background: #fff; border: 1px solid #eee; border-radius: 50px; display: inline-flex; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
                        <?php if (!empty($level['badge_url'])): ?>
                            <img src="<?php echo esc_url($level['badge_url']); ?>" alt="VIP Badge" style="width:35px; height:35px; border-radius:50%; object-fit:cover; border:2px solid <?php echo esc_attr($level['color'] ?? '#222'); ?>;">
                        <?php endif; ?>
                        <div style="padding-right: 10px;">
                            <span style="font-size:10px; text-transform:uppercase; font-weight:800; color:#888; display:block; line-height:1; margin-bottom:2px;">VIP Tier</span>
                            <strong style="font-size:15px; color:<?php echo esc_attr($level['color'] ?? '#222'); ?>; line-height:1;"><?php echo esc_html($level['name']); ?></strong>
                        </div>
                    </div>
                <?php endif; ?>

                <h2 style="margin: 0 0 5px 0; font-size: <?php echo $title_size; ?>px; font-weight: 900; color: #222;">Your Balance</h2>
                <div style="font-size: <?php echo $val_size; ?>px; font-weight: 900; color: <?php echo $prog_active; ?>; line-height: 1;">
                    <?php echo number_format($balance); ?> <span style="font-size: <?php echo (intval($val_size)/2); ?>px; color: #888;">Pts</span>
                </div>
                
                <?php if ( $show_value && $worth > 0 ): ?>
                    <p style="margin: 8px 0 0 0; font-size: 14px; font-weight: 600; color: #2ecc71;">
                        💰 Cash Value: <?php echo wc_price($worth); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div style="flex: 2; min-width: 300px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold; font-size: 14px;">
                    <span style="color: #444;">Progress to next reward</span>
                    <span style="color: <?php echo $percent >= 100 ? $prog_ready : $prog_active; ?>;"><?php echo $percent; ?>%</span>
                </div>
                
                <?php if ( $prog_style === 'linear' ): ?>
                    <div style="width: 100%; height: 12px; background: <?php echo $prog_bg; ?>; border-radius: 20px; overflow: hidden;">
                        <div style="width: <?php echo $percent; ?>%; height: 100%; background: <?php echo $percent >= 100 ? $prog_ready : $prog_active; ?>; border-radius: 20px; transition: width 1s ease-in-out;"></div>
                    </div>
                <?php else: ?>
                    <div style="display: flex; align-items: center; gap: 20px;">
                        <svg width="80" height="80" viewBox="0 0 120 120" style="transform: rotate(-90deg);">
                            <circle cx="60" cy="60" r="54" fill="none" stroke="<?php echo $prog_bg; ?>" stroke-width="12" />
                            <circle cx="60" cy="60" r="54" fill="none" stroke="<?php echo $percent >= 100 ? $prog_ready : $prog_active; ?>" stroke-width="12" stroke-dasharray="339.292" stroke-dashoffset="<?php echo 339.292 * (1 - ($percent/100)); ?>" stroke-linecap="round" style="transition: stroke-dashoffset 1s ease-in-out;" />
                        </svg>
                        <div style="font-size: 13px; font-weight: bold; color: #666; line-height: 1.4;">
                            <?php echo $percent >= 100 ? "You've unlocked rewards!" : "Just <strong>" . number_format($remaining) . "</strong> more points needed to unlock your next reward."; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ( $prog_style === 'linear' ): ?>
                    <p style="margin: 10px 0 0 0; font-size: 13px; color: #888;">
                        <?php echo $percent >= 100 ? "Reward Unlocked! Browse the catalog below." : "Just <strong>" . number_format($remaining) . "</strong> more points needed."; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_earn() {
        ob_start();
        $ways_to_earn = [];

        $spent = (float) get_option( 'mc_pts_earn_currency', 100 );
        $earn = (float) get_option( 'mc_pts_earn_points', 20 );
        if ($spent > 0 && $earn > 0) {
            $ways_to_earn[] = ['icon' => '🛍️', 'title' => 'Make a Purchase', 'desc' => 'Earn ' . $earn . ' points for every $' . $spent . ' you spend.'];
        }

        if ( get_option('mc_pts_extra_registration', 'no') === 'yes' ) {
            $pts = (int) get_option('mc_pts_extra_registration_pts', 0);
            if ($pts > 0) $ways_to_earn[] = ['icon' => '👋', 'title' => 'Sign Up Bonus', 'desc' => 'Earn ' . $pts . ' points just for creating an account.'];
        }

        if ( get_option('mc_pts_extra_login', 'no') === 'yes' ) {
            $pts = (int) get_option('mc_pts_extra_login_pts', 0);
            if ($pts > 0) $ways_to_earn[] = ['icon' => '📅', 'title' => 'Daily Login', 'desc' => 'Earn ' . $pts . ' points every day you log in.'];
        }

        if ( get_option('mc_pts_extra_reviews', 'no') === 'yes' ) {
            $pts = (int) get_option('mc_pts_extra_reviews_pts', 0);
            if ($pts > 0) $ways_to_earn[] = ['icon' => '⭐', 'title' => 'Leave a Review', 'desc' => 'Earn ' . $pts . ' points for every approved product review.'];
        }

        if ( get_option('mc_pts_extra_cart', 'no') === 'yes' ) {
            $threshold = (float) get_option('mc_pts_extra_cart_threshold', 0);
            $pts = (int) get_option('mc_pts_extra_cart_pts', 0);
            if ($threshold > 0 && $pts > 0) $ways_to_earn[] = ['icon' => '🛒', 'title' => 'Big Spender Bonus', 'desc' => 'Earn an extra ' . $pts . ' points when your cart totals $' . $threshold . ' or more.'];
        }

        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach($ways_to_earn as $way): ?>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 25px; text-align: center; box-shadow: 0 4px 10px rgba(0,0,0,0.02); transition: 0.3s; cursor: default;">
                    <div style="font-size: 40px; margin-bottom: 15px;"><?php echo $way['icon']; ?></div>
                    <h3 style="margin: 0 0 10px 0; font-size: 18px; font-weight: 800; color: #222;"><?php echo esc_html($way['title']); ?></h3>
                    <p style="margin: 0; font-size: 14px; color: #666; line-height: 1.5;"><?php echo esc_html($way['desc']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_catalog() {
        ob_start();
        $balance = self::get_accurate_user_points();
        
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_mc_points_redeem_price',
                    'value' => 0,
                    'compare' => '>'
                ]
            ]
        ];
        
        $products = get_posts($args);

        if ( empty($products) ) {
            return '<p style="padding:20px; background:#f9f9f9; border-radius:8px; color:#888;">No rewards are currently available in the catalog. Check back soon!</p>';
        }

        $pts_label = get_option('mc_customization_settings', [])['cart_ui_pts_label'] ?? 'pts';
        
        ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px;">
            <?php foreach($products as $post): 
                $product = wc_get_product($post->ID);
                if (!$product) continue;
                
                $cost = (int) $product->get_meta('_mc_points_redeem_price', true);
                $img_url = wp_get_attachment_image_url($product->get_image_id(), 'medium');
                if (!$img_url) $img_url = wc_placeholder_img_src('medium');
                
                $can_afford = $balance >= $cost;
            ?>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.03); display: flex; flex-direction: column;">
                    <div style="height: 160px; width: 100%; background: #f9f9f9; background-image: url('<?php echo esc_url($img_url); ?>'); background-size: cover; background-position: center;"></div>
                    <div style="padding: 20px; flex-grow: 1; display: flex; flex-direction: column;">
                        <h3 style="margin: 0 0 10px 0; font-size: 16px; font-weight: 800; color: #222; line-height: 1.3;">
                            <?php echo esc_html($product->get_name()); ?>
                        </h3>
                        <div style="margin-top: auto;">
                            <?php if ($can_afford): ?>
                                <a href="<?php echo esc_url( $product->get_permalink() ); ?>?add-to-cart=<?php echo $product->get_id(); ?>" 
                                   class="mc-trigger-redemption" 
                                   data-product="<?php echo esc_attr($product->get_name()); ?>" 
                                   data-points="<?php echo esc_attr($cost); ?>" 
                                   style="display: block; width: 100%; text-align: center; background: #e74c3c; color: #fff; padding: 12px 0; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s;">
                                   Redeem (<?php echo number_format($cost) . ' ' . $pts_label; ?>)
                                </a>
                            <?php else: ?>
                                <div style="display: block; width: 100%; text-align: center; background: #f0f0f0; color: #999; padding: 12px 0; border-radius: 8px; font-weight: bold; font-size: 14px; cursor: not-allowed;">
                                   Need <?php echo number_format($cost - $balance) . ' more ' . $pts_label; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function shortcode_history() {
        if ( ! is_user_logged_in() ) return '';
        $history = get_user_meta(get_current_user_id(), '_mc_points_history', true);

        ob_start();
        if ( empty($history) || !is_array($history) ) {
            echo '<p style="padding:20px; background:#f9f9f9; border-radius:8px; color:#888;">You have no point transactions yet.</p>';
        } else {
            ?>
            <div style="overflow-x: auto; background: #fff; border: 1px solid #eee; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.02);">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #fdfbf7; border-bottom: 2px solid #eee;">
                            <th style="padding: 15px; font-size: 13px; color: #444; text-transform: uppercase;">Date</th>
                            <th style="padding: 15px; font-size: 13px; color: #444; text-transform: uppercase;">Description</th>
                            <th style="padding: 15px; font-size: 13px; color: #444; text-transform: uppercase; text-align: right;">Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($history as $log): ?>
                            <tr style="border-bottom: 1px solid #eee;">
                                <td style="padding: 15px; font-size: 14px; color: #666;"><?php echo date_i18n(get_option('date_format'), $log['date']); ?></td>
                                <td style="padding: 15px; font-size: 14px; font-weight: 600; color: #222;"><?php echo esc_html($log['reason']); ?></td>
                                <td style="padding: 15px; font-size: 14px; font-weight: 900; text-align: right; color: <?php echo $log['diff'] > 0 ? '#2ecc71' : '#e74c3c'; ?>;">
                                    <?php echo ($log['diff'] > 0 ? '+' : '') . number_format($log['diff']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
        }
        return ob_get_clean();
    }

    public function shortcode_offers() {
        ob_start();
        ?>
        <div style="padding: 30px; background: linear-gradient(135deg, #f6c064 0%, #e67e22 100%); border-radius: 12px; color: #fff; text-align: center; box-shadow: 0 10px 30px rgba(230, 126, 34, 0.3);">
            <h3 style="margin: 0 0 10px 0; font-size: 24px; font-weight: 900; color: #fff;">More offers coming soon!</h3>
            <p style="margin: 0; font-size: 15px; font-weight: 600;">Keep an eye on this space for exclusive promotions and bonus point events.</p>
        </div>
        <?php
        return ob_get_clean();
    }
}
new MC_Points_Account();