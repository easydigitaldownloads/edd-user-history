<?php

class EDDUH_Cookie_Helper {

	/**
	 * Tracking cookie name.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	static $cookie_name = 'edduh_hash';

	/**
	 * Tracking cookie duration.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	static $expiration_length = 604800; // 7 Days

	/**
	 * Save tracking cookie.
	 *
	 * @since 1.0.0
	 *
	 * @return string User's unique hash.
	 */
	public static function set_cookie() {
		$hash = uniqid();
		setcookie( self::$cookie_name, $hash, time() + self::$expiration_length, '/' );
		return $hash;
	}

	/**
	 * Get stored user hash from cookie.
	 *
	 * @since 1.0.0
	 *
	 * @return string User's unique hash.
	 */
	public static function get_cookie() {
		return isset( $_COOKIE[ self::$cookie_name ] ) && ! empty( $_COOKIE[ self::$cookie_name ] )
			? esc_attr( $_COOKIE[ self::$cookie_name ] )
			: self::set_cookie();
	}

	/**
	 * Delete tracking cookie.
	 *
	 * @since 1.0.0
	 */
	public static function delete_cookie() {
		setcookie( self::$cookie_name, '', time() - HOUR_IN_SECONDS, '/' );
	}

	/**
	 * Delete visitor's stored history.
	 *
	 * @since 1.1.0
	 */
	public static function delete_history_data() {
		wcch_delete_page_history( self::get_cookie() );
		self::delete_cookie();
	}
}
