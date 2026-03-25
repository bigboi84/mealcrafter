<?php
/**
 * Class to manage badges for customer levels
 *
 * @class   YITH_WC_Points_Rewards_Editor_Levels_Badges
 * @since   2.2.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'YITH_WC_Points_Rewards_Editor', false ) ) {
	include_once YITH_YWPAR_INC . '/admin/cpt/abstract-class-yith-wc-points-rewards-editor.php';
}

if ( ! class_exists( 'YITH_WC_Points_Rewards_Editor_Levels_Badges' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Editor_Levels_Badges
	 */
	class YITH_WC_Points_Rewards_Editor_Levels_Badges extends YITH_WC_Points_Rewards_Editor {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Points_Rewards_Editor_Levels_Badges
		 */
		protected static $instance;

		/**
		 *  Post type name
		 *
		 * @var string
		 */
		public $post_type = '';

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Points_Rewards_Editor_Levels_Badges
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
		public function __construct() {

			$this->post_type = YITH_WC_Points_Rewards_Post_Types::$level_badge;
			$this->options   = include_once YITH_YWPAR_DIR . 'plugin-options/metaboxes/levels-badges-options.php';

			parent::__construct();

			add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
			add_action( 'save_post', array( $this, 'save_post' ), 1, 2 );
			add_action( 'admin_head-edit.php', array( $this, 'add_custom_header_description' ) );

		}

		/**
		 * Change the post message
		 *
		 * @param array $messages List of messages.
		 *
		 * @return array
		 */
		public function change_post_update_message( $messages ) {
			global $post;

			if ( $post && $this->post_type === $post->post_type ) {
				$messages['post'][1] = _x( 'Level updated', 'Message that appears when a post is updated', 'yith-woocommerce-points-and-rewards' );
				$messages['post'][6] = _x( 'Level published', 'Message that appears when a post is published', 'yith-woocommerce-points-and-rewards' );
			}

			return $messages;
		}

		/**
		 * Change the bulk post message
		 *
		 * @param array $messages List of messages.
		 * @param array $bulk_counts List of bulk counts.
		 *
		 * @return array
		 */
		public function change_bulk_post_updated_messages( $messages, $bulk_counts ) {
			global $post;

			if ( $post && $this->post_type === $post->post_type ) {
				$messages['post']['deleted'] = _n( '%s badge permanently deleted.', '%s badges permanently deleted.', $bulk_counts['deleted'], 'yith-woocommerce-points-and-rewards' );

			}
			return $messages;
		}

		/**
		 * Set custom columns
		 *
		 * @param array $columns Existing columns.
		 * @return  array
		 * @since   2.2.0
		 */
		public function set_custom_columns( $columns ) {
			$columns = array(
				'cb'                => '<input type="checkbox" />',
				'title'             => esc_html__( 'Level name', 'yith-woocommerce-points-and-rewards' ),
				'points_to_collect' => esc_html__( 'Points to collect', 'yith-woocommerce-points-and-rewards' ),
				'badge'             => esc_html__( 'Badge', 'yith-woocommerce-points-and-rewards' ),
				'status'            => esc_html__( 'Status', 'yith-woocommerce-points-and-rewards' ),
				'action'            => '',
			);

			return $columns;
		}

		/**
		 * Manage custom columns
		 *
		 * @param string $column Current column.
		 * @param int    $post_id Post ID.
		 *
		 * @since   2.2.0
		 */
		public function render_custom_columns( $column, $post_id ) {
			$level_badge = ywpar_get_level_badge( $post_id );

			if ( 'action' === $column ) {
				$actions = yith_plugin_fw_get_default_post_actions( $post_id );
				unset( $actions['trash'] );

				if ( current_user_can( 'delete_post', $post_id ) ) {
					$title             = _draft_or_post_title( $post_id );
					$actions['delete'] = array(
						'type'         => 'action-button',
						'title'        => _x( 'Delete', 'Post action', 'yith-woocommerce-points-and-rewards' ),
						'action'       => 'delete',
						'icon'         => 'trash',
						'url'          => get_delete_post_link( $post_id, '', true ),
						'confirm_data' => array(
							'title'               => __( 'Confirm delete', 'yith-woocommerce-points-and-rewards' ),
							// translators: %s is the title of the post object.
							'message'             => sprintf( __( 'Are you sure you want to delete "%s"?', 'yith-woocommerce-points-and-rewards' ), '<strong>' . $title . '</strong>' ) . '<br /><br />' . __( 'This action cannot be undone and you will not be able to recover this data.', 'yith-woocommerce-points-and-rewards' ),
							'cancel-button'       => __( 'No', 'yith-woocommerce-points-and-rewards' ),
							'confirm-button'      => _x( 'Yes, delete', 'Delete confirmation action', 'yith-woocommerce-points-and-rewards' ),
							'confirm-button-type' => 'delete',
						),
					);
				}

				yith_plugin_fw_get_action_buttons( $actions, true );
			}

			/* points to collect column */
			if ( 'points_to_collect' === $column ) {
				$points_to_collect = $level_badge->get_points_to_collect();
				$from              = esc_html__( 'From', 'yith-woocommerce-points-and-rewards' ) . ' ' . $points_to_collect['from'];
				$to                = empty( $points_to_collect['to'] ) ? '' : esc_html__( 'to', 'yith-woocommerce-points-and-rewards' ) . ' ' . $points_to_collect['to'];
				echo esc_html( $from . ' ' . $to );
			}

			/* badge image column */
			if ( 'badge' === $column ) {
				$image = $level_badge->get_image();
				if ( ! empty( $image ) ) {
					echo wp_kses_post( '<img class="ywpar_lvl_badge" src="' . $image . '" />' );
				} else {
					echo '';
				}
			}

			/* status column */
			if ( 'status' === $column ) {
				echo '<div class="yith-plugin-ui" data-id="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'ywpar-levels-badges-status' ) ) . '">';
				$field = array(
					'id'    => 'ywpar_lb_status',
					'name'  => 'status',
					'type'  => 'onoff',
					'title' => '',
					'class' => 'ywpar-lb-status',
					'value' => 'on' === $level_badge->get_status() ? 'yes' : 'no',
				);
				yith_plugin_fw_get_field( $field, true );
				echo '</div>';
			}
		}

		/**
		 * Return the back to list button label.
		 *
		 * @return string
		 */
		protected function get_back_button_list_label() {
			return esc_html__( 'back to levels list', 'yith-woocommerce-points-and-rewards' );
		}

		/**
		 * Customize the bulk action list.
		 *
		 * @param array $actions List of actions.
		 *
		 * @return  array
		 * @since   2.2.0
		 */
		public function customize_bulk_actions( $actions ) {

			if ( ! isset( $_GET['post_status'] ) || 'trash' !== $_GET['post_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$actions = array(
					'activate'   => esc_html_x( 'Activate', 'Admin action label to activate post type', 'yith-woocommerce-points-and-rewards' ),
					'deactivate' => esc_html_x( 'Deactivate', 'Admin action label to deactivate post type', 'yith-woocommerce-points-and-rewards' ),
					'associate'  => esc_html_x( 'Associate to customers', 'Admin action label to associate the levels to the customers', 'yith-woocommerce-points-and-rewards' ),
					'delete'     => esc_html_x( 'Delete', 'Admin action label to delete a post type', 'yith-woocommerce-points-and-rewards' ),
				);
			}
			return $actions;
		}

		/**
		 * The function to be called to output the meta box in Levels & Badges detail/edit page.
		 *
		 * @param WP_Post $post The Post object.
		 *
		 * @return  void
		 * @since   2.1.0
		 */
		public function option_metabox( $post ) {

			wp_nonce_field( 'ywpar_level_badges_save_data', 'ywpar_level_badges_meta_nonce' );

			$values = array();

			/* getting previous saved settings */
			if ( 'auto-draft' !== $post->post_status ) {
				$obj      = ywpar_get_level_badge( $post->ID );
				$obj_data = $obj->get_data();
			}
			?>
			<div class="ywpar-metabox-wrapper">
				<div class="yith-plugin-ui yith-plugin-fw">
					<table class="form-table">
						<?php foreach ( $this->options as $field ) : ?>
							<?php
							$std               = isset( $field['std'] ) ? $field['std'] : '';
							$field['value']    = isset( $obj_data[ $field['name'] ] ) ? $obj_data[ $field['name'] ] : $std;
							$custom_attributes = ywpar_get_custom_attributes_of_custom_field( $field );
							?>

							<tr class="yith-plugin-fw-panel-wc-row <?php echo esc_attr( $field['type'] ); ?> <?php echo esc_attr( $field['id'] ); ?>" <?php echo wp_kses_post( $custom_attributes ); ?>>
								<th scope="row" class="titledesc">
									<label
										for="<?php echo esc_attr( $field['name'] ); ?>"><?php echo esc_attr( $field['title'] ); ?></label>
								</th>
								<td class="forminp forminp-<?php echo esc_attr( $field['type'] ); ?>">
									<?php yith_plugin_fw_get_field( $field, true ); ?>
									<?php if ( isset( $field['desc'] ) ) : ?>
										<span class="description"><?php echo wp_kses_post( $field['desc'] ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
					<div id="submitpost" class="yith-plugin-ui">
						<?php

						$getted = $_GET; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

						if ( 'auto-draft' !== $post->post_status ) {
							$name   = 'save';
							$label  = esc_html__( 'Update', 'yith-woocommerce-points-and-rewards' );
							$delete = sprintf( ' <a href="%s" class="button-secondary yith-plugin-fw__button">%s</a>', get_delete_post_link( $getted['post'] ), esc_html__( 'Delete', 'yith-woocommerce-points-and-rewards' ) );
						} else {
							$name   = 'publish';
							$label  = esc_html__( 'Save Level', 'yith-woocommerce-points-and-rewards' );
							$delete = '';
						}

						echo sprintf( '<input name="%s" type="submit" class="yith-plugin-fw__button button-primary" id="publish" value="%s" />', $name, $label ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $delete; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Save meta box process
		 *
		 * @param integer $post_id The Post ID.
		 * @param WP_Post $post The Post object.
		 *
		 * @return  void
		 * @since   2.2.0
		 */
		public function save_post( $post_id, $post ) {

			// $post_id and $post are required.
			if ( empty( $post_id ) || empty( $post ) || $this->saved_meta_box ) {
				return;
			}

			// Check the nonce.
			if ( empty( $_POST['ywpar_level_badges_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ywpar_level_badges_meta_nonce'] ) ), 'ywpar_level_badges_save_data' ) ) {
				return;
			}

			$posted = $_POST;

			// Don't save meta boxes for revisions or autosaves.
			if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}

			// Check the post being saved == the $post_id to prevent triggering this call for other save_post events.
			if ( empty( $posted['post_ID'] ) || (int) $posted['post_ID'] !== $post_id ) {
				return;
			}

			// Check user has permission to edit.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			$this->saved_meta_box = true;
			$obj                  = ywpar_get_level_badge( $post_id );

			foreach ( $obj->get_data() as $key => $value ) {
				if ( 'name' === $key && isset( $posted[ $key ] ) ) {
					wp_update_post(
						array(
							'ID'         => $post_id,
							'post_title' => $posted[ $key ],
						)
					);
				}

				if ( isset( $posted[ $key ] ) ) {
					$changes[ $key ] = $posted[ $key ];
				} elseif ( 'id' !== $key ) {
					$changes[ $key ] = 'no';
				}
			}

			$obj->set_props( $changes );
			$obj->save();
		}

		/**
		 * Retrieve an array of parameters for blank state.
		 *
		 * @return array
		 */
		protected function get_blank_state_params() {
			return array(
				'type'     => 'list-table-blank-state',
				'icon_url' => YITH_YWPAR_ASSETS_URL . '/images/levels-empty.svg',
				// translators: placeholder is an html tag.
				'message'  => sprintf( esc_html_x( 'You have no points levels created yet.%1$sCreate your first one now!', 'placeholder is an html tag', 'yith-woocommerce-points-and-rewards' ), '<br>' ),
				'cta'      => array(
					'title' => esc_html__( 'Create level', 'yith-woocommerce-points-and-rewards' ),
					'icon'  => '',
					'url'   => admin_url( 'post-new.php?post_type=' . $this->post_type ),
				),
			);
		}


		/**
		 * Adds custom description in header for post type "Points assignment rules"
		 */
		public function add_custom_header_description() {
			global $current_screen;
			if ( 'ywpar-level-badge' != $current_screen->post_type ) {
				return;
			}
			$description = esc_html_x("Set levels and visual badges to highlight your customers' progress.",'Description of custom post type "Points Assignment Rules"', 'yith-woocommerce-points-and-rewards' );

			?>
            <script type="text/javascript">
                jQuery(function () {
                    jQuery('hr.wp-header-end').before("<div class='yith-plugin-fw-wp-page__description'><?php echo wp_kses_post( $description );?></div>");
                });
            </script>
			<?php
		}

	}

}
