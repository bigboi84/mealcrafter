<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div style="margin-bottom:20px; display:flex; justify-content:flex-end;">
    <button type="button" id="mc-add-new-giveaway" style="background:#2271b1; color:#fff; border:none; padding:8px 16px; border-radius:4px; font-weight:600; cursor:pointer; font-size:13px;">+ Add Smart Trigger</button>
</div>

<form method="post" action="options.php" id="mc-loyalty-giveaways-form">
    <?php settings_fields( 'mc_prod_giveaway_group' ); ?>
    
    <div style="display:none;">
        <input type="hidden" name="mc_pts_auto_giveaways[__empty__][id]" value="__empty__">
    </div>
    
    <div id="mc-giveaways-container">
        <p class="description" style="margin-bottom:25px; font-size:14px;">Build "Smart Triggers" to drive app downloads and higher order values. When a trigger condition is met, a free item is automatically injected into the customer's cart.</p>
        
        <?php 
        $all_giveaways = get_option('mc_pts_auto_giveaways', []); 
        if (!is_array($all_giveaways)) $all_giveaways = [];

        $giveaways = array_filter($all_giveaways, function($g) {
            return !empty($g['id']) && $g['id'] !== '__empty__' && $g['id'] !== '{id}';
        });

        if(empty($giveaways)) {
            echo '<div class="mc-rule-card" id="mc-no-giveaways-msg" style="padding:40px; text-align:center; background:#f9f9f9;"><p style="margin:0; color:#777; font-size:15px;">No smart triggers created yet. Click "+ Add Smart Trigger" to begin.</p></div>';
        } else {
            foreach($giveaways as $index => $item) {
                $id = esc_attr($item['id']);
                $trigger_type = $item['trigger_type'] ?? 'spend';
                ?>
                <div class="mc-rule-card mc-existing-rule" style="padding:0; overflow:hidden;">
                    <input type="hidden" class="mc-rule-id" name="mc_pts_auto_giveaways[<?php echo $id; ?>][id]" value="<?php echo $id; ?>">
                    
                    <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
                        <div style="display:flex; align-items:center; gap:15px;">
                            <label class="mc-toggle-switch">
                                <input type="hidden" name="mc_pts_auto_giveaways[<?php echo $id; ?>][active]" value="no">
                                <input type="checkbox" name="mc_pts_auto_giveaways[<?php echo $id; ?>][active]" value="yes" <?php checked($item['active'] ?? 'yes', 'yes'); ?>>
                                <span class="mc-slider"></span>
                            </label>
                            <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display"><?php echo esc_html($item['name'] ?: 'Unnamed Trigger'); ?></h3>
                            <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-rule-type-badge">
                                <?php echo esc_html(str_replace('_', ' ', $trigger_type)); ?>
                            </span>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <button type="button" class="mc-remove-giveaway" style="background:transparent; border:none; color:#d63638; text-decoration:none; font-weight:600; font-size:13px; cursor:pointer;">Delete</button>
                            <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▼</span>
                        </div>
                    </div>

                    <div class="mc-rule-card-body" style="display:none; padding:20px;">
                        
                        <div class="mc-form-row">
                            <span class="mc-form-label">Campaign Name</span>
                            <input type="text" class="mc-rule-name-input" name="mc_pts_auto_giveaways[<?php echo $id; ?>][name]" value="<?php echo esc_attr($item['name'] ?? ''); ?>" style="width:100%; max-width:400px;" placeholder="e.g. App First Order Gift">
                        </div>

                        <div class="mc-form-row" style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                            <span class="mc-form-label">Trigger Condition</span>
                            <select class="mc-trigger-type-select" name="mc_pts_auto_giveaways[<?php echo $id; ?>][trigger_type]" style="width:100%; max-width:400px; margin-bottom:15px;">
                                <option value="spend" <?php selected($trigger_type, 'spend'); ?>>Spend Milestone (e.g. Spend $50)</option>
                                <option value="first_order" <?php selected($trigger_type, 'first_order'); ?>>First-Time Order (0 previous orders)</option>
                                <option value="user_meta" <?php selected($trigger_type, 'user_meta'); ?>>App Task / Profile Unlock (User Meta Flag)</option>
                                <option value="bogo" <?php selected($trigger_type, 'bogo'); ?>>Specific Product Purchase (BOGO)</option>
                            </select>

                            <div class="mc-trigger-input mc-trigger-spend" style="<?php echo $trigger_type === 'spend' ? 'display:block;' : 'display:none;'; ?>">
                                <span class="mc-form-label" style="font-size:12px;">Cart Subtotal Threshold ($)</span>
                                <input type="number" step="0.01" name="mc_pts_auto_giveaways[<?php echo $id; ?>][threshold]" value="<?php echo esc_attr($item['threshold'] ?? '50'); ?>" style="width:150px;">
                            </div>

                            <div class="mc-trigger-input mc-trigger-first_order" style="<?php echo $trigger_type === 'first_order' ? 'display:block;' : 'display:none;'; ?>">
                                <span style="font-size:13px; color:#666;"><em>This triggers automatically if the logged-in user has zero previous completed orders. Perfect for app onboarding.</em></span>
                            </div>

                            <div class="mc-trigger-input mc-trigger-user_meta" style="<?php echo $trigger_type === 'user_meta' ? 'display:block;' : 'display:none;'; ?>">
                                <span class="mc-form-label" style="font-size:12px;">User Meta Key (The App Flag)</span>
                                <span class="mc-form-desc" style="font-size:11px; margin-bottom:5px;">The plugin will look for this meta key on the user's profile. Once the gift is added, the flag is removed.</span>
                                <input type="text" name="mc_pts_auto_giveaways[<?php echo $id; ?>][meta_key]" value="<?php echo esc_attr($item['meta_key'] ?? '_mc_app_task_complete'); ?>" style="width:100%; max-width:300px;">
                            </div>

                            <div class="mc-trigger-input mc-trigger-bogo" style="<?php echo $trigger_type === 'bogo' ? 'display:block;' : 'display:none;'; ?>">
                                <span class="mc-form-label" style="font-size:12px;">Required Product in Cart</span>
                                <span class="mc-form-desc" style="font-size:11px; margin-bottom:5px;">The customer must have this specific item in their cart to unlock the free gift.</span>
                                <?php 
                                $req_id = $item['req_product'] ?? '';
                                echo '<select name="mc_pts_auto_giveaways['.$id.'][req_product]" class="mc-select2 wc-product-search" style="width:100%; max-width:400px;">';
                                if($req_id) {
                                    $p = wc_get_product($req_id);
                                    if($p) echo '<option value="'.$req_id.'" selected>'.$p->get_name().'</option>';
                                }
                                echo '</select>';
                                ?>
                            </div>
                        </div>

                        <div class="mc-form-row">
                            <span class="mc-form-label" style="color:#d63638;">The Free Gift</span>
                            <span class="mc-form-desc" style="margin-bottom:5px;">This item will be injected into the cart with a price of $0.00.</span>
                            <?php 
                            $gift_id = $item['gift_id'] ?? '';
                            echo '<select name="mc_pts_auto_giveaways['.$id.'][gift_id]" class="mc-select2 wc-product-search" style="width:100%; max-width:400px;">';
                            if($gift_id) {
                                $p = wc_get_product($gift_id);
                                if($p) echo '<option value="'.$gift_id.'" selected>'.$p->get_name().'</option>';
                            }
                            echo '</select>';
                            ?>
                        </div>

                        <hr style="margin:20px 0; border:0; border-bottom:1px solid #eee;">

                        <div class="mc-form-row">
                            <span class="mc-form-label">Checkout Notification Message</span>
                            <span class="mc-form-desc">This green success message pops up when the item is unlocked.</span>
                            <input type="text" name="mc_pts_auto_giveaways[<?php echo $id; ?>][msg]" value="<?php echo esc_attr($item['msg'] ?? 'Congrats! You unlocked a free gift!'); ?>" style="width:100%;">
                        </div>

                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>

    <p class="submit" style="margin-top:20px; padding-top:20px; border-top:1px solid #eee;">
        <?php submit_button('Save Triggers', 'primary', 'submit', false, ['style' => 'background:#2271b1; border:none; padding:8px 20px; border-radius:4px; font-weight:600; font-size:14px;']); ?>
    </p>
</form>

<script type="text/template" id="mc-giveaway-template">
    <div class="mc-rule-card mc-existing-rule" style="padding:0; overflow:hidden;">
        <input type="hidden" class="mc-rule-id" name="mc_pts_auto_giveaways[{id}][id]" value="{id}">
        <div class="mc-rule-card-header" style="display:flex; justify-content:space-between; align-items:center; padding:15px 20px; background:#fcfcfc; border-bottom:1px solid #eee; margin:0; cursor:pointer;">
            <div style="display:flex; align-items:center; gap:15px;">
                <label class="mc-toggle-switch">
                    <input type="hidden" name="mc_pts_auto_giveaways[{id}][active]" value="no">
                    <input type="checkbox" name="mc_pts_auto_giveaways[{id}][active]" value="yes" checked>
                    <span class="mc-slider"></span>
                </label>
                <h3 style="margin:0; font-size:15px; color:#1d2327;" class="mc-rule-title-display">New Smart Trigger</h3>
                <span style="font-size:11px; background:#e5e5e5; padding:2px 8px; border-radius:12px; color:#555; text-transform:uppercase;" class="mc-rule-type-badge">SPEND</span>
            </div>
            <div style="display:flex; align-items:center; gap:15px;">
                <button type="button" class="mc-remove-giveaway" style="background:transparent; border:none; color:#d63638; text-decoration:none; font-weight:600; font-size:13px; cursor:pointer;">Delete</button>
                <span class="mc-toggle-indicator" style="color:#8c8f94; font-size:12px;">▲</span>
            </div>
        </div>
        <div class="mc-rule-card-body" style="padding:20px;">
            
            <div class="mc-form-row">
                <span class="mc-form-label">Campaign Name</span>
                <input type="text" class="mc-rule-name-input" name="mc_pts_auto_giveaways[{id}][name]" value="" style="width:100%; max-width:400px;" placeholder="e.g. App First Order Gift">
            </div>

            <div class="mc-form-row" style="background:#f9f9f9; padding:15px; border-radius:6px; border:1px solid #eee;">
                <span class="mc-form-label">Trigger Condition</span>
                <select class="mc-trigger-type-select" name="mc_pts_auto_giveaways[{id}][trigger_type]" style="width:100%; max-width:400px; margin-bottom:15px;">
                    <option value="spend" selected>Spend Milestone (e.g. Spend $50)</option>
                    <option value="first_order">First-Time Order (0 previous orders)</option>
                    <option value="user_meta">App Task / Profile Unlock (User Meta Flag)</option>
                    <option value="bogo">Specific Product Purchase (BOGO)</option>
                </select>

                <div class="mc-trigger-input mc-trigger-spend" style="display:block;">
                    <span class="mc-form-label" style="font-size:12px;">Cart Subtotal Threshold ($)</span>
                    <input type="number" step="0.01" name="mc_pts_auto_giveaways[{id}][threshold]" value="50" style="width:150px;">
                </div>
                <div class="mc-trigger-input mc-trigger-first_order" style="display:none;">
                    <span style="font-size:13px; color:#666;"><em>This triggers automatically if the logged-in user has zero previous completed orders. Perfect for app onboarding.</em></span>
                </div>
                <div class="mc-trigger-input mc-trigger-user_meta" style="display:none;">
                    <span class="mc-form-label" style="font-size:12px;">User Meta Key (The App Flag)</span>
                    <span class="mc-form-desc" style="font-size:11px; margin-bottom:5px;">The plugin will look for this meta key on the user's profile. Once the gift is added, the flag is removed.</span>
                    <input type="text" name="mc_pts_auto_giveaways[{id}][meta_key]" value="_mc_app_task_complete" style="width:100%; max-width:300px;">
                </div>
                <div class="mc-trigger-input mc-trigger-bogo" style="display:none;">
                    <span class="mc-form-label" style="font-size:12px;">Required Product in Cart</span>
                    <span class="mc-form-desc" style="font-size:11px; margin-bottom:5px;">The customer must have this specific item in their cart to unlock the free gift.</span>
                    <select name="mc_pts_auto_giveaways[{id}][req_product]" class="mc-select2 wc-product-search" style="width:100%; max-width:400px;" data-placeholder="Search for required product..."></select>
                </div>
            </div>

            <div class="mc-form-row">
                <span class="mc-form-label" style="color:#d63638;">The Free Gift</span>
                <span class="mc-form-desc" style="margin-bottom:5px;">This item will be injected into the cart with a price of $0.00.</span>
                <select name="mc_pts_auto_giveaways[{id}][gift_id]" class="mc-select2 wc-product-search" style="width:100%; max-width:400px;" data-placeholder="Search for reward product..."></select>
            </div>

            <hr style="margin:20px 0; border:0; border-bottom:1px solid #eee;">

            <div class="mc-form-row">
                <span class="mc-form-label">Checkout Notification Message</span>
                <input type="text" name="mc_pts_auto_giveaways[{id}][msg]" value="Congrats! You unlocked a free gift!" style="width:100%;">
            </div>

        </div>
    </div>
</script>

<script>
jQuery(document).ready(function($) {
    
    // SAFE SELECT2 INITIALIZATION
    function initSelect2(container) {
        try {
            if(!$.fn.select2) return;
            let nonce = typeof wc_enhanced_select_params !== 'undefined' ? wc_enhanced_select_params.search_products_nonce : '';
            container.find('.wc-product-search').filter(':not(.select2-hidden-accessible)').select2({
                allowClear: true, minimumInputLength: 3,
                ajax: { url: ajaxurl, dataType: 'json', delay: 250, data: function(params) { return { term: params.term, action: 'woocommerce_json_search_products_and_variations', security: nonce }; }, processResults: function(data) { var terms = []; if (data) { $.each(data, function(id, text) { terms.push({ id: id, text: text }); }); } return { results: terms }; }, cache: true }
            });
        } catch(err) { console.error("Select2 Error:", err); }
    }

    initSelect2($('#mc-giveaways-container'));

    // Accordion
    $(document).on('click', '.mc-rule-card-header', function(e) {
        if($(e.target).closest('.mc-remove-giveaway, .mc-toggle-switch').length) return;
        let $body = $(this).siblings('.mc-rule-card-body');
        let $indicator = $(this).find('.mc-toggle-indicator');
        $body.slideToggle(200, function() {
            if($body.is(':visible')) { $indicator.text('▲'); } else { $indicator.text('▼'); }
        });
    });

    // Update Badge Name
    $(document).on('input', '.mc-rule-name-input', function() {
        let val = $(this).val();
        $(this).closest('.mc-rule-card').find('.mc-rule-title-display').text(val ? val : 'Unnamed Trigger');
    });

    // Dynamic Trigger Type Inputs
    $(document).on('change', '.mc-trigger-type-select', function() {
        let $card = $(this).closest('.mc-rule-card');
        let val = $(this).val();
        
        $card.find('.mc-rule-type-badge').text(val.replace('_', ' '));
        $card.find('.mc-trigger-input').hide();
        $card.find('.mc-trigger-' + val).show();
    });

    // Add New
    $('#mc-add-new-giveaway').on('click', function(e) {
        e.preventDefault();
        $('#mc-no-giveaways-msg').hide();
        let uniqueId = 'give_' + Date.now();
        let template = $('#mc-giveaway-template').html().replace(/{id}/g, uniqueId);
        $('#mc-giveaways-container').append(template);
        let $newCard = $('#mc-giveaways-container .mc-existing-rule').last();
        initSelect2($newCard);
        $newCard.find('.mc-rule-card-body').slideDown();
    });

    // Delete
    $(document).on('click', '.mc-remove-giveaway', function(e) {
        e.preventDefault();
        if(confirm('Delete this smart trigger? Click Save Triggers to confirm.')) {
            let $card = $(this).closest('.mc-rule-card');
            $card.find('input, select').remove();
            $card.slideUp(250, function(){ $(this).remove(); });
        }
    });
});
</script>