<?php
/**
 * Handlers for admin-post.php: save purchase, save sale, cancel sale, delete, export.
 *
 * @package ImeiPhoneLedger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Every handler checks the capability and a nonce, then redirects.
 */
final class Imei_Ledger_Actions {

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
	 * Hook the handlers.
	 */
	public function register(): void {
		add_action( 'admin_post_imei_ledger_save_purchase', array( $this, 'save_purchase' ) );
		add_action( 'admin_post_imei_ledger_save_sale', array( $this, 'save_sale' ) );
		add_action( 'admin_post_imei_ledger_cancel_sale', array( $this, 'cancel_sale' ) );
		add_action( 'admin_post_imei_ledger_delete', array( $this, 'delete' ) );
		add_action( 'admin_post_imei_ledger_export', array( $this, 'export' ) );
	}

	/**
	 * Create or update the purchase side of a record.
	 */
	public function save_purchase(): void {
		$this->authorize( 'imei_ledger_save_purchase' );

		$input  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in authorize(); sanitised field by field in Imei_Ledger_Validator.
		$id     = absint( $input['id'] ?? 0 );
		$record = 0 === $id ? null : $this->repository->find( $id );
		$back   = 0 === $id ? Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_ADD ) : Imei_Ledger_View::record_url( 'edit', $id );

		if ( 0 !== $id && null === $record ) {
			$this->redirect( Imei_Ledger_Notices::add_to_url( Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST ), 'not_found' ) );
		}

		$result = Imei_Ledger_Validator::validate_purchase( $input, current_time( 'Y-m-d' ) );
		$errors = array_merge( $result['errors'], $this->sold_record_date_error( $record, $result['data'] ) );

		if ( array() !== $errors ) {
			$this->back_with_state( $back, 'purchase', $id, $input, $errors );
		}

		$this->lock_or_back( $back );
		$this->guard_duplicates( $result['data'], $record, $input, $back );

		try {
			if ( null === $record ) {
				$id = $this->repository->insert_purchase( $result['data'], get_current_user_id() );
			} else {
				$this->repository->update_purchase( $id, $result['data'] );
			}
		} catch ( RuntimeException $e ) {
			$this->fail( $e, $back );
		}

		$this->redirect( Imei_Ledger_Notices::add_to_url( Imei_Ledger_View::record_url( 'view', $id ), null === $record ? 'purchase_added' : 'purchase_updated' ) );
	}

	/**
	 * Record or correct a sale.
	 */
	public function save_sale(): void {
		$this->authorize( 'imei_ledger_save_sale' );

		$input  = wp_unslash( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in authorize(); sanitised field by field in Imei_Ledger_Validator.
		$id     = absint( $input['id'] ?? 0 );
		$record = $this->require_record( $id );
		$back   = Imei_Ledger_View::record_url( 'sell', $id );
		$result = Imei_Ledger_Validator::validate_sale( $input, (string) $record['purchase_date'], current_time( 'Y-m-d' ) );

		if ( array() !== $result['errors'] ) {
			$this->back_with_state( $back, 'sale', $id, $input, $result['errors'] );
		}

		try {
			$this->repository->record_sale( $id, $result['data'] );
		} catch ( RuntimeException $e ) {
			$this->fail( $e, $back );
		}

		$this->redirect( Imei_Ledger_Notices::add_to_url( Imei_Ledger_View::record_url( 'view', $id ), 'sale_saved' ) );
	}

	/**
	 * Undo a sale.
	 */
	public function cancel_sale(): void {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is checked next, per record.
		$this->authorize( 'imei_ledger_cancel_sale_' . $id );

		$record = $this->require_record( $id );
		$view   = Imei_Ledger_View::record_url( 'view', $id );

		if ( Imei_Ledger_Status::SOLD !== $record['status'] ) {
			$this->redirect( Imei_Ledger_Notices::add_to_url( $view, 'not_sold' ) );
		}

		$this->lock_or_back( $view );

		$matches = $this->repository->find_by_imeis( Imei_Ledger_Duplicates::imeis_to_check( $record ), $id );
		if ( Imei_Ledger_Duplicates::IN_STOCK === Imei_Ledger_Duplicates::classify( $matches ) ) {
			$this->redirect( Imei_Ledger_Notices::add_to_url( $view, 'cancel_blocked' ) );
		}

		try {
			$this->repository->cancel_sale( $id );
		} catch ( RuntimeException $e ) {
			$this->fail( $e, $view );
		}

		$this->redirect( Imei_Ledger_Notices::add_to_url( $view, 'sale_cancelled' ) );
	}

	/**
	 * Delete a record.
	 */
	public function delete(): void {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is checked next, per record.
		$this->authorize( 'imei_ledger_delete_' . $id );
		$this->require_record( $id );

		$list = Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST );

		try {
			$this->repository->delete( $id );
		} catch ( RuntimeException $e ) {
			$this->fail( $e, $list );
		}

		$this->redirect( Imei_Ledger_Notices::add_to_url( $list, 'deleted' ) );
	}

	/**
	 * Stream matching records as CSV.
	 */
	public function export(): void {
		$this->authorize( 'imei_ledger_export' );

		// The list page posts its filters so a searched name, phone or NID stays
		// out of the URL; the reports page links carry only dates and status.
		$request  = 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ? $_POST : $_GET; // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- Nonce checked in authorize(); sanitised in filters_from_request().
		$args     = self::filters_from_request( wp_unslash( $request ) );
		$filename = sprintf( 'imei-ledger-%s.csv', current_time( 'Y-m-d-His' ) );

		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Large ledgers take a while to stream.
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'X-Content-Type-Options: nosniff' );

		$out = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming to the response.

		// UTF-8 byte order mark so Excel shows Bengali names correctly.
		fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
		fputcsv( $out, Imei_Ledger_Csv::headers(), ',', '"', '' );

		$this->repository->each(
			$args,
			static function ( array $record ) use ( $out ): void {
				fputcsv( $out, Imei_Ledger_Csv::row( $record ), ',', '"', '' );
			}
		);

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
		exit;
	}

	/**
	 * Sanitised list filters from a request array.
	 *
	 * @param array $request Unslashed request.
	 * @return array Args for Imei_Ledger_Query::where().
	 */
	public static function filters_from_request( array $request ): array {
		$read = static function ( string $key ) use ( $request ): string {
			return isset( $request[ $key ] ) && is_scalar( $request[ $key ] ) ? sanitize_text_field( (string) $request[ $key ] ) : '';
		};

		return array(
			'search'     => $read( 's' ),
			'status'     => $read( 'status' ),
			'date_field' => $read( 'date_field' ),
			'date_from'  => $read( 'date_from' ),
			'date_to'    => $read( 'date_to' ),
		);
	}

	/**
	 * Stop unless the user may use the ledger and the nonce is valid.
	 *
	 * @param string $nonce_action Nonce action.
	 */
	private function authorize( string $nonce_action ): void {
		if ( ! current_user_can( Imei_Ledger_Installer::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage the IMEI ledger.', 'imei-phone-ledger' ), 403 );
		}

		check_admin_referer( $nonce_action );
	}

	/**
	 * A record, or redirect to the list with a "not found" notice.
	 *
	 * @param int $id Record ID.
	 * @return array
	 */
	private function require_record( int $id ): array {
		$record = $this->repository->find( $id );

		if ( null === $record ) {
			$this->redirect( Imei_Ledger_Notices::add_to_url( Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST ), 'not_found' ) );
		}

		return $record;
	}

	/**
	 * A sold record's purchase date may not move after its sale date.
	 *
	 * @param array|null $record Record being edited.
	 * @param array      $data   Validated purchase data.
	 * @return array<string,string>
	 */
	private function sold_record_date_error( ?array $record, array $data ): array {
		$is_sold = null !== $record && Imei_Ledger_Status::SOLD === $record['status'];

		if ( ! $is_sold || '' === $data['purchase_date'] || $data['purchase_date'] <= (string) $record['sale_date'] ) {
			return array();
		}

		return array( 'purchase_date' => __( 'The purchase date cannot be after the sale date.', 'imei-phone-ledger' ) );
	}

	/**
	 * Block an IMEI that is in stock; ask for confirmation on one sold before.
	 *
	 * On edit, only IMEIs the record did not already have are checked, so
	 * correcting the price of an old sold record does not trip over the same
	 * phone being back in stock today.
	 *
	 * @param array      $data   Validated purchase data.
	 * @param array|null $record Record being edited.
	 * @param array      $input  Raw input, for refilling the form.
	 * @param string     $back   Form URL.
	 */
	private function guard_duplicates( array $data, ?array $record, array $input, string $back ): void {
		$imeis = null === $record ? Imei_Ledger_Duplicates::imeis_to_check( $data ) : Imei_Ledger_Duplicates::changed_imeis( $data, $record );

		if ( array() === $imeis ) {
			return;
		}

		$matches   = $this->repository->find_by_imeis( $imeis, null === $record ? 0 : (int) $record['id'] );
		$level     = Imei_Ledger_Duplicates::classify( $matches );
		$confirmed = ! empty( $input['confirm_duplicate'] );

		if ( Imei_Ledger_Duplicates::NONE === $level || ( Imei_Ledger_Duplicates::SOLD_BEFORE === $level && $confirmed ) ) {
			return;
		}

		Imei_Ledger_Form_State::save(
			array(
				'form'      => 'purchase',
				'id'        => null === $record ? 0 : (int) $record['id'],
				'input'     => self::form_input( $input, Imei_Ledger_Validator::PURCHASE_FIELDS ),
				'errors'    => array(),
				'duplicate' => $level,
				'matches'   => array_map( array( self::class, 'match_summary' ), $matches ),
			)
		);

		$this->redirect( $back );
	}

	/**
	 * Save form state and go back to the form.
	 *
	 * @param string $url    Form URL.
	 * @param string $form   purchase | sale.
	 * @param int    $id     Record ID.
	 * @param array  $input  Raw input.
	 * @param array  $errors Field errors.
	 */
	private function back_with_state( string $url, string $form, int $id, array $input, array $errors ): void {
		$fields = 'sale' === $form ? Imei_Ledger_Validator::SALE_FIELDS : Imei_Ledger_Validator::PURCHASE_FIELDS;

		Imei_Ledger_Form_State::save(
			array(
				'form'   => $form,
				'id'     => $id,
				'input'  => self::form_input( $input, $fields ),
				'errors' => $errors,
			)
		);

		$this->redirect( $url );
	}

	/**
	 * Only the form's own scalar fields, to refill it after a redirect.
	 *
	 * @param array    $input  Raw input.
	 * @param string[] $fields Allowed field names.
	 * @return array<string,string>
	 */
	private static function form_input( array $input, array $fields ): array {
		$kept = array_intersect_key( $input, array_flip( $fields ) );

		return array_map(
			static function ( $value ): string {
				return is_scalar( $value ) ? (string) $value : '';
			},
			$kept
		);
	}

	/**
	 * The parts of a matching record the duplicate notice shows.
	 *
	 * @param array $record Record.
	 * @return array<string,string>
	 */
	private static function match_summary( array $record ): array {
		$keys = array( 'id', 'status', 'brand', 'model', 'storage', 'color', 'purchase_date', 'seller_name', 'sale_date', 'buyer_name' );

		return array_map( 'strval', array_intersect_key( $record, array_flip( $keys ) ) );
	}

	/**
	 * Take the ledger write lock, or go back with a "busy" notice.
	 *
	 * Stops two quick submits of the same IMEI from both passing the
	 * duplicate check. The lock is released in redirect() and, failing
	 * that, when the database connection closes.
	 *
	 * @param string $back URL to return to.
	 */
	private function lock_or_back( string $back ): void {
		if ( ! $this->repository->lock() ) {
			$this->redirect( Imei_Ledger_Notices::add_to_url( $back, 'busy' ) );
		}
	}

	/**
	 * Log a database failure and go back with an error notice.
	 *
	 * @param RuntimeException $e   Failure.
	 * @param string           $url Where to go back to.
	 */
	private function fail( RuntimeException $e, string $url ): void {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Server-side record of a failed write.
		error_log( 'IMEI Phone Ledger: ' . $e->getMessage() );

		$this->redirect( Imei_Ledger_Notices::add_to_url( $url, 'db_error' ) );
	}

	/**
	 * Safe redirect and stop.
	 *
	 * @param string $url URL.
	 * @return never
	 */
	private function redirect( string $url ): void {
		$this->repository->unlock();
		wp_safe_redirect( $url );
		exit;
	}
}
