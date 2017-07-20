<?php

if (
	!defined( 'WP_UNINSTALL_PLUGIN' )
||
	!WP_UNINSTALL_PLUGIN
||
	dirname( WP_UNINSTALL_PLUGIN ) != dirname( plugin_basename( __FILE__ ) )
) {
	status_header( 404 );
	exit;
}

delete_option( 'mhca_options' );
delete_option( 'mhca_api_key' );
delete_option( 'mhca_help' );
