<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update settings for 1.6.0
 *
 * @since  1.6.0
 *
 * @param  string $old_version Previous plugin version.
 * @param  string $new_version Current plugin version.
 */
function wcch_plugin_update_1_6_0( $old_version, $new_version ) {

	if ( '1.6.0' === $new_version ) {
		// kickstart garbage collection
		edd_user_history()->deactivation();
		edd_user_history()->activation();
	}

}
add_action( 'wcch_plugin_update', 'wcch_plugin_update_1_6_0', 10, 2 );
