<?php
/**
 * Decides what an IMEI match means for a new purchase.
 *
 * @package CodereyesImeiLedger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Duplicate IMEI rules.
 *
 * - No match: save normally.
 * - Matches that were all sold: the shop is buying the phone back. Allowed,
 *   but the user must confirm they checked it.
 * - Any match still in stock: the same phone cannot be in stock twice. Blocked.
 */
final class Imei_Ledger_Duplicates {

	public const NONE        = 'none';
	public const SOLD_BEFORE = 'sold_before';
	public const IN_STOCK    = 'in_stock';

	/**
	 * Classify records that share an IMEI with the one being saved.
	 *
	 * @param array<int,array{status:string}> $matches Matching records (excluding the record being edited).
	 * @return string One of the class constants.
	 */
	public static function classify( array $matches ): string {
		if ( array() === $matches ) {
			return self::NONE;
		}

		foreach ( $matches as $match ) {
			if ( Imei_Ledger_Status::IN_STOCK === ( $match['status'] ?? '' ) ) {
				return self::IN_STOCK;
			}
		}

		return self::SOLD_BEFORE;
	}

	/**
	 * IMEIs on an edited purchase that the record did not already have.
	 *
	 * Only these need a duplicate check: correcting the price of an old sold
	 * record must not trip over the same phone being back in stock today.
	 *
	 * @param array{imei1:string,imei2:string} $data   Validated purchase data.
	 * @param array{imei1:string,imei2:string} $record Stored record.
	 * @return string[]
	 */
	public static function changed_imeis( array $data, array $record ): array {
		return array_values( array_diff( self::imeis_to_check( $data ), self::imeis_to_check( $record ) ) );
	}

	/**
	 * Non-empty IMEIs on a validated purchase.
	 *
	 * @param array{imei1:string,imei2:string} $data Validated purchase data.
	 * @return string[]
	 */
	public static function imeis_to_check( array $data ): array {
		return array_values(
			array_filter(
				array( $data['imei1'] ?? '', $data['imei2'] ?? '' ),
				static function ( string $imei ): bool {
					return '' !== $imei;
				}
			)
		);
	}
}
