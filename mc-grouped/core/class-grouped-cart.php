<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Grouped_Cart {

    public function __construct() {
        add_filter('woocommerce_add_cart_item_data', [$this, 'add_cart_item_data'], 10, 3);
        add_filter('woocommerce_get_item_data', [$this, 'display_cart_item_data'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_order_line_item'], 10, 4);

        // Keep emails clean for grouped items as well
        add_filter('woocommerce_email_styles', [$this, 'fix_email_image_sizes'], 10, 2);
        add_filter('woocommerce_email_order_items_args', [$this, 'force_main_product_image_in_emails']);
    }

    public function force_main_product_image_in_emails( $args ) {
        $args['show_image'] = true;
        $args['image_size'] = [ 60, 60 ];
        return $args;
    }

    public function fix_email_image_sizes($css, $email) {
        $css .= '
            .mc-combo-hover-item { text-decoration: none !important; color: #555 !important; border: none !important; font-weight: normal !important; pointer-events: none !important; }
            .mc-combo-hover-img { display: none !important; }
        ';
        return $css;
    }

    public function add_cart_item_data($cart_item_data, $product_id, $variation_id) {
        $product = wc_get_product($product_id);
        
        if ($product && $product->get_type() === 'mc_grouped') {
            if (isset($_POST['mc_grouped_items']) && is_array($_POST['mc_grouped_items'])) {
                $cart_item_data['mc_grouped_data'] = wc_clean($_POST['mc_grouped_items']);
            }
        }
        return $cart_item_data;
    }

    public function display_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['mc_grouped_data'])) {
            $names = [];
            foreach($cart_item['mc_grouped_data'] as $id) {
                $p = wc_get_product($id);
                if ($p) $names[] = $p->get_name();
            }
            if (!empty($names)) {
                $item_data[] = [
                    'key' => 'Included Items',
                    'value' => implode(', ', $names)
                ];
            }
        }
        return $item_data;
    }

    public function save_order_line_item($item, $cart_item_key, $values, $order) {
        if (isset($values['mc_grouped_data'])) {
            $slots = [];
            $raw_data = [];
            
            foreach ($values['mc_grouped_data'] as $id) {
                $p = wc_get_product($id);
                if ($p) {
                    $raw_data[] = $id;
                    $price = (float) $p->get_price();
                    $price_text = $price > 0 ? ' (+$' . number_format($price, 2) . ')' : '';
                    
                    // Grab the thumbnail image URL for the specific grouped item
                    $img_url = wp_get_attachment_image_url($p->get_image_id(), 'thumbnail'); 
                    $name = esc_html($p->get_name()) . $price_text;
                    
                    // Wrap it in the exact same HTML the Combo uses so the dashboard reads it perfectly
                    if ($img_url) {
                        $name = '<span class="mc-combo-hover-item">' . $name . '<img src="'.esc_url($img_url).'" class="mc-combo-hover-img"></span>';
                    }
                    
                    $slots['Included Items'][] = $name;
                }
            }

            // Save the formatted strings with images to the order meta
            foreach ($slots as $slot_name => $items) {
                $item->add_meta_data($slot_name, implode(', ', $items), true);
            }
            
            // Save the raw IDs so the backend editor can extract it later
            $item->add_meta_data('_mc_raw_combo_data', wp_json_encode($raw_data), true);
        }
    }
}

new MC_Grouped_Cart();