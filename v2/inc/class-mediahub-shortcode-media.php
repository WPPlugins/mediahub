<?php

class MediaHub_MediaTags extends MediaHub_Core {

	/**
	 * Class constructor.
	 */
	function __construct() {

		add_shortcode( 'mh_media', array( $this, 'mh_media_shortcode' ) );

	}

	/**
	 * Media display shortcode
	 *
	 * @param  array $atts Shortcode attributes
	 * @return string      Shortcode html
	 */
	public function mh_media_shortcode( $atts ) {

		$media      = get_post_meta( get_the_ID(), $key = '_mediahub_media_types' );
		$media_type = get_post_meta( get_the_ID(), $key = '_mediahub_media_type', $single = true );
		$headings   = get_option( $option = 'mhca_articles', $default = array() );

		/*
		YouTube
		 */
		if ( $atts['type'] == 'youtube' && isset( $media[0]['youtube'] ) && count( $media[0]['youtube'] ) > 0 ) {

			$youtubes = array();

			$embed_base = 'https://youtube.com/embed/';

			foreach ( $media[0]['youtube'] as $url ) {

				// fix wrong YouTube urls
				if ( strpos( $url, '/watch?v=' )  !== false ) {
					$exploded_url = explode( '/watch?v=', $url );
					$exploded_url = explode( '&', $exploded_url[1] );
					$url = $embed_base . $exploded_url[0];
				}
				if ( strpos( $url, '/v/' )  !== false ) {
					$exploded_url = explode( '/v/', $url );
					$exploded_url = explode( '?', $exploded_url[1] );
					$url = $embed_base . $exploded_url[0];
				}

				$iframe = sprintf( $this->partials( 'iframe' ), esc_url( $url ), $width = 640, $height = 360 );

				$youtubes[] = sprintf( $this->partials( 'wrapper' ), 'youtube-wrapper', 'youtube', $iframe );

				unset( $url );
				unset( $iframe );
			}

			if ( count( $youtubes ) >= 2 ) {
				$cols = array();
				foreach ( $youtubes as $key => $youtube ) {
					if ( $key % 2 == 0 ) {
						$class = 'one-half first';
					}
					else {
						$class = 'one-half last';
					}
					$cols[] = sprintf( $this->partials( 'cols' ), $class, $youtube );
				}
				$youtubes = $cols;
			}

			if ( ! isset( $headings['mediahub_articles_titles_heading'] ) || ( isset( $headings['mediahub_articles_titles_heading'] ) && $headings['mediahub_articles_titles_heading'] == 'no' ) ) {
				$header = '';
			}
			elseif ( isset( $headings['mediahub_articles_titles_youtube'] ) ) {
				$header = sprintf( $this->partials( 'header' ), $headings['mediahub_articles_titles_heading'], $headings['mediahub_articles_titles_youtube'] );
			}

			return $header . sprintf( $this->partials( 'grid' ), implode( "", $youtubes ) );
		}
		/*
		End YouTube
		 */


		/*
		Video
		 */
		if ( $atts['type'] == 'video' && isset( $media[0]['video'] ) && count( $media[0]['video'] ) > 0 ) {

			$videos = array();

			foreach ( $media[0]['video'] as $url ) {

				$iframe = sprintf( $this->partials( 'iframe' ), esc_url( $url ), $width = 640, $height = 360 );

				$videos[] = sprintf( $this->partials( 'wrapper' ), 'video-wrapper', 'video', $iframe );

				unset( $url );
				unset( $iframe );
			}

			if ( count( $videos ) >= 2 ) {
				$cols = array();
				foreach ( $videos as $key => $video ) {
					if ( $key % 2 == 0 ) {
						$class = 'one-half first';
					}
					else {
						$class = 'one-half last';
					}
					$cols[] = sprintf( $this->partials( 'cols' ), $class, $video );
				}
				$videos = $cols;
			}

			if ( ! isset( $headings['mediahub_articles_titles_heading'] ) || ( isset( $headings['mediahub_articles_titles_heading'] ) && $headings['mediahub_articles_titles_heading'] == 'no' ) ) {
				$header = '';
			}
			elseif ( 'video' == $media_type ) {
				$header = '';
			}
			elseif ( isset( $headings['mediahub_articles_titles_video'] ) ) {
				$header = sprintf( $this->partials( 'header' ), $headings['mediahub_articles_titles_heading'], $headings['mediahub_articles_titles_video'] );
			}

			return $header . sprintf( $this->partials( 'grid' ), implode( "", $videos ) );
		}
		/*
		End Video
		 */


		/*
		Audio
		 */
		if ( $atts['type'] == 'audio' && isset( $media[0]['audio'] ) && count( $media[0]['audio'] ) > 0 ) {

			$audios = array();

			foreach ( $media[0]['audio'] as $url ) {

				$iframe = sprintf( $this->partials( 'iframe' ), esc_url( $url ), $width = 640, $height = 360 );

				$audios[] = sprintf( $this->partials( 'wrapper' ), 'audio-wrapper', 'audio', $iframe );

				unset( $url );
				unset( $iframe );
			}

			if ( count( $audios ) >= 2 ) {
				$cols = array();
				foreach ( $audios as $key => $audio ) {
					if ( $key % 2 == 0 ) {
						$class = 'one-half first';
					}
					else {
						$class = 'one-half last';
					}
					$cols[] = sprintf( $this->partials( 'cols' ), $class, $audio );
				}
				$audios = $cols;
			}

			if ( ! isset( $headings['mediahub_articles_titles_heading'] ) || ( isset( $headings['mediahub_articles_titles_heading'] ) && $headings['mediahub_articles_titles_heading'] == 'no' ) ) {
				$header = '';
			}
			elseif ( 'audio' == $media_type ) {
				$header = '';
			}
			elseif ( isset( $headings['mediahub_articles_titles_audio'] ) ) {
				$header = sprintf( $this->partials( 'header' ), $headings['mediahub_articles_titles_heading'], $headings['mediahub_articles_titles_audio'] );
			}

			return $header . sprintf( $this->partials( 'grid' ), implode( "", $audios ) );
		}
		/*
		End Audio
		 */
	}

	/**
	 * Returns partial html, wrapper function
	 *
	 * @param  string $part Defines which part to return.
	 * @return string       HTML string
	 */
	private function partials( $part ) {
		if ( $part == 'grid' ) {
			return '<div class="mh-grid"><div class="wrapper">%s</div></div>';
		}
		if ( $part == 'cols' ) {
			return '<div class="%s">%s</div>';
		}
		if ( $part == 'wrapper' ) {
			return '<div class="%s"><div class="%s">%s</div></div>';
		}
		if ( $part == 'iframe' ) {
			return '<iframe src="%s" width="%d" height="%d"></iframe>';
		}
		if ( $part == 'header' ) {
			return '<%1$s class="mh-media-header">%2$s</%1$s>';
		}
	}

}
new MediaHub_MediaTags;
