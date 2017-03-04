<?php
/**
 * Functionality for tracking and saving customer history.
 *
 * @package EDD User History
 * @author rzen Media, LLC
 * @license GPL2
 * @link https://rzen.net
 */

class EDDUH_Track_History {

	/**
	 * Fire up the engines.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		// Connect to EDD
		add_action( 'template_redirect', array( $this, 'update_user_history' ) );
		add_action( 'edd_payment_meta', array( $this, 'save_user_history' ) );

		// Uncomment the following action to enable devmode
		// add_action( 'get_header', array( $this, 'devmode' ) );

	} /* __construct() */

	/**
	 * Returning user history from session.
	 *
	 * @since 1.5.0
	 */
	private function get_user_history() {

		$user_history = EDDUH_Cookie_Helper::get_cookie();

		// If user has an established history, return that
		if ( ! empty( $user_history ) ) {
			return (array) $user_history;

		// Otherwise, return an array with the original referrer
		} else {
			$referrer = isset( $_SERVER['HTTP_REFERER'] )
				? $_SERVER['HTTP_REFERER']
				: __( 'Direct Traffic', 'wcuh' );

			return array( array( 'url' => $referrer, 'time' => time() ) );
		}

	} /* get_user_history() */

	/**
	 * Initialize tracking of user's browsing history
	 *
	 * @since 1.5.0
	 */
	public function update_user_history() {

		// Only log good URLs
		if ( ! is_404() ) {

			// Grab user history from the current session
			$history = $this->get_user_history();

			// Add the current page to the user's history
			$protocol  = ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
			$page_url  = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			$history[] = array( 'url' => $page_url, 'time' => time() );

			// Push the updated history to the current session
			EDDUH_Cookie_Helper::set_cookie( $history );
		}

	} /* update_user_history() */

	/**
	 * Save user history as payment meta.
	 *
	 * @since 1.5.0
	 *
	 * @param array $payment_meta Payment meta information.
	 */
	public function save_user_history( $payment_meta ) {

		// Grab user history from the current session
		$user_history = $this->get_user_history();

		// If user history was captured, sanitize and store the URLs
		if ( is_array( $user_history ) && ! empty( $user_history ) ) {

			// Setup a clean, safe array for the database
			$sanitized_history = array();

			// Sanitize the referrer a bit differently
			// than the rest because it may not be a URL.
			$referrer = array_shift( $user_history );
			$sanitized_history[] = array(
				'url'  => sanitize_text_field( $referrer->url ),
				'time' => absint( $referrer->time ),
			);

			// Sanitize each additional URL
			foreach ( $user_history as $history ) {
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

			// Store sanitized history as post meta
			$payment_meta['user_history'] = $sanitized_history;
			EDDUH_Cookie_Helper::delete_cookie();

		// Otherwise, no history was collected (weird)
		} else {
			$payment_meta['user_history'] = __( 'No page history collected.', 'edduh' );
		}

		return $payment_meta;

	} /* save_user_history() */

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
		if ( isset($_GET['devmode']) && 'true' == $_GET['devmode'] ) {

			// Output user history if URL querystring contains 'output=history'
			if ( isset($_GET['output']) && 'history' == $_GET['output'] ) {
				echo '<pre>' . print_r( $this->get_user_history(), 1 ) . '</pre>';
			}

			// Output user history cookie if URL querystring contains 'output=cookie'
			if ( isset($_GET['output']) && 'cookie' == $_GET['output'] ) {
				echo '<pre>' . print_r( $_COOKIE, 1 ) . '</pre>';
			}

			// Clear user_history and dump us back at the homepage if URL querystring contains 'history=reset'
			if ( isset($_GET['history']) && 'reset' == $_GET['history'] ) {
				EDDUH_Cookie_Helper::delete_cookie();
				wp_redirect( site_url() );
				exit;
			}

		}

	} /* devmode() */

}
return new EDDUH_Track_History;
