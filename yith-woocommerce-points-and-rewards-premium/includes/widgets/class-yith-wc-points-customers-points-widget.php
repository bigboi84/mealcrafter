<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YITH_YWPAR_Points_Rewards_Customers_Points add a widget to show the Best Users by Awarded Points
 *
 * @class   YITH_YWPAR_Points_Rewards_Customers_Points
 * @package YITH WooCommerce Points and Rewards
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_YWPAR_Points_Rewards_Customers_Points' ) ) {
	/**
	 * YITH YWPAR Points Rewards Customers Points Widget
	 *
	 * @since 3.0.0
	 */
	class YITH_YWPAR_Points_Rewards_Customers_Points extends WP_Widget {

		/**
		 * Constructor
		 *
		 * @access public
		 */
		public function __construct() {

			/* Widget variable settings. */
			$this->woo_widget_cssclass    = 'woocommerce widget_ywpar_customers_points';
			$this->woo_widget_description = esc_html__( "Show customers' points ", 'yith-woocommerce-points-and-rewards' );
			$this->woo_widget_idbase      = 'yith_ywpar_customers_points';
			$this->woo_widget_name        = esc_html__( 'YITH WooCommerce Points And Rewards - Customers Points', 'yith-woocommerce-points-and-rewards' );

			/* Widget settings. */
			$widget_ops = array(
				'classname'   => $this->woo_widget_cssclass,
				'description' => $this->woo_widget_description,
			);

			/* Create the widget. */
			parent::__construct( 'widget_ywpar_customers_points', $this->woo_widget_name, $widget_ops );

		}


		/**
		 * Widget function.
		 *
		 * @param array $args Arguments.
		 * @param array $instance Instance.
		 *
		 * @return void
		 * @see WP_Widget
		 * @access public
		 */
		public function widget( $args, $instance ) {

			/**
			 * Internal var
			 *
			 * @var string $before_widget
			 * @var string $before_title
			 * @var string $after_title
			 * @var string $after_widget
			 */

			extract( $args ); //phpcs:ignore

			if ( ! is_user_logged_in() ) {
				return;
			}

			$this->istance = $instance;
			$title         = isset( $instance['title'] ) ? $instance['title'] : '';
			$title         = apply_filters( 'widget_title', $title, $instance, $this->id_base );

			$style            = isset( $instance['style'] ) ? $instance['style'] : 'simple';
			$num_of_customers = isset( $instance['num_of_customers'] ) ? $instance['num_of_customers'] : 3;

			echo $before_widget; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( $title ) {
				echo $before_title . $title . $after_title; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			$shortcode = '[ywpar_customers_points style="' . $style . '" tabs="no" num_of_customers="' . $num_of_customers . '"]';
			echo is_callable( 'apply_shortcodes' ) ? apply_shortcodes( $shortcode ) : do_shortcode( $shortcode ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			echo $after_widget; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		}

		/**
		 * Update function.
		 *
		 * @param array $new_instance New instance.
		 * @param array $old_instance Old Instance.
		 *
		 * @return array
		 * @see WP_Widget->update
		 * @access public
		 */
		public function update( $new_instance, $old_instance ) {
			$instance['title']            = wp_strip_all_tags( stripslashes( $new_instance['title'] ) );
			$instance['style']            = stripslashes( $new_instance['style'] );
			$instance['num_of_customers'] = stripslashes( $new_instance['num_of_customers'] );

			$this->istance = $instance;
			return $instance;
		}

		/**
		 * Form function.
		 *
		 * @param array $instance Instance.
		 * @return void
		 * @see WP_Widget->form
		 * @access public
		 */
		public function form( $instance ) {
			$defaults = array(
				'title'            => esc_html__( 'Best Users', 'yith-woocommerce-points-and-rewards' ),
				'style'            => esc_html__( 'Simple', 'yith-woocommerce-points-and-rewards' ),
				'num_of_customers' => esc_html__( 'Number of customers to show', 'yith-woocommerce-points-and-rewards' ),
			);

			$instance = wp_parse_args( (array) $instance, $defaults ); ?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_attr_e( 'Title:', 'yith-woocommerce-points-and-rewards' ); ?></label>
				<input type="text" class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" value="<?php echo isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : ''; ?>"/>
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_attr_e( 'Style:', 'yith-woocommerce-points-and-rewards' ); ?></label>
				<select id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
					<option value="simple" <?php echo ( isset( $instance['style'] ) && 'boxed' === $instance['style'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Simple', 'yith-woocommerce-points-and-rewards' ); ?></option>
					<option value="boxed" <?php echo ( isset( $instance['style'] ) && 'boxed' === $instance['style'] ) ? 'selected' : ''; ?>><?php esc_html_e( 'Boxed', 'yith-woocommerce-points-and-rewards' ); ?></option>
				</select>
			</p>

			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'num_of_customers' ) ); ?>"><?php esc_html_e( 'Number of customers to show:', 'yith-woocommerce-points-and-rewards' ); ?></label>
				<input type="number" id="<?php echo esc_attr( $this->get_field_id( 'num_of_customers' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'num_of_customers' ) ); ?>" value="<?php echo ( isset( $instance['num_of_customers'] ) && ! empty( $instance['num_of_customers'] ) ) ? esc_attr( $instance['num_of_customers'] ) : 3; ?>" />
			</p>

			<?php
		}
	}
}
