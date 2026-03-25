<?php
/**
 * Class to integrate Points and Rewards with YITH WooCommerce Membership
 *
 * @class   YWPAR_YITH_WooCommerce_Membership
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YWPAR_YITH_WooCommerce_Membership' ) ) {
	/**
	 * Class YWPAR_YITH_WooCommerce_Membership
	 */
	class YWPAR_YITH_WooCommerce_Membership {
		/**
		 * Single instance of the class
		 *
		 * @var YWPAR_YITH_WooCommerce_Membership
		 * @since 3.0.0
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YWPAR_YITH_WooCommerce_Membership
		 * @since 3.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used.
		 *
		 * @since  3.0.0
		 */
		public function __construct() {

			add_filter( 'ywpar_points_rules_options', array( $this, 'set_earning_rule_options' ), 10 );

			// redeem standard options.
			add_filter( 'ywpar_user_role_redeem_type_options', array( $this, 'set_user_role_type_options' ), 10 );
			add_filter( 'ywpar_redeem_standard_options', array( $this, 'set_membership_plan_list_on_redeem_options' ), 10 );

			// redeem rule.
			add_filter( 'ywpar_points_rewards_rules_options', array( $this, 'set_redeeming_rule_options' ), 10 );
			
			// extra options.
			add_filter( 'ywpar_extra_points_options', array( $this, 'add_membership_extra_options' ) );

			if ( 'yes' === ywpar_get_option( 'enable_point_on_membership_plan_exp' ) ) {
				add_action( 'yith_wcmbs_membership_created', array( $this, 'give_points_to_membership' ) );
			}

			add_filter( 'ywpar_customization_options', array( $this, 'add_membership_label' ), 10, 1 );

			add_filter( 'ywpar_is_user_enabled', array( $this, 'check_if_user_is_enabled' ), 10, 3 );
		}

		/**
		 * Check if the user is enabled to earn and redeem
		 *
		 * @param bool                            $result Result of filter.
		 * @param string                          $action Earn or redeem.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 */
		public function check_if_user_is_enabled( $result, $action, $customer ) {
			if ( defined( 'YITH_WCMBS_PREMIUM' ) && ( 'earn' === $action && 'members' === ywpar_get_option( 'user_role_enabled_type', 'all' ) || ( 'redeem' === $action && 'members' === ywpar_get_option( 'user_role_redeem_type', 'all' ) ) ) ) {
				$membership = $customer->get_membership_plans();
				$result     = ! empty( $membership );
			}

			return $result;
		}

		/**
		 * Add options inside the earning rule
		 *
		 * @param array $options Options.
		 * @return array
		 */
		public function set_earning_rule_options( $options ) {
			$options['ywpar_user_type']['options']['membership'] = esc_html__( 'Users with a membership plan', 'yith-woocommerce-points-and-rewards' );

			$options['ywpar_user_plans_list'] = array(
				'id'                => 'ywpar_user_plans_list',
				'name'              => 'user_plans_list',
				'type'              => 'select',
				'class'             => 'wc-enhanced-select',
				'css'               => 'min-width:300px',
				'multiple'          => true,
				'title'             => esc_html__( 'Choose membership plans', 'yith-woocommerce-points-and-rewards' ),
				// translators: Placeholder are html tags.
				'desc'              => '',
				'options'           => $this->get_plans(),
				'placeholder'       => esc_html__( 'Search membership plan', 'yith-woocommerce-points-and-rewards' ),
				'std'               => array(),
				'custom_attributes' => array(
					'data-deps'       => 'ywpar_user_type',
					'data-deps_value' => 'membership',
				),
			);

			return $options;
		}

		/**
		 * Add the option of membership plan inside the users that can redeem points.
		 *
		 * @param array $options Option list.
		 *
		 * @return array
		 */
		public function set_user_role_type_options( $options ) {
			$options['members'] = esc_html__( 'Users with a membership plan', 'yith-woocommerce-points-and-rewards' );
			return $options;
		}

		/**
		 * Set membership plan list on redeem option
		 *
		 * @param array $options Options.
		 * @return array
		 */
		public function set_membership_plan_list_on_redeem_options( $options ) {
			$new_options = array();
			foreach ( $options as $key => $content ) {
				$new_options[ $key ] = $content;
				if ( 'user_enabled_to_redeem' === $key ) {
					$new_options['user_plans_list'] = array(
						'id'                => 'ywpar_user_plans_list',
						'name'              => 'user_plans_list',
						'type'              => 'yith-field',
						'yith-type'         => 'select',
						'class'             => 'wc-enhanced-select',
						'css'               => 'min-width:300px',
						'multiple'          => true,
						'title'             => esc_html__( 'Choose membership plans', 'yith-woocommerce-points-and-rewards' ),
						'desc'              => '',
						'options'           => $this->get_plans(),
						'placeholder'       => esc_html__( 'Search membership plan', 'yith-woocommerce-points-and-rewards' ),
						'std'               => array(),
						'custom_attributes' => array(
							'data-deps'       => 'ywpar_enable_rewards_points,ywpar_user_role_redeem_type',
							'data-deps_value' => 'yes,members',
						),
					);
				}
			}

			return $new_options;
		}

		/**
		 * Add options inside the redeeming rule
		 *
		 * @param array $options Options.
		 * @return array
		 */
		public function set_redeeming_rule_options( $options ) {
			$options['ywpar_user_type']['options']['membership'] = esc_html__( 'Users with a membership plan', 'yith-woocommerce-points-and-rewards' );

			$options['ywpar_user_plans_list'] = array(
				'id'                => 'ywpar_user_plans_list',
				'name'              => 'user_plans_list',
				'type'              => 'select',
				'class'             => 'wc-enhanced-select',
				'css'               => 'min-width:300px',
				'multiple'          => true,
				'title'             => esc_html__( 'Choose membership plans', 'yith-woocommerce-points-and-rewards' ),
				'desc'              => '',
				'options'           => $this->get_plans(),
				'placeholder'       => esc_html__( 'Search membership plan', 'yith-woocommerce-points-and-rewards' ),
				'std'               => array(),
				'custom_attributes' => array(
					'data-deps'       => 'ywpar_user_type',
					'data-deps_value' => 'membership',
				),
			);

			return $options;
		}

		/**
		 * Return the list of plans
		 *
		 * @return array
		 */
		public function get_plans() {
			$plans        = yith_wcmbs_get_plans( array( 'fields' => 'all' ) );
			$option_plans = array();
			if ( $plans ) {
				foreach ( $plans as $plan ) {
					$option_plans[ $plan->ID ] = $plan->post_title;
				}
			}
			return $option_plans;
		}


		/**
		 * Add membership option to Points and Rewards Extra Points options.
		 *
		 * @param array $options Options.
		 *
		 * @since  3.0.0
		 */
		public function add_membership_extra_options( $options ) {

			$membership_options = array(

				'point_on_membership_plan_title'       => array(
					'name' => esc_html__( 'Extra points for members of specific plans', 'yith-woocommerce-points-and-rewards' ),
					'type' => 'title',
					'id'   => 'ywpar_point_on_membership_plan_title',
				),

				'enable_points_on_membership_plan_exp' => array(
					'name'      => esc_html__( 'Assign points to the members of a membership', 'yith-woocommerce-points-and-rewards' ),
					'desc'      => esc_html__( 'Assign points to the members of a membership', 'yith-woocommerce-points-and-rewards' ),
					'type'      => 'yith-field',
					'yith-type' => 'onoff',
					'default'   => 'no',
					'id'        => 'ywpar_enable_point_on_membership_plan_exp',
				),

				'points_on_membership_level'           => array(
					'name'              => esc_html__( 'Points to assign:', 'yith-woocommerce-points-and-rewards' ),
					'desc'              => esc_html__( 'Set how many points to assign to members', 'yith-woocommerce-points-and-rewards' ),
					'type'              => 'yith-field',
					'yith-type'         => 'options-extrapoints-membership-plans',
					'default'           => array(
						'list' => array(
							array(
								'points' => 10,
								'plan'   => '',
							),
						),
					),
					'step'              => 1,
					'min'               => 1,
					'custom_attributes' => 'style=width:200px',
					'id'                => 'ywpar_points_on_membership_plan',
					'deps'              => array(
						'id'    => 'ywpar_enable_point_on_membership_plan_exp',
						'value' => 'yes',
						'type'  => 'hide',
					),
				),

				'point_on_membership_plan_title_end'   => array(
					'type' => 'sectionend',
					'id'   => 'ywpar_point_on_membership_plan_title_end',
				),

			);

			$all_options             = array_merge( $options['points-extra'], $membership_options );
			$options['points-extra'] = $all_options;

			return $options;
		}

		/**
		 * Assign points to user that got a specific plan.
		 *
		 * @param  YITH_WCMBS_Membership $ywm Membership object.
		 * @since  3.0.0
		 */
		public function give_points_to_membership( $ywm ) {
			$points_options = get_option( 'ywpar_points_on_membership_plan', '' );
			$customer       = ywpar_get_customer( $ywm->get_user_id() );
			$user_plan      = $customer->get_membership_plan();
			$plan_id        = (int) $ywm->get_plan()->get_id();

			if ( isset( $points_options['list'] ) ) {
				foreach ( $points_options['list'] as $plan_to_reach ) {
					if ( (int) $plan_to_reach['plan'] === $plan_id && (int) $user_plan !== $plan_id ) {
						$description = esc_html__( 'Achieved membership plan', 'yith-woocommerce-points-and-rewards' ) . ' ' . $ywm->get_plan_title();
						$customer->set_membership_plan( $ywm->get_plan()->get_id() );
						$customer->save();
						$customer->update_points( $plan_to_reach['points'], 'membership_achieved_exp', array( 'description' => $description ) );
					}
				}
			}
		}

		/**
		 * Add the option inside the customization panel
		 *
		 * @param array $options Options.
		 * @return mixed
		 */
		public function add_membership_label( $options ) {
			$customization = array();
			foreach ( $options['customization']  as $key => $value ) {
				$customization[ $key ] = $value;
				if ( 'label_num_of_orders_exp' === $key ) {
					$customization['label_membership_achieved_exp'] = array(
						'name'      => esc_html__( 'Target - Membership Plan', 'yith-woocommerce-points-and-rewards' ),
						'desc'      => '',
						'type'      => 'yith-field',
						'yith-type' => 'text',
						'default'   => esc_html__( 'Target achieved - Membership Plan', 'yith-woocommerce-points-and-rewards' ),
						'id'        => 'ywpar_label_membership_achieved_exp',
					);
				}
			}
			$options['customization'] = $customization;
			return $options;
		}
	}

	/**
	 * Unique access to instance of YWPAR_YITH_WooCommerce_Membership class
	 *
	 * @return YWPAR_YITH_WooCommerce_Membership
	 */
	function ywpar_membership() {
		return YWPAR_YITH_WooCommerce_Membership::get_instance();
	}

	ywpar_membership();
}
