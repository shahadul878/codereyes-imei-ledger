<?php
/**
 * Builds WHERE / ORDER BY fragments for listing and reporting.
 *
 * Pure: every column name comes from a whitelist and every user value is
 * returned as a placeholder parameter for $wpdb->prepare().
 *
 * @package ImeiPhoneLedger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Query fragments.
 */
final class Imei_Ledger_Query {

	public const DATE_FIELDS = array( 'purchase_date', 'sale_date' );

	public const SORTABLE = array( 'id', 'purchase_date', 'sale_date', 'purchase_price', 'sale_price', 'brand', 'model' );

	/**
	 * Columns searched with the digits-only form of the search term.
	 */
	private const DIGIT_COLUMNS = array( 'imei1', 'imei2', 'seller_phone', 'buyer_phone', 'seller_nid', 'buyer_nid' );

	/**
	 * Columns searched with the search term as typed.
	 */
	private const TEXT_COLUMNS = array( 'brand', 'model', 'seller_name', 'buyer_name' );

	/**
	 * Fewer digits than this would match far too many IMEIs and phone numbers.
	 */
	private const MIN_DIGIT_SEARCH = 4;

	/**
	 * Build a WHERE clause.
	 *
	 * Keys: search (IMEI, name, phone, NID, brand or model), status
	 * (in_stock|sold), date_field (purchase_date|sale_date), date_from and
	 * date_to (Y-m-d, inclusive). Unknown or invalid values are ignored.
	 *
	 * @param array $args Filters.
	 * @return array{sql: string, params: array<int,string>}
	 */
	public static function where( array $args ): array {
		$parts = array_values(
			array_filter(
				array(
					self::status_clause( (string) ( $args['status'] ?? '' ) ),
					self::search_clause( (string) ( $args['search'] ?? '' ) ),
					self::date_clause( $args ),
				)
			)
		);

		if ( array() === $parts ) {
			return array(
				'sql'    => '1=1',
				'params' => array(),
			);
		}

		return array(
			'sql'    => implode( ' AND ', array_column( $parts, 'sql' ) ),
			'params' => array_merge( ...array_column( $parts, 'params' ) ),
		);
	}

	/**
	 * Whitelisted ORDER BY.
	 *
	 * @param string $orderby Column.
	 * @param string $order   Either asc or desc.
	 * @return string
	 */
	public static function order_by( string $orderby, string $order ): string {
		$column    = in_array( $orderby, self::SORTABLE, true ) ? $orderby : 'id';
		$direction = 'asc' === strtolower( $order ) ? 'ASC' : 'DESC';

		if ( 'id' === $column ) {
			return 'id ' . $direction;
		}

		// The id tiebreaker keeps page boundaries stable when many rows share a date or price.
		return sprintf( '%1$s %2$s, id %2$s', $column, $direction );
	}

	/**
	 * Escape LIKE wildcards, matching wpdb::esc_like().
	 *
	 * @param string $text Text.
	 * @return string
	 */
	public static function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}

	/**
	 * Status filter.
	 *
	 * @param string $status Status.
	 * @return array{sql:string,params:array}|null
	 */
	private static function status_clause( string $status ): ?array {
		if ( ! in_array( $status, Imei_Ledger_Status::ALL, true ) ) {
			return null;
		}

		return array(
			'sql'    => 'status = %s',
			'params' => array( $status ),
		);
	}

	/**
	 * Free-text search across the searchable columns.
	 *
	 * @param string $search Search term.
	 * @return array{sql:string,params:array}|null
	 */
	private static function search_clause( string $search ): ?array {
		$text   = trim( $search );
		$digits = Imei_Ledger_Digits::only( $text );

		if ( '' === $text ) {
			return null;
		}

		$conditions = array();
		$params     = array();

		foreach ( self::TEXT_COLUMNS as $column ) {
			$conditions[] = $column . ' LIKE %s';
			$params[]     = '%' . self::esc_like( $text ) . '%';
		}

		if ( strlen( $digits ) >= self::MIN_DIGIT_SEARCH ) {
			foreach ( self::DIGIT_COLUMNS as $column ) {
				$conditions[] = $column . ' LIKE %s';
				$params[]     = '%' . self::esc_like( $digits ) . '%';
			}
		}

		return array(
			'sql'    => '(' . implode( ' OR ', $conditions ) . ')',
			'params' => $params,
		);
	}

	/**
	 * Inclusive date range on a whitelisted column.
	 *
	 * @param array $args Query args.
	 * @return array{sql:string,params:array}|null
	 */
	private static function date_clause( array $args ): ?array {
		$field = (string) ( $args['date_field'] ?? '' );
		$field = '' === $field ? 'purchase_date' : $field;

		if ( ! in_array( $field, self::DATE_FIELDS, true ) ) {
			return null;
		}

		$conditions = array();
		$params     = array();
		$bounds     = array(
			'>=' => (string) ( $args['date_from'] ?? '' ),
			'<=' => (string) ( $args['date_to'] ?? '' ),
		);

		foreach ( $bounds as $operator => $date ) {
			if ( Imei_Ledger_Validator::is_date( $date ) ) {
				$conditions[] = $field . ' ' . $operator . ' %s';
				$params[]     = $date;
			}
		}

		if ( array() === $conditions ) {
			return null;
		}

		return array(
			'sql'    => implode( ' AND ', $conditions ),
			'params' => $params,
		);
	}
}
