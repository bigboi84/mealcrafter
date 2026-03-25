<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Abstract for all plugin emails
 *
 * @class   YITH_YWPAR_Email
 * @package YITH\YWPAR
 * @since   4.18.0
 * @author YITH
 */

if ( ! class_exists( 'YITH_YWPAR_Email' ) ) {
	/**
	 * YITH_YWPAR_Email
	 *
	 * @since 1.0.0
	 */
	abstract class YITH_YWPAR_Email extends WC_Email {
		/**
		 * Get dummy customer
		 *
		 * @return object the customer object
		 */
		public function get_dummy_customer() {
			$customer = new stdClass();
			$customer->user_email = 'johndoe@test.com';
			$customer->first_name = 'John';
			$customer->last_name = 'Doe';
			$customer->username = 'JohnDoe';
			$customer->total_points = 1000;

			return $customer;
		}

		/**
		 * Get dummy history
		 *
		 * @return array the history array
		 */
		public function get_dummy_history() {
			$history = array();
			$history_event_1 = new stdClass();
            $history_event_1->id = 1;
            $history_event_1->user_id = 1;
            $history_event_1->action = 'level_achieved_exp';
            $history_event_1->order_id = 0;
            $history_event_1->amount = 300;
            $history_event_1->date_earning = date('Y-m-d H:i:s');
            $history_event_1->cancelled = ''; 
            $history_event_1->description = ''; 
            $history_event_1->info = '';
			$history_event_2 = new stdClass();
            $history_event_2->id = 2;
            $history_event_2->user_id = 1;
            $history_event_2->action = 'order_completed';
            $history_event_2->order_id = 0;
            $history_event_2->amount = 700;
            $history_event_2->date_earning = date( 'Y-m-d H:i:s', strtotime( '-1 day' ) );
            $history_event_2->cancelled = ''; 
            $history_event_2->description = ''; 
            $history_event_2->info = '';

			array_push( $history, $history_event_1, $history_event_2 );

			return $history;
		}
	}
}
