<?php
/**
 * This class handle all functions about import/export data for Points and Rewards.
 *
 * @class   YITH_WC_Points_Rewards
 * @since   4.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Import_Export' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Import_Export
	 */
	class YITH_WC_Points_Rewards_Import_Export {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Import_Export
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Import_Export
		 * @since 4.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  4.0.0
		 */
		public function __construct() {
			add_action( 'yith_ywpar_customers', array( $this, 'add_button' ), 5 );
			add_action( 'admin_footer', array( $this, 'add_modal_template' ) );
			add_action( 'wp_ajax_yith_wpar_submit_import_export_form', array( $this, 'submit_form' ) );
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
            if( $this->is_customers_page() ) : ?>
                <script type="text/javascript">
                    jQuery(function () {
                        jQuery('h1.yith-plugin-fw-panel-custom-tab-title').after("<a class='page-title-action export-import-custom-button'><?php echo esc_html__( 'Export/Import', 'yith-woocommerce-points-and-rewards' ); ?></a>");
                    });
                </script>
		    <?php endif;

		}


		public function add_modal_template() {
			include_once YITH_YWPAR_VIEWS_PATH . '/tabs/customers/export-import/modal/content.php';
		}


		public function submit_form() {
			$fields = array();
			if ( isset( $_POST['options'] ) ) {
				foreach ( $_POST['options'] as $option ) {
					$name            = sanitize_text_field( $option['name'] );
					$fields[ $name ] = sanitize_text_field( $option['value'] );
				}
				$this->handle_import_export( $fields, $_POST['type'] );
			}
			die();
		}


		/**
		 * Import, export.
		 *
		 * @param array $posted Posted.
		 *
		 * @since 3.0.0
		 */
		public function handle_import_export( array $posted, $type ) {
			$delimiter = $posted['csv_delimiter'];
			$format    = $posted['csv_format'];

			switch ( $type ) {
				case 'import':
					if ( ! isset( $posted['csv_file'] ) || empty( $posted['csv_file'] ) ) {
						$this->admin_notices[] = array(
							'class'   => 'ywpar_import_result error  notice-error',
							'message' => esc_html__( 'The CSV file cannot be imported.', 'yith-woocommerce-points-and-rewards' ),
						);
						wp_send_json_error( $this->admin_notices );
						return;
					}

					$import_action = $posted['action'];
					$this->import_from_csv( $posted['csv_file'], $delimiter, $format, $import_action );
					wp_send_json_success();

					break;

				case 'export':
					$this->export( $format, $delimiter );
					break;

				default:
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
			/**
			 * APPLY_FILTERS: ywpar_import_csv_file
			 *
			 * Import csv file path.
			 *
			 * @param string $path .
			 */
			$file = apply_filters( 'ywpar_import_csv_file', $file );

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
								/**
								 * APPLY_FILTERS: ywpar_import_description_label
								 *
								 * filter the import description label.
								 */
								'description'  => apply_filters( 'ywpar_import_description_label', esc_html__( 'Import', 'yith-woocommerce-points-and-rewards' ) ),
								/**
								 * APPLY_FILTERS: ywpar_save_log_on_import
								 *
								 * set if register the log of the operation or not - default 1.
								 */
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
						'message' => esc_html__( 'The CSV file has been imported.', 'yith-woocommerce-points-and-rewards' ),
					);
				} else {

					$this->admin_notices[] = array(
						'class'   => 'ywpar_import_result error  notice-error',
						'message' => esc_html__( 'The CSV file is not valid.', 'yith-woocommerce-points-and-rewards' ),
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
		 * @since 4.0.0
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
		 * @since 4.0.0
		 */
		public function export( $format = 'id', $delimiter = ',' ) {
			global $wpdb;

			/**
			 * APPLY_FILTERS: ywpar_export_csv_user_meta
			 *
			 * filter the user meta used to export.
			 *
			 * @param string $meta default '_ywpar_user_total_points'.
			 * @param string $format default 'id'.
			 */
			$results   = $wpdb->get_results( $wpdb->prepare( "SELECT u.id, u.user_email as email, um.meta_value as points FROM $wpdb->users u LEFT JOIN $wpdb->usermeta um ON ( u.id = um.user_id AND um.meta_key LIKE %s )", apply_filters( 'ywpar_export_csv_user_meta', '_ywpar_user_total_points', $format ) ) ); //phpcs:ignore
			$first_row = ( 'id' === $format ) ? array( 'id', 'points' ) : array( 'email', 'points' );

			/**
			 * APPLY_FILTERS: ywpar_export_csv_first_row
			 *
			 * filter the first row.
			 *
			 * @param array $first_row .
			 * @param string $format default 'id'.
			 */
			$data[] = apply_filters( 'ywpar_export_csv_first_row', $first_row, $format );

			if ( $results ) {
				foreach ( $results as $result ) {
					switch ( $format ) {
						case 'id':
							/**
							 * APPLY_FILTERS: ywpar_export_csv_row
							 *
							 * filter the row.
							 *
							 */
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
		 * @since 4.0.0
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
			exit();

		}

	}

}
