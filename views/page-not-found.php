<?php
/**
 * Shown when a record ID does not exist.
 *
 * @package CodereyesImeiLedger
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap ipl">
	<h1 class="ipl-title"><?php esc_html_e( 'Record not found', 'codereyes-imei-ledger' ); ?></h1>
	<p><?php esc_html_e( 'This record does not exist. It may have been deleted.', 'codereyes-imei-ledger' ); ?></p>
	<p><a class="button button-primary" href="<?php echo esc_url( Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST ) ); ?>"><?php esc_html_e( 'Back to all phones', 'codereyes-imei-ledger' ); ?></a></p>
</div>
