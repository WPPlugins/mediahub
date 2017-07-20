<?php

class MediaHub_Core {

	const MAX_POSTS_PER_PAGE = '100';
	const META_KEY           = 'mediahub_note_id';
	const GOOGLE_MAPS_KEY    = 'AIzaSyA-zp0l-rJO6JbOu2PzqOLkx-i2TVCXnzw';

	/**
	 * This is the function that gets the content from the API
	 *
	 * @param string  $get   JSON to call
	 * @param string  $query Optional Query to get
	 * @return object Result
	 */
	protected function mediahub_request( $get, $query = '', $type = 'GET' ) {

		// Checking that keys are added before attempting to retrieve data from API
		$api_keys = get_option( 'mhca_api_key' );
		if (
			( ! isset( $api_keys['mediahub_api_url'] ) || '' == $api_keys['mediahub_api_url'] )
			||
			( ! isset( $api_keys['mediahub_api_openkey'] ) || '' == $api_keys['mediahub_api_openkey'] )
			||
			( ! isset( $api_keys['mediahub_api_secretkey'] ) || '' == $api_keys['mediahub_api_secretkey'] )
			||
			( ! isset( $api_keys['mediahub_environment_id'] ) || '' == $api_keys['mediahub_environment_id'] )
		) {
			return array();
		}

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

		if ( isset( $api_settings['mediahub_environment_id'] ) ) {
			$environment_id = $api_settings['mediahub_environment_id'];
		} else {
			$environment_id = '';
		}

		$time = time();
		$nonce = md5( microtime() . mt_rand() );

		$authHash = hash_hmac( "sha512", $get . $time . $nonce, $secret );

		// Create authorisation for URL
		if ( 'GET' == $type ) {
			$auth = 'auth_key=' . $auth_key . '&auth_hash=' . $authHash . '&auth_nonce=' . $nonce . '&auth_time=' . $time. '&environment_id=' . $environment_id;
			$url = $api_url . $get . '?' . $query . $auth . '&limit=' . self::MAX_POSTS_PER_PAGE;
			if ( isset( $_GET['mh_sync'] ) ) {
				$url .= '&cache=false';
			}
			$result = wp_remote_get( $url, array( 'timeout' => 120, 'httpversion' => '1.1' ) );
		} else {
			$params['auth_key']       = $auth_key;
			$params['auth_hash']      = $authHash;
			$params['auth_nonce']     = $nonce;
			$params['auth_time']      = $time;
			$params['environment_id'] = $environment_id;

			// Add query vars as array (required for POSTs)
			foreach ( $query as $key => $value ) {
				$params[$key] = $value;
			}

			$url = $api_url . $get;
			if ( isset( $_GET['mh_sync'] ) ) {
				$url .= '&cache=false';
			}

			$result = wp_remote_post(
				$url,
				array(
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 5,
					'httpversion' => '1.1',
					'blocking'    => true,
					'headers'     => array(),
					'body'        => $params,
					'cookies'     => array()
				)
			);
		}

		// If error, just return the result
		if ( is_wp_error( $result ) ) {
			$result->success = 0;
			return $result;
		}

		// Grab and decoded the result data
		$result = $result['body'];
		$decoded_result = json_decode( $result );

		return $decoded_result;
	}

	/**
	 * Sideload an image and set it as featured image
	 *
	 * @param string  $image_url url of external image
	 * @param int     $post_id   post ID
	 * @param string  $title     title of image
	 */
	public function sideloadFeaturedImage( $image_url, $post_id, $title ) {

		// only need these if performing outside of admin environment
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// magic sideload image returns an HTML image, not an ID
		$media_src = media_sideload_image( $image_url, $post_id, $title, 'src' );

		// therefore we must find it so we can set it as featured ID
		if ( !empty( $media_src ) && !is_wp_error( $media_src ) ) {

			// We retrieve the attachment ID via the media SRC and set the post thumbnail
			$attachment_id = $this->get_image_id( $media_src );
			if ( is_numeric( $attachment_id ) ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}

		}

	}

	/**
	 * Get the attachment ID via the URL.
	 *
	 * Based on code by Pippin ... https://pippinsplugins.com/retrieve-attachment-id-from-image-url/
	 * 
	 * @param  string  $url  The image URL
	 * @return int  The attachment ID
	 */
	function get_image_id( $image_url ) {
		global $wpdb;
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url ) );
		return $attachment[0]; 
	}

	/**
	 * Delete associated media
	 *
	 * @param int     $id post ID
	 * @return void
	 */
	public function delete_associated_media( $id ) {

		$media = get_children( array(
				'post_parent' => $id,
				'post_type' => 'attachment'
			) );

		if ( empty( $media ) ) {
			return;
		}

		foreach ( $media as $file ) {
			wp_delete_attachment( $file->ID );
		}
	}
}
