<?php
/**
 * Class to earning extra points with the birthdate
 *
 * @class   YITH_WC_Points_Rewards_Extra_Points_Birthdate
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_WC_Points_Rewards_Extra_Points_Birthdate' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Extra_Points_Birthdate
	 */
	class YITH_WC_Points_Rewards_Extra_Points_Birthdate {

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		public static function init() {
			$available_places = ywpar_get_option( 'birthday_date_field_where', array( 'my-account', 'register_form', 'checkout' ) );

			if ( in_array( 'my-account', $available_places, true ) ) {
				add_action( 'woocommerce_edit_account_form', array( __CLASS__, 'add_birthday_field' ) );
			}

			if ( in_array( 'register_form', $available_places, true ) ) {
				add_action( 'woocommerce_register_form', array( __CLASS__, 'add_birthday_field' ) );
			}

			if ( in_array( 'checkout', $available_places, true ) ) {
				add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'add_birthday_field_checkout' ) );
			}

			add_action( 'ywpar_cron_birthday', array( __CLASS__, 'extra_points_birthdate' ), 10 );

			add_action( 'woocommerce_save_account_details', array( __CLASS__, 'save_birthdate' ) );
			add_action( 'woocommerce_created_customer', array( __CLASS__, 'save_birthdate' ), 10, 1 );
			add_action( 'woocommerce_checkout_update_user_meta', array( __CLASS__, 'save_birthdate' ), 10 );

			if ( is_admin() ) {
				add_action( 'show_user_profile', array( __CLASS__, 'add_birthday_field_admin' ) );
				add_action( 'edit_user_profile', array( __CLASS__, 'add_birthday_field_admin' ) );
				add_action( 'personal_options_update', array( __CLASS__, 'save_birthdate' ) );
				add_action( 'edit_user_profile_update', array( __CLASS__, 'save_birthdate' ) );
			}
		}

		/**
		 * Add the birthdate field
		 *
		 * @throws Exception Throws exception.
		 * @since   3.0.0
		 */
		public static function add_birthday_field() {

			$customer = ywpar_get_current_customer();

			$birth_date       = '';
			$date_format      = ywpar_get_option( 'birthday_date_format' );
			$date_formats     = ywpar_get_date_formats();
			$date_placeholder = ywpar_date_placeholders();
			$date_patterns    = ywpar_get_date_patterns();

			if ( $customer ) {
				$registered_date = $customer->get_birthdate();

				if ( $registered_date ) {
					$date       = DateTime::createFromFormat( 'Y-m-d', esc_attr( $registered_date ) );
					$birth_date = $date->format( $date_formats[ $date_format ] );
				}
			}

			$enabled = ( '' === $birth_date ) ? '' : 'disabled';
			if ( is_wc_endpoint_url( 'edit-account' ) ) {
				if ( ! empty( $birth_date ) ) {
					?>

					<p class="form-row form-row-wide">
						<label for="yith_birthday">
							<?php 
							/**
							 * APPLY_FILTERS: yith_birthday_label
							 *
							 * edit the birthday field label
							 *
							 * @param string $label
							 */
							echo wp_kses_post( apply_filters( 'yith_birthday_label', __( 'Date of birth', 'yith-woocommerce-points-and-rewards' ) ) );
							?>
							<?php 
							/**
							 * APPLY_FILTERS: ywpar_required_birthday
							 *
							 * set the birtday field to be required.
							 *
							 * @param string $required 
							 */
							echo wp_kses_post( ( apply_filters( 'ywpar_required_birthday', '' ) === 'required' ) ? ' <abbr class="required" title="required">*</abbr>' : '' );
							?>
						</label>
						<span id="yith_birthday"
							style="display: block;border: solid 1px #ccc;padding:10px;"><?php echo esc_html( $birth_date ); ?></span>
						<span class="yith_birthday_account_message"
							style="font-style: italic"><?php esc_html_e( 'If you need to change your date of birth, please contact the website administrator.', 'yith-woocommerce-points-and-rewards' ); ?></span>
					</p>

					<?php
					return;
				}
			}
			?>

			<p class="form-row form-row-wide">
				<label for="yith_birthday">
					<?php echo wp_kses_post( apply_filters( 'yith_birthday_label', esc_html( __( 'Date of birth', 'yith-woocommerce-points-and-rewards' ) ) ) ); ?><?php echo wp_kses_post( ( apply_filters( 'ywpar_required_birthday', '' ) === 'required' ) ? ' <abbr class="required" title="required">*</abbr>' : '' ); ?>
				</label>
				<input
					type="text"
					class="input-text"
					name="yith_birthday"
					maxlength="10"
					placeholder="<?php echo esc_attr( $date_placeholder[ $date_format ] ); ?>"
					pattern="<?php echo esc_attr( $date_patterns[ $date_format ] ); ?>"
					value="<?php echo esc_attr( $birth_date ); ?>"
					<?php echo esc_attr( apply_filters( 'ywpar_required_birthday', '' ) ); ?>
					<?php echo esc_attr( $enabled ); ?>
				/>

			</p>

			<?php

		}

		/**
		 * Add customer birthdate field to checkout process
		 *
		 * @param array $fields Checkout fields.
		 *
		 * @return  array
		 * @since   1.0.0
		 *
		 */
		public static function add_birthday_field_checkout( $fields ) {

			$date_format      = ywpar_get_option( 'birthday_date_format' );
			$date_placeholder = ywpar_date_placeholders();
			$date_patterns    = ywpar_get_date_patterns();

			if ( is_user_logged_in() ) {
				$customer        = ywpar_get_current_customer();
				$registered_date = $customer->get_birthdate();
				$section         = $registered_date ? '' : 'billing';
			} else {
				$section = 'account';
			}

			if ( ! empty( $section ) ) {
				$fields[ $section ]['yith_birthday'] = array(
					'label'             => apply_filters( 'yith_birthday_label', esc_html__( 'Date of birth', 'yith-woocommerce-points-and-rewards' ) ),
					'custom_attributes' => array(
						'pattern'   => $date_patterns[ $date_format ],
						'maxlength' => 10,
					),
					'placeholder'       => $date_placeholder[ $date_format ],
					'input_class'       => array( 'ywpar-birthday' ),
					'class'             => array( 'form-row-wide' ),
					'priority'          => 999,
				);

				if ( apply_filters( 'ywpar_required_birthday', '' ) === 'required' ) {
					$fields[ $section ]['yith_birthday']['label']                        .= ' <abbr class="required" title="required">*</abbr>';
					$fields[ $section ]['yith_birthday']['custom_attributes']['required'] = 'required';
				}
			}

			return $fields;
		}

		/**
		 * Add customer birthday field
		 *
		 * @param   WP_User $user User.
		 * @since   3.0.0
		 *
		 * @return  void
		 * @throws Exception Throws Exception.
		 */
		public static function add_birthday_field_admin( $user ) {

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			$date_format      = ywpar_get_option( 'birthday_date_format' );
			$date_formats     = ywpar_get_date_formats();
			$date_placeholder = ywpar_date_placeholders();
			$date_patterns    = ywpar_get_date_patterns();
			$birth_date       = '';
			$customer         = ywpar_get_customer( $user->ID );
			$registered_date  = $customer ? $customer->get_birthdate() : false;

			if ( $registered_date ) {
				$date       = DateTime::createFromFormat( 'Y-m-d', esc_attr( $registered_date ) );
				$birth_date = $date->format( $date_formats[ $date_format ] );
			}
			?>
			<h3><?php esc_html_e( 'Points and Rewards', 'yith-woocommerce-points-and-rewards' ); ?></h3>
			<table class="form-table">
				<tr>
					<th>
						<label for="yith_birthday"><?php esc_html_e( 'Date of birth', 'yith-woocommerce-points-and-rewards' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							class="ywpar_date"
							name="yith_birthday"
							id="yith_birthday"
							value="<?php echo esc_attr( $birth_date ); ?>"
							placeholder="<?php echo esc_attr( $date_placeholder[ $date_format ] ); ?>"
							maxlength="10"
							pattern="<?php echo esc_attr( $date_patterns[ $date_format ] ); ?>"

						/>
					</td>
				</tr>
			</table>

			<?php

		}


		/**
		 * Save customer birthdate from admin page
		 *
		 * @param   int $customer_id Customer ID.
		 * @since   3.0.0
		 * @return  void
		 */
		public static function save_birthdate( $customer_id ) {
			if ( isset( $_POST['yith_birthday'] ) ) { //phpcs:ignore
				$birth_date    = sanitize_text_field( wp_unslash( $_POST['yith_birthday'] ) ); //phpcs:ignore
				$date_format   = ywpar_get_option( 'birthday_date_format' );
				$date_patterns = ywpar_get_date_patterns();
				$date_formats  = ywpar_get_date_formats();
				if ( preg_match( "/{$date_patterns[$date_format]}/", $birth_date ) ) {
					$date       = DateTime::createFromFormat( $date_formats[ $date_format ], $birth_date );
					$birth_date = $date->format( 'Y-m-d' );
					update_user_meta( $customer_id, 'yith_birthday', $birth_date );
				} else {
					delete_user_meta( $customer_id, 'yith_birthday' );
				}
			}

		}


		/**
		 * Trigger the extra points for users that today make the birthday
		 */
		public static function extra_points_birthdate() {
			global $wpdb;
            $usermeta_table = apply_filters( 'yith_extrapoints_usermeta_table',$wpdb->base_prefix.'usermeta' );
			$user_query = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$usermeta_table} WHERE ( meta_key = 'ywces_birthday' OR  meta_key = 'yith_birthday' ) AND MONTH(meta_value) = MONTH(NOW()) AND DAY(meta_value) = DAY(NOW())" ); //phpcs:ignore

			if ( ! empty( $user_query ) ) {
				$today = new DateTime();
				foreach ( $user_query as $user_id ) {
					$customer    = ywpar_get_customer( $user_id );
					$last_points = $customer->get_last_birthday_points();
					if ( $customer->is_enabled() && $today->format( 'Y' ) !== $last_points ) {
						yith_points()->extra_points->handle_actions( array( 'birthday', 'points' ), $customer );
						$customer->set_last_birthday_points( gmdate( 'Y' ) );
						yith_points()->logger->add( 'yith_extra_points_birthdate', 'Rewarded birthdate extra points to the customer ' . $user_id );
						$customer->save();
					}
				}
			}

		}
	}

}

