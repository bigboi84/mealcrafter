<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div style="margin-bottom:20px; display:flex; justify-content:flex-end;">
    <button type="button" id="mc-add-new-banner" style="background:#2271b1; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">+ Add New Banner</button>
</div>

<form method="post" action="options.php" id="mc-loyalty-banners-form">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div id="mc-banners-container">
        <p class="description" style="margin-bottom:25px; font-size:14px;">Build custom banners to display on the My Account page. Choose between Simple messages, Target progress bars, or "Get Points" calls to action.</p>
        
        <?php 
        $raw_banners = get_option('mc_pts_banners', []); 
        if (!is_array($raw_banners)) $raw_banners = [];

        // THE SOFT DELETE FILTER: Only show banners that still have an ID
        $banners = array_filter($raw_banners, function($b) {
            return !empty($b['id']); 
        });

        if(empty($banners)) {
            echo '<div class="mc-rule-card" id="mc-no-banners-msg" style="padding:40px; text-align:center; background:#f9f9f9;"><p style="margin:0; color:#777; font-size:15px;">No banners created yet. Click "Add New Banner" above to get started.</p></div>';
        } else {
            foreach($banners as $index => $banner) {
                $id = esc_attr($banner['id']);
                $type = $banner['type'] ?? 'simple';
                ?>
                <div class="mc-rule-card mc-existing-banner" style="padding:0; overflow:hidden;">
                    <input type="hidden" class="mc-banner-id" name="mc_pts_banners[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                    
                    <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
                        <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                            <label class="mc-toggle-switch" title="Toggle Active/Inactive">
                                <input type="checkbox" name="mc_pts_banners[<?php echo $id; ?>][active]" value="yes" <?php checked($banner['active'] ?? 'yes', 'yes'); ?>>
                                <span class="mc-slider"></span>
                            </label>
                            <div class="mc-header-badge" style="width:30px; height:30px; border-radius:4px; background:<?php echo esc_attr($banner['bg_color'] ?? '#f1f1f1'); ?>; border:1px solid #ccc; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                                <?php if(!empty($banner['image_url'])): ?>
                                    <img src="<?php echo esc_url($banner['image_url']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                <?php endif; ?>
                            </div>
                            <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-banner-title-display"><?php echo esc_html($banner['name'] ?: 'Unnamed Banner'); ?></h3>
                            <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-banner-type-badge"><?php echo esc_html(str_replace('_', ' ', $type)); ?></span>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <a href="#" class="mc-remove-banner" style="color:#d63638; text-decoration:none; font-weight:600; font-size:13px;">Delete Banner</a>
                            <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▼</span>
                        </div>
                    </div>

                    <div class="mc-rule-card-body" style="display:none; padding:20px;">
                        
                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Banner name</span><span class="mc-form-desc">Internal identifier for this banner.</span></div>
                            <div class="mc-form-control"><input type="text" class="mc-banner-name-input" name="mc_pts_banners[<?php echo $id; ?>][name]" value="<?php echo esc_attr($banner['name'] ?? ''); ?>" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Banner type</span></div>
                            <div class="mc-form-control mc-radio-group">
                                <label><input type="radio" class="mc-banner-type-toggle" name="mc_pts_banners[<?php echo $id; ?>][type]" value="simple" <?php checked($type, 'simple'); ?>> Simple</label>
                                <label><input type="radio" class="mc-banner-type-toggle" name="mc_pts_banners[<?php echo $id; ?>][type]" value="target" <?php checked($type, 'target'); ?>> Target</label>
                                <label><input type="radio" class="mc-banner-type-toggle" name="mc_pts_banners[<?php echo $id; ?>][type]" value="get_points" <?php checked($type, 'get_points'); ?>> Get Points</label>
                            </div>
                        </div>

                        <div class="mc-type-section mc-type-target" style="<?php echo $type === 'target' ? 'display:block;' : 'display:none;'; ?>">
                            <div class="mc-form-row">
                                <div class="mc-form-info"><span class="mc-form-label">Target Action Type</span></div>
                                <div class="mc-form-control">
                                    <select name="mc_pts_banners[<?php echo $id; ?>][target_action]" style="width:100%; max-width:400px; padding:6px; border-radius:4px;">
                                        <?php $t_act = $banner['target_action'] ?? 'next_level'; ?>
                                        <option value="next_level" <?php selected($t_act, 'next_level'); ?>>Points of next level</option>
                                        <option value="points_collected" <?php selected($t_act, 'points_collected'); ?>>Extra points for points collected</option>
                                        <option value="amount_spent" <?php selected($t_act, 'amount_spent'); ?>>Extra points for amount spent</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr style="margin:20px 0; border:0; border-bottom:1px solid #eee;">
                            
                            <div class="mc-form-row" style="display:flex; align-items:center; gap:15px; margin-bottom:15px;">
                                <label class="mc-toggle-switch">
                                    <input type="checkbox" class="mc-progress-toggle" name="mc_pts_banners[<?php echo $id; ?>][show_progress]" value="yes" <?php checked($banner['show_progress'] ?? 'yes', 'yes'); ?>>
                                    <span class="mc-slider"></span>
                                </label>
                                <span style="font-weight:600; color:#1d2327;">Show Progress Slider</span>
                            </div>

                            <div class="mc-progress-colors-wrap" style="<?php echo ($banner['show_progress'] ?? 'yes') === 'yes' ? 'display:flex;' : 'display:none;'; ?> align-items:center; gap:15px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee; margin-bottom:20px;">
                                <span style="font-weight:600; color:#555;">Bar Background Color</span>
                                <input type="color" name="mc_pts_banners[<?php echo $id; ?>][progress_bar_color]" value="<?php echo esc_attr($banner['progress_bar_color'] ?? '#e5e5e5'); ?>">
                                <span style="font-weight:600; color:#555; margin-left:20px;">Progress Fill Color</span>
                                <input type="color" name="mc_pts_banners[<?php echo $id; ?>][progress_fill_color]" value="<?php echo esc_attr($banner['progress_fill_color'] ?? '#8bc34a'); ?>">
                            </div>
                        </div>

                        <div class="mc-type-section mc-type-get_points" style="<?php echo $type === 'get_points' ? 'display:block;' : 'display:none;'; ?>">
                            <div class="mc-form-row">
                                <div class="mc-form-info"><span class="mc-form-label">Get Points Action Type</span></div>
                                <div class="mc-form-control">
                                    <select name="mc_pts_banners[<?php echo $id; ?>][get_points_action]" style="width:100%; max-width:400px; padding:6px; border-radius:4px;">
                                        <?php $gp_act = $banner['get_points_action'] ?? 'complete_profile'; ?>
                                        <option value="complete_profile" <?php selected($gp_act, 'complete_profile'); ?>>Complete profile</option>
                                        <option value="refer_friend" <?php selected($gp_act, 'refer_friend'); ?>>Refer a friend</option>
                                        <option value="referral_purchase" <?php selected($gp_act, 'referral_purchase'); ?>>Referral purchase</option>
                                        <option value="leave_review" <?php selected($gp_act, 'leave_review'); ?>>Leave a review</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Banner title</span></div>
                            <div class="mc-form-control"><input type="text" name="mc_pts_banners[<?php echo $id; ?>][title]" value="<?php echo esc_attr($banner['title'] ?? ''); ?>" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Banner text</span></div>
                            <div class="mc-form-control"><textarea name="mc_pts_banners[<?php echo $id; ?>][text]" rows="3" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;"><?php echo esc_textarea($banner['text'] ?? ''); ?></textarea></div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Banner Image</span></div>
                            <div class="mc-form-control" style="display:flex; align-items:center; gap:15px;">
                                <input type="hidden" class="mc-banner-img-input" name="mc_pts_banners[<?php echo $id; ?>][image_url]" value="<?php echo esc_url($banner['image_url'] ?? ''); ?>">
                                <div class="mc-banner-img-preview" style="width:60px; height:60px; border-radius:4px; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f9f9f9;">
                                    <?php if(!empty($banner['image_url'])): ?>
                                        <img src="<?php echo esc_url($banner['image_url']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span style="color:#aaa; font-size:10px;">No Image</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <button type="button" class="button mc-upload-banner-btn">Upload Image</button>
                                    <a href="#" class="mc-remove-banner-img" style="display:<?php echo empty($banner['image_url']) ? 'none' : 'inline-block'; ?>; color:#d63638; margin-left:10px; font-size:12px; text-decoration:none;">Remove</a>
                                </div>
                            </div>
                        </div>

                        <div class="mc-form-row mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                            <span style="font-weight:600;">Background Color</span>
                            <input type="color" name="mc_pts_banners[<?php echo $id; ?>][bg_color]" value="<?php echo esc_attr($banner['bg_color'] ?? '#f1f1f1'); ?>">
                            
                            <span style="font-weight:600; margin-left:15px;">Title Color</span>
                            <input type="color" name="mc_pts_banners[<?php echo $id; ?>][title_color]" value="<?php echo esc_attr($banner['title_color'] ?? '#1d2327'); ?>">

                            <span style="font-weight:600; margin-left:15px;">Text Color</span>
                            <input type="color" name="mc_pts_banners[<?php echo $id; ?>][text_color]" value="<?php echo esc_attr($banner['text_color'] ?? '#333333'); ?>">
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Add custom link</span></div>
                            <div class="mc-form-control"><input type="url" name="mc_pts_banners[<?php echo $id; ?>][link_url]" value="<?php echo esc_url($banner['link_url'] ?? ''); ?>" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;" placeholder="https://..."></div>
                        </div>

                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Banners', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script type="text/template" id="mc-banner-template">
    <div class="mc-rule-card mc-existing-banner" style="padding:0; overflow:hidden;">
        <input type="hidden" class="mc-banner-id" name="mc_pts_banners[{id}][id]" value="{id}">
        
        <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
            <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                <label class="mc-toggle-switch"><input type="checkbox" name="mc_pts_banners[{id}][active]" value="yes" checked><span class="mc-slider"></span></label>
                <div class="mc-header-badge" style="width:30px; height:30px; border-radius:4px; background:#f1f1f1; border:1px solid #ccc; display:flex; align-items:center; justify-content:center; overflow:hidden;"></div>
                <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-banner-title-display">New Banner</h3>
                <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-banner-type-badge">SIMPLE</span>
            </div>
            <div style="display:flex; align-items:center; gap:15px;">
                <a href="#" class="mc-remove-banner" style="color:#d63638; text-decoration:none; font-weight:600; font-size:13px;">Delete Banner</a>
                <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▲</span>
            </div>
        </div>

        <div class="mc-rule-card-body" style="padding:20px;">
            
            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Banner name</span></div>
                <div class="mc-form-control"><input type="text" class="mc-banner-name-input" name="mc_pts_banners[{id}][name]" value="" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Banner type</span></div>
                <div class="mc-form-control mc-radio-group">
                    <label><input type="radio" class="mc-banner-type-toggle" name="mc_pts_banners[{id}][type]" value="simple" checked> Simple</label>
                    <label><input type="radio" class="mc-banner-type-toggle" name="mc_pts_banners[{id}][type]" value="target"> Target</label>
                    <label><input type="radio" class="mc-banner-type-toggle" name="mc_pts_banners[{id}][type]" value="get_points"> Get Points</label>
                </div>
            </div>

            <div class="mc-type-section mc-type-target" style="display:none;">
                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Target Action Type</span></div>
                    <div class="mc-form-control">
                        <select name="mc_pts_banners[{id}][target_action]" style="width:100%; max-width:400px; padding:6px; border-radius:4px;">
                            <option value="next_level">Points of next level</option>
                            <option value="points_collected">Extra points for points collected</option>
                            <option value="amount_spent">Extra points for amount spent</option>
                        </select>
                    </div>
                </div>
                
                <hr style="margin:20px 0; border:0; border-bottom:1px solid #eee;">

                <div class="mc-form-row" style="display:flex; align-items:center; gap:15px; margin-bottom:15px;">
                    <label class="mc-toggle-switch">
                        <input type="checkbox" class="mc-progress-toggle" name="mc_pts_banners[{id}][show_progress]" value="yes" checked>
                        <span class="mc-slider"></span>
                    </label>
                    <span style="font-weight:600; color:#1d2327;">Show Progress Slider</span>
                </div>

                <div class="mc-progress-colors-wrap" style="display:flex; align-items:center; gap:15px; background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee; margin-bottom:20px;">
                    <span style="font-weight:600; color:#555;">Bar Background Color</span>
                    <input type="color" name="mc_pts_banners[{id}][progress_bar_color]" value="#e5e5e5">
                    <span style="font-weight:600; color:#555; margin-left:20px;">Progress Fill Color</span>
                    <input type="color" name="mc_pts_banners[{id}][progress_fill_color]" value="#8bc34a">
                </div>
            </div>

            <div class="mc-type-section mc-type-get_points" style="display:none;">
                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Get Points Action Type</span></div>
                    <div class="mc-form-control">
                        <select name="mc_pts_banners[{id}][get_points_action]" style="width:100%; max-width:400px; padding:6px; border-radius:4px;">
                            <option value="complete_profile">Complete profile</option>
                            <option value="refer_friend">Refer a friend</option>
                            <option value="referral_purchase">Referral purchase</option>
                            <option value="leave_review">Leave a review</option>
                        </select>
                    </div>
                </div>
            </div>

            <hr style="margin:25px 0; border:0; border-bottom:1px solid #eee;">

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Banner title</span></div>
                <div class="mc-form-control"><input type="text" name="mc_pts_banners[{id}][title]" value="" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Banner text</span></div>
                <div class="mc-form-control"><textarea name="mc_pts_banners[{id}][text]" rows="3" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;"></textarea></div>
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Banner Image</span></div>
                <div class="mc-form-control" style="display:flex; align-items:center; gap:15px;">
                    <input type="hidden" class="mc-banner-img-input" name="mc_pts_banners[{id}][image_url]" value="">
                    <div class="mc-banner-img-preview" style="width:60px; height:60px; border-radius:4px; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f9f9f9;">
                        <span style="color:#aaa; font-size:10px;">No Image</span>
                    </div>
                    <div>
                        <button type="button" class="button mc-upload-banner-btn">Upload Image</button>
                        <a href="#" class="mc-remove-banner-img" style="display:none; color:#d63638; margin-left:10px; font-size:12px; text-decoration:none;">Remove</a>
                    </div>
                </div>
            </div>

            <div class="mc-form-row mc-inline-inputs" style="background:transparent; border:none; padding:0;">
                <span style="font-weight:600;">Background Color</span>
                <input type="color" name="mc_pts_banners[{id}][bg_color]" value="#f1f1f1">
                
                <span style="font-weight:600; margin-left:15px;">Title Color</span>
                <input type="color" name="mc_pts_banners[{id}][title_color]" value="#1d2327">

                <span style="font-weight:600; margin-left:15px;">Text Color</span>
                <input type="color" name="mc_pts_banners[{id}][text_color]" value="#333333">
            </div>

            <div class="mc-form-row">
                <div class="mc-form-info"><span class="mc-form-label">Add custom link</span></div>
                <div class="mc-form-control"><input type="url" name="mc_pts_banners[{id}][link_url]" value="" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;" placeholder="https://..."></div>
            </div>

        </div>
    </div>
</script>

<script>
jQuery(document).ready(function($) {
    var mc_media_uploader;

    // Accordion Toggle
    $(document).on('click', '.mc-rule-card-header', function(e) {
        if($(e.target).closest('.mc-remove-banner, .mc-toggle-switch').length) return;
        let $body = $(this).siblings('.mc-rule-card-body');
        let $indicator = $(this).find('.mc-toggle-indicator');
        $body.slideToggle(200, function() {
            if($body.is(':visible')) { $indicator.text('▲'); } else { $indicator.text('▼'); }
        });
    });

    // Live Title Update
    $(document).on('input', '.mc-banner-name-input', function() {
        let val = $(this).val();
        $(this).closest('.mc-rule-card').find('.mc-banner-title-display').text(val ? val : 'Unnamed Banner');
    });

    // Type Toggle Logic
    $(document).on('change', '.mc-banner-type-toggle', function() {
        let $card = $(this).closest('.mc-rule-card');
        let val = $(this).val();
        
        $card.find('.mc-banner-type-badge').text(val.replace('_', ' '));
        $card.find('.mc-type-section').hide();
        $card.find('.mc-type-' + val).show();
    });

    // Progress Bar Toggle Logic
    $(document).on('change', '.mc-progress-toggle', function() {
        let $colorWrap = $(this).closest('.mc-type-target').find('.mc-progress-colors-wrap');
        if($(this).is(':checked')) {
            $colorWrap.css('display', 'flex').hide().slideDown();
        } else {
            $colorWrap.slideUp();
        }
    });

    // Add New Banner
    $('#mc-add-new-banner').on('click', function(e) {
        e.preventDefault();
        $('#mc-no-banners-msg').hide();
        let uniqueId = 'banner_' + Date.now();
        let template = $('#mc-banner-template').html().replace(/{id}/g, uniqueId);
        
        // Append instead of prepend so they add to the bottom smoothly
        $('#mc-banners-container').append(template);
        
        // Automatically open the new banner
        $('#mc-banners-container .mc-existing-banner').last().find('.mc-rule-card-body').slideDown();
        $('#mc-banners-container .mc-existing-banner').last().find('.mc-toggle-indicator').text('▲');
    });

    // THE PROVEN SOFT DELETE
    $(document).on('click', '.mc-remove-banner', function(e) {
        e.preventDefault();
        if(confirm('Delete this banner? Click Save Banners below to permanently remove it.')) {
            let $card = $(this).closest('.mc-rule-card');
            
            // Empty out the ID so PHP ignores it on save
            $card.find('.mc-banner-id').val('');
            
            // Hide it visually
            $card.slideUp(300);
        }
    });

    // Media Uploader
    $(document).on('click', '.mc-upload-banner-btn', function(e) {
        e.preventDefault();
        let container = $(this).closest('.mc-form-control');
        
        if (mc_media_uploader) { mc_media_uploader.open(); return; }
        
        mc_media_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Banner Image', button: { text: 'Use this image' }, multiple: false
        });

        mc_media_uploader.on('select', function() {
            let attachment = mc_media_uploader.state().get('selection').first().toJSON();
            container.find('.mc-banner-img-input').val(attachment.url);
            container.find('.mc-banner-img-preview').html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
            
            let headerBadge = container.closest('.mc-rule-card').find('.mc-header-badge');
            headerBadge.html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
            
            container.find('.mc-remove-banner-img').show();
        });

        mc_media_uploader.open();
    });

    // Remove Image
    $(document).on('click', '.mc-remove-banner-img', function(e) {
        e.preventDefault();
        let container = $(this).closest('.mc-form-control');
        container.find('.mc-banner-img-input').val('');
        container.find('.mc-banner-img-preview').html('<span style="color:#aaa; font-size:10px;">No Image</span>');
        container.closest('.mc-rule-card').find('.mc-header-badge').empty();
        $(this).hide();
    });
});
</script>