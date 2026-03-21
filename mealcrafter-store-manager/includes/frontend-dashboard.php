<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'template_redirect', function() {
    $manager_page_id = get_option('mc_manager_page_id');
    if ( $manager_page_id && is_page($manager_page_id) ) {
        if ( !is_user_logged_in() || !current_user_can('manage_woocommerce') ) { 
            wp_redirect( wc_get_page_permalink( 'myaccount' ) );
            exit;
        }
        mc_render_full_portal_html(); exit;
    }
});

function mc_render_full_portal_html() {
    $current_user = wp_get_current_user();
    $view_type = get_option('mc_order_view_type', 'modal');
    $theme = get_option('mc_dashboard_theme', 'light');
    $lookback = get_option('mc_order_lookback', '30');
    $per_page = get_option('mc_orders_per_page', '20');
    $pill_style = get_option('mc_status_pill_style', 'text');
    $enabled_cols = get_option('mc_manager_columns', ['status','order_id','customer','purchased','total','actions']);
    $notify_interval = get_option('mc_check_interval', '15') * 1000;
    
    $can_edit_items = get_option('mc_enable_item_editing', 'yes') === 'yes';
    $can_create_order = get_option('mc_enable_create_order', 'yes') === 'yes';
    
    $paged = isset($_GET['mc_page']) ? max(1, intval($_GET['mc_page'])) : 1;
    
    $query_args = [
        'limit' => $per_page, 'page' => $paged, 'paginate' => true,
        'date_created' => '>'.date('Y-m-d', strtotime("-$lookback days")),
        'status' => ['processing', 'pending', 'on-hold', 'wc-prep-pickup', 'wc-prep-deliv', 'completed', 'cancelled'],
        'orderby' => 'date', 'order' => 'DESC'
    ];

    if ( ! current_user_can('administrator') ) {
        $assigned_locations = get_user_meta($current_user->ID, '_mc_assigned_stores', true) ?: [];
        if ( ! empty($assigned_locations) ) {
            $query_args['meta_key'] = '_mc_assigned_location';
            $query_args['meta_value'] = $assigned_locations;
            $query_args['meta_compare'] = 'IN';
        }
    }

    $results = wc_get_orders($query_args);
    $orders = $results->orders;
    $total_pages = $results->max_num_pages;

    $sidebar_bg = get_option('mc_bg_sidebar') ? "url('".get_option('mc_bg_sidebar')."')" : get_option('mc_color_sidebar', '#2c3e50');
    $color_primary = get_option('mc_color_primary', '#3498db');
    $color_username = get_option('mc_color_username', '#ffffff');
    $brand_name = get_option('mc_brand_name', get_bloginfo('name'));
    $brand_logo = get_option('mc_brand_logo');
    
    $c_pend = get_option('mc_color_status_pending', '#e67e22');
    $c_proc = get_option('mc_color_status_processing', '#f1c40f');
    $c_comp = get_option('mc_color_status_completed', '#95a5a6');
    $c_canc = get_option('mc_color_status_cancelled', '#e74c3c');
    $c_prep_p = get_option('mc_color_status_prep_pickup', '#3498db');
    $c_prep_d = get_option('mc_color_status_prep_deliv', '#9b59b6');
    $c_out_d = get_option('mc_color_status_out_deliv', '#1abc9c');

    $qr_active = class_exists('MC_QR_Scanner');
    $alerts_active = class_exists('MC_Standalone_Alerts_Engine');
    ?>
    <!DOCTYPE html>
    <html class="mc-theme-<?php echo esc_attr($theme); ?>">
    <head>
        <?php wp_head(); ?>
        <?php if($qr_active): ?><script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script><?php endif; ?>
        <style>
            :root { --mc-primary: <?php echo $color_primary; ?>; --mc-primary-light: <?php echo $color_primary; ?>1A; }
            html.mc-theme-light { --mc-bg-main: #f4f7fa; --mc-bg-card: #ffffff; --mc-bg-alt: #f9f9f9; --mc-text-main: #1e2d3b; --mc-text-muted: #666666; --mc-border-color: #eeeeee; --mc-border-dark: #333333; --mc-shadow: 0 4px 15px rgba(0,0,0,0.02); }
            html.mc-theme-dark { --mc-bg-main: #121212; --mc-bg-card: #1e1e1e; --mc-bg-alt: #2a2a2a; --mc-text-main: #ffffff; --mc-text-muted: #aaaaaa; --mc-border-color: #333333; --mc-border-dark: #555555; --mc-shadow: 0 4px 15px rgba(0,0,0,0.5); }

            body, html { margin:0; height:100%; background:var(--mc-bg-main); overflow:hidden; font-family:'Inter',sans-serif; color:var(--mc-text-main); transition: background 0.3s; }
            #mc-portal { display:flex; height:100vh; }
            body.admin-bar #mc-portal { height: calc(100vh - 32px); }
            .mc-sidebar { width:280px; background: <?php echo $sidebar_bg; ?> center/cover no-repeat; color:#fff; flex-shrink:0; display:flex; flex-direction:column; z-index:999; position:relative; }
            .mc-sidebar::before { content:''; position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:-1; <?php if(!get_option('mc_bg_sidebar')) echo 'display:none;'; ?>}
            .mc-sidebar-top { padding:40px 20px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.1); }
            .mc-brand-logo { max-width:140px; max-height:80px; margin-bottom:15px; }
            .mc-sidebar-title { color: <?php echo $color_username; ?>; font-size:18px; font-weight:800; margin:0 0 5px; }
            .mc-sidebar-user { color: <?php echo $color_username; ?>; opacity:0.8; font-size:13px; }
            .mc-nav { flex-grow:1; display:flex; flex-direction:column; padding-top:20px; }
            .mc-nav-link { display:flex; align-items:center; padding:18px 30px; color:#ecf0f1; text-decoration:none; font-weight:600; border-left:4px solid transparent; cursor:pointer; transition:0.2s; }
            .mc-nav-link:hover { background:rgba(255,255,255,0.1); color:#fff; }
            .mc-nav-link.active { background:var(--mc-primary-light); color:var(--mc-primary); border-left-color:var(--mc-primary); }
            .mc-main { flex-grow:1; display:flex; flex-direction:column; overflow:hidden; position:relative; }
            .mc-header { padding:20px 40px; background:var(--mc-bg-card); display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--mc-border-color); }
            .mc-title-area { display:flex; align-items:center; gap:20px; }
            .mc-title-area h1 { margin:0; font-size:26px; font-weight:900; color:var(--mc-text-main); }
            .mc-bell-wrapper { position:relative; cursor:pointer; padding:5px; border-radius:50%; transition:0.3s; }
            .mc-bell-wrapper:hover { background:var(--mc-bg-alt); }
            .mc-bell-icon { font-size:26px; width:26px; height:26px; color:var(--mc-text-muted); transition:0.3s; }
            .mc-bell-dot { position:absolute; top:2px; right:2px; width:10px; height:10px; border-radius:50%; background:#e74c3c; border:2px solid var(--mc-bg-card); transition:0.3s; }
            .mc-search-box { display:flex; align-items:center; background:var(--mc-bg-alt); padding:10px 20px; border-radius:30px; width:350px; border:1px solid var(--mc-border-color); }
            .mc-search-box input { border:none; background:transparent; outline:none; font-size:14px; width:100%; margin-left:10px; color:var(--mc-text-main);}
            .mc-body { padding:40px; overflow-y:auto; flex-grow:1; }
            .mc-table { width:100%; background:var(--mc-bg-card); border-radius:15px; border-collapse:collapse; box-shadow:var(--mc-shadow); }
            .mc-table th { padding:20px; text-align:left; font-size:11px; text-transform:uppercase; color:var(--mc-text-muted); background:var(--mc-bg-alt); border-bottom:1px solid var(--mc-border-color); }
            .mc-table td { padding:18px 20px; border-bottom:1px solid var(--mc-border-color); font-size:14px; font-weight:600; color:var(--mc-text-main); }
            .mc-theme-card { background:var(--mc-bg-card); border:1px solid var(--mc-border-color); padding:25px; border-radius:12px; box-shadow:var(--mc-shadow); }
            .mc-status-dropdown-wrap { position: relative; display: inline-block; }
            .mc-status-dropdown-wrap select { appearance: none; -webkit-appearance: none; padding: 12px 40px 12px 20px; border-radius: 8px; font-weight: 800; font-size: 14px; border: 2px solid var(--mc-border-color); background: var(--mc-bg-card); color: var(--mc-text-main); cursor: pointer; outline: none; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
            .mc-status-dropdown-wrap::after { content: '\f347'; font-family: 'dashicons'; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); pointer-events: none; color: var(--mc-text-muted); }
            .mc-combo-meta-wrapper ul { list-style:none; padding:0; margin:0; } 
            .mc-combo-meta-wrapper li { font-size:13px; margin-bottom:5px; } 
            .mc-combo-meta-wrapper p { margin:0; display:inline; }
            .mc-combo-hover-item { position: relative; cursor: help; border-bottom: 1px dashed #e74c3c; color: #e74c3c; font-weight: bold; transition: 0.2s; white-space: nowrap; display: inline-block; }
            .mc-combo-hover-item:hover { background: #fff3cd; color: #333; }
            .mc-combo-hover-img { display: none; position: absolute; top: 100%; left: 0; width: 100px; height: 100px; object-fit: cover; border-radius: 8px; box-shadow: 0 5px 25px rgba(0,0,0,0.5); z-index: 999999 !important; pointer-events: none; border: 3px solid #e74c3c; background: #fff; margin-top: 5px; }
            .mc-combo-hover-item:hover .mc-combo-hover-img { display: block; }

            <?php if ($pill_style === 'text'): ?>
                .mc-status-pill { padding:6px 12px; border-radius:6px; font-size:11px; text-transform:uppercase; font-weight:800; color:#fff; display:inline-block; text-align:center; }
            <?php else: ?>
                .mc-status-pill { width:12px; height:12px; border-radius:50%; display:inline-block; overflow:hidden; text-indent:-999px; }
            <?php endif; ?>
            
            .status-pending { background: <?php echo $c_pend; ?>; }
            .status-on-hold { background: #f39c12; }
            .status-processing { background: <?php echo $c_proc; ?>; }
            .status-completed { background: <?php echo $c_comp; ?>; }
            .status-cancelled { background: <?php echo $c_canc; ?>; }
            .status-prep-pickup { background: <?php echo $c_prep_p; ?>; }
            .status-prep-deliv { background: <?php echo $c_prep_d; ?>; }
            .status-out-deliv { background: <?php echo $c_out_d; ?>; }
            
            .mc-action-icon { font-size:22px; color:var(--mc-primary); cursor:pointer; margin: 0 8px; transition:0.2s; }
            .mc-action-icon:hover { transform: scale(1.15); filter: brightness(1.1); }
            
            .mc-pagination { display:flex; justify-content:center; align-items:center; margin-top:30px; gap:10px; }
            .mc-btn-page { background:var(--mc-bg-card); color:var(--mc-text-main); padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:bold; box-shadow:0 4px 10px rgba(0,0,0,0.05); transition:0.2s; }
            .mc-btn-page:hover { background:var(--mc-primary); color:#fff; }

            #mc-full-overlay { position:absolute; top:0; left:0; width:100%; height:100%; background:var(--mc-bg-main); z-index:100; display:none; padding:40px; overflow-y:auto; box-sizing:border-box; }
            .mc-modal { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); display:none; align-items:center; justify-content:center; z-index:9999; }
            .mc-modal-inner { background:var(--mc-bg-main); width:95%; max-width:1100px; border-radius:20px; padding:40px; position:relative; min-height:60vh; max-height:90vh; overflow-y:auto; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
            
            #mc-combo-build-modal { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999999; display:none; align-items:center; justify-content:center; }
            #mc-combo-build-content { background:var(--mc-bg-main); width:95%; max-width:1300px; height:90vh; border-radius:15px; overflow-y:auto; position:relative; box-shadow:0 15px 50px rgba(0,0,0,0.5); }
            #mc-combo-build-close { position:absolute; top:20px; right:20px; font-size:30px; cursor:pointer; color:#999; z-index:1000; }
        </style>
        <script>var mc_portal_nonce = '<?php echo wp_create_nonce('mc_portal_secure'); ?>';</script>
    </head>
    <body <?php body_class(); ?>>
    <div id="mc-portal">
        <aside class="mc-sidebar">
            <div class="mc-sidebar-top">
                <?php if($brand_logo): ?><img src="<?php echo $brand_logo; ?>" class="mc-brand-logo"><?php endif; ?>
                <h3 class="mc-sidebar-title"><?php echo esc_html($brand_name); ?></h3>
                <div class="mc-sidebar-user">Staff: <?php echo $current_user->display_name; ?></div>
            </div>
            
            <nav class="mc-nav">
                <a href="<?php echo home_url(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)); ?>" class="mc-nav-link active"><span class="dashicons dashicons-cart" style="margin-right:8px;"></span> Orders</a>
                <?php if ($can_create_order): ?>
                    <a href="#" id="mc-create-draft-order" class="mc-nav-link"><span class="dashicons dashicons-plus" style="margin-right:8px;"></span> Create New Order</a>
                <?php endif; ?>
            </nav>

            <div style="margin-top:auto; padding-bottom:20px;">
                <div class="mc-nav-link" id="mc-theme-toggle" style="border-top:1px solid rgba(255,255,255,0.1);">
                    <span class="dashicons dashicons-visibility" style="margin-right:8px;"></span> Toggle Dark Mode
                </div>
                <a href="<?php echo wp_logout_url(home_url()); ?>" class="mc-nav-link" style="border-top:1px solid rgba(255,255,255,0.1);">
                    <span class="dashicons dashicons-exit" style="margin-right:8px;"></span> Logout
                </a>
            </div>
        </aside>
        
        <main class="mc-main">
            <header class="mc-header">
                <div class="mc-title-area">
                    <h1>Dashboard</h1>
                    <?php if($qr_active): ?>
                    <div id="mc-trigger-camera" class="mc-bell-wrapper" title="Scan QR Code" style="margin-left:15px;">
                        <span class="dashicons dashicons-camera mc-bell-icon"></span>
                    </div>
                    <?php endif; ?>
                    <?php if($alerts_active): ?>
                    <div id="mc-bell-toggle" class="mc-bell-wrapper" title="Click to Enable Notifications">
                        <span class="dashicons dashicons-bell mc-bell-icon"></span>
                        <span id="mc-bell-dot" class="mc-bell-dot"></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mc-search-box">
                    <span class="dashicons dashicons-search" style="color:var(--mc-text-muted);"></span>
                    <input type="text" id="mc-search-input" placeholder="Search order #, customer name, or location...">
                </div>
            </header>
            <div class="mc-body">
                <table class="mc-table">
                    <thead><tr>
                        <?php foreach($enabled_cols as $c) {
                            $align = ($c === 'actions') ? 'text-align:center;' : '';
                            echo "<th style='$align'>".strtoupper($c)."</th>"; 
                        } ?>
                    </tr></thead>
                    <tbody id="mc-stream">
                        <?php foreach($orders as $o): $c_name = $o->get_billing_first_name().' '.$o->get_billing_last_name(); ?>
                        <tr id="row-<?php echo $o->get_id(); ?>">
                            <?php if(in_array('status',$enabled_cols)): ?>
                                <td><span class='mc-status-pill status-<?php echo $o->get_status(); ?>'><?php echo ($pill_style === 'text') ? wc_get_order_status_name($o->get_status()) : ''; ?></span></td>
                            <?php endif; ?>
                            <?php if(in_array('order_id',$enabled_cols)) echo "<td><a class='view-trigger' data-id='".$o->get_id()."' style='color:var(--mc-primary); cursor:pointer; text-decoration:none;'>#".$o->get_id()."</a></td>"; ?>
                            <?php if(in_array('customer',$enabled_cols)) echo "<td>$c_name</td>"; ?>
                            <?php if(in_array('purchased',$enabled_cols)) echo "<td>".$o->get_item_count()." items</td>"; ?>
                            <?php if(in_array('ship',$enabled_cols)) echo "<td><a class='view-trigger' data-id='".$o->get_id()."' style='color:var(--mc-text-muted); cursor:pointer; text-decoration:none; font-size:12px;'>".strtoupper($o->get_meta('_mc_order_method') ?: ($o->get_shipping_method() ?: 'Pickup'))."</a><br><small>".$o->get_shipping_city()."</small></td>"; ?>
                            <?php if(in_array('date',$enabled_cols)) echo "<td>".($o->get_date_created() ? $o->get_date_created()->date('M j') : '')."</td>"; ?>
                            <?php if(in_array('total',$enabled_cols)) echo "<td>".$o->get_formatted_order_total()."</td>"; ?>
                            <?php if(in_array('actions',$enabled_cols)): ?>
                                <td style="text-align:center;"><span class="dashicons dashicons-visibility mc-action-icon view-trigger" data-id="<?php echo $o->get_id(); ?>"></span></td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="mc-pagination">
                        <?php if ($paged > 1): ?><a href="?mc_page=<?php echo $paged - 1; ?>" class="mc-btn-page">&laquo; Prev</a><?php endif; ?>
                        <span style="font-weight:bold; color:var(--mc-text-muted);">Page <?php echo $paged; ?> of <?php echo $total_pages; ?></span>
                        <?php if ($paged < $total_pages): ?><a href="?mc_page=<?php echo $paged + 1; ?>" class="mc-btn-page">Next &raquo;</a><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div id="mc-full-overlay"><div onclick="jQuery('#mc-full-overlay').fadeOut(); window.history.pushState(null, '', window.location.pathname);" style="cursor:pointer; font-weight:bold; margin-bottom:20px; color:var(--mc-primary);">← BACK TO DASHBOARD</div><div id="mc-full-content"></div></div>
        </main>
    </div>
    
    <div id="mc-modal" class="mc-modal"><div class="mc-modal-inner"><span class="mc-modal-close-trigger" style="position:absolute; top:20px; right:20px; font-size:30px; cursor:pointer; color:#999; z-index:100;">&times;</span><div id="mc-modal-data"></div></div></div>

    <div id="mc-combo-build-modal">
        <div id="mc-combo-build-content">
            <span id="mc-combo-build-close">×</span>
            <div id="mc-combo-render-area"></div>
        </div>
    </div>

    <?php if($qr_active): ?>
    <div id="mc-camera-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:9999999; align-items:center; justify-content:center;">
        <div style="background:#fff; padding:30px; border-radius:15px; text-align:center; width:90%; max-width:500px;">
            <h2 style="margin:0 0 20px; color:#333;">Scan QR Code</h2>
            <div id="mc-reader" style="width:100%; margin-bottom:20px;"></div>
            <button id="mc-close-camera" style="padding:10px 20px; background:#e74c3c; color:#fff; border:none; border-radius:8px; font-weight:bold; cursor:pointer; width:100%;">CANCEL</button>
        </div>
    </div>
    <?php endif; ?>

    <?php if($alerts_active): ?>
    <div id="mc-new-order-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:999999; align-items:center; justify-content:center;">
        <div id="mc-custom-popup-bg" style="background:#ffffff; padding:40px; border-radius:15px; text-align:center; max-width:400px; width:90%; box-shadow:0 15px 35px rgba(0,0,0,0.5);">
            <div id="mc-custom-icon-wrapper" style="margin:0 auto 20px; display:inline-block; border-radius:50%; overflow:hidden;">
                <span id="mc-default-bell-icon" class="dashicons dashicons-bell" style="background:#e74c3c; color:#fff; width:70px; height:70px; display:flex; align-items:center; justify-content:center; font-size:35px; animation: mcPulseAlert 2s infinite;"></span>
                <img id="mc-custom-icon-img" src="" style="display:none; width:70px; height:70px; object-fit:cover; animation: mcPulseAlert 2s infinite;">
            </div>
            <h2 id="mc-new-order-title" style="margin:0 0 10px; font-weight:900; color:#333333; font-size:24px;">New Order!</h2>
            <p id="mc-new-order-text" style="color:#666666; font-size:16px; margin-bottom:30px; line-height:1.5;"></p>
            <div style="display:flex; gap:15px; justify-content:center;">
                <button id="mc-new-order-close" style="padding:12px 25px; border-radius:8px; border:2px solid #ddd; background:transparent; color:inherit; font-weight:bold; cursor:pointer;">CLOSE</button>
                <button id="mc-new-order-accept" class="mc-trigger-accept-view" data-target="" style="padding:12px 25px; border-radius:8px; border:none; background:#2ecc71; color:#fff; font-weight:bold; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.2);">ACCEPT & VIEW</button>
            </div>
        </div>
    </div>
    <audio id="mc-alert-audio" preload="auto"></audio>
    <?php endif; ?>

    <?php wp_footer(); ?>
    <script>
    jQuery(document).ready(function($) {
        let viewStyle = '<?php echo esc_js($view_type); ?>';

        if(localStorage.getItem('mc_show_images') === 'yes') { 
            $('body').addClass('mc-show-item-images'); 
        }

        <?php if($alerts_active): ?>
        let audioNode = document.getElementById('mc-alert-audio');
        let defaultAudio = 'https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3';

        function killAlerts() {
            $('#mc-new-order-modal').hide();
            if(audioNode && !audioNode.paused) { audioNode.pause(); audioNode.currentTime = 0; }
        }
        
        $('#mc-new-order-close').on('click', function(e) { e.preventDefault(); killAlerts(); });

        $(document).on('click', '.mc-trigger-accept-view', function(e) {
            e.preventDefault(); let targetId = $(this).attr('data-target'); killAlerts(); 
            if(targetId) { 
                if(viewStyle === 'full') { let url = new URL(window.location.href); url.searchParams.set('order', targetId); window.history.pushState(null, '', url.toString()); }
                loadOrderDetails(targetId); 
            }
        });
        
        $('.mc-modal-close-trigger').on('click', function() { 
            $('#mc-modal').hide(); killAlerts();
            let url = new URL(window.location.href); url.searchParams.delete('order'); window.history.pushState(null, '', url.toString());
        });

        let notifyEnabled = localStorage.getItem('mc_notify_ready') === 'yes';
        let lastOrderId = <?php echo !empty($orders) ? $orders[0]->get_id() : 0; ?>;

        function updateBellUI() {
            if(notifyEnabled && typeof Notification !== 'undefined' && Notification.permission === 'granted') {
                $('#mc-bell-dot').css('background', '#2ecc71'); $('.mc-bell-icon').css('color', 'var(--mc-primary)'); $('#mc-bell-toggle').attr('title', 'Notifications Active. Click to disable.');
            } else {
                $('#mc-bell-dot').css('background', '#e74c3c'); $('.mc-bell-icon').css('color', 'var(--mc-text-muted)'); $('#mc-bell-toggle').attr('title', 'Notifications Off. Click to enable.');
            }
        }
        updateBellUI();

        $('#mc-bell-toggle').click(function() {
            if(!notifyEnabled || Notification.permission !== 'granted') {
                Notification.requestPermission().then(function(perm) { if(perm === 'granted') { localStorage.setItem('mc_notify_ready', 'yes'); notifyEnabled = true; updateBellUI(); } });
            } else { localStorage.setItem('mc_notify_ready', 'no'); notifyEnabled = false; updateBellUI(); }
        });

        let audioUnlocked = false;
        $('body').one('click keydown touchstart', function() {
            if(!audioUnlocked) {
                audioNode.src = 'data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA';
                audioNode.play().then(() => { audioNode.pause(); audioNode.currentTime = 0; }).catch(() => {});
                audioUnlocked = true;
            }
        });

        function checkNewOrders() {
            let paramPage = new URLSearchParams(window.location.search).get('mc_page');
            if(paramPage != null && paramPage != '1') return;
            
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_frontend_heartbeat', last_order_id: lastOrderId, security: mc_portal_nonce }, function(res) {
                if(res.success && res.data.orders.length > 0) {
                    let notificationFired = false;
                    let newMaxId = lastOrderId;
                    
                    let incomingOrders = res.data.orders.reverse();
                    
                    incomingOrders.forEach(order => {
                        if (order.id > lastOrderId) {
                            newMaxId = Math.max(newMaxId, order.id); 
                            let activeRule = res.data.rules ? res.data.rules.find(r => r.status === 'any' || r.status === order.status) : null;
                            
                            let popupTitle = "New Order!";
                            let popupMsg = "Order #" + order.id + " from " + order.customer;
                            let finalSound = defaultAudio;
                            let doLoop = false;
                            let showPopup = true;

                            if (activeRule) {
                                popupTitle = activeRule.title ? activeRule.title.replace('[order_id]', order.id).replace('[order_total]', order.total) : popupTitle;
                                popupMsg = activeRule.msg ? activeRule.msg.replace('[order_id]', order.id).replace('[order_total]', order.total) : popupMsg;
                                if(activeRule.sound) finalSound = activeRule.sound;
                                doLoop = (activeRule.loop === 'on');
                                showPopup = (activeRule.popup === 'yes');

                                $('#mc-custom-popup-bg').css('background', activeRule.bg);
                                $('#mc-new-order-title').css('color', activeRule.text);
                                $('#mc-new-order-text').css('color', activeRule.text);
                                $('#mc-new-order-close').css('color', activeRule.text);
                                $('#mc-new-order-accept').css('background', activeRule.btn);

                                if(activeRule.icon) { $('#mc-default-bell-icon').hide(); $('#mc-custom-icon-img').attr('src', activeRule.icon).show(); } 
                                else { $('#mc-default-bell-icon').show(); $('#mc-custom-icon-img').hide(); }
                            }

                            $('#mc-new-order-title').html(popupTitle);
                            $('#mc-new-order-text').html(popupMsg);
                            $('#mc-new-order-accept').attr('data-target', order.id); 

                            audioNode.src = finalSound; 
                            audioNode.loop = doLoop;
                            audioNode.volume = 1.0; 
                            
                            if(showPopup) { $('#mc-new-order-modal').css('display', 'flex'); }
                            
                            if(!notificationFired && notifyEnabled) {
                                let playPromise = audioNode.play();
                                if (playPromise !== undefined) { playPromise.catch(error => { console.log("Audio autoplay blocked."); }); }
                                if (typeof Notification !== 'undefined' && Notification.permission === 'granted') { new Notification(popupTitle, { body: popupMsg, icon: "<?php echo $brand_logo ?: ''; ?>" }); }
                                notificationFired = true; 
                            }
                        }
                        
                        if($('#row-' + order.id).length === 0) {
                            let rowHTML = `<tr id="row-${order.id}" class="mc-new-row">
                                <?php if(in_array('status',$enabled_cols)): ?><td><span class="mc-status-pill status-${order.status}">${order.status_display}</span></td><?php endif; ?>
                                <?php if(in_array('order_id',$enabled_cols)): ?><td><a class="view-trigger" data-id="${order.id}" style="color:var(--mc-primary); cursor:pointer; text-decoration:none;">#${order.id}</a></td><?php endif; ?>
                                <?php if(in_array('customer',$enabled_cols)): ?><td>${order.customer}</td><?php endif; ?>
                                <?php if(in_array('purchased',$enabled_cols)): ?><td>${order.items} items</td><?php endif; ?>
                                <?php if(in_array('ship',$enabled_cols)): ?><td><a class="view-trigger" data-id="${order.id}" style="color:var(--mc-text-muted); cursor:pointer; text-decoration:none; font-size:12px;">${order.ship}</a><br><small>${order.city}</small></td><?php endif; ?>
                                <?php if(in_array('date',$enabled_cols)): ?><td>${order.date}</td><?php endif; ?>
                                <?php if(in_array('total',$enabled_cols)): ?><td>${order.total}</td><?php endif; ?>
                                <?php if(in_array('actions',$enabled_cols)): ?><td style="text-align:center;"><span class="dashicons dashicons-visibility mc-action-icon view-trigger" data-id="${order.id}"></span></td><?php endif; ?>
                            </tr>`;
                            $('#mc-stream').prepend(rowHTML);
                        }
                    });
                    lastOrderId = newMaxId;
                }
            });
        }
        
        let intervalMs = <?php echo get_option('mc_check_interval', '15') * 1000; ?>;
        const workerBlob = new Blob([ `let interval; self.addEventListener('message', function(e) { if (e.data === 'start') { interval = setInterval(function() { self.postMessage('tick'); }, ${intervalMs}); } });` ], { type: 'application/javascript' });
        const worker = new Worker(URL.createObjectURL(workerBlob));
        worker.onmessage = function() { checkNewOrders(); };
        <?php if(get_option('mc_frontend_notifications', 'yes') === 'yes'): ?>
        worker.postMessage('start');
        <?php endif; ?>
        <?php endif; ?>

        <?php if($qr_active): ?>
        let html5QrcodeScanner = null;
        $('#mc-trigger-camera').on('click', function() {
            $('#mc-camera-modal').css('display', 'flex');
            html5QrcodeScanner = new Html5QrcodeScanner("mc-reader", { fps: 10, qrbox: 250 });
            html5QrcodeScanner.render(function(decodedText) {
                let tokenMatch = decodedText.match(/[?&]mc_auth=([^&]+)/i);
                if (tokenMatch) {
                    let token = tokenMatch[1];
                    html5QrcodeScanner.clear(); $('#mc-camera-modal').hide();
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_lookup_token', token: token, security: mc_portal_nonce }, function(res) {
                        if(res.success) { loadOrderDetails(res.data.order_id); } else { alert("Invalid or expired QR code."); }
                    });
                } else {
                    let idMatch = decodedText.match(/MC-ORD-(\d+)/i) || decodedText.match(/[?&]order=(\d+)/i);
                    if(idMatch) { html5QrcodeScanner.clear(); $('#mc-camera-modal').hide(); loadOrderDetails(idMatch[1]); }
                }
            });
        });
        $('#mc-close-camera').on('click', function() { if(html5QrcodeScanner) html5QrcodeScanner.clear(); $('#mc-camera-modal').hide(); });

        let barcodeBuffer = ""; let barcodeTimeout;
        $(document).on('keydown', function(e) {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;
            if (e.key.length === 1 || e.key === 'Enter') {
                barcodeBuffer += e.key; clearTimeout(barcodeTimeout); barcodeTimeout = setTimeout(function() { barcodeBuffer = ""; }, 150);
                if (e.key === 'Enter' || barcodeBuffer.includes('mc_auth=')) {
                    let tokenMatch = barcodeBuffer.match(/[?&]mc_auth=([^&]+)/i);
                    if (tokenMatch) {
                        let token = tokenMatch[1]; barcodeBuffer = "";
                        $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_lookup_token', token: token, security: mc_portal_nonce }, function(res) { if(res.success) loadOrderDetails(res.data.order_id); });
                    } else {
                        let idMatch = barcodeBuffer.match(/MC-ORD-(\d+)/i) || barcodeBuffer.match(/[?&]order=(\d+)/i);
                        if (idMatch) { let orderId = idMatch[1]; barcodeBuffer = ""; loadOrderDetails(orderId); }
                    }
                }
            }
        });
        <?php endif; ?>

        $('#mc-theme-toggle').on('click', function() {
            $('html').toggleClass('mc-theme-dark mc-theme-light');
        });

        $('#mc-image-toggle').on('click', function() {
            $('body').toggleClass('mc-show-item-images');
            localStorage.setItem('mc_show_images', $('body').hasClass('mc-show-item-images') ? 'yes' : 'no');
        });

        // Expose loadOrderDetails globally so the Grouped Engine can call it
        window.loadOrderDetails = function(orderId) {
            if(typeof killAlerts === 'function') killAlerts(); 
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_get_order_details', order_id: orderId, security: mc_portal_nonce }, function(res) {
                if(res.success) { 
                    if(viewStyle === 'modal') { $('#mc-modal-data').html(res.data); $('#mc-modal').css('display','flex'); } 
                    else { $('#mc-full-content').html(res.data); $('#mc-full-overlay').fadeIn(300); }
                }
            });
        };

        $(document).on('click', '.view-trigger', function(e) { 
            e.preventDefault(); let id = $(this).data('id'); 
            if(viewStyle === 'full') {
                let url = new URL(window.location.href); url.searchParams.set('order', id); window.history.pushState(null, '', url.toString());
            }
            window.loadOrderDetails(id); 
        });

        $('#mc-search-input').on('keyup', function() { let value = $(this).val().toLowerCase(); $('#mc-stream tr').filter(function() { $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1) }); });

        $('#mc-create-draft-order').on('click', function(e) {
            e.preventDefault(); $(this).html('<span class="dashicons dashicons-update spin" style="margin-right:8px;"></span> Generating...');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_create_order', security: mc_portal_nonce }, function(res) {
                if(res.success) { 
                    let url = new URL(window.location.href); url.searchParams.set('order', res.data.order_id); window.history.pushState(null, '', url.toString()); 
                    window.loadOrderDetails(res.data.order_id); $('#mc-create-draft-order').html('<span class="dashicons dashicons-plus" style="margin-right:8px;"></span> Create New Order'); 
                }
            });
        });

        $(document).on('click', '.mc-btn-discard', function() {
            if(!confirm("Are you sure you want to discard this order completely?")) return;
            let orderId = $(this).data('id'); $(this).html('DISCARDING...').css('pointer-events', 'none');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_discard_order', order_id: orderId, security: mc_portal_nonce }, function(res) {
                if(res.success) { location.reload(); } else { alert(res.data.msg || "Failed to discard order."); }
            });
        });

        $(document).on('click', '.mc-save-qty-btn', function() {
            let orderId = $(this).data('order'); let itemId = $(this).data('item'); let qty = $(this).siblings('.mc-qty-input').val();
            $(this).removeClass('dashicons-saved').addClass('dashicons-update spin');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_update_item_qty', order_id: orderId, item_id: itemId, qty: qty, security: mc_portal_nonce }, function(res) {
                if(res.success) { window.loadOrderDetails(orderId); } else { alert("Failed to update quantity."); }
            });
        });

        $(document).on('click', '.mc-add-item-btn', function() {
            let orderId = $(this).data('order'); let $select = $('#mc-add-product-select'); let productId = $select.val(); let $btn = $(this);
            if(!productId) { alert("Select a product first."); return; }
            $btn.html('...').prop('disabled', true);
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_add_item', order_id: orderId, product_id: productId, security: mc_portal_nonce }, function(res) {
                if(res.success) { 
                    if(res.data.is_combo || res.data.is_grouped) {
                        $('#mc-combo-render-area').html(res.data.html); $('#mc-combo-build-modal').css('display', 'flex'); $('#mc-combo-render-area').attr('data-active-order', orderId);
                        
                        // THE FIX: ONLY attach the save listener if it's a Combo. Grouped handles itself natively!
                        if (res.data.is_combo) {
                            setTimeout(() => {
                                $('#mc-submit-combo').off('click').on('click', function(e) {
                                    e.preventDefault(); if (!$(this).hasClass('mc-ready')) { alert("Please complete required steps."); return; }
                                    $(this).html('SAVING TO ORDER...').css('pointer-events', 'none');
                                    let selectedData = [];
                                    $('.mc-combo-card.has-qty').each(function() {
                                        let id = $(this).data('id'); let qty = parseInt($(this).find('.mc-qty-num').text()); let slotName = $(this).closest('.mc-step-container').find('.mc-step-title-text').text();
                                        for(let i=0; i<qty; i++) { selectedData.push({ id: id, slot: slotName }); }
                                    });
                                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_save_combo_to_order', security: mc_portal_nonce, order_id: orderId, product_id: productId, mc_combo_items: selectedData }, function(cr) {
                                        if(cr.success) { $('#mc-combo-build-modal').hide(); $('#mc-combo-render-area').empty(); window.loadOrderDetails(orderId); } else { alert("Failed to save item."); }
                                    });
                                });
                            }, 500);
                        }
                    } else { window.loadOrderDetails(orderId); }
                } else { alert("Failed to add product."); }
                $btn.html('ADD').prop('disabled', false);
            });
        });

        $(document).on('click', '.mc-edit-item', function() {
            let orderId = $(this).data('order'); let itemId = $(this).data('item'); $(this).removeClass('dashicons-edit').addClass('dashicons-update spin');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_edit_item_ui', order_id: orderId, item_id: itemId, security: mc_portal_nonce }, function(res) {
                if(res.success) {
                    $('#mc-combo-render-area').html(res.data.html); $('#mc-combo-build-modal').css('display', 'flex');
                    
                    // THE FIX: ONLY attach the save listener if it's a Combo. Grouped handles itself natively!
                    if (!res.data.is_grouped) {
                        setTimeout(() => {
                            let prefillStr = $('#mc-edit-combo-injector').data('prefill'); let prefillData = typeof prefillStr === 'string' ? JSON.parse(prefillStr) : prefillStr;
                            $('#mc-submit-combo').off('click').on('click', function(e) {
                                e.preventDefault(); if (!$(this).hasClass('mc-ready')) { alert("Please complete required steps."); return; }
                                $(this).html('UPDATING ITEM...').css('pointer-events', 'none'); let selectedData = [];
                                $('.mc-combo-card.has-qty').each(function() {
                                    let id = $(this).data('id'); let qty = parseInt($(this).find('.mc-qty-num').text()); let slotName = $(this).closest('.mc-step-container').find('.mc-step-title-text').text();
                                    for(let i=0; i<qty; i++) { selectedData.push({ id: id, slot: slotName }); }
                                });
                                $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_save_combo_to_order', security: mc_portal_nonce, order_id: orderId, product_id: res.data.product_id, mc_combo_items: selectedData, replace_item_id: itemId }, function(cr) {
                                    if(cr.success) { $('#mc-combo-build-modal').hide(); $('#mc-combo-render-area').empty(); window.loadOrderDetails(orderId); } else { alert("Failed to update item."); }
                                });
                            });
                            if (prefillData && prefillData.length > 0) {
                                $('#mc-combo-app').attr('data-prefill', JSON.stringify(prefillData)); $('#mc-combo-app').attr('data-prefill-qty', $('#mc-edit-combo-injector').data('qty'));
                                setTimeout(() => { $('.mc-change-sel').first().trigger('click'); }, 100);
                            }
                        }, 500);
                    }
                } else { alert("Unable to edit this item."); }
                $('.mc-edit-item').removeClass('dashicons-update spin').addClass('dashicons-edit');
            });
        });

        $('#mc-combo-build-close').on('click', function() { $('#mc-combo-build-modal').hide(); $('#mc-combo-render-area').empty(); });

        $(document).on('click', '.mc-remove-item', function() {
            if(!confirm("Are you sure you want to remove this item? This will recalculate the order total.")) return;
            let orderId = $(this).data('order'); let itemId = $(this).data('item'); $(this).removeClass('dashicons-trash').addClass('dashicons-update spin');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_remove_item', order_id: orderId, item_id: itemId, security: mc_portal_nonce }, function(res) {
                if(res.success) { window.loadOrderDetails(orderId); } else { alert("Failed to remove item."); }
            });
        });

        $(document).on('click', '.mc-btn-update', function() {
            let id = $(this).data('id'); let status = $('#mc-new-status-val').val(); let statusText = $('#mc-new-status-val option:selected').text(); let $btn = $(this); $btn.html('SAVING...').prop('disabled', true);
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'mc_portal_update_status', order_id: id, status: status, security: mc_portal_nonce }, function(res) {
                if(res.success) { 
                    $('#mc-success-overlay').css('display','flex').hide().fadeIn(300); 
                    let pill = $('#row-' + id + ' .mc-status-pill');
                    if(pill.length) { pill.removeClass(function (i, c) { return (c.match(/(^|\s)status-\S+/g) || []).join(' '); }).addClass('status-' + status).text(statusText); }
                } 
                $btn.html('UPDATE STATUS').prop('disabled', false);
            });
        });

        $(document).on('click', '#mc-toggle-edit-fulfill', function() { $('#mc-fulfill-static').toggle(); $('#mc-fulfill-edit').toggle(); });

        $(document).on('click', '.mc-save-fulfill-btn', function() {
            let orderId = $(this).data('order');
            let data = { action: 'mc_portal_update_fulfillment', security: mc_portal_nonce, order_id: orderId, loc_id: $('#mc-edit-loc-id').val(), method: $('#mc-edit-loc-method').val(), date: $('#mc-edit-loc-date').val(), time: $('#mc-edit-loc-time').val() };
            $(this).html('SAVING...').prop('disabled', true);
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', data, function(res) {
                if(res.success) { window.loadOrderDetails(orderId); } else { alert("Failed to update fulfillment."); }
            });
        });

        $(document).on('click', '.mc-back-to-dash', function() { location.reload(); });
    });
    </script>
    </body></html>
    <?php
}