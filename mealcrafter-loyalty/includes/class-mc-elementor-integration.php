<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MC_Elementor_Integration {

    public function __construct() {
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets_legacy' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'add_category' ] );
    }

    public function add_category( $elements_manager ) {
        $elements_manager->add_category(
            'mealcrafter-loyalty',
            [
                'title' => 'MealCrafter Loyalty',
                'icon'  => 'fa fa-star', // Native FontAwesome Star
            ]
        );
    }

    public function register_widgets( $widgets_manager ) {
        $this->load_and_register_widget($widgets_manager);
    }

    public function register_widgets_legacy( $widgets_manager ) {
        $this->load_and_register_widget($widgets_manager, true);
    }

    private function load_and_register_widget($widgets_manager, $is_legacy = false) {
        $widget_file = MC_LOYALTY_PATH . 'includes/class-mc-widget-offer.php';
        
        if ( file_exists( $widget_file ) ) {
            require_once $widget_file;
            if ( class_exists('MC_Widget_Offer') ) {
                if ( method_exists( $widgets_manager, 'register' ) ) {
                    $widgets_manager->register( new \MC_Widget_Offer() );
                } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
                    $widgets_manager->register_widget_type( new \MC_Widget_Offer() );
                }
            }
        }
    }
}
new MC_Elementor_Integration();