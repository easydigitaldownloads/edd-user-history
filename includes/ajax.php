<?php

/**
 * AJAX Helper to track user history.
 *
 * @since 1.6.0
 */
function edduh_ajax_track_history() {

	$page_url = isset( $_REQUEST['page_url'] ) ? esc_url( urldecode( $_REQUEST['page_url'] ) ) : false;
	$referrer = isset( $_REQUEST['referrer'] ) ? esc_url( urldecode( $_REQUEST['referrer'] ) ) : false;

	if ( $page_url ) {
		do_action( 'edduh_visited_url', $page_url, time(), $referrer );
	}

	wp_send_json_success( array( 'page_url' => $page_url ) );
}
add_action( 'wp_ajax_edduh_track_history', 'edduh_ajax_track_history' );
add_action( 'wp_ajax_nopriv_edduh_track_history', 'edduh_ajax_track_history' );
