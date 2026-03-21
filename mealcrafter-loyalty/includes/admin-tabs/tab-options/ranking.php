<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<form method="post" action="options.php">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div style="margin-bottom:25px;">
        <p class="description" style="font-size:14px;">Foster competition among your customers by displaying a public leaderboard of the top points earners.</p>
    </div>

    <div class="mc-form-section">
        <h3>Ranking Settings</h3>
        
        <div class="mc-toggle-row">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Enable customer ranking</span>
                <span class="mc-form-desc">Turn on the ranking system to calculate and track the top customers by points.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" class="mc-reveal-toggle" data-target="#mc-ranking-account-wrap" name="mc_pts_ranking_enable" value="yes" <?php checked(get_option('mc_pts_ranking_enable', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>

        <div class="mc-toggle-row" id="mc-ranking-account-wrap" style="<?php echo get_option('mc_pts_ranking_enable') === 'yes' ? '' : 'display:none;'; ?> border-bottom:none;">
            <div class="mc-form-info" style="margin:0;">
                <span class="mc-form-label">Show ranking in My Account</span>
                <span class="mc-form-desc">Automatically display the leaderboard on the WooCommerce My Account page.</span>
            </div>
            <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_ranking_my_account" value="yes" <?php checked(get_option('mc_pts_ranking_my_account', 'no'), 'yes'); ?>><span class="mc-slider"></span></label>
        </div>
    </div>

    <div class="mc-form-section" id="mc-ranking-shortcodes-wrap" style="<?php echo get_option('mc_pts_ranking_enable') === 'yes' ? '' : 'display:none;'; ?>">
        <h3>Ranking Shortcodes</h3>
        <p class="description" style="margin-bottom:20px;">Use these shortcodes to display the leaderboard on any page or post (like a dedicated "VIP Hall of Fame" page).</p>
        
        <div class="mc-rule-card" style="background:#fcfcfc; padding:20px; border-radius:8px; border:1px solid #e5e5e5; margin-bottom:15px;">
            <h4 style="margin:0 0 10px 0; font-size:15px; color:#1d2327;">Simple Customers List</h4>
            <p style="margin:0 0 15px 0; color:#646970; font-size:13px;">Prints the list with a clean, simple text layout.</p>
            <div style="background:#fff; padding:10px 15px; border:1px solid #ddd; border-radius:4px; display:flex; justify-content:space-between; align-items:center;">
                <code style="font-size:14px; color:#c7254e; background:transparent; padding:0;">[mc_points_ranking layout="simple" limit="10"]</code>
                <button type="button" class="button mc-copy-shortcode" data-code='[mc_points_ranking layout="simple" limit="10"]'>Copy</button>
            </div>
        </div>

        <div class="mc-rule-card" style="background:#fcfcfc; padding:20px; border-radius:8px; border:1px solid #e5e5e5;">
            <h4 style="margin:0 0 10px 0; font-size:15px; color:#1d2327;">Boxed Customers List</h4>
            <p style="margin:0 0 15px 0; color:#646970; font-size:13px;">Prints the list with a highly visual, boxed grid layout featuring avatars.</p>
            <div style="background:#fff; padding:10px 15px; border:1px solid #ddd; border-radius:4px; display:flex; justify-content:space-between; align-items:center;">
                <code style="font-size:14px; color:#c7254e; background:transparent; padding:0;">[mc_points_ranking layout="boxed" limit="10"]</code>
                <button type="button" class="button mc-copy-shortcode" data-code='[mc_points_ranking layout="boxed" limit="10"]'>Copy</button>
            </div>
        </div>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Ranking', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    // Elegant slide toggle for revealing inputs when the main switch is turned on
    $(document).on('change', '.mc-reveal-toggle', function() {
        let isChecked = $(this).is(':checked');
        if(isChecked) {
            $('#mc-ranking-account-wrap').css('display', 'flex').hide().slideDown(250);
            $('#mc-ranking-shortcodes-wrap').hide().slideDown(250);
        } else {
            $('#mc-ranking-account-wrap, #mc-ranking-shortcodes-wrap').slideUp(250);
        }
    });

    // Copy to clipboard function
    $('.mc-copy-shortcode').on('click', function(e) {
        e.preventDefault();
        let btn = $(this);
        let code = btn.data('code');
        
        navigator.clipboard.writeText(code).then(function() {
            let originalText = btn.text();
            btn.text('Copied!');
            btn.css('color', '#8bc34a');
            setTimeout(function() {
                btn.text(originalText);
                btn.css('color', '');
            }, 2000);
        });
    });
});
</script>