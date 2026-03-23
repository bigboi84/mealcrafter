<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. HANDLE STANDARD BULK ACTIONS
// ==========================================
if ( isset($_POST['mc_bulk_action_submit']) && isset($_POST['mc_bulk_nonce']) && wp_verify_nonce($_POST['mc_bulk_nonce'], 'mc_bulk_action') && current_user_can('manage_options') ) {
    
    $target     = sanitize_text_field($_POST['mc_bulk_target'] ?? 'all');
    $roles      = isset($_POST['mc_bulk_roles']) ? array_map('sanitize_text_field', $_POST['mc_bulk_roles']) : [];
    $spec_users = isset($_POST['mc_bulk_users']) ? array_map('intval', $_POST['mc_bulk_users']) : [];
    
    $type       = sanitize_text_field($_POST['mc_bulk_type']);
    $amount     = intval($_POST['mc_bulk_amount'] ?? 0);
    $reason     = sanitize_text_field($_POST['mc_bulk_reason']);
    $ban_action = sanitize_text_field($_POST['mc_bulk_ban']);
    $adj_date   = !empty($_POST['mc_bulk_date']) ? strtotime($_POST['mc_bulk_date']) : current_time('timestamp');
    
    $user_ids_to_process = [];

    // Determine targets
    if ($target === 'roles' && empty($roles)) {
        echo '<div style="background:#f8d7da; border-left:4px solid #d63638; color:#721c24; padding:12px 20px; margin-bottom:20px;">Error: Please select at least one user role.</div>';
    } elseif ($target === 'users' && empty($spec_users)) {
        echo '<div style="background:#f8d7da; border-left:4px solid #d63638; color:#721c24; padding:12px 20px; margin-bottom:20px;">Error: Please select at least one specific user.</div>';
    } else {
        if ($target === 'all') {
            $user_ids_to_process = get_users(['fields' => 'ID']);
        } elseif ($target === 'roles') {
            $user_ids_to_process = get_users(['role__in' => $roles, 'fields' => 'ID']);
        } elseif ($target === 'users') {
            $user_ids_to_process = $spec_users;
        }
        
        $updated_count = 0;
        $banned_count = 0;

        foreach ($user_ids_to_process as $user_id) {
            // Process Bans
            if ($ban_action === 'ban') {
                update_user_meta($user_id, '_mc_loyalty_banned', 'yes');
                $banned_count++;
            } elseif ($ban_action === 'unban') {
                update_user_meta($user_id, '_mc_loyalty_banned', 'no');
                $banned_count++;
            }

            // Process Points
            if ($type !== 'none' && $amount >= 0) {
                $pts_n = (int)get_user_meta($user_id, '_mc_user_points', true);
                $pts_o = (int)get_user_meta($user_id, 'mc_points', true);
                $current_pts = max($pts_n, $pts_o);

                $new_balance = $current_pts;
                $diff = 0;
                
                if ($type === 'add') {
                    $new_balance += $amount;
                    $diff = $amount;
                    $lifetime = (int)get_user_meta($user_id, '_mc_lifetime_points', true);
                    if ($lifetime < $current_pts) $lifetime = $current_pts;
                    update_user_meta($user_id, '_mc_lifetime_points', $lifetime + $amount);
                }
                elseif ($type === 'deduct') {
                    $new_balance = max(0, $current_pts - $amount);
                    $diff = -($current_pts - $new_balance);
                }
                elseif ($type === 'set') {
                    $new_balance = $amount;
                    $diff = $new_balance - $current_pts;
                }
                
                if ($new_balance !== $current_pts || $type === 'set') {
                    update_user_meta($user_id, '_mc_user_points', $new_balance);
                    update_user_meta($user_id, 'mc_points', $new_balance);
                    
                    $history = get_user_meta($user_id, '_mc_points_history', true);
                    if (!is_array($history)) $history = [];
                    
                    $log_entry = [
                        'id'      => uniqid(),
                        'date'    => $adj_date,
                        'reason'  => empty($reason) ? 'Bulk Admin Adjustment' : $reason,
                        'order'   => '-',
                        'diff'    => $diff,
                        'balance' => $new_balance
                    ];
                    
                    array_unshift($history, $log_entry);
                    $history = array_slice($history, 0, 200);
                    update_user_meta($user_id, '_mc_points_history', $history);

                    $updated_count++;
                }
            }
        }
        
        $msg = "✅ Bulk action complete! ";
        if ($updated_count > 0) $msg .= "Updated points for $updated_count users. ";
        if ($banned_count > 0) $msg .= "Updated ban status for $banned_count users.";
        if ($updated_count === 0 && $banned_count === 0) $msg = "No changes were needed.";

        echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-bottom:20px; border-radius:4px; font-weight:600;">' . esc_html($msg) . '</div>';
    }
}

// ==========================================
// 2. HANDLE "RESET POINTS" (DANGER ZONE)
// ==========================================
if ( isset($_POST['mc_reset_action_submit']) && isset($_POST['mc_reset_nonce']) && wp_verify_nonce($_POST['mc_reset_nonce'], 'mc_reset_action') && current_user_can('manage_options') ) {
    
    $target     = sanitize_text_field($_POST['mc_reset_target'] ?? 'all');
    $roles      = isset($_POST['mc_reset_roles']) ? array_map('sanitize_text_field', $_POST['mc_reset_roles']) : [];
    $spec_users = isset($_POST['mc_reset_users']) ? array_map('intval', $_POST['mc_reset_users']) : [];
    
    $do_pts = isset($_POST['mc_reset_do_points']) ? true : false;
    $do_log = isset($_POST['mc_reset_do_history']) ? true : false;
    
    if (!$do_pts && !$do_log) {
        echo '<div style="background:#f8d7da; border-left:4px solid #d63638; color:#721c24; padding:12px 20px; margin-bottom:20px;">Error: You must select at least one reset option (Points or History).</div>';
    } elseif ($target === 'roles' && empty($roles)) {
        echo '<div style="background:#f8d7da; border-left:4px solid #d63638; color:#721c24; padding:12px 20px; margin-bottom:20px;">Error: Please select at least one user role to reset.</div>';
    } elseif ($target === 'users' && empty($spec_users)) {
        echo '<div style="background:#f8d7da; border-left:4px solid #d63638; color:#721c24; padding:12px 20px; margin-bottom:20px;">Error: Please select at least one specific user to reset.</div>';
    } else {
        $user_ids_to_reset = [];
        if ($target === 'all') {
            $user_ids_to_reset = get_users(['fields' => 'ID']);
        } elseif ($target === 'roles') {
            $user_ids_to_reset = get_users(['role__in' => $roles, 'fields' => 'ID']);
        } elseif ($target === 'users') {
            $user_ids_to_reset = $spec_users;
        }
        
        $reset_pts_count = 0;
        $reset_log_count = 0;

        foreach ($user_ids_to_reset as $user_id) {
            if ($do_pts) {
                update_user_meta($user_id, '_mc_user_points', 0);
                update_user_meta($user_id, 'mc_points', 0);
                update_user_meta($user_id, '_mc_lifetime_points', 0);
                $reset_pts_count++;
            }
            if ($do_log) {
                update_user_meta($user_id, '_mc_points_history', []);
                $reset_log_count++;
            }
        }
        
        $msg = "✅ Reset complete! ";
        if ($do_pts) $msg .= "Points reset to 0 for $reset_pts_count users. ";
        if ($do_log) $msg .= "History deleted for $reset_log_count users.";
        
        echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-bottom:20px; border-radius:4px; font-weight:600;">' . esc_html($msg) . '</div>';
    }
}
?>

<div style="margin-bottom: 25px;">
    <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;">Bulk Actions</h2>
    <p style="margin:0; font-size:13px; color:#646970;">Mass assign, deduct, log, reset, or ban users.</p>
</div>

<div class="mc-rule-card" style="padding:25px; border-left:4px solid #f39c12; margin-bottom:30px;">
    <form method="post" action="">
        <?php wp_nonce_field('mc_bulk_action', 'mc_bulk_nonce'); ?>
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Bulk Point Assignment</h3>
        
        <div class="mc-form-row" style="margin-bottom:15px;">
            <span class="mc-form-label">Assign points to</span>
            <div class="mc-radio-group" style="display:flex; flex-direction:column; gap:10px;">
                <label style="color:#111;"><input type="radio" name="mc_bulk_target" value="all" checked class="mc-target-toggle" data-form="bulk"> All users</label>
                <label style="color:#111;"><input type="radio" name="mc_bulk_target" value="roles" class="mc-target-toggle" data-form="bulk"> Only specified user roles</label>
                <label style="color:#111;"><input type="radio" name="mc_bulk_target" value="users" class="mc-target-toggle" data-form="bulk"> Only specific users</label>
            </div>
        </div>

        <div class="mc-form-row" id="mc_bulk_roles_wrapper" style="display:none; max-width:600px; margin-bottom:20px;">
            <span class="mc-form-label">Select Roles</span>
            <select name="mc_bulk_roles[]" multiple="multiple" style="width:100%;">
                <?php 
                $wp_roles = wp_roles()->roles;
                foreach($wp_roles as $role_key => $role_details) {
                    echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_details['name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="mc-form-row" id="mc_bulk_users_wrapper" style="display:none; max-width:600px; margin-bottom:20px;">
            <span class="mc-form-label">Search Users</span>
            <select name="mc_bulk_users[]" class="mc-ajax-customer-search" multiple="multiple" style="width:100%;" data-placeholder="Search by name or email..."></select>
        </div>

        <div class="mc-form-row" style="display:flex; gap:20px; max-width:800px; padding:15px; background:#f9f9f9; border-radius:6px; border:1px solid #eee;">
            <div style="flex:1;">
                <span class="mc-form-label">Point Action Type</span>
                <select name="mc_bulk_type" style="width:100%;">
                    <option value="none">-- No Point Adjustment --</option>
                    <option value="add">➕ Add Points</option>
                    <option value="deduct">➖ Deduct Points</option>
                    <option value="set">⚙️ Set Exact Balance</option>
                </select>
            </div>
            <div style="flex:1;">
                <span class="mc-form-label">Amount</span>
                <input type="number" name="mc_bulk_amount" min="0" step="1" style="width:100%;" placeholder="e.g. 1000">
            </div>
        </div>

        <div class="mc-form-row" style="display:flex; gap:20px; max-width:800px;">
            <div style="flex:2;">
                <span class="mc-form-label">Reason (Visible to Customers)</span>
                <input type="text" name="mc_bulk_reason" style="width:100%;" placeholder="e.g. Holiday Bonus Points!">
            </div>
            <div style="flex:1;">
                <span class="mc-form-label">Backdate (Optional)</span>
                <input type="date" name="mc_bulk_date" style="width:100%;" value="<?php echo date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="mc-form-row" style="margin-bottom:30px; max-width:800px;">
            <span class="mc-form-label" style="color:#d63638;">Ban/Unban Users</span>
            <select name="mc_bulk_ban" style="width:100%; border-color:#f5c2c7; background:#fff3f3;">
                <option value="none">-- Do not change ban status --</option>
                <option value="ban">🚫 BAN selected users</option>
                <option value="unban">✅ UNBAN selected users</option>
            </select>
        </div>

        <button type="submit" name="mc_bulk_action_submit" style="background:#2271b1; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer;" onclick="return confirm('WARNING: You are about to mass-edit records. This cannot be undone. Proceed?');">Execute Bulk Action</button>
    </form>
</div>

<div class="mc-rule-card" style="padding:25px; border-left:4px solid #d63638; background:#fffafa;">
    <form method="post" action="">
        <?php wp_nonce_field('mc_reset_action', 'mc_reset_nonce'); ?>
        <h3 style="margin-top:0; border-bottom:1px solid #f5c2c7; padding-bottom:10px; margin-bottom:20px; color:#d63638;">Reset Points</h3>
        <p style="color:#646970; font-size:13px; margin-bottom:20px;"><strong>Warning:</strong> This process is irreversible. Please use with caution.</p>

        <div class="mc-form-row" style="margin-bottom:15px;">
            <div class="mc-radio-group" style="display:flex; flex-direction:column; gap:10px;">
                <label style="color:#111;"><input type="radio" name="mc_reset_target" value="all" checked class="mc-target-toggle" data-form="reset"> All users</label>
                <label style="color:#111;"><input type="radio" name="mc_reset_target" value="roles" class="mc-target-toggle" data-form="reset"> Only specified user roles</label>
                <label style="color:#111;"><input type="radio" name="mc_reset_target" value="users" class="mc-target-toggle" data-form="reset"> Only specific users</label>
            </div>
        </div>

        <div class="mc-form-row" id="mc_reset_roles_wrapper" style="display:none; max-width:600px; margin-bottom:20px;">
            <select name="mc_reset_roles[]" multiple="multiple" style="width:100%;">
                <?php 
                foreach($wp_roles as $role_key => $role_details) {
                    echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_details['name']) . '</option>';
                }
                ?>
            </select>
        </div>

        <div class="mc-form-row" id="mc_reset_users_wrapper" style="display:none; max-width:600px; margin-bottom:20px;">
            <select name="mc_reset_users[]" class="mc-ajax-customer-search" multiple="multiple" style="width:100%;" data-placeholder="Search by name or email..."></select>
        </div>

        <div class="mc-form-row" style="background:#fff; padding:15px; border-radius:6px; border:1px solid #f5c2c7; max-width:600px; margin-bottom:25px;">
            <label style="display:flex; align-items:center; gap:10px; margin-bottom:12px; font-weight:600; color:#d63638;">
                <input type="checkbox" name="mc_reset_do_points" value="1"> Reset users' points to 0
            </label>
            <label style="display:flex; align-items:center; gap:10px; font-weight:600; color:#d63638;">
                <input type="checkbox" name="mc_reset_do_history" value="1"> Delete all points history
            </label>
        </div>

        <button type="submit" name="mc_reset_action_submit" style="background:#d63638; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer;" onclick="return confirm('Are you absolutely sure you want to permanently reset these points/history?');">Reset Points</button>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/Hide Wrappers based on Radio Button Selection
    $('.mc-target-toggle').on('change', function() {
        let formType = $(this).data('form'); // 'bulk' or 'reset'
        let target = $(this).val(); // 'all', 'roles', or 'users'
        
        let $roleWrap = $('#mc_' + formType + '_roles_wrapper');
        let $userWrap = $('#mc_' + formType + '_users_wrapper');

        if (target === 'roles') {
            $userWrap.slideUp();
            $roleWrap.slideDown();
        } else if (target === 'users') {
            $roleWrap.slideUp();
            $userWrap.slideDown();
        } else {
            $roleWrap.slideUp();
            $userWrap.slideUp();
        }
    });

    // Initialize WooCommerce Customer Search AJAX Engine
    if($.fn.select2) {
        let wc_cust_nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_customers_nonce : '';
        $('.mc-ajax-customer-search').select2({
            allowClear: true, 
            minimumInputLength: 3,
            ajax: { 
                url: ajaxurl, 
                dataType: 'json', 
                delay: 250, 
                data: function(p) { return { term: p.term, action: 'woocommerce_json_search_customers', security: wc_cust_nonce }; }, 
                processResults: function(d) { 
                    var t = []; 
                    if (d) { $.each(d, function(id, text) { t.push({ id: id, text: text }); }); } 
                    return { results: t }; 
                }, 
                cache: true 
            }
        });
    }
});
</script>