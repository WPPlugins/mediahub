<?php

/**
 * Adding MediaHub specific CSS.
 */
class MediaHub_CSS {

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts',    array( $this, 'css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_css' ) );
	}

	/**
	 * Adding CSS for video grid display.
	 */
	public function css() {
		wp_register_style( 'mediahub', plugins_url( '/css/mh-grid.css' , dirname( __FILE__ ) ) );
		wp_enqueue_style( 'mediahub' );
	}

	/**
	 * Admin CSS.
	 */
	public function admin_css() {
		if ( isset( $_GET['page'] ) && 'mediahub_api' == $_GET['page'] ) {
			wp_register_style( 'mediahub_admin_css', plugins_url( '/css/admin.css' , dirname( __FILE__ ) ) );
			wp_enqueue_style( 'mediahub_admin_css' );
		}
	}

}
new MediaHub_CSS;
