<?php
/**
 * Creates the records table and grants the capability.
 *
 * @package ImeiPhoneLedger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Installation and schema upgrades.
 */
final class Imei_Ledger_Installer {

	public const DB_VERSION = '1.0.0';
	public const OPTION     = 'imei_ledger_db_version';
	public const CAPABILITY = 'manage_imei_ledger';

	public const GRANTED_OPTION = 'imei_ledger_granted_roles';

	/**
	 * Roles that get the capability on activation.
	 */
	private const ROLES = array( 'administrator', 'shop_manager' );

	/**
	 * Activation hook.
	 */
	public static function activate(): void {
		self::install();
	}

	/**
	 * Run the schema update when the stored version is behind.
	 *
	 * Also covers multisite sites that were added after network activation.
	 */
	public static function maybe_upgrade(): void {
		if ( self::DB_VERSION !== get_option( self::OPTION ) ) {
			self::install();
		}
	}

	/**
	 * Grant the capability to each default role once, including roles that
	 * appear after activation (Shop Manager when WooCommerce is installed
	 * later). Roles already granted are remembered, so a site owner who
	 * removes the capability on purpose does not get it added back.
	 */
	public static function ensure_capability(): void {
		$granted = (array) get_option( self::GRANTED_OPTION, array() );
		$pending = array_diff( self::ROLES, $granted );
		$added   = array();

		foreach ( $pending as $role_name ) {
			$role = get_role( $role_name );

			if ( null !== $role ) {
				$role->add_cap( self::CAPABILITY );
				$added[] = $role_name;
			}
		}

		if ( array() !== $added ) {
			update_option( self::GRANTED_OPTION, array_values( array_merge( $granted, $added ) ), false );
		}
	}

	/**
	 * Full table name.
	 *
	 * @return string
	 */
	public static function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . 'imei_ledger_records';
	}

	/**
	 * Create or update the table, then grant the capability.
	 */
	private static function install(): void {
		self::create_table();
		self::ensure_capability();
		update_option( self::OPTION, self::DB_VERSION, false );
	}

	/**
	 * Create or update the records table with dbDelta.
	 */
	private static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		// dbDelta needs two spaces after PRIMARY KEY and one field per line.
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			imei1 varchar(15) NOT NULL,
			imei2 varchar(15) NOT NULL DEFAULT '',
			brand varchar(100) NOT NULL DEFAULT '',
			model varchar(100) NOT NULL DEFAULT '',
			color varchar(50) NOT NULL DEFAULT '',
			storage varchar(50) NOT NULL DEFAULT '',
			device_condition varchar(20) NOT NULL DEFAULT '',
			status varchar(10) NOT NULL DEFAULT 'in_stock',
			purchase_date date NOT NULL,
			purchase_price decimal(12,2) NOT NULL DEFAULT '0.00',
			seller_name varchar(150) NOT NULL DEFAULT '',
			seller_phone varchar(20) NOT NULL DEFAULT '',
			seller_nid varchar(20) NOT NULL DEFAULT '',
			seller_address text NULL,
			sale_date date NULL,
			sale_price decimal(12,2) NULL,
			buyer_name varchar(150) NOT NULL DEFAULT '',
			buyer_phone varchar(20) NOT NULL DEFAULT '',
			buyer_nid varchar(20) NOT NULL DEFAULT '',
			buyer_address text NULL,
			notes text NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY imei1 (imei1),
			KEY imei2 (imei2),
			KEY status (status),
			KEY purchase_date (purchase_date),
			KEY sale_date (sale_date)
		) {$charset};";

		dbDelta( $sql );
	}

	/**
	 * Remove the capability from every role (used on uninstall).
	 */
	public static function remove_capability(): void {
		foreach ( wp_roles()->role_objects as $role ) {
			$role->remove_cap( self::CAPABILITY );
		}
	}
}
