<?php

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
function eddct_30_migration( $order_id, $payment_meta, $meta ) {
	$campaign_info = ! empty( $payment_meta['eddct_campaign'] ) ? $payment_meta['eddct_campaign'] : false;
	if ( ! $campaign_info ) {
		return;
	}

	edd_add_order_meta( $order_id, 'eddct_campaign', $campaign_info );
	$migrated_meta = edd_get_order_meta( $order_id, 'payment_meta', true );
	if ( is_array( $migrated_meta ) && ! empty( $migrated_meta ) ) {
		unset( $migrated_meta['eddct_campaign'] );
	}
	if ( empty( $migrated_meta ) ) {
		edd_delete_order_meta( $order_id, 'payment_meta' );
	} else {
		edd_update_order_meta( $order_id, 'payment_meta', $migrated_meta );
	}
}
add_action( 'edd_30_migrate_order', 'eddct_30_migration', 10, 3 );
