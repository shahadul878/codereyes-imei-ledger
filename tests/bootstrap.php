<?php
/**
 * PHPUnit bootstrap for pure-logic unit tests (no WordPress runtime).
 *
 * @package CodereyesImeiLedger
 */

define( 'ABSPATH', sys_get_temp_dir() . '/' );

require_once __DIR__ . '/stubs/wordpress.php';

$imei_ledger_includes = dirname( __DIR__ ) . '/includes/';

require_once $imei_ledger_includes . 'class-imei-ledger-status.php';
require_once $imei_ledger_includes . 'class-imei-ledger-digits.php';
require_once $imei_ledger_includes . 'class-imei-ledger-imei.php';
require_once $imei_ledger_includes . 'class-imei-ledger-money.php';
require_once $imei_ledger_includes . 'class-imei-ledger-validator.php';
require_once $imei_ledger_includes . 'class-imei-ledger-duplicates.php';
require_once $imei_ledger_includes . 'class-imei-ledger-query.php';
require_once $imei_ledger_includes . 'class-imei-ledger-csv.php';
