<?php
/**
 * MealCrafter: Tab - Customers Points CRM (Router)
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Tab_Customers {

    public function render() {
        $current_sub = isset($_GET['sub']) ? sanitize_text_field($_GET['sub']) : 'manage';
        
        $subtabs = [
            'manage'        => 'Manage Customers',
            'bulk'          => 'Bulk Actions',
            'import-export' => 'Import & Export'
        ];

        ?>
        <div class="mc-layout-wrapper">
            <div class="mc-sidebar-nav">
                <?php foreach($subtabs as $sub_key => $sub_name): ?>
                    <a href="?page=mc-loyalty-settings&tab=customers&sub=<?php echo esc_attr($sub_key); ?>" class="mc-subtab-link <?php echo $current_sub === $sub_key ? 'active' : ''; ?>">
                        <?php echo esc_html($sub_name); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="mc-main-content">
                <?php 
                $part_file = MC_LOYALTY_PATH . 'includes/admin-tabs/tab-customers/' . $current_sub . '.php';

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