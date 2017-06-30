<?php

/**
 * Parse search engine referrals for original search query
 *
 * @link   http://www.electrictoolbox.com/php-keywords-search-engine-referer-url-2/
 *
 * @since  1.0.0
 *
 * @param  string      $url URL to parse.
 * @return string|bool      Original search query if available, otherwise false.
 */
function rzen_edduh_get_users_search_query( $url = false ) {

	// If no URL is specified, bail here
	if( ! $url )
		return false;

	// Parse URL and look for standard query strings
	$parts_url = parse_url( $url );
	$query = isset( $parts_url['query'] )
		? $parts_url['query']
		: ( isset( $parts_url['fragment'] )
			? $parts_url['fragment']
			: ''
		);

	// If no query was found, bail here
	if( ! $query )
		return false;

	// Parse the query and return the user's search string
	parse_str( $query, $parts_query );
	return isset( $parts_query['q'] ) ? $parts_query['q'] : ( isset( $parts_query['p'] ) ? $parts_query['p'] : '' );

}

/**
 * Calculate time elapsed between two timestamps.
 *
 * @since  1.0.0
 *
 * @param  integer $original_time Older timestamp.
 * @param  integer $new_time      Newer timestamp.
 * @return string                 Time elapsed as 0d 00h 00m 00s.
 */
function rzen_edduh_calculate_elapsed_time( $original_time = 0, $new_time = 0 ) {

	// If no original time present, bail here
	if ( ! $original_time ) {
		return __( 'N/A', 'edduh' );
	}

	// If no new time present, use current time
	$new_time = $new_time ? $new_time : time();

	// Calculate elapsed time
	$elapsed_time = absint( absint( $new_time ) - absint( $original_time ) );

	// Output progressive amounts of detail
	if ( MINUTE_IN_SECONDS >= $elapsed_time ) {
		return sprintf(
			__( '%1$02ds', 'edduh' ),
			$elapsed_time % 60
		);
	} elseif ( HOUR_IN_SECONDS >= $elapsed_time ) {
		return sprintf(
			__( '%1$02dm %2$02ds', 'edduh' ),
			floor( $elapsed_time / MINUTE_IN_SECONDS % 60 ),
			$elapsed_time % 60
		);
	} elseif ( DAY_IN_SECONDS >= $elapsed_time ) {
		return sprintf(
			__( '%1$02dh %2$02dm %3$02ds', 'edduh' ),
			floor( $elapsed_time / HOUR_IN_SECONDS ),
			floor( $elapsed_time / MINUTE_IN_SECONDS % 60 ),
			$elapsed_time % 60
		);
	} else {
		return sprintf(
			__( '%1$d:%2$02d:%3$02d:%4$02d', 'edduh' ),
			floor( $elapsed_time / DAY_IN_SECONDS ),
			floor( $elapsed_time / HOUR_IN_SECONDS % 24 ),
			floor( $elapsed_time / MINUTE_IN_SECONDS % 60 ),
			$elapsed_time % 60
		);
	}

}

/**
 * Back Compat: Update older single-dimensional history array
 * to be multi-dimensional so that we don't break old history.
 *
 * @since  1.5.0
 *
 * @param  array $user_history User's browsing history.
 * @return array               Potentially updated history array.
 */
function rzen_edduh_normalize_history_array( $user_history = array() ) {

	// If the first item isn't an array, none of them are
	if ( is_array( $user_history ) && ! is_array( $user_history[0] ) ) {
		foreach ( $user_history as $key => $url ) {
			$user_history[ $key ] = array( 'url' => $url, 'time' => 0 );
		}
	}

	return $user_history;

}
