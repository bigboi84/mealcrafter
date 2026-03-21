<?php
/**
 * MealCrafter: Tab - Customers Points
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Customers {

    public function __construct() {
        // We hook the AJAX handler directly in the module it belongs to
        add_action( 'wp_ajax_mc_adjust_user_points', [$this, 'ajax_adjust_user_points'] );
    }

    public function render() {
        ?>
        <style>
            .mc-points-table { width:100%; border-collapse:collapse; }
            .mc-points-table th, .mc-points-table td { padding:15px; text-align:left; border-bottom:1px solid #eee; }
            .mc-points-table th { background:#f9f9f9; font-weight:800; color:#333; }
            .mc-adjust-btn { background:#3498db; color:#fff; border:none; padding:8px 15px; border-radius:4px; font-weight:bold; cursor:pointer; font-size:12px; }
        </style>

        <div class="mc-main-content" style="margin-top:20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0; font-weight:800;">Customer Balances</h2>
                <input type="text" id="mc-user-search" placeholder="Search by name or email..." style="padding:8px 15px; width:280px; border-radius:6px; border:1px solid #ccc;">
            </div>

            <div style="border:1px solid #eee; border-radius:8px; overflow:hidden;">
                <table class="mc-points-table">
                    <thead><tr><th style="width:60px;">User</th><th>Name & Email</th><th>Current Balance</th><th>Actions</th></tr></thead>
                    <tbody id="mc-user-table-body">
                        <?php
                        $users = get_users(['role__in' => ['customer', 'subscriber', 'administrator']]);
                        foreach($users as $user) {
                            $balance = mc_get_user_points($user->ID);
                            $avatar = get_avatar_url($user->ID, ['size' => 40]);
                            ?>
                            <tr class="mc-user-row">
                                <td><img src="<?php echo esc_url($avatar); ?>" style="border-radius:50%; width:40px; height:40px;"></td>
                                <td>
                                    <strong class="mc-searchable" style="font-size:14px;"><?php echo esc_html($user->display_name); ?></strong><br>
                                    <span style="color:#888; font-size:12px;" class="mc-searchable"><?php echo esc_html($user->user_email); ?></span>
                                </td>
                                <td><span style="font-size:18px; font-weight:900; color:#e74c3c;" id="bal-<?php echo $user->ID; ?>"><?php echo number_format($balance); ?></span></td>
                                <td><button class="mc-adjust-btn" data-id="<?php echo esc_attr($user->ID); ?>" data-name="<?php echo esc_attr($user->display_name); ?>">Adjust Points</button></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>

            <div id="mc-adjust-modal" style="display:none; position:fixed; z-index:99999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); align-items:center; justify-content:center;">
                <div style="background:#fff; padding:30px; border-radius:12px; width:400px; box-shadow:0 10px 30px rgba(0,0,0,0.2);">
                    <h3 style="margin-top:0;">Adjust Points for <span id="mc-modal-name" style="color:#e74c3c;"></span></h3>
                    <input type="hidden" id="mc-modal-userid">
                    <p style="margin-bottom:5px; font-weight:bold;">Amount (Use negative to deduct):</p>
                    <input type="number" id="mc-modal-amount" placeholder="e.g. 500 or -200" style="width:100%; padding:10px; margin-bottom:15px; border-radius:4px; border:1px solid #ccc;">
                    <p style="margin-bottom:5px; font-weight:bold;">Reason / Description:</p>
                    <input type="text" id="mc-modal-reason" placeholder="e.g. Apology for late delivery" style="width:100%; padding:10px; margin-bottom:20px; border-radius:4px; border:1px solid #ccc;">
                    <div style="display:flex; gap:10px;">
                        <button id="mc-modal-cancel" style="flex:1; padding:12px; background:#eee; color:#333; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">Cancel</button>
                        <button id="mc-modal-save" style="flex:1; padding:12px; background:#2ecc71; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Save</button>
                    </div>
                </div>
            </div>
            <script>
                jQuery(document).ready(function($) {
                    $('#mc-user-search').on('keyup', function() { let val = $(this).val().toLowerCase(); $('.mc-user-row').filter(function() { $(this).toggle($(this).find('.mc-searchable').text().toLowerCase().indexOf(val) > -1) }); });
                    $('.mc-adjust-btn').on('click', function(e) { e.preventDefault(); $('#mc-modal-userid').val($(this).data('id')); $('#mc-modal-name').text($(this).data('name')); $('#mc-modal-amount, #mc-modal-reason').val(''); $('#mc-adjust-modal').css('display', 'flex'); });
                    $('#mc-modal-cancel').on('click', function(e) { e.preventDefault(); $('#mc-adjust-modal').hide(); });
                    $('#mc-modal-save').on('click', function(e) {
                        e.preventDefault(); let btn = $(this); let uid = $('#mc-modal-userid').val(); let amt = $('#mc-modal-amount').val(); let reason = $('#mc-modal-reason').val() || 'Admin Adjustment';
                        if(!amt || amt == 0) return; btn.text('Saving...').css('pointer-events','none');
                        $.post(ajaxurl, { action: 'mc_adjust_user_points', user_id: uid, amount: amt, description: reason }, function(res) {
                            if(res.success) { let newBal = parseFloat($('#bal-'+uid).text().replace(/,/g, '')) + parseFloat(amt); $('#bal-'+uid).text(newBal.toLocaleString('en-US')); $('#mc-adjust-modal').hide(); }
                            btn.text('Save').css('pointer-events','auto');
                        });
                    });
                });
            </script>
        </div>
        <?php
    }

    public function ajax_adjust_user_points() {
        if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
        $user_id = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $desc = sanitize_text_field($_POST['description']);
        if (mc_update_user_points($user_id, $amount, 'adjusted', $desc)) { wp_send_json_success(); } else { wp_send_json_error(); }
    }
}
// We instantiate it immediately so the AJAX hook is always listening
new MC_Tab_Customers();