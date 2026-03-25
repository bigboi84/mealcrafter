<?php
/**
 * Customer list table
 *
 * @class   YITH_WC_Points_Rewards_Customers_List_Table
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class YITH_WC_Points_Rewards_Customers_List_Table
 */
class YITH_WC_Points_Rewards_Customers_List_Table extends WP_List_Table {

	/**
	 * Banner user list
	 *
	 * @var array
	 */
	protected $banned_users;

	/**
	 * YITH_WC_Points_Rewards_Customers_List_Table constructor.
	 *
	 * @param array $args Arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( array() );
		$this->banned_users = (array) ywpar_get_option( 'banned_users' );
		$this->handle_bulk_actions();
	}

	/**
	 * Column list.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array(
			'cb'               => '<input type="checkbox" />',
			'user_info'        => esc_html__( 'User', 'yith-woocommerce-points-and-rewards' ),
			'points_collected' => esc_html__( 'Points collected', 'yith-woocommerce-points-and-rewards' ),
			'total_points'     => esc_html__( 'Points to redeem', 'yith-woocommerce-points-and-rewards' ),
			'rank'             => esc_html__( 'Rank', 'yith-woocommerce-points-and-rewards' ),
			'action'           => '',
		);
		return $columns;
	}

	/**
	 * Return the sortable columns
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		$sortable_columns = array(
			'user_info'        => array( 'display_name', false ),
			'points_collected' => array( 'points_collected', false ),
			'total_points'     => array( 'user_total_points', false ),
			'rank'             => array( 'rank', false ),
		);
		return $sortable_columns;
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param object $item Current item.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="user[]" value="%s" />', $item->ID );
	}


	/**
	 * Prepares the list of items for displaying.
	 */
	public function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$users_per_page = 25;

		$request = $_REQUEST; //phpcs:ignore

		$paged = ( isset( $request['paged'] ) ) ? $request['paged'] : '';

		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}

		$args = array(
			'number'     => $users_per_page,
			'offset'     => ( $paged - 1 ) * $users_per_page,
			'order'      => 'DESC',
			'orderby'    => 'meta_value_num',
			'meta_query' => array( //phpcs:ignore
				'relation' => 'OR',
				array(
					'key'     => '_ywpar_points_collected' . ywpar_get_blog_suffix(),
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_ywpar_points_collected' . ywpar_get_blog_suffix(),
					'compare' => 'EXISTS',
				),
			),
		);

		if ( isset( $request['orderby'] ) ) {
			if ( 'user_info' !== $request['orderby'] ) {
				$args['orderby']    = 'meta_value_num';
				$args['meta_query'] = array( //phpcs:ignore
					'relation' => 'OR',
					array(
						'key'     => '_ywpar_' . $request['orderby'] . ywpar_get_blog_suffix(),
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_ywpar_' . $request['orderby'] . ywpar_get_blog_suffix(),
						'compare' => 'EXISTS',
					),
				);
			} else {
				$args['orderby'] = $request['orderby'];
			}
		}

		if ( isset( $request['order'] ) ) {
			$args['order'] = $request['order'];
		}

		$args = $this->add_filter_args( $args, $request );

		/* filter only banned users */
		if ( isset( $request['ywpar_list_filter'] ) && 'only_banned' === $request['ywpar_list_filter'] ) {
			$args['include'] = $this->banned_users;
		}

		/**
		 * APPLY_FILTERS: ywpar_customers_table_list_args
		 * 
		 * Customers' list query arguments
		 * 
		 * @param array $args arguments for user query.
		 */
		$args = apply_filters( 'ywpar_customers_table_list_args', $args );

		$wp_user_search = new WP_User_Query( $args );
		$this->items    = $wp_user_search->get_results();

		$this->set_pagination_args(
			array(
				'total_items' => $wp_user_search->get_total(),
				'per_page'    => $users_per_page,
			)
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @return array|false|string
	 * @since  1.0.0
	 */
	protected function get_bulk_actions() {
		return array(
			'reset'       => esc_html__( 'Remove points', 'yith-woocommerce-points-and-rewards' ),
			'ban'         => esc_html__( 'Ban users', 'yith-woocommerce-points-and-rewards' ),
			'unban'       => esc_html__( 'Unban users', 'yith-woocommerce-points-and-rewards' ),
			'extrapoints' => esc_html__( 'Apply extra points rules', 'yith-woocommerce-points-and-rewards' ),

		);
	}

	/**
	 * Process Bulk Actions
	 */
	public function handle_bulk_actions() {
		$action = $this->current_action();
		if ( ! empty( $action ) && isset( $_REQUEST['user'] ) && wp_unslash( $_REQUEST['user'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended.
			$users = (array) wp_unslash( $_REQUEST['user'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended.
			foreach ( $users as $user ) {
				$point_user = ywpar_get_customer( $user );

				switch ( $action ) {
					case 'reset':
						$point_user->reset();
						break;
					case 'ban':
						$point_user->ban();
						break;
					case 'unban':
						$point_user->unban();
						break;
					case 'extrapoints':
						yith_points()->extra_points->handle_actions( array( 'points', 'level_achieved', 'num_of_orders', 'amount_spent' ), $point_user );
						break;
				}
			}

			$this->banned_users = (array) ywpar_get_option( 'banned_users' );
		}
	}

	/**
	 * Adds in any query arguments based on the current filters
	 *
	 * @param array $args Associative array of WP_Query arguments used to query and populate the list table.
	 * @param array $request Post request.
	 * @return array
	 * @since 1.0
	 */
	private function add_filter_args( $args, $request ) {

		// filter by customer.
		if ( isset( $request['_customer_user'] ) && $request['_customer_user'] > 0 ) {
			$args['include'] = array( $request['_customer_user'] );
		}

		if ( isset( $request['ywpar_list_filter'] ) ) {
			$filter_type = $request['ywpar_list_filter'];
			if ( 'with_points' === $filter_type ) {
				$args['meta_query'] = array( //phpcs:ignore
					array(
						'key'     => '_ywpar_user_total_points',
						'compare' => '>',
						'value'   => 0,
						'type'    => 'numeric',
					),
				);
			}
		}

		return $args;
	}

	/**
	 * Return the content of default columns
	 *
	 * @param object $item Current User.
	 * @param string $column_name Column name.
	 *
	 * @return mixed|string|void
	 */
	protected function column_default( $item, $column_name ) {
		$ywpar_customer = ywpar_get_customer( $item->ID );
		switch ( $column_name ) {
			case 'status':
				break;
			case 'user_info':
				$email  = '<a href="mailto:' . $item->user_email . '">' . $item->user_email . '</a>';
				$banned = $ywpar_customer->is_banned() ? '<div class="ywpar-ban">' . __( 'banned', 'yith-woocommerce-points-and-rewards' ) . '</div>' : '';
				return '<div><span class="ywpar-user-name">' . $item->display_name . '</span><br><span class="ywpar-user-email">' . $email . '</span></div>' . $banned;
			case 'total_points':
				return $ywpar_customer->get_total_points();
			case 'points_collected':
				return $ywpar_customer->get_points_collected();
			case 'rank':
				return $ywpar_customer->get_rank_position();
			default:
				return '';
		}

	}

	/**
	 * Show the action inside the action column.
	 *
	 * @param mixed $item Current user.
	 *
	 * @return string
	 */
	protected function column_action( $item ) {
		$arg              = remove_query_arg( array( 'paged', 'orderby', 'order' ) );
		$point_user       = ywpar_get_customer( $item->ID );
		$actions          = array(
			'history' => array(
				'type'   => 'action-button',
				'title'  => _x( 'View history', 'Customer Points action', 'yith-woocommerce-points-and-rewards' ),
				'action' => 'duplicate',
				'icon'   => 'eye',
				'url'    => add_query_arg(
					array(
						'action'  => 'update',
						'user_id' => $item->ID,
					),
					$arg
				),
			),
		);
		$actions['reset'] = array(
			'type'   => 'action-button',
			'title'  => _x( 'Remove points', 'Customer Points action', 'yith-woocommerce-points-and-rewards' ),
			'action' => 'reset',
			'icon'   => 'close',
			'url'    => add_query_arg(
				array(
					'action' => 'reset',
					'user'   => $item->ID,
				),
				$arg
			),
		);

		if ( $point_user->is_banned() ) {
			$actions['unban'] = array(
				'type'   => 'action-button',
				'title'  => _x( 'Unban user', 'Customer Points action', 'yith-woocommerce-points-and-rewards' ),
				'action' => 'unban',
				'icon'   => 'user-off',
				'url'    => add_query_arg(
					array(
						'action' => 'unban',
						'user'   => $item->ID,
					),
					$arg
				),
			);
		} else {
			$actions['ban'] = array(
				'type'   => 'action-button',
				'title'  => _x( 'Ban user', 'Customer Points action', 'yith-woocommerce-points-and-rewards' ),
				'action' => 'ban',
				'icon'   => 'user-off',
				'url'    => add_query_arg(
					array(
						'action' => 'ban',
						'user'   => $item->ID,
					),
					$arg
				),
			);

		}

		return yith_plugin_fw_get_action_buttons( $actions, false );

	}


	/**
	 * Extra controls to be displayed between bulk actions and pagination
	 *
	 * @param string $which The placement, one of 'top' or 'bottom'.
	 * @since 1.0
	 * @see WP_List_Table::extra_tablenav();
	 */
	public function extra_tablenav( $which ) {
		if ( 'top' === $which ) {
			$user_id = 0;
			$sel     = array();
			if ( ! empty( $_REQUEST['_customer_user'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$user_id = absint( sanitize_text_field( wp_unslash( $_REQUEST['_customer_user'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$user    = get_user_by( 'id', $user_id );
				/* translators: 1: user display name 2: user ID 3: user email */
				$user_string     = sprintf(
					esc_html( '%1$s (#%2$s &ndash; %3$s)' ),
					$user->display_name,
					absint( $user->ID ),
					$user->user_email
				);
				$sel[ $user_id ] = $user_string;
			}

			echo '<div class="alignleft actions bulkactions">';

			yit_add_select2_fields(
				array(
					'type'              => 'hidden',
					'class'             => 'wc-customer-search',
					'id'                => 'customer_user',
					'name'              => '_customer_user',
					'data-placeholder'  => esc_html__( 'Show all users', 'yith-woocommerce-points-and-rewards' ),
					'data-allow_clear'  => false,
					'data-selected'     => $sel,
					'data-multiple'     => false,
					'data-action'       => '',
					'value'             => $user_id,
					'style'             => 'width:200px',
					'custom-attributes' => array(),
				)
			);

			submit_button( __( 'Filter', 'yith-woocommerce-points-and-rewards' ), 'button', false, false, array( 'id' => 'post-query-submit' ) );

			echo '</div>';

			$type_list   = array(
				'all'         => esc_html__( 'All users', 'yith-woocommerce-points-and-rewards' ),
				'with_points' => esc_html__( 'Only users with points', 'yith-woocommerce-points-and-rewards' ),
				'only_banned' => esc_html__( 'Only banned users', 'yith-woocommerce-points-and-rewards' ),
			);
			$filter_type = isset( $_REQUEST['ywpar_list_filter'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['ywpar_list_filter'] ) ) : 'all'; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			echo '<div class="alignleft actions bulkactions">';
			?>
			<select id="ywpar_list_filter" name="ywpar_list_filter">
				<?php foreach ( $type_list as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
			submit_button(
				__( 'Filter', 'yith-woocommerce-points-and-rewards' ),
				'button',
				false,
				false,
				array(
					'id'    => 'post-query-submit',
					'class' => 'ywpar_filter_button',
				)
			);
			echo '</div>';
			if ( isset( $_POST['ywpar_list_filter'] ) ) {
				$reset_url = add_query_arg( 'admin.php', array( 'page' => 'yith_woocommerce_points_and_rewards') );
				echo '<a href="' . $reset_url . '" class="yith-plugin-fw__button--tertiary">' . __( 'Reset filters', 'yith-woocommerce-points-and-rewards' ) . '</a>';
			}
		}
	}

}
