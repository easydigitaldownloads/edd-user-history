<?php
/**
 * Plugin Name: Easy Digital Downloads User History
 * Plugin URI: http://easydigitaldownloads.com/extensions/
 * Description: Track and store customer browsing history with their order.
 * Version: 1.5.2
 * Author: Brian Richards
 * Author URI: http://rzen.net
 * License: GPL2
 * Text Domain: edduh
 * Domain Path: languages
 */

/*
Copyright 2013 rzen Media, LLC (email : brian@rzen.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

/**
 * Main plugin instantiation class.
 *
 * @since 1.0.0
 */
class EDD_User_History {

	/**
	 * Tracks current plugin version throughout codebase.
	 *
	 * @since x.x.x
	 * @var string
	 */
	var $version = '1.5.2';

	/**
	 * Fire up the engines.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Define plugin constants
		$this->basename       = plugin_basename( __FILE__ );
		$this->directory_path = plugin_dir_path( __FILE__ );
		$this->directory_url  = plugin_dir_url( __FILE__ );

		// Basic setup
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'admin_init', array( $this, 'licensed_updates' ), 9 );
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );

	} /* __construct() */

	/**
	 * Load localization.
	 *
	 * @since 1.5.0
	 */
	public function i18n() {
		load_plugin_textdomain( 'edduh', false, $this->directory_path . '/languages/' );

		// Set filter for plugin's languages directory
		$edduh_lang_dir = $this->directory_path . '/languages/';
		$edduh_lang_dir = apply_filters( 'edduh_languages_directory', $edduh_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'edduh' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'edduh', $locale );

		// Setup paths to current locale file
		$mofile_local  = $edduh_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/edd/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/edduh folder
			load_textdomain( 'edduh', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
			load_textdomain( 'edduh', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'edduh', false, $edduh_lang_dir );
		}

	} /* i18n() */

	/**
	 * Include file dependencies.
	 *
	 * @since 1.5.0
	 */
	public function includes() {
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . '/includes/class.EDDUH_Cookie_Helper.php' );
			require_once( $this->directory_path . '/includes/utilities.php' );
			require_once( $this->directory_path . '/includes/track-history.php' );
			require_once( $this->directory_path . '/includes/show-history.php' );
		}
	} /* includes() */

	/**
	 * Register EDD License
	 *
	 * @since 1.5.0
	 */
	public function licensed_updates() {
		if ( class_exists( 'EDD_License' ) ) {
			$license = new EDD_License( __FILE__, 'User History', $this->version, 'Brian Richards' );
		}
	} /* licensed_updates() */

	/**
	 * Check if all requirements are met.
	 *
	 * @since 1.4.0
	 *
	 * @return bool True if requirements are met, otherwise false.
	 */
	private function meets_requirements() {
		if ( function_exists( 'EDD' ) && defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '1.9.0', '>=' ) ) {
			return true;
		} else {
			return false;
		}
	} /* meets_requirements() */

	/**
	 * Output error message and disable plugin if requirements are not met.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.4.0
	 */
	public function maybe_disable_plugin() {
		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'EDD User History requires Easy Digital Downloads 1.9.0 or greater and has been <a href="%s">deactivated</a>.', 'edduh' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}
	} /* maybe_disable_plugin() */

}
$GLOBALS['edd_user_history'] = new EDD_User_History;
