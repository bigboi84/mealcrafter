<?php
/**
 * Class to manage redeeming rules
 *
 * @class   YITH_WC_Points_Rewards_Redeeming_Rule
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Cpt_Object', false ) ) {
	include_once YITH_YWPAR_INC . '/objects/abstract-yith-wc-points-rewards-cpt-object.php';
}

if ( ! class_exists( 'YITH_WC_Points_Rewards_Redeeming_Rule' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Redeeming_Rule
	 */
	class YITH_WC_Points_Rewards_Redeeming_Rule extends YITH_WC_Points_Rewards_Cpt_Object {

		/**
		 * Array of data
		 *
		 * @var array
		 */
		protected $data = array(
			'name'                       => '',
			'priority'                   => 1,
			'status'                     => 'on',
			'type'                       => 'conversion_rate',
			'conversion_rate'            => array(),
			'percentage_conversion_rate' => array(),
			'maximum_discount_type'      => 'percentage',
			'max_discount'               => 0,
			'max_discount_percentage'    => 50,
			'apply_to'                   => 'all_products',
			'apply_to_products_list'     => array(),
			'apply_to_categories_list'   => array(),
			'apply_to_tags_list'         => array(),
			'exclude_products'           => 'no',
			'exclude_products_list'      => array(),
			'user_type'                  => 'all',
			'user_roles_list'            => array(),
			'user_levels_list'           => array(),
			'user_plans_list'            => array(),
		);

		/**
		 * Post type name
		 *
		 * @var string
		 */
		protected $post_type = 'ywpar-redeeming-rule';

		/**
		 * Object type
		 *
		 * @var string
		 */
		protected $object_type = 'redeeming_rule';

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
		 * Return the type of this rule
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_type( $context = 'view' ) {
			return $this->get_prop( 'type', $context );
		}

		/**
		 * Return the conversion rate of this rule
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_conversion_rate( $context = 'view' ) {
			return (array) $this->get_prop( 'conversion_rate', $context );
		}


		/**
		 * Return the percentage conversion rate of this rule
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_percentage_conversion_rate( $context = 'view' ) {
			return (array) $this->get_prop( 'percentage_conversion_rate', $context );
		}

		/**
		 * Return the maximum discount type of this rule
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_maximum_discount_type( $context = 'view' ) {
			return $this->get_prop( 'maximum_discount_type', $context );
		}

		/**
		 * Return the maximum fixed discount
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 */
		public function get_max_discount( $context = 'view' ) {
			return (int) $this->get_prop( 'max_discount', $context );
		}

		/**
		 * Return the maximum fixed discount
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 */
		public function get_max_discount_percentage( $context = 'view' ) {
			return (int) $this->get_prop( 'max_discount_percentage', $context );
		}

		/**
		 * Return the type of products to apply this rule
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
		 * Return if there are products to exclude
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
		 * Return the type of users enabled
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return mixed
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
		 * Return the list of level
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_user_levels_list( $context = 'view' ) {
			return (array) $this->get_prop( 'user_levels_list', $context );
		}


		/**
		 * Return the list of plans
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_user_plans_list( $context = 'view' ) {
			return (array) $this->get_prop( 'user_plans_list', $context );
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
		 * Set the name of the rule
		 *
		 * @param string $value The value to set.
		 */
		public function set_name( $value ) {
			$this->set_prop( 'name', $value );
		}

		/**
		 * Set the priority of the rule
		 *
		 * @param int $value The value to set.
		 */
		public function set_priority( $value ) {
			$this->set_prop( 'priority', (int) $value );
		}

		/**
		 * Set the type of the rule
		 *
		 * @param string $value The value to set.
		 */
		public function set_type( $value ) {
			$this->set_prop( 'type', $value );
		}

		/**
		 * Set the fixed conversion rate
		 *
		 * @param array $value The value to set.
		 */
		public function set_conversion_rate( $value ) {
			$this->set_prop( 'conversion_rate', (array) $value );
		}

		/**
		 * Set the percentage conversion rate
		 *
		 * @param array $value The value to set.
		 */
		public function set_percentage_conversion_rate( $value ) {
			$this->set_prop( 'percentage_conversion_rate', (array) $value );
		}

		/**
		 * Set the maximum discount type
		 *
		 * @param string $value The value to set.
		 */
		public function set_maximum_discount_type( $value ) {
			$this->set_prop( 'maximum_discount_type', $value );
		}

		/**
		 * Set the max point fixed discount
		 *
		 * @param int $value The value to set.
		 */
		public function set_max_discount( $value ) {
			$this->set_prop( 'max_discount', (int) $value );
		}

		/**
		 * Set the max point percentage discount
		 *
		 * @param int $value The value to set.
		 */
		public function set_max_discount_percentage( $value ) {
			$this->set_prop( 'max_discount_percentage', (int) $value );
		}


		/**
		 * Set the type of products to apply the rule
		 *
		 * @param string $value The value to set.
		 */
		public function set_apply_to( $value ) {
			$this->set_prop( 'apply_to', $value );
		}

		/**
		 * Set the product list
		 *
		 * @param array $value The value to set.
		 */
		public function set_apply_to_products_list( $value ) {
			$this->set_prop( 'apply_to_products_list', (array) $value );
		}

		/**
		 * Set the category list
		 *
		 * @param array $value The value to set.
		 */
		public function set_apply_to_categories_list( $value ) {
			$this->set_prop( 'apply_to_categories_list', (array) $value );
		}

		/**
		 * Set the tag list
		 *
		 * @param array $value The value to set.
		 */
		public function set_apply_to_tags_list( $value ) {
			$this->set_prop( 'apply_to_tags_list', (array) $value );
		}

		/**
		 * Set if exclude products
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
			$this->set_prop( 'exclude_products_list', (array) $value );
		}

		/**
		 * Set the type of users enabled
		 *
		 * @param mixed $value The value to set.
		 */
		public function set_user_type( $value ) {
			$this->set_prop( 'user_type', $value );
		}

		/**
		 * Set the role list
		 *
		 * @param array $value The value to set.
		 */
		public function set_user_roles_list( $value ) {
			$this->set_prop( 'user_roles_list', (array) $value );
		}

		/**
		 * Set the list of level
		 *
		 * @param array $value The value to set.
		 */
		public function set_user_levels_list( $value ) {
			$this->set_prop( 'user_levels_list', (array) $value );
		}


		/**
		 * Set the list of level
		 *
		 * @param array $value The value to set.
		 */
		public function set_user_plans_list( $value ) {
			$this->set_prop( 'user_plans_list', (array) $value );
		}


		/**
		 * Check if the rule is valid for the product.
		 *
		 * @param int $product_id Product id.
		 * @return bool
		 */
		public function is_valid_for_product( $product_id ) {

			if ( 'conversion_rate' === $this->get_type() && apply_filters( 'ywpar_is_valid_for_product_force', true ) ) {
				return true;
			}

			return parent::is_valid_for_product( $product_id );
		}

		/**
		 * Return the max discount that can be applied to a product
		 *
		 * @param float  $price Product price.
		 * @param string $currency Currency.
		 *
		 * @return float
		 */
		public function calculate_max_discount( $price, $currency ) {
			$max_discount = $price;

			if ( 'max_discount' === $this->get_type() ) {
				$discount_type = $this->get_maximum_discount_type();
				if ( 'fixed' === $discount_type ) {
					$max_discount = $this->get_max_discount();
				} else {
					$max_discount_percentage = (float) $this->get_max_discount_percentage();
					$max_discount            = (float) ( $price * $max_discount_percentage ) / 100;
				}
			}

			return $max_discount;
		}

		/**
		 * Set the coherence between global user option and the rule user option
		 */
		public function update_user_type_options() {
			$global_role_enabled = ywpar_get_option( 'user_role_redeem_type', 'all' );

			$current_user_type = $this->get_user_type();
			if ( 'all' === $global_role_enabled && is_array( $current_user_type ) ) {
				if ( in_array( 'levels', $current_user_type, true ) ) {
					$this->set_user_type( 'levels' );
				} else {
					$this->set_user_type( 'roles' );
					$this->set_user_roles_list( $current_user_type );
				}

				if ( in_array( 'membership', $current_user_type, true ) ) {
					$this->set_user_type( 'membership' );
				} else {
					$this->set_user_type( 'roles' );
					$this->set_user_roles_list( $current_user_type );
				}
			} else {
				$roles_enabled = ywpar_get_option( 'user_role_redeem_enabled' );
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

if ( ! function_exists( 'ywpar_get_redeeming_rule' ) ) {
	/**
	 * Return the redeeming rule object
	 *
	 * @param mixed $redeeming_rule Redeeming rule.
	 *
	 * @return YITH_WC_Points_Rewards_Redeeming_Rule
	 */
	function ywpar_get_redeeming_rule( $redeeming_rule ) {
		return new YITH_WC_Points_Rewards_Redeeming_Rule( $redeeming_rule );
	}
}
