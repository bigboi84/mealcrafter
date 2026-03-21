<?php
/**
 * MealCrafter Master Dashboard: Native Menu Layout
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_menu', 'mc_master_ecosystem_menus' );

function mc_master_ecosystem_menus() {
    // Top Level Hub
    add_menu_page( 'MealCrafter Hub', 'MealCrafter', 'manage_options', 'mc-hub', 'mc_render_hub_page', 'dashicons-food', 4 );
    add_submenu_page( 'mc-hub', 'Hub & Branding', 'Hub & Branding', 'manage_options', 'mc-hub', 'mc_render_hub_page' );
    
    // Core Module Slots
    if ( shortcode_exists('mc_grouped_product') ) {
        add_submenu_page( 'mc-hub', 'Grouped Product', 'Grouped Product', 'manage_options', 'mc-grouped', 'mc_render_grouped_page' );
    }
    if ( shortcode_exists('mc_combo_product') ) {
        add_submenu_page( 'mc-hub', 'Combo Product', 'Combo Product', 'manage_options', 'mc-combo', 'mc_render_combo_page' );
    }

    // Dynamic slot for other modules (Badges, etc)
    do_action( 'mc_register_plugin_submenus', 'mc-hub' );
}

function mc_render_hub_page() {
    $current_tab = $_GET['tab'] ?? 'dashboard';
    ?>
    <div class="wrap">
        <h1 style="font-weight:900;">MealCrafter <span style="font-weight:100; color:#999;">Hub</span></h1>
        
        <h2 class="nav-tab-wrapper">
            <a href="?page=mc-hub&tab=dashboard" class="nav-tab <?php echo $current_tab === 'dashboard' ? 'nav-tab-active' : ''; ?>">Modules</a>
            <a href="?page=mc-hub&tab=branding" class="nav-tab <?php echo $current_tab === 'branding' ? 'nav-tab-active' : ''; ?>">Global Branding</a>
            <a href="?page=mc-hub&tab=licensing" class="nav-tab <?php echo $current_tab === 'licensing' ? 'nav-tab-active' : ''; ?>">Licensing</a>
        </h2>

        <div style="background:#fff; padding:30px; border:1px solid #ddd; margin-top:-1px;">
            <?php if ( $current_tab === 'dashboard' ) : ?>
                <h3>Active MealCrafter Tools</h3>
                <p>Welcome to your ecosystem. Configure each module using the sidebar links.</p>

            <?php elseif ( $current_tab === 'branding' ) : ?>
                <form method="post" action="options.php">
                    <?php settings_fields( 'mc_branding_group' ); ?>
                    <?php 
                        $font_family = get_option( 'mc_font_family', 'inherit' );
                        $brand_color = get_option( 'mc_brand_color', '#e74c3c' );
                    ?>
                    <table class="form-table">
                        <tr><th>Font Family</th><td><input type="text" name="mc_font_family" value="<?php echo esc_attr($font_family); ?>" class="regular-text"></td></tr>
                        <tr><th>Primary Color</th><td><input type="color" name="mc_brand_color" value="<?php echo esc_attr($brand_color); ?>"></td></tr>
                    </table>
                    <?php submit_button(); ?>
                </form>

            <?php elseif ( $current_tab === 'licensing' ) : ?>
                <h3>Licensing</h3>
                <p>Manage your keys and subscription status here.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

function mc_render_grouped_page() {
    $gp_title_size = get_option( 'mc_gp_title_size', '16' );
    $gp_price_size = get_option( 'mc_gp_price_size', '15' );
    $gp_card_pad   = get_option( 'mc_gp_card_pad', '20' );
    ?>
    <div class="wrap">
        <h1 style="font-weight:900;">Grouped <span style="font-weight:100; color:#999;">Product</span></h1>
        <div style="background:#fff; padding:30px; border:1px solid #ddd; margin-top:20px;">
            <form method="post" action="options.php">
                <?php settings_fields( 'mc_grouped_group' ); ?>
                <table class="form-table">
                    <tr><th>Title Size</th><td><input type="number" name="mc_gp_title_size" value="<?php echo esc_attr($gp_title_size); ?>"> px</td></tr>
                    <tr><th>Price Size</th><td><input type="number" name="mc_gp_price_size" value="<?php echo esc_attr($gp_price_size); ?>"> px</td></tr>
                    <tr><th>Padding</th><td><input type="number" name="mc_gp_card_pad" value="<?php echo esc_attr($gp_card_pad); ?>"> px</td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    <?php
}

function mc_render_combo_page() {
    $cb_summary_box  = get_option( 'mc_cb_summary_box', 'on' );
    $cb_cart_text    = get_option( 'mc_cb_cart_text', 'ADD COMBO' );
    ?>
    <div class="wrap">
        <h1 style="font-weight:900;">Combo <span style="font-weight:100; color:#999;">Product</span></h1>
        <div style="background:#fff; padding:30px; border:1px solid #ddd; margin-top:20px;">
            <form method="post" action="options.php">
                <?php settings_fields( 'mc_combo_group' ); ?>
                <table class="form-table">
                    <tr><th>Enable Summary</th><td><select name="mc_cb_summary_box"><option value="on" <?php selected($cb_summary_box,'on');?>>On</option><option value="off" <?php selected($cb_summary_box,'off');?>>Off</option></select></td></tr>
                    <tr><th>Button Text</th><td><input type="text" name="mc_cb_cart_text" value="<?php echo esc_attr($cb_cart_text); ?>"></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
    </div>
    <?php
}

add_action( 'admin_init', function() {
    register_setting( 'mc_branding_group', 'mc_font_family' );
    register_setting( 'mc_branding_group', 'mc_brand_color' );
    register_setting( 'mc_grouped_group', 'mc_gp_title_size' );
    register_setting( 'mc_grouped_group', 'mc_gp_price_size' );
    register_setting( 'mc_grouped_group', 'mc_gp_card_pad' );
    register_setting( 'mc_combo_group', 'mc_cb_summary_box' );
    register_setting( 'mc_combo_group', 'mc_cb_cart_text' );
});