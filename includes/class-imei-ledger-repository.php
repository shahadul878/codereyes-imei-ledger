<?php
/**
 * All database access for ledger records.
 *
 * phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom table; nothing to cache across requests.
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders -- WHERE and ORDER BY come from Imei_Ledger_Query (whitelisted columns, placeholder values).
 *
 * @package CodereyesImeiLedger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes rows in the records table.
 */
final class Imei_Ledger_Repository {

	/**
	 * Rows fetched per query while exporting.
	 */
	private const EXPORT_CHUNK = 500;

	/**
	 * Seconds to wait for the write lock.
	 */
	private const LOCK_TIMEOUT = 5;

	/**
	 * Whether this request holds the write lock.
	 *
	 * @var bool
	 */
	private $has_lock = false;

	/**
	 * Database handle.
	 *
	 * @var wpdb
	 */
	private $db;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 *
	 * @param wpdb   $db    Database handle.
	 * @param string $table Table name.
	 */
	public function __construct( wpdb $db, string $table ) {
		$this->db    = $db;
		$this->table = $table;
	}

	/**
	 * One record by ID.
	 *
	 * @param int $id Record ID.
	 * @return array|null
	 */
	public function find( int $id ): ?array {
		$row = $this->db->get_row(
			$this->db->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table, $id ),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}

	/**
	 * Records whose IMEI 1 or IMEI 2 matches any of the given IMEIs.
	 *
	 * @param string[] $imeis      IMEIs to look for.
	 * @param int      $exclude_id Record to leave out (the one being edited).
	 * @return array[] Newest first.
	 */
	public function find_by_imeis( array $imeis, int $exclude_id = 0 ): array {
		if ( array() === $imeis ) {
			return array();
		}

		$placeholders = implode( ', ', array_fill( 0, count( $imeis ), '%s' ) );
		$params       = array_merge( array( $this->table ), $imeis, $imeis, array( $exclude_id ) );

		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM %i WHERE ( imei1 IN ( {$placeholders} ) OR imei2 IN ( {$placeholders} ) ) AND id <> %d ORDER BY id DESC",
				$params
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Insert a new purchase. The phone starts in stock.
	 *
	 * @param array $data    Validated purchase data.
	 * @param int   $user_id Current user.
	 * @return int New record ID.
	 * @throws RuntimeException When the insert fails.
	 */
	public function insert_purchase( array $data, int $user_id ): int {
		$now = current_time( 'mysql' );
		$row = array_merge(
			$data,
			array(
				'status'     => Imei_Ledger_Status::IN_STOCK,
				'created_by' => $user_id,
				'created_at' => $now,
				'updated_at' => $now,
			)
		);

		if ( false === $this->db->insert( $this->table, $row ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Logged, never displayed.
			throw new RuntimeException( 'Insert failed: ' . $this->db->last_error );
		}

		return (int) $this->db->insert_id;
	}

	/**
	 * Update the purchase details of a record.
	 *
	 * @param int   $id   Record ID.
	 * @param array $data Validated purchase data.
	 * @throws RuntimeException When the update fails.
	 */
	public function update_purchase( int $id, array $data ): void {
		$this->update( $id, $data );
	}

	/**
	 * Record (or correct) the sale of a record.
	 *
	 * @param int   $id   Record ID.
	 * @param array $data Validated sale data.
	 * @throws RuntimeException When the update fails.
	 */
	public function record_sale( int $id, array $data ): void {
		$this->update( $id, array_merge( $data, array( 'status' => Imei_Ledger_Status::SOLD ) ) );
	}

	/**
	 * Undo a sale and put the phone back in stock.
	 *
	 * @param int $id Record ID.
	 * @throws RuntimeException When the update fails.
	 */
	public function cancel_sale( int $id ): void {
		$this->update(
			$id,
			array(
				'status'        => Imei_Ledger_Status::IN_STOCK,
				'sale_date'     => null,
				'sale_price'    => null,
				'buyer_name'    => '',
				'buyer_phone'   => '',
				'buyer_nid'     => '',
				'buyer_address' => null,
			)
		);
	}

	/**
	 * Delete a record.
	 *
	 * @param int $id Record ID.
	 * @throws RuntimeException When the delete fails.
	 */
	public function delete( int $id ): void {
		if ( false === $this->db->delete( $this->table, array( 'id' => $id ), array( '%d' ) ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Logged, never displayed.
			throw new RuntimeException( 'Delete failed: ' . $this->db->last_error );
		}
	}

	/**
	 * A page of records plus the total match count.
	 *
	 * @param array  $args     Filters for Imei_Ledger_Query::where().
	 * @param int    $per_page Rows per page.
	 * @param int    $page     1-based page.
	 * @param string $orderby  Column.
	 * @param string $order    asc | desc.
	 * @return array{rows: array[], total: int}
	 */
	public function search( array $args, int $per_page, int $page, string $orderby = 'id', string $order = 'desc' ): array {
		$where = Imei_Ledger_Query::where( $args );
		$rows  = $this->rows( $args, $per_page, $page, $orderby, $order );

		$total = $this->db->get_var(
			$this->db->prepare(
				"SELECT COUNT(*) FROM %i WHERE {$where['sql']}",
				array_merge( array( $this->table ), $where['params'] )
			)
		);

		return array(
			'rows'  => $rows,
			'total' => (int) $total,
		);
	}

	/**
	 * A page of matching records, without counting the total.
	 *
	 * @param array  $args     Filters for Imei_Ledger_Query::where().
	 * @param int    $per_page Rows per page.
	 * @param int    $page     1-based page.
	 * @param string $orderby  Column.
	 * @param string $order    Either asc or desc.
	 * @return array[]
	 */
	private function rows( array $args, int $per_page, int $page, string $orderby, string $order ): array {
		$where  = Imei_Ledger_Query::where( $args );
		$sort   = Imei_Ledger_Query::order_by( $orderby, $order );
		$offset = max( 0, ( $page - 1 ) * $per_page );

		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT * FROM %i WHERE {$where['sql']} ORDER BY {$sort} LIMIT %d OFFSET %d",
				array_merge( array( $this->table ), $where['params'], array( $per_page, $offset ) )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Take the named MySQL lock that serialises duplicate checks and writes.
	 *
	 * @return bool Whether the lock was acquired.
	 */
	public function lock(): bool {
		$acquired = $this->db->get_var( $this->db->prepare( 'SELECT GET_LOCK( %s, %d )', $this->lock_name(), self::LOCK_TIMEOUT ) );

		$this->has_lock = '1' === (string) $acquired;

		return $this->has_lock;
	}

	/**
	 * Release the lock if this request holds it.
	 */
	public function unlock(): void {
		if ( ! $this->has_lock ) {
			return;
		}

		$this->db->query( $this->db->prepare( 'SELECT RELEASE_LOCK( %s )', $this->lock_name() ) );
		$this->has_lock = false;
	}

	/**
	 * Lock name, unique per table so multisite sites do not block each other.
	 *
	 * @return string
	 */
	private function lock_name(): string {
		return 'imei_ledger_' . md5( $this->table );
	}

	/**
	 * Call $callback for each matching record, fetching in chunks.
	 *
	 * @param array    $args     Filters for Imei_Ledger_Query::where().
	 * @param callable $callback Receives one row array.
	 */
	public function each( array $args, callable $callback ): void {
		$page = 1;

		do {
			$rows = $this->rows( $args, self::EXPORT_CHUNK, $page, 'id', 'asc' );
			array_map( $callback, $rows );
			$fetched = count( $rows );
			++$page;
		} while ( self::EXPORT_CHUNK === $fetched );
	}

	/**
	 * Totals for a date range.
	 *
	 * Purchases are counted by purchase date, sales by sale date. Stock is
	 * what is in stock right now, regardless of the range.
	 *
	 * @param string $from Y-m-d, inclusive.
	 * @param string $to   Y-m-d, inclusive.
	 * @return array<string,string>
	 */
	public function summary( string $from, string $to ): array {
		$purchases = $this->db->get_row(
			$this->db->prepare(
				'SELECT COUNT(*) AS count, COALESCE( SUM( purchase_price ), 0 ) AS total FROM %i WHERE purchase_date BETWEEN %s AND %s',
				$this->table,
				$from,
				$to
			),
			ARRAY_A
		);

		$sales = $this->db->get_row(
			$this->db->prepare(
				'SELECT COUNT(*) AS count, COALESCE( SUM( sale_price ), 0 ) AS total, COALESCE( SUM( sale_price - purchase_price ), 0 ) AS profit FROM %i WHERE status = %s AND sale_date BETWEEN %s AND %s',
				$this->table,
				Imei_Ledger_Status::SOLD,
				$from,
				$to
			),
			ARRAY_A
		);

		$stock = $this->db->get_row(
			$this->db->prepare(
				'SELECT COUNT(*) AS count, COALESCE( SUM( purchase_price ), 0 ) AS total FROM %i WHERE status = %s',
				$this->table,
				Imei_Ledger_Status::IN_STOCK
			),
			ARRAY_A
		);

		return array(
			'purchased_count' => (string) ( $purchases['count'] ?? '0' ),
			'purchased_total' => (string) ( $purchases['total'] ?? '0.00' ),
			'sold_count'      => (string) ( $sales['count'] ?? '0' ),
			'sold_total'      => (string) ( $sales['total'] ?? '0.00' ),
			'profit'          => (string) ( $sales['profit'] ?? '0.00' ),
			'stock_count'     => (string) ( $stock['count'] ?? '0' ),
			'stock_value'     => (string) ( $stock['total'] ?? '0.00' ),
		);
	}

	/**
	 * Update a row and stamp updated_at.
	 *
	 * @param int   $id   Record ID.
	 * @param array $data Columns to change.
	 * @throws RuntimeException When the update fails.
	 */
	private function update( int $id, array $data ): void {
		$row = array_merge( $data, array( 'updated_at' => current_time( 'mysql' ) ) );

		if ( false === $this->db->update( $this->table, $row, array( 'id' => $id ) ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Logged, never displayed.
			throw new RuntimeException( 'Update failed: ' . $this->db->last_error );
		}
	}
}
