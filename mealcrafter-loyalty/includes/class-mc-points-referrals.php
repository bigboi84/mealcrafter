<?php
/**
 * MealCrafter: Advanced Referral Engine
 * Handles code generation, URL tracking, Fraud Prevention, and Application workflows.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Referrals {

    public function __construct() {
        if ( get_option('mc_ref_enable', 'no') !== 'yes' ) return;

        // Create Custom Role on Init if enabled
        add_action( 'init', [$this, 'maybe_create_custom_role'] );

        // URL Tracking & Checkout Injection
        add_action( 'init', [$this, 'track_referral_url'] );
        add_action( 'woocommerce_register_form', [$this, 'add_referral_input_to_registration'] );
        
        // Awarding Hooks
        add_action( 'user_register', [$this, 'process_new_user_referral'] );
        add_action( 'woocommerce_order_status_completed', [$this, 'process_referrer_purchase_bonus'], 10, 1 );

        // Application AJAX
        add_action( 'wp_ajax_mc_apply_referral', [$this, 'ajax_apply_referral'] );

        // Admin User Profile Management (Approve/Ban)
        add_action( 'show_user_profile', [$this, 'admin_user_profile_fields'] );
        add_action( 'edit_user_profile', [$this, 'admin_user_profile_fields'] );
        add_action( 'personal_options_update', [$this, 'save_admin_user_profile_fields'] );
        add_action( 'edit_user_profile_update', [$this, 'save_admin_user_profile_fields'] );
    }

    public function maybe_create_custom_role() {
        if ( get_option('mc_ref_custom_role_enable', 'no') === 'yes' ) {
            $role_name = get_option('mc_ref_custom_role_name', '');
            if ( !empty($role_name) ) {
                $role_slug = sanitize_title($role_name);
                if ( ! wp_roles()->is_role( $role_slug ) ) {
                    $customer_role = get_role( 'customer' );
                    $caps = $customer_role ? $customer_role->capabilities : [];
                    add_role( $role_slug, sanitize_text_field($role_name), $caps );
                }
            }
        }
    }

    /**
     * Generates a unique customized code for a user if they don't have one.
     */
    public static function get_user_referral_code( $user_id ) {
        $code = get_user_meta( $user_id, '_mc_ref_code', true );
        if ( empty($code) ) {
            // Pull the custom prefix from settings, fallback to MC
            $prefix = strtoupper( sanitize_text_field( get_option('mc_ref_prefix', 'MC') ) );
            $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix); // Keep it clean
            if ( empty($prefix) ) $prefix = 'MC';

            $code = $prefix . '-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 5));
            update_user_meta( $user_id, '_mc_ref_code', $code );
            
            // Save reverse lookup
            update_option( '_mc_ref_lookup_' . $code, $user_id );
        }
        return $code;
    }

    public function track_referral_url() {
        if ( isset($_GET['ref']) && !is_user_logged_in() ) {
            $code = sanitize_text_field($_GET['ref']);
            setcookie( 'mc_ref_code', $code, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN );
        }
    }

    public function add_referral_input_to_registration() {
        $saved_code = isset($_COOKIE['mc_ref_code']) ? sanitize_text_field($_COOKIE['mc_ref_code']) : '';
        
        $prefix = strtoupper( sanitize_text_field( get_option('mc_ref_prefix', 'MC') ) );
        $prefix = preg_replace('/[^A-Z0-9]/', '', $prefix);
        if ( empty($prefix) ) $prefix = 'MC';

        ?>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="mc_reg_ref_code">Referral Code (Optional)</label>
            <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="mc_reg_ref_code" id="mc_reg_ref_code" value="<?php echo esc_attr($saved_code); ?>" placeholder="e.g. <?php echo esc_attr($prefix); ?>-XXXXX" />
        </p>
        <?php
    }

    public function process_new_user_referral( $new_user_id ) {
        $code = isset($_POST['mc_reg_ref_code']) ? sanitize_text_field($_POST['mc_reg_ref_code']) : '';
        if ( empty($code) && isset($_COOKIE['mc_ref_code']) ) {
            $code = sanitize_text_field($_COOKIE['mc_ref_code']);
        }

        if ( empty($code) ) return;

        $referrer_id = get_option( '_mc_ref_lookup_' . strtoupper($code) );
        if ( !$referrer_id || $referrer_id == $new_user_id ) return; // Invalid code or self

        // Fraud Check
        if ( get_option('mc_ref_fraud_ip', 'yes') === 'yes' ) {
            $referrer_ip = get_user_meta( $referrer_id, '_mc_last_ip', true );
            $new_ip = $_SERVER['REMOTE_ADDR'] ?? '';
            if ( $referrer_ip === $new_ip && !empty($new_ip) ) {
                return; // IP Match detected. Block reward.
            }
        }
        update_user_meta( $new_user_id, '_mc_last_ip', $_SERVER['REMOTE_ADDR'] ?? '' );

        // Stamp relationship
        update_user_meta( $new_user_id, '_mc_referred_by', $referrer_id );

        // Award Referee (The Friend)
        $referee_pts = (int) get_option('mc_ref_referee_pts', '50');
        if ( $referee_pts > 0 && function_exists('mc_update_user_points') ) {
            mc_update_user_points( $new_user_id, $referee_pts, 'earned', 'Friend Signup Bonus' );
            $this->sync_points($new_user_id, $referee_pts, 'Friend Signup Bonus');
        }

        // Increment Referrer's Stats
        $count = (int) get_user_meta( $referrer_id, '_mc_ref_count', true );
        update_user_meta( $referrer_id, '_mc_ref_count', $count + 1 );
    }

    public function process_referrer_purchase_bonus( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( !$order ) return;
        
        $user_id = $order->get_user_id();
        if ( !$user_id ) return;

        $referrer_id = get_user_meta( $user_id, '_mc_referred_by', true );
        $already_rewarded = get_user_meta( $user_id, '_mc_referral_purchase_rewarded', true );

        if ( $referrer_id && !$already_rewarded ) {
            // Ensure this isn't a banned referrer
            $status = get_user_meta( $referrer_id, '_mc_referral_status', true );
            if ( $status === 'banned' ) return;

            $referrer_pts = (int) get_option('mc_ref_referrer_pts', '50');
            if ( $referrer_pts > 0 && function_exists('mc_update_user_points') ) {
                mc_update_user_points( $referrer_id, $referrer_pts, 'earned', 'Referral First Purchase Bonus' );
                $this->sync_points($referrer_id, $referrer_pts, 'Referral First Purchase Bonus');
                
                // Track points earned via referrals
                $earned_so_far = (int) get_user_meta( $referrer_id, '_mc_ref_points_earned', true );
                update_user_meta( $referrer_id, '_mc_ref_points_earned', $earned_so_far + $referrer_pts );
            }
            update_user_meta( $user_id, '_mc_referral_purchase_rewarded', 'yes' );
        }
    }

    public function ajax_apply_referral() {
        $user_id = get_current_user_id();
        if ( !$user_id ) wp_send_json_error();

        update_user_meta( $user_id, '_mc_referral_status', 'pending' );

        $admin_email = get_option('mc_ref_admin_email', get_option('admin_email'));
        $user_info = get_userdata($user_id);
        $edit_link = admin_url("user-edit.php?user_id=" . $user_id . "#mc-referral-mgmt");

        $subject = "New Referral Program Application: " . $user_info->user_email;
        $message = "A user has applied to join the MealCrafter Referral Program.\n\nUser: " . $user_info->display_name . "\nEmail: " . $user_info->user_email . "\n\nClick here to review and approve:\n" . $edit_link;
        wp_mail( $admin_email, $subject, $message );

        wp_send_json_success();
    }

    public function admin_user_profile_fields( $user ) {
        if ( ! current_user_can( 'manage_options' ) ) return;
        
        $status = get_user_meta( $user->ID, '_mc_referral_status', true );
        if ( empty($status) ) $status = 'none';

        ?>
        <h2 id="mc-referral-mgmt">MealCrafter: Referral Management</h2>
        <table class="form-table">
            <tr>
                <th><label for="mc_referral_status">Referral Program Status</label></th>
                <td>
                    <select name="mc_referral_status" id="mc_referral_status">
                        <option value="none" <?php selected($status, 'none'); ?>>Standard (No Action)</option>
                        <option value="pending" <?php selected($status, 'pending'); ?>>Pending Application</option>
                        <option value="approved" <?php selected($status, 'approved'); ?>>Approved (Active Ambassador)</option>
                        <option value="banned" <?php selected($status, 'banned'); ?>>Banned (Blocked from earning)</option>
                    </select>
                    <p class="description">Manage this user's ability to generate codes and earn referral points.</p>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_admin_user_profile_fields( $user_id ) {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( isset( $_POST['mc_referral_status'] ) ) {
            $new_status = sanitize_text_field($_POST['mc_referral_status']);
            update_user_meta( $user_id, '_mc_referral_status', $new_status );

            // Dynamically assign or remove custom role based on approval
            if ( get_option('mc_ref_custom_role_enable', 'no') === 'yes' ) {
                $role_name = get_option('mc_ref_custom_role_name', '');
                if ( !empty($role_name) ) {
                    $role_slug = sanitize_title($role_name);
                    $user_obj = new WP_User( $user_id );
                    if ( $new_status === 'approved' ) {
                        $user_obj->add_role( $role_slug );
                    } else {
                        $user_obj->remove_role( $role_slug );
                    }
                }
            }
        }
    }

    private function sync_points($user_id, $points, $reason) {
        $pts_n = get_user_meta($user_id, '_mc_user_points', true);
        $pts_o = get_user_meta($user_id, 'mc_points', true);
        $current_balance = ($pts_n !== '') ? (int)$pts_n : (int)$pts_o;
        $new_balance = max(0, $current_balance + $points);

        update_user_meta( $user_id, '_mc_user_points', $new_balance );
        update_user_meta( $user_id, 'mc_points', $new_balance );

        $history = get_user_meta($user_id, '_mc_points_history', true);
        if (!is_array($history)) $history = [];
        array_unshift($history, [
            'id' => uniqid(), 'date' => current_time('timestamp'), 'reason' => $reason, 'order' => '-', 'diff' => $points, 'balance' => $new_balance
        ]);
        update_user_meta($user_id, '_mc_points_history', array_slice($history, 0, 200));
    }
}
new MC_Points_Referrals();