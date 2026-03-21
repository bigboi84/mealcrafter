<?php
/**
 * MealCrafter: Server-Side Combo Security, Math Engine, & Cart UI
 * Fix: Smart Math Meta Display, Saves Raw Data, & Clean Email Formatting
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Combo_Cart_Security {

    public function __construct() {
        add_filter( 'woocommerce_add_to_cart_validation', [ $this, 'validate_combo_integrity' ], 10, 3 );
        add_filter( 'woocommerce_add_cart_item_data', [ $this, 'attach_combo_data' ], 10, 3 );
        add_action( 'woocommerce_before_calculate_totals', [ $this, 'calculate_secure_price' ], 20, 1 );
        add_filter( 'woocommerce_cart_item_name', [ $this, 'display_combo_in_cart' ], 10, 3 );
        add_action( 'woocommerce_add_to_cart', [ $this, 'remove_old_edited_cart_item' ], 10, 6 );
        add_filter( 'woocommerce_cart_item_price', [ $this, 'hide_combo_unit_price' ], 10, 3 );
        add_filter( 'woocommerce_cart_item_class', [ $this, 'add_combo_row_class' ], 10, 3 );
        
        // CSS Injections for Hover Images on Frontend
        add_action( 'wp_head', [ $this, 'inject_cart_styles' ] );
        add_action( 'admin_head', [ $this, 'inject_cart_styles' ] ); 
        
        // Save to Database
        add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'save_combo_order_meta' ], 10, 4 );

        // THE FIX: Clean up WooCommerce Emails (Show Main Image, Hide Inline Hover Images)
        add_filter('woocommerce_email_styles', [$this, 'fix_combo_email_image_sizes'], 10, 2);
        add_filter('woocommerce_email_order_items_args', [$this, 'force_main_product_image_in_emails']);
    }

    public function force_main_product_image_in_emails( $args ) {
        // Forces WooCommerce to show the Main Product Image (e.g. The Coke Bottle) in emails
        $args['show_image'] = true;
        $args['image_size'] = [ 60, 60 ];
        return $args;
    }

    public function fix_combo_email_image_sizes($css, $email) {
        // Hides the inline sub-item images ONLY in emails, converting it back to clean text.
        // The Store Manager dashboard will still have the hovers!
        $css .= '
            .mc-combo-hover-item { text-decoration: none !important; color: #555 !important; border: none !important; font-weight: normal !important; pointer-events: none !important; }
            .mc-combo-hover-img { display: none !important; }
        ';
        return $css;
    }

    public function display_combo_in_cart( $item_name, $cart_item, $cart_item_key ) {
        if ( ! isset( $cart_item['mc_combo_selections'] ) || $cart_item['data']->get_type() !== 'mc_combo' ) return $item_name;

        $selections = $cart_item['mc_combo_selections'];
        $product    = $cart_item['data'];
        $qty        = intval( $cart_item['quantity'] );
        $math_logic = get_option( 'mc_combo_math_logic', 'on' );
        
        $highest_extra = 0;
        if ( $math_logic === 'on' ) {
            foreach ( $selections as $sel ) {
                $id = is_array($sel) ? $sel['id'] : $sel;
                $p = wc_get_product( $id );
                if ( $p && (float)$p->get_price() > $highest_extra ) {
                    $highest_extra = (float)$p->get_price();
                }
            }
        }

        $slots = [];
        $charged_highest = false;

        foreach ( $selections as $sel ) {
            $id = is_array($sel) ? $sel['id'] : $sel;
            $slot_name = (is_array($sel) && isset($sel['slot'])) ? $sel['slot'] : 'Selections';

            $p = wc_get_product( $id );
            if ( $p ) { 
                $price = (float) $p->get_price();
                $price_text = '';
                
                if ( $price > 0 ) {
                    if ( $math_logic === 'on' ) {
                        if ( $price == $highest_extra && !$charged_highest ) {
                            $price_text = ' <strong style="color:#e74c3c;">(+$' . number_format($price, 2) . ')</strong>';
                            $charged_highest = true;
                        } else {
                            $price_text = ' <span style="color:#aaa;"><del>(+$' . number_format($price, 2) . ')</del> <small>Waived</small></span>';
                        }
                    } else {
                        $price_text = ' (+$' . number_format($price, 2) . ')';
                    }
                }

                $slots[$slot_name][] = $p->get_name() . $price_text; 
            }
        }

        $formatted_name = '<span style="font-weight:900; color:#222;">(' . $qty . ')</span> ' . $item_name;
        $short_desc = $product->get_short_description();
        $product_url= $product->get_permalink();
        $edit_url   = add_query_arg( 'edit_combo', $cart_item_key, $product_url );
        $remove_url = wc_get_cart_remove_url( $cart_item_key );

        ob_start();
        ?>
        <div class="mc-cart-combo-summary" style="margin-top: 6px;">
            <?php foreach($slots as $slot_name => $items): ?>
                <div style="font-size: 13px; color: #444; line-height: 1.4; margin-bottom: 2px;">
                    <strong><?php echo esc_html($slot_name); ?>:</strong> <?php echo implode(', ', $items); ?>
                </div>
            <?php endforeach; ?>
            
            <?php if ( $short_desc ) : ?>
                <div style="font-size: 12px; color: #888; font-style: italic; margin-bottom: 6px;">
                    <?php echo wp_kses_post( $short_desc ); ?>
                </div>
            <?php endif; ?>

            <?php if ( is_cart() ) : ?>
                <div style="display: flex; gap: 15px; margin-top: 10px; font-weight: 800; font-size: 11px; text-transform: uppercase;">
                    <a href="<?php echo esc_url( $edit_url ); ?>" style="color: #222; text-decoration: none; border-bottom: 1px solid #222; padding-bottom: 1px;">Edit Selections</a>
                    <a href="<?php echo esc_url( $remove_url ); ?>" style="color: #222; text-decoration: none; border-bottom: 1px solid #222; padding-bottom: 1px;">Remove</a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $custom_html = ob_get_clean();
        return $formatted_name . $custom_html;
    }

    public function validate_combo_integrity( $passed, $product_id, $quantity ) {
        $product = wc_get_product( $product_id );
        if ( ! $product || $product->get_type() !== 'mc_combo' ) return $passed;

        if ( ! isset( $_POST['mc_combo_items'] ) || empty( $_POST['mc_combo_items'] ) ) {
            wc_add_notice( 'Please complete your combo selections.', 'error' );
            return false;
        }

        $combo_data = get_post_meta( $product_id, '_mc_combo_meta', true );
        $posted_items = [];
        foreach((array) $_POST['mc_combo_items'] as $item) {
            $posted_items[] = is_array($item) ? intval($item['id']) : intval($item);
        }
        
        $total_selected = count( $posted_items );
        $required_total = 0;
        $allowed_ids = [];

        foreach ( $combo_data as $step ) {
            if ( $step['required'] ) $required_total += (int)$step['limit'];
            if ( $step['type'] === 'category' ) {
                $args = ['post_type' => 'product', 'posts_per_page' => -1, 'fields' => 'ids', 'tax_query' => [['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $step['items']]]];
                $allowed_ids = array_merge($allowed_ids, get_posts($args));
            } else {
                $allowed_ids = array_merge($allowed_ids, (array)$step['items']);
            }
        }

        if ( $total_selected < $required_total ) { wc_add_notice( 'Security Error: Required combo steps missing.', 'error' ); return false; }
        foreach ( $posted_items as $item_id ) {
            if ( ! in_array( $item_id, $allowed_ids ) ) { wc_add_notice( 'Security Error: Invalid or unauthorized item detected.', 'error' ); return false; }
        }
        return $passed;
    }

    public function attach_combo_data( $cart_item_data, $product_id, $variation_id ) {
        if ( isset( $_POST['mc_combo_items'] ) ) { $cart_item_data['mc_combo_selections'] = $_POST['mc_combo_items']; }
        return $cart_item_data;
    }

    public function calculate_secure_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        $math_logic = get_option( 'mc_combo_math_logic', 'on' );

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( isset( $cart_item['mc_combo_selections'] ) && $cart_item['data']->get_type() === 'mc_combo' ) {
                $base_price = (float) $cart_item['data']->get_regular_price();
                $highest_extra = 0;

                if ( $math_logic === 'on' ) {
                    foreach ( $cart_item['mc_combo_selections'] as $sel ) {
                        $id = is_array($sel) ? $sel['id'] : $sel;
                        $p = wc_get_product( $id );
                        if ( $p && (float) $p->get_price() > $highest_extra ) {
                            $highest_extra = (float) $p->get_price();
                        }
                    }
                    $final_price = $base_price + $highest_extra;
                } else { $final_price = $base_price; }
                $cart_item['data']->set_price( $final_price );
            }
        }
    }

    public function remove_old_edited_cart_item( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
        if ( isset( $_POST['mc_edit_key'] ) && ! empty( $_POST['mc_edit_key'] ) ) WC()->cart->remove_cart_item( wc_clean( $_POST['mc_edit_key'] ) );
    }

    public function hide_combo_unit_price( $price, $cart_item, $cart_item_key ) {
        if ( $cart_item['data']->get_type() === 'mc_combo' ) return ''; 
        return $price;
    }

    public function add_combo_row_class( $class, $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['data'] ) && $cart_item['data']->get_type() === 'mc_combo' ) $class .= ' mc-combo-cart-row';
        return $class;
    }

    public function inject_cart_styles() {
        ?>
        <style>
            .woocommerce-cart-form table.shop_table, .woocommerce-checkout table.shop_table, #order_review table.shop_table, table.woocommerce-checkout-review-order-table { border-collapse: separate !important; border-spacing: 0 8px !important; background: transparent !important; border: none !important; }
            .woocommerce-cart-form table.shop_table tbody, table.woocommerce-checkout-review-order-table tbody { background: transparent !important; }
            .woocommerce-cart-form table.shop_table th, .woocommerce-checkout table.shop_table th, table.woocommerce-checkout-review-order-table th { border: none !important; text-transform: uppercase; font-size: 12px; color: #888; padding-bottom: 0 !important; background: transparent !important; }
            .woocommerce-checkout-review-order-table tbody .cart_item, .woocommerce-cart-form table.shop_table tbody .cart_item { position: relative; margin-bottom: 10px !important; background: #fff !important; box-shadow: 0 4px 10px rgba(0,0,0,0.03) !important; border-radius: 10px !important; transition: 0.2s; border: 1px solid #eee; }
            .woocommerce-cart-form table.shop_table tr.cart_item td, .woocommerce-checkout table.shop_table tr.cart_item td, table.woocommerce-checkout-review-order-table tr.cart_item td { border: none !important; padding: 12px 15px !important; vertical-align: middle !important; background: transparent !important; }
            .woocommerce-cart-form table.shop_table tr.cart_item td:first-child, .woocommerce-checkout table.shop_table tr.cart_item td:first-child, table.woocommerce-checkout-review-order-table tr.cart_item td:first-child { border-radius: 10px 0 0 10px !important; }
            .woocommerce-cart-form table.shop_table tr.cart_item td:last-child, .woocommerce-checkout table.shop_table tr.cart_item td:last-child, table.woocommerce-checkout-review-order-table tr.cart_item td:last-child { border-radius: 0 10px 10px 0 !important; }
            table.woocommerce-checkout-review-order-table tr.cart_item td.product-thumbnail { display: none !important; }
            table.woocommerce-checkout-review-order-table tr.mc-combo-cart-row td.product-name strong.product-quantity { display: none !important; }

            .mc-combo-hover-item { position: relative; cursor: help; border-bottom: 1px dashed #e74c3c; color: #e74c3c; font-weight: bold; transition: 0.2s; white-space: nowrap; display: inline-block; }
            .mc-combo-hover-item:hover { background: #fff3cd; color: #333; }
            .mc-combo-hover-img { display: none; position: absolute; top: 100%; left: 0; width: 100px; height: 100px; object-fit: cover; border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.5); z-index: 999999 !important; pointer-events: none; border: 3px solid #e74c3c; background: #fff; margin-top: 5px; }
            .mc-combo-hover-item:hover .mc-combo-hover-img { display: block; }
        </style>
        <?php
    }

    public function save_combo_order_meta( $item, $cart_item_key, $values, $order ) {
        if ( isset( $values['mc_combo_selections'] ) && ! empty( $values['mc_combo_selections'] ) ) {
            $slots = [];
            $math_logic = get_option( 'mc_combo_math_logic', 'on' );

            $highest_extra = 0;
            if ( $math_logic === 'on' ) {
                foreach ( $values['mc_combo_selections'] as $sel ) {
                    $id = is_array($sel) ? $sel['id'] : $sel;
                    $p = wc_get_product( $id );
                    if ( $p && (float)$p->get_price() > $highest_extra ) {
                        $highest_extra = (float)$p->get_price();
                    }
                }
            }

            $charged_highest = false;
            foreach ( $values['mc_combo_selections'] as $sel ) {
                $id = is_array($sel) ? $sel['id'] : $sel;
                $slot_name = (is_array($sel) && isset($sel['slot'])) ? $sel['slot'] : 'Selections';

                $p = wc_get_product( $id );
                if ( $p ) {
                    $price = (float) $p->get_price();
                    $price_text = '';
                    
                    if ( $price > 0 ) {
                        if ( $math_logic === 'on' ) {
                            if ( $price == $highest_extra && !$charged_highest ) {
                                $price_text = ' <strong style="color:#e74c3c;">(+$' . number_format($price, 2) . ')</strong>';
                                $charged_highest = true;
                            } else {
                                $price_text = ' <span style="color:#aaa;"><del>(+$' . number_format($price, 2) . ')</del> <small>Waived</small></span>';
                            }
                        } else {
                            $price_text = ' (+$' . number_format($price, 2) . ')';
                        }
                    }

                    $img_url = wp_get_attachment_image_url( $p->get_image_id(), 'medium' );
                    $name = esc_html($p->get_name()) . $price_text;
                    
                    if ($img_url) {
                        $name = '<span class="mc-combo-hover-item">' . $name . '<img src="'.esc_url($img_url).'" class="mc-combo-hover-img"></span>';
                    }
                    
                    $slots[$slot_name][] = $name;
                }
            }
            
            foreach ($slots as $slot_name => $items) {
                $item->add_meta_data( sanitize_text_field($slot_name), implode(', ', $items), true );
            }
            
            $item->add_meta_data('_mc_raw_combo_data', wp_json_encode($values['mc_combo_selections']), true);
        }
    }
}
new MC_Combo_Cart_Security();