<?php
/**
 * Class to earning extra points
 *
 * @class   YITH_WC_Points_Rewards_Extra_Points
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_WC_Points_Rewards_Extra_Points' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Extra_Points
	 */
	class YITH_WC_Points_Rewards_Extra_Points {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Extra_Points
		 */
		protected static $instance;

		/**
		 * List of extra points enabled.
		 *
		 * @var array
		 */
		protected static $extrapoint_active;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Extra_Points
		 * @since  1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();

		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		private function __construct() {

			self::$extrapoint_active = ywpar_get_active_extra_points_rules();

			// extrapoint on registration.
			if ( in_array( 'enable_points_on_registration_exp', self::$extrapoint_active, true ) ) {
				add_action( 'user_register', array( $this, 'extra_points_to_new_customer_registration' ), 10 );
			}

			// extrapoint on daily login.
			if ( in_array( 'enable_points_on_daily_login_exp', self::$extrapoint_active, true ) && is_user_logged_in() ) {
				add_action( 'wp_loaded', array( $this, 'extra_points_to_daily_login' ), 10 );
			}

			// extrapoint on complete profile.
			if ( in_array( 'enable_points_on_completed_profile_exp', self::$extrapoint_active, true ) ) {
				add_action( 'woocommerce_save_account_details', array( $this, 'extra_points_on_completed_profile' ), 99 );
				add_action( 'woocommerce_customer_save_address', array( $this, 'extra_points_on_completed_profile' ), 99 );
			}

			// extrapoint on birthdate.
			if ( in_array( 'enable_points_on_birthday_exp', self::$extrapoint_active, true ) && ! ( class_exists( 'YITH_WC_Coupon_Email_System' ) && get_option( 'ywces_enable_birthday' ) === 'yes' ) ) {
				YITH_WC_Points_Rewards_Extra_Points_Birthdate::init();
			}

			// extrapoint on collected points.
			if ( in_array( 'enable_point_on_collected_points_exp', self::$extrapoint_active, true ) ) {
				add_action( 'ywpar_cron', array( $this, 'extra_points_on_collected_points' ) );
			}

			if ( in_array( 'enable_review_exp', self::$extrapoint_active, true ) ) {
				if ( class_exists( 'YITH_WooCommerce_Advanced_Reviews' ) && defined( 'YITH_YWAR_PREMIUM' ) ) {
					add_action( 'ywar_review_approve_status_changed', array( $this, 'extra_points_on_review_with_advanced_reviews' ), 10, 2 );
				} else {
					add_action( 'comment_post', array( $this, 'extra_points_on_review' ), 10, 2 );
					add_action( 'wp_set_comment_status', array( $this, 'extra_points_on_review' ), 10, 2 );
					add_action( 'edit_comment', array( $this, 'extra_points_on_review' ), 10, 2 );
				}
			}

			if ( in_array( 'enable_points_on_referral_registration_exp', self::$extrapoint_active, true ) || in_array( 'enable_points_on_referral_purchase_exp', self::$extrapoint_active, true ) ) {
				YITH_WC_Points_Rewards_Referral::get_instance();
			}

			add_action( 'ywpar_customer_updated_points', array( $this, 'trigger_extra_points_action' ), 10, 4 );

		}

		/**
		 * Registration extra-points.
		 *
		 * Assign extra-points to the user is the conditions setting about the registration are valid.
		 *
		 * @param int $user_id User id.
		 */
		public function extra_points_to_new_customer_registration( $user_id ) {
			$customer = ywpar_get_customer( $user_id );
			$extras = $customer->get_extrapoint();
			$registered = false;
			if ( $extras ) {
				foreach ( $extras as $index => $ar ) {
					if ( isset( $ar['option'] ) && 'registration' === $ar['option'] ) {
						$registered = true;
						break;
					}
				}
			}
			if ( !$registered ) {
				$this->handle_actions( array( 'registration' ), $user_id );
			}
			
		}

		/**
		 * Daily Login extra-points.
		 */
		public function extra_points_to_daily_login() {
			if ( ! is_user_logged_in() ) {
				return;
			}
			$customer = ywpar_get_current_customer();

			if ( ! $customer ) {
				return;
			}

			$last_login = $customer->get_daily_login();
			$today      = new DateTime();
			$today      = $today->format( 'Y-m-d' );

			if ( empty( $last_login ) || $customer->get_daily_login() !== $today ) {
				$this->handle_actions( array( 'daily_login' ), $customer );
				$customer->set_daily_login( $today );
				$customer->save();
				/**
				 * DO_ACTION: ywpar_extra_points_for_daily_login
				 *
				 * hooks after login points given.
				 *
				 * @param YITH_WC_Points_Rewards_Customer $customer customer object
				 */
				do_action( 'ywpar_extra_points_for_daily_login', $customer );
			}

		}

		/**
		 * Profile completed extra-points.
		 *
		 * @param int $user_id User id.
		 */
		public function extra_points_on_completed_profile( $user_id ) {

			$customer = ywpar_get_customer( $user_id );

			if ( 'yes' === $customer->get_completed_profile() ) {
				return;
			}

			$full = true;
			$user = get_user_by( 'id', $user_id );

			/* check basic wp fields */
			/**
			 * APPLY_FILTERS: ywpar_save_account_details_required_fields
			 *
			 * filter the required account fields.
			 *
			 * @param array $required_fields
			 */
			$basic_fields = apply_filters(
				'ywpar_save_account_details_required_fields',
				array(
					'first_name',
					'last_name',
					'display_name',
					'user_email',
				)
			);

			foreach ( $basic_fields as $field ) {
				if ( '' === $user->$field ) {
					$full = false;
					break;
				}
			}

			if ( ! $full ) {
				return;
			}

			$wc_customer     = $customer->get_wc_customer();
			$all_fields      = WC()->checkout()->get_checkout_fields();
			$billing_fields  = $all_fields['billing'];
			$shipping_fields = $all_fields['shipping'];

			if ( $full ) {
				foreach ( $billing_fields as $field => $values ) {
					$func = 'get_' . $field;
					if ( isset( $values['required'] ) && 1 === (int) $values['required'] && is_callable( array( $wc_customer, $func ) ) && '' === $wc_customer->$func() ) {
						$full = false;
						break;
					}
				}
			}

			if ( $full ) {
				/* check for shipping */
				foreach ( $shipping_fields as $field => $values ) {
					$func = 'get_' . $field;
					if ( isset( $values['required'] ) && 1 === (int) $values['required'] && is_callable( array( $wc_customer, $func ) ) && '' === $wc_customer->$func() ) {
						$full = false;
						break;
					}
				}
			}

			/* check for extra points birthday field */
			if ( in_array( 'enable_points_on_birthday_exp', self::$extrapoint_active, true ) ) {
				if ( '' === get_user_meta( $user_id, 'yith_birthday', true ) ) {
					$full = false;
				}
			}

			if ( $full ) {
				$this->handle_actions( array( 'completed_profile' ), $customer );
				$customer->set_completed_profile( 'yes' );
				$customer->save();
			}

		}

		/**
		 * This function get the user id to pass to handle_actions method
		 * when a review status changed is triggered in YITH WooCommerce Advanced Review
		 *
		 * Triggered by 'ywar_review_approve_status_changed' YITH Advanced Review hook
		 *
		 * @param int $review_id Review id.
		 * @param int $status Status of review.
		 */
		public function extra_points_on_review_with_advanced_reviews( $review_id, $status ) {
			// only if the review is approved assign the point to the user.
			if ( 1 !== intval( $status ) || ! is_user_logged_in() ) {
				return;
			}

			$review_user = get_post_meta( $review_id, '_ywar_review_user_id', true );
			$customer    = ywpar_get_customer( $review_user );

			if ( $customer ) {
				$this->handle_actions( array( 'reviews' ), $customer );
			}

		}

		/**
		 * Triggered when a review status changes.
		 *
		 * If extra point to review is set, call the handle_actions method.
		 * Called by 'comment_post' 'wp_set_comment_status' hooks.
		 *
		 * @param int    $comment_id Comment id.
		 * @param string $status Comment status.
		 */
		public function extra_points_on_review( $comment_id, $status ) {
			if ( isset( $status['comment_approved'] ) ) {
				$status = (int) $status['comment_approved'];
			}

			if ( ( 'approve' !== $status && 1 !== $status ) || ! is_user_logged_in() ) {
				return;
			}

			$comment = get_comment( $comment_id );
			/**
			 * APPLY_FILTERS:ywpar_verified_owner_review_exp
			 *
			 * This filter allow disable the assigment of extra points to a review if the owner is not verified
			 *
			 * @return bool
			 */
			if ( 'product' !== get_post_type( $comment->comment_post_ID ) || ( apply_filters( 'ywpar_verified_owner_review_exp', false ) && ! wc_review_is_from_verified_owner( $comment_id ) ) ) {
				return;
			}

			$comment_user_id = $comment->user_id;

			/**
			 * APPLY_FILTERS:ywpar_force_reviews_by_email
			 *
			 * Force to check the review user by email
			 *
			 * @param bool
			 */
			if ( ! $comment->user_id && apply_filters( 'ywpar_force_reviews_by_email', false ) ) {
				$comment_user = get_user_by( 'email', $comment->comment_author_email );
				$comment_user_id = $comment_user->ID;
			}

			$customer = ywpar_get_customer( $comment_user_id );
			if ( $customer ) {
				$this->handle_actions( array( 'reviews' ), $customer );
			}
		}

		/**
		 * Check if it's necessary trigger extra point actions.
		 *
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 * @param int                             $points Points.
		 * @param string                          $action Action.
		 * @param array                           $args Additional arguments.
		 */
		public function trigger_extra_points_action( $customer, $points, $action, $args ) {
			if ( $points > 0 && stripos( $action, '_exp' ) === false ) {
				$this->handle_actions( array( 'points' ), $customer );
			}
		}


		/**
		 * Cron function to check collected points extra points
		 *
		 * @since  2.1.0
		 */
		public function extra_points_on_collected_points() {
			$timing = ywpar_get_option( 'points_on_collected_points_timing' );
			if ( ! $timing ) {
				return;
			}

			$result = false;
			$today  = new DateTime();

			switch ( $timing['when'] ) {
				case 'each_month':
					$current_day = $today->format( 'd' ); // current day in digit ex: 01.
					$max_days    = $today->format( 't' ); // number of days in current month.

					if ( 'first_day' === $timing['day'] && '01' === $current_day || 'last_day' === $timing['day'] && $current_day === $max_days ) {
						$result = yith_points()->points_log->get_total_points_amount_and_user_by_interval( 'monthly' );
					}
					break;
				case 'each_week':
					$current_day = $today->format( 'w' );
					if ( 'first_day' === $timing['day'] && 2 === (int) $current_day || 'last_day' === $timing['day'] && 0 === (int) $current_day ) {
						$result = yith_points()->points_log->get_total_points_amount_and_user_by_interval( 'weekly' );
					}
					break;
			}

			if ( $result ) {
				// sort the array to get the highest value first.
				array_multisort( array_column( $result, 'total' ), SORT_DESC, SORT_NUMERIC, $result );

				$max_points = $result[0]->total; // get the max value of points.

				/* filter the array to see if we have more than one user with the max value */
				$winners = array_filter(
					$result,
					function ( $value ) use ( $max_points ) {
						return (int) $value->total === (int) $max_points;
					}
				);

				$points_to_add = ywpar_get_option( 'points_on_collected_points', 0 );
				if ( $points_to_add > 0 ) {
					foreach ( $winners as $winner ) {

						$customer = ywpar_get_customer( $winner->user_id );
						if ( $customer && $customer->is_enabled() ) {
							$customer->update_points( $points_to_add, 'collected_points_exp' );
						}
					}
				}
			}

		}

		/**
		 * Add extra points to the user.
		 *
		 * @param array                               $types Type of extra points.
		 * @param int|YITH_WC_Points_Rewards_Customer $customer Customer.
		 * @param int                                 $order_id Order id.
		 *
		 * @return void|bool
		 */
		public function handle_actions( $types, $customer, $order_id = 0 ) {

			$customer = is_numeric( $customer ) ? ywpar_get_customer( $customer ) : $customer;

			/**
			 * APPLY_FILTERS: ywpar_prevent_extra_points
			 *
			 * filter to prevent or not the extra points action.
			 *
			 * @param bool $value the actual value - default false.
			 * @param array $types Type of extra points.
			 * @param int $customer_id the customer id.
			 * @param int $order_id the order id.
			 */
			if ( empty( $types ) || empty( $customer ) || ! $customer->is_enabled() || apply_filters( 'ywpar_prevent_extra_points', false, $types, $customer->get_id(), $order_id ) ) {
				return false;
			}

			$extrapoint_list    = $customer->get_extrapoint();
			$extrapoint_counter = $customer->get_extrapoint_counter();
			$current_points     = $customer->get_total_points();

			if ( empty( $extrapoint_counter ) ) {
				$extrapoint_counter = $this->populate_extra_points_counter( $customer->get_id(), $extrapoint_list );
			}
			$rules = array();
			foreach ( $types as $current_type ) {
				$extra_points_earned = 0;
				$rule                = '';
				switch ( $current_type ) {
					case 'registration':
					case 'daily_login':
					case 'completed_profile':
					case 'birthday':
						$method = 'get_item_for_' . $current_type;

						if ( method_exists( $this, $method ) ) {
							$rule = $this->$method();
							if ( $rule ) {
								array_push( $extrapoint_list, $rule );
								array_push( $rules, $rule );
								$extra_points_earned += isset( $rule['points'] ) ? $rule['points'] : 0;
							}
						}

						break;
					case 'reviews':
						$review_rules = ywpar_get_option( 'review_exp' );

						if ( empty( $review_rules['list'] ) ) {
							continue 2;
						}

						if ( ! isset( $extrapoint_counter[ $current_type ] ) ) {
							$extrapoint_counter[ $current_type ] = array(
								'starter_date' => gmdate( 'Y-m-d' ),
								'review_used'  => 0,
							);
						}

						$starter_date    = $extrapoint_counter[ $current_type ]['starter_date'];
						$usable_comments = ywpar_get_usable_comments( $customer->get_id(), $starter_date );

						$review_used = $extrapoint_counter[ $current_type ]['review_used'];
						$review_used = is_nan( $review_used ) ? 0 : $review_used;

						$review_num = $usable_comments ? count( $usable_comments ) - $review_used : 0;
						$review_num = is_nan( $review_num ) ? 0 : $review_num;
						$review_num = apply_filters( 'ypar_extrapoints_renew_num', $review_num, $review_rules['list'] );

						if ( $review_num > 0 ) {
							foreach ( $review_rules['list'] as $review_rule ) {
								if ( $review_num > 0 ) {
									$repeat = isset( $review_rule['repeat'] ) ? $review_rule['repeat'] : 0;
									$rule   = array(
										'option' => $current_type,
										'value'  => $review_rule['number'],
										'points' => $review_rule['points'],
										'repeat' => $repeat,
									);

									// check if the rule is already applied.
									if ( ! $this->check_extrapoint_rule( $rule, $extrapoint_list ) ) {
										continue;
									}

									// if the customer has enough reviews to use.
									if ( $review_num >= $rule['value'] ) {
										$repeat_times         = $repeat ? floor( $review_num / $review_rule['number'] ) : 1;
										$extra_points_earned += $repeat_times * $rule['points'];
										$review_used         += $repeat_times * $review_rule['number'];
										$review_num          -= $repeat_times * $review_rule['number'];
										$rule['repeat_times'] = $repeat_times;
										array_push( $extrapoint_list, $rule );
										array_push( $rules, $rule );
									}
								}
							}

							$extrapoint_counter[ $current_type ]['review_used'] = $review_used;
						}

						break;
					case 'points':
						if ( ! in_array( 'enable_number_of_points_exp', self::$extrapoint_active, true ) ) {
							continue 2;
						}

						$this->assign_extra_points_on_points( $extra_points_earned, $extrapoint_list, $current_points, $customer, $rules );

						break;
					case 'num_of_orders':
						if ( ! in_array( 'enable_num_order_exp', self::$extrapoint_active, true ) ) {
							continue 2;
						}

						$this->assign_extra_points_on_num_of_orders( $extra_points_earned, $extrapoint_counter, $extrapoint_list, $customer, $rules );

						break;
					case 'amount_spent':
						if ( ! in_array( 'enable_amount_spent_exp', self::$extrapoint_active, true ) ) {
							continue 2;
						}

						$this->assign_extra_points_on_amount_spent( $extra_points_earned, $extrapoint_counter, $customer, $extrapoint_list, $order_id, $rules );
						break;
					case 'level_achieved':
						if ( ! in_array( 'enable_point_on_achieve_level_exp', self::$extrapoint_active, true ) ) {
							continue 2;
						}

						$this->assign_extra_points_on_level_achieved( $extra_points_earned, $extrapoint_counter, $customer, $rules );
						break;
					case 'checkout_threshold':
						if ( ! in_array( 'enable_checkout_threshold_exp', self::$extrapoint_active, true ) || 0 === $order_id ) {
							continue 2;
						}

						$this->assign_extra_points_on_checkout_threshold( $extra_points_earned, $extrapoint_list, $order_id, $rules );

						break;
				}

				$extrapoint_list = array_filter( $extrapoint_list );
				$rules           = array_filter( $rules );
				$customer->set_extrapoint( $extrapoint_list );

				$customer->set_extrapoint_counter( $extrapoint_counter );
				$customer->save();

				if ( $extra_points_earned > 0 ) {
					$args = array( 'info' => $rules );
					if ( $order_id !== 0 ) {
						$args['order_id'] = $order_id;
					}
					$customer->update_points( $extra_points_earned, $current_type . '_exp', $args );
					$current_points += $extra_points_earned;
				}

				$customer->save();
			}
		}

		/**
		 * Check if it is possible to assign points to the customers when their points earned change.
		 *
		 * @param int                             $extra_points_earned Total points earned.
		 * @param array                           $extrapoint_list Extra point history rule applied.
		 * @param int                             $current_points Current user points.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 * @param array                           $rules List of Rules.
		 */
		public function assign_extra_points_on_points( &$extra_points_earned, &$extrapoint_list, &$current_points, $customer, &$rules = array() ) {
			$points_rules = ywpar_get_option( 'number_of_points_exp' );

			if ( empty( $points_rules ) || empty( $points_rules['list'] ) ) {
				return;
			}

			$usable_points = $customer->get_points_collected();
			usort(
				$points_rules['list'],
				function ( $a, $b ) {
					return $b['number'] <=> $a['number'];
				}
			);

			foreach ( $points_rules['list'] as $points_rule ) {
				if ( $usable_points > 0 ) {
					$repeat = isset( $points_rule['repeat'] ) ? $points_rule['repeat'] : 0;
					$rule   = array(
						'option' => 'points',
						'value'  => $points_rule['number'],
						'points' => $points_rule['points'],
						'repeat' => $repeat,
						'used'   => 0,
					);

					// check if the rule is already applied.
					if ( ! $this->check_extrapoint_rule( $rule, $extrapoint_list ) ) {
						continue;
					}

					$override = false;
					if ( $extrapoint_list ) {
						foreach ( $extrapoint_list as $key => $extrapoint_used_item ) {
							if ( ! isset( $extrapoint_used_item['option'] ) || $extrapoint_used_item['option'] !== $rule['option'] ) {
								continue;
							}

							if ( $rule['repeat'] && $extrapoint_used_item['value'] == $rule['value'] && $extrapoint_used_item['points'] == $rule['points'] ) { //phpcs:ignore
								$rule['used'] = isset( $extrapoint_used_item['used'] ) ? $extrapoint_used_item['used'] : 1;
								$override     = $key;
							}
						}
					}

					// if the customer has enough usable points to use.
					if ( $usable_points >= $rule['value'] ) {
						$repeat_times         = ( 1 === (int) $repeat ) ? floor( $usable_points / $rule['value'] ) : 1;
						$repeat_times        -= $rule['used'];
						$extra_points_earned += (int)$repeat_times * (int)$rule['points'];
						$rule['used']        += $repeat_times;
						$rule['repeat_times'] = $repeat_times;
						if ( false !== $override ) {
							$extrapoint_list[ $override ] = $rule;
						} else {
							array_push( $extrapoint_list, $rule );
						}

						array_push( $rules, $rule );

						break;
					}
				}
			}

		}

		/**
		 * Check if it is possible to assign points to the customers when their levels change.
		 *
		 * @param int                             $extra_points_earned Total points earned.
		 * @param array                           $extrapoint_counter Extra point counter.
		 * @param array                           $extrapoint_list Extra point history rule applied.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 * @param array                           $rules List of Rules.
		 */
		private function assign_extra_points_on_num_of_orders( &$extra_points_earned, &$extrapoint_counter, &$extrapoint_list, $customer, &$rules = array() ) {

			$num_order_rules = ywpar_get_option( 'num_order_exp' );

			if ( empty( $num_order_rules ) || empty( $num_order_rules['list'] ) ) {
				return;
			}

			if ( ! isset( $extrapoint_counter['num_of_orders'] ) ) {
				$extrapoint_counter['num_of_orders'] = array(
					'starter_date' => gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ),
					'order_used'   => 0,
				);
			}

			$starter_date = $extrapoint_counter['num_of_orders']['starter_date'];
			$order_used   = $extrapoint_counter['num_of_orders']['order_used'];

			$usable_num_of_order = ywpar_get_customer_order_count( $customer->get_id(), $starter_date );
			$order_num           = $usable_num_of_order - $order_used;

			if ( $order_num <= 0 ) {
				return;
			}

			foreach ( $num_order_rules['list'] as $num_order_rule ) {
				if ( $order_num > 0 ) {
					$repeat = isset( $num_order_rule['repeat'] ) ? $num_order_rule['repeat'] : 0;
					$rule   = array(
						'option' => 'num_of_orders',
						'value'  => $num_order_rule['number'],
						'points' => $num_order_rule['points'],
						'repeat' => $repeat,
					);

					// check if the rule is already applied.
					if ( ! $this->check_extrapoint_rule( $rule, $extrapoint_list ) ) {
						continue;
					}

					// if the customer has enough reviews to use.
					if ( $order_num >= $rule['value'] ) {
						$repeat_times         = ( 1 === (int) $repeat ) ? floor( $order_num / $num_order_rule['number'] ) : 1;
						$extra_points_earned += $repeat_times * (int) $rule['points'];
						$order_used          += $repeat_times * (int) $num_order_rule['number'];
						$order_num           -= $repeat_times * (int) $num_order_rule['number'];
						$rule['repeat_times'] = $repeat_times;
						array_push( $rules, $rule );
						array_push( $extrapoint_list, $rule );
					}
				}
			}

			$extrapoint_counter['num_of_orders']['order_used'] = $order_used;
		}

		/**
		 * Check if it is possible to assign points to the customers when their levels change.
		 *
		 * @param int                             $extra_points_earned Total points earned.
		 * @param array                           $extrapoint_counter Extra point counter.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 * @param array                           $rules List of Rules.
		 */
		private function assign_extra_points_on_level_achieved( &$extra_points_earned, &$extrapoint_counter, $customer, &$rules = array() ) {
			$level_rules = ywpar_get_option( 'points_on_levels' );

			if ( empty( $level_rules ) || empty( $level_rules['list'] ) ) {
				return;
			}

			$extrapoint_counter['level_achieved']['levels'] = isset( $extrapoint_counter['level_achieved'] ) ? $extrapoint_counter['level_achieved'] : array();

			$levels_achieved = $extrapoint_counter['level_achieved']['levels'];
			// to make sure that the meta is set.
			$customer->update_level( false );
			$current_level = $customer->get_level();
			$levels        = YITH_WC_Points_Rewards_Helper::get_levels_badges( 'on' );

			foreach ( $level_rules['list'] as $level_rule ) {
				$level_rule_id = (int) $level_rule['level'];
				if ( ! in_array( $level_rule_id, $levels_achieved, true ) && isset( $levels[ $level_rule_id ] ) ) {
					if ( $level_rule_id === $current_level ) {
						$extra_points_earned += (int) $level_rule['points'];
						$levels_achieved[]    = $level_rule_id;
						array_push( $rules, $level_rule );
					}
				}
			}

			$extrapoint_counter['level_achieved']['levels'] = $levels_achieved;
		}

		/**
		 * Check if it is possible to assign points to the customers when their earn points
		 *
		 * @param int                             $extra_points_earned Total points earned.
		 * @param array                           $extrapoint_counter Extra point counter.
		 * @param YITH_WC_Points_Rewards_Customer $customer Customer.
		 * @param array                           $extrapoint_list Extra point history rule applied.
		 * @param int                             $order_id Order id.
		 * @param array                           $rules List of Rules.
		 */
		private function assign_extra_points_on_amount_spent( &$extra_points_earned, &$extrapoint_counter, $customer, &$extrapoint_list, $order_id, &$rules = array() ) {

			$amount_spent_rules = ywpar_get_option( 'amount_spent_exp' );

			if ( empty( $amount_spent_rules ) || empty( $amount_spent_rules['list'] ) ) {
				return;
			}

			if ( ! isset( $extrapoint_counter['amount_spent'] ) ) {
				$extrapoint_counter['amount_spent'] = array(
					'starter_date' => gmdate( 'Y-m-d', time() - DAY_IN_SECONDS ),
				);
			}

			$starter_date     = $extrapoint_counter['amount_spent']['starter_date'];
			$wc_customer      = $customer->get_id();
			$usable_amount    = yith_ywpar_calculate_user_total_orders_amount( $wc_customer, $order_id, $starter_date );
			$amount           = $usable_amount;
			$current_currency = ywpar_get_currency();

			usort(
				$amount_spent_rules['list'],
				function ( $a, $b ) {
					if ( isset( $b['number'], $a['number'] ) ) {
						return $b['number'] <=> $a['number'];
					}
				}
			);

			foreach ( $amount_spent_rules['list'] as $amount_spent_rule ) {
				if ( $amount > 0 ) {
					$repeat = isset( $amount_spent_rule['repeat'] ) ? (int) $amount_spent_rule['repeat'] : 0;
					$rule   = array(
						'option' => 'amount_spent',
						'value'  => $amount_spent_rule[ $current_currency ]['number'],
						'points' => $amount_spent_rule[ $current_currency ]['points'],
						'repeat' => $repeat,
						'used'   => 0,
					);

					// check if the rule is already applied.
					if ( ! $this->check_extrapoint_rule( $rule, $extrapoint_list ) ) {
						continue;
					}

					$override = false;
					if ( $extrapoint_list ) {
						foreach ( $extrapoint_list as $key => $extrapoint_used_item ) {
							if ( ! isset( $extrapoint_used_item['option'] ) || $extrapoint_used_item['option'] !== $rule['option'] ) {
								continue;
							}

							if ( $rule['repeat'] && $extrapoint_used_item['value'] == $rule['value'] && $extrapoint_used_item['points'] == $rule['points'] ) { //phpcs:ignore
								$rule['used'] = isset( $extrapoint_used_item['used'] ) ? $extrapoint_used_item['used'] : 1;
								$override     = $key;
							}
						}
					}

					// if the customer has enough reviews to use.
					if ( $amount >= $rule['value'] ) {
						$repeat_times         = ( 1 === $repeat ) ? floor( $amount / $amount_spent_rule[ $current_currency ]['number'] ) : 1;
						$repeat_times        -= $rule['used'];
						$extra_points_earned += (int) $repeat_times * (int) $rule['points'];
						$rule['used']        += $repeat_times;
						$rule['repeat_times'] = $repeat_times;
						if ( false !== $override ) {
							$extrapoint_list[ $override ] = $rule;
						} else {
							array_push( $extrapoint_list, $rule );
						}

						array_push( $rules, $rule );

						break;
					}
				}
			}
		}

		/**
		 * Check if it is possible to assign points to the customers when their earn points
		 *
		 * @param int   $extra_points_earned Total points earned.
		 * @param array $extrapoint_list Extra point history rule applied.
		 * @param int   $order_id Order id.
		 * @param array $rules List of Rules.
		 */
		private function assign_extra_points_on_checkout_threshold( &$extra_points_earned, &$extrapoint_list, $order_id, &$rules = array() ) {
			$order            = wc_get_order( $order_id );
			/**
			* APPLY_FILTERS: ywpar_extra_points_on_checkout_threshold_total
			*
			* Manage order total for extra points on checkout threshold.
			* 
			* @param float    $total order total.
			* @param WC_Order $order the order.
			* 
			*/
			$total            = apply_filters( 'ywpar_extra_points_on_checkout_threshold_total', $order->get_total(), $order );
			$current_currency = $order->get_currency();
			$thresholds       = array();

			$checkout_thresholds = ywpar_get_option( 'checkout_threshold_exp' );
			if ( empty( $checkout_thresholds ) || empty( $checkout_thresholds['list'] ) ) {
				return;
			}

			foreach ( $checkout_thresholds['list'] as $list ) {
				if ( isset( $list[ $current_currency ] ) ) {
					$list[ $current_currency ]['repeat'] = isset( $list['repeat'] ) ? $list['repeat'] : 0;
					$thresholds[]                        = $list[ $current_currency ];
				}
			}

			// sort the thresholds array by number value.
			array_multisort( array_column( $thresholds, 'number' ), SORT_DESC, $thresholds );

			if ( ! empty( $thresholds ) ) {
				foreach ( $thresholds as $threshold ) {
					if ( ! empty( $threshold['number'] ) && ! empty( $threshold['points'] ) ) {

						$rule = array(
							'option' => 'checkout_thresholds',
							'value'  => $threshold['number'],
							'points' => $threshold['points'],
						);

						if ( $total >= $rule['value'] ) {

							$extra_points_earned += $rule['points'];
							array_push( $extrapoint_list, $rule );
							array_push( $rules, $rule );
							if ( ywpar_get_option( 'checkout_threshold_not_cumulate' ) === 'yes' ) {
								break;
							}
						}
					}
				}
			}
		}

		/**
		 * Return the item for the registration extra points.
		 *
		 * @return bool|array
		 */
		private function get_item_for_registration() {
			$item        = false;
			$point_value = ywpar_get_option( 'points_on_registration', 0 );

			if ( ! empty( $point_value ) && $point_value > 0 ) {
				$item = array(
					'option'    => 'registration',
					'value'     => 1,
					'points'    => $point_value,
					'date_from' => gmdate( 'Y-m-d' ),
				);
			}

			return $item;
		}

		/**
		 * Return the item for the birthday extra points.
		 *
		 * @return bool|array
		 */
		private function get_item_for_birthday() {
			$item        = false;
			$point_value = ywpar_get_option( 'points_on_birthday', 0 );

			if ( ! empty( $point_value ) && $point_value > 0 ) {
				$item = array(
					'option'    => 'birthday',
					'value'     => 1,
					'points'    => $point_value,
					'date_from' => gmdate( 'Y-m-d' ),
				);
			}

			return $item;
		}

		/**
		 * Return the item for the completed profile extra points.
		 *
		 * @return bool|array
		 */
		private function get_item_for_completed_profile() {
			$item        = false;
			$point_value = ywpar_get_option( 'points_on_completed_profile', 0 );

			if ( ! empty( $point_value ) && $point_value > 0 ) {
				$item = array(
					'option'    => 'completed_profile',
					'value'     => 1,
					'points'    => $point_value,
					'date_from' => gmdate( 'Y-m-d' ),
				);
			}

			return $item;
		}

		/**
		 * Return the item for the daily login extra points.
		 *
		 * @return bool|array
		 */
		private function get_item_for_daily_login() {
			$item        = false;
			$point_value = ywpar_get_option( 'points_on_daily_login', 0 );

			if ( ! empty( $point_value ) && $point_value > 0 ) {
				$item = array(
					'option'    => 'daily_login',
					'value'     => 1,
					'points'    => $point_value,
					'date_from' => gmdate( 'Y-m-d' ),
				);
			}

			return $item;
		}

		/**
		 * Porting old extra-points method to the new
		 *
		 * @param int   $user_id User id.
		 * @param array $user_extra_point Extrapoint list.
		 *
		 * @return array
		 */
		public function populate_extra_points_counter( int $user_id, array $user_extra_point ) {
			$extra_point_counter = array();

			$review_counter       = 0;
			$num_orders           = 0;
			$amount_used          = 0;
			$current_starter_date = gmdate( 'Y-m-d', time() - DAY_IN_SECONDS );

			if ( $user_extra_point ) {
				foreach ( $user_extra_point as $item ) {
					if ( isset( $item['option'] ) ) {
						switch ( $item['option'] ) {
							case 'reviews':
								$review_counter += $item['value'];
								break;
							case 'num_of_orders':
								$num_orders += $item['value'];
								break;
							case 'amount_spent':
								$amount_used += $item['value'];
								break;
						}
					}
				}
			}

			$today = new DateTime();

			// reviews.
			$extra_point_counter['reviews']['review_used']  = $review_counter;
			$extra_point_counter['reviews']['starter_date'] = $today->format( 'Y-m-d' );

			// num of orders.
			if ( $num_orders ) {
				$date_earning = yith_points()->points_log->get_start_date_of_action( 'num_of_orders_exp', $user_id );
				$starter_date = $date_earning ? $date_earning : $current_starter_date;
			} else {
				$starter_date = $current_starter_date;
			}

			$extra_point_counter['num_of_orders']['order_used']   = $num_orders;
			$extra_point_counter['num_of_orders']['starter_date'] = $starter_date;

			// amount used.
			if ( $amount_used ) {
				$date_earning = yith_points()->points_log->get_start_date_of_action( 'amount_spent_exp', $user_id );
				$starter_date = $date_earning ? $date_earning : $current_starter_date;
			} else {
				$starter_date = $current_starter_date;
			}

			$extra_point_counter['amount_spent']['amount_used']  = $amount_used;
			$extra_point_counter['amount_spent']['starter_date'] = $starter_date;

			return $extra_point_counter;

		}

		/**
		 * Check if an extra-points rule is used by customer.
		 *
		 * @param array $rule Extra point rule.
		 * @param array $extrapoint_list Extra point list.
		 *
		 * @return bool
		 */
		public function check_extrapoint_rule( $rule, $extrapoint_list ) {
			$result = true;

			if ( $extrapoint_list ) {
				foreach ( $extrapoint_list as $extrapoint_used_item ) {
					if ( ! isset( $extrapoint_used_item['option'] ) || $extrapoint_used_item['option'] !== $rule['option'] ) {
						continue;
					}
					if ( ! $rule['repeat'] ) {
						if ( $extrapoint_used_item['value'] == $rule['value'] && $extrapoint_used_item['points'] == $rule['points'] ) { //phpcs:ignore
							$result = false;
						}
					}
				}
			}

			return $result;
		}

	}

}

