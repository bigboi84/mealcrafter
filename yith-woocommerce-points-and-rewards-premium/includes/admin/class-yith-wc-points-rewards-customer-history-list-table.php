<?php
/**
 * Customer list table
 *
 * @class   YITH_WC_Points_Rewards_Customer_History_List_Table
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class YITH_WC_Points_Rewards_Customer_History_List_Table
 */
class YITH_WC_Points_Rewards_Customer_History_List_Table extends WP_List_Table {

	/**
	 * Total amount
	 *
	 * @var float
	 */
	private $total_amount = 0;

	/**
	 * Current customer
	 *
	 * @var YITH_WC_Points_Rewards_Customer
	 */
	private $ywpar_customer = 0;

	/**
	 * YITH_WC_Points_Rewards_Customers_List_Table constructor.
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array() );

		if ( isset( $args['customer'] ) ) {
			$this->ywpar_customer = $args['customer'];
			$this->handle_update_actions();
		}

	}

	/**
	 * Column list.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'date_earning' => __( 'Date', 'yith-woocommerce-points-and-rewards' ),
			'amount'       => __( 'Amount', 'yith-woocommerce-points-and-rewards' ),
			'order_id'     => __( 'Order No.', 'yith-woocommerce-points-and-rewards' ),
			'action'       => __( 'Reason', 'yith-woocommerce-points-and-rewards' ),
			'description'  => __( 'Description', 'yith-woocommerce-points-and-rewards' ),
		);
		if( isset( $_GET['ywpar_dev']) ){
			$columns['cancelled'] = 'Cancelled';
			$columns['info'] = 'Info';
		}
		return $columns;
	}

	/**
	 * Return the sortable columns
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'date_earning' => array( 'date_earning', false ),
			'amount'       => array( 'amount', false ),
			'order_id'     => array( 'order_id', false ),
			'action'       => array( 'action', false ),
			'description'  => array( 'description', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Prepare content of table
	 */
	public function prepare_items() {

		global $_wp_column_headers;

		$screen                = get_current_screen();
		$columns               = $this->get_columns();
		$this->_column_headers = array( $columns, array(), $this->get_sortable_columns() );

		$request = $_REQUEST; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		/**
		 * APPLY_FILTERS: ywpar_customer_history_items_per_page
		 *
		 * filter the max number of customer's history items.
		 *
		 * @param integer $number of rows.
		 */
		$perpage = apply_filters( 'ywpar_customer_history_items_per_page', 25 );
		$user_id = ! empty( $request['user_id'] ) ? $request['user_id'] : 0;
		$orderby = ! empty( $request['orderby'] ) ? $request['orderby'] : 'date_earning';
		$order   = ! empty( $request['order'] ) ? $request['order'] : 'DESC';
		$paged   = ! empty( $request['paged'] ) ? $request['paged'] : '';

		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}

		$args = array(
			'user_id'   => $user_id,
			'per_pages' => $perpage,
			'page'      => $paged,
			'order'     => $order,
			'orderby'   => $orderby,
		);

		$this->ywpar_customer = ywpar_get_customer( $user_id );
		$this->total_amount   = $this->ywpar_customer->get_total_points();

		$totalitems = yith_points()->points_log->get_customer_log_items_count( $user_id );
		$totalpages = ceil( $totalitems / $perpage );

		$this->set_pagination_args(
			array(
				'total_items' => $totalitems,
				'total_pages' => $totalpages,
				'per_page'    => $perpage,
			)
		);

		$_wp_column_headers[ $screen->id ] = $columns;
		$this->items                       = $this->ywpar_customer->get_history( $args );

	}

	/**
	 * Show the content inside the columns
	 *
	 * @param object $item Current object.
	 * @param string $column_name Column name.
	 *
	 * @return string|void
	 */
	protected function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'order_id':
				if ( (int) $item['order_id'] > 0 ) {
					return sprintf( '<a href="%s">#%d</a>', admin_url( 'post.php?post=' . $item['order_id'] . '&action=edit' ), $item['order_id'] );
				}
				return '<span class="no-order">-</span>';
			case 'date_earning':
				return esc_html( ( $item['date_earning'] ) ? date_i18n( wc_date_format() . ' ' . wc_time_format(), wc_string_to_timestamp( $item['date_earning'] ) ) : '-' );
			case 'action':
				return ywpar_get_action_label( $item['action'] );
			case 'description':
				return stripslashes( $item['description'] );
			case 'amount':
				if ( $item['amount'] < 0 ) {
					$class  = 'ywpar-minus';
					$amount = $item['amount'];
				} else {
					$class  = 'ywpar-plus';
					$amount = '+' . $item['amount'];
				}

				/**
				 * APPLY_FILTERS: ywpar_customer_points_history_show_prev_amount
				 *
				 * Show or not the calculations aside the points value for each customer history items.
				 *
				 * @param bool $true
				 */
				if ( apply_filters( 'ywpar_customer_points_history_show_prev_amount', true ) ) {
					return sprintf( '<span class="%s">%s</span> (%d)', $class, $amount, $item['prev_amount'] + (int) $item['amount'] );
				} else {
					return sprintf( '<span class="%s">%s</span>', $class, $amount );
				}

			case 'info':
				return print_r(maybe_unserialize( $item['info'] ), 1 );
			case 'cancelled':
				return esc_html( ( $item['cancelled'] ) ? date_i18n( wc_date_format() . ' ' . wc_time_format(), wc_string_to_timestamp( $item['cancelled'] ) ) : '-' );
			default:
				return ( isset( $item[ $column_name ] ) ) ? $item[ $column_name ] : '';
		}
	}

	/**
	 * Show table function
	 */
	public function display() {
		if ( $this->has_items() ) {
			parent::display();
		} else {
			echo sprintf( '<p>%s</p>', esc_html__( 'This user has not collected any points yet.', 'yith-woocommerce-points-and-rewards' ) );
		}
	}

	/**
	 * Process Bulk Actions
	 */
	public function handle_update_actions() {
		$action = $this->current_action();

		if ( in_array( $action, array( 'reset', 'ban', 'unban' ), true ) ) {
			$this->ywpar_customer->$action();
			wp_safe_redirect( remove_query_arg( 'action' ) );
		}

	}

	/**
	 * Return the list of actions to show under the total points.
	 *
	 * @return array|array[]
	 */
	public function get_user_actions() {
		$actions      = array();
		$total_points = $this->ywpar_customer->get_total_points();
		if ( ! $this->ywpar_customer->is_banned() ) {
			$actions = array(
				'add' => array(
					'type'   => 'action-button',
					'title'  => _x( 'Add points', 'Customer Points action', 'yith-woocommerce-points-and-rewards' ),
					'action' => 'add',
					'icon'   => 'plus',
					'url'    => '#',
				),
			);

			if ( $total_points > 0 || ! apply_filters('ywpar_disable_negative_point', true )) {
				$actions['remove'] = array(
					'type'   => 'action-button',
					'title'  => _x( 'Remove points', 'Customer Points action', 'yith-woocommerce-points-and-rewards' ),
					'action' => 'remove',
					'icon'   => 'minus',
					'url'    => '#',
				);
			}
		}

		if ( $total_points > 0 ) {
			$actions['reset'] = array(
				'type'   => 'action-button',
				'title'  => _x( 'Remove all points', 'Customer Points action', 'yith-woocommerce-points-and-rewards' ),
				'action' => 'reset',
				'icon'   => 'trash',
				'url'    => add_query_arg(
					array(
						'action' => 'reset',
					)
				),
			);
		}

		return apply_filters( 'ywpar_customer_history_update_user_action', $actions, $this->ywpar_customer );
	}
}
