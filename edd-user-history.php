<?php
/**
 * Plugin Name: Easy Digital Downloads User History
 * Plugin URI: http://easydigitaldownloads.com/extensions/
 * Description: Track and store customer browsing history with their order.
 * Version: 1.6.0
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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin instantiation class.
 *
 * @since 1.0.0
 */
class EDD_User_History {

	/**
	 * Track instance of the EDD_User_History class.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	protected static $_instance = null;

	/**
	 * Tracks current plugin version throughout codebase.
	 *
	 * @since 1.6.0
	 * @var string
	 */
	var $version = '1.6.0';

	/**
	 * Main EDD_User_History Instance
	 *
	 * Ensures only one instance of EDD_User_History is loaded or can be loaded.
	 *
	 * @since 1.2.0
	 *
	 * @return EDD_User_History - Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Fire up the engines.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Define plugin constants
		$this->plugin_file    = __FILE__;
		$this->basename       = plugin_basename( $this->plugin_file );
		$this->directory_path = plugin_dir_path( $this->plugin_file );
		$this->directory_url  = plugin_dir_url( $this->plugin_file );

		// Register EDD license updates
		add_action( 'admin_init', array( $this, 'licensed_updates' ), 9 );

		// Handle plugin activation and deactivation
		register_activation_hook( $this->plugin_file, array( $this, 'activation' ) );
		register_deactivation_hook( $this->plugin_file, array( $this, 'deactivation' ) );

		// Basic setup
		add_action( 'plugins_loaded', array( $this, 'i18n' ) );
		add_action( 'plugins_loaded', array( $this, 'includes' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'admin_notices', array( $this, 'maybe_disable_plugin' ) );
		add_action( 'plugins_loaded', array( $this, 'maybe_update_plugin' ) );

	}

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
	 * Plugin activation hook.
	 *
	 * @since  1.6.0
	 */
	public function activation() {
		$this->includes();
		edduh_schedule_garbage_collection();
	}

	/**
	 * Plugin deactivation hook.
	 *
	 * @since  1.6.0
	 */
	public function deactivation() {
		$this->includes();
		edduh_unschedule_garbage_collection();
	}

	/**
	 * Load localization.
	 *
	 * @since 1.0.0
	 */
	public function i18n() {
		load_plugin_textdomain( 'edduh', false, $this->directory_path . '/languages/' );
	} /* i18n() */

	/**
	 * Include file dependencies.
	 *
	 * @since 1.0.0
	 */
	public function includes() {
		if ( $this->meets_requirements() ) {
			require_once( $this->directory_path . 'includes/utilities.php' );
			require_once( $this->directory_path . 'includes/database.php' );
			require_once( $this->directory_path . 'includes/ajax.php' );
			require_once( $this->directory_path . 'includes/class.EDDUH_Cookie_Helper.php' );
			require_once( $this->directory_path . 'includes/track-history.php' );
			require_once( $this->directory_path . 'includes/show-history.php' );
			require_once( $this->directory_path . 'includes/settings.php' );
		}
	} /* includes() */

	/**
	 * Register JS files.
	 *
	 * @since 1.6.0
	 */
	public function load_scripts() {
		wp_enqueue_script( 'edduh-tracking', $this->directory_url . 'assets/js/tracking.js', array( 'jquery' ), '1.2.0' );
		wp_localize_script( 'edduh-tracking', 'edduh', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'currentUrl' => home_url( add_query_arg( null, null ) ),
		) );
	}

	/**
	 * Output error message and disable plugin if requirements are not met.
	 *
	 * This fires on admin_notices.
	 *
	 * @since 1.0.0
	 */
	public function maybe_disable_plugin() {

		if ( ! $this->meets_requirements() ) {
			// Display our error
			echo '<div id="message" class="error">';
			echo '<p>' . sprintf( __( 'EDD User History requires Easy Digital Downloads 1.9.0 or greater and has been <a href="%s">deactivated</a>. Please install, activate, or update Easy Digital Downloads and then reactivate this plugin.', 'edduh' ), admin_url( 'plugins.php' ) ) . '</p>';
			echo '</div>';

			// Deactivate our plugin
			deactivate_plugins( $this->basename );
		}

	} /* maybe_disable_plugin() */

	/**
	 * Check if all plugin requirements are met.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if requirements are met, otherwise false.
	 */
	private function meets_requirements() {
		return ( function_exists( 'EDD' ) && defined( 'EDD_VERSION' ) && version_compare( EDD_VERSION, '1.9.0', '>=' ) );
	} /* meets_requirements() */

	/**
	 * Run an update routine for the plugin.
	 *
	 * @since 1.6.0
	 */
	function maybe_update_plugin() {

		// Bail early if not on an admin page
		if ( ! is_admin() ) {
			return;
		}

		// Get the stored and current plugin database versions
		$stored_db_version = get_option( 'edduh_plugin_db_version', '0.0.0' );

		// Only trigger updates when stored version is lower than current version
		if ( version_compare( $stored_db_version, $this->version, '<' ) ) {
			require_once( $this->directory_path . '/includes/updates.php' );
			do_action( 'edduh_plugin_update', $stored_db_version, $this->version );
			update_option( 'edduh_plugin_db_version', $this->version );
		}

	} /* maybe_update_plugin() */
}

/**
 * Returns the main instance of edduh.
 *
 * @since  1.6.0
 * @return WooCommerce
 */
function edd_user_history() {
	return EDD_User_History::instance();
}
edd_user_history();
