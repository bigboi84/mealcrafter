<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ==========================================
// 1. HANDLE EXPORT
// ==========================================
if (isset($_POST['mc_export_csv']) && current_user_can('manage_options')) {
    
    $target = sanitize_text_field($_POST['mc_export_target'] ?? 'all');
    $roles  = isset($_POST['mc_export_roles']) ? array_map('sanitize_text_field', $_POST['mc_export_roles']) : [];
    
    $args = [];
    if ($target === 'roles' && !empty($roles)) {
        $args['role__in'] = $roles;
    }
    
    $users = get_users($args);
    
    if (empty($users)) {
        echo '<div style="background:#f8d7da; border-left:4px solid #d63638; color:#721c24; padding:12px 20px; margin-bottom:20px;">No users found for the selected criteria.</div>';
    } else {
        ob_end_clean(); // Clear any visual junk before downloading
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="mealcrafter_points_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        // Define exact CSV Header
        fputcsv($output, ['User ID', 'Email', 'Name', 'Points Balance']);
        
        foreach ($users as $user) {
            $pts_n = (int)get_user_meta($user->ID, '_mc_user_points', true);
            $pts_o = (int)get_user_meta($user->ID, 'mc_points', true);
            $points = max($pts_n, $pts_o);
            
            fputcsv($output, [$user->ID, $user->user_email, $user->display_name, $points]);
        }
        fclose($output);
        exit;
    }
}

// ==========================================
// 2. HANDLE IMPORT
// ==========================================
if (isset($_POST['mc_import_csv']) && current_user_can('manage_options') && !empty($_FILES['mc_csv_file']['tmp_name'])) {
    
    $file = $_FILES['mc_csv_file']['tmp_name'];
    $import_action = sanitize_text_field($_POST['mc_import_action'] ?? 'override'); // 'override' or 'add'
    
    $handle = fopen($file, "r");
    $row = 0;
    $updated = 0;
    
    if ($handle !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $row++;
            if ($row === 1) continue; // Skip header row
            
            $user_id = (int)($data[0] ?? 0);
            $csv_pts = (int)($data[3] ?? 0); // Column D
            
            if ($user_id > 0 && get_userdata($user_id)) {
                
                $pts_n = (int)get_user_meta($user_id, '_mc_user_points', true);
                $pts_o = (int)get_user_meta($user_id, 'mc_points', true);
                $current_pts = max($pts_n, $pts_o);
                
                $new_pts = $current_pts;
                $diff = 0;
                
                if ($import_action === 'override') {
                    $new_pts = max(0, $csv_pts);
                    $diff = $new_pts - $current_pts;
                } elseif ($import_action === 'add') {
                    $new_pts = max(0, $current_pts + $csv_pts); // Can also subtract if CSV has negative numbers
                    $diff = $new_pts - $current_pts;
                }
                
                if ($new_pts !== $current_pts) {
                    // Update Database (Bridge Mode)
                    update_user_meta($user_id, '_mc_user_points', $new_pts);
                    update_user_meta($user_id, 'mc_points', $new_pts);
                    
                    // Add to lifetime if points increased
                    if ($diff > 0) {
                        $lifetime = (int)get_user_meta($user_id, '_mc_lifetime_points', true);
                        if ($lifetime < $current_pts) $lifetime = $current_pts;
                        update_user_meta($user_id, '_mc_lifetime_points', $lifetime + $diff);
                    }
                    
                    // Write to History Log
                    $history = get_user_meta($user_id, '_mc_points_history', true);
                    if (!is_array($history)) $history = [];
                    $log_entry = [
                        'id'      => uniqid(),
                        'date'    => current_time('timestamp'),
                        'reason'  => 'CSV Import Adjustment',
                        'order'   => '-',
                        'diff'    => $diff,
                        'balance' => $new_pts
                    ];
                    array_unshift($history, $log_entry);
                    $history = array_slice($history, 0, 200);
                    update_user_meta($user_id, '_mc_points_history', $history);
                    
                    $updated++;
                }
            }
        }
        fclose($handle);
        echo '<div style="background:#d4edda; border-left:4px solid #28a745; color:#155724; padding:12px 20px; margin-bottom:20px; border-radius:4px; font-weight:600;">✅ Import complete! Successfully processed records and updated balances for ' . $updated . ' users.</div>';
    } else {
        echo '<div style="background:#f8d7da; border-left:4px solid #d63638; color:#721c24; padding:12px 20px; margin-bottom:20px;">Error reading the CSV file. Please make sure it is formatted correctly.</div>';
    }
}
?>

<div style="margin-bottom: 25px;">
    <h2 style="margin:0 0 5px 0; font-size:22px; color:#1d2327;">Export and Import</h2>
    <p style="margin:0; font-size:13px; color:#646970;">Download your customer balances for auditing, or upload a CSV to automatically update points.</p>
</div>

<div style="display:flex; gap:25px; flex-wrap:wrap;">

    <div class="mc-rule-card" style="flex:1; min-width:400px; padding:25px; border-top:4px solid #2ecc71;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Export Balances</h3>
        <p style="color:#666; font-size:13px; margin-bottom:20px;">Download a CSV of users, showing their IDs, emails, names, and current point balances.</p>
        
        <form method="post" action="">
            <div class="mc-form-row" style="margin-bottom:15px;">
                <span class="mc-form-label">Export users</span>
                <div class="mc-radio-group" style="display:flex; flex-direction:column; gap:10px; margin-top:8px;">
                    <label style="color:#111;"><input type="radio" name="mc_export_target" value="all" checked id="mc_export_target_all"> All users</label>
                    <label style="color:#111;"><input type="radio" name="mc_export_target" value="roles" id="mc_export_target_roles"> Only specified user roles</label>
                </div>
            </div>

            <div class="mc-form-row" id="mc_export_roles_wrapper" style="display:none; margin-bottom:20px;">
                <span class="mc-form-label">Select Roles</span>
                <select name="mc_export_roles[]" multiple="multiple" style="width:100%;">
                    <?php 
                    $wp_roles = wp_roles()->roles;
                    foreach($wp_roles as $role_key => $role_details) {
                        echo '<option value="' . esc_attr($role_key) . '">' . esc_html($role_details['name']) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <button type="submit" name="mc_export_csv" style="background:#2ecc71; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer;">📥 Export CSV</button>
        </form>
    </div>

    <div class="mc-rule-card" style="flex:1; min-width:400px; padding:25px; border-top:4px solid #2271b1;">
        <h3 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px; margin-bottom:20px;">Import Points</h3>
        <p style="color:#666; font-size:13px; margin-bottom:20px;">Upload a CSV to instantly adjust point balances. <strong style="color:#d63638;">Column A must be the User ID, and Column D must be the Points.</strong></p>
        
        <form method="post" action="" enctype="multipart/form-data">
            
            <div class="mc-form-row" style="background:#f9f9f9; padding:15px; border:1px dashed #ccc; border-radius:6px; margin-bottom:20px;">
                <span class="mc-form-label">Select CSV File</span>
                <input type="file" name="mc_csv_file" accept=".csv" required style="width:100%; margin-top:8px;">
            </div>

            <div class="mc-form-row" style="margin-bottom:25px;">
                <span class="mc-form-label">Action on existing points</span>
                <span class="mc-form-desc">Choose how the imported numbers will affect the users.</span>
                <select name="mc_import_action" style="width:100%; margin-top:8px;">
                    <option value="override">Overwrite existing points</option>
                    <option value="add">Add to existing points</option>
                </select>
            </div>

            <button type="submit" name="mc_import_csv" style="background:#2271b1; color:#fff; border:none; padding:10px 24px; border-radius:4px; font-weight:600; cursor:pointer;">📤 Import CSV</button>
        </form>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Show/Hide Role Selector for Export Form
    $('input[name="mc_export_target"]').on('change', function() {
        if ($('#mc_export_target_roles').is(':checked')) {
            $('#mc_export_roles_wrapper').slideDown();
        } else {
            $('#mc_export_roles_wrapper').slideUp();
        }
    });
});
</script>