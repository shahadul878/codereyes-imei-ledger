<?php
/**
 * Success and error notices shown after a redirect.
 *
 * @package CodereyesImeiLedger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Notices are passed as a whitelisted code in the URL, never as free text.
 */
final class Imei_Ledger_Notices {

	public const QUERY_ARG = 'ipl_notice';

	/**
	 * Code => array( type, message ).
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	private static function messages(): array {
		return array(
			'purchase_added'   => array( 'success', __( 'Purchase saved. The phone is now in stock.', 'codereyes-imei-ledger' ) ),
			'purchase_updated' => array( 'success', __( 'Purchase details updated.', 'codereyes-imei-ledger' ) ),
			'sale_saved'       => array( 'success', __( 'Sale saved.', 'codereyes-imei-ledger' ) ),
			'sale_cancelled'   => array( 'success', __( 'Sale cancelled. The phone is back in stock.', 'codereyes-imei-ledger' ) ),
			'deleted'          => array( 'success', __( 'Record deleted.', 'codereyes-imei-ledger' ) ),
			'not_found'        => array( 'error', __( 'That record no longer exists.', 'codereyes-imei-ledger' ) ),
			'db_error'         => array( 'error', __( 'The record could not be saved. Please try again.', 'codereyes-imei-ledger' ) ),
			'busy'             => array( 'error', __( 'Another save is in progress. Please try again.', 'codereyes-imei-ledger' ) ),
			'cancel_blocked'   => array( 'error', __( 'This sale cannot be cancelled: the same IMEI has since been bought again and is in stock.', 'codereyes-imei-ledger' ) ),
			'not_sold'         => array( 'error', __( 'Only phones that are sold can have their sale cancelled.', 'codereyes-imei-ledger' ) ),
		);
	}

	/**
	 * Add a notice code to a URL.
	 *
	 * @param string $url  URL.
	 * @param string $code Notice code.
	 * @return string
	 */
	public static function add_to_url( string $url, string $code ): string {
		return add_query_arg( self::QUERY_ARG, $code, $url );
	}

	/**
	 * Print the notice named in the current URL, if any.
	 */
	public static function render(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of a whitelisted code.
		$code     = isset( $_GET[ self::QUERY_ARG ] ) ? sanitize_key( wp_unslash( $_GET[ self::QUERY_ARG ] ) ) : '';
		$messages = self::messages();

		if ( ! isset( $messages[ $code ] ) ) {
			return;
		}

		list( $type, $message ) = $messages[ $code ];

		wp_admin_notice(
			$message,
			array(
				'type'        => $type,
				'dismissible' => true,
			)
		);
	}
}
