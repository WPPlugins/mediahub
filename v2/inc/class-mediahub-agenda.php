<?php

class MediaHub_Agenda extends MediaHub_Core {

	/**
	 * Class constructor.
	 */
	function __construct() {
		add_shortcode( 'mediahub_agenda', array( $this, 'agenda_shortcode' ) );
	}


	/**
	 * Displays a list of posts added by MediaHub.
	 *
	 * @return string $event Agenda items
	 */
	function agenda_shortcode( $args ) {

		// Cater for no arguments
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		// Loop through arguments and add in booleans
		foreach( $args as $key => $value ) {
			if ( 'hide_title' == $value ) {
				$args['hide_title'] = true;
			}
			if ( 'hide_date' == $value ) {
				$args['hide_date'] = true;
			}
			if ( 'last_day' == $value ) {
				$args['last_day'] = true;
			}
			if ( 'content' == $value ) {
				$args['content'] = true;
			}
		}

		// WP_Query arguments
		$query_args = array (
			'post_type'              => 'post',
			'ignore_sticky_posts'    => true,
			'meta_key'               => self::META_KEY,
			'orderby'                => 'meta_value',
			'order'                  => 'ASC',
			'meta_query'             => array(
				array(
					'key'       => self::META_KEY,
				),
			),
		);

		// Set the category
		if ( isset( $args['category'] ) ) {
			$query_args['category_name'] = esc_html( $args['category'] );
		}

		// Limit to last day
		if ( isset( $args['last_day'] ) ) {
			$query_args['last_day'] = array(
				array(
					'year'  => date( 'y', time() ),
					'month' => date( 'm', time() ),
					'day'   => date( 'd', time() ),
				),
			);
		}

		$content = '';

		// Limit to date range
		if ( isset( $args['before_date'] ) && isset( $args['after_date'] ) ) {
			$before_date = explode( '-', $args['before_date'] );
			$after_date  = explode( '-', $args['after_date'] );

			// Validate date values
			$value_error = __( 'Invalid date range', 'mediahub' );
			if ( isset ( $before_date[1] ) && 1 <= $before_date[1] && $before_date[1] <= 12 ) {
				$before_month = $before_date[1];
			} elseif ( is_user_logged_in() ) {
				$content .= $value_error;
			}
			if ( isset ( $after_date[1] ) && 1 <= $after_date[1] && $after_date[1] <= 12 ) {
				$after_month = $after_date[1];
			} elseif ( is_user_logged_in() ) {
				$content .= $value_error;
			}
			if ( isset ($before_date[0] ) && 1990 <= $before_date[0] && $before_date[0] <= 2100 ) {
				$before_year = $before_date[0];
			} elseif ( is_user_logged_in() ) {
				$content .= $value_error;
			}
			if ( isset( $after_date[0] ) && 1990 <= $after_date[0] && $after_date[0] <= 2100 ) {
				$after_year = $after_date[0];
			} elseif ( is_user_logged_in() ) {
				$content .= $value_error;
			}
			if ( isset ( $before_date[2] ) && 1 <= $before_date[2] && $before_date[2] <= 31 ) {
				$before_day = $before_date[2];
			} elseif ( is_user_logged_in() ) {
				$content .= $value_error;
			}
			if ( isset ( $after_date[2] ) && 1 <= $after_date[2] && $after_date[2] <= 31 ) {
				$after_day = $after_date[2];
			} elseif ( is_user_logged_in() ) {
				$content .= $value_error;
			}

			if ( isset( $before_year ) && isset( $before_month ) && isset( $before_day ) &&  isset( $after_year ) && isset( $after_month ) && isset( $after_day ) ) {
				$query_args['date_query'] = array(
					array(
						'before'    => array(
							'year'    => absint( $before_year ),
							'month'   => absint( $before_month ),
							'day'     => absint( $before_day ),
						),
						'after'     => array(
							'year'    => absint( $after_year ),
							'month'   => absint( $after_month ),
							'day'     => absint( $after_day ),
						),

						'inclusive' => true,
					),
				);
			}
		}

		if ( isset( $args['posts_per_page'] ) && is_numeric( $args['posts_per_page'] ) ) {
			if ( $args['posts_per_page'] < self::MAX_POSTS_PER_PAGE ) {
				$query_args['posts_per_page'] = $args['posts_per_page'];
			} else {
				$query_args['posts_per_page'] = self::MAX_POSTS_PER_PAGE;
			}
		}

		// The Query
		$mediahub_events = new WP_Query( $query_args );
		$content = '';

		// Loop through all existing posts made by MediaHub
		$no_posts_found = true; // used for serving error after loop
		if ( $mediahub_events->have_posts() ) {

			while ( $mediahub_events->have_posts() ) {
				$mediahub_events->the_post();

				// Only display article if it is an event (which have designated start and end times)
				if ( '' != get_post_meta( get_the_ID(), 'mediahub_event_started_on', true ) && '' != get_post_meta( get_the_ID(), 'mediahub_event_ended_on', true ) ) {

					$no_posts_found = false;

					// Display title
					if ( ! isset( $args['hide_title'] ) ) {
						$content .= '<h2><a href="' . esc_url( get_permalink() ) . '">' . get_the_title() . "</a></h2>\n";
					}

					if ( ! isset( $args['hide_date'] ) ) {
						$start = date_i18n( get_option( 'date_format' ), get_post_meta( get_the_ID(), 'mediahub_event_started_on', true ) );
						$end   = date_i18n( get_option( 'date_format' ), get_post_meta( get_the_ID(), 'mediahub_event_ended_on', true ) );
						$content .= '<p class="event-date">' . $start . ' ' . __( 'to', 'mediahub' ) . ' ' . $end . "</p>\n";
					}

					// Adding excerpts
					if ( isset( $args['content'] ) ) {
						$the_content = get_the_content();
						$the_content = apply_filters( 'the_content', $the_content );
					} else {
						$the_content = get_the_excerpt();
					}

					$content .= '<p class="event-excerpt">' . $the_content . ' <a class="readmore" href="' . esc_url( get_permalink() ) . '">' . __( 'Read more', 'mediahub' ) . "</a></p>\n";

				}
			}
		}

		// If no posts found, then say so
		if ( true == $no_posts_found ) {
			// Message to display when no posts found
			$content .= '<p>' . __( 'No current calendar items found', 'mediahub' ) . '</p>';
		}

		// Restore original Post Data
		wp_reset_postdata();

		return $content;
	}

}
new MediaHub_Agenda;
