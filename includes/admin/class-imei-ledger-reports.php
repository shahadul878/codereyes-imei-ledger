<?php
/**
 * Reports screen: totals and sales for a date range.
 *
 * @package ImeiPhoneLedger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the reports screen.
 */
final class Imei_Ledger_Reports {

	/**
	 * Sales listed on screen; the CSV has them all.
	 */
	private const SALES_ON_SCREEN = 100;

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
	 * Render the screen.
	 */
	public function render(): void {
		list( $from, $to ) = $this->range();

		$sales = $this->repository->search(
			array(
				'status'     => Imei_Ledger_Status::SOLD,
				'date_field' => 'sale_date',
				'date_from'  => $from,
				'date_to'    => $to,
			),
			self::SALES_ON_SCREEN,
			1,
			'sale_date',
			'desc'
		);

		Imei_Ledger_View::render(
			'page-reports',
			array(
				'from'      => $from,
				'to'        => $to,
				'presets'   => $this->presets(),
				'summary'   => $this->repository->summary( $from, $to ),
				'sales'     => $sales['rows'],
				'sales_all' => $sales['total'],
				'exports'   => array(
					'sales'     => $this->export_url( 'sale_date', $from, $to, Imei_Ledger_Status::SOLD ),
					'purchases' => $this->export_url( 'purchase_date', $from, $to, '' ),
				),
			)
		);
	}

	/**
	 * Selected range; defaults to this month. Swaps the dates if reversed.
	 *
	 * @return array{0:string,1:string}
	 */
	private function range(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only date filter.
		$from = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
		$to   = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
		// phpcs:enable

		$from = Imei_Ledger_Validator::is_date( $from ) ? $from : current_time( 'Y-m-01' );
		$to   = Imei_Ledger_Validator::is_date( $to ) ? $to : current_time( 'Y-m-d' );

		return $from <= $to ? array( $from, $to ) : array( $to, $from );
	}

	/**
	 * Quick ranges: label => array( from, to ).
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	private function presets(): array {
		$today = new DateTimeImmutable( current_time( 'Y-m-d' ) );

		return array(
			__( 'Today', 'imei-phone-ledger' )       => array( $today->format( 'Y-m-d' ), $today->format( 'Y-m-d' ) ),
			__( 'Last 7 days', 'imei-phone-ledger' ) => array( $today->modify( '-6 days' )->format( 'Y-m-d' ), $today->format( 'Y-m-d' ) ),
			__( 'This month', 'imei-phone-ledger' )  => array( $today->format( 'Y-m-01' ), $today->format( 'Y-m-d' ) ),
			__( 'Last month', 'imei-phone-ledger' )  => array( $today->modify( 'first day of last month' )->format( 'Y-m-d' ), $today->modify( 'last day of last month' )->format( 'Y-m-d' ) ),
			__( 'This year', 'imei-phone-ledger' )   => array( $today->format( 'Y-01-01' ), $today->format( 'Y-m-d' ) ),
		);
	}

	/**
	 * Nonce-protected CSV export URL.
	 *
	 * @param string $date_field purchase_date | sale_date.
	 * @param string $from       Y-m-d.
	 * @param string $to         Y-m-d.
	 * @param string $status     Status filter, or ''.
	 * @return string Unescaped URL.
	 */
	private function export_url( string $date_field, string $from, string $to, string $status ): string {
		$args = array_filter(
			array(
				'action'     => 'imei_ledger_export',
				'date_field' => $date_field,
				'date_from'  => $from,
				'date_to'    => $to,
				'status'     => $status,
			)
		);

		return wp_nonce_url( add_query_arg( $args, admin_url( 'admin-post.php' ) ), 'imei_ledger_export' );
	}
}
