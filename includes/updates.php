<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Update settings for x.x.x
 *
 * @since  x.x.x
 *
 * @param  string $old_version Previous plugin version.
 * @param  string $new_version Current plugin version.
 */
function wcch_plugin_update_x_x_x( $old_version, $new_version ) {

	if ( 'x.x.x' === $new_version ) {
		// kickstart garbage collection
		edd_user_history()->deactivation();
		edd_user_history()->activation();
	}

}
add_action( 'wcch_plugin_update', 'wcch_plugin_update_x_x_x', 10, 2 );
