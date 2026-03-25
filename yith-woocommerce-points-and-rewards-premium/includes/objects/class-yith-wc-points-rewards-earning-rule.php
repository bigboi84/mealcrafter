<?php
/**
 * Class to manage an earning rule
 *
 * @class   YITH_WC_Points_Rewards_Earning_Rule
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Cpt_Object', false ) ) {
	include_once YITH_YWPAR_INC . '/objects/abstract-yith-wc-points-rewards-cpt-object.php';
}

if ( ! class_exists( 'YITH_WC_Points_Rewards_Earning_Rule' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Earning_Rule
	 */
	class YITH_WC_Points_Rewards_Earning_Rule extends YITH_WC_Points_Rewards_Cpt_Object {

		/**
		 * Array of data
		 *
		 * @var array
		 */
		protected $data = array(
			'name'                        => '',
			'status'                      => 'on',
			'priority'                    => 1,
			'points_type_conversion'      => 'fixed',
			'fixed_points_to_earn'        => 0,
			'percentage_points_to_earn'   => 0,
			'earn_points_conversion_rate' => array(),
			'apply_to'                    => 'all_products',
			'apply_to_products_list'      => array(),
			'apply_to_categories_list'    => array(),
			'apply_to_tags_list'          => array(),
			'exclude_products'            => 'no',
			'exclude_products_list'       => array(),
			'user_type'                   => 'all',
			'user_roles_list'             => array(),
			'user_levels_list'            => array(),
			'user_plans_list'             => array(),
			'is_rule_scheduled'           => 'no',
			'rule_schedule'               => array(),
		);

		/**
		 * Post type name
		 *
		 * @var string
		 */
		protected $post_type = 'ywpar-earning-rule';

		/**
		 * Object type
		 *
		 * @var string
		 */
		protected $object_type = 'earning_rule';

		/**
		 * Return the name of the rule
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_name( $context = 'view' ) {
			return $this->get_prop( 'name', $context );
		}

		/**
		 * Return the status of this level
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_status( $context = 'view' ) {
			return $this->get_prop( 'status', $context );
		}


		/**
		 * Return the priority of this rule
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 */
		public function get_priority( $context = 'view' ) {
			return (int) $this->get_prop( 'priority', $context );
		}

		/**
		 * Return the points type conversion of this rule
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_points_type_conversion( $context = 'view' ) {
			return $this->get_prop( 'points_type_conversion', $context );
		}

		/**
		 * Return the points to earn when the type of conversion is fixed
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 */
		public function get_fixed_points_to_earn( $context = 'view' ) {
			return (int) $this->get_prop( 'fixed_points_to_earn', $context );
		}

		/**
		 * Return the points to earn when the type of conversion is percentage
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return float
		 */
		public function get_percentage_points_to_earn( $context = 'view' ) {
			return (float) $this->get_prop( 'percentage_points_to_earn', $context );
		}

		/**
		 * Return the points conversion rate when the type of conversion is percentage
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_earn_points_conversion_rate( $context = 'view' ) {
			return (array) $this->get_prop( 'earn_points_conversion_rate', $context );
		}

		/**
		 * Return the type of elements where the rule will be applied
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_apply_to( $context = 'view' ) {
			return $this->get_prop( 'apply_to', $context );
		}

		/**
		 * Return the list of products
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_apply_to_products_list( $context = 'view' ) {
			return (array) $this->get_prop( 'apply_to_products_list', $context );
		}


		/**
		 * Return the list of categories
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_apply_to_categories_list( $context = 'view' ) {
			return (array) $this->get_prop( 'apply_to_categories_list', $context );
		}

		/**
		 * Return the list of tags
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_apply_to_tags_list( $context = 'view' ) {
			return (array) $this->get_prop( 'apply_to_tags_list', $context );
		}

		/**
		 * Return the excluded products option
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_exclude_products( $context = 'view' ) {
			return $this->get_prop( 'exclude_products', $context );
		}

		/**
		 * Return the list of products to exclude
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_exclude_products_list( $context = 'view' ) {
			return (array) $this->get_prop( 'exclude_products_list', $context );
		}

		/**
		 * Return the option user_type
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_user_type( $context = 'view' ) {
			return $this->get_prop( 'user_type', $context );
		}

		/**
		 * Return the role list
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_user_roles_list( $context = 'view' ) {
			return (array) $this->get_prop( 'user_roles_list', $context );
		}

		/**
		 * Return the level list
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_user_levels_list( $context = 'view' ) {
			return (array) $this->get_prop( 'user_levels_list', $context );
		}

		/**
		 * Return the plans list
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_user_plans_list( $context = 'view' ) {
			return (array) $this->get_prop( 'user_plans_list', $context );
		}


		/**
		 * Return if the rule is scheduled.
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_is_rule_scheduled( $context = 'view' ) {
			return $this->get_prop( 'is_rule_scheduled', $context );
		}

		/**
		 * Return the scheduled period
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_rule_schedule( $context = 'view' ) {
			return (array) $this->get_prop( 'rule_schedule', $context );
		}

		/**
		 * Set the status of the rule
		 *
		 * @param string $value The value to set.
		 */
		public function set_status( $value ) {
			$this->set_prop( 'status', $value );
		}

		/**
		 * Set the priority of the rule
		 *
		 * @param string $value The value to set.
		 */
		public function set_priority( $value ) {
			$this->set_prop( 'priority', (int) $value );
		}

		/**
		 * Set the points_type_conversion of the rule
		 *
		 * @param string $value The value to set.
		 */
		public function set_points_type_conversion( $value ) {
			$this->set_prop( 'points_type_conversion', $value );
		}

		/**
		 * Set the points to earn when the type of conversion is fixed
		 *
		 * @param int $value The value to set.
		 */
		public function set_fixed_points_to_earn( $value ) {
			$this->set_prop( 'fixed_points_to_earn', (int) $value );
		}

		/**
		 * Set the points to earn when the type of conversion is percentage
		 *
		 * @param float $value The value to set.
		 */
		public function set_percentage_points_to_earn( $value ) {
			$this->set_prop( 'percentage_points_to_earn', (float) $value );
		}


		/**
		 * Set the name to the rule
		 *
		 * @param string $value The value to set.
		 */
		public function set_name( $value ) {
			$this->set_prop( 'name', $value );
		}

		/**
		 * Set to which assign the points
		 *
		 * @param string $value The value to set.
		 */
		public function set_apply_to( $value ) {
			$this->set_prop( 'apply_to', $value );
		}

		/**
		 * Set the list of products
		 *
		 * @param array $value The value to set.
		 */
		public function set_apply_to_products_list( $value ) {
			$this->set_prop( 'apply_to_products_list', $value );
		}

		/**
		 * Set the list of categories
		 *
		 * @param array $value The value to set.
		 */
		public function set_apply_to_categories_list( $value ) {
			$this->set_prop( 'apply_to_categories_list', $value );
		}

		/**
		 * Set the list of tags
		 *
		 * @param array $value The value to set.
		 */
		public function set_apply_to_tags_list( $value ) {
			$this->set_prop( 'apply_to_tags_list', $value );
		}

		/**
		 * Set exclude products option
		 *
		 * @param string $value The value to set.
		 */
		public function set_exclude_products( $value ) {
			$this->set_prop( 'exclude_products', $value );
		}

		/**
		 * Set the list of products to exclude
		 *
		 * @param array $value The value to set.
		 */
		public function set_exclude_products_list( $value ) {
			$this->set_prop( 'exclude_products_list', $value );
		}

		/**
		 * Set the conversion rate
		 *
		 * @param array $value The value to set.
		 */
		public function set_earn_points_conversion_rate( $value ) {
			$this->set_prop( 'earn_points_conversion_rate', (array) $value );
		}

		/**
		 * Set the user type to apply
		 *
		 * @param mixed $value The value to set.
		 */
		public function set_user_type( $value ) {
			$this->set_prop( 'user_type', $value );
		}

		/**
		 * Set the user role list
		 *
		 * @param array $value The value to set.
		 */
		public function set_user_roles_list( $value ) {
			$this->set_prop( 'user_roles_list', (array) $value );
		}

		/**
		 * Set the user level list
		 *
		 * @param array $value The value to set.
		 */
		public function set_user_levels_list( $value ) {
			$this->set_prop( 'user_levels_list', (array) $value );
		}

		/**
		 * Set the user plan list
		 *
		 * @param array $value The value to set.
		 */
		public function set_user_plans_list( $value ) {
			$this->set_prop( 'user_plans_list', (array) $value );
		}

		/**
		 * Set the user level list
		 *
		 * @param string $value The value to set.
		 */
		public function set_is_rule_scheduled( $value ) {
			$this->set_prop( 'is_rule_scheduled', $value );
		}

		/**
		 * Set the user level list
		 *
		 * @param array $value The value to set.
		 */
		public function set_rule_schedule( $value ) {
			$this->set_prop( 'rule_schedule', (array) $value );
		}

		/**
		 * Add the category to the list of categories.
		 *
		 * @param int $category_id Category id to add.
		 */
		public function add_category_to_the_list( $category_id ) {
			$selected_categories = $this->get_apply_to_categories_list();
			array_push( $selected_categories, $category_id );
			$this->set_apply_to_categories_list( $selected_categories );
		}


		/**
		 * Add the product to the list of products.
		 *
		 * @param int $product_id Product id to add.
		 */
		public function add_product_to_the_list( $product_id ) {
			$selected_products = $this->get_apply_to_products_list();
			array_push( $selected_products, $product_id );
			$this->set_apply_to_products_list( $selected_products );
		}

		/**
		 * Check if the rule is valid now
		 *
		 * @return bool
		 */
		public function is_valid_now() {
			$is_valid = true;

			if ( 'yes' === $this->get_is_rule_scheduled() ) {
				$scheduled = $this->get_rule_schedule();
				$is_valid  = ywpar_check_date_interval( strtotime( $scheduled['from'] ), strtotime( $scheduled['to'] ) );
			}

			return $is_valid;
		}


		/**
		 * Check if the rule is valid for the category.
		 *
		 * @param int $term_id Category id.
		 * @return bool
		 */
		public function is_valid_for_category( $term_id ) {

			$is_valid = false;

			if ( 'selected_categories' === $this->get_apply_to() ) {
				$categories = $this->get_apply_to_categories_list();

				if ( in_array( $term_id, $categories, true ) ) {
					$is_valid = true;
				}
			}

			return $is_valid;
		}

		/**
		 * Calculate points
		 *
		 * @param WC_Product $product Product.
		 * @param float|int  $global_points Global points amount.
		 * @param string     $currency Currency.
		 * @return float|int
		 */
		public function calculate_points( $product, $global_points, $currency ) {

			$type   = $this->get_points_type_conversion();
			$points = 0;
			switch ( $type ) {
				case 'fixed':
					$points = $this->get_fixed_points_to_earn();
					break;
				case 'not_assign':
					$points = 0;
					break;
				case 'percentage':
					$points = ( $this->get_percentage_points_to_earn() * $global_points ) / 100;
					break;
				case 'override':
					$conversion_rate = $this->get_earn_points_conversion_rate();
					$conversion_rate = ( isset( $conversion_rate[ $currency ] ) && $conversion_rate[ $currency ]['money'] > 0 ) ? $conversion_rate[ $currency ] : yith_points()->earning->get_conversion_option( $currency );
					$price           = ywpar_get_product_price( $product, 'earn', $currency );
					$points          = (float) $price / $conversion_rate['money'] * $conversion_rate['points'];
					break;
			}

			return $points;
		}


		/**
		 * Set the coherence between global user option and the rule user option
		 */
		public function update_user_type_options() {
			$global_role_enabled = ywpar_get_option( 'user_role_enabled_type', 'all' );

			$current_user_type = $this->get_user_type();
			if ( 'all' === $global_role_enabled && is_array( $current_user_type ) ) {
				if ( in_array( 'levels', $current_user_type, true ) ) {
					$this->set_user_type( 'levels' );
					unset( $current_user_type['levels'] );
				}

				if ( in_array( 'membership', $current_user_type, true ) ) {
					$this->set_user_type( 'membership' );
					unset( $current_user_type['membership'] );
				}

				if ( empty( $current_user_type ) ) {
					$this->set_user_type( 'roles' );
					$this->set_user_roles_list( $current_user_type );
				}
			} else {
				$roles_enabled = ywpar_get_option( 'user_role_enabled' );

				if ( is_array( $current_user_type ) ) {
					$rule_role_enabled = array_intersect( $current_user_type, $roles_enabled );
					if ( in_array( 'levels', $current_user_type, true ) ) {
						array_push( $rule_role_enabled, 'levels' );
					}
					$this->set_user_type( $rule_role_enabled );
				} else {
					if ( 'all' === $current_user_type ) {
						$rule_role_enabled = $roles_enabled;
					}

					if ( 'roles' === $current_user_type ) {
						$rule_role_enabled = array_intersect( $this->get_user_roles_list(), $roles_enabled );
					}

					if ( 'levels' === $current_user_type ) {
						$rule_role_enabled = array( 'levels' );
					}

					if ( 'membership' === $current_user_type ) {
						$rule_role_enabled = array( 'membership' );
					}

					$this->set_user_type( $rule_role_enabled );
				}
			}

			$this->save();
		}

		/**
		 * Add the role inside the role list
		 *
		 * @param string $role Role to add.
		 */
		public function add_role_to_the_list( $role ) {
			$user_type = $this->get_user_type();

			if ( is_array( $user_type ) ) {
				array_push( $user_type, $role );

				$this->set_user_type( array_unique( $user_type ) );
			} else {
				$role_list = $this->get_user_roles_list();
				array_push( $role_list, $role );

				$this->set_user_roles_list( array_unique( $role_list ) );
			}
		}

	}
}

if ( ! function_exists( 'ywpar_get_earning_rule' ) ) {
	/**
	 * Return the earning rule object
	 *
	 * @param mixed $earning_rule Earning Rule.
	 * @return YITH_WC_Points_Rewards_Earning_Rule
	 */
	function ywpar_get_earning_rule( $earning_rule ) {
		return new YITH_WC_Points_Rewards_Earning_Rule( $earning_rule );
	}
}
