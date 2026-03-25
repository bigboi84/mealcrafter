<?php
/**
 * Class to implement Ajax calls
 *
 * @class   YITH_WC_Points_Rewards_Ajax
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Ajax' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Ajax
	 */
	class YITH_WC_Points_Rewards_Ajax {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Ajax
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Ajax
		 * @since  2.2.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();

		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 2.2.0
		 */
		private function __construct() {
			$ajax_actions = array(
				'update_points',
				'bulk_action',
				'change_status_to_earning_rule',
				'change_status_to_redeeming_rule',
				'change_status_to_level',
				'change_status_to_banner',
				'order_list_table',
				'order_banner_list_table',
				'order_earning_rules_list_table',
				'calculate_worth_from_points_on_share_points',
				'create_share_points_coupon',
				'apply_wc_points_rewards',
			);

			foreach ( $ajax_actions as $ajax_action ) {
				add_action( 'wp_ajax_ywpar_' . $ajax_action, array( $this, $ajax_action ) );
				add_action( 'wp_ajax_nopriv_ywpar_' . $ajax_action, array( $this, $ajax_action ) );
			}

			add_action( 'admin_action_yith_ywpar_duplicate_banner', array( $this, 'duplicate_banner' ) );
			add_action( 'admin_action_yith_ywpar_duplicate_redeeming_rule', array( $this, 'duplicate_redeeming_rule' ) );
			add_action( 'admin_action_yith_ywpar_duplicate_earning_rule', array( $this, 'duplicate_earning_rule' ) );
		}

		/**
		 * Sort the items inside the wp list.
		 */
		public function order_list_table() {
			if ( isset( $_POST['sorted'] ) ) { //phpcs:ignore
				YITH_WC_Points_Rewards_Helper::sort_posts( $_POST['sorted'] ); //phpcs:ignore
			}

		}

		/**
		 * Sort the items inside the wp list.
		 */
		public function order_earning_rules_list_table() {
			check_ajax_referer( 'ywpar_earning_rule_save_data', 'nonce' );
			YITH_WC_Points_Rewards_Helper::sort_posts( $_POST['order'] ); //phpcs:ignore
		}

		/**
		 * Sort the items inside the wp list.
		 */
		public function order_redeeming_rules_list_table() {
			check_ajax_referer( 'ywpar_redeeming_rule_save_data', 'nonce' );
			YITH_WC_Points_Rewards_Helper::sort_posts( $_POST['order'] ); //phpcs:ignore
		}


		/**
		 * Duplicate Banner
		 */
		public function duplicate_banner() {

			if ( isset( $_REQUEST['action'], $_GET['duplicate_nonce'], $_GET['banner_id'] ) && 'yith_ywpar_duplicate_banner' === $_REQUEST['action'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['duplicate_nonce'] ) ), 'yith_ywpar_duplicate_banner' ) && current_user_can( 'edit_' . YITH_WC_Points_Rewards_Post_Types::$banner . 's', absint( wp_unslash( $_GET['banner_id'] ) ) ) ) {
				$post_id = absint( wp_unslash( $_GET['banner_id'] ) );
				$post    = get_post( $post_id );

				if ( ! $post || YITH_WC_Points_Rewards_Post_Types::$banner !== $post->post_type ) {
					return;
				}

				$new_post_id = YITH_WC_Points_Rewards_Post_Types::duplicate_post( $post, YITH_WC_Points_Rewards_Post_Types::$banner );

				$redirect_url = apply_filters(
					'yith_ywpar_duplicate_banner_redirect_url', // APPLY_FILTERS: yith_ywpar_duplicate_banner_redirect_url | filter the banner creation url | @param string $url the url
					add_query_arg(
						array(
							'post'   => $new_post_id,
							'action' => 'edit',
						),
						admin_url( 'post.php' )
					)
				);

				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

		/**
		 * Duplicate Earning Rule
		 */
		public function duplicate_earning_rule() {

			if ( isset( $_REQUEST['action'], $_GET['duplicate_nonce'], $_GET['rule_id'] ) && 'yith_ywpar_duplicate_earning_rule' === $_REQUEST['action'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['duplicate_nonce'] ) ), 'yith_ywpar_duplicate_rule' ) && current_user_can( 'edit_' . YITH_WC_Points_Rewards_Post_Types::$earning_rule . 's', absint( wp_unslash( $_GET['rule_id'] ) ) ) ) {
				$post_id = absint( wp_unslash( $_GET['rule_id'] ) );
				$post    = get_post( $post_id );

				if ( ! $post || YITH_WC_Points_Rewards_Post_Types::$earning_rule !== $post->post_type ) {
					return;
				}

				$new_post_id = YITH_WC_Points_Rewards_Post_Types::duplicate_post( $post, YITH_WC_Points_Rewards_Post_Types::$earning_rule );

				$redirect_url = apply_filters(
					'yith_ywpar_duplicate_earning_rule_redirect_url',// APPLY_FILTERS: yith_ywpar_duplicate_earning_rule_redirect_url | filter the earning rule creation url | @param string $url the url
					add_query_arg(
						array(
							'post'   => $new_post_id,
							'action' => 'edit',
						),
						admin_url( 'post.php' )
					)
				);

				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

		/**
		 * Duplicate Redeeming Ryle
		 */
		public function duplicate_redeeming_rule() {

			if ( isset( $_REQUEST['action'], $_GET['duplicate_nonce'], $_GET['redeeming_rule_id'] ) && 'yith_ywpar_duplicate_redeeming_rule' === $_REQUEST['action'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['duplicate_nonce'] ) ), 'yith_ywpar_duplicate_redeeming_rule' ) && current_user_can( 'edit_' . YITH_WC_Points_Rewards_Post_Types::$redeeming_rule . 's', absint( wp_unslash( $_GET['redeeming_rule_id'] ) ) ) ) {
				$post_id = absint( wp_unslash( $_GET['redeeming_rule_id'] ) );
				$post    = get_post( $post_id );

				if ( ! $post || YITH_WC_Points_Rewards_Post_Types::$redeeming_rule !== $post->post_type ) {
					return;
				}

				$new_post_id = YITH_WC_Points_Rewards_Post_Types::duplicate_post( $post, YITH_WC_Points_Rewards_Post_Types::$redeeming_rule );

				$redirect_url = apply_filters(
					'yith_ywpar_duplicate_redeeming_rule_redirect_url',// APPLY_FILTERS: yith_ywpar_duplicate_redeeming_rule_redirect_url | filter the redeeming rule creation url | @param string $url the url
					add_query_arg(
						array(
							'post'   => $new_post_id,
							'action' => 'edit',
						),
						admin_url( 'post.php' )
					)
				);

				wp_safe_redirect( $redirect_url );
				exit;
			}
		}


		/**
		 * Duplicate Points Rule
		 */
		public function duplicate_rule() {

			if ( isset( $_REQUEST['action'], $_GET['duplicate_nonce'], $_GET['rule_id'] ) && 'yith_ywpar_duplicate_rule' === $_REQUEST['action'] && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['duplicate_nonce'] ) ), 'yith_ywpar_duplicate_redeeming_rule' ) && current_user_can( 'edit_' . YITH_WC_Points_Rewards_Earning_Rules::POST_TYPE . 's', absint( wp_unslash( $_GET['rule_id'] ) ) ) ) {
				$post_id = absint( wp_unslash( $_GET['rule_id'] ) );
				$post    = get_post( $post_id );

				if ( ! $post || YITH_WC_Points_Rewards_Earning_Rules::POST_TYPE !== $post->post_type ) {
					return;
				}

				$new_title = $post->post_title . esc_html_x( ' - Copy', 'Name of duplicated rule', 'yith-woocommerce-points-and-rewards' );
				$new_post  = array(
					'post_status' => 'publish',
					'post_type'   => YITH_WC_Points_Rewards_Earning_Rules::POST_TYPE,
					'post_title'  => $new_title,
				);

				$new_post_id = wp_insert_post( $new_post );
				$metas       = get_post_meta( $post_id );

				if ( ! empty( $metas ) ) {
					foreach ( $metas as $meta_key => $meta_value ) {
						if ( '_edit_lock' === $meta_key || '_edit_last' === $meta_key ) {
							continue;
						}

						update_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value[0] ) );
					}
				}

				update_post_meta( $new_post_id, '_ywpar_rule_name', $new_title );

				$redirect_url = apply_filters(
					'yith_ywpar_duplicate_rule_redirect_url',// APPLY_FILTERS: yith_ywpar_duplicate_rule_redirect_url | filter the duplication rule url | @param string $url the url
					add_query_arg(
						array(
							'post'   => $new_post_id,
							'action' => 'edit',
						),
						admin_url( 'post.php' )
					)
				);

				wp_safe_redirect( $redirect_url );
				exit;
			}
		}

		/**
		 * Bulk options actions
		 */
		public function bulk_action() {

			check_ajax_referer( 'ywpar_bulk_actions', 'security' );

			$result = array(
				'message' => '',
				'step'    => 1,
			);

			if ( ! isset( $_POST['step'], $_POST['action'] ) ) {
				wp_send_json_error(
					array( 'error' => __( 'An error occurred during the bulk operation', 'yith-woocommerce-points-and-rewards' ) )
				);
			}

			$data = wp_unslash( $_POST ); //phpcs:ignore
			$step = isset( $_POST['step'] ) ? sanitize_text_field( wp_unslash( $_POST['step'] ) ) : 1;

			$result = YITH_WC_Points_Rewards_Customer_Bulk_Actions::handle_bulk_actions( $data, $step );

			if ( isset( $_POST['ywpar_bulk_action_type'] ) ) {
				switch ( $_POST['ywpar_bulk_action_type'] ) {
					case 'add_points':
						$message = __( 'Points added correctly.', 'yith-woocommerce-points-and-rewards' );
						break;

					case 'remove_points':
						$message = __( 'Points removed correctly.', 'yith-woocommerce-points-and-rewards' );
						break;

					case 'ban':
						$message = __( 'User banned correctly.', 'yith-woocommerce-points-and-rewards' );
						break;

					case 'unban':
						$message = __( 'User unbanned correctly.', 'yith-woocommerce-points-and-rewards' );
						break;

					case 'reset':
						$message = __( 'Points reset correctly.', 'yith-woocommerce-points-and-rewards' );
						break;


					default:
						$message = __( 'Done.', 'yith-woocommerce-points-and-rewards' );
						break;
				}
			} else {
				$message = __( 'Error.', 'yith-woocommerce-points-and-rewards' );
			}

			wp_send_json_success(
				array(
					'step'       => $result['next_step'],
					'percentage' => $result['percentage'],
					'message'    => $message,
				)
			);
		}

		/**
		 * Update points to the customer
		 */
		public function update_points() {
			check_ajax_referer( 'ywpar_update_points', 'security' );

			if ( ! isset( $_REQUEST['user_id'], $_REQUEST['action_type'], $_REQUEST['points_amount'], $_REQUEST['context'], $_REQUEST['description'] ) ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Some fields are missing', 'Error message during admin actions', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}

			$user_id       = sanitize_text_field( wp_unslash( $_REQUEST['user_id'] ) );
			$action        = sanitize_text_field( wp_unslash( $_REQUEST['action_type'] ) );
			$points_amount = (int) sanitize_text_field( wp_unslash( $_REQUEST['points_amount'] ) );
			$context       = sanitize_text_field( wp_unslash( $_REQUEST['context'] ) );

			$points_amount = ( 'remove' === $action ) ? ( -1 ) * abs( $points_amount ) : abs( $points_amount );

			$args = array(
				'description' => sanitize_text_field( wp_unslash( $_REQUEST['description'] ) ),
			);

			$ywpar_customer = ywpar_get_customer( $user_id );
			$ywpar_customer->update_points( $points_amount, $context, $args );

			wp_send_json(
				array(
					// translators: subscription number.
					'success' => 'ok',
				)
			);
		}

		/**
		 * Change status to earning rule
		 */
		public function change_status_to_earning_rule() {
			check_ajax_referer( 'ywpar-earning-rule-status', 'security' );

			if ( ! isset( $_REQUEST['post_id'], $_REQUEST['status'] ) ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Some fields are missing', 'Error message during admin actions', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}

			$post_id = sanitize_text_field( wp_unslash( $_REQUEST['post_id'] ) );
			$status  = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );

			$earning_rule = ywpar_get_earning_rule( $post_id );
			if ( $earning_rule ) {
				$earning_rule->set_status( 'yes' === $status ? 'on' : 'off' );
				$earning_rule->save();
			}

			/* reset product points transient */
			delete_transient('ywpar_product_points');

			wp_send_json_success();
		}

		/**
		 * Change status to redeeming rule
		 */
		public function change_status_to_redeeming_rule() {
			check_ajax_referer( 'ywpar-redeeming-rule-status', 'security' );

			if ( ! isset( $_REQUEST['post_id'], $_REQUEST['status'] ) ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Some fields are missing', 'Error message during admin actions', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}

			$post_id = sanitize_text_field( wp_unslash( $_REQUEST['post_id'] ) );
			$status  = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );

			$redeeming_rule = ywpar_get_redeeming_rule( $post_id );
			if ( $redeeming_rule ) {
				$redeeming_rule->set_status( 'yes' === $status ? 'on' : 'off' );
				$redeeming_rule->save();
			}

			wp_send_json_success();
		}

		/**
		 * Change status to level
		 */
		public function change_status_to_level() {
			check_ajax_referer( 'ywpar-levels-badges-status', 'security' );

			if ( ! isset( $_REQUEST['post_id'], $_REQUEST['status'] ) ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Some fields are missing', 'Error message during admin actions', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}

			$post_id = sanitize_text_field( wp_unslash( $_REQUEST['post_id'] ) );
			$status  = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );

			$level_badge = ywpar_get_level_badge( $post_id );
			if ( $level_badge ) {
				$level_badge->set_status( 'yes' === $status ? 'on' : 'off' );
				$level_badge->save();
			}

			wp_send_json_success();
		}

		/**
		 * Change status to banners
		 */
		public function change_status_to_banner() {
			check_ajax_referer( 'ywpar-banner-status', 'security' );

			if ( ! isset( $_REQUEST['post_id'], $_REQUEST['status'] ) ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Some fields are missing', 'Error message during admin actions', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}

			$post_id = sanitize_text_field( wp_unslash( $_REQUEST['post_id'] ) );
			$status  = sanitize_text_field( wp_unslash( $_REQUEST['status'] ) );

			$banner = ywpar_get_banner( $post_id );
			if ( $banner ) {
				$banner->set_status( 'yes' === $status ? 'on' : 'off' );
				$banner->save();
			}

			wp_send_json_success();
		}

		/**
		 * Calculate the worth when the points on share points changes
		 */
		public function calculate_worth_from_points_on_share_points() {
			check_ajax_referer( 'ywpar_share_points', 'security' );
			if ( ! isset( $_POST['points'], $_POST['customer'] ) ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Some fields are missing', 'Error message during admin actions', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}
			$points   = sanitize_text_field( wp_unslash( $_POST['points'] ) );
			$customer = sanitize_text_field( wp_unslash( $_POST['customer'] ) );
			$customer = ywpar_get_customer( $customer );

			$worth = yith_points()->redeeming->calculate_price_worth_from_points( $points, $customer );
			wp_send_json_success(
				array(
					'worth' => $worth,
				)
			);
		}

		/**
		 * Create share points coupon
		 */
		public function create_share_points_coupon() {
			check_ajax_referer( 'ywpar_share_points', 'security' );
			if ( ! isset( $_POST['points'], $_POST['customer'] ) ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Some fields are missing', 'Error message during admin actions', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}
			$points   = sanitize_text_field( wp_unslash( $_POST['points'] ) );
			$customer = sanitize_text_field( wp_unslash( $_POST['customer'] ) );
			$customer = ywpar_get_customer( $customer );

			// double check points can be used.
			$total_points = $customer->get_total_points();
			if ( $points > $total_points ) {
				wp_send_json(
					array(
						'error' => esc_html_x( "You don't have enough points to create this coupon.", 'Error message during share points coupon creation', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}

			$share_usable_points = YITH_WC_Points_Rewards_Share_Points::get_max_points_usable( $total_points );
			$min_value_to_share  = YITH_WC_Points_Rewards_Share_Points::get_minimum_amount();

			if ( $points > $share_usable_points || $min_value_to_share > $points ) {
				wp_send_json(
					array(
						'error' => esc_html_x( 'Please check the number of points to share.', 'Error message during share points coupon creation', 'yith-woocommerce-points-and-rewards' ),
					)
				);
			}

			$coupon         = YITH_WC_Points_Rewards_Share_Points::create_coupon( $points, $customer );
			$coupon_details = array(
				'id'            => $coupon->get_id(),
				'code'          => $coupon->get_code(),
				'amount'        => $coupon->get_amount(),
				'discount_type' => $coupon->get_discount_type(),
				'date_created'  => $coupon->get_date_created(),
				'date_expires'  => $coupon->get_date_expires(),
			);

			$customer->save_shared_coupon( $coupon_details );

			wp_send_json_success();
		}

		/**
		 * Migrate points from WC Points and Rewards
		 */
		public function apply_wc_points_rewards() {

			check_ajax_referer( 'apply_wc_points_rewards', 'security' );
			$success_count = YITH_WC_Points_Rewards_Porting()->migrate_points();
			// translators: placeholder number of points.
			$response = sprintf( _nx( '<strong>%d</strong> point has been updated', '<strong>%d</strong> points have been updated', $success_count, '', 'yith-woocommerce-points-and-rewards' ), $success_count );

			wp_send_json( $response );
		}
	}

}
