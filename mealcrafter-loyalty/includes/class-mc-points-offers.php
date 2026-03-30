<?php
/**
 * MealCrafter: Loyalty Offers & Coupons Engine
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'MC_Points_Offers' ) ) {

    class MC_Points_Offers {

        public function __construct() {
            add_action( 'woocommerce_coupon_options', [$this, 'add_general_coupon_fields'], 10, 2 );
            add_action( 'woocommerce_coupon_options_save', [$this, 'save_reward_coupon_meta'], 10, 2 );

            // AJAX for unlocking points
            add_action( 'wp_ajax_mc_unlock_offer', [$this, 'ajax_unlock_offer'] );
            
            // NEW: AJAX for applying free promos to the cart
            add_action( 'wp_ajax_mc_apply_promo', [$this, 'ajax_apply_promo'] );
            add_action( 'wp_ajax_nopriv_mc_apply_promo', [$this, 'ajax_apply_promo'] ); // Allows guests to use it too!
        }

        public function add_general_coupon_fields( $coupon_id, $coupon ) {
            echo '<div class="options_group" style="background:#fef8ee; border-left:4px solid #f39c12; margin-top:15px; margin-bottom:15px;">';
            
            woocommerce_wp_checkbox([
                'id'          => '_mc_is_reward_offer',
                'label'       => 'Enable as Loyalty Reward Offer',
                'description' => 'Check this box to allow customers to unlock this coupon using loyalty points.'
            ]);
            
            echo '<div id="mc_reward_offer_settings" style="padding: 15px 15px 15px 25px; border-top: 1px dashed #f6c064; margin-top: 10px; display: none;">';
            echo '<h4 style="margin-top:0; color:#d35400;">Reward Offer Details</h4>';
            
            woocommerce_wp_text_input(['id' => '_mc_offer_point_cost', 'label' => 'Point Cost', 'type' => 'number', 'description' => 'Points required to unlock this offer.']);
            woocommerce_wp_text_input(['id' => '_mc_offer_title', 'label' => 'Elementor Offer Title', 'description' => 'e.g., $10 Off Your Next Order']);
            woocommerce_wp_text_input(['id' => '_mc_offer_subtitle', 'label' => 'Elementor Subtitle']);
            woocommerce_wp_text_input(['id' => '_mc_offer_image_url', 'label' => 'Image URL', 'description' => 'Paste an image URL to show in the Elementor block.']);
            woocommerce_wp_text_input(['id' => '_mc_offer_days_valid', 'label' => 'Rolling Expiration (Days)', 'type' => 'number', 'description' => 'Number of days the code is valid after the user unlocks it.']);
            woocommerce_wp_text_input(['id' => '_mc_offer_max_unlocks_user', 'label' => 'Max Unlocks Per User', 'type' => 'number']);
            woocommerce_wp_text_input(['id' => '_mc_offer_max_unlocks_global', 'label' => 'Global Inventory Limits', 'type' => 'number']);

            echo '</div></div>';
            
            ?>
            <script>
            jQuery(document).ready(function($) {
                function mcToggleRewardSettings() {
                    if ($('#_mc_is_reward_offer').is(':checked')) { $('#mc_reward_offer_settings').slideDown(); } 
                    else { $('#mc_reward_offer_settings').slideUp(); }
                }
                $('#_mc_is_reward_offer').on('change', mcToggleRewardSettings);
                mcToggleRewardSettings();
            });
            </script>
            <?php
        }

        public function save_reward_coupon_meta( $post_id, $coupon ) {
            $is_reward = isset( $_POST['_mc_is_reward_offer'] ) ? 'yes' : 'no';
            $coupon->update_meta_data( '_mc_is_reward_offer', $is_reward );
            
            if ( $is_reward === 'yes' ) {
                $fields = ['_mc_offer_point_cost', '_mc_offer_title', '_mc_offer_subtitle', '_mc_offer_image_url', '_mc_offer_days_valid', '_mc_offer_max_unlocks_user', '_mc_offer_max_unlocks_global'];
                foreach($fields as $f) {
                    if ( isset($_POST[$f]) ) { $coupon->update_meta_data( $f, sanitize_text_field( $_POST[$f] ) ); }
                }
            }
            $coupon->save_meta_data();
        }

        public function ajax_unlock_offer() {
            if ( ! is_user_logged_in() ) wp_send_json_error(['message' => 'Please log in to unlock rewards.']);
            
            $coupon_id = isset($_POST['coupon_id']) ? intval($_POST['coupon_id']) : 0;
            if ( !$coupon_id ) wp_send_json_error(['message' => 'Invalid offer.']);
            
            $coupon = new WC_Coupon($coupon_id);
            if ( !$coupon->get_id() || $coupon->get_meta('_mc_is_reward_offer') !== 'yes' ) {
                wp_send_json_error(['message' => 'Offer no longer available.']);
            }
            
            $cost = (int) $coupon->get_meta('_mc_offer_point_cost');
            $user_id = get_current_user_id();
            $balance = class_exists('MC_Points_Account') ? MC_Points_Account::get_accurate_user_points($user_id) : 0;
            
            if ( $balance < $cost ) wp_send_json_error(['message' => 'You do not have enough points.']);
            
            $user_unlocks = (int) get_user_meta($user_id, '_mc_unlocked_offer_' . $coupon_id, true);
            $max_user = $coupon->get_meta('_mc_offer_max_unlocks_user');
            if ( $max_user !== '' && $user_unlocks >= (int) $max_user ) wp_send_json_error(['message' => 'You reached the claim limit.']);
            
            $global_unlocks = (int) $coupon->get_meta('_mc_global_unlocks_count');
            $max_global = $coupon->get_meta('_mc_offer_max_unlocks_global');
            if ( $max_global !== '' && $global_unlocks >= (int) $max_global ) wp_send_json_error(['message' => 'This reward is sold out.']);
            
            if ( function_exists('mc_update_user_points') ) {
                mc_update_user_points($user_id, -$cost, 'adjusted', 'Unlocked Reward: ' . $coupon->get_code());
            }
            
            $new_balance = max(0, $balance - $cost);
            update_user_meta( $user_id, '_mc_user_points', $new_balance );
            update_user_meta( $user_id, 'mc_points', $new_balance );
            
            $history = get_user_meta($user_id, '_mc_points_history', true);
            if (!is_array($history)) $history = [];
            array_unshift($history, ['id' => uniqid(), 'date' => current_time('timestamp'), 'reason' => 'Unlocked Reward: ' . $coupon->get_code(), 'order' => '-', 'diff' => -$cost, 'balance' => $new_balance]);
            update_user_meta($user_id, '_mc_points_history', array_slice($history, 0, 200));

            $new_code = 'MC-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
            $new_coupon = new WC_Coupon();
            $new_coupon->set_code($new_code);
            $new_coupon->set_discount_type($coupon->get_discount_type());
            $new_coupon->set_amount($coupon->get_amount());
            $new_coupon->set_individual_use($coupon->get_individual_use());
            $new_coupon->set_product_ids($coupon->get_product_ids());
            $new_coupon->set_exclude_product_ids($coupon->get_exclude_product_ids());
            $new_coupon->set_usage_limit(1);
            $new_coupon->set_email_restrictions([wp_get_current_user()->user_email]);
            
            $days_valid = $coupon->get_meta('_mc_offer_days_valid');
            if ( $days_valid !== '' && (int) $days_valid > 0 ) {
                $new_coupon->set_date_expires( strtotime('+' . (int)$days_valid . ' days') );
            }
            
            $new_coupon->save();
            $new_coupon->add_meta_data('_mc_is_cloned_reward', 'yes');
            $new_coupon->add_meta_data('_mc_parent_reward', $coupon_id);
            $new_coupon->save_meta_data();

            update_user_meta($user_id, '_mc_unlocked_offer_' . $coupon_id, $user_unlocks + 1);
            $coupon->update_meta_data('_mc_global_unlocks_count', $global_unlocks + 1);
            $coupon->save_meta_data();

            // INSTANTLY APPLY TO CART IF POSSIBLE
            if ( null === WC()->session ) { WC()->session = new WC_Session_Handler(); WC()->session->init(); }
            if ( null === WC()->cart ) { WC()->cart = new WC_Cart(); }
            WC()->cart->add_discount( $new_code );

            wp_send_json_success(['code' => $new_code, 'message' => 'Success!']);
        }

        // NEW: Handles clicking a free promo code
        public function ajax_apply_promo() {
            $code = isset($_POST['coupon_code']) ? sanitize_text_field($_POST['coupon_code']) : '';
            if ( empty($code) ) wp_send_json_error(['message' => 'Invalid code.']);

            if ( null === WC()->session ) { WC()->session = new WC_Session_Handler(); WC()->session->init(); }
            if ( null === WC()->customer ) { WC()->customer = new WC_Customer( get_current_user_id(), true ); }
            if ( null === WC()->cart ) { WC()->cart = new WC_Cart(); }

            if ( WC()->cart->has_discount( $code ) ) {
                wp_send_json_success(['message' => 'Already applied!']);
            }

            $result = WC()->cart->add_discount( $code );
            
            if ( $result ) {
                wp_send_json_success(['message' => 'Applied to cart!']);
            } else {
                wp_send_json_error(['message' => 'Cart does not meet requirements for this promo.']);
            }
        }
    }
    
    new MC_Points_Offers();
}