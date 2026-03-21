<?php
/**
 * MealCrafter: Advanced Badge Engine (Frontend Render)
 * Fix: Stops WordPress from overwriting the main Hub menu
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Badge_Engine {

    public function __construct() {
        add_action( 'init', [ $this, 'register_badge_post_type' ] );
        add_action( 'woocommerce_before_shop_loop_item_title', [ $this, 'render_badge_html' ], 10 );
        add_action( 'woocommerce_before_single_product_summary', [ $this, 'render_badge_html' ], 10 );
        add_action( 'wp_head', [ $this, 'inject_global_css' ] );
    }

    public function register_badge_post_type() {
        register_post_type( 'mc_badge', [
            'labels' => [
                'name' => 'Badges', 
                'singular_name' => 'Badge',
                'add_new' => 'Create New Badge',
                'edit_item' => 'Edit Badge Designer'
            ],
            'public' => false, 
            'show_ui' => true, 
            'supports' => ['title'],
            'menu_icon' => 'dashicons-tag', 
            /* THE FIX: False prevents WP from destroying the MC Hub link */
            'show_in_menu' => false, 
        ]);
    }

    public function inject_global_css() {
        ?>
        <style>
            .mc-product-badge { position: absolute !important; z-index: 50 !important; pointer-events: none !important; display: flex !important; transform: translate(var(--mx,0), var(--my,0)); }
            .mc-anchor-top-left { top:8px; left:8px; }
            .mc-anchor-top-center { top:8px; left:50%; margin-left: calc(var(--w) / -2); }
            .mc-anchor-top-right { top:8px; right:8px; }
            .mc-anchor-middle-left { top:50%; left:8px; transform: translateY(-50%) translate(var(--mx,0), var(--my,0)); }
            .mc-anchor-middle-center { top:50%; left:50%; transform: translate(calc(-50% + var(--mx,0)), calc(-50% + var(--my,0))); }
            .mc-anchor-middle-right { top:50%; right:8px; transform: translateY(-50%) translate(var(--mx,0), var(--my,0)); }
            .mc-anchor-bottom-left { bottom:8px; left:8px; }
            .mc-anchor-bottom-center { bottom:8px; left:50%; margin-left: calc(var(--w) / -2); }
            .mc-anchor-bottom-right { bottom:8px; right:8px; }
            
            @media (max-width: 768px) {
                .mc-product-badge { transform: translate(var(--mmx, var(--mx,0)), var(--mmy, var(--my,0))); }
            }
            .woocommerce ul.products li.product a img, .woocommerce-product-gallery__image { position: relative; }
        </style>
        <?php
    }

    public function render_badge_html() {
        global $product;
        if($product) echo self::get_badge_by_id($product->get_id());
    }

    public static function get_badge_by_id($product_id) {
        $override_ids = get_post_meta($product_id, '_mc_product_badge_ids', true) ?: [];
        if (!is_array($override_ids)) {
            $override_ids = filter_var($override_ids, FILTER_VALIDATE_INT) ? [$override_ids] : [];
        }
        
        $badges = get_posts([
            'post_type' => 'mc_badge', 'post_status' => 'publish', 'numberposts' => -1, 
            'meta_key' => '_mc_badge_priority', 'orderby' => 'meta_value_num', 'order' => 'DESC'
        ]);

        $html = '';
        foreach ($badges as $badge) {
            $allowed_prods = get_post_meta($badge->ID, '_mc_badge_prods', true) ?: [];
            
            if (in_array($badge->ID, $override_ids) || in_array($product_id, $allowed_prods)) {
                $grid = get_post_meta($badge->ID, '_mc_badge_grid', true) ?: 'top-right';
                $mx   = get_post_meta($badge->ID, '_mc_badge_off_x', true) ?: '0';
                $my   = get_post_meta($badge->ID, '_mc_badge_off_y', true) ?: '0';
                $mmx  = get_post_meta($badge->ID, '_mc_badge_m_off_x', true) ?: $mx;
                $mmy  = get_post_meta($badge->ID, '_mc_badge_m_off_y', true) ?: $my;
                $w    = get_post_meta($badge->ID, '_mc_badge_width', true) ?: '60';
                $img  = get_post_meta($badge->ID, '_mc_badge_image', true);
                
                if($img) {
                    $vars = "--mx:{$mx}px; --my:{$my}px; --mmx:{$mmx}px; --mmy:{$mmy}px; --w:{$w}px;";
                    $html .= "<div class='mc-product-badge mc-anchor-{$grid}' style='{$vars}'><img src='{$img}' style='width:{$w}px; height:auto; display:block;'></div>";
                }
            }
        }
        return $html;
    }
}
new MC_Badge_Engine();