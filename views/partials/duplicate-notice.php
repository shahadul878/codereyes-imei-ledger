<?php
/**
 * Duplicate IMEI notice shown on the purchase form after a save attempt.
 *
 * @package CodereyesImeiLedger
 *
 * @var array $vars {
 *     @type string $level   Imei_Ledger_Duplicates level.
 *     @type array  $matches Matching records.
 * }
 */

defined( 'ABSPATH' ) || exit;

if ( Imei_Ledger_Duplicates::NONE === $vars['level'] ) {
	return;
}

$imei_ledger_blocked = Imei_Ledger_Duplicates::IN_STOCK === $vars['level'];
?>
<div class="ipl-duplicate <?php echo $imei_ledger_blocked ? 'ipl-duplicate--block' : 'ipl-duplicate--warn'; ?>" role="alert">
	<p class="ipl-duplicate__title">
		<?php
		echo $imei_ledger_blocked
			? esc_html__( 'This phone is already in stock. The same IMEI cannot be in stock twice, so nothing was saved.', 'codereyes-imei-ledger' )
			: esc_html__( 'This IMEI has been bought and sold before. Nothing was saved yet.', 'codereyes-imei-ledger' );
		?>
	</p>

	<ul class="ipl-duplicate__list">
		<?php foreach ( $vars['matches'] as $imei_ledger_match ) : ?>
			<li>
				<a href="<?php echo esc_url( Imei_Ledger_View::record_url( 'view', (int) $imei_ledger_match['id'] ) ); ?>" target="_blank" rel="noopener">
					<?php
					/* translators: %d: record ID. */
					echo esc_html( sprintf( __( 'Record #%d', 'codereyes-imei-ledger' ), (int) $imei_ledger_match['id'] ) );
					?>
				</a>
				· <?php echo esc_html( Imei_Ledger_View::phone_name( $imei_ledger_match ) ); ?>
				· <?php Imei_Ledger_View::status_badge( (string) $imei_ledger_match['status'] ); ?>
				·
				<?php
				/* translators: 1: purchase date, 2: seller name. */
				echo esc_html( sprintf( __( 'bought %1$s from %2$s', 'codereyes-imei-ledger' ), Imei_Ledger_View::date( $imei_ledger_match['purchase_date'] ), $imei_ledger_match['seller_name'] ) );

				if ( Imei_Ledger_Status::SOLD === $imei_ledger_match['status'] ) {
					/* translators: 1: sale date, 2: buyer name. */
					echo esc_html( sprintf( __( ', sold %1$s to %2$s', 'codereyes-imei-ledger' ), Imei_Ledger_View::date( $imei_ledger_match['sale_date'] ), $imei_ledger_match['buyer_name'] ) );
				}
				?>
			</li>
		<?php endforeach; ?>
	</ul>

	<?php if ( ! $imei_ledger_blocked ) : ?>
		<label class="ipl-duplicate__confirm">
			<input type="checkbox" name="confirm_duplicate" value="1" required>
			<?php esc_html_e( 'I have checked the earlier record and the seller. Save this as a new purchase.', 'codereyes-imei-ledger' ); ?>
		</label>
	<?php endif; ?>
</div>
