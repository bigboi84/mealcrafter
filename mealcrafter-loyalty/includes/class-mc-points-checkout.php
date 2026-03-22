<?php
/**
 * MealCrafter: Loyalty Checkout & Cart Logic
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Checkout {

    public function __construct() {
        // 1. The Smart Trigger Engine: Watch the cart and Add/Remove gifts
        add_action( 'template_redirect', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_add_to_cart', [$this, 'process_smart_triggers'] );
        add_action( 'woocommerce_cart_item_removed', [$this, 'process_smart_triggers'] );
        
        // 2. Force the gift prices to $0.00
        add_action( 'woocommerce_before_calculate_totals', [$this, 'set_giveaway_prices_to_zero'], 9999 );
        
        // 3. Identify the item as a "Free Gift" in the cart UI
        add_filter( 'woocommerce_cart_item_name', [$this, 'add_giveaway_badge_to_cart_name'], 10, 3 );

        // 4. APP FEATURE: Remove the User Meta flag ONLY after the order is officially placed
        add_action( 'woocommerce_checkout_order_processed', [$this, 'consume_app_task_flags'], 10, 1 );
    }

    /**
     * The Master Cart Listener for Smart Triggers
     */
    public function process_smart_triggers() {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( ! WC()->cart || WC()->cart->is_empty() ) return;

        // Prevent infinite loops if adding a gift triggers this function again
        static $is_processing = false;
        if ( $is_processing ) return;
        $is_processing = true;

        $giveaways = get_option('mc_pts_auto_giveaways', []);
        if ( empty($giveaways) ) {
            $is_processing = false;
            return;
        }

        $cart_total = 0;
        $gift_ids_in_cart = [];
        $bogo_products_in_cart = [];

        // 1. Scan the cart
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product_id = $cart_item['product_id'];
            
            if ( isset($cart_item['_mc_is_giveaway']) ) {
                $gift_ids_in_cart[$product_id] = $cart_item_key;
            } else {
                $cart_total += ($cart_item['data']->get_price() * $cart_item['quantity']); 
                $bogo_products_in_cart[] = $product_id;
            }
        }

        $eligible_gifts = [];
        $user_id = get_current_user_id();
        $order_count = $user_id ? wc_get_customer_order_count( $user_id ) : 0;
        
        $highest_spend_threshold = -1;
        $spend_gift = null;

        // 2. Evaluate all active Smart Triggers
        foreach ($giveaways as $g) {
            if (!isset($g['active']) || $g['active'] !== 'yes' || empty($g['gift_id'])) continue;
            
            $type = $g['trigger_type'] ?? 'spend';
            $gift_id = (int) $g['gift_id'];
            
            // Trigger 1: Spend Milestone (Only take the highest one)
            if ($type === 'spend') {
                $threshold = (float) $g['threshold'];
                if ($cart_total >= $threshold && $threshold > $highest_spend_threshold) {
                    $highest_spend_threshold = $threshold;
                    $spend_gift = $g;
                }
            } 
            // Trigger 2: First-Time App/Web Order
            elseif ($type === 'first_order') {
                if ($order_count === 0 && is_user_logged_in()) {
                    $eligible_gifts[$gift_id] = $g;
                }
            }
            // Trigger 3: App Task Unlock (User Meta Flag)
            elseif ($type === 'user_meta') {
                if (is_user_logged_in() && !empty($g['meta_key'])) {
                    $has_meta = get_user_meta($user_id, sanitize_text_field($g['meta_key']), true);
                    if ($has_meta) {
                        $eligible_gifts[$gift_id] = $g;
                    }
                }
            }
            // Trigger 4: BOGO / Specific Product
            elseif ($type === 'bogo') {
                $req_id = (int) $g['req_product'];
                if (in_array($req_id, $bogo_products_in_cart)) {
                    $eligible_gifts[$gift_id] = $g;
                }
            }
        }

        if ($spend_gift) {
            $eligible_gifts[(int)$spend_gift['gift_id']] = $spend_gift;
        }

        // 3. APPLY LOGIC: Add newly eligible gifts
        foreach ($eligible_gifts as $gift_id => $g) {
            if (!isset($gift_ids_in_cart[$gift_id])) {
                WC()->cart->add_to_cart($gift_id, 1, 0, [], [
                    '_mc_is_giveaway' => true,
                    '_mc_giveaway_msg' => $g['msg'],
                    '_mc_trigger_meta_key' => ($g['trigger_type'] === 'user_meta') ? sanitize_text_field($g['meta_key']) : ''
                ]);
                
                $msg = !empty($g['msg']) ? $g['msg'] : 'Congrats! You unlocked a free gift!';
                if (!defined('DOING_AJAX')) { wc_add_notice( esc_html($msg), 'success' ); }
            }
        }

        // 4. REVERT LOGIC: Remove gifts the user no longer qualifies for
        foreach ($gift_ids_in_cart as $gift_id => $cart_key) {
            if (!isset($eligible_gifts[$gift_id])) {
                WC()->cart->remove_cart_item($cart_key);
                if (!defined('DOING_AJAX')) { wc_add_notice( 'A free gift was removed from your cart as the requirements are no longer met.', 'notice' ); }
            }
        }

        $is_processing = false;
    }

    /**
     * Forces the targeted item's price to $0.00
     */
    public function set_giveaway_prices_to_zero( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
        if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

        foreach ( $cart->get_cart() as $cart_item ) {
            if ( isset( $cart_item['_mc_is_giveaway'] ) && $cart_item['_mc_is_giveaway'] === true ) {
                $cart_item['data']->set_price( 0 );
            }
        }
    }

    /**
     * Consumes the "App Task" flag ONLY when the order is successfully placed
     */
    public function consume_app_task_flags( $order_id ) {
        $order = wc_get_order($order_id);
        if (!$order) return;
        
        $customer_id = $order->get_customer_id();
        if (!$customer_id) return;

        foreach ($order->get_items() as $item) {
            $meta_key = $item->get_meta('_mc_trigger_meta_key');
            if (!empty($meta_key)) {
                // Delete the flag so the user can't claim the app reward a second time
                delete_user_meta($customer_id, $meta_key);
            }
        }
    }

    /**
     * Adds a visual "FREE GIFT" badge next to the item name in the cart
     */
    public function add_giveaway_badge_to_cart_name( $item_name, $cart_item, $cart_item_key ) {
        if ( isset( $cart_item['_mc_is_giveaway'] ) && $cart_item['_mc_is_giveaway'] === true ) {
            $badge = '<span style="background:#e74c3c; color:#fff; font-size:10px; padding:3px 8px; border-radius:12px; margin-left:8px; vertical-align:middle; font-weight:bold; letter-spacing:0.5px; box-shadow:0 2px 4px rgba(231,76,60,0.3);">FREE GIFT</span>';
            return $item_name . $badge;
        }
        return $item_name;
    }
}

new MC_Points_Checkout();