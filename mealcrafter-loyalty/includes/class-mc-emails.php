<?php
/**
 * MealCrafter: Email Engine & Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Emails {

    // Used to temporarily store the Order ID so shortcodes work inside Woo Emails
    private static $current_email_order_id = 0;

    public function __construct() {
        
        // Shortcodes for Custom Email Builders (YayMail, Kadence, etc.)
        add_shortcode('mc_points_earned_this_order', [$this, 'shortcode_earned_this_order']);
        add_shortcode('mc_points_total_balance', [$this, 'shortcode_total_balance']);

        // Auto-Inject into standard WooCommerce Emails
        add_action( 'woocommerce_email_before_order_table', [$this, 'capture_email_order_id'], 1, 4 );
        add_action( 'woocommerce_email_after_order_table', [$this, 'inject_into_woo_receipt'], 10, 4 );

        // Custom Standalone Email Hooks
        add_action( 'mc_points_earned_event', [$this, 'send_earned_email'], 10, 3 );
        add_action( 'mc_reward_unlocked_event', [$this, 'send_reward_unlocked_email'], 10, 4 );
        add_action( 'mc_points_manual_update_event', [$this, 'send_admin_update_email'], 10, 4 );

        // Daily Expiration Cron Job
        add_action('mc_daily_expiration_check', [$this, 'cron_check_expiring_points']);
        if (!wp_next_scheduled('mc_daily_expiration_check')) {
            wp_schedule_event(time(), 'daily', 'mc_daily_expiration_check');
        }
    }

    /* --------------------------------------------------------
     * 1. WOOCOMMERCE INJECTOR & SHORTCODES
     * -------------------------------------------------------- */

    public function capture_email_order_id($order, $sent_to_admin, $plain_text, $email) {
        if ( $order ) {
            self::$current_email_order_id = $order->get_id();
        }
    }

    public function inject_into_woo_receipt($order, $sent_to_admin, $plain_text, $email) {
        if ( $sent_to_admin || ! $order ) return;
        
        // Only inject on processing or completed emails to avoid spamming "Order On Hold" emails
        if ( ! in_array( $email->id, ['customer_processing_order', 'customer_completed_order'] ) ) return;
        
        if ( get_option('mc_email_woo_inject_enable', 'yes') !== 'yes' ) return;

        $user_id = $order->get_user_id();
        if ( !$user_id ) return;

        // See if points were actually earned on this order
        $points_earned = (float) $order->get_meta('_mc_points_earned');
        if ( $points_earned <= 0 ) return;

        $total_points = class_exists('MC_Points_Account') ? \MC_Points_Account::get_accurate_user_points($user_id) : get_user_meta($user_id, 'mc_points', true);

        $body = get_option('mc_email_woo_inject_body', "<div style='background:#fef8ee; padding:15px; border:2px dashed #f39c12; text-align:center; font-weight:bold; color:#d35400;'>🎉 You earned {points_earned} points on this order! Your new balance is {total_points} points.</div>");
        
        $body = str_replace('{points_earned}', number_format($points_earned), $body);
        $body = str_replace('{total_points}', number_format((float)$total_points), $body);
        
        echo wp_kses_post($body) . '<br><br>';
    }

    public function shortcode_earned_this_order($atts) {
        if ( !self::$current_email_order_id ) return '0';
        $order = wc_get_order(self::$current_email_order_id);
        if (!$order) return '0';
        return number_format((float)$order->get_meta('_mc_points_earned'));
    }

    public function shortcode_total_balance($atts) {
        $user_id = 0;
        if ( self::$current_email_order_id ) {
            $order = wc_get_order(self::$current_email_order_id);
            if ($order) $user_id = $order->get_user_id();
        }
        if (!$user_id) $user_id = get_current_user_id();
        if (!$user_id) return '0';
        
        $bal = class_exists('MC_Points_Account') ? \MC_Points_Account::get_accurate_user_points($user_id) : get_user_meta($user_id, 'mc_points', true);
        return number_format((float)$bal);
    }

    /* --------------------------------------------------------
     * 2. STANDALONE EMAIL SENDERS
     * -------------------------------------------------------- */

    private function get_html_headers() {
        return array('Content-Type: text/html; charset=UTF-8');
    }

    public function send_earned_email( $user_id, $points_earned, $new_total ) {
        if ( get_option('mc_email_earned_enable', 'yes') !== 'yes' ) return;
        $user = get_userdata($user_id);
        if ( !$user ) return;

        $subject = get_option('mc_email_earned_subject', 'You just earned {points_earned} points!');
        $body = get_option('mc_email_earned_body', "Hi {first_name}, you earned {points_earned} points. Total: {total_points}");

        $subject = str_replace('{points_earned}', number_format($points_earned), $subject);
        
        $body = str_replace('{first_name}', $user->first_name ?: $user->display_name, $body);
        $body = str_replace('{points_earned}', number_format($points_earned), $body);
        $body = str_replace('{total_points}', number_format($new_total), $body);

        wp_mail( $user->user_email, wp_strip_all_tags($subject), wpautop($body), $this->get_html_headers() );
    }

    public function send_reward_unlocked_email( $user_id, $points_spent, $coupon_code, $new_total ) {
        if ( get_option('mc_email_reward_enable', 'yes') !== 'yes' ) return;
        $user = get_userdata($user_id);
        if ( !$user ) return;

        $subject = get_option('mc_email_reward_subject', 'Here is your MealCrafter Reward!');
        $body = get_option('mc_email_reward_body', "Hi {first_name}, your code is {coupon_code}.");

        $body = str_replace('{first_name}', $user->first_name ?: $user->display_name, $body);
        $body = str_replace('{points_spent}', number_format($points_spent), $body);
        $body = str_replace('{coupon_code}', '<strong>' . $coupon_code . '</strong>', $body);
        $body = str_replace('{total_points}', number_format($new_total), $body);

        wp_mail( $user->user_email, wp_strip_all_tags($subject), wpautop($body), $this->get_html_headers() );
    }

    public function send_admin_update_email( $user_id, $diff, $reason, $new_total ) {
        if ( get_option('mc_email_updated_enable', 'yes') !== 'yes' ) return;
        $user = get_userdata($user_id);
        if ( !$user ) return;

        $subject = get_option('mc_email_updated_subject', 'Your point balance has been updated');
        $body = get_option('mc_email_updated_body', "Hi {first_name}, adjustment: {point_difference}. Reason: {update_reason}.");

        $formatted_diff = ($diff > 0) ? '+' . number_format($diff) : number_format($diff);

        $body = str_replace('{first_name}', $user->first_name ?: $user->display_name, $body);
        $body = str_replace('{point_difference}', $formatted_diff, $body);
        $body = str_replace('{update_reason}', $reason, $body);
        $body = str_replace('{total_points}', number_format($new_total), $body);

        wp_mail( $user->user_email, wp_strip_all_tags($subject), wpautop($body), $this->get_html_headers() );
    }

    /* --------------------------------------------------------
     * 3. EXPIRATION CRON JOB
     * -------------------------------------------------------- */

    public function cron_check_expiring_points() {
        if ( get_option('mc_email_expiring_enable', 'yes') !== 'yes' ) return;
        
        // This function will scan the database for expiring points based on the timeframes set in options.
        // It requires the Expiration module to be fully built and storing expiration dates in user meta!
        // (Placeholder hook for when the expiration logic is tied together).
    }
}