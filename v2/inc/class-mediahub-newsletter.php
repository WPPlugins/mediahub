<?php

class MediaHub_Newsletter extends MediaHub_Core {

	private $args;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		$this->args = array(
			'email'           => __( 'Email address', 'mediahub' ),
			'preposition'     => __( 'Initials', 'mediahub' ),
			'firstname'       => __( 'First name', 'mediahub' ),
			'prefix_lastname' => __( 'Prefix last name', 'mediahub' ),
			'lastname'        => __( 'Surname', 'mediahub' ),
			'groups'          => __( 'API groups', 'mediahub' ),
		);

		add_action( 'init', array( $this, 'send_newsletter_details_to_api' ) );
		add_shortcode( 'mediahub_newsletter', array( $this, 'shortcode' ) );
	}

	/**
	 * Add the newsletter form shortcode.
	 *
	 * @param array   $args The shortcode arguments
	 * @return   string          The form HTML
	 */
	public function shortcode( $args ) {
		$content = '';

		// Provide warning if no arguments set
		if ( empty( $args ) ) {
			$content .= '<p>' . __( 'No arguments were set in the [mediahub_newsletter] shortcode.', 'mediahub' );
			return $content;
		}

		// If data submitted successfully, then serve message saying so
		if ( isset( $this->success ) && true == $this->success ) {
			$content .= '<p>' . __( 'Your details have been added to our newsletter service.', 'mediahub' ) . '</p>';
			return $content;
		}

		// Catering for debug errors when no fields set
		if ( ! in_array( 'email', $args ) ) {
			$args = array();
			if ( is_user_logged_in() ) {
				$content .= __( 'The email field is required', 'mediahub' );
			}
		}

		// If groups argument not set, then we can use the default value entered into the settings page
		if ( ! in_array( 'groups', $args ) ) {
			$options = get_option( 'mhca_options' );
			$args[] = 'groups';
			$this->post['mediahub_newsletter']['groups'] = $options['mediahub_api_group'];
		}

		// Error message for replicated email address
		if ( isset( $this->error ) && in_array( 'groups', $this->error ) ) {
			$content .= '
				<p>
					<strong>' . __( 'You are already subscribed.', 'mediahub' ) . '</strong>
				</p>';
		}

		$content .= '
		<form method="post" action="">';

		// Loop through each possible argument
		foreach ( $this->args as $arg => $label ) {

			// Check if possible argument is in those selected
			if ( in_array( $arg , $args ) ) {

				// Email addresses are treated differently
				if ( 'email' == $arg ) {
					$type = 'email';
					$attributes = '';
					$required = 'required ';
				} elseif ( 'groups' == $arg ) {
					$type = 'hidden';
					$attributes = ' style="display:none"';
					$required = '';
				} else {
					$type = 'text';
					$attributes = '';
					$required = '';
				}

				// Add error message for field
				if ( isset( $this->error ) && in_array( $arg, $this->error ) ) {
					$label .= ' <strong>(' . __( 'Error: invalid value', 'mediahub' ) . ')</strong>';
				}

				// Add existing value
				if ( isset( $args[$arg] ) ) {
					$value = $args[$arg];
				} elseif ( isset( $this->post['mediahub_newsletter'][$arg] ) ) {
					$value = $this->post['mediahub_newsletter'][$arg];
				} else {
					$value = '';
				}

				// Add an input field
				$content .= '
				<p' . $attributes . '>
					<label>' . $label . '</label>
					<input ' . $required . 'name="' . esc_attr( 'mediahub_newsletter[' . $arg . ']' ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" />
				</p>';
			}
		}

		$content .= '
			<input type="submit" value="' . __( 'Submit' ). '" />
		</form>';

		return $content;
	}

	/**
	 * Send the newsletter submission to the API.
	 */
	public function send_newsletter_details_to_api() {

		if ( ! isset( $_POST['mediahub_newsletter'] ) ) {
			return;
		}

		// Loop through each field
		$query = '';
		foreach ( $_POST['mediahub_newsletter'] as $arg => $value ) {

			// Check field against white list
			if ( array_key_exists( $arg, $this->args ) ) {

				// Sanitize the outputs
				if ( 'email' == $arg ) {
					$result[$arg] = $new_value = sanitize_email( $value );
				} else {
					$result[$arg] = $new_value = wp_kses_post( $value );
				}

				$query[$arg] = $new_value;
			}
		}

		// Do API call
		$result = $this->mediahub_request( 'newsletters/subscribers', $query, 'POST' );

		// If sucessful, then continue to form
		if ( 1 == $result->success ) {
			$this->success = true;
			return;
		}

		// Handle errors
		$error_object = $result->error;

		// Setup each type of error, and loop through to check for their presence
		$error_types = array(
			'groups.exists'        => 'groups',
			'email.email'          => 'email',
			'email.required'       => 'email',
			'preposition.in'       => 'preposition',
			'firstname.regex'      => 'firstname',
			'prefixlastname.regex' => 'prefix_lastname',
			'lastname.regex'       => 'lastname',
		);
		foreach ( $error_types as $error_type => $field ) {

			if ( isset( $error_object->$field ) ) {
				$error = $error_object->$field;
				$error = $error[0];

				if ( 'newsletter/subscriber.validator.error.' . $error_type == $error ) {

					// Add error to object - used later in the form
					$this->error[] = $field;

				}

			}
		}

		// Put POST variables into object so that they can be easily used later in the form
		$this->post = $_POST;
	}

}
new MediaHub_Newsletter;
