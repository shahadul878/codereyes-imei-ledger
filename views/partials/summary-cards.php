<?php
/**
 * Summary cards: stock, sales and profit.
 *
 * @package CodereyesImeiLedger
 *
 * @var array $vars {
 *     @type array  $summary Imei_Ledger_Repository::summary() result.
 *     @type string $period  Label for the period, e.g. "This month".
 * }
 */

defined( 'ABSPATH' ) || exit;

$imei_ledger_summary   = $vars['summary'];
$imei_ledger_profit    = Imei_Ledger_Money::to_cents( $imei_ledger_summary['profit'] );
$imei_ledger_profit_cl = 0 > $imei_ledger_profit ? 'ipl-loss' : 'ipl-gain';

$imei_ledger_phones = static function ( string $count ): string {
	/* translators: %s: number of phones. */
	return sprintf( _n( '%s phone', '%s phones', (int) $count, 'codereyes-imei-ledger' ), number_format_i18n( (int) $count ) );
};
?>
<section class="ipl-stats" aria-label="<?php esc_attr_e( 'Summary', 'codereyes-imei-ledger' ); ?>">
	<div class="ipl-stat ipl-stat--hero">
		<p class="ipl-stat__label">
			<?php
			/* translators: %s: period, e.g. "This month". */
			echo esc_html( sprintf( __( 'Profit · %s', 'codereyes-imei-ledger' ), $vars['period'] ) );
			?>
		</p>
		<p class="ipl-stat__value <?php echo esc_attr( $imei_ledger_profit_cl ); ?>"><?php echo esc_html( Imei_Ledger_View::money( $imei_ledger_summary['profit'] ) ); ?></p>
		<p class="ipl-stat__meta">
			<?php
			/* translators: 1: number of phones sold, 2: sales total. */
			echo esc_html( sprintf( __( '%1$s sold for %2$s', 'codereyes-imei-ledger' ), $imei_ledger_phones( $imei_ledger_summary['sold_count'] ), Imei_Ledger_View::money( $imei_ledger_summary['sold_total'] ) ) );
			?>
		</p>
	</div>

	<div class="ipl-stat">
		<p class="ipl-stat__label"><?php esc_html_e( 'In stock now', 'codereyes-imei-ledger' ); ?></p>
		<p class="ipl-stat__value"><?php echo esc_html( number_format_i18n( (int) $imei_ledger_summary['stock_count'] ) ); ?></p>
		<p class="ipl-stat__meta">
			<?php
			/* translators: %s: stock value at purchase price. */
			echo esc_html( sprintf( __( 'Bought for %s', 'codereyes-imei-ledger' ), Imei_Ledger_View::money( $imei_ledger_summary['stock_value'] ) ) );
			?>
		</p>
	</div>

	<div class="ipl-stat">
		<p class="ipl-stat__label">
			<?php
			/* translators: %s: period, e.g. "This month". */
			echo esc_html( sprintf( __( 'Bought · %s', 'codereyes-imei-ledger' ), $vars['period'] ) );
			?>
		</p>
		<p class="ipl-stat__value"><?php echo esc_html( number_format_i18n( (int) $imei_ledger_summary['purchased_count'] ) ); ?></p>
		<p class="ipl-stat__meta">
			<?php
			/* translators: %s: total spent on purchases. */
			echo esc_html( sprintf( __( 'Spent %s', 'codereyes-imei-ledger' ), Imei_Ledger_View::money( $imei_ledger_summary['purchased_total'] ) ) );
			?>
		</p>
	</div>
</section>
