<?php
/**
 * MealCrafter: Loyalty Admin Router & Wrapper
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Admin {

    public function __construct() {
        add_action( 'admin_menu', [$this, 'register_suite_menu'], 20 );
        add_action( 'admin_init', [$this, 'register_global_settings'] ); 
        add_action( 'admin_enqueue_scripts', function() { wp_enqueue_media(); } );
        add_action( 'woocommerce_product_options_general_product_data', [$this, 'add_product_point_fields'] );
        add_action( 'woocommerce_process_product_meta', [$this, 'save_product_point_fields'] );
    }

    public function register_global_settings() {
        // Main Points Options Group
        register_setting('mc_loyalty_options_group', 'mc_pts_assign_type');
        register_setting('mc_loyalty_options_group', 'mc_pts_assign_roles');
        register_setting('mc_loyalty_options_group', 'mc_pts_specific_roles'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_earn_currency'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_earn_points'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_calc_basis'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_tax_incl'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_assign_guests'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_assign_new_registered'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_order_status'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_exclude_sale'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_exclude_coupons'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_remove_cancelled'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_remove_refunded'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_reassign_refunded'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_disable_earn_on_redeem'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_allow_shop_manager'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_rounding'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_expiration_enabled'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_expiration_time'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_expiration_type'); 
        register_setting('mc_loyalty_options_group', 'mc_pts_rules'); 
        
        // EXTRA POINTS SETTINGS
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_registration');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_registration_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_login');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_login_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_profile');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_profile_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_birthday');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_birthday_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_referral');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_referral_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_referral_revoke');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_ref_purchase');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_ref_purchase_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_reviews');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_reviews_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_orders');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_orders_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_orders_repeat');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_cart');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_cart_pts');
        register_setting('mc_loyalty_options_group', 'mc_pts_extra_cart_threshold');
        
        // LEVELS, BANNERS, RANKING
        register_setting('mc_loyalty_options_group', 'mc_pts_levels');
        register_setting('mc_loyalty_options_group', 'mc_pts_banners');
        register_setting('mc_loyalty_options_group', 'mc_pts_ranking_enable');
        register_setting('mc_loyalty_options_group', 'mc_pts_ranking_my_account');
        
        // REDEEMING OPTIONS (Global)
        register_setting('mc_loyalty_options_group', 'mc_pts_allow_redeem');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_user_type');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_specific_roles');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_method');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_points_ratio');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_currency_ratio');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_tax_calc');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_exclude_sale');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_exclude_product_level');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_auto_apply');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_box_style');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_message');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_free_shipping');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_coupon_type');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_restrictions_enable');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_max_discount');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_min_cart');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_min_discount');
        register_setting('mc_loyalty_options_group', 'mc_pts_allow_coupon_generation');
        register_setting('mc_loyalty_options_group', 'mc_pts_coupon_limits_enable');
        register_setting('mc_loyalty_options_group', 'mc_pts_coupon_min');
        register_setting('mc_loyalty_options_group', 'mc_pts_coupon_max');
        register_setting('mc_loyalty_options_group', 'mc_pts_coupon_expiry_enable');
        register_setting('mc_loyalty_options_group', 'mc_pts_coupon_expiry_days');
        register_setting('mc_loyalty_options_group', 'mc_pts_redeem_rules');

        // ==============================================
        // ISOLATED PRODUCT-LEVEL GROUPS
        // ==============================================
        
        // General Tab Group
        register_setting('mc_prod_general_group', 'mc_pts_prod_enable');
        register_setting('mc_prod_general_group', 'mc_pts_prod_max_per_cart');
        register_setting('mc_prod_general_group', 'mc_pts_prod_min_cart_total');
        register_setting('mc_prod_general_group', 'mc_pts_prod_target_users');
        register_setting('mc_prod_general_group', 'mc_pts_prod_target_roles');
        register_setting('mc_prod_general_group', 'mc_pts_prod_target_levels');
        register_setting('mc_prod_general_group', 'mc_pts_prod_base_price_only');
        register_setting('mc_prod_general_group', 'mc_pts_prod_tax_override');

        // Bulk Costs Tab Group
        register_setting('mc_prod_bulk_group', 'mc_pts_bulk_costs');

        // Auto-Giveaways Tab Group
        register_setting('mc_prod_giveaway_group', 'mc_pts_auto_giveaways');
        
        // Reward Catalog Tab Group
        register_setting('mc_prod_catalog_group', 'mc_pts_catalog_settings');
    }


    public function register_suite_menu() {
        if ( empty ( $GLOBALS['admin_page_hooks']['mc-hub'] ) ) {
            add_menu_page( 'MealCrafter Hub', 'MealCrafter', 'manage_options', 'mc-hub', [$this, 'render_fallback_dashboard'], 'dashicons-star-filled', 55 );
        }
        add_submenu_page( 'mc-hub', 'Points & Rewards', 'Points & Rewards', 'manage_options', 'mc-loyalty-settings', [$this, 'render_loyalty_settings'] );
    }

    public function render_fallback_dashboard() {
        echo '<div class="wrap"><div style="background:#1e2d3b; color:#fff; padding:30px; border-radius:12px; text-align:center;">';
        echo '<h1 style="color:#fff; font-size:32px; margin-bottom:10px;">MealCrafter Suite</h1></div></div>';
    }

    public function render_loyalty_settings() {
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'customers';

        $tabs = [
            'customers'     => 'Customers Points',
            'options'       => 'Points Options',
            'redeeming'     => 'Points Redeeming',
            'product_level' => 'Product-Level',
            'catalog'       => 'Reward Catalog',
            'customization' => 'Customization',
            'emails'        => 'Emails'
        ];

        ?>
        <style>
            .mc-layout-wrapper { display: flex; gap: 25px; margin-top: 20px; align-items: flex-start; }
            .mc-sidebar-nav { width: 240px; flex-shrink: 0; display: flex; flex-direction: column; gap: 8px; }
            .mc-subtab-link { text-decoration: none; padding: 12px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; color: #444; background: #fff; border: 1px solid #ddd; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.02); }
            .mc-subtab-link:hover { background: #f9f9f9; border-color: #ccc; color: #111; }
            .mc-subtab-link.active { background: #d63638; color: #fff; border-color: #d63638; box-shadow: 0 4px 10px rgba(214, 54, 56, 0.2); }
            .mc-main-content { flex-grow: 1; background: #fff; padding: 35px; border: 1px solid #ddd; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }

            .mc-form-section { border-bottom: 1px solid #eee; padding-bottom: 25px; margin-bottom: 25px; }
            .mc-form-section:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
            .mc-form-section h3 { font-size: 18px; margin: 0 0 20px 0; color: #1d2327; font-weight: 600; }
            
            .mc-form-row { margin-bottom: 25px; display: block; }
            .mc-form-info { margin-bottom: 8px; }
            .mc-form-label { font-weight: 600; display: block; margin-bottom: 4px; color: #1d2327; font-size: 14px; }
            .mc-form-desc { font-size: 13px; color: #646970; display: block; line-height: 1.5; margin-top: 4px; }
            
            .mc-form-control { width: 100%; max-width: 100%; }
            
            .mc-radio-group label { display: inline-block; margin-right: 20px; margin-bottom: 10px; cursor: pointer; color: #3c434a; font-weight: 400;}
            .mc-radio-group input[type="radio"] { margin-right: 6px; }
            
            .mc-inline-inputs { display: inline-flex; align-items: center; gap: 10px; background: #f6f7f7; padding: 10px 15px; border-radius: 6px; border: 1px solid #dcdcde; }
            .mc-inline-inputs input[type="number"], .mc-inline-inputs input[type="text"], .mc-inline-inputs select { padding: 4px 8px; border-radius: 4px; border: 1px solid #8c8f94; }
            
            .mc-toggle-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f0f0f1; }
            .mc-toggle-row:last-child { border-bottom: none; }
            .mc-toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink:0; }
            .mc-toggle-switch input { opacity: 0; width: 0; height: 0; }
            .mc-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 22px; }
            .mc-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
            input:checked + .mc-slider { background-color: #8bc34a; }
            input:checked + .mc-slider:before { transform: translateX(18px); }

            .mc-rule-card { background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
            .mc-rule-card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
            .mc-remove-rule { color: #d63638; cursor: pointer; font-weight: 600; text-decoration: none; font-size: 13px; }
            .mc-remove-rule:hover { text-decoration: underline; }
            .select2-container--default .select2-selection--multiple { border-radius: 4px !important; border: 1px solid #8c8f94 !important; min-height: 32px; }
        </style>

        <div class="wrap">
            <h1 style="font-weight:900;">Points & <span style="font-weight:100; color:#999;">Rewards</span></h1>
            
            <h2 class="nav-tab-wrapper" style="margin-top:20px;">
                <?php foreach($tabs as $key => $name): ?>
                    <a href="?page=mc-loyalty-settings&tab=<?php echo esc_attr($key); ?>" class="nav-tab <?php echo $current_tab === $key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($name); ?>
                    </a>
                <?php endforeach; ?>
            </h2>

            <?php
            // ONE clean switch statement with the new product_level routing
            switch ($current_tab) {
                case 'customers':
                    if (class_exists('MC_Tab_Customers')) { $tab_module = new MC_Tab_Customers(); $tab_module->render(); }
                    break;
                case 'options':
                    if (class_exists('MC_Tab_Options')) { $tab_module = new MC_Tab_Options(); $tab_module->render(); }
                    break;
                case 'redeeming':
                    if (class_exists('MC_Tab_Redeeming')) { $tab_module = new MC_Tab_Redeeming(); $tab_module->render(); }
                    break;
                case 'product_level':
                    if (class_exists('MC_Tab_Product_Level')) { $tab_module = new MC_Tab_Product_Level(); $tab_module->render(); }
                    break;
                default:
                    echo '<div class="mc-main-content" style="margin-top:20px;"><h2 style="margin-top:0; font-weight:800; border-bottom:2px solid #eee; padding-bottom:15px; margin-bottom:20px;">' . esc_html($tabs[$current_tab]) . '</h2><p>Module coming soon.</p></div>';
                    break;
            }
            ?>
        </div>
        <?php
    }

    public function add_product_point_fields() {
        echo '<div class="options_group" style="background:#f0f8ff; border-left:4px solid #3498db; padding-bottom:10px;">';
        echo '<p style="padding-left:12px; margin-bottom:0; font-weight:bold; color:#3498db;">Loyalty & Rewards Status</p>';
        woocommerce_wp_text_input(['id' => '_mc_points_redeem_price', 'label' => 'Point Cost (Redemption)', 'type' => 'number', 'desc_tip' => true, 'description' => 'Points required to get this item free.']);
        woocommerce_wp_checkbox(['id' => '_mc_points_exempt_earn', 'label' => 'Disable Point Earning']);
        woocommerce_wp_checkbox(['id' => '_mc_points_exempt_global', 'label' => 'Disable Cash Redemption']);
        echo '</div>';
    }

    public function save_product_point_fields( $post_id ) {
        update_post_meta( $post_id, '_mc_points_redeem_price', sanitize_text_field( $_POST['_mc_points_redeem_price'] ?? '' ) );
        update_post_meta( $post_id, '_mc_points_exempt_earn', isset( $_POST['_mc_points_exempt_earn'] ) ? 'yes' : 'no' );
        update_post_meta( $post_id, '_mc_points_exempt_global', isset( $_POST['_mc_points_exempt_global'] ) ? 'yes' : 'no' );
    }
}
new MC_Points_Admin();