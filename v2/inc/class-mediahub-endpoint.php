<?php

/**
 * If ?mh=XXX query var is present, then do redirect to the corresponding post.
 */
class MediaHub_Endpoint extends MediaHub_Core {

	/**
	 * Class constructor.
	 */
	public function __construct() {

		if ( isset( $_GET['mh'] ) && ctype_digit( $_GET['mh'] ) ) {
			add_action( 'init', array( $this, 'redirect_to_post' ) );
		}
	}

	/**
	 * Redirecting to the post ID.
	 */
	public function redirect_to_post() {

		$item_id = absint( $_GET['mh'] );

		$args = array(
			'post_type'           => 'post',
			'ignore_sticky_posts' => 1,
			'post_status'         => 'any',
			'posts_per_page'      => 1,
			'meta_query'          => array(
				array(
					'key'     => self::META_KEY,
					'value'   => $item_id,
					'compare' => '='
				)
			)
		);
		$post_exists = new WP_Query( $args );

		foreach( $post_exists->posts as $key => $post ) {
			$url = get_permalink( $post->ID );
			$url = esc_url( $url );
			wp_safe_redirect( $url, 302 );
			exit;
		}

	}

}
new MediaHub_Endpoint;
