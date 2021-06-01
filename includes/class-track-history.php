<?php
/**
 * Functionality for tracking and saving customer history.
 *
 * @package EDD User History
 * @author  Easy Digital Downloads, LLC
 * @license GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class EDDUH_Track_History {

	/**
	 * Fire up the engines.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {
		add_action( 'edduh_visited_url', array( $this, 'update_customer_history' ), 10, 3 );
		add_filter( 'edd_payment_meta', array( $this, 'save_customer_history' ) );
		add_action( 'edd_built_order', array( $this, 'add_user_history_order_meta' ) );

		// Uncomment the following action to enable devmode
		// add_action( 'get_header', array( $this, 'devmode' ) );
	}

	/**
	 * Get customer's tracked browsing history.
	 *
	 * @since    1.5.0
	 *
	 * @referrer string The Referrer for ths user
	 */
	private function get_customer_history( $referrer = '' ) {
		$user_hash        = EDDUH_Cookie_Helper::get_cookie();
		$customer_history = edduh_get_page_history( $user_hash );

		// If user has an established history, return that
		if ( ! empty( $customer_history ) ) {
			return (array) $customer_history;

			// Otherwise, return an array with the original referrer
		} else {
			$referrer = esc_url( $referrer ) ? $referrer : __( 'Direct Traffic', 'edduh' );

			return array( array( 'url' => $referrer, 'time' => time() ) );
		}
	}

	/**
	 * Update customer's tracked browsing history.
	 *
	 * @since 1.5.0
	 */
	public function update_customer_history( $page_url = '', $timestamp = 0, $referrer = '' ) {
		// Grab browsing history from the current session
		$history   = $this->get_customer_history( $referrer );
		$history[] = array( 'url' => esc_url( $page_url ), 'time' => absint( $timestamp ) );

		// Push the updated history to the current session
		$user_hash = EDDUH_Cookie_Helper::get_cookie();
		edduh_set_page_history( $user_hash, $history );
	}

	/**
	 * Save user history as payment meta.
	 *
	 * @since 1.5.0
	 *
	 * @param  array $payment_meta EDD Payment meta information.
	 *
	 * @return array $payment_meta
	 */
	public function save_customer_history( $payment_meta ) {

		// In EDD 3.0, this is handled by the add_user_history_order_meta method.
		if ( function_exists( 'edd_add_order_meta' ) ) {
			return $payment_meta;
		}
		// Bail early if not on the purchase screen
		// Fixes issue as result of https://github.com/easydigitaldownloads/easy-digital-downloads/issues/5490#issuecomment-283978899
		if ( ! did_action( 'edd_purchase' ) ) {
			return $payment_meta;
		}

		// Grab browsing history from the current session
		$customer_history = $this->get_sanitized_history();

		if ( ! empty( $customer_history ) ) {
			// Store sanitized history as post meta
			$payment_meta['user_history'] = $customer_history;
		}
		EDDUH_Cookie_Helper::delete_cookie();

		return $payment_meta;
	}

	/**
	 * Adds the user history to the order meta in EDD 3.0.
	 *
	 * @since 1.6.2
	 * @param int $order_id
	 * @return void
	 */
	public function add_user_history_order_meta( $order_id ) {
		// Grab browsing history from the current session
		$customer_history = $this->get_sanitized_history();

		if ( ! empty( $customer_history ) ) {
			// Store sanitized history as post meta
			edd_add_order_meta( $order_id, 'user_history', $customer_history );
		}
		EDDUH_Cookie_Helper::delete_cookie();
	}

	/**
	 * Gets the user history as a sanitized array of data.
	 *
	 * @since 1.6.2
	 * @return array
	 */
	private function get_sanitized_history() {
		// Setup a clean, safe array for the database
		$sanitized_history = array();

		// Grab browsing history from the current session
		$customer_history = $this->get_customer_history();

		// If browsing history was captured, sanitize and store the URLs
		if ( ! is_array( $customer_history ) || empty( $customer_history ) ) {
			return $sanitized_history;
		}

		// Sanitize the referrer a bit differently
		// than the rest because it may not be a URL.
		$referrer = array_shift( $customer_history );
		if ( is_object( $referrer ) ) {
			$sanitized_history[] = array(
				'url'  => sanitize_text_field( $referrer->url ),
				'time' => absint( $referrer->time ),
			);
		}

		// Sanitize each additional URL
		foreach ( $customer_history as $history ) {
			$sanitized_history[] = array(
				'url'  => esc_url_raw( $history->url ),
				'time' => absint( $history->time ),
			);
		}

		// Add one final timestamp for order complete
		$sanitized_history[] = array(
			'url'  => __( 'Order Complete', 'edduh' ),
			'time' => time(),
		);

		return $sanitized_history;
	}

	/**
	 * Handle developer debug data.
	 *
	 * Usage: Hook to get_header, append "?devmode=true" to any front-end URL.
	 * To view tracked history, add "&output=history".
	 * To view session object, add "&output=session".
	 * To reset tracked history, add "&reset=history".
	 *
	 * @since 1.5.0
	 */
	public function devmode() {
		// Only proceed if URL querystring cotnains "devmode=true"
		if ( defined( 'EDD_DEBUG' ) && EDD_DEBUG && isset( $_GET['devmode'] ) && 'true' == $_GET['devmode'] ) {

			// Output user history if URL querystring contains 'output=history'
			if ( isset( $_GET['output'] ) && 'history' == $_GET['output'] ) {
				var_dump( EDDUH_Cookie_Helper::get_cookie() );
				echo '<pre>' . print_r( $this->get_customer_history(), 1 ) . '</pre>';
			}

			// Output user history cookie if URL querystring contains 'output=cookie'
			if ( isset( $_GET['output'] ) && 'cookie' == $_GET['output'] ) {
				echo '<pre>' . print_r( $_COOKIE, 1 ) . '</pre>';
			}

			// Clear customer history and dump us back at the homepage if URL querystring contains 'history=reset'
			if ( isset( $_GET['history'] ) && 'reset' == $_GET['history'] ) {
				EDDUH_Cookie_Helper::delete_history_data();
				wp_redirect( site_url() );
				exit;
			}

		}
	}
}
