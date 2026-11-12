<?php
/**
 * Record status values.
 *
 * @package CodereyesImeiLedger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * A record is either in stock or sold.
 */
final class Imei_Ledger_Status {

	public const IN_STOCK = 'in_stock';
	public const SOLD     = 'sold';
	public const ALL      = array( self::IN_STOCK, self::SOLD );

	/**
	 * Human labels.
	 *
	 * @return array<string,string>
	 */
	public static function labels(): array {
		return array(
			self::IN_STOCK => __( 'In stock', 'codereyes-imei-ledger' ),
			self::SOLD     => __( 'Sold', 'codereyes-imei-ledger' ),
		);
	}
}
