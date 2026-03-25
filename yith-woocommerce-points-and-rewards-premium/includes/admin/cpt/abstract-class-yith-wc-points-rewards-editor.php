<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName.
/**
 * Editor to manage Points and Rewards Custom Post Type
 *
 * @class   YITH_WC_Points_Rewards_Editor
 * @since   3.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Points and Rewards
 */

defined( 'ABSPATH' ) || exit;


if ( ! class_exists( 'YITH_WC_Points_Rewards_Editor' ) ) {

	/**
	 * Class YITH_WC_Points_Rewards_Editor
	 */
	abstract class YITH_WC_Points_Rewards_Editor {

		/**
		 *  Post type name
		 *
		 * @var string
		 */
		public $post_type = '';

		/**
		 * Metabox is saved
		 *
		 * @var boolean
		 */
		protected $saved_meta_box = false;

		/**
		 * Options list
		 *
		 * @var array
		 */
		protected $options = array();

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since 1.0.0
		 */
		public function __construct() {

			/* list table */
			add_filter( "manage_{$this->post_type}_posts_columns", array( $this, 'set_custom_columns' ) );
			add_action( "manage_{$this->post_type}_posts_custom_column", array( $this, 'render_custom_columns' ), 10, 2 );
			add_filter( 'months_dropdown_results', array( $this, 'remove_date_drowpdown' ), 10, 2 );
			add_filter( "bulk_actions-edit-{$this->post_type}", array( $this, 'customize_bulk_actions' ), 1 );
			add_filter( 'post_row_actions', array( $this, 'customize_row_actions' ), 10, 2 );
			add_filter( 'handle_bulk_actions-edit-' . $this->post_type, array( $this, 'handle_bulk_actions' ), 10, 3 );

			/* post editor */
			add_filter( 'woocommerce_screen_ids', array( $this, 'add_screen_ids' ) );
			global $sitepress;
			if ( ! $sitepress ) {
				add_action( 'admin_menu', array( $this, 'remove_publish_box' ) );
			}

			add_filter( 'admin_body_class', array( $this, 'add_post_edit_body_class' ) );
			add_action( 'edit_form_top', array( $this, 'add_back_button' ) );
			add_action( 'manage_posts_extra_tablenav', array( $this, 'maybe_render_blank_state' ) );
			add_filter( 'views_edit-' . $this->post_type, array( $this, 'remove_user_views' ) );

			add_filter( 'post_updated_messages', array( $this, 'change_post_update_message' ) );
			add_filter( 'bulk_post_updated_messages', array( $this, 'change_bulk_post_updated_messages' ), 10, 2 );
		}

		/**
		 * Change the post message
		 *
		 * @param array $messages List of messages.
		 * @param array $bulk_counts List of bulk counts.
		 *
		 * @return array
		 */
		public function change_bulk_post_updated_messages( $messages, $bulk_counts ) {
			return $messages;
		}

		/**
		 * Change the bulk post message
		 *
		 * @param array $messages List of messages.
		 *
		 * @return array
		 */
		public function change_post_update_message( $messages ) {
			return $messages;
		}

		/**
		 * Remove the views form these custom post types.
		 *
		 * @param array $views Views.
		 *
		 * @return array|void
		 */
		public function remove_user_views( $views ) {
			global $post_type;
			if ( $this->post_type === $post_type ) {
				return array();
			}

			return $views;
		}

		/**
		 * Add custom post type screen to WooCommerce list
		 *
		 * @param array $screen_ids Array of Screen IDs.
		 *
		 * @return  array
		 * @since   3.0.0
		 */
		public function add_screen_ids( $screen_ids ) {

			$screen_ids[] = 'edit-' . $this->post_type;
			$screen_ids[] = $this->post_type;

			return $screen_ids;
		}


		/**
		 * Set custom columns
		 *
		 * @param array $columns Existing columns.
		 * @return array
		 * @since   3.0.0
		 */
		public function set_custom_columns( $columns ) {
			return $columns;
		}

		/**
		 * Remove the publish metabox
		 *
		 * @return  void
		 * @since   3.0.0
		 */
		public function remove_publish_box() {
			remove_meta_box( 'submitdiv', $this->post_type, 'side' );
		}

		/**
		 * Manage custom columns
		 *
		 * @param string $column Current column.
		 * @param int    $post_id Post ID.
		 *
		 * @return  void
		 * @since   3.0.0
		 */
		public function render_custom_columns( $column, $post_id ) {
			$values = $this->get_settings_values( $post_id );

			/* status column */
			if ( 'status' === $column ) {
				echo '<div class="yith-plugin-ui" data-id="' . esc_attr( $post_id ) . '" data-nonce="' . esc_attr( wp_create_nonce( 'ywpar-earning-rule-status' ) ) . '">';
				$field = array(
					'id'    => '_ywpar_rule_status_' . $post_id,
					'name'  => '_ywpar_rule_status_' . $post_id,
					'class' => 'ywpar-rule-status',
					'type'  => 'onoff',
					'title' => '',
					'value' => isset( $values['_ywpar_rule_status'] ) ? $values['_ywpar_rule_status'] : 'no',
				);
				yith_plugin_fw_get_field( $field, true );
				echo '</div>';
			}
		}

		/**
		 * Add a back button at the top of the page
		 *
		 * @param WP_Post $post The Post Object.
		 *
		 * @return  void
		 * @since   3.0.0
		 */
		public function add_back_button( $post ) {

			$getted = $_GET; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$back_to_list_label = $this->get_back_button_list_label();

			if ( ( isset( $getted['post_type'] ) && $this->post_type === $getted['post_type'] ) || ( $post && $post->post_type === $this->post_type ) ) {
				printf( '<a href="%1$s" class="ywpar_back_button" title="%2$s">< %2$s</a>', esc_url( esc_url( add_query_arg( array( 'post_type' => $this->post_type ), admin_url( 'edit.php' ) ) ) ), esc_html( $back_to_list_label ) );
			}
		}

		/**
		 * Return the back to list button label.
		 *
		 * @return string
		 */
		protected function get_back_button_list_label() {
			return '';
		}

		/**
		 * Add custom body class
		 *
		 * @param string $classes Classes.
		 *
		 * @return  string
		 * @since   3.0.0
		 */
		public function add_post_edit_body_class( $classes ) {
			if ( isset( $_GET['post'] ) && get_post_type( $_GET['post'] ) === $this->post_type && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) { //phpcs:ignore
				$classes .= ' ' . $this->post_type . '-edit';
			} elseif ( isset( $_GET['post_type'] ) && sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) === $this->post_type && $this->is_empty_list() ) { //phpcs:ignore
				$classes .= ' yith-empty-list';
			}
			return $classes;
		}

		/**
		 * The function to be called to output the meta box in Earning Rule detail/edit page.
		 *
		 * @param WP_Post $post The Post object.
		 *
		 * @return  void
		 * @since   3.0.0
		 */
		public function option_metabox( $post ) {

			wp_nonce_field( 'ywpar_earning_rule_save_data', 'ywpar_earning_rule_meta_nonce' );

			$values = array();

			/* getting previous saved settings */
			if ( 'auto-draft' !== $post->post_status ) {
				$values = $this->get_settings_values( $post->ID );
			}

			$options = $this->options; ?>
			<div class="ywpar-metabox-wrapper">
				<div class="yith-plugin-ui yith-plugin-fw">
					<table class="form-table">
						<?php
						foreach ( $options as $field ) :
							$field['value']    = isset( $values[ $field['id'] ] ) ? $values[ $field['id'] ] : $field['std'];
							$custom_attributes = ywpar_get_custom_attributes_of_custom_field( $field );
							?>
							<tr valign="top"
								class="yith-plugin-fw-panel-wc-row <?php echo esc_attr( $field['type'] ); ?> <?php echo esc_attr( $field['id'] ); ?>" <?php echo wp_kses_post( $custom_attributes ); ?>>
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
							$delete = sprintf( ' <a href="%s" class="button-secondary button">%s</a>', get_delete_post_link( $getted['post'] ), esc_html__( 'Delete', 'yith-woocommerce-points-and-rewards' ) );
						} else {
							$name   = 'publish';
							$label  = esc_html__( 'Save Rule', 'yith-woocommerce-points-and-rewards' );
							$delete = '';
						}

						echo sprintf( '<input name="%s" type="submit" class="button button-primary button" id="publish" value="%s" />', $name, $label ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $delete; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
			</div>
			<?php
		}


		/**
		 * Get the post settings values
		 *
		 * @param integer $post_id The Post ID.
		 *
		 * @return  array
		 * @since   3.0.0
		 */
		public function get_settings_values( $post_id ) {

			$settings = array();

			if ( get_post_type( $post_id ) === $this->post_type ) {

				if ( is_array( $this->options ) ) {
					foreach ( $this->options as $option ) {
						$settings[ $option['id'] ] = get_post_meta( $post_id, $option['id'], true );
					}
				}
			}

			return $settings;
		}


		/**
		 * Remove date dropdown inside the wp list table
		 *
		 * @param object[] $months Array of the months drop-down query results.
		 * @param string   $post_type The post type.
		 *
		 * @return  array
		 * @since   3.0.0
		 */
		public function remove_date_drowpdown( $months, $post_type ) {

			if ( $post_type === $this->post_type ) {
				$months = array();
			}

			return $months;
		}

		/**
		 * Customize the bulk action list.
		 *
		 * @param array $actions List of actions.
		 *
		 * @return  array
		 * @since   3.0.0
		 */
		public function customize_bulk_actions( $actions ) {

			$actions = array(
				'activate'   => esc_html_x( 'Activate', 'Admin action label to activate post type', 'yith-woocommerce-points-and-rewards' ),
				'deactivate' => esc_html_x( 'Deactivate', 'Admin action label to deactivate post type', 'yith-woocommerce-points-and-rewards' ),
				'delete'     => esc_html_x( 'Delete', 'Admin action label to delete a post type', 'yith-woocommerce-points-and-rewards' ),
			);

			return $actions;
		}

		/**
		 * Handle bulk actions.
		 *
		 * @param string $redirect_to URL to redirect to.
		 * @param string $action Action name.
		 * @param array  $ids List of ids.
		 *
		 * @return string
		 */
		public function handle_bulk_actions( $redirect_to, $action, $ids ) {

			if ( 'associate' === $action && YITH_WC_Points_Rewards_Post_Types::$level_badge === $this->post_type ) {
				$wp_user_query = new WP_User_Query(
					array(
						'number' => -1,
						'fields' => 'ids',
					)
				);
				$customers     = $wp_user_query->get_results();

				foreach ( $customers as $customer ) {
					$point_customer = ywpar_get_customer( $customer );

					if ( $point_customer ) {
						$point_customer->update_level();
						$point_customer->save();
					}
				}

				return esc_url_raw( $redirect_to );
			}

			if ( ! in_array( $action, array( 'activate', 'deactivate' ), true ) || empty( $ids ) ) {
				return esc_url_raw( $redirect_to );
			}

			foreach ( $ids as $id ) {
				$this->set_status( $id, 'activate' === $action ? 'on' : 'off' );
			}

			return esc_url_raw( $redirect_to );

		}

		/**
		 * Customize row actions
		 *
		 * @param array   $actions List of actions.
		 * @param WP_Post $post Post.
		 * @return  array
		 * @since   3.0.0
		 */
		public function customize_row_actions( $actions, $post ) {
			if ( $post->post_type === $this->post_type ) {
				return array();
			}
			return $actions;
		}


		/**
		 * Change status to the post
		 *
		 * @param int    $post_id Post id.
		 * @param string $status Status ('yes' or 'not').
		 *
		 * @return void
		 */
		public function set_status( $post_id, $status ) {
			update_post_meta( $post_id, '_status', $status );
		}

		/**
		 * Add a metabox
		 *
		 * @return  void
		 * @since   2.1.0
		 */
		public function add_metabox() {
			remove_meta_box( 'slugdiv', $this->post_type, 'normal' );
			add_meta_box( 'ywpar-' . $this->post_type . '-metabox', esc_html__( 'Options', 'yith-woocommerce-points-and-rewards' ), array( $this, 'option_metabox' ), $this->post_type, 'normal', 'default' );
		}


		/**
		 * Retrieve an array of parameters for blank state.
		 *
		 * @return array{
		 * @type string $icon The YITH icon. You can use this one (to use an YITH icon) or icon_class or icon_url.
		 * @type string $icon_class The icon class. You can use this one (to use a custom class for your icon) or icon or icon_url.
		 * @type string $icon_url The icon URL. You can use this one (to specify an icon URL) or icon_icon or icon_class.
		 * @type string $message The message to be shown.
		 * @type string $cta {
		 *            The call-to-action button params.
		 * @type string $title The call-to-action button title.
		 * @type string $icon The call-to-action button icon.
		 * @type string $url The call-to-action button URL.
		 * @type string $class The call-to-action button class.
		 *                            }
		 *              }
		 */
		protected function get_blank_state_params() {
			return array(
				'type'     => 'list-table-blank-state',
				'icon_url' => YITH_YWPAR_ASSETS_URL . '/images/banners-empty.svg',
				'message'  => 'You have no events yet!',
				'cta'      => array(
					'title' => 'Create your first event',
					'icon'  => 'plus',
					'url'   => admin_url( 'post-new.php?post_type=event' ),
				),
			);
		}

		/**
		 * Show blank slate.
		 *
		 * @param string $which String which tablenav is being shown.
		 * @since 3.0.0
		 */
		public function maybe_render_blank_state( $which ) {
			global $post_type;

			if ( $this->get_blank_state_params() && $post_type === $this->post_type && 'bottom' === $which && $this->is_empty_list() ) {
				$this->render_blank_state();
				echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom > *, .wrap .subsubsub  { display: none; } #posts-filter .tablenav.bottom { height: auto; display: block } </style>';
			}
		}

		/**
		 * Check if the table is empty
		 */
		protected function is_empty_list() {
			$posts = get_posts(
				array(
					'post_type' => $this->post_type,
					'status'    => 'publish',
					'fields'    => 'ids',
				)
			);
			return empty( $posts );
		}

		/**
		 * Render blank state. Extend to add content.
		 */
		protected function render_blank_state() {
			$component         = $this->get_blank_state_params();
			$component['type'] = 'list-table-blank-state';

			yith_plugin_fw_get_component( $component, true );
		}


	}
}
