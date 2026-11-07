<?php
/**
 * Uninstall handler.
 *
 * This is a shop's ledger, so records are kept by default. To delete the
 * table and settings, add this to wp-config.php before uninstalling:
 *
 *     define( 'IMEI_LEDGER_DELETE_DATA', true );
 *
 * @package ImeiPhoneLedger
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'IMEI_LEDGER_DELETE_DATA' ) || true !== IMEI_LEDGER_DELETE_DATA ) {
	return;
}

require_once __DIR__ . '/includes/class-imei-ledger-installer.php';

global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- Dropping the plugin's own table on explicit request.
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', Imei_Ledger_Installer::table_name() ) );

delete_option( Imei_Ledger_Installer::OPTION );
delete_option( Imei_Ledger_Installer::GRANTED_OPTION );
Imei_Ledger_Installer::remove_capability();
