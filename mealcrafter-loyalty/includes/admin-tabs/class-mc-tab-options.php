<?php
/**
 * MealCrafter: Tab - Points Options (Router)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Options {

    public function render() {
        $current_sub = isset($_GET['sub']) ? sanitize_text_field($_GET['sub']) : 'assignment';
        
        $subtabs = [
            'assignment' => 'Points Assignment',
            'rules'      => 'Points Rules',
            'extra'      => 'Extra Points',
            'levels'     => 'Levels & Badges',
            'banners'    => 'Banners',
            'ranking'    => 'Ranking'
        ];

        ?>
        <div class="mc-layout-wrapper">
            <div class="mc-sidebar-nav">
                <?php foreach($subtabs as $sub_key => $sub_name): ?>
                    <a href="?page=mc-loyalty-settings&tab=options&sub=<?php echo esc_attr($sub_key); ?>" class="mc-subtab-link <?php echo $current_sub === $sub_key ? 'active' : ''; ?>">
                        <?php echo esc_html($sub_name); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mc-main-content">
                <h2 style="margin-top:0; font-weight:800; border-bottom:2px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                    <?php echo esc_html($subtabs[$current_sub]); ?>
                </h2>
                
                <?php 
                $part_file = MC_LOYALTY_PATH . 'includes/admin-tabs/tab-options/' . $current_sub . '.php';

                if ( file_exists( $part_file ) ) {
                    include $part_file;
                } else {
                    echo '<p class="description" style="color:#777;">The ' . esc_html($subtabs[$current_sub]) . ' module is coming soon.</p>';
                }
                ?>
            </div>
        </div>
        <?php
    }
}