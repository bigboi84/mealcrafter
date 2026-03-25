<?php
/**
 * This class it used to migrate points from WooCommerce Points and Rewards to YITH WooCommerce Points and Rewards Premium
 *
 * @class   YITH_WC_Points_Rewards
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Porting' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Porting
	 */
	class YITH_WC_Points_Rewards_Porting {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Porting
		 */
		protected static $instance;

		/**
		 * Admin notices
		 *
		 * @var array
		 */
		public $admin_notices = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Porting
		 * @since 1.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			if ( class_exists( 'WC_Points_Rewards' ) ) {
				add_filter( 'ywpar_import_more', array( $this, 'add_settings' ) );
			}

			add_action( 'admin_notices', array( $this, 'show_update_error' ) );
		}


		/**
		 * Add the option to import from WooCommerce Points and Rewards Plugins
		 *
		 * @param array $settings Settings.
		 *
		 * @return void
		 */
		public function add_settings( $settings ) {

			$field = array(
				'title'           => __( 'Apply points from WooCommerce Points and Rewards', 'yith-woocommerce-points-and-rewards' ),
				'desc'            => __( 'You can do this action only one time', 'yith-woocommerce-points-and-rewards' ),
				'type'            => 'yith-field',
				'yith-type'       => 'points-previous-order',
				'label'           => __( 'Import points', 'yith-woocommerce-points-and-rewards' ),
				'button-class'    => 'ywrac_reset_points',
				'button-name'     => __( 'Import Points', 'yith-woocommerce-points-and-rewards' ),
				'show_datepicker' => false,
				'id'              => 'ywpar_apply_points_from_wc_points_rewards',
				'name'            => 'ywpar_apply_points_from_wc_points_rewards',
			);
			?>
			<div class="yit-admin-panel-content-wrap ywpar-wc-points-import" style="margin-top: 40px;">
				<h2 class="wp-heading-inline"><?php esc_html_e( 'WooCommerce Points and Rewards points import', 'yith-woocommerce-points-and-rewards' ); ?></h2>
				<form id="plugin-fw-wc" method="post">
					<table class="form-table">
						<tbody>
						<tr valign="top" class="yith-plugin-fw-panel-wc-row <?php echo esc_html( $field['type'] ); ?> <?php echo esc_html( $field['name'] ); ?> ">
							<th scope="row" class="titledesc">
								<label for="<?php echo esc_html( $field['name'] ); ?>"><?php echo esc_html( $field['title'] ); ?></label>
							</th>
							<td class="forminp forminp-<?php echo esc_html( $field['type'] ); ?>">
								<button id="<?php echo esc_attr( $field['id'] ); ?>_btn" class="button button-primary"><?php echo esc_html( $field['label'] ); ?></button>
								<?php
								if ( isset( $field['desc'] ) && '' !== $field['desc'] ) {
									?>
									<span class="description"><?php echo esc_html( $field['desc'] ); ?></span>
									<?php
								}
								?>
							</td>
						</tr>
						</tbody>
					</table>
				</form>
			</div>
			<?php

		}


		/**
		 * Migrate point from WooCommerce Points and Rewards
		 *
		 * @return int|void
		 */
		public function migrate_points() {

			if ( ! class_exists( 'WC_Points_Rewards' ) ) {
				return;
			}

			$success = 0;

			$porting_done = get_option( 'yith_ywpar_porting_done' );

			if ( $porting_done ) {
				return $success;
			}

			global $wpdb;
			$actions = array(
				'product-review'   => 'reviews_exp',
				'order-redeem'     => 'redeemed_points',
				'account-signup'   => 'registration_exp',
				'order-placed'     => 'order_completed',
				'expire'           => 'expired_points',
				'admin-adjustment' => 'admin_action',
				'order-cancelled'  => 'order_refund',
			);

			// initialize the custom table names.
			$user_points_log_db_tablename = $wpdb->prefix . 'wc_points_rewards_user_points_log';
			$user_points_db_tablename     = $wpdb->prefix . 'wc_points_rewards_user_points';

			$sql_users_old     = "SELECT wpp.user_id  FROM $user_points_db_tablename as wpp  WHERE wpp.points = 100 AND wpp.date LIKE '2015-07-22%'"; //phpcs:ignore
			$results_users_old = $wpdb->get_col( $sql_users_old );

			$sql = "SELECT
					wplog.user_id as user_id,
					wplog.type as type,
					wplog.points as points,
					wplog.order_id as order_id,
					wplog.date as datelog,
					up.id as point_id,
					up.points_balance as points_balance,
					up.date as up_date
					FROM $user_points_log_db_tablename wplog LEFT JOIN $user_points_db_tablename up ON(wplog.user_points_id = up.id) WHERE 1 ";

			$results = $wpdb->get_results( $sql ); //phpcs:ignore

			$users   = array();
			$counter = 0;

			if ( $results ) {

				$ywpar_table   = $wpdb->prefix . 'yith_ywpar_points_log';
				$initial_query = "INSERT INTO $ywpar_table ( user_id, action, order_id, amount, date_earning, cancelled ) VALUES ";

				$values        = array();
				$place_holders = array();
				$step          = 100;

				foreach ( $results as $item ) {

					if ( isset( $users[ $item->user_id ] ) ) {
						$users[ $item->user_id ] = $users[ $item->user_id ] + $item->points;
					} else {
						$users[ $item->user_id ] = $item->points;
					}

					if ( ! in_array( $item->type, array( 'expire', 'order-cancelled' ), true ) ) {
						array_push( $values, $item->user_id, $actions[ $item->type ], $item->order_id ? $item->order_id : 0, $item->points, $item->datelog, '' );
						$place_holders[] = "('%d', '%s', '%d', '%d', '%s', '%s')";

					} else {
						array_push( $values, $item->user_id, $actions[ $item->type ], $item->order_id ? $item->order_id : 0, $item->points, $item->up_date, $item->datelog );
						$place_holders[] = "('%d', '%s', '%d', '%d', '%s', '%s')";

					}

					if ( 0 === $counter % $step ) {
						$initial_query .= implode( ', ', $place_holders );
						$wpdb->query( $wpdb->prepare( "$initial_query ", $values ) );

						$values        = array();
						$place_holders = array();
						$initial_query = "INSERT INTO $ywpar_table ( user_id, action, order_id, amount, date_earning, cancelled ) VALUES ";
					}

					$counter++;
				}

				if ( ! empty( $place_holders ) ) {
					$initial_query .= implode( ', ', $place_holders );
					$wpdb->query( $wpdb->prepare( "$initial_query ", $values ) );
				}
			}

			if ( $users ) {
				foreach ( $users as $user_id => $points ) {
					$customer      = ywpar_get_customer( $user_id );
					$current_point = $customer->get_total_points();

					if ( is_array( $results_users_old ) && in_array( $user_id, $results_users_old ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$current_point += 100;
					}

					$new_points = $current_point + $points;
					$customer->set_total_points( ( $new_points > 0 ) ? $new_points : 0 );
					$customer->save();
				}
			}

			update_option( 'yith_ywpar_porting_done', true );

			return $counter;
		}


		/**
		 * Shows messages if there are update errors
		 */
		public function show_update_error() {

			if ( ! $this->admin_notices ) {
				return;
			}

			foreach ( $this->admin_notices as $admin_notice ) :
				?>
				 <div id="message" class="ywpar_notices updated notice notice-success is-dismissible yith-plugin-fw-animate__appear-from-top inline <?php echo esc_attr( $admin_notice['class'] ); ?>" style="display: block;">
					<p><?php echo wp_kses_post( $admin_notice['message'] ); ?></p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>
				</div>
				<?php
			endforeach;

		}


		/**
		 * Check if the file csv is sent and call the method import_from_csv
		 *
		 * @param array $posted Posted.
		 */
		public function import( $posted ) {
			//phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_FILES['file_import_csv'] ) || ! is_uploaded_file( $_FILES['file_import_csv']['tmp_name'] ) ) {
				return;
			}

			$uploaddir = wp_upload_dir();

			$userfile_tmp  = $_FILES['file_import_csv']['tmp_name'];
			$userfile_name = $_FILES['file_import_csv']['name'];

			if ( ! move_uploaded_file( $userfile_tmp, $uploaddir . $userfile_name ) ) {
				return;
			}

			$this->import_from_csv( $uploaddir . $userfile_name, $_REQUEST['delimiter'], $_REQUEST['csv_format'], $_REQUEST['csv_import_action'] );

		}

		/**
		 * Import, export
		 *
		 * @param array $posted Posted.
		 *
		 * @since 3.0.0
		 */
		public function import_export( array $posted ) {

			$delimiter = sanitize_text_field( wp_unslash( $posted['delimiter'] ) );
			$format    = sanitize_text_field( wp_unslash( $posted['csv_format'] ) );
			$action    = sanitize_text_field( wp_unslash( $posted['ywpar_safe_submit_field'] ) );
			switch ( $action ) {
				case 'import_points':
					if ( ! isset( $_FILES['file_import_csv']['tmp_name'], $_FILES['file_import_csv']['name'], $posted['csv_import_action'] ) || ! is_uploaded_file( sanitize_text_field( wp_unslash( $_FILES['file_import_csv']['tmp_name'] ) ) ) ) {
						$this->admin_notices[] = array(
							'class'   => 'ywpar_import_result error  notice-error',
							'message' => esc_html__( 'The CSV cannot be imported.', 'yith-woocommerce-points-and-rewards' ),
						);
						return;
					}

					$uploaddir = wp_upload_dir();

					$temp_name = sanitize_text_field( wp_unslash( $_FILES['file_import_csv']['tmp_name'] ) );
					$file_name = sanitize_text_field( wp_unslash( $_FILES['file_import_csv']['name'] ) );

					if ( ! move_uploaded_file( $temp_name, $uploaddir['basedir'] . '\\' . $file_name ) ) {
						$this->admin_notices[] = array(
							'class'   => 'ywpar_import_result error  notice-error',
							'message' => esc_html__( 'The CSV cannot be imported.', 'yith-woocommerce-points-and-rewards' ),
						);
						return;
					}

					$import_action = sanitize_text_field( wp_unslash( $posted['csv_import_action'] ) );
					$this->import_from_csv( $uploaddir['basedir'] . '\\' . $file_name, $delimiter, $format, $import_action );
					break;
				case 'export_points':
					$this->export( $format, $delimiter );
					break;
			}
		}

		/**
		 * Import points from a csv file
		 *
		 * @param string $file File to import.
		 * @param string $delimiter Delimiter.
		 * @param string $format Format.
		 * @param string $action Action.
		 *
		 * @return mixed|void
		 */
		public function import_from_csv( $file, $delimiter, $format, $action ) {

			$response = '';
			$loop     = 0;

			$this->import_start();

			if ( ( $handle = fopen( $file, 'r' ) ) !== false ) { //phpcs:ignore

				$header = fgetcsv( $handle, 0, $delimiter );
				if ( 2 === count( $header ) ) {

					while ( ( $row = fgetcsv( $handle, 0, $delimiter ) ) !== false ) { //phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition

						if ( ! is_array( $row ) || count( $row ) < 2 ) {
							continue;
						}

						list( $field1, $points ) = $row;
						// check if the user exists.
						$user = get_user_by( $format, $field1 );
						if ( false === $user ) {
							// user id does not exist.
							continue;
						} else {
							// user id exists.
							$customer = ywpar_get_customer( $user->ID );
							if ( 'remove' === $action ) {
								// delete all the entries in the log table of user
								// remove points from the user meta.
								$customer->reset();
								$customer->save();
							}

							$args = array(
								'description'  => apply_filters( 'ywpar_import_description_label', esc_html__( 'Import', 'yith-woocommerce-points-and-rewards' ) ),
								'register_log' => apply_filters( 'ywpar_save_log_on_import', 1 ),
							);

							$customer->update_points( $points, 'admin_action', $args );
							$customer->save();

							$loop++;
						}
					}

					$response              = $loop;
					$this->admin_notices[] = array(
						'class'   => 'ywpar_import_result success  notice-success',
						'message' => esc_html__( 'The CSV has been imported.', 'yith-woocommerce-points-and-rewards' ),
					);
				} else {

					$this->admin_notices[] = array(
						'class'   => 'ywpar_import_result error  notice-error',
						'message' => esc_html__( 'The CSV is invalid.', 'yith-woocommerce-points-and-rewards' ),
					);
				}

				fclose( $handle ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
			}

			return apply_filters( 'ywpar_import_from_csv_response', $response, $loop, $file, $delimiter, $format, $action );
		}

		/**
		 * Start import
		 *
		 * @return void
		 * @since 1.0.0
		 */
		private function import_start() {
			if ( function_exists( 'gc_enable' ) ) {
				gc_enable();
			}
			//phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
			@set_time_limit( 0 );
			@ob_flush();
			@flush();
			@ini_set( 'auto_detect_line_endings', '1' );
		}

		/**
		 * This function does the query to database and get the file csv to export
		 *
		 * @param string $format Format.
		 * @param string $delimiter Delimiter.
		 *
		 * @since 1.2.7
		 */
		public function export( $format = 'id', $delimiter = ',' ) {

			global $wpdb;

			$results   = $wpdb->get_results( $wpdb->prepare( "SELECT u.id, u.user_email as email, um.meta_value as points FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON ( u.id = um.user_id AND um.meta_key LIKE %s )", apply_filters( 'ywpar_export_csv_user_meta', '_ywpar_user_total_points', $format ) ) ); //phpcs:ignore
			$first_row = ( 'id' === $format ) ? array( 'id', 'points' ) : array( 'email', 'points' );

			$data[] = apply_filters( 'ywpar_export_csv_first_row', $first_row, $format );

			if ( $results ) {
				foreach ( $results as $result ) {
					switch ( $format ) {
						case 'id':
							$data[] = apply_filters(
								'ywpar_export_csv_row',
								array(
									'id'     => $result->id,
									'points' => empty( $result->points ) ? 0 : $result->points,
								),
								$result
							);
							break;
						case 'email':
							$data[] = apply_filters(
								'ywpar_export_csv_row',
								array(
									'email'  => $result->email,
									'points' => empty( $result->points ) ? 0 : $result->points,
								),
								$result
							);
							break;
						default:
					}
				}
			}

			ob_end_clean();
			header( 'Content-type: text/csv' );
			header( 'Content-Disposition: attachment; filename=ywpar_' . date_i18n( 'Y-m-d' ) . '.csv' );
			header( 'Pragma: no-cache' );
			header( 'Expires: 0' );

			$this->getCSV( $data, $delimiter );
			exit();
		}

		/**
		 * Creates the file CSV
		 *
		 * @since 1.2.6
		 *
		 * @param array  $data Content.
		 * @param string $delimiter Delimiter.
		 */
		private function getCSV( $data, $delimiter ) { //phpcs:ignore
			$output = fopen( 'php://output', 'w' );

			foreach ( $data as $row ) {
				if ( false !== $row ) {
					fputcsv( $output, $row, $delimiter ); // here you can change delimiter/enclosure.
				}
			}

			fclose( $output ); //phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
		}

	}


}

/**
 * Unique access to instance of YITH_WC_Points_Rewards_Porting class
 *
 * @return \YITH_WC_Points_Rewards_Porting
 */
function YITH_WC_Points_Rewards_Porting() { //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_WC_Points_Rewards_Porting::get_instance();
}
