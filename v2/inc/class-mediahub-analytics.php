<?php

/**
 * Adding analytics tracking code.
 * Allows MediaHub to track activity on websites running the plugin.
 */
class MediaHub_Analytics extends MediaHub_Core {

	const MEDIA_UA_CODE = 'UA-53708342-1';

	/**
	 * Class constructor.
	 */
	function __construct() {
		add_action( 'wp_head',                       array( $this, 'manual_analytics_code' ) );

		add_filter( 'yoast-ga-push-array-universal', array( $this, 'yoast_analytics_code' ) );
	}

	/**
	 * Manually added Google Analytics tracking code.
	 * This tracking code will be used when Yoast's plugin is not activated.
	 */
	public function manual_analytics_code() {

		// If Yoast's plugin is activated, then we'll use that instead
		if ( class_exists( 'Yoast_GA_Admin' ) ) {
			return;
		}

		if ( '' != get_post_meta( get_the_ID(), self::META_KEY, true ) ) {
			$note_id_addition = "\n_gaq.push(['_setCustomVar',1, 'note_id', '" . absint( get_post_meta( get_the_ID(), self::META_KEY, true ) ) . "']);";
		} else {
			$note_id_addition = '';
		}

		echo "\n<script>
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '" . self::MEDIA_UA_CODE . "']);" . $note_id_addition . "
_gaq.push(['_trackPageview']);
(function() {
var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>\n";
	}

	/**
	 * Adding new push code for Yoast's plugin.
	 * Avoids having to load multiple instances of Google Analytics on one page.
	 *
	 * @param   array   $gaq_push   Controls push codes for Google Analytics
	 * @return  array               Push codes, with added secondary tracker
	 */
	public function yoast_analytics_code( $gaq_push ) {

		// If Yoast's plugin isn't activated, then don't run this
		if ( ! class_exists( 'Yoast_GA_Admin' ) ) {
			return;
		}

		$tracker_name = 'MediaHubTracker';
		$gaq_push[] = "'create', '" . self::MEDIA_UA_CODE . "', 'auto', {'name': '$tracker_name'}";
		$gaq_push[] = "'set', 'forceSSL', true, {'name': '$tracker_name'}";
		$gaq_push[] = "'$tracker_name.send', 'pageview'";


		if ( '' != get_post_meta( get_the_ID(), self::META_KEY, true ) ) {
			$gaq_push[] = "'set', 'CustomVar', 1, {'note_id': '" . get_post_meta( get_the_ID(), self::META_KEY, true ) . "'}";
		}

		return $gaq_push;
	}

}
new MediaHub_Analytics;
