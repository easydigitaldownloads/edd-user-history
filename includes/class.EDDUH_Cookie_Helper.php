<?php

class EDDUH_Cookie_Helper {

	static $cookie_name = 'edd_user_history';

	/**
	 * Store history data to cookie.
	 *
	 * @since 1.0.0
	 *
	 * @param array $data History data.
	 */
	public static function set_cookie( $data = array() ) {
		setcookie( self::$cookie_name, json_encode( $data ), time() + ( 7 * DAY_IN_SECONDS ), '/' );
	}

	/**
	 * Get stored history data.
	 *
	 * @since  1.00
	 *
	 * @return array Stored history data, or empty array.
	 */
	public static function get_cookie() {
		return isset( $_COOKIE[ self::$cookie_name ] ) && ! empty( $_COOKIE[ self::$cookie_name ] ) ? json_decode( stripslashes( $_COOKIE[ self::$cookie_name ] ) ) : array();
	}

	/**
	 * Delete stored history data.
	 *
	 * @since 1.0.0
	 */
	public static function delete_cookie() {
		setcookie( self::$cookie_name, '', time() - HOUR_IN_SECONDS, '/' );
	}
}
