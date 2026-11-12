<?php
/**
 * Plugin Name:       Codereyes IMEI Ledger for Mobile Shops
 * Description:       Buy and sell ledger for second-hand mobile phone shops, tracked by IMEI. Search by IMEI, record sellers and buyers, catch duplicate IMEIs, see profit and export CSV reports.
 * Version:           1.0.1
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            H M Shahadul Islam
 * Author URI:        https://github.com/shahadul878
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       codereyes-imei-ledger
 *
 * @package CodereyesImeiLedger
 */

defined( 'ABSPATH' ) || exit;

define( 'IMEI_LEDGER_VERSION', '1.0.1' );
define( 'IMEI_LEDGER_FILE', __FILE__ );
define( 'IMEI_LEDGER_DIR', plugin_dir_path( __FILE__ ) );
define( 'IMEI_LEDGER_URL', plugin_dir_url( __FILE__ ) );

require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-status.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-digits.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-imei.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-money.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-validator.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-duplicates.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-query.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-csv.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-installer.php';
require_once IMEI_LEDGER_DIR . 'includes/class-imei-ledger-repository.php';

register_activation_hook( __FILE__, array( 'Imei_Ledger_Installer', 'activate' ) );

/**
 * Shared repository instance.
 *
 * @return Imei_Ledger_Repository
 */
function imei_ledger_repository() {
	static $repository = null;

	if ( null === $repository ) {
		global $wpdb;
		$repository = new Imei_Ledger_Repository( $wpdb, Imei_Ledger_Installer::table_name() );
	}

	return $repository;
}

/**
 * Boot the plugin.
 */
function imei_ledger_boot() {
	Imei_Ledger_Installer::maybe_upgrade();

	if ( ! is_admin() ) {
		return;
	}

	require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-view.php';
	require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-form-state.php';
	require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-notices.php';
	require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-admin.php';
	require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-actions.php';
	require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-ajax.php';

	add_action( 'admin_init', array( 'Imei_Ledger_Installer', 'ensure_capability' ) );

	$repository = imei_ledger_repository();

	( new Imei_Ledger_Admin( $repository ) )->register();
	( new Imei_Ledger_Actions( $repository ) )->register();
	( new Imei_Ledger_Ajax( $repository ) )->register();
}
add_action( 'plugins_loaded', 'imei_ledger_boot' );
