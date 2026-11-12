<?php
/**
 * Reports screen.
 *
 * @package CodereyesImeiLedger
 *
 * @var array $vars {
 *     @type string  $from      Y-m-d.
 *     @type string  $to        Y-m-d.
 *     @type array   $presets   Label => array( from, to ).
 *     @type array   $summary   Totals.
 *     @type array[] $sales     Sold records in range (first page).
 *     @type int     $sales_all Total sold records in range.
 *     @type array   $exports   sales | purchases => CSV URL.
 * }
 */

defined( 'ABSPATH' ) || exit;

$imei_ledger_period = sprintf( '%s – %s', Imei_Ledger_View::date( $vars['from'] ), Imei_Ledger_View::date( $vars['to'] ) );
?>
<div class="wrap ipl">
	<header class="ipl-header">
		<div>
			<p class="ipl-eyebrow"><?php esc_html_e( 'IMEI Ledger', 'codereyes-imei-ledger' ); ?></p>
			<h1 class="ipl-title"><?php esc_html_e( 'Reports', 'codereyes-imei-ledger' ); ?></h1>
		</div>
		<div class="ipl-header__actions">
			<a class="button" href="<?php echo esc_url( $vars['exports']['purchases'] ); ?>">
				<span class="dashicons dashicons-download" aria-hidden="true"></span>
				<?php esc_html_e( 'Purchases CSV', 'codereyes-imei-ledger' ); ?>
			</a>
			<a class="button button-primary" href="<?php echo esc_url( $vars['exports']['sales'] ); ?>">
				<span class="dashicons dashicons-download" aria-hidden="true"></span>
				<?php esc_html_e( 'Sales CSV', 'codereyes-imei-ledger' ); ?>
			</a>
		</div>
	</header>
	<hr class="wp-header-end">

	<form class="ipl-range" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( Imei_Ledger_View::PAGE_REPORTS ); ?>">
		<nav class="ipl-range__presets" aria-label="<?php esc_attr_e( 'Quick ranges', 'codereyes-imei-ledger' ); ?>">
			<?php
			// Early in a month several presets cover the same dates; highlight only the last
			// match, so the default range shows as "This month" rather than "Last 7 days".
			$imei_ledger_selected = array( $vars['from'], $vars['to'] );
			$imei_ledger_matches  = array_keys( array_filter( $vars['presets'], static fn( $dates ) => $imei_ledger_selected === $dates ) );
			$imei_ledger_current  = (string) end( $imei_ledger_matches );
			?>
			<?php foreach ( $vars['presets'] as $imei_ledger_label => $imei_ledger_dates ) : ?>
				<?php
				$imei_ledger_active = $imei_ledger_current === (string) $imei_ledger_label;
				$imei_ledger_url    = Imei_Ledger_View::page_url(
					Imei_Ledger_View::PAGE_REPORTS,
					array(
						'from' => $imei_ledger_dates[0],
						'to'   => $imei_ledger_dates[1],
					)
				);
				?>
				<a class="ipl-chip<?php echo $imei_ledger_active ? ' is-active' : ''; ?>" <?php echo $imei_ledger_active ? 'aria-current="true"' : ''; ?> href="<?php echo esc_url( $imei_ledger_url ); ?>">
					<?php echo esc_html( $imei_ledger_label ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<div class="ipl-range__custom">
			<label><span><?php esc_html_e( 'From', 'codereyes-imei-ledger' ); ?></span> <input type="date" name="from" value="<?php echo esc_attr( $vars['from'] ); ?>"></label>
			<label><span><?php esc_html_e( 'To', 'codereyes-imei-ledger' ); ?></span> <input type="date" name="to" value="<?php echo esc_attr( $vars['to'] ); ?>"></label>
			<button type="submit" class="button"><?php esc_html_e( 'Show', 'codereyes-imei-ledger' ); ?></button>
		</div>
	</form>

	<?php
	Imei_Ledger_View::render(
		'partials/summary-cards',
		array(
			'summary' => $vars['summary'],
			'period'  => $imei_ledger_period,
		)
	);
	?>

	<section class="ipl-card ipl-card--flush">
		<h2 class="ipl-card__title">
			<?php esc_html_e( 'Sales in this period', 'codereyes-imei-ledger' ); ?>
			<span class="ipl-count"><?php echo esc_html( number_format_i18n( $vars['sales_all'] ) ); ?></span>
		</h2>

		<?php if ( array() === $vars['sales'] ) : ?>
			<p class="ipl-empty"><?php esc_html_e( 'No phones were sold in this period.', 'codereyes-imei-ledger' ); ?></p>
		<?php else : ?>
			<div class="ipl-scroll">
				<table class="widefat striped ipl-report-table">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Sold on', 'codereyes-imei-ledger' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Phone', 'codereyes-imei-ledger' ); ?></th>
							<th scope="col"><?php esc_html_e( 'IMEI', 'codereyes-imei-ledger' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Buyer', 'codereyes-imei-ledger' ); ?></th>
							<th scope="col" class="ipl-num"><?php esc_html_e( 'Bought for', 'codereyes-imei-ledger' ); ?></th>
							<th scope="col" class="ipl-num"><?php esc_html_e( 'Sold for', 'codereyes-imei-ledger' ); ?></th>
							<th scope="col" class="ipl-num"><?php esc_html_e( 'Profit', 'codereyes-imei-ledger' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $vars['sales'] as $imei_ledger_sale ) : ?>
							<tr>
								<td><?php echo esc_html( Imei_Ledger_View::date( $imei_ledger_sale['sale_date'] ) ); ?></td>
								<td><a href="<?php echo esc_url( Imei_Ledger_View::record_url( 'view', (int) $imei_ledger_sale['id'] ) ); ?>"><?php echo esc_html( Imei_Ledger_View::phone_name( $imei_ledger_sale ) ); ?></a></td>
								<td class="ipl-imei"><?php echo esc_html( Imei_Ledger_View::imei( $imei_ledger_sale['imei1'] ) ); ?></td>
								<td><?php echo esc_html( $imei_ledger_sale['buyer_name'] ); ?></td>
								<td class="ipl-num ipl-amount"><?php echo esc_html( Imei_Ledger_View::money( $imei_ledger_sale['purchase_price'] ) ); ?></td>
								<td class="ipl-num ipl-amount"><?php echo esc_html( Imei_Ledger_View::money( $imei_ledger_sale['sale_price'] ) ); ?></td>
								<td class="ipl-num ipl-amount"><?php Imei_Ledger_View::profit( $imei_ledger_sale ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<?php if ( $vars['sales_all'] > count( $vars['sales'] ) ) : ?>
				<p class="ipl-card__footer">
					<?php
					/* translators: %d: number of sales shown. */
					echo esc_html( sprintf( __( 'Showing the latest %d sales. Download the Sales CSV for all of them.', 'codereyes-imei-ledger' ), count( $vars['sales'] ) ) );
					?>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</section>
</div>
