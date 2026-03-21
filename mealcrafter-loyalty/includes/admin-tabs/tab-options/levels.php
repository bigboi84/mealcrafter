<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div style="margin-bottom:20px; display:flex; justify-content:flex-end;">
    <button type="button" id="mc-add-new-level" style="background:#2271b1; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">+ Add New Level</button>
</div>

<form method="post" action="options.php" id="mc-loyalty-levels-form">
    <?php settings_fields( 'mc_loyalty_options_group' ); ?>
    
    <div id="mc-levels-container">
        <p class="description" style="margin-bottom:25px; font-size:14px;">Create gamified levels and associate custom badges. Users will automatically level up as they collect more points over their lifetime.</p>
        
        <?php 
        $levels = get_option('mc_pts_levels', []); 
        if (!is_array($levels)) $levels = [];
        
        // Sort levels by minimum points required
        usort($levels, function($a, $b) { return ($a['min_points'] ?? 0) <=> ($b['min_points'] ?? 0); });

        if(empty($levels)) {
            echo '<div class="mc-rule-card" id="mc-no-levels-msg" style="padding:40px; text-align:center; background:#f9f9f9;"><p style="margin:0; color:#777; font-size:15px;">No levels created yet. Click "Add New Level" above to get started.</p></div>';
        } else {
            foreach($levels as $index => $level) {
                $id = esc_attr($level['id'] ?? 'level_' . uniqid());
                ?>
                <div class="mc-rule-card mc-existing-level" style="padding:0; overflow:hidden;">
                    <input type="hidden" name="mc_pts_levels[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                    
                    <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer; transition:background 0.2s;">
                        <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                            <?php if(!empty($level['badge_url'])): ?>
                                <img src="<?php echo esc_url($level['badge_url']); ?>" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid #ccc;">
                            <?php else: ?>
                                <div style="width:30px; height:30px; border-radius:50%; background:#eee; border:1px solid #ccc;"></div>
                            <?php endif; ?>
                            <h3 style="margin:0; font-size:15px; color:<?php echo esc_attr($level['color'] ?? '#1d2327'); ?>;" class="mc-level-title-display"><?php echo esc_html($level['name'] ?: 'Unnamed Level'); ?></h3>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <span style="font-size:12px; color:#777; font-weight:600; background:#eee; padding:3px 8px; border-radius:12px;">
                                <?php echo esc_html($level['min_points'] ?? 0); ?> pts
                            </span>
                            <a href="#" class="mc-remove-level" style="color:#d63638; text-decoration:none; font-weight:600; font-size:13px;">Delete Level</a>
                            <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▼</span>
                        </div>
                    </div>

                    <div class="mc-rule-card-body" style="display:none; padding:20px;">
                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Level name</span></div>
                            <div class="mc-form-control"><input type="text" class="mc-level-name-input" name="mc_pts_levels[<?php echo $id; ?>][name]" value="<?php echo esc_attr($level['name'] ?? ''); ?>" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;" placeholder="e.g. Gold VIP"></div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info"><span class="mc-form-label">Points to collect</span><span class="mc-form-desc">Set the minimum points required to reach this level.</span></div>
                            <div class="mc-form-control mc-inline-inputs">
                                <span>From</span>
                                <input type="number" name="mc_pts_levels[<?php echo $id; ?>][min_points]" value="<?php echo esc_attr($level['min_points'] ?? 0); ?>" style="width:100px;">
                                <span>points</span>
                            </div>
                        </div>

                        <div class="mc-form-row">
                            <div class="mc-form-info">
                                <span class="mc-form-label">Add a badge image</span>
                                <span class="mc-form-desc">Upload a badge to identify this level.</span>
                            </div>
                            <div class="mc-form-control" style="display:flex; align-items:center; gap:15px;">
                                <input type="hidden" class="mc-badge-url-input" name="mc_pts_levels[<?php echo $id; ?>][badge_url]" value="<?php echo esc_url($level['badge_url'] ?? ''); ?>">
                                
                                <div class="mc-badge-preview" style="width:60px; height:60px; border-radius:6px; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f9f9f9;">
                                    <?php if(!empty($level['badge_url'])): ?>
                                        <img src="<?php echo esc_url($level['badge_url']); ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <span style="color:#aaa; font-size:12px;">No Image</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div>
                                    <button type="button" class="button mc-upload-badge-btn">Upload Image</button>
                                    <a href="#" class="mc-remove-badge" style="display:<?php echo empty($level['badge_url']) ? 'none' : 'inline-block'; ?>; color:#d63638; margin-left:10px; font-size:12px; text-decoration:none;">Remove</a>
                                </div>
                            </div>
                        </div>

                        <div class="mc-form-row" style="margin-bottom:0;">
                            <div class="mc-form-info"><span class="mc-form-label">Level text color</span></div>
                            <div class="mc-form-control">
                                <input type="color" class="mc-level-color-input" name="mc_pts_levels[<?php echo $id; ?>][color]" value="<?php echo esc_attr($level['color'] ?? '#1d2327'); ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <div id="mc-level-template" style="display:none;">
        <div class="mc-rule-card mc-existing-level" style="padding:0; overflow:hidden;">
            <input type="hidden" name="mc_pts_levels[{id}][id]" value="{id}">
            
            <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
                <div style="display:flex; align-items:center; gap:15px;" class="mc-header-controls">
                    <div class="mc-header-badge" style="width:30px; height:30px; border-radius:50%; background:#eee; border:1px solid #ccc;"></div>
                    <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-level-title-display">New Level</h3>
                </div>
                <div style="display:flex; align-items:center; gap:15px;">
                    <a href="#" class="mc-remove-level" style="color:#d63638; text-decoration:none; font-weight:600; font-size:13px;">Delete Level</a>
                    <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▲</span>
                </div>
            </div>

            <div class="mc-rule-card-body" style="padding:20px;">
                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Level name</span></div>
                    <div class="mc-form-control"><input type="text" class="mc-level-name-input" name="mc_pts_levels[{id}][name]" value="" style="width:100%; padding:6px; border:1px solid #8c8f94; border-radius:4px;" placeholder="e.g. Gold VIP"></div>
                </div>

                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Points to collect</span></div>
                    <div class="mc-form-control mc-inline-inputs">
                        <span>From</span>
                        <input type="number" name="mc_pts_levels[{id}][min_points]" value="0" style="width:100px;">
                        <span>points</span>
                    </div>
                </div>

                <div class="mc-form-row">
                    <div class="mc-form-info"><span class="mc-form-label">Add a badge image</span></div>
                    <div class="mc-form-control" style="display:flex; align-items:center; gap:15px;">
                        <input type="hidden" class="mc-badge-url-input" name="mc_pts_levels[{id}][badge_url]" value="">
                        
                        <div class="mc-badge-preview" style="width:60px; height:60px; border-radius:6px; border:1px dashed #ccc; display:flex; align-items:center; justify-content:center; overflow:hidden; background:#f9f9f9;">
                            <span style="color:#aaa; font-size:12px;">No Image</span>
                        </div>
                        
                        <div>
                            <button type="button" class="button mc-upload-badge-btn">Upload Image</button>
                            <a href="#" class="mc-remove-badge" style="display:none; color:#d63638; margin-left:10px; font-size:12px; text-decoration:none;">Remove</a>
                        </div>
                    </div>
                </div>

                <div class="mc-form-row" style="margin-bottom:0;">
                    <div class="mc-form-info"><span class="mc-form-label">Level text color</span></div>
                    <div class="mc-form-control">
                        <input type="color" class="mc-level-color-input" name="mc_pts_levels[{id}][color]" value="#1d2327">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Levels', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script>
jQuery(document).ready(function($) {
    
    // Ensure WordPress Media Uploader is loaded
    var mc_media_uploader;

    // Hover effect on headers
    $(document).on('mouseenter', '.mc-rule-card-header', function() { $(this).css('background', '#f5f5f5'); })
               .on('mouseleave', '.mc-rule-card-header', function() { $(this).css('background', '#fcfcfc'); });

    // Accordion Toggle
    $(document).on('click', '.mc-rule-card-header', function(e) {
        if($(e.target).closest('.mc-remove-level').length) return;
        
        let $body = $(this).siblings('.mc-rule-card-body');
        let $indicator = $(this).find('.mc-toggle-indicator');
        
        $body.slideToggle(200, function() {
            if($body.is(':visible')) { $indicator.text('▲'); } else { $indicator.text('▼'); }
        });
    });

    // Live Title & Color Update
    $(document).on('input', '.mc-level-name-input', function() {
        let val = $(this).val();
        $(this).closest('.mc-rule-card').find('.mc-level-title-display').text(val ? val : 'Unnamed Level');
    });
    
    $(document).on('input', '.mc-level-color-input', function() {
        let val = $(this).val();
        $(this).closest('.mc-rule-card').find('.mc-level-title-display').css('color', val);
    });

    // Add New Level
    $('#mc-add-new-level').on('click', function(e) {
        e.preventDefault();
        $('#mc-no-levels-msg').hide();
        let uniqueId = 'level_' + Date.now();
        let template = $('#mc-level-template').html().replace(/{id}/g, uniqueId);
        $('#mc-levels-container').append(template);
    });

    // Remove Level
    $(document).on('click', '.mc-remove-level', function(e) {
        e.preventDefault();
        if(confirm('Are you sure you want to delete this Level? Click Save Levels to permanently remove it.')) {
            $(this).closest('.mc-rule-card').slideUp(300, function() { $(this).remove(); });
        }
    });

    // Media Uploader
    $(document).on('click', '.mc-upload-badge-btn', function(e) {
        e.preventDefault();
        let button = $(this);
        let container = button.closest('.mc-form-control');
        
        if (mc_media_uploader) { mc_media_uploader.open(); return; }
        
        mc_media_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose a Badge Image',
            button: { text: 'Use this image' },
            multiple: false
        });

        mc_media_uploader.on('select', function() {
            let attachment = mc_media_uploader.state().get('selection').first().toJSON();
            
            // Set Hidden Input
            container.find('.mc-badge-url-input').val(attachment.url);
            
            // Update Preview
            container.find('.mc-badge-preview').html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;">');
            
            // Update Header Icon
            let headerBadge = container.closest('.mc-rule-card').find('.mc-header-badge');
            if(headerBadge.length) {
                headerBadge.replaceWith('<img src="' + attachment.url + '" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border:1px solid #ccc;">');
            } else {
                container.closest('.mc-rule-card').find('.mc-rule-card-header img').attr('src', attachment.url);
            }

            // Show Remove button
            container.find('.mc-remove-badge').show();
        });

        mc_media_uploader.open();
    });

    // Remove Badge
    $(document).on('click', '.mc-remove-badge', function(e) {
        e.preventDefault();
        let container = $(this).closest('.mc-form-control');
        
        container.find('.mc-badge-url-input').val('');
        container.find('.mc-badge-preview').html('<span style="color:#aaa; font-size:12px;">No Image</span>');
        $(this).hide();
        
        // Reset header icon
        container.closest('.mc-rule-card').find('.mc-rule-card-header img').replaceWith('<div style="width:30px; height:30px; border-radius:50%; background:#eee; border:1px solid #ccc;" class="mc-header-badge"></div>');
    });
});
</script>