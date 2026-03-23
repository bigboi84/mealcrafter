<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Process Clear History (NEW: Now resets the Lifetime Odometer too!)
if ( isset($_POST['mc_clear_history']) && isset($_POST['mc_customer_id']) && current_user_can('manage_options') ) {
    $uid = intval($_POST['mc_customer_id']);
    
    // Clear the visual history logs
    update_user_meta($uid, '_mc_points_history', []);
    
    // Factory Reset the "Total Collected" odometer to match their current active balance
    $current_active_pts = max((int)get_user_meta($uid, '_mc_user_points', true), (int)get_user_meta($uid, 'mc_points', true));
    update_user_meta($uid, '_mc_lifetime_points', $current_active_pts);
    
    echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-bottom:20px; border-radius:4px;">✅ Points history cleared and Lifetime Total reset for user.</div>';
}

// 2. Process Adjustments & Logging
if ( isset($_POST['mc_submit_adjustment']) && isset($_POST['mc_adjust_pts_field']) && wp_verify_nonce($_POST['mc_adjust_pts_field'], 'mc_adjust_pts_nonce') && current_user_can('manage_options') ) {
    $user_id = intval($_POST['mc_customer_id']);
    $type    = sanitize_text_field($_POST['mc_adjust_type']);
    $amount  = intval($_POST['mc_adjust_amount'] ?? 0);
    $reason  = sanitize_text_field($_POST['mc_adjust_reason']);
    $banned  = sanitize_text_field($_POST['mc_adjust_banned'] ?? 'no');
    
    $adj_date = !empty($_POST['mc_adjust_date']) ? strtotime($_POST['mc_adjust_date']) : current_time('timestamp');

    if ( $user_id > 0 ) {
        $messages = [];
        $current_ban_status = get_user_meta($user_id, '_mc_loyalty_banned', true) === 'yes' ? 'yes' : 'no';
        if ($banned !== $current_ban_status) {
            update_user_meta($user_id, '_mc_loyalty_banned', $banned);
            $messages[] = $banned === 'yes' ? "User banned from loyalty." : "User ban lifted.";
        }

        if ( $type !== 'none' && $amount >= 0 ) {
            
            // STRICT DATABASE ENGINE: Get highest value between legacy key and new key
            $pts_new = (int)get_user_meta($user_id, '_mc_user_points', true);
            $pts_old = (int)get_user_meta($user_id, 'mc_points', true);
            $current_pts = max($pts_new, $pts_old);
            
            $new_balance = $current_pts;
            $diff = 0;

            if ( $type === 'add' ) {
                $new_balance += $amount;
                $diff = $amount;
                $lifetime = (int)get_user_meta($user_id, '_mc_lifetime_points', true);
                if ($lifetime < $current_pts) $lifetime = $current_pts; 
                update_user_meta($user_id, '_mc_lifetime_points', $lifetime + $amount);
            }
            elseif ( $type === 'deduct' ) {
                $new_balance = max(0, $current_pts - $amount);
                $diff = -($current_pts - $new_balance);
            }
            elseif ( $type === 'set' ) {
                $new_balance = $amount;
                $diff = $new_balance - $current_pts;
            }

            if ($new_balance !== $current_pts || $type === 'set') {
                // FORCE OVERWRITE ALL KEYS (Bridge ensures zero conflicts)
                update_user_meta($user_id, '_mc_user_points', $new_balance);
                update_user_meta($user_id, 'mc_points', $new_balance); 
                
                $messages[] = "Points updated to " . number_format($new_balance) . " PTS.";

                $history = get_user_meta($user_id, '_mc_points_history', true);
                if (!is_array($history)) $history = [];
                
                $log_entry = [
                    'id'      => uniqid(),
                    'date'    => $adj_date,
                    'reason'  => empty($reason) ? 'Manual Admin Adjustment' : $reason,
                    'order'   => '-',
                    'diff'    => $diff,
                    'balance' => $new_balance
                ];
                
                array_unshift($history, $log_entry);
                $history = array_slice($history, 0, 200); 
                update_user_meta($user_id, '_mc_points_history', $history);
            }
        }

        if (!empty($messages)) {
            echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-bottom:20px; border-radius:4px; font-weight:600;">✅ ' . implode(' | ', $messages) . '</div>';
        }
    }
}

$search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$users_per_page = 20;

$args = [
    'number'  => $users_per_page,
    'paged'   => $paged,
    'orderby' => 'display_name',
    'order'   => 'ASC',
];

if ( !empty($search_query) ) {
    $args['search']         = '*' . $search_query . '*';
    $args['search_columns'] = ['user_login', 'user_email', 'user_nicename', 'display_name'];
}

$user_query = new WP_User_Query( $args );
$users = $user_query->get_results();
$total_users = $user_query->get_total();
$total_pages = ceil($total_users / $users_per_page);
?>

<style>
    .mc-crm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .mc-crm-search { display: flex; gap: 10px; }
    .mc-crm-search input[type="text"] { padding: 6px 12px; border-radius: 4px; border: 1px solid #8c8f94; width: 250px; }
    .mc-crm-search button { background: #f6f7f7; border: 1px solid #8c8f94; border-radius: 4px; padding: 0 15px; cursor: pointer; font-weight: 600; color: #2271b1; }
    .mc-customer-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.04); border: 1px solid #ccd0d4; }
    .mc-customer-table th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 700; color: #1d2327; border-bottom: 2px solid #eee; }
    .mc-customer-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; color: #3c434a; }
    .mc-user-cell { display: flex; align-items: center; gap: 15px; }
    .mc-user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #eee; }
    .mc-user-details strong { display: block; font-size: 14px; color: #1d2327; margin-bottom: 2px; }
    .mc-user-details span { font-size: 12px; color: #888; display: flex; align-items: center; gap: 8px; }
    .mc-pts-badge { font-weight: 800; font-size: 15px; color: #2271b1; background: #eaf2fa; padding: 4px 12px; border-radius: 20px; display: inline-block; }
    .mc-banned-tag { font-size: 10px; font-weight: 800; color: #d63638; background: #fcf0f1; padding: 2px 6px; border-radius: 4px; border: 1px solid #f5c2c7; letter-spacing: 0.5px; }
    
    .mc-action-group { display: flex; gap: 8px; justify-content: flex-end; }
    .mc-action-btn { background: transparent; border: 1px solid #2271b1; color: #2271b1; padding: 6px 12px; border-radius: 4px; font-weight: 600; font-size: 12px; cursor: pointer; transition: all 0.2s; text-transform: uppercase; }
    .mc-action-btn:hover { background: #2271b1; color: #fff; }
    .mc-btn-history { border-color: #8c8f94; color: #50575e; }
    .mc-btn-history:hover { background: #8c8f94; color: #fff; }

    .mc-pagination { display: flex; gap: 5px; justify-content: flex-end; margin-top: 20px; }
    .mc-pagination a, .mc-pagination span { padding: 6px 12px; border: 1px solid #ccd0d4; background: #fff; border-radius: 4px; text-decoration: none; color: #2271b1; font-weight: 600; font-size: 13px; }
    
    .mc-modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 999999; align-items: center; justify-content: center; }
    .mc-modal-box { background: #fff; width: 100%; max-width: 500px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); overflow: hidden; }
    .mc-modal-box.mc-large { max-width: 700px; }
    .mc-modal-header { background: #f8f9fa; padding: 20px 25px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; }
    .mc-modal-close { cursor: pointer; font-size: 24px; color: #888; border: none; background: transparent; }
    .mc-modal-body { padding: 25px; max-height: 60vh; overflow-y: auto; }
    .mc-modal-footer { padding: 15px 25px; background: #f8f9fa; border-top: 1px solid #eee; display: flex; justify-content: flex-end; gap: 10px; }
    
    .mc-hist-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .mc-hist-table th { padding: 10px; border-bottom: 2px solid #eee; text-align: left; color: #111; }
    .mc-hist-table td { padding: 12px 10px; border-bottom: 1px solid #eee; color: #555; }
    .mc-hist-pos { color: #28a745; font-weight: bold; }
    .mc-hist-neg { color: #d63638; font-weight: bold; }
</style>

<div class="mc-crm-header">
    <div>
        <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;">Manage Customers</h2>
        <p style="margin:0; font-size:13px; color:#646970;">Manage balances, issue refunds, or ban abusers from the loyalty program.</p>
    </div>
    
    <form method="get" class="mc-crm-search">
        <input type="hidden" name="page" value="mc-loyalty-settings">
        <input type="hidden" name="tab" value="customers">
        <input type="hidden" name="sub" value="manage">
        <input type="text" name="s" value="<?php echo esc_attr($search_query); ?>" placeholder="Search by name or email...">
        <button type="submit">Search</button>
        <?php if(!empty($search_query)): ?>
            <a href="?page=mc-loyalty-settings&tab=customers&sub=manage" style="padding: 6px 12px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; text-decoration: none; color: #d63638; font-weight: 600;">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($users)): ?>
    <div style="background:#fff; padding:40px; text-align:center; border:1px solid #ccd0d4; border-radius:8px;">
        <p style="font-size:16px; color:#777; margin:0;">No customers found matching your search.</p>
    </div>
<?php else: ?>
    <table class="mc-customer-table">
        <thead>
            <tr>
                <th>Customer Profile</th>
                <th>Registration Date</th>
                <th>Current Balance</th>
                <th style="text-align:right;">Management</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($users as $user): 
                $pts_n = (int)get_user_meta($user->ID, '_mc_user_points', true);
                $pts_o = (int)get_user_meta($user->ID, 'mc_points', true);
                $points = max($pts_n, $pts_o);

                $is_banned = get_user_meta($user->ID, '_mc_loyalty_banned', true) === 'yes';
                $history = get_user_meta($user->ID, '_mc_points_history', true);
                if (!is_array($history)) $history = [];
            ?>
            <tr>
                <td>
                    <div class="mc-user-cell">
                        <?php echo get_avatar($user->ID, 40, '', '', ['class' => 'mc-user-avatar']); ?>
                        <div class="mc-user-details">
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                            <span><?php echo esc_html($user->user_email); ?> <?php if($is_banned): ?><span class="mc-banned-tag">BANNED</span><?php endif; ?></span>
                        </div>
                    </div>
                </td>
                <td style="font-size:13px;"><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></td>
                <td><span class="mc-pts-badge" style="<?php echo $is_banned ? 'background:#f1f1f1; color:#888; text-decoration:line-through;' : ''; ?>"><?php echo number_format($points); ?> PTS</span></td>
                <td style="text-align:right;">
                    <div class="mc-action-group">
                        <button type="button" class="mc-action-btn mc-btn-history" onclick="mcOpenHistory(<?php echo esc_attr($user->ID); ?>)">View History</button>
                        <button type="button" class="mc-action-btn mc-open-modal" 
                            data-userid="<?php echo esc_attr($user->ID); ?>" 
                            data-name="<?php echo esc_attr($user->display_name); ?>" 
                            data-points="<?php echo esc_attr($points); ?>"
                            data-banned="<?php echo $is_banned ? 'yes' : 'no'; ?>">Adjust Points</button>
                    </div>
                    
                    <div id="mc-hist-data-<?php echo esc_attr($user->ID); ?>" style="display:none;">
                        <?php if (empty($history)): ?>
                            <p style="text-align:center; color:#888; padding:20px;">No point history found for this user.</p>
                        <?php else: ?>
                            <table class="mc-hist-table">
                                <tr><th>Date</th><th>Reason</th><th>Order No.</th><th style="text-align:right;">Points</th></tr>
                                <?php foreach($history as $log): 
                                    $diff = (int)$log['diff'];
                                    $class = $diff > 0 ? 'mc-hist-pos' : ($diff < 0 ? 'mc-hist-neg' : '');
                                    $sign = $diff > 0 ? '+' : '';
                                ?>
                                <tr>
                                    <td><?php echo date('M j, Y', $log['date']); ?></td>
                                    <td><?php echo esc_html($log['reason']); ?></td>
                                    <td><?php echo esc_html($log['order']); ?></td>
                                    <td style="text-align:right;">
                                        <div class="<?php echo $class; ?>"><?php echo $sign . $diff; ?></div>
                                        <div style="font-size:10px; color:#999;"><?php echo number_format((int)$log['balance']); ?> Balance</div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($total_pages > 1): ?>
        <div class="mc-pagination">
            <?php echo paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'prev_text' => '&laquo;', 'next_text' => '&raquo;', 'total' => $total_pages, 'current' => $paged]); ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="mc-modal-overlay" id="mc-adjust-modal">
    <div class="mc-modal-box">
        <form method="post" action="">
            <?php wp_nonce_field('mc_adjust_pts_nonce', 'mc_adjust_pts_field'); ?>
            <input type="hidden" name="mc_customer_id" id="mc-modal-userid" value="">
            <div class="mc-modal-header"><h3>Manage: <span id="mc-modal-name" style="color:#2271b1;"></span></h3><button type="button" class="mc-modal-close" onclick="jQuery('#mc-adjust-modal').hide();">&times;</button></div>
            <div class="mc-modal-body">
                <div style="margin-bottom:20px; display:flex; justify-content:space-between; background:#f0f8ff; padding:12px 15px; border-radius:6px; border:1px solid #cce5ff; align-items:center;">
                    <span style="font-weight:600; color:#333;">Current Balance:</span><strong style="color:#2271b1; font-size:18px;" id="mc-modal-current-pts">0</strong>
                </div>
                
                <div class="mc-form-row" style="display:flex; gap:15px;">
                    <div style="flex:1;">
                        <span class="mc-form-label">Point Action Type</span>
                        <select name="mc_adjust_type" style="width:100%;"><option value="none">-- No Adjustment --</option><option value="add">➕ Add Points</option><option value="deduct">➖ Deduct Points</option><option value="set">⚙️ Set Exact Balance</option></select>
                    </div>
                    <div style="flex:1;">
                        <span class="mc-form-label">Amount</span>
                        <input type="number" name="mc_adjust_amount" min="0" step="1" style="width:100%;">
                    </div>
                </div>

                <div class="mc-form-row" style="display:flex; gap:15px;">
                    <div style="flex:2;">
                        <span class="mc-form-label">Reason (Visible to Customer)</span>
                        <input type="text" name="mc_adjust_reason" style="width:100%;" placeholder="e.g. Order #123 Refund">
                    </div>
                    <div style="flex:1;">
                        <span class="mc-form-label">Backdate (Optional)</span>
                        <input type="date" name="mc_adjust_date" style="width:100%;" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>

                <div class="mc-toggle-row" style="background:#fff3f3; padding:15px; border-radius:6px; border:1px solid #f5c2c7;"><div class="mc-form-info" style="margin:0;"><span class="mc-form-label" style="color:#d63638;">Ban User from Loyalty</span><span class="mc-form-desc">Cannot earn or redeem points.</span></div><label class="mc-toggle-switch"><input type="hidden" name="mc_adjust_banned" value="no"><input type="checkbox" name="mc_adjust_banned" id="mc-modal-banned" value="yes"><span class="mc-slider"></span></label></div>
            </div>
            <div class="mc-modal-footer"><button type="button" onclick="jQuery('#mc-adjust-modal').hide();" style="background:transparent; border:1px solid #888; color:#555; padding:8px 16px; border-radius:4px; cursor:pointer;">Cancel</button><button type="submit" name="mc_submit_adjustment" style="background:#2271b1; color:#fff; border:none; padding:8px 20px; border-radius:4px; cursor:pointer;">Apply Changes</button></div>
        </form>
    </div>
</div>

<div class="mc-modal-overlay" id="mc-history-modal">
    <div class="mc-modal-box mc-large">
        <div class="mc-modal-header">
            <h3>Points History</h3>
            <div style="display:flex; gap:15px; align-items:center;">
                <form method="post" action="" style="margin:0;" onsubmit="return confirm('Are you sure you want to permanently delete this history log? Doing so will also reset the user\'s Lifetime Points Odometer back to their current active balance.');">
                    <input type="hidden" name="mc_customer_id" id="mc-hist-userid" value="">
                    <button type="submit" name="mc_clear_history" style="background:transparent; border:none; color:#d63638; cursor:pointer; font-weight:600; text-decoration:underline;">🗑️ Clear History & Reset Odometer</button>
                </form>
                <button type="button" class="mc-modal-close" onclick="jQuery('#mc-history-modal').hide();">&times;</button>
            </div>
        </div>
        <div class="mc-modal-body" id="mc-history-content"></div>
    </div>
</div>

<script>
function mcOpenHistory(uid) {
    let content = jQuery('#mc-hist-data-' + uid).html();
    jQuery('#mc-hist-userid').val(uid);
    jQuery('#mc-history-content').html(content);
    jQuery('#mc-history-modal').css('display', 'flex');
}
jQuery(document).ready(function($) {
    $('.mc-open-modal').on('click', function(e) {
        e.preventDefault();
        $('#mc-modal-userid').val($(this).data('userid'));
        $('#mc-modal-name').text($(this).data('name'));
        $('#mc-modal-current-pts').text($(this).data('points') + ' PTS');
        $('#mc-modal-banned').prop('checked', $(this).data('banned') === 'yes');
        $('#mc-adjust-modal').css('display', 'flex');
    });
});
</script>