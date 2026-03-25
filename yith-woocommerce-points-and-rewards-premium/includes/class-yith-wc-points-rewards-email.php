<?php
/**
 * Class to manage the emails to customers.
 *
 * @class   YITH_WC_Points_Rewards_Email
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Email' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Email
	 */
	class YITH_WC_Points_Rewards_Email {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Email
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Email
		 * @since  3.0.0
		 */
		public static function get_instance() {
			return ! is_null( self::$instance ) ? self::$instance : self::$instance = new self();
		}


		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 3.0.0
		 */
		private function __construct() {
			add_filter( 'woocommerce_email_classes', array( __CLASS__, 'add_woocommerce_emails' ) );
			add_action( 'woocommerce_init', array( __CLASS__, 'load_wc_mailer' ) );
			add_filter( 'woocommerce_email_styles', array( __CLASS__, 'add_email_styles' ), 10, 2 );
			add_action( 'ywpar_customer_updated_points', array( __CLASS__, 'maybe_send_update_email' ), 10, 4 );

			if ( ywpar_get_option( 'enable_update_point_email' ) === 'yes' && ywpar_get_option( 'update_point_mail_time', 'daily' ) === 'daily' ) {
				add_action( 'ywpar_cron', array( __CLASS__, 'send_email_update_points' ) );
			}

			if ( 'yes' === ywpar_get_option( 'show_point_summary_on_email' ) ) {
				add_action( 'woocommerce_email_customer_details', 'ywpar_add_order_points_summary', 5 );
			}

		}


		/**
		 * Check if send the update email
		 *
		 * @param int    $customer_id Customer id.
		 * @param int    $points_amount Amount of points.
		 * @param string $action Action.
		 * @param array  $args Arguments.
		 */
		public static function maybe_send_update_email( $customer_id, $points_amount, $action, $args ) {
			if ( ywpar_get_option( 'enable_update_point_email' ) === 'yes' && ywpar_get_option( 'update_point_mail_time', 'daily' ) === 'every_update' ) {
				if ( 'admin_action' === $action && ywpar_get_option( 'update_point_mail_on_admin_action', 'yes' ) === 'yes' ) {
					return;
				}
				self::send_email_update_points( $customer_id );
			}
		}

		/**
		 * Loads WC Mailer when needed
		 *
		 * @return void
		 */
		public static function load_wc_mailer() {
			add_action( 'expired_points_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
			add_action( 'update_points_mail', array( 'WC_Emails', 'send_transactional_email' ), 10 );
		}

		/**
		 * Filters woocommerce available mails, to add wishlist related ones
		 *
		 * @param array $emails Emails.
		 *
		 * @return array
		 */
		public static function add_woocommerce_emails( $emails ) {
			require_once YITH_YWPAR_INC . 'emails/class-yith-ywpar-email.php';
			$emails['YITH_YWPAR_Expiration']    = include YITH_YWPAR_INC . 'emails/class-yith-ywpar-expiration.php';
			$emails['YITH_YWPAR_Update_Points'] = include YITH_YWPAR_INC . 'emails/class-yith-ywpar-update-points.php';

			return $emails;
		}

		/**
		 * Send the email if the user has updated his points
		 *
		 * @param int|string $customer_id Customer id.
		 */
		public static function send_email_update_points( $customer_id = '' ) {

			$user_active = yith_points()->points_log->get_user_list_active_in_the_last_day( $customer_id );

			if ( ! empty( $user_active ) ) {

				foreach ( $user_active as $user ) {
					$current_user_id = $user;
					$customer        = ywpar_get_customer( $user );
					if ( ! $customer || ! $customer->is_enabled() ) {
						continue;
					}

					global $sitepress;
					if ( ! is_null( $sitepress ) ) {
						$user_locale = get_user_meta( $current_user_id, 'locale', true );
						if ( ! empty( $user_locale ) ) {
							$sitepress->switch_lang( $user_locale );
						}
					}

					$email_content   = ywpar_get_option( 'update_point_email_content' );
					$history         = yith_points()->points_log->get_items_earned_in_last_day_by_customer( $current_user_id );

					if ( ! empty( $history ) ) {
						/**
						 * APPLY_FILTERS: ywpar_email_current_points_formatted
						 *
						 * filter the user points in the email content.
						 *
						 * @param int $total_points
						 */
						$current_points = apply_filters( 'ywpar_email_current_points_formatted', $customer->get_total_points() );
						$worth          = yith_points()->redeeming->calculate_price_worth_from_points( abs( $current_points ), $customer );

						$email_content = str_replace( '{username}', $customer->get_wc_customer()->get_username(), $email_content );
						$email_content = str_replace( '{first_name}', $customer->get_wc_customer()->get_billing_first_name(), $email_content );
						$email_content = str_replace( '{last_name}', $customer->get_wc_customer()->get_billing_last_name(), $email_content );
						$email_content = str_replace( '{label_points}', strtolower( ywpar_get_option( 'points_label_plural' ) ), $email_content );
						$email_content = str_replace( '{total_points}', $current_points, $email_content );
						$email_content = str_replace( '{discount}', $worth, $email_content );
						$email_content = str_replace( '{shop_url}', wc_get_page_permalink( 'shop' ), $email_content );
						$email_content = str_replace( '{website_name}', get_option( 'blogname', '' ), $email_content );

                        /**
                         * APPLY_FILTERS: ywpar_update_email_send
                         *
                         * filter description.
                         *
                         * @param bool $send .
                         * @param YITH_WC_Points_Rewards_Customer $customer customer object.
                         * @param array $history customer points history.
                         */
                        if ( ! apply_filters( 'ywpar_update_email_send', true, $customer, $history ) ) {
                            continue;
                        }

						ob_start();
						wc_get_template( '/emails/latest-updates.php', array( 'history' => $history ), YITH_YWPAR_TEMPLATE_PATH, YITH_YWPAR_TEMPLATE_PATH );
						$args = array(
							'user_email'    => $customer->get_wc_customer()->get_email(),
							'email_content' => str_replace( '{latest_updates}', ob_get_contents(), $email_content ),
						);
						/**
						 * DO_ACTION: update_points_mail
						 *
						 * hook on the update points email.
						 *
						 * @param array $args user email and email content.
						 */
						do_action( 'update_points_mail', $args );
						ob_end_clean();
					}
				}
			}
		}

		/**
		 * Add CSS to WC emails
		 *
		 * @param string   $css   The email CSS.
		 * @param WC_Email $email The current email object.
		 *
		 * @return string
		 */
		public static function add_email_styles( $css, $email = null ) {
			if ( $email && $email instanceof WC_Email ) {
				switch ( $email->id ) {
					case 'ywpar_update_points':
					case 'ywpar_expiration':
						ob_start();
						?>
						.points_banner {
						background: #ebebeb url('<?php echo esc_url( YITH_YWPAR_ASSETS_URL ); ?>/images/email_points.svg') no-repeat;
						background-position: 15px 15px;
						background-size: 32px;
						padding: 10px 20px 10px 60px;
						margin-bottom: 10px;
						}

						.points_banner a {
						text-decoration: none !important;
						}

						.shop_table.ywpar_points_rewards {
						border-top: 1px solid #ececec;
						border-bottom: 1px solid #ececec;
						margin-bottom: 10px;
						border-collapse: collapse;
						}

						.shop_table.ywpar_points_rewards thead th {
						font-size: 16px;
						}

						.shop_table.ywpar_points_rewards th, .shop_table.ywpar_points_rewards td {
						border: none;
						border-right: 1px solid #ececec;
						}

						.shop_table.ywpar_points_rewards th:last-child, .shop_table.ywpar_points_rewards td:last-child {
						border-right: none;
						}

						.shop_table.ywpar_points_rewards tr:nth-child(even), .shop_table.ywpar_points_rewards thead {
						background-color: #ececec;
						}
						<?php
						$newcss = ob_get_clean();
						/**
						 * APPLY_FILTERS: ywpar_additional_email_css
						 *
						 * filter the email css.
						 *
						 * @param string $css
						 */
						$css .= apply_filters( 'ywpar_additional_email_css', $newcss );
						break;
				}
			}

			return $css;
		}

	}

}
