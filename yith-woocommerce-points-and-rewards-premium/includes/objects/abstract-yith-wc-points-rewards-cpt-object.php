<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * General class to manage custom post types
 *
 * @class   YITH_WC_Points_Rewards_Cpt_Object
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Cpt_Object' ) ) {
	/**
	 * Abstract class
	 */
	abstract class YITH_WC_Points_Rewards_Cpt_Object {
		/**
		 * Array of data
		 *
		 * @var array
		 */
		protected $data = array();


		/**
		 * Post type name
		 *
		 * @var string
		 */
		protected $post_type = '';

		/**
		 * ID of post type
		 *
		 * @var int
		 */
		protected $id;

		/**
		 * Object type
		 *
		 * @var string
		 */
		protected $object_type = 'cpt_object';

		/**
		 * Object read
		 *
		 * @var bool
		 */
		protected $object_read = false;

		/**
		 * YITH_WC_Points_Rewards_Cpt_Object constructor.
		 *
		 * @param mixed $obj Object.
		 */
		public function __construct( $obj ) {

			if ( 0 === $obj ) {
				return false;
			}

			if ( is_numeric( $obj ) && $obj > 0 ) {
				$this->set_id( $obj );
			} elseif ( $obj instanceof self ) {
				$this->set_id( absint( $obj->get_id() ) );
			} elseif ( ! empty( $obj->ID ) ) {
				$this->set_id( absint( $obj->ID ) );
			}

			if ( $this->get_id() ) {
				if ( ! $this->post_type || get_post_type( $this->get_id() ) === $this->post_type ) {
					$this->populate_props();
					$this->object_read = true;
				} else {
					$this->set_id( 0 );
				}
			}

			return false;
		}

		/**
		 * Prefix for action and filter hooks on data.
		 *
		 * @return string
		 */
		protected function get_hook_prefix() {
			return 'ywpar_' . $this->object_type . '_get_';
		}

		/**
		 * Prefix for action and filter hooks on data.
		 *
		 * @return string
		 */
		protected function get_hook() {
			return 'ywpar_' . $this->object_type . '_get';
		}


		/**
		 * Get object properties
		 *
		 * @param string $prop Properties.
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return mixed
		 */
		protected function get_prop( $prop, $context = 'view' ) {
			$value = null;

			if ( array_key_exists( $prop, $this->data ) ) {
				$value = $this->data[ $prop ];

				if ( 'view' === $context ) {
					$value = apply_filters( $this->get_hook_prefix() . $prop, $value, $this );
					$value = apply_filters( $this->get_hook(), $value, $prop, $this );
				}
			}

			return $value;
		}

		/**
		 * Return the meta by prop
		 *
		 * @param string $prop Property name.
		 * @return string
		 */
		protected function get_meta_by_prop( $prop ) {
			return '_' . $prop;
		}

		/**
		 * Populate all props
		 */
		protected function populate_props() {
			foreach ( $this->data as $prop => $default_value ) {
				$meta   = $this->get_meta_by_prop( $prop );
				$value  = metadata_exists( 'post', $this->get_id(), $meta ) ? get_post_meta( $this->get_id(), $meta, true ) : $default_value;
				$setter = "set_{$prop}";
				if ( method_exists( $this, $setter ) ) {
					$this->$setter( $value );
				} else {
					$this->set_prop( $prop, $value );
				}
			}
		}

		/**
		 * Set an object property
		 *
		 * @param string $prop Property name.
		 * @param mixed  $value The value of the properties.
		 */
		protected function set_prop( $prop, $value ) {
			if ( array_key_exists( $prop, $this->data ) ) {
				$this->data[ $prop ] = $value;
			}
		}

		/**
		 * Set object properties
		 *
		 * @param array $props Properties.
		 */
		public function set_props( $props ) {
			foreach ( $props as $key => $value ) {
				$setter = 'set_' . $key;
				if ( is_callable( array( $this, $setter ) ) ) {
					$this->$setter( $value );
				} else {
					$this->set_prop( $key, $value );
				}
			}
		}


		/**
		 * Merge changes with data and clear.
		 */
		protected function update_post_meta() {
			$props_to_update = $this->data;

			foreach ( $props_to_update as $prop => $value ) {
				$meta = $this->get_meta_by_prop( $prop );
				update_post_meta( $this->id, $meta, $value );
			}
		}

		/**
		 * Store options in DB
		 *
		 * @return int
		 */
		public function save() {
			$this->update_post_meta();
			return $this->get_id();
		}

		/**
		 * Return the object ID
		 *
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Set the object ID
		 *
		 * @param int $id ID.
		 */
		public function set_id( $id ) {
			$this->id = absint( $id );
		}

		/**
		 * Check if the post type is valid
		 *
		 * @return bool
		 */
		public function is_valid() {
			return ! ! $this->get_id() && ( ! $this->post_type || get_post_type( $this->get_id() ) === $this->post_type );
		}

		/**
		 * Trash the post
		 */
		public function trash() {
			return wp_trash_post( $this->get_id() );
		}

		/**
		 * Delete the post
		 */
		public function delete() {
			return wp_delete_post( $this->get_id() );
		}

		/**
		 * Return the post_status
		 *
		 * @return string
		 */
		public function get_post_status() {
			return get_post_status( $this->get_id() );
		}

		/**
		 * Return the data
		 *
		 * @return array
		 */
		public function get_data() {
			return array_merge( $this->data, array( 'id' => $this->get_id() ) );
		}

		/**
		 * Check if the rule is valid for the user.
		 *
		 * @param int $user_id User id.
		 * @return bool
		 */
		public function is_valid_for_user( $user_id = 0 ) {

			$is_valid = true;
			$type     = $this->get_user_type();

			if ( 'all' === $type ) {
				return $is_valid;
			}

			$customer = ywpar_get_customer( $user_id );

			if ( is_array( $type ) ) {

				if ( empty( $type ) ) {
					return false;
				}

				if ( ! is_user_logged_in() && in_array( 'customer', $type ) ) {
					$is_valid = true;
				} elseif ( $customer ) {
					if ( in_array( 'levels', $type, true ) ) {
						$levels = $this->get_user_levels_list();
						if ( ! in_array( $customer->get_level(), $levels ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$is_valid = false;
						}
						$type = array_diff( $type, array( 'levels' ) );
					}

					if ( $is_valid && in_array( 'membership', $type, true ) && defined( 'YITH_WCMBS_PREMIUM' ) ) {
						$plans          = $this->get_user_plans_list();
						$customer_plans = array_keys( $customer->get_membership_plans() );
						$intersect      = array_intersect( $plans, $customer_plans );
						if ( empty( $intersect ) ) {
							$is_valid = false;
						}

						$type = array_diff( $type, array( 'membership' ) );
					}

					if ( $is_valid ) {
						$user_role = $customer->get_roles();
						$intersect = array_intersect( (array) $user_role, $type );
						if ( empty( $intersect ) ) {
							$is_valid = false;
						}
					}
				}
			} else {
				if ( ! is_user_logged_in() ) {
					if ( 'roles' === $type ) {
						$enabled_roles = $this->get_user_roles_list();
						$is_valid      = in_array( 'customer', $enabled_roles, true );
					}
					
					if ( 'levels' === $type || 'membership' === $type) {
						$is_valid = false;
					}
				} elseif ( $customer ) {
					if ( 'roles' === $type ) {
						$enabled_roles = $this->get_user_roles_list();
						$user_role     = $customer->get_roles();
						$intersect     = array_intersect( (array) $user_role, $enabled_roles );
						$is_valid      = ! empty( $intersect );
					}

					if ( 'levels' === $type ) {
						$levels = $this->get_user_levels_list();
						if ( ! in_array( $customer->get_level(), $levels ) ) { //phpcs:ignore
							$is_valid = false;
						}
					}

					if ( 'membership' === $type && defined( 'YITH_WCMBS_PREMIUM' ) ) {
						$plans          = $this->get_user_plans_list();
						$customer_plans = array_keys( $customer->get_membership_plans() );
						$intersect      = array_intersect( $plans, $customer_plans );
						if ( empty( $intersect ) ) {
							$is_valid = false;
						}
					}
				}
			}

			return $is_valid;
		}

		/**
		 * Check if the product is valid
		 *
		 * @param int $product_id Product id.
		 */
		public function is_valid_for_product( $product_id ) {
			$is_valid  = false;
			$product   = wc_get_product( $product_id );
			$parent_id = $product->get_parent_id();


			if ( 'all_products' === $this->get_apply_to() ) {
				$is_valid = true;
				if ( 'yes' === $this->get_exclude_products() ) {
					$excluded_products = $this->get_exclude_products_list();
					$is_valid          = ! in_array( $product_id, $excluded_products ) && ! ( $parent_id && in_array( $parent_id, $excluded_products ) ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
				}
			}

			if ( 'selected_products' === $this->get_apply_to() ) {
				$selected_products = $this->get_apply_to_products_list();
				$is_valid          = in_array( $product_id, $selected_products ) || ( $parent_id && in_array( $parent_id, $selected_products ) ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
			}

			if ( 'on_sale_products' === $this->get_apply_to() ) {

				if ( 'yes' === $this->get_exclude_products() ) {
					$excluded_products = $this->get_exclude_products_list();
					if ( in_array( $product_id, $excluded_products ) || ( $parent_id && in_array( $parent_id, $excluded_products ) ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						return false;
					}
				}

				$is_valid = $product->is_on_sale();
			}

			if ( 'selected_categories' === $this->get_apply_to() ) {
				$is_valid = false;
				if ( 'yes' === $this->get_exclude_products() ) {
					$excluded_products = $this->get_exclude_products_list();
					if ( in_array( $product_id, $excluded_products ) || ( $parent_id && in_array( $parent_id, $excluded_products ) ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						return false;
					}
				}

				$selected_categories = $this->get_apply_to_categories_list();
				$categories          = $product->is_type( 'variation' ) ? get_the_terms( $parent_id, 'product_cat' ) : get_the_terms( $product_id, 'product_cat' );

				if ( $categories && $selected_categories ) {
					foreach ( $categories as $category ) {
						if ( in_array( $category->term_id, $selected_categories ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$is_valid = true;
							break;
						}
					}
				}
			}

			if ( 'selected_tags' === $this->get_apply_to() ) {
				$is_valid = false;
				if ( 'yes' === $this->get_exclude_products() ) {
					$excluded_products = $this->get_exclude_products_list();
					if ( in_array( $product_id, $excluded_products ) || ( $parent_id && in_array( $parent_id, $excluded_products ) ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						return false;
					}
				}

				$selected_tags = $this->get_apply_to_tags_list();
				$tags          = $product->is_type( 'variation' ) ? get_the_terms( $parent_id, 'product_tag' ) : get_the_terms( $product_id, 'product_tag' );

				if ( $tags && $selected_tags ) {
					foreach ( $tags as $tag ) {
						if ( in_array( $tag->term_id, $selected_tags ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
							$is_valid = true;
							break;
						}
					}
				}
			}

			return $is_valid;
		}
	}
}
