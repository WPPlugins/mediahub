<?php

class MediaHub_Content_API extends MediaHub_Core {

	/**
	 * Class variables.
	 *
	 * @access private
	 */
	private $settings_api;
	private $allowed_html = array(
		'iframe'         => array(
			'src'        => array(),
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'a'       => array(
			'href'       => array(),
			'title'      => array(),
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'div'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'dl'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'dd'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'dt'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'form'     => array(
			'role'       => array(),
			'method'     => array(),
			'action'     => array(),
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'input'     => array(
			'placeholder'=> array(),
			'type'       => array(),
			'value'      => array(),
			'name'       => array(),
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'span'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'p'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'h1'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'h2'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'h3'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'h4'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'h5'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'h6'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'table'      => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'blockquote'      => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'small'      => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'code'      => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'pre'       => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'tr'        => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'td'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'th'        => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'thead'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'tfoot'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'style'     => array(
			'type'       => array(),
			'id'         => array(),
			'rel'        => array(),
			'media'      => array(),
			'href'       => array()
		),
		'ul'        => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'li'        => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'ol'         => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'img'         => array(
			'src'        => array(),
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'article'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'aside'       => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'header'      => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'nav'        => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'footer'     => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'section'    => array(
			'style'      => array(),
			'class'      => array(),
			'id'         => array()
		),
		'br'     => array(),
		'em'     => array(),
		'i'      => array(),
		'strong' => array(),
		'b'      => array(),
		'u'      => array(),
		'font'   => array()
	);

	/**
	 * Settings page tabs.
	 */
	public $settings_sections;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->settings_sections = array(
			array(
				'id' => 'mhca_options',
				'title' => __( 'General settings', 'mediahub' ),
			),
			array(
				'id' => 'mhca_articles',
				'title' => __( 'Post settings', 'mediahub' ),
			),
			array(
				'id' => 'mhca_api_key',
				'title' => __( 'API settings', 'mediahub' ),
			),
			array(
				'id' => 'mhca_help',
				'title' => __( 'Instructions', 'mediahub' ),
			)
		);

		$this->settings_api = new MH_Settings_API;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Add settings link to Plugins page
		$plugin_folder_exploded = explode( '/', dirname( dirname( __DIR__ ) ) );
		$plugin_folder = sanitize_title( $plugin_folder_exploded[count( $plugin_folder_exploded ) - 1] );
		add_filter( 'plugin_action_links_' . $plugin_folder . '/plugin.php', array( $this, 'plugin_add_settings_link' ) );

		// Register (de)activation hooks - these are currently overridden by the same hooks found in the plugin.php file
		$file = dirname( dirname( dirname( __FILE__ ) ) ) . '/plugin.php';
		register_activation_hook( $file, array( $this, 'mh_activation' ) );
		register_deactivation_hook( $file, array( $this, 'mh_deactivation' ) );

		// Add custom cron schedule
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( 'mh_event_hook', array( $this, 'get_or_update_notes' ) );

		// Allow for forcing the synchronisation to occur via a URL with query var ?mh_sync=xxx
		if ( isset( $_GET['mh_sync'] ) ) {
			add_action( 'init', array( $this, 'get_or_update_notes' ) );
		}

	}

	/**
	 * Initialize admin settings
	 *
	 * @return void
	 */
	public function admin_init() {

		// Creating new categories when selected on MediaHub settings page
		if ( isset( $_POST['mhca_options']['mediahub_cat2cat'] ) ) {
			$categories = $_POST['mhca_options']['mediahub_cat2cat'];
			foreach ( $categories as $key => $category ) {
				if ( ! is_numeric( $category ) ) {
					$category = explode( '|', $category['category'] );

					// If "new category" chosen in select box, then create it
					if ( 'new-mediahub-category' == $category[0] ) {
						$category_id = wp_create_category( $category[2] );
						$_POST['mhca_options']['mediahub_cat2cat'][$category[1]] = array( 'category' => $category_id );
					}

				}
			}
		}

		// If on admin page, then update posts (automatically run on WP Cron too)
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'mediahub_api' && isset( $_GET['update'] ) && $_GET['update'] == 'yes' ) {
			$this->get_or_update_notes();
		}

		// Implement the admin settings fields (uses a settings fields library)
		$this->settings_api->set_sections( $this->settings_sections );
		$this->settings_api->set_fields( $this->get_settings_fields() );
		$this->settings_api->admin_init();
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function admin_menu() {
		$options = get_option( 'mhca_options' );
		if ( isset( $options['mediahub_user_level'] ) && ( 'editor' == $options['mediahub_user_level'] ) ) {
			add_submenu_page( 'options-general.php', 'MediaHub API', 'MediaHub API', 'edit_pages', 'mediahub_api', array( $this, 'plugin_page' ) );
		} else {
			add_options_page( 'MediaHub API', 'MediaHub API', 'manage_options', 'mediahub_api', array( $this, 'plugin_page' ) );
		}
	}

	/**
	 * Plugin setting link
	 *
	 * @param array   $links Plugin link array
	 * @return array $links Amended plugin settings link
	 */
	public function plugin_add_settings_link( $links ) {
		$settings_link = '<a href="' . trailingslashit( admin_url() ) . 'options-general.php?page=mediahub_api">' . __( 'Settings', 'mediahub' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public function get_settings_fields() {

		// Get the categories
		$categories = $this->mediahub_request( $get = 'section/categories', $query = '' );
		$cat_array = null;

		if ( is_object( $categories ) && $categories->success == 1 && count( $categories->data ) > 0 ) {

			foreach ( $categories->data as $id => $object ) {
				$cat_options[ $object->id ] = $object->name;
			}

			// Set $cat_options if no data available yet)
			if ( ! isset( $cat_options ) ) {
				$cat_options = array();
			}

			$cat2cat_array = array(
				'name'    => 'mediahub_cat2cat',
				'label'   => stripslashes( __( 'Categories', 'mediahub' ) ),
				'type'    => 'cat2cat',
				'options' => $cat_options,
				'default' => 'no',
			);

			$update_array = array(
				'name'  => 'mediahub_request_posts',
				'label' => __( 'Update', 'mediahub' ),
				'desc'  => '<a href="options-general.php?page=mediahub_api&update=yes" class="button">' . __( 'Update', 'mediahub' ) . '</a> <p class="description">' . __( 'Click the update button to import manually.', 'mediahub' ) . '<br><strong>' . __( 'NOTE: this is not the save button!', 'mediahub' ) . '</strong></p>',
				'type'  => 'html'
			);
		}
		elseif ( isset( $categories->success ) && '' == $categories->success ) {
			$cat_array = array(
				'name'  => 'mediahub_categorieen',
				'label' => '<strong>' . __( 'NOTE:', 'mediahub' ) . '</strong>',
				'desc'  => '<strong>' . __( 'The MediaHub API is not returning data.', 'mediahub' ) . '</strong>',
				'type'  => 'html'
			);

			$cat2cat_array = array();

			$update_array = array(
				'name'  => 'mediahub_request_posts',
				'label' => '',
				'desc'  => '',
				'type'  => 'html'
			);
		}
		else {
			$cat_array = array(
				'name'  => 'mediahub_categorieen',
				'label' => '<strong>' . __( 'NOTE:', 'mediahub' ) . '</strong>',
				'desc'  => '<strong>' . __( 'First fill in all API settings correctly.', 'mediahub' ) . '</strong>',
				'type'  => 'html'
			);

			$cat2cat_array = array();

			$update_array = array(
				'name'  => 'mediahub_request_posts',
				'label' => '',
				'desc'  => '',
				'type'  => 'html'
			);
		}

		// Get newsletter groups from API
		$results = $this->mediahub_request( 'newsletters/groups', '', 'GET' );
		$api_groups = array();
		if ( isset( $results ) && isset( $results->data ) ) {
			$results = $results->data;
			if ( is_array( $results ) ) {
				foreach ( $results as $key => $result ) {
					$api_groups[$result->id] = $result->name;
				}
			}
		}

		$settings_fields['mhca_options'][] = $cat_array;
		$settings_fields['mhca_options'][] = $update_array;
		$settings_fields['mhca_options'][] = $cat2cat_array;

		$settings_fields['mhca_options'][] = array(
			'name'  => 'mediahub_api_group',
			'label' => __( 'API Group', 'mediahub' ),
			'desc'  => __( 'Choose the group to which subscribers must be added:', 'mediahub' ),
			'type'  => 'select',
			'default' => 'no',
			'options' => $api_groups,
		);

		$settings_fields['mhca_options'][] = array(
			'name'    => 'cron_interval',
			'label'   => __( 'Checking Interval', 'mediahub' ),
			'desc'    => __( 'Choose the time between checking for new posts', 'mediahub' ),
			'type'    => 'select',
			'default' => 'no',
			'options' => array(
				'minutes5'  => __( '5 minutes', 'mediahub' ),
				'minutes10' => __( '10 minutes', 'mediahub' ),
				'minutes15' => __( '15 minutes', 'mediahub' ),
			),
		);

		$settings_fields['mhca_articles'] = array(
			array(
				'name' => 'mediahub_articles_titles_heading',
				'label'   => __( 'Heading', 'mediahub' ),
				'desc'    => '<br>' . __( "Choose heading size. Choose 'No headings' to show no headings for any media type.", 'mediahub' ),
				'type'    => 'select',
				'default' => 'no',
				'options' => array(
					'no' => __( 'No headings', 'mediahub' ),
					'h2' => 'h2',
					'h3' => 'h3',
					'h4' => 'h4',
					'h5' => 'h5',
					'h6' => 'h6'
				)
			),
			array(
				'name'    => 'mediahub_articles_titles_youtube',
				'label'   => __( 'YouTube title', 'mediahub' ),
				'desc'    => '<br>' . sprintf( __( 'Title to be shown above %s embeds. Leave empty to show no title.', 'mediahub' ), __( 'YouTube', 'mediahub' ) ),
				'default' => 'YouTube videos',
				'type'    => 'text'
			),
			array(
				'name'    => 'mediahub_articles_titles_video',
				'label'   => __( 'Video title', 'mediahub' ),
				'desc'    => '<br>' . sprintf( __( 'Title to be shown above %s embeds. Leave empty to show no title.', 'mediahub' ), __( 'video', 'mediahub' ) ),
				'default' => 'Videos',
				'type'    => 'text'
			),
			array(
				'name'    => 'mediahub_articles_titles_audio',
				'label'   => __( 'Audio title', 'mediahub' ),
				'desc'    => '<br>' . sprintf( __( 'Title to be shown above %s embeds. Leave empty to show no title.', 'mediahub' ), __( 'audio', 'mediahub' ) ),
				'default' => 'Audio',
				'type'    => 'text'
			),
			array(
				'name'    => 'mediahub_articles_titles_photo',
				'label'   => __( 'Photo title', 'mediahub' ),
				'desc'    => '<br>' . sprintf( __( 'Title to be shown above %s embeds. Leave empty to show no title.', 'mediahub' ), __( 'photo', 'mediahub' ) ),
				'default' => 'Photos',
				'type'    => 'text'
			),
			array(
				'name'    => 'mediahub_articles_titles_gallery',
				'label'   => __( 'Gallery title', 'mediahub' ),
				'desc'    => '<br>' . sprintf( __( 'Title to be shown above %s embeds. Leave empty to show no title.', 'mediahub' ), __( 'gallery', 'mediahub' ) ),
				'default' => 'Gallery',
				'type'    => 'text'
			),
		);

		$settings_fields['mhca_api_key'] = array(
			array(
				'name'  => 'mediahub_api_url',
				'label' => __( 'API URL', 'mediahub' ),
				'type'  => 'text'
			),
			array(
				'name'  => 'mediahub_api_openkey',
				'label' => __( 'API Open Key', 'mediahub' ),
				'type'  => 'text'
			),
			array(
				'name'  => 'mediahub_api_secretkey',
				'label' => __( 'API Secret Key', 'mediahub' ),
				'type'  => 'text'
			),
			array(
				'name'  => 'mediahub_environment_id',
				'label' => __( 'Environment ID', 'mediahub' ),
				'type'  => 'text'
			),

		);

		$help_desc = __( '<p>Below you see all the themes from the MediaHub<br>Check the desired categories and save the changes with the blue button.<br>Then you can retrieve the messages manually. The first time you do this may take a long time because of the amount of messages that has to be retrieved.</p><p>You only have to manually update the first time and / or optionally if you add a new category.<br>New messages are automatically retrieved after setting up.</p>', 'mediahub' );

		$help_algemeen = __( '<p>If you have correctly filled in the API settings you will see a list of categories to choose from.<br> Check the categories you want and save with the button below. <br> After that you can import manually. The first time you do the import it can be slow due to the amount of items that needs to be imported.</p><p>You only have to manually import once and/or optionally if a new category is added.<br> New items items will be imported at the set interval.</p>', 'mediahub' );

		$settings_fields['mhca_help'] = array(
			array(
				'name' => 'mediahub_help_algemeen',
				'label' => '<strong>' . __( 'General settings', 'mediahub' ) . '</strong>',
				'desc' => $help_algemeen,
				'type' => 'html'
			),
			array(
				'name' => 'mediahub_help_api',
				'label' => '<strong>' . __( 'API settings', 'mediahub' ) . '</strong>',
				'desc' => $help_desc,
				'type' => 'html'
			),
			array(
				'name' => 'mediahub_help_cat2cat',
				'label' => __( 'How to', 'mediahub' ),
				'desc' => sprintf( __( 'Choose a WordPress category from the selection to convert it to the MediaHub category.%1$s The MediaHub category is shown at the right (after the media icons).%1$s%1$s The media icons can be selected to only show:%1$s %2$s article%1$s %3$s video%1$s %4$s audio%1$s If none are selected everything is shown.%1$s Multiple selections possible.', 'mediahub' ), '<br>', '<span class="dashicons dashicons-media-text"></span>', '<span class="dashicons dashicons-video-alt3"></span>', '<span class="dashicons dashicons-format-audio"></span>' ),
				'type' => 'html'
			)
		);

		$settings_fields['mhca_options'][] = array(
			'name'  => 'mediahub_user_level',
			'label' => __( 'User level', 'mediahub' ),
			'desc'  => __( 'The user level required for editing settings page:', 'mediahub' ),
			'type'  => 'select',
			'default' => 'administrator',
			'options' => array(
				'administrator' => __( 'Administrator', 'mediahub' ),
				'editor'        => __( 'Editor', 'mediahub' ),
			),
		);

		return $settings_fields;
	}

	/**
	 * Display settings page
	 *
	 * @return string Settings page html
	 */
	function plugin_page() {
		echo '<div class="wrap">';

		$this->settings_api->show_navigation();
		$this->settings_api->show_forms();

		echo '</div>';
	}

	/**
	 * Adds 5, 10 and 15 minute cron schedules.
	 *
	 * @param array   $schedules Cron schedule array
	 * @return array $schedules Amended cron schedule array
	 */
	public function cron_schedules( $schedules ) {

		$schedules['minutes5'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 minutes' )
		);
		$schedules['minutes10'] = array(
			'interval' => 600,
			'display'  => __( 'Every 10 minutes' )
		);
		$schedules['minutes15'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 minutes' )
		);
		return $schedules;
	}

	/**
	 * On activation, set a time, frequency and name of an action hook to be scheduled.
	 *
	 * @return void
	 */
	public function mh_activation() {

		// first run = Now + 15 minutes
		$first_run_time = current_time ( 'timestamp' ) + 900;
		wp_schedule_event( $first_run_time, 'minutes15', 'mh_event_hook' );
	}

	/**
	 * On deactivation, remove all functions from the scheduled action hook.
	 *
	 * @return void
	 */
	public function mh_deactivation() {
		wp_clear_scheduled_hook( 'mh_event_hook' );
	}

	/**
	 * Gets the posts from Mediahub API, inserts/updates posts and media
	 *
	 * @return void
	 */
	public function get_or_update_notes() {

		// Get cron time
		$data = get_option( 'mhca_options' );
		$cron_interval = $data['cron_interval'];
		switch ( $cron_interval ) {
			case 'minutes15':
				$cron_time = MINUTE_IN_SECONDS * 15;
			case 'minutes10':
				$cron_time = MINUTE_IN_SECONDS * 10;
			case 'minutes5':
				$cron_time = MINUTE_IN_SECONDS * 5;
		}

		// Check if sync'ing individual post at the moment
		if ( isset( $_GET['mh_sync'] ) ) {
			$id = absint( $_GET['mh_sync'] );

			// Bail out if task running
			if ( true == get_transient( 'mediahub_active_' . $id ) ) {
				return;
			}

			set_transient( 'mediahub_active_' . $id, true, $cron_time );
		} elseif ( true == get_transient( 'mediahub_cron_active' ) ) {
			return; // Bail out if cron alredy running
		}

		set_transient( 'mediahub_cron_active', true, $cron_time );

		// Get Mediahub options
		if ( $options = get_option( 'mhca_options', false ) ) {

			global $wpdb;

			// Load the necessary files for side loading when using cron
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/taxonomy.php';

			// Set an unlimited timeout (avoids timeouts due to excessively long processing times)
			set_time_limit( 0 );

			// Get mapped categories, array is as this: ['mediahub_cat2cat'][ {mediahub-term-id} ]['category'] = {wp-category-id}
			$categories = $options['mediahub_cat2cat'];

			// Get all mediahub category items (articles, video, audio) and check for existing matches in the WP DB
			if ( isset( $categories ) ) {

				$existing_matches       = array();
				$mh_combined_item_array = array();

				foreach ( $categories as $term_id => $mapping ) {

					// If not set to true (1) then continue, as we aren't meant to be processing that category right now
					if ( 0 == $mapping['category'] ) {
						continue;
					}

					// Set the basis for which items to import. Default is all.
					$import_media = array( 'all' => true );

					// If article is selected, import article
					if ( isset( $mapping['import_article'] ) ) {
						$import_media['article'] = true;
						unset( $import_media['all'] );
					}

					// If video is selected, import video
					if ( isset( $mapping['import_video'] ) ) {
						$import_media['video'] = true;
						unset( $import_media['all'] );
					}

					// If audio is selected, import audio
					if ( isset( $mapping['import_audio'] ) ) {
						$import_media['audio'] = true;
						unset( $import_media['all'] );
					}

					// import all
					if ( isset( $import_media['all'] ) ) {
						$mh_combined_item_array[] = $this->mediahub_request( $get = 'articles', $query = 'status=1&section_categories=' . $term_id . '&' );
						$mh_combined_item_array[] = $this->mediahub_request( $get = 'videos', $query = 'status=1&section_categories=' . $term_id . '&' );
						$mh_combined_item_array[] = $this->mediahub_request( $get = 'audios', $query = 'status=1&section_categories=' . $term_id . '&' );
					}

					// import articles
					if ( isset( $import_media['article'] ) ) {
						$mh_combined_item_array[] = $this->mediahub_request( $get = 'articles', $query = 'status=1&section_categories=' . $term_id . '&' );
					}

					// import videos
					if ( isset( $import_media['video'] ) ) {
						$mh_combined_item_array[] = $this->mediahub_request( $get = 'videos', $query = 'status=1&section_categories=' . $term_id . '&' );
					}

					// import audios
					if ( isset( $import_media['audio'] ) ) {
						$mh_combined_item_array[] = $this->mediahub_request( $get = 'audios', $query = 'status=1&section_categories=' . $term_id . '&' );
					}

					// get item IDs
					$match_note_ids = array();
					foreach ( $mh_combined_item_array as $items ) {
						if ( isset( $items->data ) ) {
							foreach ( $items->data as $key => $item ) {
								$match_note_ids[] = $item->id;
							}
						}
					}

					// Get existing matching note_ids and post IDs by note_ids
					if ( $post_exists = $wpdb->get_results( "SELECT $wpdb->postmeta.meta_value, $wpdb->posts.ID FROM $wpdb->posts INNER JOIN $wpdb->postmeta ON ( $wpdb->posts.ID = $wpdb->postmeta.post_id ) WHERE 1=1  AND (( $wpdb->postmeta.meta_key = '" . self::META_KEY . "' AND CAST($wpdb->postmeta.meta_value AS CHAR) IN (" . implode( ",", $match_note_ids ) . ") )) AND $wpdb->posts.post_type = 'post'", OBJECT_K ) ) {
						foreach ( $post_exists as $key => $value ) {
							// resulting array is array( note_id => ID )
							$existing_matches[$key] = $post_exists[$key]->ID;
						}
					}
				}

				$post_exists = $existing_matches;

				// convert multidimensional mixed array/objects to array
				$mh_combined_item_array = json_decode( json_encode( $mh_combined_item_array ), true );

				// combine results of different themes/categories
				$new_array = array();
				foreach ( $mh_combined_item_array as $key => $value ) {
					foreach ( $value['data'] as $data_key => $data_value ) {
						$new_array[] = $data_value;
					}
				}

				// deduplicate
				$mh_combined_item_array = array_map( 'unserialize', array_unique( array_map( 'serialize', $new_array ) ) );
				unset( $new_array );

				// loop through each item
				foreach ( $mh_combined_item_array as $item ) {

					// Only run all if not trying to sync a single ID
					if ( ! isset( $_GET['mh_sync'] ) || $item['id'] == $_GET['mh_sync'] ) {

						// Work out which status to apply
						if ( 'published' == $item['status']['name'] ) {
							$status = 'publish'; // MediaHub uses different terminology for the status of "publish"
						} elseif ( 'pending' == $item['status']['name'] ) {
							$status = 'publish'; // Pending within MediaHub refers to scheduled posts within WordPress, so we just switch them to use the status of "publish" instead
						} else {
							$status = 'reject'; // If no suitable status found, then just reject it outright
						}

						if ( 'reject' != $status ) {

							// Sanitize data inputs
							$title        = wp_kses_post( $item['revision']['title'] );
							$content      = wp_kses( $item['revision']['content'], $this->allowed_html, '' );
							$excerpt      = wp_kses_post( $item['revision']['excerpt'] );
							$slug         = sanitize_title( $item['revision']['slug'] ); // Original code with end point ... $title . '-' . $item['id']
							$created_on   = absint( $item['revision']['created_on'] );
							$published_on = absint( strtotime( $item['published_on'] ) );
							$username     = sanitize_title( $item['revision']['created_by']['firstname'] . ' ' . $item['revision']['created_by']['prefix_lastname'] . ' ' . $item['revision']['created_by']['lastname'] );

							// Get or create user ID
							if ( ! username_exists( $username ) ) {
								$users = $this->mediahub_request( $get = 'users/' . $item['revision']['created_by']['id'], $query = '&' );
								if ( isset( $users->data->email[0]->email ) ) {
									$email = $users->data->email[0]->email;
								} else {
									$email = '';
								}
								$password = wp_generate_password( 20, false );
								$user_id = wp_create_user( $username, $password, $email );
								$args = array(
									'ID'           => $user_id,
									'first_name'   => $item['revision']['created_by']['firstname'],
									'last_name'    => $last_name = $item['revision']['created_by']['prefix_lastname'] . ' ' . $item['revision']['created_by']['lastname'],
									'display_name' => $item['revision']['created_by']['firstname'] . ' ' . $last_name,
									'nickname'     => $item['revision']['created_by']['firstname'] . ' ' . $last_name,
								);
								wp_update_user( $args );
							} else {
								$user_id = username_exists( $username );
							}

							// If no post is found
							if ( ! isset( $post_exists[$item['id']] ) ) {

								// Insert new post
								$post = array(
									'post_name'     => $slug,
									'post_title'    => $title,
									'post_date'     => date( 'Y-m-d H:i:s', $published_on ),
									'post_content'  => '',
									'post_status'   => $status,
									'post_author'   => $user_id
								);

								if ( ! defined( 'MEDIAHUB_TEST' ) ) {
									$post_id = wp_insert_post( $post );
								}

								// Add the post content
								$this->add_post_content( $item, $post_id, $options, $excerpt, $content, $slug, $title );
							}

							// If post already exists ...
							if ( isset( $post_exists[$item['id']] ) ) {

								// set $post_id
								$post_id = $post_exists[$item['id']];

								// If depublish time has passed, then delete the post
								if ( '' != $item['depublished_on'] ) {
									$depublished_on = absint( strtotime( $item['depublished_on'] ) );
									if ( $depublished_on < time() ) {
										$this->delete_associated_media( $post_id );
										wp_delete_post( $post_id, true );
									}
								}

								// If new revision, then update it's content
								if ( $item['revision']['id'] != get_post_meta( $post_id, 'mediahub_revision_id', true ) ) {

									// Add meta data
									delete_post_meta( $post_id, self::META_KEY );
									delete_post_meta( $post_id, 'mediahub_date_created' );
									delete_post_meta( $post_id, 'mediahub_date_changed' );
									delete_post_meta( $post_id, 'mediahub_date_published' );
									delete_post_meta( $post_id, 'mediahub_revision_id' );
									delete_post_meta( $post_id, '_mediahub_structure' );

									// Deleting existing attachments
									$this->delete_associated_media( $post_id );
									$attachments = get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $post_id );
									foreach ( $attachments as $attachment_id => $image ) {
										wp_delete_attachment( $attachment_id, true );
									}

									// Add the post content
									$this->add_post_content( $item, $post_id, $options, $excerpt, $content, $slug, $title );
								}

								unset( $post_id );
							}

							unset( $status );
						}
					}
				}
			}
		}

		// Sync is complete, so we delete the transient to ensure we can run another sync next time
		if ( isset( $_GET['mh_sync'] ) ) {
			$id = absint( $_GET['mh_sync'] );
			delete_transient( 'mediahub_active_' . $id );
			return;
		} else {
			delete_transient( 'mediahub_cron_active' );
			wp_redirect( admin_url() . 'options-general.php?page=mediahub_api', 302 );
		}

	}

	/**
	 * Embedding media files.
	 *
	 * @param object  $item The item object
	 * @param bool    $titles  Whether to display titles or not (typically only used on audio)
	 * @return  string  $html         The HTML to be added to the post content
	 */
	public function video_audio_embed( $item, $post_id ) {

		$html                    = '';
		$mh_media_types          = array( 'youtube', 'video', 'audio' );
		$media_post_meta         = array();
		$media_post_meta_counter = 0;

		// structure 4 = audio
		// structure 5 = video
		if ( $item['structure'] == 5 ) {
			$url = 'https://player.demediahub.nl/video/' . $item['id'] . '/' . $item['revision']['slug'];
			$media_post_meta['video'][0] = $url;
			update_post_meta( $post_id, $meta_key = '_mediahub_media_types', $meta_value = $media_post_meta );
			update_post_meta( $post_id, $meta_key = '_mediahub_media_type', $meta_value = 'video' );
			return "\n\n[mh_media type=video]";
		}
		if ( $item['structure'] == 4 ) {
			$url = 'https://player.demediahub.nl/audio/' . $item['id'] . '/' . $item['revision']['slug'];
			$media_post_meta['audio'][0] = $url;
			update_post_meta( $post_id, $meta_key = '_mediahub_media_types', $meta_value = $media_post_meta );
			update_post_meta( $post_id, $meta_key = '_mediahub_media_type', $meta_value = 'audio' );
			return "\n\n[mh_media type=audio]";
		}

		foreach ( $mh_media_types as $mh_media_type ) {

			if ( isset( $item['media'][$mh_media_type] ) ) {
				$media = $item['media'][$mh_media_type];

				$html .= "\n\n";

				foreach ( $media as $key => $media_item ) {

					// Set the first video image as featured image
					if ( !isset( $this->featured_media_image ) && isset( $media_item['file']['hd']['thumbnail'] ) ) {
						$this->featured_media_image = $media_item['file']['hd']['thumbnail'];
					}

					// Cater for different media types
					if ( 'youtube' == $mh_media_type ) {
						$url = str_replace( 'youtube.com/watch?v=', 'youtube.com/embed/', $media_item['file'] );
						$media_post_meta['youtube'][$media_post_meta_counter] = $url;
					}
					if ( 'video' == $mh_media_type ) {
						$url = 'https://player.demediahub.nl/video/' . $media_item['id'] . '/' . $media_item['revision']['slug'];
						$media_post_meta['video'][$media_post_meta_counter] = $url;
					}
					if ( 'audio' == $mh_media_type ) {
						$url = 'https://player.demediahub.nl/audio/' . $media_item['id'] . '/' . $media_item['revision']['slug'];
						$media_post_meta['audio'][$media_post_meta_counter] = $url;
					}

					$media_post_meta_counter++;
					unset( $url );
				}

				$html .= '[mh_media type=' . $mh_media_type . ']';
			}
		}

		update_post_meta( $post_id, $meta_key = '_mediahub_media_types', $meta_value = $media_post_meta );

		return $html;
	}

	/**
	 * Adds the post content, featured images and some post meta.
	 * There are a large number of parameters due to a late change in the code base to accommodate updating post content.
	 *
	 * @param object  $item
	 * @param int     $post_id
	 * @param array   $categories
	 * @param string  $excerpt
	 * @param string  $content
	 * @param string  $slug
	 * @param string  $title
	 */
	public function add_post_content( $item, $post_id, $categories, $excerpt, $content, $slug, $title ) {

		// Add meta data
		add_post_meta( $post_id, self::META_KEY,            absint( $item['id'] ), true );
		add_post_meta( $post_id, 'mediahub_date_created',   absint( $item['revision']['created_on'] ), true );
		add_post_meta( $post_id, 'mediahub_date_changed',   absint( $item['revision']['changed_on'] ), true );
		add_post_meta( $post_id, 'mediahub_date_published', absint( $item['published_on'] ), true );
		add_post_meta( $post_id, 'mediahub_revision_id',    absint ( $item['revision']['id'] ), true );
		add_post_meta( $post_id, '_mediahub_structure',     absint ( $item['structure'] ), true );

		// Alter content and/or excerpt depending if or which are empty
		if ( $excerpt != '' && $content != '' ) {
			$content = $excerpt . "\n<!--more-->\n" . $content;
		} elseif ( $excerpt == '' && $content != '' ) {
			$content = $content;
		} elseif ( $excerpt != '' && $content == '' ) {
			$content = $excerpt;
		}

		// Embed video and audio galleries
		$content .= $this->video_audio_embed( $item, $post_id );

		// Compare ID with event ID, if true add mediahub_event meta
		if ( isset( $item['event']['started_on'] ) ) {
			update_post_meta( $post_id, 'mediahub_event_started_on', strtotime( $item['event']['started_on'] ) );
		}
		if ( isset( $item['event']['started_on'] ) ) {
			update_post_meta( $post_id, 'mediahub_event_ended_on', strtotime( $item['event']['started_on'] ) );
		}


		// Loop over all item categories and set the post category to the mapped category if the ID is anything but 0
		$category_ids_to_set = array();
		foreach ( $item['metadata']['category'] as $key => $category ) {
			if ( 0 != $categories['mediahub_cat2cat'][$category['id']]['category'] ) {
				$category_ids_to_set[] = $categories['mediahub_cat2cat'][$category['id']]['category'];
			}
		}
		wp_set_post_categories( $post_id, $category_ids_to_set );


		// Post tags
		$terms = array();

		if ( isset( $item['metadata']['keyword'] ) ) {
			$keywords = $item['metadata']['keyword'];
			foreach ( $keywords as $keyword_id => $keyword ) {
				$terms[] = strtolower( $keyword['name'] ); // Add keywords to post -tags
			}
		}

		if ( isset( $item['metadata']['person'] ) ) {
			$persons = $item['metadata']['person'];
			foreach ( $persons as $person_id => $person ) {
				$terms[] = strtolower( $person['name'] ); // Add person to post -tags
			}
		}

		if ( isset( $item['metadata']['organisation'] ) ) {
			$organisations = $item['metadata']['organisation'];
			foreach ( $organisations as $organisation_id => $organisation ) {
				$terms[] = strtolower( $organisation['name'] ); // Add organisation to post -tags
			}
		}

		wp_set_post_terms( $post_id, $terms, 'post_tag', $append = false ); // Finally adding post tags

		// Add photos gallery to content (separate from the mediatags galleries added later on)
		if ( isset( $item['media']['photo'] ) && count( $item['media']['photo'] ) > 1 ) {

			$media = $item['media']['photo'];

			// create gallery array
			$gallery_images = array();

			$gallery_image_counter = 0;

			foreach ( $media as $key => $media_item ) {
				$files = $media_item['file']; // Grab all available files (various sizes of same image)

				foreach ( $files as $size => $file ) {

					if ( 300 == $size ) {
						$gallery_images[$gallery_image_counter]['thumbnail'] = $file['file'];
					}

					if ( 1920 == $size ) {
						$gallery_images[$gallery_image_counter]['hd'] = $file['file'];

						if ( ! isset( $this->single_photo_image ) ) {
							$this->single_photo_image = $file['file'];
						}
					}
				}
				$gallery_image_counter++;
			}

			update_post_meta( $post_id, $meta_key = '_mediahub_gallery_post_images', $gallery_images );

			// If there are items, then add gallery
			if ( count( $gallery_images ) > 0 ) {
				$content .= "\n\n";
				$content .= '[gallery columns="2" type="mediahub_gallery" gallery_type="post_images"]';
			}
		}

		// Create gallery - using mediatags (separate from the photo album added via the direct API call)
		if ( isset( $item['media']['mediatag'] ) ) {
			$mediatag = $item['media']['mediatag'];

			// create gallery array
			$gallery_images = array();

			$gallery_image_counter = 0;

			foreach ( $mediatag as $mediatag_key => $mediatag_value ) {

				if ( isset( $mediatag_value['id'] ) ) {
					$gallery = $this->mediahub_request( $get = 'section/mediatags/' . $mediatag_value['id'], $query = '&' );
					$gallery = $gallery->data->media->photo;

					foreach ( $gallery as $gallery_key => $gallery_value ) {
						$files = $gallery_value->file;

						foreach ( $files as $size => $file ) {

							if ( 300 == $size ) {
								$gallery_images[$gallery_image_counter]['thumbnail'] = $file->file;
							}

							if ( 1920 == $size ) {
								$gallery_images[$gallery_image_counter]['hd'] = $file->file;
							}
						}
						$gallery_image_counter++;
					}
				}
			}

			// If there are items, then add gallery
			if ( count( $gallery_images ) > 1 ) {

				$content .= "\n\n";
				$content .= '[gallery columns="2" type="mediahub_gallery" gallery_type="gallery_images"]';
			}

			update_post_meta( $post_id, $meta_key = '_mediahub_gallery_gallery_images', $gallery_images );
			unset( $gallery_images );
		}

		// Add document lists
		if ( isset( $item['media']['document'] ) ) {
			$content .= "\n\n<ul id='document-list'>\n";

			foreach ( $item['media']['document'] as $doc_key => $doc_data ) {
				$content .= '<li><a href="' . esc_url( $doc_data['file']['file'] ) . '">' . wp_kses_post( $doc_data['revision']['title'] ) . "</a></li>\n";
			}
			$content .= "<ul>";

		}

		// Add Google Maps
		if ( isset( $item['location'] ) ) {
			if ( isset( $item['location']['latitude'] ) && isset( $item['location']['longitude'] ) ) {
				$latitude = $item['location']['latitude'];
				$longitude = $item['location']['longitude'];
				$url = 'https://www.google.com/maps/embed/v1/place?q=' . $latitude . ',+' . $longitude . '&key=' . self::GOOGLE_MAPS_KEY;

				$content .= "\n\n";
				$content .= '<iframe width="600" height="450" frameborder="0" style="border:0" src="' . esc_url( $url ) . '"></iframe>';
			}
		}

		/*
		 * Set the Featured Image
		 */

		// Featured image
		if ( isset( $item['media']['primary']['structure'] ) && isset( $item['media']['primary']['file'] ) && is_array( $item['media']['primary']['file'] ) ) {

			foreach ( $item['media']['primary']['file'] as $key => $featured_image_block ) {
				if ( 1920 == $key && is_string( $featured_image_block['file'] ) ) {
					$featured_image = $featured_image_block['file'];
				}
			}
		}

		// Featured image for video posts
		if ( ! isset( $featured_image ) && isset( $item['structure'] ) && $item['structure'] == 5 && isset( $item['file']['hd']['thumbnail'] ) && is_string( $item['file']['hd']['thumbnail'] ) ) {
			$featured_image = $item['file']['hd']['thumbnail'];
		}

		// Sideload the featured image
		if ( isset( $featured_image ) && strpos( $featured_image, '.jpg' ) !== false ) {
			$this->sideloadFeaturedImage( $featured_image, $post_id, $title );
		}
		unset( $featured_image );

		// Update post with new gallery shortcode(s) added
		$post_update = array(
			'ID'           => $post_id,
			'post_title'   => $title,
			'post_content' => $content,
		);
		wp_update_post( $post_update );

	}

}

$settings = new MediaHub_Content_API;
