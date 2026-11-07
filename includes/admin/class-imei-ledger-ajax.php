<?php
/**
 * Live IMEI check while the purchase form is being filled in.
 *
 * @package ImeiPhoneLedger
 */

defined( 'ABSPATH' ) || exit;

/**
 * AJAX endpoint: is this IMEI valid, and has the shop seen it before?
 */
final class Imei_Ledger_Ajax {

	public const NONCE = 'imei_ledger_ajax';

	/**
	 * Repository.
	 *
	 * @var Imei_Ledger_Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 *
	 * @param Imei_Ledger_Repository $repository Repository.
	 */
	public function __construct( Imei_Ledger_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Hook the endpoint (logged-in users only).
	 */
	public function register(): void {
		add_action( 'wp_ajax_imei_ledger_check_imei', array( $this, 'check_imei' ) );
	}

	/**
	 * Respond with validity, duplicate level and matching records.
	 */
	public function check_imei(): void {
		check_ajax_referer( self::NONCE );

		if ( ! current_user_can( Imei_Ledger_Installer::CAPABILITY ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'imei-phone-ledger' ) ), 403 );
		}

		$imei       = Imei_Ledger_Imei::normalize( isset( $_POST['imei'] ) ? sanitize_text_field( wp_unslash( $_POST['imei'] ) ) : '' );
		$exclude_id = isset( $_POST['exclude_id'] ) ? absint( $_POST['exclude_id'] ) : 0;

		if ( ! Imei_Ledger_Imei::is_valid( $imei ) ) {
			wp_send_json_success(
				array(
					'valid' => false,
					'imei'  => $imei,
				)
			);
		}

		$matches = $this->repository->find_by_imeis( array( $imei ), $exclude_id );

		wp_send_json_success(
			array(
				'valid'   => true,
				'imei'    => $imei,
				'level'   => Imei_Ledger_Duplicates::classify( $matches ),
				'matches' => array_map( array( $this, 'summarize' ), $matches ),
			)
		);
	}

	/**
	 * The parts of a match the form needs. No personal data.
	 *
	 * @param array $record Record.
	 * @return array
	 */
	private function summarize( array $record ): array {
		$labels = Imei_Ledger_Status::labels();

		return array(
			'id'           => (int) $record['id'],
			'phone'        => Imei_Ledger_View::phone_name( $record ),
			'status'       => $record['status'],
			'statusLabel'  => $labels[ $record['status'] ] ?? $record['status'],
			'purchaseDate' => Imei_Ledger_View::date( $record['purchase_date'] ),
			'saleDate'     => Imei_Ledger_View::date( $record['sale_date'] ),
			'url'          => Imei_Ledger_View::record_url( 'view', (int) $record['id'] ),
		);
	}
}
