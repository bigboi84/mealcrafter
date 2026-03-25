<?php
/**
 * Customer Class
 *
 * @class   YITH_WC_Points_Rewards_Customer
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Customer' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Customer
	 */
	class YITH_WC_Points_Rewards_Customer {

		/**
		 * Customer id
		 *
		 * @var int
		 */
		protected $id;

		/**
		 * WC Customer
		 *
		 * @var WC_Customer
		 */
		protected $wc_customer = false;


		/**
		 * Customer points data
		 *
		 * @var array
		 */
		protected $data = array(
			'_ywpar_user_total_points'     => 0,
			'_ywpar_user_total_discount'   => 0,
			'_ywpar_extrapoint'            => array(),
			'_ywpar_rewarded_points'       => 0,
			'_ywpar_used_points'           => 0,
			'_ywpar_extrapoint_counter'    => array(),
			'_ywpar_user_level'            => 0,
			'_ywpar_points_collected'      => 0,
			'_ywpar_daily_login'           => '',
			'_ywpar_completed_profile'     => '',
			'_ywpar_referral_registration' => array(),
			'_ywpar_user_reusable_points'  => 0,
			'_ywpar_shared_coupons'        => array(),
			'ywpar_last_birthday_points'   => '',
			'ywpar_membership_plan'        => 0,
			'_ywpar_rank'                  => 0,
		);

		/**
		 * Metakey to data
		 *
		 * @var array
		 */
		protected $meta_key_to_props = array(
			'_ywpar_user_total_points'     => 'total_points',
			'_ywpar_user_total_discount'   => 'total_discount',
			'_ywpar_extrapoint'            => 'extrapoint',
			'_ywpar_rewarded_points'       => 'rewarded_points',
			'_ywpar_used_points'           => 'used_points',
			'_ywpar_extrapoint_counter'    => 'extrapoint_counter',
			'_ywpar_user_level'            => 'level',
			'_ywpar_points_collected'      => 'points_collected', // these points are useful to assign levels.
			'_ywpar_daily_login'           => 'daily_login',
			'_ywpar_completed_profile'     => 'completed_profile',
			'_ywpar_referral_registration' => 'referral_registration',
			'_ywpar_user_reusable_points'  => 'reusable_points',
			'_ywpar_shared_coupons'        => 'shared_coupons',
			'ywpar_last_birthday_points'   => 'last_birthday_points',
			'ywpar_membership_plan'        => 'membership_plan',
			'_ywpar_rank'                  => 'rank',
		);

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @param   mixed $customer  Customer.
		 *
		 * @return void|bool
		 * @since 1.0.0
		 */
		public function __construct( $customer ) {

			if ( is_numeric( $customer ) ) {
				$this->set_id( $customer );
			} elseif ( $customer instanceof self ) {
				$this->set_id( $this->get_id() );
			}

			if ( $this->get_id() > 0 ) {
				$this->populate();
			} else {
				return false;
			}

		}

		/**
		 * Populate the data of customer.
		 */
		protected function populate() {
			$save = false;
			foreach ( $this->data as $prop => $default ) {
				$blog_prop = $prop . ywpar_get_blog_suffix();
				$value     = $default;

				if ( metadata_exists( 'user', $this->get_id(), $blog_prop ) ) {
					$value = get_user_meta( $this->get_id(), $blog_prop, true );
				} elseif ( metadata_exists( 'user', $this->get_id(), $prop ) ) {
					$value = get_user_meta( $this->get_id(), $prop, true );
				} elseif ( '_ywpar_points_collected' === $prop ) {
					$save  = true;
					$value = yith_points()->points_log->get_collected_points( $this->id );
				}

				$setter = "set_{$prop}";
				$setter = isset( $this->meta_key_to_props[ $prop ] ) ? 'set_' . $this->meta_key_to_props[ $prop ] : $setter;
				if ( method_exists( $this, $setter ) ) {
					$this->$setter( $value );
				} else {
					$this->set_prop( $prop, $value );
				}
			}

			if ( $save ) {
				$this->save();
			}

			$this->update_level( false );
		}


		/**
		 * Set the customer id
		 *
		 * @param   int $customer_id  Customer id.
		 */
		public function set_id( $customer_id ) {
			$this->id = absint( $customer_id );
		}

		/**
		 * Set rank
		 *
		 * @param   int $rank  Current rank.
		 */
		public function set_rank( $rank ) {
			$this->data['_ywpar_rank'] = (int) $rank;
		}

		/**
		 * Set the reusable points
		 *
		 * @param   int $reusable_points  Points.
		 */
		public function set_reusable_points( $reusable_points ) {
			$this->data['_ywpar_user_reusable_points'] = (int) $reusable_points;
		}


		/**
		 * Set the user points
		 *
		 * @param   int $used_points  Used points id.
		 */
		public function set_used_points( $used_points ) {
			$this->data['_ywpar_used_points'] = (int) $used_points;
		}

		/**
		 * Return the total points of customer
		 *
		 * @param   int $total_points  Total points.
		 */
		public function set_total_points( $total_points ) {
			$total_points = ( empty( $total_points ) || ( apply_filters( 'ywpar_disable_negative_point_on_customer_total_points', true, $this->get_id() ) && $total_points < 0 ) ) ? 0 : (int) $total_points;

			$this->data['_ywpar_user_total_points'] = (int) $total_points;
		}

		/**
		 * Return the points collected by customer
		 *
		 * This meta is used to calculate the customer level.
		 *
		 * @param   int $points_collected  Total points collected.
		 */
		public function set_points_collected( $points_collected ) {
			$points_collected = ( empty( $points_collected ) || $points_collected < 0 ) ? 0 : (int) $points_collected;

			$this->data['_ywpar_points_collected'] = (int) $points_collected;
		}


		/**
		 * Set the extra point list to the customer
		 *
		 * @param   array $extrapoint  Extra Point.
		 */
		public function set_extrapoint( $extrapoint ) {
			$this->data['_ywpar_extrapoint'] = (array) $extrapoint;
		}

		/**
		 * Set shared coupons
		 *
		 * @param   array $shared_coupons  Coupon list.
		 */
		public function set_shared_coupons( $shared_coupons ) {
			$this->data['_ywpar_shared_coupons'] = (array) $shared_coupons;
		}

		/**
		 * Set the extra point counter to the customer
		 *
		 * @param   array $extrapoint_counter  Extra Point counter.
		 */
		public function set_extrapoint_counter( $extrapoint_counter ) {
			$this->data['_ywpar_extrapoint_counter'] = (array) $extrapoint_counter;
		}

		/**
		 * Set the list of users registered by this customer
		 *
		 * @param   array $referral_registration  Referral registration list.
		 */
		public function set_referral_registration( $referral_registration ) {
			$this->data['_ywpar_referral_registration'] = (array) $referral_registration;
		}

		/**
		 * Set the last birthday for this customer
		 *
		 * @param   string $last_birthday_points  Last birthday points.
		 */
		public function set_last_birthday_points( $last_birthday_points ) {
			$this->data['ywpar_last_birthday_points'] = $last_birthday_points;
		}

		/**
		 * Set the membership plan for this customer
		 *
		 * @param   int $membership_plan  Membership plan.
		 */
		public function set_membership_plan( $membership_plan ) {
			$this->data['ywpar_membership_plan'] = (int) $membership_plan;
		}

		/**
		 * Set the customer id
		 *
		 * @param   int $level  YITH_WC_Points_Rewards_Level_Badge id.
		 */
		public function set_level( $level ) {
			$this->data['_ywpar_user_level'] = $level;
		}


		/**
		 * Set the customer id
		 *
		 * @param   int $rewarded_points  Rewarded Points.
		 */
		public function set_rewarded_points( $rewarded_points ) {
			$this->data['_ywpar_rewarded_points'] = (int) $rewarded_points;
		}


		/**
		 * Set the total discount
		 *
		 * @param   float $discount_amount  Discount amount.
		 */
		public function set_total_discount( $discount_amount ) {
			$this->data['_ywpar_user_total_discount'] = (float) $discount_amount;
		}

		/**
		 * Set the daily login date
		 *
		 * @param   string $date  Date to register.
		 */
		public function set_daily_login( $date ) {
			$this->data['_ywpar_daily_login'] = $date;
		}

		/**
		 * Set the completed profile
		 *
		 * @param   string $completed_profile  Completed profile.
		 */
		public function set_completed_profile( $completed_profile ) {
			$this->data['_ywpar_completed_profile'] = $completed_profile;
		}

		/**
		 * Set the customer id
		 *
		 * @return int
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Return the data of customer.
		 *
		 * @return array
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Set the customer id
		 *
		 * @return WC_Customer
		 */
		public function get_wc_customer() {
			if ( ! $this->wc_customer ) {
				try {
					$this->wc_customer = new WC_Customer( $this->get_id() );
				} catch ( Exception $e ) {
					$this->wc_customer = false;
				}
			}

			return $this->wc_customer;
		}

		/**
		 * Get Products to review by user id
		 *
		 * @param   int $limit  Products to show.
		 *
		 * @return array
		 * @since  3.0.0
		 */
		public function get_products_to_review( $limit = 5 ) {
			$orders = wc_get_orders(
				array(
					'customer_id' => $this->get_id(),
					/**
					 * APPLY_FILTERS: ywpar_get_order_status_for_review
					 *
					 * This filter allows adding, removing or changing the order status that the order must have to retrieve the products available for a review.
					 *
					 * @param   array  $order_status  List of order status.
					 *
					 * @return array
					 */
					'status'      => apply_filters( 'ywpar_get_order_status_for_review', array( 'wc-completed' ) ),
				)
			);

			$products = array();

			foreach ( $orders as $order ) {
				foreach ( $order->get_items() as $item ) {
					$product_id = $item->get_product_id();
					$product    = wc_get_product( $product_id );
					if ( ! $product || ! $product->get_reviews_allowed() ) {
						continue;
					}

					$query = array(
						'status'  => 1,
						'user_id' => $this->get_id(),
						'post_id' => $product_id,
					);

					$comments = get_comments( $query );

					if ( ! $comments && ! isset( $products[ $product_id ] ) ) {
						$products[ $product_id ] = $order->get_date_completed()->date_i18n();
						if ( count( $products ) >= $limit ) {
							break 2;
						}
					}
				}
			}
			/**
			 * APPLY_FILTERS: ywpar_get_products_to_review
			 *
			 * filter description.
			 *
			 * @param array $products list of the products to review.
			 * @param int $user_id the id of the user.
			 */
			return apply_filters( 'ywpar_get_products_to_review', $products, $this->get_id() );
		}

		/**
		 * Get the user birthdate
		 *
		 * Check if there's the date registered on YITH WooCommerce Coupons Email System.
		 *
		 * @return mixed
		 */
		public function get_birthdate() {
			$birthdate = get_user_meta( $this->get_id(), 'yith_birthday', true );
			if ( empty( $birthdate ) ) {
				$birthdate = get_user_meta( $this->get_id(), 'ywces_birthday', true );

				if ( ! empty( $birthdate ) ) {
					update_user_meta( $this->get_id(), 'yith_birthday', $birthdate );
				}
			}

			return $birthdate;
		}

		/**
		 * Return the last birthday for this customer
		 *
		 * @return string
		 */
		public function get_last_birthday_points() {
			return (int) $this->data['ywpar_last_birthday_points'];
		}


		/**
		 * Return the membership plan
		 *
		 * @return int
		 */
		public function get_membership_plan() {
			return (int) $this->data['ywpar_membership_plan'];
		}

		/**
		 * Return the customer name
		 */
		public function get_name() {
			$wc_customer = $this->get_wc_customer();

			if ( $wc_customer->get_first_name() || $wc_customer->get_last_name() ) {
				// translators:First placeholder: user id; second placeholder: user first name; third placeholder: user last name.
				$name = sprintf( _x( '#%1$d - %2$s %3$s', 'First placeholder: user id; second placeholder: user first name; third placeholder: user last name', 'yith-woocommerce-points-and-rewards' ), $this->get_id(), $wc_customer->get_first_name(), $wc_customer->get_last_name() );
			} else {
				// translators:First placeholder: user id; second placeholder: user display name.
				$name = sprintf( _x( '#%1$d - %2$s', 'First placeholder: user id; second placeholder: user display name', 'yith-woocommerce-points-and-rewards' ), $this->get_id(), $wc_customer->get_display_name() );
			}

			/**
			 * APPLY_FILTERS: ywpar_customer_name
			 *
			 * filter the customer name.
			 *
			 * @param string $name the user name.
			 * @param int $id the user id.
			 * @param YITH_WC_Points_Rewards_Customer $customer the customer object
			 */
			return apply_filters( 'ywpar_customer_name', $name, $this->get_id(), $this );
		}

		/**
		 * Return the total points of customer
		 *
		 * @return int
		 */
		public function get_total_points() {
			$total_points = (int) $this->data['_ywpar_user_total_points'];
			$total_points = ( empty( $total_points ) || ( apply_filters( 'ywpar_disable_negative_point_on_customer_total_points', true, $this->get_id() ) && $total_points < 0 ) ) ? 0 : (int) $total_points;

			return $total_points;
		}

		/**
		 * Check if the customer can share points
		 *
		 * @return bool
		 */
		public function can_share_points() {
			$is_enabled   = false;
			$total_points = $this->get_total_points();

			if ( $total_points > 0 ) {
				$is_enabled = YITH_WC_Points_Rewards_Share_Points::are_number_points_valid_to_share( $total_points );
			}

			/**
			 * APPLY_FILTERS: ywpar_customer_can_share_points
			 *
			 * user can share points.
			 *
			 * @param bool $is_enabled
			 * @param YITH_WC_Points_Rewards_Customer $customer the customer
			 */
			return apply_filters( 'ywpar_customer_can_share_points', $is_enabled, $this );
		}

		/**
		 * Return the points collected by customer
		 *
		 * @return int
		 */
		public function get_points_collected() {
			$points_collected = (int) $this->data['_ywpar_points_collected'];

			return empty( $points_collected ) ? 0 : (int) $points_collected;
		}

		/**
		 * Return the total rewarded points of customer
		 *
		 * @param   bool $recalculate  If yes calculate the value from db.
		 *
		 * @return int
		 */
		public function get_rewarded_points( $recalculate = false ) {
			if ( $recalculate ) {
				$this->data['_ywpar_rewarded_points'] = yith_points()->points_log->get_user_rewarded_points( $this->get_id() );
				$this->save();
			}

			return (int) $this->data['_ywpar_rewarded_points'];
		}

		/**
		 * Return the total discount collected by customer
		 *
		 * @return float
		 */
		public function get_total_discount() {
			return (float) $this->data['_ywpar_user_total_discount'];
		}

		/**
		 * Return current level of customer.
		 *
		 * @return int
		 */
		public function get_level() {
			return (int) $this->data['_ywpar_user_level'];
		}

		/**
		 * Return reusable points of customer.
		 *
		 * @return int
		 */
		public function get_reusable_points() {
			return (int) $this->data['_ywpar_user_reusable_points'];
		}

		/**
		 * Return the extra point array of customer
		 *
		 * @return array
		 */
		public function get_extrapoint() {
			return (array) $this->data['_ywpar_extrapoint'];
		}

		/**
		 * Return the extra point array counter of customer
		 *
		 * @return array
		 */
		public function get_extrapoint_counter() {
			return (array) $this->data['_ywpar_extrapoint_counter'];
		}

		/**
		 * Return the shared coupons of customer
		 *
		 * @return array
		 */
		public function get_shared_coupons() {
			return (array) $this->data['_ywpar_shared_coupons'];
		}

		/**
		 * Return membership plans of the customer
		 *
		 * @return array
		 */
		public function get_membership_plans() {
			$plans            = array();
			$user_memberships = yith_wcmbs_get_memberships(
				array(
					'user'        => $this->get_id(),
					'active_only' => true,
					'return'      => 'memberships',
				)
			);

			if ( $user_memberships ) {
				foreach ( $user_memberships as $membership ) {

					if ( ! isset( $plans[ $membership->plan_id ] ) ) {
						$plans[ $membership->plan_id ] = $membership->get_plan_title();
					}
				}
			}

			return $plans;
		}

		/**
		 * Return the referral registration info
		 *
		 * @return array
		 */
		public function get_referral_registration() {
			return (array) $this->data['_ywpar_referral_registration'];
		}


		/**
		 * Return the used points
		 *
		 * @param   bool $recalculate  If yes calculate the value from db.
		 *
		 * @return int
		 */
		public function get_used_points( $recalculate = false ) {
			if ( $recalculate ) {
				$this->data['_ywpar_used_points'] = yith_points()->points_log->get_user_used_points( $this->get_id() );
				$this->save();
			}

			return (int) $this->data['_ywpar_used_points'];
		}

		/**
		 * Return the last login of customer
		 *
		 * @return string
		 */
		public function get_daily_login() {
			return $this->data['_ywpar_daily_login'];
		}

		/**
		 * Return completed profile parameter
		 *
		 * @return string
		 */
		public function get_completed_profile() {
			return $this->data['_ywpar_completed_profile'];
		}

		/**
		 * Increment the points collected by customer
		 *
		 * @param   int $points  Point to add to collected points.
		 */
		public function increment_points_collected( $points ) {
			$total_points = $this->get_points_collected() + $points;
			$this->set_points_collected( $total_points );
		}

		/**
		 * Reset props
		 */
		public function reset_props() {
			foreach ( $this->data as $meta => $value ) {
				$prop   = $this->meta_key_to_props[ $meta ];
				$setter = "set_{$prop}";
				if ( method_exists( $this, $setter ) ) {
					$this->$setter( 0 );
				} else {
					$this->set_prop( $meta, 0 );
				}
			}

			$this->save();
		}

		/**
		 * Set customer properties
		 *
		 * @param   string $prop   Property name.
		 * @param   mixed  $value  Property value.
		 */
		public function set_prop( $prop, $value ) {
			$this->data[ $prop ] = $value;
		}

		/**
		 * Save the customer props
		 */
		public function save() {
			foreach ( $this->data as $prop => $value ) {
				$prop = $prop . ywpar_get_blog_suffix();
				if ( empty( $value ) && ! metadata_exists( 'user', $this->get_id(), $prop ) ) {
					continue;
				}

				update_user_meta( $this->get_id(), $prop, $value );
			}

			/**
			 * APPLY_FILTERS: ywpar_flush_cache
			 *
			 * Flush cache.
			 */
			if ( apply_filters( 'ywpar_flush_cache', false ) ) {
				wp_cache_flush();
			}
		}

		/**
		 * Return if the customer is already banned.
		 *
		 * @return bool
		 */
		public function is_banned() {
			$banned_users = (array) ywpar_get_option( 'banned_users' );

			/**
			 * APPLY_FILTERS: ywpar_is_user_banned
			 *
			 * filter if user is banned.
			 *
			 * @param bool $is_banned if is banned
			 * @param YITH_WC_Points_Rewards_Customer $customer the customer.
			 */
			return apply_filters( 'ywpar_is_user_banned', in_array( $this->get_id(), $banned_users ), $this->get_id() ); //phpcs:ignore
		}

		/**
		 * Return if the customer is enabled to earn or redeem points.
		 *
		 * @param   string $action  Earn or Redeem.
		 *
		 * @return bool
		 */
		public function is_enabled( $action = 'earn' ) {
			$is_enabled = ! $this->is_banned();

			if ( $is_enabled ) {
				$roles_enabled = ( 'earn' === $action ) ? yith_ywpar_get_roles_enabled_to_earn() : yith_ywpar_get_roles_enabled_to_redeem();
				$intersect     = array_intersect( (array) $this->get_roles(), $roles_enabled );
				$is_enabled    = in_array( 'all', $roles_enabled, true ) || ( $intersect && count( $intersect ) );
			}

			if ( 'earn' === $action && ! ywpar_automatic_earning_points_enabled() ) {
				$is_enabled = true;
			}
			/**
			 * APPLY_FILTERS: ywpar_is_user_enabled
			 *
			 * filter if user is enabled to get points or redeem.
			 *
			 * @param bool $is_enabled if is enabled
			 * @param string $action earn or redeem.
			 * @param YITH_WC_Points_Rewards_Customer $customer the customer.
			 */
			return apply_filters( 'ywpar_is_user_enabled', $is_enabled, $action, $this );
		}

		/**
		 * Return the roles of customer
		 *
		 * @return array
		 */
		public function get_roles() {
			$user_meta = get_userdata( $this->get_id() );

			return $user_meta ? $user_meta->roles : false;
		}

		/**
		 * Add the user inside the banned list.
		 *
		 * @return bool
		 */
		public function ban() {

			if ( $this->is_banned() ) {
				return false;
			}
			$banned_users = (array) ywpar_get_option( 'banned_users' );
			array_push( $banned_users, $this->get_id() );
			/**
			 * DO_ACTION: ywpar_banned_user
			 *
			 * hook on banning a user.
			 *
			 * @param YITH_WC_Points_Rewards_Customer $user the user.
			 */
			do_action( 'ywpar_banned_user', $this );

			return yith_points()->set_option( 'banned_users', $banned_users );

		}

		/**
		 * Remove the user from the banned list.
		 *
		 * @return bool
		 */
		public function unban() {
			if ( ! $this->is_banned() ) {
				return false;
			}

			$banned_users = (array) ywpar_get_option( 'banned_users' );
			$key          = array_search( $this->get_id(), $banned_users ); //phpcs:ignore
			unset( $banned_users[ $key ] );
			yith_points()->set_option( 'banned_users', $banned_users );
		}

		/**
		 * Reset the customer
		 *
		 * @param
		 *
		 * @return void
		 * @since 3.0.0
		 */
		public function reset() {
			$this->reset_points();
			$this->reset_props();

			$this->update_level();
		}

		/**
		 * Reset points to the customer
		 *
		 * @return void
		 * @since 3.0.0
		 */
		public function reset_points() {
			$cap = ywpar_get_manage_points_capability();
			if ( current_user_can( $cap ) ) {
				// remove the history.
				yith_points()->points_log->remove_user_log( $this->get_id() );
				// remove points to user.

			}
		}


		/**
		 * Update customer points
		 *
		 * @param   int    $points_amount  Points amount.
		 * @param   string $action         Action used to update the points.
		 * @param   array  $args           List of arguments.
		 *
		 * @return bool
		 */
		public function update_points( $points_amount, $action, $args = array() ) {
			if ( empty( $points_amount ) ) {
				return false;
			}

			$remove_collected_points = isset( $args['remove_collected_points'] ) ?? false;
			$remove_redeemed_points  = isset( $args['remove_redeemed_points'] ) ?? false;
			$arguments               = array(
				'user_id'     => $this->get_id(),
				'action'      => $action,
				'order_id'    => isset( $args['order_id'] ) ? $args['order_id'] : 0,
				'description' => isset( $args['description'] ) ? $args['description'] : '',
				'amount'      => $points_amount,
				'info'        => isset( $args['info'] ) ? serialize( $args['info'] ) : '',
			);

			if ( isset( $args['cancelled'] ) ) {
				$arguments['cancelled'] = $args['cancelled'];
			}

			if ( isset( $args['date_earning'] ) ) {
				$arguments['date_earning'] = $args['date_earning'];
			}

			$total_points = $this->get_total_points() + $points_amount;

			// APPLY_FILTER : ywpar_disable_negative_point: disable or not negative points.
			if ( apply_filters( 'ywpar_disable_negative_point', true, $this->get_id(), $points_amount, $action, $arguments['order_id'], $arguments['description'] ) ) {
				$total_points = $total_points > 0 ? $total_points : 0;
			}
			$update_result = yith_points()->points_log->add_item( $arguments );
			/**
			 * APPLY_FILTERS: ywpar_disable_log
			 *
			 * disable or not the logger
			 */
			if ( apply_filters( 'ywpar_disable_log', true ) ) {
				yith_points()->logger->add( 'ywpar_update_points', 'Updated points to ' . $this->get_id() . ': ' . print_r( $arguments, 1 ) );
			}

			if ( $update_result ) {
				$this->set_total_points( $total_points );

				if ( ( $points_amount > 0 || $remove_collected_points ) && ! $remove_redeemed_points ) {
					$this->increment_points_collected( $points_amount );
					$this->update_level();
				}

				$this->save();

				/**
				 * DO_ACTION: ywpar_customer_updated_points
				 *
				 * hook on customer updated points.
				 *
				 * @param int $customer_id customer id.
				 * @param int $points_amount the points updated.
				 * @param string $action type of the action.
				 * @param array $arguments list of arguments.
				 */
				do_action( 'ywpar_customer_updated_points', $this->get_id(), $points_amount, $action, $arguments );

				// todo:move to rewards using the action.
				if ( $points_amount < 0 && ! in_array( $action, array( 'order_refund', 'expired_points' ), true ) ) {
					$this->add_rewarded_points( absint( $points_amount ) );
				}

				if ( stripos( $action, '_exp' ) === false ) {
					yith_points()->extra_points->handle_actions( array( 'points' ), $this->get_id() );
				}

				/**
				 * DO_ACTION: ywpar_earned_points
				 *
				 * Points earned.
				 *
				 */
				do_action( 'ywpar_earned_points' );
			}

			return $update_result;
		}

		/**
		 * Update the level of customer based on the total point amount earned.
		 *
		 * @param   bool $check_extrapoints  Check extrapoints.
		 */
		public function update_level( $check_extrapoints = true ) {
			$points_collected = $this->get_points_collected();
			$total_points     = $this->get_total_points();
			$points_collected = $total_points > $points_collected ? $total_points : $points_collected;

			$levels             = YITH_WC_Points_Rewards_Helper::get_levels_badges();
			$customer_level     = $this->get_level();
			$new_customer_level = $customer_level;
			if ( $levels ) {
				foreach ( $levels as $level_id => $level ) {
					$points_to_collect = $level->get_points_to_collect();

					if ( $points_to_collect['from'] <= $points_collected && ( empty( $points_to_collect['to'] ) || $points_collected <= $points_to_collect['to'] ) ) {
						$new_customer_level = $level_id;
						break;
					}
				}
				$new_customer_level = apply_filters( 'ywpar_update_new_customer_level', $new_customer_level, $customer_level, $points_collected, $total_points, $this->get_points_collected() );
				if ( $new_customer_level !== $customer_level ) {
					$this->set_level( $new_customer_level );
					$this->save();
					/**
					 * DO_ACTION: ywpar_level_updated
					 *
					 * hook on updated level.
					 *
					 * @param int $new_customer_level the new level.
					 * @param int $customer_level actual level.
					 * @param YITH_WC_Points_Rewards_Customer $this customer object.
					 */
					do_action( 'ywpar_level_updated', $new_customer_level, $customer_level, $this );
					if ( $check_extrapoints ) {
						yith_points()->extra_points->handle_actions( array( 'level_achieved' ), $this );
					}
				}
			}

		}


		/**
		 * Return the history of customer
		 *
		 * @param   array $args  Arguments.
		 *
		 * @return array
		 */
		public function get_history( $args = array() ) {
			$args['user_id']  = $this->get_id();
			$items            = yith_points()->points_log->get_items( $args );
			$all_filled_items = $this->get_all_items_with_prev_amount( $items );
			foreach ( $items as $key => $item ) {
				$items[ $key ] = $all_filled_items[ $item['id'] ];
			}

			return $items;
		}

		/**
		 * Fill items of prev amount
		 *
		 * @param   array $items  List of items.
		 *
		 * return array
		 */
		public function get_all_items_with_prev_amount( $items = array() ) {

			$cache_key = 'items_with_prev_amount_' . $this->get_id();
			$elements  = wp_cache_get( $cache_key, 'ywpar_customer' );
			if ( false === $elements || count( $items ) !== count( $elements ) ) {
				$args = array(
					'user_id'   => $this->get_id(),
					'per_pages' => - 1,
					'orderby'   => 'date_earning',
					'order'     => 'DESC',
				);

				$total_points = $this->get_total_points();

				$elements = array();
				if ( apply_filters( 'ywpar_prev_amount_history_list_force_get_all_items', false ) ) {
					$items        = yith_points()->points_log->get_items( $args );
				}
				foreach ( $items as $key => $item ) {
					$total_points                -= (int) $item['amount'];
					$items[ $key ]['prev_amount'] = $total_points;
					$elements[ $item['id'] ]      = $items[ $key ];
				}

				wp_cache_set( $cache_key, $elements, 'ywpar_customer' );
			}

			return $elements;
		}

		/**
		 * Set user rewarded points, add $rewarded_points to the user meta '_ywpar_rewarded_points'
		 *
		 * @param   int $rewarded_points  Points rewarded.
		 *
		 * @return void
		 * @since 3.0.0
		 */
		public function add_rewarded_points( $rewarded_points ) {
			$this->set_rewarded_points( (int) $rewarded_points + $this->get_rewarded_points() );
			$this->save();
		}


		/**
		 * Set user rewarded points, add $rewarded_points to the user meta '_ywpar_rewarded_points'
		 *
		 * @param   float $discount_amount  Discount amount.
		 *
		 * @return void
		 * @since 3.0.0
		 */
		public function add_total_discount( $discount_amount ) {
			$this->set_total_discount( (float) abs( $discount_amount ) + $this->get_total_discount() );
			$this->save();
		}


		/**
		 * Return the usable points difference between the total points and reusable points
		 *
		 * @return int
		 */
		public function get_usable_points() {
			$reusable_points = $this->get_reusable_points();
			$usable_points   = $this->get_total_points() - $reusable_points;

			return $usable_points < 0 ? 0 : $usable_points;
		}

		/**
		 * Return the valid banner for customer
		 *
		 * @param   string $type  Type of banner.
		 *
		 * @return array
		 */
		public function get_banners( $type ) {
			$customer_banners = array();
			$banners          = ywpar_get_banners( $type );

			if ( $banners ) {
				foreach ( $banners as $banner ) {
					/**
					 * Current banner
					 *
					 * @var YITH_WC_Points_Rewards_Banner $banner
					 */
					if ( $banner->is_valid_for_customer( $this ) ) {
						array_push( $customer_banners, $banner );
					}
				}
			}
			/**
			 * APPLY_FILTERS: ywpar_customer_banner
			 *
			 * filter the customer banners
			 *
			 * @param array  $customer_banners list of the banners
			 * @param string $type Type of banner.
			 * @param YITH_WC_Points_Rewards_Customer $customer the customer.
			 */
			return apply_filters( 'ywpar_customer_banner', $customer_banners, $type, $this );
		}


		/**
		 * Return the usable amount
		 *
		 * @return float
		 */
		public function get_usable_amount() {
			$user_extra_point_counter = $this->get_extrapoint_counter();
			$amount_used              = isset( $user_extra_point_counter['amount_spent'], $user_extra_point_counter['amount_spent']['amount_used'] ) ? $user_extra_point_counter['amount_spent']['amount_used'] : 0;
			$starter_date             = isset( $user_extra_point_counter['amount_spent'], $user_extra_point_counter['amount_spent']['starter_date'] ) ? $user_extra_point_counter['amount_spent']['starter_date'] : gmdate( 'Y-m-d', time() - DAY_IN_SECONDS );
			$usable_amount            = yith_ywpar_calculate_user_total_orders_amount( $this->get_id(), 0, $starter_date );

			return $usable_amount;
		}

		/**
		 * Clear cache for this object
		 */
		private function clear_cache() {
			wp_cache_delete( 'items_with_prev_amount_' . $this->get_id(), 'ywpar_customer' );
		}

		/**
		 * Clear cache for this object
		 */
		public function get_rank_position() {
			$rank      = '';
			$rank_list = YITH_WC_Points_Rewards_Helper::get_rank_list();
			if ( $rank_list ) {
				$key  = array_search( $this->get_id(), $rank_list ); //phpcs:ignore
				$rank = false !== $key ? ( (int) $key + 1 ) : $rank;
			}

			$this->set_rank( $rank );
			$this->save();

			return $rank;
		}

		/**
		 * Add inside the shared coupons list the new coupon
		 *
		 * @param   array $coupon_details  Coupon details.
		 */
		public function save_shared_coupon( $coupon_details ) {
			$shared_coupons                            = $this->get_shared_coupons();
			$shared_coupons[ $coupon_details['code'] ] = $coupon_details;
			$this->set_shared_coupons( $shared_coupons );
			$this->save();
		}

		/**
		 * Return the number of points from renews
		 *
		 * @param   int $max_review  Max review.
		 *
		 * @return int
		 */
		public function calculate_points_from_renews( $max_review = 5 ) {
			$points       = 0;
			$review_rules = ywpar_get_option( 'review_exp' );
			if ( empty( $review_rules['list'] ) ) {
				return $points;
			}
			$products_to_review = $this->get_products_to_review( $max_review );

			$extrapoint_list = $this->get_extrapoint();
			$review_num      = count( $products_to_review );

			if ( $review_num > 0 ) {
				foreach ( $review_rules['list'] as $review_rule ) {
					if ( $review_num > 0 ) {
						$repeat = isset( $review_rule['repeat'] ) ? $review_rule['repeat'] : 0;
						$rule   = array(
							'option' => 'reviews',
							'value'  => $review_rule['number'],
							'points' => $review_rule['points'],
							'repeat' => $repeat,
						);

						// check if the rule is already applied.
						if ( ! yith_points()->extra_points->check_extrapoint_rule( $rule, $extrapoint_list ) ) {
							continue;
						}

						// if the customer has enough reviews to use.
						if ( $review_num >= $rule['value'] ) {
							$repeat_times = $repeat ? floor( $review_num / $review_rule['number'] ) : 1;
							$points      += (int) $repeat_times * (int) $rule['points'];
							$review_num  -= (int) $repeat_times * (int) $review_rule['number'];
						}
					}
				}
			}

			return $points;

		}

		/**
		 * Recalculate total points.
		 */
		public function recalculate_total_points() {
			$total_points = yith_points()->points_log->get_total_points( $this->get_id() );
			$this->set_total_points( $total_points );
			$this->save();
		}

	}

}
