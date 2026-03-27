<?php
/**
 * MealCrafter: Points Expiration Engine
 * Handles the background cron job to expire old points
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Points_Expiration {

    public function __construct() {
        // Register the cron job schedule
        add_action( 'wp', [$this, 'schedule_expiration_cron'] );
        
        // Hook the cron event to our processing function
        add_action( 'mc_daily_points_expiration_check', [$this, 'process_expired_points'] );

        // Admin tool to run it manually (for testing)
        add_action( 'admin_post_mc_force_run_expiration', [$this, 'manual_force_run'] );
    }

    /**
     * Ensures the daily cron job is scheduled in WordPress
     */
    public function schedule_expiration_cron() {
        if ( ! wp_next_scheduled( 'mc_daily_points_expiration_check' ) ) {
            // Schedule to run daily at midnight
            wp_schedule_event( strtotime('midnight'), 'daily', 'mc_daily_points_expiration_check' );
        }
    }

    /**
     * The actual engine that runs every night to clear expired points
     */
    public function process_expired_points() {
        // 1. Check if expiration is even enabled in settings
        if ( get_option('mc_pts_expiration_enabled', 'no') !== 'yes' ) {
            return; // Expiration is turned off.
        }

        $exp_time = (int) get_option('mc_pts_expiration_time', '365');
        $exp_type = get_option('mc_pts_expiration_type', 'days'); // days, months, years
        
        if ( $exp_time <= 0 ) return;

        // 2. Calculate the "Threshold Date" (Any points earned BEFORE this date are expired)
        $threshold_timestamp = strtotime( "-{$exp_time} {$exp_type}" );

        // 3. Query all users who actually have points
        $args = [
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key'     => '_mc_user_points',
                    'value'   => 0,
                    'compare' => '>'
                ],
                [
                    'key'     => 'mc_points',
                    'value'   => 0,
                    'compare' => '>'
                ]
            ],
            'fields' => 'ID'
        ];
        
        $users_with_points = get_users( $args );

        if ( empty($users_with_points) ) return;

        // 4. Iterate through each user and evaluate their history log
        foreach ( $users_with_points as $user_id ) {
            
            // We use the same secure balance fetcher we use everywhere else
            $pts_n = get_user_meta($user_id, '_mc_user_points', true);
            $pts_o = get_user_meta($user_id, 'mc_points', true);
            $current_balance = ($pts_n !== '') ? (int)$pts_n : (int)$pts_o;

            if ( $current_balance <= 0 ) continue; // Nothing to expire

            $history = get_user_meta( $user_id, '_mc_points_history', true );
            if ( ! is_array($history) || empty($history) ) continue;

            $points_to_expire = 0;
            $total_earned_ever = 0;
            $total_spent_ever = 0;

            // First, calculate their true "Earned vs Spent" ratio
            foreach ( $history as $log ) {
                if ( $log['diff'] > 0 ) {
                    $total_earned_ever += $log['diff'];
                    
                    // If this specific chunk of earned points is older than the threshold, 
                    // flag it as potentially expiring.
                    if ( $log['date'] < $threshold_timestamp ) {
                        $points_to_expire += $log['diff'];
                    }
                } elseif ( $log['diff'] < 0 ) {
                    // This includes redemptions, manual deductions, and previous expirations
                    $total_spent_ever += abs($log['diff']);
                }
            }

            // FIF0 Logic (First In, First Out)
            // If the user has spent points over their lifetime, we assume they spent the OLDEST points first.
            // We subtract their total lifetime spend from the "expiring" pile.
            $actual_points_expiring = max( 0, $points_to_expire - $total_spent_ever );

            // Failsafe: You can never expire more points than the user currently has in their active balance.
            $actual_points_expiring = min( $actual_points_expiring, $current_balance );

            // 5. If points need to expire, deduct them and log it!
            if ( $actual_points_expiring > 0 ) {
                $new_balance = $current_balance - $actual_points_expiring;

                // Update Database
                update_user_meta( $user_id, '_mc_user_points', $new_balance );
                update_user_meta( $user_id, 'mc_points', $new_balance );

                // Add to History Log
                $log_entry = [
                    'id'      => uniqid(),
                    'date'    => current_time('timestamp'),
                    'reason'  => 'Points Expired',
                    'order'   => '-',
                    'diff'    => -$actual_points_expiring,
                    'balance' => $new_balance
                ];
                
                array_unshift( $history, $log_entry );
                $history = array_slice( $history, 0, 200 ); // Keep history clean
                update_user_meta( $user_id, '_mc_points_history', $history );
            }
        }
    }

    /**
     * Allows shop managers to force the cron job to run immediately via a button
     */
    public function manual_force_run() {
        if ( ! current_user_can('manage_options') ) {
            wp_die('Unauthorized access.');
        }

        $this->process_expired_points();

        wp_redirect( add_query_arg( ['page' => 'mc-loyalty-settings', 'tab' => 'options', 'msg' => 'expiration_run'], admin_url('admin.php') ) );
        exit;
    }
}

new MC_Points_Expiration();