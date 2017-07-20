<?php

/**
 * weDevs Settings API wrapper class
 *
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link http://tareq.weDevs.com Tareq's Planet
 * @example settings-api.php How to use the class
 */
if ( !class_exists( 'MH_Settings_API' ) ):
class MH_Settings_API {

	/**
	 * settings sections array
	 *
	 * @var array
	 */
	private $settings_sections = array();

	/**
	 * Settings fields array
	 *
	 * @var array
	 */
	private $settings_fields = array();

	/**
	 * Singleton instance
	 *
	 * @var object
	 */
	private static $_instance;

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts and styles
	 */
	function admin_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'media-upload' );
		wp_enqueue_script( 'thickbox' );
	}

	/**
	 * Set settings sections
	 *
	 * @param array   $sections setting sections array
	 */
	function set_sections( $sections ) {
		$this->settings_sections = $sections;

		return $this;
	}

	/**
	 * Add a single section
	 *
	 * @param array   $section
	 */
	function add_section( $section ) {
		$this->settings_sections[] = $section;

		return $this;
	}

	/**
	 * Set settings fields
	 *
	 * @param array   $fields settings fields array
	 */
	function set_fields( $fields ) {
		$this->settings_fields = $fields;

		return $this;
	}

	function add_field( $section, $field ) {
		$defaults = array(
			'name' => '',
			'label' => '',
			'desc' => '',
			'type' => 'text'
		);

		$arg = wp_parse_args( $field, $defaults );
		$this->settings_fields[$section][] = $arg;

		return $this;
	}

	/**
	 * Initialize and registers the settings sections and fileds to WordPress
	 *
	 * Usually this should be called at `admin_init` hook.
	 *
	 * This function gets the initiated settings sections and fields. Then
	 * registers them to WordPress and ready for use.
	 */
	function admin_init() {
		//register settings sections
		foreach ( $this->settings_sections as $section ) {
			if ( false == get_option( $section['id'] ) ) {
				add_option( $section['id'] );
			}

			if ( isset($section['desc']) && !empty($section['desc']) ) {
				$section['desc'] = '<div class="inside">'.$section['desc'].'</div>';
				$callback = create_function('', 'echo "'.str_replace('"', '\"', $section['desc']).'";');
			} else {
				$callback = '__return_false';
			}

			add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
		}

		//register settings fields
		foreach ( $this->settings_fields as $section => $field ) {
			foreach ( $field as $option ) {

				$type = isset( $option['type'] ) ? $option['type'] : 'text';

				// Only creating setting if option exists
				if ( isset( $option['name'] ) ) {
					$args = array(
						'id' => $option['name'],
						'desc' => isset( $option['desc'] ) ? $option['desc'] : '',
						'name' => $option['label'],
						'section' => $section,
						'size' => isset( $option['size'] ) ? $option['size'] : null,
						'options' => isset( $option['options'] ) ? $option['options'] : '',
						'std' => isset( $option['default'] ) ? $option['default'] : '',
						'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
					);
					add_settings_field( $section . '[' . $option['name'] . ']', $option['label'], array( $this, 'callback_' . $type ), $section, $section, $args );
				}
			}
		}

		// Save settings
		foreach ( $this->settings_sections as $key => $section ) {
			if ( isset( $_POST[$section['id']] ) ) {
				$option_name = wp_kses_post( $section['id'] );

				$settings = $_POST[$section['id']];
				foreach ( $settings as $option_key => $setting ) {
					$option_key = wp_kses_post( $option_key );

					if ( is_array( $setting ) ) {
						foreach ( $setting as $x => $xk ) {
							$x = wp_kses_post( $x );
							$setting[$x] = wp_kses_post( $xk );
						}
					} else {
						$setting = wp_kses_post( $setting );
					}

					$settings[$option_key] = $setting;
				}

				update_option( $option_name, $settings );
			}
		}
	}

	/**
	 * Displays a text field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_text( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a checkbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_checkbox( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

		$html = sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
		$html .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s[%2$s]" name="%1$s[%2$s]" value="on"%4$s />', $args['section'], $args['id'], $value, checked( $value, 'on', false ) );
		$html .= sprintf( '<label for="%1$s[%2$s]"> %3$s</label>', $args['section'], $args['id'], $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_multicheck( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

		$html = '';
		foreach ( $args['options'] as $key => $label ) {
			$checked = isset( $value[$key] ) ? $value[$key] : '0';
			$html .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
			$html .= sprintf( '<label for="%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
		}
		$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_cat2cat( $args ) {

		// var_dump( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );
		$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

		if ( ! is_array( $value ) ) {
			$value = array();
		}

		$cat_args = array(
		  'orderby' => 'name',
		  'order' => 'ASC',
		  'hide_empty' => 0,
		);
		$categories = get_categories( $cat_args );

		$html = '';
		foreach ( $args['options'] as $key => $label ) {

			$html .= sprintf( '<select id="%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s][category]" />', $args['section'], $args['id'], $key );

			$html .= '<option value="0"></option>';
			$html .= '<option value="new-mediahub-category|' . $key . '|' . $label . '"> - '. __( 'add as new category', 'mediahub' ) . ' - </option>';

			foreach ($categories as $category) {

				// Setting variable to prevent debug notices
				if ( ! isset( $value[$key]['category'] ) ) {
					$value[$key]['category'] = '';
				}

				$html .= sprintf(
					'<option value="%s"%s>%s</option>',
					$category->cat_ID,
					selected(
						$value[$key]['category'],
						$category->cat_ID,
						false
					),
					$category->name
				);
			}
			$html .= '</select>';

			if ( ! isset( $value[$key]['import_article'] ) ) {
				$value[$key]['import_article'] = 0;
			}
			if ( ! isset( $value[$key]['import_video'] ) ) {
				$value[$key]['import_video'] = 0;
			}
			if ( ! isset( $value[$key]['import_audio'] ) ) {
				$value[$key]['import_audio'] = 0;
			}

			$html .= sprintf( ' <label for="cb_%1$s[%2$s][%3$s][import_article]"><input type="checkbox" class="checkbox mh-filter" id="cb_%1$s[%2$s][%3$s][import_article]" name="%1$s[%2$s][%3$s][import_article]" value="1" %4$s /><span class="dashicons dashicons-media-text"></span></label>', $args['section'], $args['id'], $key, checked( $value[$key]['import_article'], 1, false ) );
			$html .= sprintf( ' <label for="cb_%1$s[%2$s][%3$s][import_video]"><input type="checkbox" class="checkbox mh-filter" id="cb_%1$s[%2$s][%3$s][import_video]" name="%1$s[%2$s][%3$s][import_video]" value="1" %4$s /><span class="dashicons dashicons-video-alt3"></span></label>', $args['section'], $args['id'], $key, checked( $value[$key]['import_video'], 1, false ) );
			$html .= sprintf( ' <label for="cb_%1$s[%2$s][%3$s][import_audio]"><input type="checkbox" class="checkbox mh-filter" id="cb_%1$s[%2$s][%3$s][import_audio]" name="%1$s[%2$s][%3$s][import_audio]" value="1" %4$s /><span class="dashicons dashicons-format-audio"></span></label>', $args['section'], $args['id'], $key, checked( $value[$key]['import_audio'], 1, false ) );
			$html .= sprintf( ' <label for="%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
		}
		$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a multicheckbox a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_radio( $args ) {

		$value = $this->get_option( $args['id'], $args['section'], $args['std'] );

		$html = '';
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<input type="radio" class="radio" id="%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s"%4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
			$html .= sprintf( '<label for="%1$s[%2$s][%4$s]"> %3$s</label><br>', $args['section'], $args['id'], $label, $key );
		}
		$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a selectbox for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_select( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );
		foreach ( $args['options'] as $key => $label ) {
			$html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
		}
		$html .= sprintf( '</select>' );
		$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_textarea( $args ) {

		$value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value );
		$html .= sprintf( '<br><span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a textarea for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_html( $args ) {
		echo $args['desc'];
	}

	/**
	 * Displays a rich text textarea for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_wysiwyg( $args ) {

		$value = wpautop( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';

		echo '<div style="width: ' . $size . ';">';

		wp_editor( $value, $args['section'] . '[' . $args['id'] . ']', array( 'teeny' => true, 'textarea_rows' => 10 ) );

		echo '</div>';

		echo sprintf( '<br><span class="description"> %s</span>', $args['desc'] );
	}

	/**
	 * Displays a file upload field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_file( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
		$id = $args['section']  . '[' . $args['id'] . ']';
		$js_id = $args['section']  . '\\\\[' . $args['id'] . '\\\\]';
		$html = sprintf( '<input type="text" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= '<input type="button" class="button wpsf-browse" id="'. $id .'_button" value="Browse" />
		<script type="text/javascript">
		jQuery(document).ready(function($){
			$("#'. $js_id .'_button").click(function() {
				tb_show("", "media-upload.php?post_id=0&amp;type=image&amp;TB_iframe=true");
				window.original_send_to_editor = window.send_to_editor;
				window.send_to_editor = function(html) {
					var url = $(html).attr(\'href\');
					if ( !url ) {
						url = $(html).attr(\'src\');
					};
					$("#'. $js_id .'").val(url);
					tb_remove();
					window.send_to_editor = window.original_send_to_editor;
				};
				return false;
			});
		});
		</script>';
		$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Displays a password field for a settings field
	 *
	 * @param array   $args settings field args
	 */
	function callback_password( $args ) {

		$value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
		$size = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
		$html .= sprintf( '<span class="description"> %s</span>', $args['desc'] );

		echo $html;
	}

	/**
	 * Resetting the Cron schedule.
	 */
	public function reset_cron( $time ) {

		if ( 'minutes5' == $time ) {
			$offset = 300;
		}
		elseif ( 'minutes10' == $time ) {
			$offset = 600;
		}
		// default is minutes15
		else {
			$offset = 900;
		}

		$first_run_time = current_time ( 'timestamp' ) + $offset;

		wp_clear_scheduled_hook( 'mh_event_hook' );
		wp_schedule_event( $first_run_time, $time, 'mh_event_hook' );
	}

	/**
	 * Sanitize callback for Settings API
	 */
	function sanitize_options( $options ) {

		if ( $options ) {
			foreach( $options as $option_slug => $option_value ) {
				$sanitize_callback = $this->get_sanitize_callback( $option_slug );

				// If callback is set, call it
				if ( $sanitize_callback ) {
					$options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
					continue;
				}

				// Treat everything that's not an array as a string
				if ( !is_array( $option_value ) ) {
					$options[ $option_slug ] = sanitize_text_field( $option_value );
					continue;
				}
			}
		}

		if ( isset( $options['cron_interval'] ) ) {
			$this->reset_cron( $options['cron_interval'] );
		}

		return $options;
	}

	/**
	 * Get sanitization callback for given option slug
	 *
	 * @param string $slug option slug
	 *
	 * @return mixed string or bool false
	 */
	function get_sanitize_callback( $slug = '' ) {
		if ( empty( $slug ) ) {
			return false;
		}
		// Iterate over registered fields and see if we can find proper callback
		foreach( $this->settings_fields as $section => $options ) {
			foreach ( $options as $option ) {
				if ( isset( $option['name'] ) && $option['name'] != $slug ) {
					continue;
				}
				// Return the callback name
				return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
			}
		}
		return false;
	}

	/**
	 * Get the value of a settings field
	 *
	 * @param string  $option  settings field name
	 * @param string  $section the section name this field belongs to
	 * @param string  $default default text if it's not found
	 * @return string
	 */
	function get_option( $option, $section, $default = '' ) {

		$options = get_option( $section );

		if ( isset( $options[$option] ) ) {
			return $options[$option];
		}

		return $default;
	}

	/**
	 * Show navigations as tab
	 *
	 * Shows all the settings section labels as tab
	 */
	function show_navigation() {
		$html = '<h2 class="nav-tab-wrapper">';

		foreach ( $this->settings_sections as $tab ) {
			$html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
		}

		$html .= '</h2>';

		echo $html;
	}

	/**
	 * Show the section settings forms
	 *
	 * This function displays every sections in a different form
	 */
	function show_forms() {
		?>
		<div class="metabox-holder">
			<div class="postbox">
				<?php foreach ( $this->settings_sections as $form ) { ?>
					<div id="<?php echo $form['id']; ?>" class="group">
						<form method="post" action="">

							<?php do_action( 'wsa_form_top_' . $form['id'], $form ); ?>
							<?php settings_fields( $form['id'] ); ?>
							<?php do_settings_sections( $form['id'] ); ?>
							<?php do_action( 'wsa_form_bottom_' . $form['id'], $form ); ?>

							<div style="padding-left: 10px">
								<?php submit_button(); ?>
							</div>
						</form>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
		$this->script();
	}

	/**
	 * Tabbable JavaScript codes
	 *
	 * This code uses localstorage for displaying active tabs
	 */
	function script() {
		?>
		<script>
			jQuery(document).ready(function($) {
				// Switches option sections
				$('.group').hide();
				var activetab = '';
				if (typeof(localStorage) != 'undefined' ) {
					activetab = localStorage.getItem("activetab");
				}
				if (activetab != '' && $(activetab).length ) {
					$(activetab).fadeIn();
				} else {
					$('.group:first').fadeIn();
				}
				$('.group .collapsed').each(function(){
					$(this).find('input:checked').parent().parent().parent().nextAll().each(
					function(){
						if ($(this).hasClass('last')) {
							$(this).removeClass('hidden');
							return false;
						}
						$(this).filter('.hidden').removeClass('hidden');
					});
				});

				if (activetab != '' && $(activetab + '-tab').length ) {
					$(activetab + '-tab').addClass('nav-tab-active');
				}
				else {
					$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
				}
				$('.nav-tab-wrapper a').click(function(evt) {
					$('.nav-tab-wrapper a').removeClass('nav-tab-active');
					$(this).addClass('nav-tab-active').blur();
					var clicked_group = $(this).attr('href');
					if (typeof(localStorage) != 'undefined' ) {
						localStorage.setItem("activetab", $(this).attr('href'));
					}
					$('.group').hide();
					$(clicked_group).fadeIn();
					evt.preventDefault();
				});
			});
		</script>
		<?php
	}

}
endif;
