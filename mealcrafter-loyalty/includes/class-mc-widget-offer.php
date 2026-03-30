<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( '\Elementor\Widget_Base' ) ) return;

class MC_Widget_Offer extends \Elementor\Widget_Base {

    public function get_name() { return 'mc_loyalty_offer'; }
    public function get_title() { return 'MC Loyalty: Offer Card'; }
    public function get_icon() { return 'fas fa-ticket-alt'; } 
    public function get_categories() { return [ 'mealcrafter-loyalty' ]; }

    protected function register_controls() {
        $this->start_controls_section('content_section', [
            'label' => 'Select Reward/Promo',
            'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
        ]);

        $options = [];
        $coupons = get_posts(['post_type' => 'shop_coupon', 'posts_per_page' => -1]);
        foreach ($coupons as $c) {
            $is_reward = get_post_meta($c->ID, '_mc_is_reward_offer', true) === 'yes';
            $label = $is_reward ? '⭐ ' . $c->post_title . ' (Point Reward)' : '🎟️ ' . $c->post_title . ' (Free Promo)';
            $options[$c->ID] = $label;
        }

        $this->add_control('coupon_id', [
            'label' => 'Select Coupon',
            'type' => \Elementor\Controls_Manager::SELECT,
            'options' => $options,
            'default' => '',
            'description' => 'Select any coupon. The design is controlled globally in Points & Rewards > Offers & Coupons.',
        ]);
        
        $this->end_controls_section();
    }

    private function get_used_order_id( $user_id, $coupon_code ) {
        if ( ! $user_id || empty($coupon_code) || !function_exists('wc_get_orders') ) return false;
        
        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit'       => -1,
            'status'      => ['completed', 'processing', 'on-hold']
        ]);

        foreach ($orders as $order) {
            $used_coupons = $order->get_coupon_codes();
            if ( in_array( strtolower($coupon_code), array_map('strtolower', $used_coupons) ) ) {
                return $order->get_id(); 
            }
        }
        return false;
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $coupon_id = $settings['coupon_id'];
        
        if ( ! $coupon_id ) { 
            echo '<div style="padding:20px; border:2px dashed #ccc; text-align:center;">Please select a WooCommerce Coupon.</div>'; 
            return; 
        }

        if ( !class_exists('WC_Coupon') ) return;

        $coupon = new \WC_Coupon($coupon_id);
        if ( ! $coupon->get_id() ) return;

        // Fetch Global Design Settings
        $bg = get_option('mc_offers_design_bg', '#ffffff');
        $title_color = get_option('mc_offers_design_title', '#222222');
        $sub_color = get_option('mc_offers_design_sub', '#666666');
        $btn_color = get_option('mc_offers_design_btn', '#2ecc71');
        $promo_color = get_option('mc_offers_design_promo', '#d35400');
        $fallback_icon = get_option('mc_offers_design_fallback_icon', '🎁');

        $is_reward = $coupon->get_meta('_mc_is_reward_offer') === 'yes';
        $cost = (int) $coupon->get_meta('_mc_offer_point_cost');
        
        $title = $coupon->get_meta('_mc_offer_title') ?: 'Special Offer: ' . $coupon->get_code();
        $subtitle = $coupon->get_meta('_mc_offer_subtitle') ?: 'Use this code at checkout!';
        $img_url = $coupon->get_meta('_mc_offer_image_url');

        $user_id = get_current_user_id();
        $balance = class_exists('MC_Points_Account') ? \MC_Points_Account::get_accurate_user_points($user_id) : 0;
        
        $used_order_id = false;
        if ( is_user_logged_in() ) {
            $used_order_id = $this->get_used_order_id( $user_id, $coupon->get_code() );
        }

        $box_style = "background: " . esc_attr($bg) . "; border: 1px solid #eee; border-radius: 12px; overflow: hidden; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; transition: 0.3s;";
        if ( $used_order_id ) {
            $box_style .= " opacity: 0.6; filter: grayscale(100%); pointer-events: none;";
        }
        
        ?>
        <div class="mc-offer-box" style="<?php echo esc_attr($box_style); ?>">
            
            <?php if (!empty($img_url)): ?>
                <div style="height: 150px; width: 100%; background: url('<?php echo esc_url($img_url); ?>') center/cover;"></div>
            <?php else: ?>
                <div style="height: 80px; width: 100%; background: rgba(0,0,0,0.03); display:flex; align-items:center; justify-content:center; font-size:40px;"><?php echo esc_html($fallback_icon); ?></div>
            <?php endif; ?>

            <div style="padding: 25px; flex-grow: 1; display: flex; flex-direction: column;">
                <h3 style="margin: 0 0 5px 0; font-size: 20px; font-weight: 800; color: <?php echo esc_attr($title_color); ?>;"><?php echo esc_html($title); ?></h3>
                <p style="margin: 0 0 20px 0; font-size: 14px; color: <?php echo esc_attr($sub_color); ?>;"><?php echo esc_html($subtitle); ?></p>
                
                <div style="margin-top: auto; pointer-events: auto;">
                    
                    <?php if ( $used_order_id ): ?>
                        <div style="background: #e0e0e0; color: #555; padding: 12px; border-radius: 8px; font-weight: bold; font-size: 14px;">
                            ✅ Claimed on Order #<?php echo esc_html($used_order_id); ?>
                            <a href="<?php echo esc_url( wc_get_endpoint_url( 'view-order', $used_order_id, wc_get_page_permalink( 'myaccount' ) ) ); ?>" style="display:block; margin-top:5px; font-size:12px; color:#2271b1; text-decoration:underline; pointer-events: auto;">View Receipt</a>
                        </div>
                    
                    <?php elseif ( $is_reward ): ?>
                        <?php if ( !is_user_logged_in() ): ?>
                            <div style="color: <?php echo esc_attr($promo_color); ?>; font-weight:bold; padding:12px; background:rgba(0,0,0,0.03); border-radius:30px;">Log in to unlock</div>
                        <?php elseif ( $balance >= $cost ): ?>
                            <button class="mc-unlock-offer-btn" data-id="<?php echo esc_attr($coupon_id); ?>" style="background: <?php echo esc_attr($btn_color); ?>; color: #fff; border: none; padding: 12px 25px; border-radius: 30px; font-weight: bold; cursor: pointer; transition: 0.2s; width: 100%;">
                                Unlock for <?php echo number_format($cost); ?> Pts
                            </button>
                        <?php else: ?>
                            <button disabled style="background: #f0f0f0; color: #aaa; border: none; padding: 12px 25px; border-radius: 30px; font-weight: bold; width: 100%; cursor: not-allowed;">
                                Need <?php echo number_format($cost - $balance); ?> more Pts
                            </button>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <button class="mc-apply-promo-btn" data-code="<?php echo esc_attr($coupon->get_code()); ?>" style="background: #f9f9f9; border: 2px dashed <?php echo esc_attr($promo_color); ?>; color: <?php echo esc_attr($promo_color); ?>; padding: 12px; border-radius: 8px; font-weight: 900; font-size: 18px; letter-spacing: 1px; width: 100%; cursor: pointer; transition: 0.2s;">
                            <?php echo esc_html($coupon->get_code()); ?>
                        </button>
                        <p class="mc-promo-helper" style="margin: 8px 0 0 0; font-size:12px; color:#888;">Click to apply to cart!</p>
                    <?php endif; ?>
                    
                    <div class="mc-offer-result" style="margin-top: 15px; font-weight: bold; font-size: 16px;"></div>
                </div>
            </div>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // Handle Unlocking Reward Points
                $('.mc-unlock-offer-btn[data-id="<?php echo esc_attr($coupon_id); ?>"]').off('click').on('click', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var cid = btn.data('id');
                    var resultDiv = btn.siblings('.mc-offer-result');
                    
                    btn.text('Unlocking & Applying...').css('opacity', '0.7').prop('disabled', true);
                    
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'mc_unlock_offer',
                        coupon_id: cid
                    }, function(res) {
                        if (res.success) {
                            btn.hide();
                            resultDiv.html('<span style="color:<?php echo esc_attr($btn_color); ?>;">✅ Unlocked & Applied!</span>');
                        } else {
                            btn.text('Unlock for <?php echo number_format($cost); ?> Pts').css('opacity', '1').prop('disabled', false);
                            alert(res.data.message);
                        }
                    });
                });

                // Handle Clicking Free Promos
                $('.mc-apply-promo-btn[data-code="<?php echo esc_attr($coupon->get_code()); ?>"]').off('click').on('click', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var code = btn.data('code');
                    var helperTxt = btn.siblings('.mc-promo-helper');
                    
                    btn.text('Applying...').css('opacity', '0.7').prop('disabled', true);
                    
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'mc_apply_promo',
                        coupon_code: code
                    }, function(res) {
                        btn.css('opacity', '1');
                        if (res.success) {
                            btn.css({'background': '#d4edda', 'color': '#155724', 'border-color': '#28a745'});
                            btn.text('✅ Applied to Cart!');
                            helperTxt.hide();
                        } else {
                            btn.prop('disabled', false).text(code);
                            alert(res.data.message);
                        }
                    });
                });
            });
        </script>
        <?php
    }
}