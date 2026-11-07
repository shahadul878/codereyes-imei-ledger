<?php
/**
 * CSV rows for export.
 *
 * @package ImeiPhoneLedger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Turns records into spreadsheet-safe CSV rows.
 */
final class Imei_Ledger_Csv {

	/**
	 * Characters that make a spreadsheet treat a cell as a formula.
	 */
	private const FORMULA_TRIGGERS = array( '=', '+', '-', '@', "\t", "\r" );

	/**
	 * Column headings, in row order.
	 *
	 * @return string[]
	 */
	public static function headers(): array {
		return array(
			'ID',
			'IMEI 1',
			'IMEI 2',
			'Brand',
			'Model',
			'Color',
			'Storage',
			'Condition',
			'Status',
			'Purchase date',
			'Purchase price',
			'Seller name',
			'Seller phone',
			'Seller NID',
			'Sale date',
			'Sale price',
			'Buyer name',
			'Buyer phone',
			'Buyer NID',
			'Profit',
			'Notes',
		);
	}

	/**
	 * One CSV row for a record.
	 *
	 * IMEI, phone and NID numbers get a leading tab so Excel keeps them as
	 * text instead of showing 4.90154E+14 or dropping a leading zero.
	 *
	 * @param array $record Database row.
	 * @return string[]
	 */
	public static function row( array $record ): array {
		$get = static function ( string $key ) use ( $record ): string {
			return isset( $record[ $key ] ) ? (string) $record[ $key ] : '';
		};

		$is_sold = Imei_Ledger_Status::SOLD === $get( 'status' );
		$profit  = $is_sold ? Imei_Ledger_Money::profit( $get( 'purchase_price' ), $get( 'sale_price' ) ) : null;

		return array(
			$get( 'id' ),
			self::number_as_text( $get( 'imei1' ) ),
			self::number_as_text( $get( 'imei2' ) ),
			self::escape_cell( $get( 'brand' ) ),
			self::escape_cell( $get( 'model' ) ),
			self::escape_cell( $get( 'color' ) ),
			self::escape_cell( $get( 'storage' ) ),
			$get( 'device_condition' ),
			$get( 'status' ),
			$get( 'purchase_date' ),
			$get( 'purchase_price' ),
			self::escape_cell( $get( 'seller_name' ) ),
			self::number_as_text( $get( 'seller_phone' ) ),
			self::number_as_text( $get( 'seller_nid' ) ),
			$is_sold ? $get( 'sale_date' ) : '',
			$is_sold ? $get( 'sale_price' ) : '',
			self::escape_cell( $get( 'buyer_name' ) ),
			self::number_as_text( $get( 'buyer_phone' ) ),
			self::number_as_text( $get( 'buyer_nid' ) ),
			null === $profit ? '' : $profit,
			self::escape_cell( $get( 'notes' ) ),
		);
	}

	/**
	 * Neutralise spreadsheet formulas in free text.
	 *
	 * @param string $value Cell value.
	 * @return string
	 */
	public static function escape_cell( string $value ): string {
		if ( '' !== $value && in_array( $value[0], self::FORMULA_TRIGGERS, true ) ) {
			return "'" . $value;
		}

		return $value;
	}

	/**
	 * Keep a digit string as text in spreadsheets.
	 *
	 * Validated numbers contain only digits and an optional leading "+", so
	 * the tab prefix cannot be combined with a formula.
	 *
	 * @param string $value Digits.
	 * @return string
	 */
	private static function number_as_text( string $value ): string {
		return '' === $value ? '' : "\t" . $value;
	}
}
