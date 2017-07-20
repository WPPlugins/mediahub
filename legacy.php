<?php

// Loading version 1 of the plugin
require( 'v1-legacy/plugin.php' );

function mediahub_upgrade_notice() {

	// Bail out if user chose not to display upgrade notice
	if ( true == get_option( 'mediahub_no_upgrade_notice' ) ) {
		return;
	}

	// Display the uprade notice
	?>
	<div class="error">
		<p><strong><?php _e( 'Important upgrade notice:</strong> Version 4 of the MediaHub API is now available.', 'mediahub' ); ?></p>
		<p><?php _e( 'An update to the MediaHub API is available. To be able to update to the new API, you need new API-keys. Request them via <a href="mailto:servicedesk@demediahub.nl">servicedesk@demediahub.nl</a>.', 'mediahub' ); ?></p>
		<p>
			<a href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=mediahub_api' ), 'mediahub_upgrade', 'mediahub_upgrade' ) ); ?>" class="button button-primary">
				<?php _e( 'Click here to upgrade to the new API', 'mediahub' ); ?>
			</a>
		</p>
		<p><a href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=mediahub_api' ), 'mediahub_no_upgrade_notice', 'mediahub_no_upgrade_notice' ) ); ?>"><?php _e( "Don't show this notice again", 'mediahub' ); ?></a></p>
	</div>
	<?php
}
add_action( 'admin_notices', 'mediahub_upgrade_notice' );

/**
 * Display subtle notice for users who have chosen not to see the upgrade notice.
 * We still need to show something, so that those who choose not to see the notice, will still be able to upgrade.
 */
function mediahub_subtle_upgrade_notice() {

	// Only show if user has blocked upgrade notices
	if ( true != get_option( 'mediahub_no_upgrade_notice' ) ) {
		return;
	}

	// Display the uprade notice
	?>
	<p>
		<a href="<?php echo esc_url( wp_nonce_url( admin_url('options-general.php?page=mediahub_api' ), 'mediahub_upgrade', 'mediahub_upgrade' ) ); ?>">
			Click here to upgrade to the new version
		</a>
	</p><?php
}
add_action( 'mediahub_legacy_notices', 'mediahub_subtle_upgrade_notice' );

function mediahub_no_upgrade_notice_processing() {

	// Bail out if nonce not set
	if ( current_user_can( 'manage_options' ) && ( ! isset($_GET['mediahub_no_upgrade_notice'] ) || ! wp_verify_nonce( $_GET['mediahub_no_upgrade_notice'], 'mediahub_no_upgrade_notice' ) ) ) {
		return;
	}

	// Hide the upgrade notice
	update_option( 'mediahub_no_upgrade_notice', true );
}
add_action( 'admin_init', 'mediahub_no_upgrade_notice_processing' );

function mediahub_upgrade_processing() {

	// Bail out if nonce not set
	if ( current_user_can( 'manage_options' ) && ( ! isset($_GET['mediahub_upgrade'] ) || ! wp_verify_nonce( $_GET['mediahub_upgrade'], 'mediahub_upgrade' ) ) ) {
		return;
	}

	// Switch the API to use version 4
	$keys = get_option( 'mhca_api_key');
	$keys['mediahub_api_url'] = 'https://api.demediahub.nl/v4/';
	update_option( 'mhca_api_key', $keys );

}
add_action( 'admin_init', 'mediahub_upgrade_processing' );
