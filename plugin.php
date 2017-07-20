<?php
/*
Plugin Name: MediaHub Content API
Plugin URI: https://forsite.media/
Description: Retrieves content from the MediaHub.
Author: Forsite Media
Version: 2.0.2
Author URI: https://forsite.media/
License: GPLv2
Text Domain: mediahub
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


/**
 * Initialize the plugin.
 */
class MediaHub_Init {

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'localization' ), 5 );
		add_action( 'plugins_loaded', array( $this, 'plugin_init' ), 6 );

		// Add Cron task
		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		$file = __FILE__;
		register_activation_hook( $file, array( $this, 'mh_activation' ) );
		register_deactivation_hook( $file, array( $this, 'mh_deactivation' ) );
	}

	/**
	 * Initialisation of the selected version of the plugin.
	 */
	public function plugin_init() {

		/**
		 * Load the plugin.
		 * Users of version 2 or 3 of the API will default to the legacy version.
		 * Everyone else will automatically load the latest version.
		 */
		$keys = get_option( 'mhca_api_key');
		if ( '' != $keys && ( strpos( $keys['mediahub_api_url'],'v3' ) !== false || strpos( $keys['mediahub_api_url'],'v2' ) !== false ) ) {
			require( 'legacy.php' );
		} else {
			require( 'v2/mediahub.php' );
		}
	}

	/*
	 * Setup localization for translations
	 */
	public function localization() {

		load_plugin_textdomain(
			'mediahub', // Unique identifier
			false, // Deprecated abs path
			dirname( plugin_basename( __FILE__ ) ) . '/languages/' // Languages folder
		);

	}

	/**
	 * On activation, set a time, frequency and name of an action hook to be scheduled.
	 * This is a replica of the method in version two, as the method is needed before 
	 * version two is initialised.
	 */
	public function mh_activation() {

		// first run = Now + 15 minutes
		$first_run_time = current_time ( 'timestamp' ) + 900;
		wp_schedule_event( $first_run_time, 'minutes15', 'mh_event_hook' );
	}

	/**
	 * On deactivation, remove all functions from the scheduled action hook.
	 * This is a replica of the method in version two, as the method is needed before 
	 * version two is initialised.
	 */
	public function mh_deactivation() {
		wp_clear_scheduled_hook( 'mh_event_hook' );
	}

	/**
	 * Adds 5, 10 and 15 minute cron schedules.
	 * This is a replica of the method in version two, as the method is needed before 
	 * version two is initialised.
	 *
	 * @param array   $schedules Cron schedule array
	 * @return array $schedules Amended cron schedule array
	 */
	public function cron_schedules( $schedules ) {

		$schedules['minutes5'] = array(
			'interval' => 300,
			'display'  => __( 'Every 5 minutes' )
		);
		$schedules['minutes10'] = array(
			'interval' => 600,
			'display'  => __( 'Every 10 minutes' )
		);
		$schedules['minutes15'] = array(
			'interval' => 900,
			'display'  => __( 'Every 15 minutes' )
		);
		return $schedules;
	}

}
new MediaHub_Init;
