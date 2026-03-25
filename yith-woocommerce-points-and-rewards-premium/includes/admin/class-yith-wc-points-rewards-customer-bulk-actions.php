<?php
/**
 * Admin Bulk Actions Class
 *
 * @class   YITH_WC_Points_Rewards_Customer_Bulk_Actions
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Customer_Bulk_Actions' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Customer_Bulk_Actions
	 */
	class YITH_WC_Points_Rewards_Customer_Bulk_Actions {

		/**
		 * Max number of customer to process
		 */
		const MAX_NUMBER_OF_CUSTOMERS_TO_PROCESS = 50;
		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Customer_Bulk_Actions
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Customer_Bulk_Actions
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
			add_action( 'yith_ywpar_customers', array( $this, 'add_button' ), 5 );
			add_action( 'admin_footer', array( $this, 'add_modal_template' ) );
		}

		/**
		 * Detect if is Customers admin page.
		 *
		 * @return bool
		 * @since 4.0
		 */
		public function is_customers_page() {
			return ( isset( $_GET['page'] ) && 'yith_woocommerce_points_and_rewards' === $_GET['page'] && ! isset( $_GET['tab'] ) ) || isset( $_GET['tab'] ) && 'customers' === $_GET['tab'] && ! isset( $_GET['action'] );
		}


		/**
		 * Add Export/Import button on admin panel.
		 * @since 4.0.0
		 * @return void
		 */
		public function add_button() {
            if( $this->is_customers_page() ) :
            ?>
				<script type="text/javascript">
                    jQuery(function () {
                        jQuery('.export-import-custom-button').after("<a class='page-title-action bulk-actions-custom-button'><?php echo esc_html__( 'Bulk actions', 'yith-woocommerce-points-and-rewards' ); ?></a>");
                    });
				</script>
			<?php endif;

		}

		/**
		 * Add modal template for Bulk Actions section.
		 *
		 * @return void
		 */
		public function add_modal_template() {
			include_once YITH_YWPAR_VIEWS_PATH . '/tabs/customers/bulk-actions/modal/content.php';
		}

		/**
		 * Return the users to apply the bulk actions
		 *
		 * @param array $data Bulk Actions Selected Options.
		 * @param int   $step Current step.
		 *
		 * @return array|false
		 */
		public static function get_users( $data, $step ) {
			$result      = false;
			$user_number = self::MAX_NUMBER_OF_CUSTOMERS_TO_PROCESS;
			$offset      = ( $step - 1 ) * $user_number;
			$user_search = $data['ywpar_bulk_apply_to'];

			$args = array(
				'offset'      => $offset,
				'number'      => $user_number,
				'count_total' => 1,
				'fields'      => 'ID',
			);

			switch ( $user_search ) {
				case 'everyone':
					if ( isset( $data['ywpar_active_exclusion'] ) && 'yes' === $data['ywpar_active_exclusion'] ) {
						if ( 'by_role' === $data['ywpar_exclude_users_type'] ) {
							$args['role__not_in'] = isset( $data['ywpar_user_role_excluded'] ) ? $data['ywpar_user_role_excluded'] : array();
						} elseif ( 'by_user' === $data['ywpar_exclude_users_type'] ) {
							$args['exclude'] = isset( $data['ywpar_customer_list_exclude'] ) ? $data['ywpar_customer_list_exclude'] : array();
						}
					}
					break;
				case 'role_list':
					$args['role__in'] = isset( $data['ywpar_user_role'] ) ? $data['ywpar_user_role'] : array();
					break;
				case 'customers_list':
					$args['include'] = isset( $data['ywpar_customer_list'] ) ? $data['ywpar_customer_list'] : array();
					break;
			}

			$users_query = new WP_User_Query( $args );

			if ( $users_query->get_total() ) {
				$percentage = ceil( 100 * ( $user_number * $step ) / $users_query->get_total() );
				$percentage = $percentage > 100 ? 100 : $percentage;
				$result     = array(
					'total'      => $users_query->get_total(),
					'percentage' => $percentage,
					'users'      => $users_query->get_results(),
					'next_step'  => ( ( $user_number * $step ) < $users_query->get_total() ) ? ++$step : 'done',
				);
			}

			return $result;
		}


		/**
		 * Bulk action process
		 *
		 * @param string $data Posted content.
		 * @param int    $step Current step.
		 */
		public static function handle_bulk_actions( $data, $step ) {

			$action = isset( $data['ywpar_bulk_action_type'] ) ? $data['ywpar_bulk_action_type'] : '';

			if ( 'add_points_to_orders' === $action ) {
				$from    = ( 'from' === $data['ywpar_apply_points_previous_order_to'] ) ? $data['ywpar_apply_points_previous_order_start_date'] : '';
				$results = yith_points()->redeeming->order->add_points_to_previous_orders( $from, $step, $data );
				return $results;
			}

			$users = self::get_users( $data, $step );
			if ( $users['users'] ) {
				! defined( 'YITH_YWPAR_DOING_BULK_ACTION' ) && define( 'YITH_YWPAR_DOING_BULK_ACTION', true );

				switch ( $action ) {
					case 'add_points':
					case 'remove_points':
						$points = (int) sanitize_text_field( wp_unslash( $data['ywpar_bulk_add_points'] ) );
						$points = abs( $points ) * ( 'remove_points' === $action ? ( -1 ) : 1 );

						$description = sanitize_text_field( wp_unslash( $data['ywpar_bulk_add_description'] ) );

						if ( 0 !== $points ) {
							foreach ( $users['users'] as $user_id ) {
								$ywpar_user = ywpar_get_customer( $user_id );
								$ywpar_user->update_points( $points, 'admin_action', array( 'description' => $description ) );
							}
						}
						break;

					case 'ban':
					case 'unban':
					case 'reset':
						foreach ( $users['users'] as $user_id ) {
							$ywpar_user = ywpar_get_customer( $user_id );
							$ywpar_user->$action();
						}
						break;
				}
			}

			$results['next_step']  = $users['next_step'];
			$results['percentage'] = $users['percentage'];

			return $results;
		}

	}

}
