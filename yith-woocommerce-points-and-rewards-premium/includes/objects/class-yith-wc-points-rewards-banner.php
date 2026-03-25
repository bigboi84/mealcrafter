<?php
/**
 * Class to manage banners for my account page
 *
 * @class   YITH_WC_Points_Rewards_Banner
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Cpt_Object', false ) ) {
	include_once YITH_YWPAR_INC . '/objects/abstract-yith-wc-points-rewards-cpt-object.php';
}

if ( ! class_exists( 'YITH_WC_Points_Rewards_Banner' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Banner
	 */
	class YITH_WC_Points_Rewards_Banner extends YITH_WC_Points_Rewards_Cpt_Object {

		/**
		 * Array of data
		 *
		 * @var array
		 */
		protected $data = array(
			'name'                        => '',
			'status'                      => 'on',
			'priority'                    => 1,
			'type'                        => 'target',
			'action_type'                 => 'simple',
			'action_target_type'          => 'simple',
			'title'                       => '',
			'subtitle'                    => '',
			'image'                       => '',
			'banner_colors'               => array(),
			'link_status'                 => 'no',
			'link_url'                    => '',
			'progress_bar_status'         => 'no',
			'progress_bar_type'           => '',
			'progress_bar_colors'         => array(),
			'max_review_products_to_show' => 5,
			'simple_position'             => 'target',
		);

		/**
		 * Post type name
		 *
		 * @var string
		 */
		protected $post_type = 'ywpar-banner';

		/**
		 * Object type
		 *
		 * @var string
		 */
		protected $object_type = 'banner';

		/**
		 * Return the name of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_name( $context = 'view' ) {
			return $this->get_prop( 'name', $context );
		}

		/**
		 * Return the status of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_status( $context = 'view' ) {
			return $this->get_prop( 'status', $context );
		}

		/**
		 * Return the status of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_simple_position( $context = 'view' ) {
			return $this->get_prop( 'simple_position', $context );
		}

		/**
		 * Return the type of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_type( $context = 'view' ) {
			return $this->get_prop( 'type', $context );
		}

		/**
		 * Return the type of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_type_name() {
			switch ( $this->get_type() ) {
				case 'simple':
					$name = __( 'Simple', 'yith-woocommerce-points-and-rewards' );
					break;

				case 'target':
					$name = __( 'Target', 'yith-woocommerce-points-and-rewards' );
					break;

				case 'get_points':
					$name = __( 'Get points', 'yith-woocommerce-points-and-rewards' );
					break;

				default:
					$name = '';
					break;
			}

			return $name;

		}

		/**
		 * Return the action type of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_action_type( $context = 'view' ) {
			if ( $this->get_type() === 'simple' ) {
				return '';
			}

			if ( $this->get_type() === 'target' ) {
				return $this->get_prop( 'action_target_type', $context );
			}
			return $this->get_prop( 'action_type', $context );
		}

		/**
		 * Return the title of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_title( $context = 'view' ) {
			return $this->get_prop( 'title', $context );
		}

		/**
		 * Return the subtitle of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_subtitle( $context = 'view' ) {
			return $this->get_prop( 'subtitle', $context );
		}

		/**
		 * Return the image of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_image( $context = 'view' ) {
			return $this->get_prop( 'image', $context );
		}

		/**
		 * Return the colors of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_banner_colors( $context = 'view' ) {
			return $this->get_prop( 'banner_colors', $context );
		}

		/**
		 * Return the link status of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_link_status( $context = 'view' ) {
			return $this->get_prop( 'link_status', $context );
		}

		/**
		 * Return the link url of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_link_url( $context = 'view' ) {
			return $this->get_prop( 'link_url', $context );
		}

		/**
		 * Return the progress bar status of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_progress_bar_status( $context = 'view' ) {
			return $this->get_prop( 'progress_bar_status', $context );
		}

		/**
		 * Return the progress bar type of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return string
		 */
		public function get_action_target_type( $context = 'view' ) {
			return $this->get_prop( 'action_target_type', $context );
		}

		/**
		 * Return the progress bar colors of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_progress_bar_colors( $context = 'view' ) {
			return $this->get_prop( 'progress_bar_colors', $context );
		}

		/**
		 * Return the priority of the banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return array
		 */
		public function get_priority( $context = 'view' ) {
			return $this->get_prop( 'priority', $context );
		}

		/**
		 * Return the max product to show for review banner
		 *
		 * @param string $context What the value is for. Valid values are view and edit.
		 * @return int
		 */
		public function get_max_review_products_to_show( $context = 'view' ) {
			return (int) $this->get_prop( 'max_review_products_to_show', $context );
		}

		/**
		 * Set the status of banner
		 *
		 * @param string $value The value to set.
		 */
		public function set_status( $value ) {
			$this->set_prop( 'status', $value );
		}

		/**
		 * Set the priority of banner
		 *
		 * @param string $value The value to set.
		 */
		public function set_priority( $value ) {
			$this->set_prop( 'priority', $value );
		}

		/**
		 * Set single position of banner
		 *
		 * @param string $value The value to set.
		 */
		public function set_simple_position( $value ) {
			$this->set_prop( 'simple_position', $value );
		}


		/**
		 * Set the progress bar status of banner
		 *
		 * @param string $value The value to set.
		 */
		public function set_progress_bar_status( $value ) {
			$this->set_prop( 'progress_bar_status', $value );
		}

		/**
		 * Set the max product to show for review banner
		 *
		 * @param int $value The value to set.
		 */
		public function set_max_review_products_to_show( $value ) {
			$this->set_prop( 'max_review_products_to_show', (int) $value );
		}

		/**
		 * Get Banner template
		 *
		 * @since   2.2.0
		 */
		public function get_template() {
			$type   = $this->get_type();
			$action = $this->get_action_type();
			if ( 'target' === $type ) {
				$action = $this->get_action_target_type();
			}

			$template_filename = ywpar_get_banner_template_filename( $action );

			if ( ! empty( $template_filename ) ) {

				$template_filename = '/myaccount/ywpar-banner-' . $template_filename . '.php';
				if ( file_exists( YITH_YWPAR_TEMPLATE_PATH . $template_filename ) ) {
					$args = array(
						'banner'   => $this,
						'customer' => ywpar_get_current_customer(),
					);

					wc_get_template( $template_filename, $args, '', YITH_YWPAR_TEMPLATE_PATH );
				}
			}

			return false;
		}

		/**
		 * Check if the product is valid
		 *
		 * @param int $product_id Product id.
		 */
		public function is_valid_for_product( $product_id ) {
			return true;
		}


		/**
		 * Check if the banner is valid for customer.
		 *
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 *
		 * @return bool
		 */
		public function is_valid_for_customer( $customer ) {
			$is_valid = true;

			$type   = $this->get_type();
			$action = $this->get_action_type();

			if ( 'simple' === $type ) {
				return $is_valid;
			}

			if ( 'target' === $type ) {
				$action = $this->get_action_target_type();
			}

			switch ( $action ) {
				case 'enable_number_of_points_exp':
					if ( 'yes' === ywpar_get_option( 'enable_number_of_points_exp' ) ) {
						$steps    = $this->get_steps_of_target_points_banner( $customer );
						$is_valid = ( 0 !== count( $steps ) );
					} else {
						$is_valid = false;
					}
					break;
				case 'enable_point_on_achieve_level_exp':
					if ( 'yes' === ywpar_get_option( 'enable_point_on_achieve_level_exp' ) ) {
						$steps    = $this->get_steps_of_target_level_banner( $customer );
						$is_valid = isset( $steps['steps'] ) && 0 !== count( $steps['steps'] );
					} else {
						$is_valid = false;
					}

					break;
				case 'enable_amount_spent_exp':
					if ( 'yes' === ywpar_get_option( 'enable_amount_spent_exp' ) ) {
						$steps = $this->get_steps_of_amount_spent_banner( $customer );

						$is_valid = isset( $steps ) && 0 !== count( $steps );
					} else {
						$is_valid = false;
					}
					break;
				case 'enable_points_on_completed_profile_exp':
					if ( empty( $customer->get_completed_profile() ) ) {
						yith_points()->extra_points->extra_points_on_completed_profile( get_current_user_id() );
					}

					$is_valid = ( 'yes' === ywpar_get_option( 'enable_points_on_completed_profile_exp' ) ) && 'yes' !== $customer->get_completed_profile();
					break;
				case 'enable_points_on_referral_registration_exp':
					$is_valid = 'yes' === ywpar_get_option( 'enable_points_on_referral_registration_exp' );
					break;
				case 'enable_points_on_referral_purchase_exp':
					$is_valid = 'yes' === ywpar_get_option( 'enable_points_on_referral_purchase_exp' );
					break;
				case 'enable_review_exp':
					$is_valid = 'yes' === ywpar_get_option( 'enable_review_exp' );
					break;
			}

			return $is_valid;
		}

		/**
		 * Calculate step of target level banner
		 *
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 *
		 * @return array
		 */
		public function get_steps_of_target_level_banner( $customer ) {
			$steps           = array();
			$steps_to_points = array();
			$options         = ywpar_get_option( 'points_on_levels' );
			$points          = $customer->get_points_collected();

			foreach ( $options['list'] as $option => $values ) {
				$level            = ywpar_get_level_badge( $values['level'] );
				$option_lvl_range = $level->get_points_to_collect();

				if ( (int) $points < (int) $option_lvl_range['from'] ) {
					$steps[ $option_lvl_range['from'] ]  = $values['level'];
					$steps_to_points[ $values['level'] ] = $values['points'];
				}
			}
			return array(
				'steps'           => $steps,
				'steps_to_points' => $steps_to_points,
			);
		}

		/**
		 * Calculate step of target banner
		 *
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 *
		 * @return array
		 */
		public function get_steps_of_target_points_banner( $customer ) {
			$steps             = array();
			$usable_points     = $customer->get_points_collected();
			$user_extra_points = $customer->get_extrapoint();

			$options = ywpar_get_option( 'number_of_points_exp' );

			usort(
				$options['list'],
				function ( $a, $b ) {
					return $b['number'] <=> $a['number'];
				}
			);

			foreach ( $options['list'] as $values ) {

				$rule = array(
					'option' => 'points',
					'value'  => $values['number'],
					'points' => $values['points'],
					'repeat' => isset( $values['repeat'] ) ? $values['repeat'] : 0,
					'used'   => 0,
				);

				if ( $user_extra_points ) {
					foreach ( $user_extra_points as $extrapoint_used_item ) {
						if ( ! isset( $extrapoint_used_item['option'] ) || $extrapoint_used_item['option'] !== $rule['option'] ) {
							continue;
						}

						if ( $rule['repeat'] && $extrapoint_used_item['value'] == $rule['value'] && $extrapoint_used_item['points'] == $rule['points'] ) { //phpcs:ignore
							$rule['used'] = isset( $extrapoint_used_item['used'] ) ? $extrapoint_used_item['used'] : 1;
						}
					}
				}

				if ( yith_points()->extra_points->check_extrapoint_rule( $rule, $user_extra_points ) ) {
					if ( $usable_points < $values['number'] ) {
						if ( 0 === $rule['used'] && yith_points()->extra_points->check_extrapoint_rule( $rule, $user_extra_points ) ) {
							$steps[ $values['number'] ] = $values['points'];
						}
					}

					if ( $rule['repeat'] ) {
						$target = $values['number'];
						if ( $rule['repeat'] ) {
							$target = $values['number'] + $values['number'] * $rule['used'];
							while ( $usable_points > $target ) {
								$target += $values['number'];
							}
						}

						$steps[ $target ] = $values['points'];
					}
				}
			}

			ksort( $steps );
			return $steps;
		}

		/**
		 * Calculate step of target banner
		 *
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 *
		 * @return array
		 */
		public function get_steps_of_amount_spent_banner( $customer ) {
			$steps             = array();
			$usable_amount     = $customer->get_usable_amount();
			$current_currency  = ywpar_get_currency();
			$options           = ywpar_get_option( 'amount_spent_exp' );
			$user_extra_points = $customer->get_extrapoint();

			$currency_filtered = array();
			if ( $options['list'] ) {
				$currency_filtered = array();
				foreach ( $options['list'] as $item ) {
					if ( isset( $item['repeat'] ) ) {
						$item[ $current_currency ]['repeat'] = $item['repeat'];
					}
					array_push( $currency_filtered, $item[ $current_currency ] );
				}
			}

			usort(
				$currency_filtered,
				function ( $a, $b ) {
					return $b['number'] <=> $a['number'];
				}
			);

			foreach ( $currency_filtered as $amount_spent_rule ) {
				$rule = array(
					'option' => 'amount_spent',
					'value'  => $amount_spent_rule['number'],
					'points' => $amount_spent_rule['points'],
					'repeat' => isset( $amount_spent_rule['repeat'] ) ? $amount_spent_rule['repeat'] : 0,
					'used'   => 0,
				);

				if ( $user_extra_points ) {
					foreach ( $user_extra_points as $extrapoint_used_item ) {
						if ( ! isset( $extrapoint_used_item['option'] ) || $extrapoint_used_item['option'] !== $rule['option'] ) {
							continue;
						}

						if ( $rule['repeat'] && $extrapoint_used_item['value'] == $rule['value'] && $extrapoint_used_item['points'] == $rule['points'] ) { //phpcs:ignore
							$rule['used'] = isset( $extrapoint_used_item['used'] ) ? $extrapoint_used_item['used'] : 1;
						}
					}
				}

				if ( $usable_amount < $rule['value'] & yith_points()->extra_points->check_extrapoint_rule( $rule, $customer->get_extrapoint() ) ) {
					$steps[ $rule['value'] ] = $rule['points'];
				}

				if ( yith_points()->extra_points->check_extrapoint_rule( $rule, $user_extra_points ) ) {
					if ( $usable_amount < $amount_spent_rule['number'] ) {
						if ( 0 === $rule['used'] && yith_points()->extra_points->check_extrapoint_rule( $rule, $user_extra_points ) ) {
							$steps[ $amount_spent_rule['number'] ] = $amount_spent_rule['points'];
						}
					}

					if ( $rule['repeat'] ) {
						$target = $amount_spent_rule['number'];
						if ( $rule['repeat'] ) {
							$target = $amount_spent_rule['number'] + $amount_spent_rule['number'] * $rule['used'];
							while ( $usable_amount > $target ) {
								$target += $amount_spent_rule['number'];
							}
						}

						$steps[ $target ] = $amount_spent_rule['points'];
					}
				}
			}

			ksort( $steps );

			return $steps;
		}
	}
}

if ( ! function_exists( 'ywpar_get_banner' ) ) {
	/**
	 * Return the banner object
	 *
	 * @param mixed $banner Banner.
	 *
	 * @return YITH_WC_Points_Rewards_Banner
	 */
	function ywpar_get_banner( $banner ) {

		if ( function_exists( 'wpml_object_id_filter' ) ) {
			global $sitepress;

			if ( ! is_null( $sitepress ) && is_callable( array( $sitepress, 'get_current_language' ) ) ) {
				$banner = wpml_object_id_filter( $banner, 'post', true, $sitepress->get_current_language() );
			}
		}
		return new YITH_WC_Points_Rewards_Banner( $banner );
	}
}
