<?php
/**
 * MealCrafter: Loyalty Inline Checkout Redemption
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Checkout {

    public function __construct() {
        // 1. Add the Redeem/Undo links under the product names
        add_filter( 'woocommerce_cart_item_name', [$this, 'inline_redemption_link'], 10, 3 );

        // 2. Force the price to $0.00 for redeemed items
        add_action( 'woocommerce_before_calculate_totals', [$this, 'apply_redemption_price'], 10, 1 );

        // 3. Save the redeemed flag to the actual order so the Earning Engine sees it
        add_action( 'woocommerce_checkout_create_order_line_item', [$this, 'save_redeemed_meta_to_order'], 10, 4 );

        // 4. Deduct the points from the user's account when the order is successfully placed
        add_action( 'woocommerce_checkout_order_processed', [$this, 'deduct_points_on_checkout'], 10, 1 );

        // 5. Handle the AJAX clicks without leaving the page
        add_action( 'wp_ajax_mc_redeem_item', [$this, 'ajax_redeem_item'] );
        add_action( 'wp_ajax_mc_undo_redeem_item', [$this, 'ajax_undo_redeem_item'] );

        // 6. Inject the Javascript
        add_action( 'wp_footer', [$this, 'inject_checkout_js'] );
    }

    public function inline_redemption_link( $name, $cart_item, $cart_item_key ) {
        if ( ! is_user_logged_in() ) return $name;
        if ( ! is_cart() && ! is_checkout() ) return $name;

        $product_id = $cart_item['product_id'];
        $point_price = (int) get_post_meta($product_id, '_mc_points_redeem_price', true);
        $is_exempt = get_post_meta($product_id, '_mc_points_exempt_redeem', true) === 'yes';

        // If the product is eligible for redemption
        if ( $point_price > 0 && ! $is_exempt ) {
            $balance = mc_get_user_points(get_current_user_id());

            // Check if it's currently flagged as redeemed in the session
            if ( !empty($cart_item['mc_is_redeemed']) ) {
                $name .= '<br><small style="color:#2ecc71; font-weight:700; display:inline-block; margin-top:5px;">✓ REDEEMED WITH POINTS</small> ';
                $name .= '<a href="#" class="mc-undo-redeem" data-key="'.esc_attr($cart_item_key).'" style="font-size:11px; color:#e74c3c; text-decoration:none; margin-left:5px;">(Undo)</a>';
            } 
            // If not redeemed, but they have enough points to afford it
            elseif ( $balance >= $point_price ) {
                $name .= '<br><a href="#" class="mc-redeem-inline" data-key="'.esc_attr($cart_item_key).'" style="color:#e74c3c; font-weight:900; font-size:11px; text-decoration:none; display:inline-block; margin-top:5px; background:#e74c3c15; padding:3px 8px; border-radius:4px;">REDEEM FOR '.number_format($point_price).' PTS</a>';
            }
        }
        return $name;
    }

    public function apply_redemption_price( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;

        foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
            if ( !empty($cart_item['mc_is_redeemed']) ) {
                // Instantly force the cost of this specific item to $0.00
                $cart_item['data']->set_price(0);
            }
        }
    }

    public function save_redeemed_meta_to_order( $item, $cart_item_key, $values, $order ) {
        if ( !empty($values['mc_is_redeemed']) ) {
            // This secretly tags the order line item so the Earning Engine knows NOT to award points for it!
            $item->add_meta_data( '_mc_is_redeemed', 'yes' );
            
            // Log how much it cost so we can deduct it
            $point_price = (int) get_post_meta($values['product_id'], '_mc_points_redeem_price', true);
            $item->add_meta_data( '_mc_points_cost', $point_price );
        }
    }

    public function deduct_points_on_checkout( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        
        $user_id = $order->get_user_id();
        if ( ! $user_id ) return;

        // Safety check so we don't deduct twice if the hook fires twice
        if ( $order->get_meta( '_mc_points_deducted' ) === 'yes' ) return;

        $total_points_spent = 0;
        foreach( $order->get_items() as $item ) {
            if ( $item->get_meta( '_mc_is_redeemed' ) === 'yes' ) {
                $total_points_spent += (int) $item->get_meta( '_mc_points_cost' );
            }
        }

        // If they spent points, deduct them from the database!
        if ( $total_points_spent > 0 ) {
            // Notice the negative sign!
            mc_update_user_points( 
                $user_id, 
                -$total_points_spent, 
                'redeemed', 
                sprintf('Redeemed item(s) on Order #%s', $order->get_order_number()), 
                $order_id 
            );
            
            $order->update_meta_data( '_mc_points_deducted', 'yes' );
            $order->add_order_note( sprintf( 'MealCrafter Loyalty: Deducted %s points for redeemed items.', number_format( $total_points_spent ) ) );
            $order->save();
        }
    }

    public function ajax_redeem_item() {
        $cart_key = sanitize_text_field($_POST['cart_key']);
        if ( isset( WC()->cart->cart_contents[ $cart_key ] ) ) {
            WC()->cart->cart_contents[ $cart_key ]['mc_is_redeemed'] = true;
            WC()->cart->set_session();
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function ajax_undo_redeem_item() {
        $cart_key = sanitize_text_field($_POST['cart_key']);
        if ( isset( WC()->cart->cart_contents[ $cart_key ] ) ) {
            unset( WC()->cart->cart_contents[ $cart_key ]['mc_is_redeemed'] );
            WC()->cart->set_session();
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    public function inject_checkout_js() {
        if ( ! is_cart() && ! is_checkout() ) return;
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Handle the REDEEM click
            $(document).on('click', '.mc-redeem-inline', function(e) {
                e.preventDefault();
                let $btn = $(this);
                let key = $btn.data('key');
                
                $btn.text('APPLYING...').css('pointer-events', 'none');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', { 
                    action: 'mc_redeem_item', 
                    cart_key: key 
                }, function(res) {
                    if(res.success) {
                        $('body').trigger('update_checkout');
                        $(document.body).trigger('wc_update_cart'); 
                    } else {
                        $btn.text('ERROR').css('pointer-events', 'auto');
                    }
                });
            });

            // Handle the UNDO click
            $(document).on('click', '.mc-undo-redeem', function(e) {
                e.preventDefault();
                let $btn = $(this);
                let key = $btn.data('key');
                
                $btn.text('(Removing...)').css('pointer-events', 'none');
                
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', { 
                    action: 'mc_undo_redeem_item', 
                    cart_key: key 
                }, function(res) {
                    if(res.success) {
                        $('body').trigger('update_checkout');
                        $(document.body).trigger('wc_update_cart'); 
                    }
                });
            });
        });
        </script>
        <?php
    }
}
new MC_Points_Checkout();