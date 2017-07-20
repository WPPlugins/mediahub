<?php
/*
Plugin Name: MediaHub Content API
Plugin URI: http://forsitemedia.nl/
Description: Haalt content op uit MediaHub.
Author: ForSite Media, Daan Kortenbach
Version: 1.1.3
Author URI: http://forsitemedia.nl/
License: GPLv2
*/

/*  Copyright 2013  Daan Kortenbach  (email : daan@forsitemedia.nl)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Require settings api class
require_once 'lib/class.settings-api.php';

// Load the necessary files for side loading when using cron
require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

class MediaHub_Content_API {

	private $settings_api;

	/**
	 * Construct
	 */
	function __construct() {
		$this->settings_api = new WeDevs_Settings_API;

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

		// Add settings link to Plugins page
		add_filter( 'plugin_action_links_mediahub/plugin.php', array( $this, 'plugin_add_settings_link' ) );

		// Register (de)activation hooks
		register_activation_hook( __FILE__, array( $this, 'mh_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'mh_deactivation' ) );

		// Add custom cron schedule
		add_filter( 'cron_schedules', array( $this, 'mh_cron_15' ) );
		add_action( 'mh_15minutes_event_hook', array( $this, 'mh_get_or_update_notes' ) );

		// Agenda shortcode
		add_shortcode( 'mediahub_agenda', array( $this, 'mediahub_agenda_shortcode' ) );
	}

	/**
	 * Initialize admin settings
	 *
	 * @return void
	 */
	function admin_init() {

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'mediahub_api' && isset( $_GET['update'] ) && $_GET['update'] == 'yes' )
			$this->mh_get_or_update_notes();


		// Manage category options
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'mediahub_api' && isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true ) {

			// Get 'mhca_options' options
			$mhca_options = get_option( 'mhca_options' );

			if ( isset( $mhca_options['mediahub_cat2cat'] ) ) {

				// Clean 'mediahub_categorieen'
				unset( $mhca_options['mediahub_categorieen'] );

				foreach ( $mhca_options['mediahub_cat2cat'] as $key => $value ) {

					if ( strpos( $value, 'new-mediahub-category' ) !== false ) {

						$cat_name = explode( '|', $value );

						// Insert category
						$result = wp_insert_term( strtolower( $cat_name[2] ), 'category', array( 'slug' => sanitize_title( $cat_name[2] ) ) );

						if ( is_wp_error( $result ) )
							$cat_id = $result->error_data['term_exists'];

						if ( isset( $result['term_id'] ) )
							$cat_id = $result['term_id'];

						// Set category id
						$mhca_options['mediahub_cat2cat'][ $key ] = $cat_id;
					}

					// Set 'mediahub_categorieen' options
					if ( $mhca_options['mediahub_cat2cat'][ $key ] != 0 )
						$mhca_options['mediahub_categorieen'][ $key ] = $key;
				}

				update_option( 'mhca_options', $mhca_options );
			}
		}


		// Set the settings
		$this->settings_api->set_sections( $this->get_settings_sections() );
		$this->settings_api->set_fields( $this->get_settings_fields() );

		// Initialize settings
		$this->settings_api->admin_init();
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	function admin_menu() {
		add_options_page( 'MediaHub API', 'MediaHub API', 'delete_posts', 'mediahub_api', array( $this, 'plugin_page' ) );
	}

	/**
	 * Plugin setting link
	 *
	 * @param  array $links Plugin link array
	 * @return array $links Amended plugin settings link
	 */
	function plugin_add_settings_link( $links ) {
		$settings_link = '<a href="' . trailingslashit( admin_url() ) . 'options-general.php?page=mediahub_api">' . __( 'Settings', 'mediahub' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Settings page tabs
	 *
	 * @return array $settings Settings page tabs
	 */
	function get_settings_sections() {
		$sections = array(
			array(
				'id' => 'mhca_options',
				'title' => __( 'Algemene instellingen', 'mediahub' )
			),
			array(
				'id' => 'mhca_api_key',
				'title' => __( 'API instellingen', 'mediahub' )
			),
			array(
				'id' => 'mhca_help',
				'title' => __( 'Handleiding', 'mediahub' )
			)
		);
		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	function get_settings_fields() {

		$settings_fields['mhca_options'] = array(
			array(
				'name' => 'mediahub_howto',
				'label' => __( '', 'mediahub' ),
				'desc' => __( 'Kies op deze pagina de weer te geven categorieen.', 'mediahub' ),
				'type' => 'html'
			)
		);

		// Get the categories
		$mh_categories = $this->mediahub_get( $get = '/terms.json', $query = '&structure=1&limit=1000' );

		$cat_array = null;

		if ( is_object( $mh_categories ) && $mh_categories->success == 1 && count( $mh_categories->data ) > 0 ){

			foreach ( $mh_categories->data as $id => $object ) {
				$cat_options[ $object->id ] = $object->name;
			}

			// Set $cat_options if no data available yet)
			if ( ! isset( $cat_options ) ) {
				$cat_options = array();
			}

			$cat2cat_array = array(
				'name' => 'mediahub_cat2cat',
				'label' => __( 'Categorieen', 'mediahub' ),
				'type' => 'cat2cat',
				'options' => $cat_options,
				'default' => 'no',
			);

			$update_array = array(
				'name' => 'mediahub_get_posts',
				'label' => __( 'Haal berichten handmatig op', 'mediahub' ),
				'desc' => __( '<a href="options-general.php?page=mediahub_api&update=yes" class="button">Update</a> <p class="description">Klik op de update button om berichten handmatig op te halen. <br><strong>LET OP: dit is niet de save knop!</strong></p>', 'mediahub' ),
				'type' => 'html'
			);
		}
		else {
			$cat_array = array(
				'name' => 'mediahub_categorieen',
				'label' => __( '<strong>LET OP:</strong>', 'mediahub' ),
				'desc' => __( '<strong>Vul eerst alle API instellingen correct in.</strong>', 'mediahub' ),
				'type' => 'html'
			);

			$cat2cat_array = array();

			$update_array = array(
				'name' => 'mediahub_get_posts',
				'label' => '',
				'desc' => '',
				'type' => 'html'
			);
		}


		$settings_fields['mhca_options'][] = $update_array;
		$settings_fields['mhca_options'][] = $cat2cat_array;


		$settings_fields['mhca_api_key'] = array(
			array(
				'name' => 'mediahub_api_url',
				'label' => __( 'API Url', 'mediahub' ),
				'type' => 'text'
			),
			array(
				'name' => 'mediahub_api_openkey',
				'label' => __( 'API Open Key', 'mediahub' ),
				'type' => 'text'
			),
			array(
				'name' => 'mediahub_api_secretkey',
				'label' => __( 'API Secret Key', 'mediahub' ),
				'type' => 'text'
			),
			array(
				'name' => 'mediahub_environment_id',
				'label' => __( 'Environment ID', 'mediahub' ),
				'type' => 'text'
			)
		);

		$help_desc = '<p>Hier dien je de API gegevens in te vullen. De volgende gegevens zijn nodig:</p><p>- Url<br>- Open key<br>- Secret key<br>- Environment ID</p><p>Vraag deze gegevens op bij je Mediahub contact.</p>';

		$help_algemeen = '<p>Wanneer je de API gegevens correct ingevuld hebt zie je hier een lijst met categorien die je kan kiezen.<br>
		Vink de gewenste categorien aan en sla daarna de wijziging op met de blauwe knop.<br>
		Hierna kan je de berichten handmatig ophalen. De eerste keer dat je dit doet kan het lang duren vanwege de hoeveelheid berichten die opgehaald moeten worden.</p>
		<p>Je hoeft het handmatig updaten alleen de eerste keer te doen en/of optioneel als je een nieuwe categorie toevoegd.<br>
		Nieuwe berichten worden na het instellen automatisch elke 15 minuten opgehaald.</p>';

		$settings_fields['mhca_help'] = array(
			array(
				'name' => 'mediahub_help_algemeen',
				'label' => __( '<strong>Algemene instellingen</strong>', 'mediahub' ),
				'desc' => $help_algemeen,
				'type' => 'html'
			),
			array(
				'name' => 'mediahub_help_api',
				'label' => __( '<strong>API Instellingen</strong>', 'mediahub' ),
				'desc' => $help_desc,
				'type' => 'html'
			)
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
	 * Get all the pages
	 *
	 * @return array page names with key value pairs
	 */
	function get_pages() {
		$pages = get_pages();
		$pages_options = array();
		if ( $pages ) {
			foreach ( $pages as $page ) {
				$pages_options[$page->ID] = $page->post_title;
			}
		}

		return $pages_options;
	}

	/**
	 * This is the function that gets the content from the API
	 *
	 * @param  string $get JSON to call
	 * @param  string $query Optional Query to get
	 * @return object Result
	 */
	private function mediahub_get( $get, $query = '' ) {

		$api_settings = get_option( 'mhca_api_key', false );

		// Set authorization key
		if ( isset( $api_settings['mediahub_api_openkey'] ) ) {
			$auth_key = $api_settings['mediahub_api_openkey'];
		} else {
			$auth_key = '';
		}

		// Set secret key
		if ( isset( $api_settings['mediahub_api_secretkey'] ) ) {
			$secret = $api_settings['mediahub_api_secretkey'];
		} else {
			$secret = '';
		}

		// Set API URL
		if ( isset( $api_settings['mediahub_api_url'] ) ) {
			$api_url = $api_settings['mediahub_api_url'];
		} else {
			$api_url = '';
		}

		// Set request URL
		if ( isset( $api_settings['mediahub_environment_id'] ) ) {
			$request_url  = '/environments/' . $api_settings['mediahub_environment_id'] . $get;
		} else {
			$request_url = '';
		}

		$time = time();

		$nonce = md5( microtime() . mt_rand() );

		$authHash = hash_hmac( "sha512", $request_url . $time . $nonce, $secret );

		$auth = '&auth_key=' . $auth_key . '&auth_hash=' . $authHash . '&auth_nonce=' . $nonce . '&auth_time=' . $time;

		$url = $api_url . $request_url . '?' . $query . $auth;

		$result = wp_remote_get( $url, array( 'timeout' => 120, 'httpversion' => '1.1' ) );

		if ( is_wp_error( $result ) ) {
			$result->success = 0;
			return $result;
		}

		$result = $result['body'];
		return json_decode( $result );
	}

	/**
	 * Adds a 15 minute cron schedule
	 *
	 * @param array $schedules Cron schedule array
	 * @return array $schedules Amended cron schedule array
	 */
	function mh_cron_15( $schedules ) {

		$schedules['minutes15'] = array(
			'interval' => 900,
			'display' => __('Every 15 minutes')
		);
		return $schedules;
	}

	/**
	 * On activation, set a time, frequency and name of an action hook to be scheduled.
	 *
	 * @return void
	 */
	function mh_activation() {
		wp_schedule_event( current_time ( 'timestamp' ), 'minutes15', 'mh_15minutes_event_hook' );
	}

	/**
	 * On deactivation, remove all functions from the scheduled action hook.
	 *
	 * @return void
	 */
	function mh_deactivation() {
		wp_clear_scheduled_hook( 'mh_15minutes_event_hook' );
	}

	/**
	 * Gets the posts from Mediahub API, inserts/updates posts and media
	 *
	 * @return void
	 */
	function mh_get_or_update_notes() {

		// Get Mediahub categories
		if ( $mh_categories = get_option( 'mhca_options', false ) ) {

			// Get ID's from events to compare with note ID's later on
			if( $mh_events = $this->mediahub_get( $get = '/notes.json', $query = '&type=event' ) ) {

				$event_ids = array();
				foreach ($mh_events->data as $event_key => $event_value) {
					$event_ids[] = $event_key;
				}
			}

			// Loop through found categories
			foreach ( $mh_categories['mediahub_categorieen'] as $term_id ) {

				// Category term
				// $mh_cat_term = $this->mediahub_get( $get = '/terms/' . $term_id . '.json', $query = '&structure=1' );

				// Posts in category
				$mh_cat_notes = $this->mediahub_get( $get = '/terms/' . $term_id . '/notes.json', $query = '&structure=1' );

				// If there is any data in the result
				if ( isset( $mh_cat_notes->data ) ) {

					// Loop over every post
					foreach ( $mh_cat_notes->data as $note_id => $object ) {

						// Check if post already exists
						$args = array(
							'post_type'       => 'post',
							'ignore_sticky_posts' => 1,
							'post_status'     => 'any',
							'posts_per_page'  => -1,
							'meta_key'        => 'mediahub_note_id',
							'meta_query'      => array(
								array(
									'key'   => 'mediahub_note_id',
									'value' => $object->id,
									'compare' => '='
								)
							)
						);
						$post_exists = new WP_Query( $args );

						// If no post is found
						if ( count( $post_exists->posts ) == 0 ) {

							// If status is published
							if ( $object->status == 'published' ) {

								// Get all info
								$mh_all = $this->mediahub_get( $get = '/notes/' . $object->id . '/all.json', $query = '' );

								// Get latest revision
								$mh_post = new StdClass;
								$mh_post->data = $mh_all->data->revision;
								$mh_post->success = $mh_all->success;

								// Default content is empty
								$post_content = '';

								// Excerpt & content are filled, add more tag
								if ( $mh_post->data->excerpt != '' && $mh_post->data->content != '' )
									$post_content = $mh_post->data->excerpt . "\n<!--more-->\n" . $mh_post->data->content;

								// Excerpt is empty, just add content
								if ( $mh_post->data->excerpt == '' && $mh_post->data->content != '' )
									$post_content = $mh_post->data->content;

								// Content is empty, just add excerpt
								if ( $mh_post->data->excerpt != '' && $mh_post->data->content == '' )
									$post_content = $mh_post->data->excerpt;

								// Do media stuff
								if ( isset( $mh_all->data->media ) && $media = json_decode( json_encode( $mh_all->data->media ), true ) ) {

									if ( isset( $media[4] ) ) {
										$audio = $media[4];
										$audio = reset( $audio );
										$audio = reset( $audio );
										if ( isset( $audio['file'] ) )
											$post_content .= "\n\n" . '[embed]' . $audio['file'] . '[/embed]' . "<br/>";
										unset( $audio );
									}

									if ( isset( $media[5] ) ) {
										$video = $media[5];
										$video = reset( $video );
										if ( isset( $video['hd'] ) )
											$post_content .= "\n\n" . '[embed width="' . $video['sd']['width'] . '" height="' . $video['sd']['height'] . '"]' . $video['hd']['file'] . '[/embed]';
										unset( $video );
									}
								}

								// Insert new post
								$post = array(
									'post_title'    => $mh_post->data->title,
									'post_date'     => $object->date_published,
									'post_content'  => $post_content,
									'post_status'   => 'publish',
									'post_author'   => 1
								);
								$post_id = wp_insert_post( $post );

								// Add meta data
								add_post_meta( $post_id, 'mediahub_note_id', $object->id, true );
								add_post_meta( $post_id, 'mediahub_date_created', $object->date_created, true );
								add_post_meta( $post_id, 'mediahub_date_changed', $object->date_changed, true );
								add_post_meta( $post_id, 'mediahub_date_published', $object->date_published, true );

								// Compare ID with event ID, if true add mediahub_event meta
								if ( in_array( $object->id, $event_ids ) ) {
									update_post_meta( $post_id, 'mediahub_event', 'true' );
									if ( isset( $mh_all->data->event->start ) )
										update_post_meta( $post_id, 'mediahub_event_start', $mh_all->data->event->start );
									if ( isset( $mh_all->data->event->end ) )
										update_post_meta( $post_id, 'mediahub_event_end', $mh_all->data->event->end );
								}

								// Category
								foreach ( $mh_categories['mediahub_cat2cat'] as $key => $value) {
									if ( $key == $term_id )
										wp_set_post_categories( $post_id, array( $value ) );
								}

								// Tags
								$terms = array();
								foreach ( $mh_all->data->term as $term_key => $term_value) {
									if ( $term_value->structure == 2 )
										$terms[] = strtolower( $term_value->name );
								}
								wp_set_post_terms( $post_id, $terms, 'post_tag', $append = false );

								// Featured Image
								if ( isset( $media[3] ) ) {
									$featured_image = $media[3];
									$featured_image = reset( $featured_image );

									// Sideload the featured image
									if ( isset( $featured_image['img-hd'] ) ) {

										// Add 20 seconds to time limit due to GD being slow sometimes
										set_time_limit( 20 );

										$image = media_sideload_image( $featured_image['img-hd']['file'], $post_id, $mh_post->data->title );

										// Get attached images
										$images = get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $post_id );
										$images = reset( $images );
										$images = reset( $images );

										// Set first attached image as featured image
										add_post_meta( $post_id, '_thumbnail_id', $images );
									}

									// Cleanup
									unset( $featured_image );
								}
								// Cleanup
								unset( $media );
							}
						}
						elseif ( count( $post_exists->posts ) == 1 ) {

							// If status is unpublished
							if ( $object->status == 'unpublished' )
								wp_delete_post( $post_exists->posts[0]->ID, $force_delete = true );

							// If status is expired
							elseif ( $object->status == 'expired' )
								wp_delete_post( $post_exists->posts[0]->ID, $force_delete = true );

							// If status is concept or pending
							elseif ( $object->status == 'concept' or $object->status == 'pending' )
								$post_status = 'draft';

							// In every other case, just set to publish
							else
								$post_status = 'publish';

							if ( isset( $post_status ) ) {

								// If modified date is the same, continue to next item
								if ( $object->date_changed == get_post_meta( $post_exists->posts[0]->ID, $key = 'mediahub_date_changed', $single = true ) )
									continue;

								// Get all info
								$mh_all = $this->mediahub_get( $get = '/notes/' . $object->id . '/all.json', $query = '' );

								// Get latest revision
								$mh_post = new StdClass;
								$mh_post->data = $mh_all->data->revision;
								$mh_post->success = $mh_all->success;

								// Default content is empty
								$post_content = '';

								// Excerpt & content are filled, add more tag
								if ( $mh_post->data->excerpt != '' && $mh_post->data->content != '' )
									$post_content = $mh_post->data->excerpt . "\n<!--more-->\n" . $mh_post->data->content;

								// Excerpt is empty, just add content
								if ( $mh_post->data->excerpt == '' && $mh_post->data->content != '' )
									$post_content = $mh_post->data->content;

								// Content is empty, just add excerpt
								if ( $mh_post->data->excerpt != '' && $mh_post->data->content == '' )
									$post_content = $mh_post->data->excerpt;

								// Do media stuff
								if ( isset( $mh_all->data->media ) && $media = json_decode( json_encode( $mh_all->data->media ), true ) ) {

									if ( isset( $media[4] ) ) {
										$audio = $media[4];
										$audio = reset( $audio );
										$audio = reset( $audio );
										if ( isset( $audio['file'] ) )
											$post_content .= "\n\n" . '[embed]' . $audio['file'] . '[/embed]' . "<br/>";
										unset( $audio );
									}

									if ( isset( $media[5] ) ) {
										$video = $media[5];
										$video = reset( $video );
										if ( isset( $video['hd'] ) )
											$post_content .= "\n\n" . '[embed width="' . $video['sd']['width'] . '" height="' . $video['sd']['height'] . '"]' . $video['hd']['file'] . '[/embed]';
										unset( $video );
									}
								}

								// Update post
								$post = array(
									'ID'            => $post_exists->posts[0]->ID,
									'post_title'    => $mh_post->data->title,
									'post_date'     => $object->date_published,
									'post_content'  => $post_content,
									'post_status'   => $post_status,
									'post_author'   => 1
								);
								$post_id = wp_update_post( $post );

								// Add meta data
								update_post_meta( $post_id, 'mediahub_note_id', $object->id );
								update_post_meta( $post_id, 'mediahub_date_created', $object->date_created );
								update_post_meta( $post_id, 'mediahub_date_changed', $object->date_changed );
								update_post_meta( $post_id, 'mediahub_date_published', $object->date_published );

								// Reset Categories and Tags
								wp_set_object_terms( $post_id, null, 'category' );
								wp_set_object_terms( $post_id, null, 'post_tag' );

								// Category
								foreach ( $mh_categories['mediahub_cat2cat'] as $key => $value) {
									if ( $key == $term_id )
										wp_set_post_categories( $post_id, array( $value ) );
								}

								// Tags
								$terms = array();
								foreach ( $mh_all->data->term as $term_key => $term_value) {
									if ( $term_value->structure == 2 )
										$terms[] = strtolower( $term_value->name );
								}
								wp_set_post_terms( $post_id, $terms, 'post_tag', $append = false );

								// Compare ID with event ID, if true add mediahub_event meta
								if ( in_array( $object->id, $event_ids ) ) {
									update_post_meta( $post_id, 'mediahub_event', 'true' );
									if ( isset( $mh_all->data->event->start ) )
										update_post_meta( $post_id, 'mediahub_event_start', $mh_all->data->event->start );
									if ( isset( $mh_all->data->event->end ) )
										update_post_meta( $post_id, 'mediahub_event_end', $mh_all->data->event->end );
								}

								// Featured Image
								if ( isset( $media[3] ) ) {
									$featured_image = $media[3];
									$featured_image = reset( $featured_image );

									// First remove the featured image
									if ( $images = get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $post_id ) ) {
										$images = reset( $images );
										$images = reset( $images );

										wp_delete_attachment( $images, true );
									}


									// Sideload the featured image
									if ( isset( $featured_image['img-hd'] ) ) {

										// Add 20 seconds to time limit due to GD being slow sometimes
										set_time_limit( 20 );

										$image = media_sideload_image( $featured_image['img-hd']['file'], $post_id, $mh_post->data->title );

										// Get attached images
										$images = get_children( 'post_type=attachment&post_mime_type=image&post_parent=' . $post_id );
										$images = reset( $images );
										$images = reset( $images );

										// Set first attached image as featured image
										add_post_meta( $post_id, '_thumbnail_id', $images );
									}

									// Cleanup
									unset( $featured_image );
								}
								// Cleanup
								unset( $media );
							}

						}

						// Multiple posts found, this should not be happening. Panic!
						// elseif ( count( $post_exists->posts ) > 1 ) {
						// 	$error_post = $post_exists->posts[0]->ID;
						// 	// Mail admin
						// 	wp_mail( 'servicedesk@amersfoortbreed.tv', 'ERROR: Mediahub API WP plugin', "Multiple posts found for update of Post ID" . $error_post .", this should not be happening. \n\nThis message originated from: " . get_home_url() );
						// }
					}
				}
			}
		}

		wp_redirect( admin_url() . 'options-general.php?page=mediahub_api' );
	}


	/**
	 * Agenda shortcode displays agenda items
	 *
	 * @return string $event Agenda items
	 */
	function mediahub_agenda_shortcode() {

		// WP_Query arguments
		$args = array (
			'post_type'              => 'post',
			'post_status'            => 'all',
			'pagination'             => false,
			'posts_per_page'         => '-1',
			'ignore_sticky_posts'    => true,
			'meta_key'               => 'mediahub_event_start',
			'orderby'                => 'meta_value',
			'order'                  => 'ASC',
			'meta_query'             => array(
				array(
					'key'       => 'mediahub_event',
					'value'     => 'true',
					'compare'   => '=',
					'type'      => 'CHAR',
				),
			),
		);

		// The Query
		$mediahub_events = new WP_Query( $args );

		$event = '';

		// The Loop
		if ( $mediahub_events->have_posts() ) {

			while ( $mediahub_events->have_posts() ) {
				$mediahub_events->the_post();

				$event .= '<h2><a href="' . get_permalink() . '">' . get_the_title() . "</a></h2>\n";

				$start = date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( get_the_ID(), $key = 'mediahub_event_start', $single = true ) ) );
				$end   = date_i18n( get_option( 'date_format' ), strtotime( get_post_meta( get_the_ID(), $key = 'mediahub_event_end', $single = true ) ) );

				$event .= '<p class="event-date">' . $start . ' tot ' . $end . "</p>\n";
				$event .= '<p class="event-excerpt">' . get_the_excerpt() . ' <a class="readmore" href="' . get_permalink() . '">Lees verder' . "</a></p>\n";
			}
		} else {
			// no posts found
			$event = '<p>Geen actuele agenda-items gevonden</p>';
		}

		// Restore original Post Data
		wp_reset_postdata();

		return $event;
	}

}

$settings = new MediaHub_Content_API();
