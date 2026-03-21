<?php
/**
 * MealCrafter: Loyalty My Account Dashboard & Progress Bars
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Account {

    public function __construct() {
        // 1. Create the standalone shortcode
        add_shortcode( 'mc_rewards_dashboard', [$this, 'render_dashboard'] );

        // 2. Automatically add a new tab to WooCommerce My Account
        add_action( 'init', [$this, 'add_my_account_endpoint'] );
        add_filter( 'woocommerce_account_menu_items', [$this, 'add_my_account_menu_item'] );
        add_action( 'woocommerce_account_mc-rewards_endpoint', [$this, 'my_account_content'] );
    }

    public function add_my_account_endpoint() {
        add_rewrite_endpoint( 'mc-rewards', EP_ROOT | EP_PAGES );
    }

    public function add_my_account_menu_item( $items ) {
        // Insert it right after the "Orders" tab
        $new_items = [];
        foreach ( $items as $key => $value ) {
            $new_items[$key] = $value;
            if ( $key === 'orders' ) {
                $new_items['mc-rewards'] = 'Points & Rewards';
            }
        }
        return $new_items;
    }

    public function my_account_content() {
        // Just call our shortcode function to render the content in the tab!
        echo $this->render_dashboard();
    }

    public function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<p>Please log in to view your rewards.</p>';
        }

        $user_id = get_current_user_id();
        $balance = mc_get_user_points($user_id);

        // Fetch all products that have a point price AND are not exempt from redemption
        $args = [
            'post_type' => 'product',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_mc_points_redeem_price',
                    'value' => 0,
                    'compare' => '>',
                    'type' => 'NUMERIC'
                ],
                [
                    'relation' => 'OR',
                    [
                        'key' => '_mc_points_exempt_redeem',
                        'compare' => 'NOT EXISTS'
                    ],
                    [
                        'key' => '_mc_points_exempt_redeem',
                        'value' => 'yes',
                        'compare' => '!='
                    ]
                ]
            ]
        ];
        $products = get_posts($args);

        ob_start();
        ?>
        <div class="mc-rewards-dashboard" style="background:#f9f9f9; padding:30px; border-radius:15px; font-family:inherit;">
            
            <div style="text-align:center; margin-bottom:40px; background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
                <span style="font-size:14px; color:#888; text-transform:uppercase; font-weight:bold;">Current Balance</span>
                <div style="font-size:48px; font-weight:900; color:#e74c3c; line-height:1; margin-top:5px;">
                    <?php echo number_format($balance); ?> <span style="font-size:18px; color:#666;">PTS</span>
                </div>
            </div>

            <h3 style="margin-bottom:20px; font-weight:800;">Rewards Catalog</h3>
            
            <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(250px, 1fr)); gap:20px;">
                <?php 
                if(empty($products)) {
                    echo '<p>No rewards available at the moment. Check back soon!</p>';
                } else {
                    foreach($products as $p): 
                        $product = wc_get_product($p->ID);
                        $points_needed = (int) get_post_meta($p->ID, '_mc_points_redeem_price', true);
                        $percent = min(100, ($balance / $points_needed) * 100);
                        $points_away = max(0, $points_needed - $balance);
                        
                        // Get the product image
                        $image = $product->get_image('woocommerce_thumbnail', ['style' => 'width:100%; height:auto; max-height:180px; object-fit:cover; border-radius:8px; margin-bottom:15px;']);
                    ?>
                        <div style="background:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.05); border:1px solid #eee; display:flex; flex-direction:column;">
                            <?php echo $image; ?>
                            <div style="font-weight:800; font-size:16px; margin-bottom:5px; line-height:1.2;"><?php echo esc_html($p->post_title); ?></div>
                            <div style="font-size:13px; color:#e74c3c; font-weight:bold; margin-bottom:15px;">Cost: <?php echo number_format($points_needed); ?> PTS</div>
                            
                            <div style="margin-top:auto;">
                                <div style="background:#eee; height:12px; border-radius:10px; overflow:hidden; position:relative; margin-bottom:8px;">
                                    <div style="background:<?php echo $percent >= 100 ? '#2ecc71' : '#f39c12'; ?>; width:<?php echo $percent; ?>%; height:100%; transition:width 0.5s;"></div>
                                </div>
                                
                                <div style="display:flex; justify-content:space-between; font-size:11px; font-weight:800; text-transform:uppercase;">
                                    <?php if ($points_away > 0): ?>
                                        <span style="color:#888;"><?php echo number_format($points_away); ?> pts away</span>
                                    <?php else: ?>
                                        <span style="color:#2ecc71;">UNLOCKED!</span>
                                    <?php endif; ?>
                                    <span style="color:#444;"><?php echo round($percent); ?>%</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; 
                } ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
new MC_Points_Account();