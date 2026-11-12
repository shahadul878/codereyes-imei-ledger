<?php
/**
 * Single record screen.
 *
 * @package CodereyesImeiLedger
 *
 * @var array $vars {
 *     @type array   $record  Record.
 *     @type array[] $history Other records with the same IMEI.
 * }
 */

defined( 'ABSPATH' ) || exit;

$imei_ledger_record     = $vars['record'];
$imei_ledger_id         = (int) $imei_ledger_record['id'];
$imei_ledger_is_sold    = Imei_Ledger_Status::SOLD === $imei_ledger_record['status'];
$imei_ledger_conditions = Imei_Ledger_Validator::condition_labels();

$imei_ledger_rows = static function ( array $rows ): void {
	foreach ( $rows as $label => $value ) {
		if ( '' === (string) $value ) {
			continue;
		}

		printf( '<div><dt>%1$s</dt><dd>%2$s</dd></div>', esc_html( $label ), nl2br( esc_html( (string) $value ) ) );
	}
};
?>
<div class="wrap ipl">
	<header class="ipl-header">
		<div>
			<p class="ipl-eyebrow">
				<?php
				/* translators: %d: record ID. */
				echo esc_html( sprintf( __( 'Record #%d', 'codereyes-imei-ledger' ), $imei_ledger_id ) );
				?>
			</p>
			<h1 class="ipl-title"><?php echo esc_html( Imei_Ledger_View::phone_name( $imei_ledger_record ) ); ?></h1>
		</div>
		<div class="ipl-header__actions">
			<a class="button" href="<?php echo esc_url( Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST ) ); ?>"><?php esc_html_e( 'All phones', 'codereyes-imei-ledger' ); ?></a>
			<a class="button" href="<?php echo esc_url( Imei_Ledger_View::record_url( 'edit', $imei_ledger_id ) ); ?>"><?php esc_html_e( 'Edit purchase', 'codereyes-imei-ledger' ); ?></a>
			<?php if ( $imei_ledger_is_sold ) : ?>
				<a class="button" href="<?php echo esc_url( Imei_Ledger_View::record_url( 'sell', $imei_ledger_id ) ); ?>"><?php esc_html_e( 'Edit sale', 'codereyes-imei-ledger' ); ?></a>
			<?php else : ?>
				<a class="button button-primary" href="<?php echo esc_url( Imei_Ledger_View::record_url( 'sell', $imei_ledger_id ) ); ?>"><?php esc_html_e( 'Sell this phone', 'codereyes-imei-ledger' ); ?></a>
			<?php endif; ?>
		</div>
	</header>
	<hr class="wp-header-end">

	<?php Imei_Ledger_Notices::render(); ?>

	<section class="ipl-device">
		<div>
			<p class="ipl-device__status"><?php Imei_Ledger_View::status_badge( $imei_ledger_record['status'] ); ?></p>
			<p class="ipl-imei ipl-device__imei"><?php echo esc_html( Imei_Ledger_View::imei( $imei_ledger_record['imei1'] ) ); ?></p>
			<?php if ( '' !== $imei_ledger_record['imei2'] ) : ?>
				<p class="ipl-imei ipl-muted"><?php echo esc_html( Imei_Ledger_View::imei( $imei_ledger_record['imei2'] ) ); ?></p>
			<?php endif; ?>
			<p class="ipl-device__neir">
				<a href="<?php echo esc_url( Imei_Ledger_View::NEIR_URL ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Check this IMEI on BTRC NEIR', 'codereyes-imei-ledger' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'codereyes-imei-ledger' ); ?></span>
				</a>
			</p>
		</div>
		<dl class="ipl-device__facts">
			<div>
				<dt><?php esc_html_e( 'Bought for', 'codereyes-imei-ledger' ); ?></dt>
				<dd class="ipl-amount"><?php echo esc_html( Imei_Ledger_View::money( $imei_ledger_record['purchase_price'] ) ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Sold for', 'codereyes-imei-ledger' ); ?></dt>
				<dd class="ipl-amount"><?php echo esc_html( $imei_ledger_is_sold ? Imei_Ledger_View::money( $imei_ledger_record['sale_price'] ) : '—' ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Profit', 'codereyes-imei-ledger' ); ?></dt>
				<dd class="ipl-amount"><?php Imei_Ledger_View::profit( $imei_ledger_record ); ?></dd>
			</div>
		</dl>
	</section>

	<div class="ipl-columns">
		<section class="ipl-card">
			<h2 class="ipl-card__title"><?php esc_html_e( 'Purchase', 'codereyes-imei-ledger' ); ?></h2>
			<dl class="ipl-details">
				<?php
				$imei_ledger_rows(
					array(
						__( 'Date', 'codereyes-imei-ledger' )  => Imei_Ledger_View::date( $imei_ledger_record['purchase_date'] ),
						__( 'Seller', 'codereyes-imei-ledger' ) => $imei_ledger_record['seller_name'],
						__( 'Mobile number', 'codereyes-imei-ledger' ) => $imei_ledger_record['seller_phone'],
						__( 'NID number', 'codereyes-imei-ledger' ) => $imei_ledger_record['seller_nid'],
						__( 'Address', 'codereyes-imei-ledger' ) => (string) $imei_ledger_record['seller_address'],
						__( 'Condition', 'codereyes-imei-ledger' ) => $imei_ledger_conditions[ $imei_ledger_record['device_condition'] ] ?? '',
						__( 'Notes', 'codereyes-imei-ledger' ) => (string) $imei_ledger_record['notes'],
					)
				);
				?>
			</dl>
		</section>

		<section class="ipl-card">
			<h2 class="ipl-card__title"><?php esc_html_e( 'Sale', 'codereyes-imei-ledger' ); ?></h2>
			<?php if ( $imei_ledger_is_sold ) : ?>
				<dl class="ipl-details">
					<?php
					$imei_ledger_rows(
						array(
							__( 'Date', 'codereyes-imei-ledger' )          => Imei_Ledger_View::date( $imei_ledger_record['sale_date'] ),
							__( 'Buyer', 'codereyes-imei-ledger' )         => $imei_ledger_record['buyer_name'],
							__( 'Mobile number', 'codereyes-imei-ledger' ) => $imei_ledger_record['buyer_phone'],
							__( 'NID number', 'codereyes-imei-ledger' )    => $imei_ledger_record['buyer_nid'],
							__( 'Address', 'codereyes-imei-ledger' )       => (string) $imei_ledger_record['buyer_address'],
						)
					);
					?>
				</dl>
				<p class="ipl-card__footer">
					<a class="ipl-confirm-cancel button-link" href="<?php echo esc_url( Imei_Ledger_View::record_action_url( 'cancel_sale', $imei_ledger_id ) ); ?>">
						<?php esc_html_e( 'Cancel this sale', 'codereyes-imei-ledger' ); ?>
					</a>
				</p>
			<?php else : ?>
				<p class="ipl-empty"><?php esc_html_e( 'This phone is still in stock.', 'codereyes-imei-ledger' ); ?></p>
				<a class="button button-primary" href="<?php echo esc_url( Imei_Ledger_View::record_url( 'sell', $imei_ledger_id ) ); ?>"><?php esc_html_e( 'Sell this phone', 'codereyes-imei-ledger' ); ?></a>
			<?php endif; ?>
		</section>
	</div>

	<?php if ( array() !== $vars['history'] ) : ?>
		<section class="ipl-card">
			<h2 class="ipl-card__title"><?php esc_html_e( 'Other records with this IMEI', 'codereyes-imei-ledger' ); ?></h2>
			<ul class="ipl-history">
				<?php foreach ( $vars['history'] as $imei_ledger_other ) : ?>
					<li>
						<a href="<?php echo esc_url( Imei_Ledger_View::record_url( 'view', (int) $imei_ledger_other['id'] ) ); ?>">
							<?php
							/* translators: %d: record ID. */
							echo esc_html( sprintf( __( 'Record #%d', 'codereyes-imei-ledger' ), (int) $imei_ledger_other['id'] ) );
							?>
						</a>
						<?php Imei_Ledger_View::status_badge( $imei_ledger_other['status'] ); ?>
						<span class="ipl-muted">
							<?php
							/* translators: 1: purchase date, 2: seller name. */
							echo esc_html( sprintf( __( 'Bought %1$s from %2$s', 'codereyes-imei-ledger' ), Imei_Ledger_View::date( $imei_ledger_other['purchase_date'] ), $imei_ledger_other['seller_name'] ) );
							?>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endif; ?>

	<p class="ipl-danger-zone">
		<a class="ipl-confirm-delete ipl-link-danger" href="<?php echo esc_url( Imei_Ledger_View::record_action_url( 'delete', $imei_ledger_id ) ); ?>">
			<?php esc_html_e( 'Delete this record', 'codereyes-imei-ledger' ); ?>
		</a>
	</p>
</div>
