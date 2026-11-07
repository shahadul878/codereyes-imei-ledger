<?php
/**
 * All Phones screen.
 *
 * @package ImeiPhoneLedger
 *
 * @var array $vars {
 *     @type Imei_Ledger_List_Table $table   List table (items prepared).
 *     @type array                  $summary This month's summary.
 *     @type array                  $filters Current filters.
 * }
 */

defined( 'ABSPATH' ) || exit;

$imei_ledger_filters = $vars['filters'];
?>
<div class="wrap ipl">
	<header class="ipl-header">
		<div>
			<p class="ipl-eyebrow"><?php esc_html_e( 'IMEI Ledger', 'imei-phone-ledger' ); ?></p>
			<h1 class="ipl-title"><?php esc_html_e( 'All phones', 'imei-phone-ledger' ); ?></h1>
		</div>
		<a class="button button-primary" href="<?php echo esc_url( Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_ADD ) ); ?>">
			<?php esc_html_e( 'Add purchase', 'imei-phone-ledger' ); ?>
		</a>
	</header>
	<hr class="wp-header-end">

	<?php Imei_Ledger_Notices::render(); ?>

	<?php
	Imei_Ledger_View::render(
		'partials/summary-cards',
		array(
			'summary' => $vars['summary'],
			'period'  => __( 'This month', 'imei-phone-ledger' ),
		)
	);
	?>

	<form class="ipl-search" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" role="search">
		<input type="hidden" name="page" value="<?php echo esc_attr( Imei_Ledger_View::PAGE_LIST ); ?>">
		<?php if ( '' !== $imei_ledger_filters['status'] ) : ?>
			<input type="hidden" name="status" value="<?php echo esc_attr( $imei_ledger_filters['status'] ); ?>">
		<?php endif; ?>

		<div class="ipl-search__main">
			<label class="screen-reader-text" for="ipl-search-input"><?php esc_html_e( 'Search phones', 'imei-phone-ledger' ); ?></label>
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<input type="search" id="ipl-search-input" name="s" value="<?php echo esc_attr( $imei_ledger_filters['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search by IMEI, name, mobile number, NID or model', 'imei-phone-ledger' ); ?>" autocomplete="off">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Search', 'imei-phone-ledger' ); ?></button>
		</div>

		<details class="ipl-search__more" <?php echo ( '' !== $imei_ledger_filters['date_from'] || '' !== $imei_ledger_filters['date_to'] ) ? 'open' : ''; ?>>
			<summary><?php esc_html_e( 'Filter by date', 'imei-phone-ledger' ); ?></summary>
			<div class="ipl-search__dates">
				<label>
					<span><?php esc_html_e( 'Date of', 'imei-phone-ledger' ); ?></span>
					<select name="date_field">
						<option value="purchase_date" <?php selected( $imei_ledger_filters['date_field'], 'purchase_date' ); ?>><?php esc_html_e( 'Purchase', 'imei-phone-ledger' ); ?></option>
						<option value="sale_date" <?php selected( $imei_ledger_filters['date_field'], 'sale_date' ); ?>><?php esc_html_e( 'Sale', 'imei-phone-ledger' ); ?></option>
					</select>
				</label>
				<label>
					<span><?php esc_html_e( 'From', 'imei-phone-ledger' ); ?></span>
					<input type="date" name="date_from" value="<?php echo esc_attr( $imei_ledger_filters['date_from'] ); ?>">
				</label>
				<label>
					<span><?php esc_html_e( 'To', 'imei-phone-ledger' ); ?></span>
					<input type="date" name="date_to" value="<?php echo esc_attr( $imei_ledger_filters['date_to'] ); ?>">
				</label>
				<button type="submit" class="button"><?php esc_html_e( 'Apply', 'imei-phone-ledger' ); ?></button>
				<a class="button-link" href="<?php echo esc_url( Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST ) ); ?>"><?php esc_html_e( 'Clear', 'imei-phone-ledger' ); ?></a>
			</div>
		</details>
	</form>

	<div class="ipl-table-bar">
		<?php $vars['table']->views(); ?>
		<?php // POST so a searched name, phone or NID is not written into the URL. ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="imei_ledger_export">
			<?php wp_nonce_field( 'imei_ledger_export' ); ?>
			<input type="hidden" name="s" value="<?php echo esc_attr( $imei_ledger_filters['search'] ); ?>">
			<input type="hidden" name="status" value="<?php echo esc_attr( $imei_ledger_filters['status'] ); ?>">
			<input type="hidden" name="date_field" value="<?php echo esc_attr( $imei_ledger_filters['date_field'] ); ?>">
			<input type="hidden" name="date_from" value="<?php echo esc_attr( $imei_ledger_filters['date_from'] ); ?>">
			<input type="hidden" name="date_to" value="<?php echo esc_attr( $imei_ledger_filters['date_to'] ); ?>">
			<button type="submit" class="button">
				<span class="dashicons dashicons-download" aria-hidden="true"></span>
				<?php esc_html_e( 'Export CSV', 'imei-phone-ledger' ); ?>
			</button>
		</form>
	</div>

	<div class="ipl-table">
		<?php $vars['table']->display(); ?>
	</div>
</div>
