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
function edduh_plugin_update_1_6_0( $old_version, $new_version ) {
	if ( '1.6.0' === $new_version ) {
		// kickstart garbage collection
		edd_user_history()->deactivation();
		edd_user_history()->activation();
	}
}
add_action( 'edduh_plugin_update', 'edduh_plugin_update_1_6_0', 10, 2 );

add_action( 'edd_30_migrate_order', 'eeduh_30_migration', 10, 3 );
/**
 * During the EDD 3.0 migration, copies the user history from the old post metadata
 * to the new order meta table.
 *
 * If the user history is the only thing in the `payment_meta`, delete that metadata.
 * If anything else is left in the metadata, remove just the user history and update the order meta.
 *
 * @since 1.6.2
 * @param int   $order_id      The new order ID.
 * @param array $payment_meta  The original payment meta.
 * @param array $name          The original post meta.
 * @return void
 */
function eeduh_30_migration( $order_id, $payment_meta, $meta ) {
	$user_history = ! empty( $payment_meta['user_history'] ) ? $payment_meta['user_history'] : false;
	if ( ! $user_history ) {
		return;
	}

	edd_add_order_meta( $order_id, 'user_history', $user_history );
	$migrated_meta = edd_get_order_meta( $order_id, 'payment_meta', true );
	if ( $migrated_meta ) {
		unset( $migrated_meta['user_history'] );
	}
	if ( empty( $migrated_meta ) ) {
		edd_delete_order_meta( $order_id, 'payment_meta' );
	} else {
		edd_update_order_meta( $order_id, 'payment_meta', $migrated_meta );
	}
}
