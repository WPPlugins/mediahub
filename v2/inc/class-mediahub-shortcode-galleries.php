<?php

class MediaHub_Galleries extends MediaHub_Core {

	/**
	 * Class constructor.
	 */
	function __construct() {

		add_filter( 'post_gallery', array( $this, 'gallery_shortcode' ), 10, 3 );
		add_filter( 'gallery_style', array( $this, 'gallery_style' ), 10, 1 );
	}

	/**
	 * Ouputs gallery headings
	 *
	 * @param  string $output Inline css styles
	 * @return string         Inline css styles prepended with heading
	 */
	public function gallery_style( $output ) {

		$headings = get_option( $option = 'mhca_articles', $default = array() );

		if ( strpos( $output, 'galleryid-post-images-' ) !== false ) {

			if ( isset( $headings['mediahub_articles_titles_photo'] ) && isset( $headings['mediahub_articles_titles_heading'] ) && $headings['mediahub_articles_titles_heading'] != 'no' ) {
				$output = '<' . $headings['mediahub_articles_titles_heading'] . ' class="mh-media-header">' . $headings['mediahub_articles_titles_photo'] . '</' . $headings['mediahub_articles_titles_heading'] . '>' . $output;
			}


		}
		if ( strpos( $output, 'galleryid-gallery-images-' ) !== false ) {

			if ( isset( $headings['mediahub_articles_titles_gallery'] ) && isset( $headings['mediahub_articles_titles_heading'] ) && $headings['mediahub_articles_titles_heading'] != 'no' ) {
				$output = '<' . $headings['mediahub_articles_titles_heading'] . ' class="mh-media-header">' . $headings['mediahub_articles_titles_gallery'] . '</' . $headings['mediahub_articles_titles_heading'] . '>' . $output;
			}

		}
		return $output;
	}

	/**
	 * Builds the Gallery shortcode output.
	 *
	 * This implements the functionality of the Gallery Shortcode for displaying
	 * WordPress images on a post.
	 *
	 * @since 2.5.0
	 *
	 * @param array $attr {
	 *     Attributes of the gallery shortcode.
	 *
	 *     @type string $order      Order of the images in the gallery. Default 'ASC'. Accepts 'ASC', 'DESC'.
	 *     @type string $orderby    The field to use when ordering the images. Default 'menu_order ID'.
	 *                              Accepts any valid SQL ORDERBY statement.
	 *     @type int    $id         Post ID.
	 *     @type string $itemtag    HTML tag to use for each image in the gallery.
	 *                              Default 'dl', or 'figure' when the theme registers HTML5 gallery support.
	 *     @type string $icontag    HTML tag to use for each image's icon.
	 *                              Default 'dt', or 'div' when the theme registers HTML5 gallery support.
	 *     @type string $captiontag HTML tag to use for each image's caption.
	 *                              Default 'dd', or 'figcaption' when the theme registers HTML5 gallery support.
	 *     @type int    $columns    Number of columns of images to display. Default 3.
	 *     @type string $size       Size of the images to display. Default 'thumbnail'.
	 *     @type string $ids        A comma-separated list of IDs of attachments to display. Default empty.
	 *     @type string $include    A comma-separated list of IDs of attachments to include. Default empty.
	 *     @type string $exclude    A comma-separated list of IDs of attachments to exclude. Default empty.
	 *     @type string $link       What to link each image to. Default empty (links to the attachment page).
	 *                              Accepts 'file', 'none'.
	 * }
	 * @return string HTML content to display gallery.
	 */
	public function gallery_shortcode( $empty, $attr, $instance ) {

		// return if gallery is not a mediahub gallery
		if ( ! isset( $attr['type'] ) || ( isset( $attr['type'] ) && 'mediahub_gallery' != $attr['type'] ) ) {
			return '';
		}

		add_filter( 'jp_carousel_force_enable', '__return_true' );

		$post = get_post();

		$html5 = current_theme_supports( 'html5', 'gallery' );
		$atts = shortcode_atts( array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => $post ? $post->ID : 0,
			'itemtag'    => $html5 ? 'figure'     : 'dl',
			'icontag'    => $html5 ? 'div'        : 'dt',
			'captiontag' => $html5 ? 'figcaption' : 'dd',
			'columns'    => 3,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => '',
			'link'       => ''
		), $attr, 'gallery' );

		$mh_galleries = array();

		if ( isset( $attr['gallery_type'] ) && 'post_images' == $attr['gallery_type'] ) {

			$mh_galleries_meta = get_post_meta( $post->ID, $key = '_mediahub_gallery_post_images', $single = true );

			$mh_galleries = $mh_galleries_meta;
			$id = 'post-images-' . $post->ID;
		}
		else {

			$mh_galleries_meta = get_post_meta( $post->ID, $key = '_mediahub_gallery_gallery_images', $single = true );

			$mh_galleries = $mh_galleries_meta;
			$id = 'gallery-images-' . $post->ID;
		}

		if ( empty( $mh_galleries ) ) {
			return '';
		}

		$attachments = $mh_galleries;

		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment ) {
				$output .= $attachment['thumbnail'] . "\n";
			}
			return $output;
		}

		$itemtag = tag_escape( $atts['itemtag'] );
		$captiontag = tag_escape( $atts['captiontag'] );
		$icontag = tag_escape( $atts['icontag'] );
		$valid_tags = wp_kses_allowed_html( 'post' );
		if ( ! isset( $valid_tags[ $itemtag ] ) ) {
			$itemtag = 'dl';
		}
		if ( ! isset( $valid_tags[ $captiontag ] ) ) {
			$captiontag = 'dd';
		}
		if ( ! isset( $valid_tags[ $icontag ] ) ) {
			$icontag = 'dt';
		}

		$columns = intval( $atts['columns'] );
		$itemwidth = $columns > 0 ? floor(100/$columns) : 100;
		$float = is_rtl() ? 'right' : 'left';

		$selector = "gallery-{$instance}";

		$gallery_style = '';

		/**
		 * Filter whether to print default gallery styles.
		 *
		 * @since 3.1.0
		 *
		 * @param bool $print Whether to print default gallery styles.
		 *                    Defaults to false if the theme supports HTML5 galleries.
		 *                    Otherwise, defaults to true.
		 */
		if ( apply_filters( 'use_default_gallery_style', ! $html5 ) ) {
			$gallery_style = "
			<style type='text/css'>
				#{$selector} {
					margin: auto;
				}
				#{$selector} .gallery-item {
					float: {$float};
					margin-top: 10px;
					text-align: center;
					width: {$itemwidth}%;
				}
				#{$selector} img {
					border: 2px solid #cfcfcf;
				}
				#{$selector} .gallery-caption {
					margin-left: 0;
				}
				/* see gallery_shortcode() in wp-includes/media.php */
			</style>\n\t\t";
		}

		$size_class = sanitize_html_class( $atts['size'] );
		$gallery_div = "<div id='$selector' class='mediahub-gallery gallery galleryid-{$id} gallery-columns-{$columns} gallery-size-{$size_class}'>";

		/**
		 * Filter the default gallery shortcode CSS styles.
		 *
		 * @since 2.5.0
		 *
		 * @param string $gallery_style Default CSS styles and opening HTML div container
		 *                              for the gallery shortcode output.
		 */
		$output = "<div class='mediahub-gallery-wrapper'>" . apply_filters( 'gallery_style', $gallery_style . $gallery_div );

		$i = 0;
		$count = 0;
		foreach ( $attachments as $id => $attachment ) {

			if ( $count % 2 == 0 ) {
				$class = 'one-half first';
			}
			else {
				$class = 'one-half last';
			}

			$image_output = '<a href="' . $attachment['hd'] . '">';
			$image_output .= '<img src="' . $attachment['thumbnail'] .'"';
			$image_output .= 'data-orig-file="' . $attachment['hd'] .'"';
			$image_output .= 'data-large-file="' . $attachment['hd'] . '"';
			$image_output .= 'data-medium-file="' . $attachment['hd'] . '"';
			$image_output .= 'data-orig-size="1920,1080"';
			$image_output .= 'data-comments-opened="0"';
			$image_output .= 'data-attachment-id="' . $post->ID . '"';
			$image_output .= 'data-image-meta=""';
			$image_output .= 'data-image-title=""';
			$image_output .= 'data-image-description=""';
			$image_output .= 'class="attachment-medium" alt="' . esc_attr( get_the_title( $post->ID ) ) . '"';
			$image_output .= '></a>';

			$orientation = 'landscape';

			$output .= "<{$itemtag} class='gallery-item {$class}'>";
			$output .= "<{$icontag} class='gallery-icon {$orientation}'>$image_output</{$icontag}>";
			$output .= "</{$itemtag}>";
			if ( ! $html5 && $columns > 0 && ++$i % $columns == 0 ) {
				$output .= '<br style="clear: both" />';
			}
			$count++;
		}

		if ( ! $html5 && $columns > 0 && $i % $columns !== 0 ) {
			$output .= "<br style='clear: both' />";
		}

		$output .= "</div></div>\n";

		return $output;
	}
}
new MediaHub_Galleries;
