<?php

class MediaHub_Comments extends MediaHub_Core {

	/**
	 * Class constructor.
	 */
	function __construct() {

		add_filter( 'preprocess_comment' , array( $this, 'preprocess_comment_handler' ) );

	}

	/**
	 * Check if comment is on a MediaHub post. If it is, tell MediaHub about the comment.
	 *
	 * @param  array  $comment_data Comment meta data
	 * @return array  $comment_data Unmodified comment meta data
	 */
	public function preprocess_comment_handler( $comment_data ) {
		$post_id = absint( $comment_data['comment_post_ID'] );
		$post_id_from_meta = get_post_meta( $post_id, self::META_KEY, true );
		if ( '' != $post_id_from_meta ) {
			// Do API call here

			$query['content'] = $comment_data['comment_content'];
			$query['anonymous_name'] = $comment_data['comment_author'];
			$query['email'] = $comment_data['comment_author_email'];
			$response = $this->mediahub_request( 'articles/' . $post_id_from_meta . '/comment', $query, 'POST' );

		}

		return $comment_data;
	}

}
new MediaHub_Comments;
