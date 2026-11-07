<?php
/**
 * Sell (or correct a sale) screen.
 *
 * @package ImeiPhoneLedger
 *
 * @var array $vars {
 *     @type array  $record Record.
 *     @type array  $values Field values.
 *     @type array  $errors Field => message.
 *     @type string $today  Y-m-d.
 * }
 */

defined( 'ABSPATH' ) || exit;

$imei_ledger_record = $vars['record'];
$imei_ledger_values = $vars['values'];
$imei_ledger_errors = $vars['errors'];
$imei_ledger_id     = (int) $imei_ledger_record['id'];
$imei_ledger_resale = Imei_Ledger_Status::SOLD === $imei_ledger_record['status'];

$imei_ledger_field = static function ( string $name, string $label, array $extra = array() ) use ( $imei_ledger_values, $imei_ledger_errors ): void {
	$value = $imei_ledger_values[ $name ] ?? '';

	Imei_Ledger_View::field(
		array_merge(
			array(
				'name'  => $name,
				'label' => $label,
				'value' => is_scalar( $value ) ? (string) $value : '',
				'error' => $imei_ledger_errors[ $name ] ?? '',
			),
			$extra
		)
	);
};
?>
<div class="wrap ipl">
	<header class="ipl-header">
		<div>
			<p class="ipl-eyebrow"><?php esc_html_e( 'IMEI Ledger', 'imei-phone-ledger' ); ?></p>
			<h1 class="ipl-title">
				<?php echo $imei_ledger_resale ? esc_html__( 'Edit sale', 'imei-phone-ledger' ) : esc_html__( 'Sell phone', 'imei-phone-ledger' ); ?>
			</h1>
		</div>
		<a class="button" href="<?php echo esc_url( Imei_Ledger_View::record_url( 'view', $imei_ledger_id ) ); ?>"><?php esc_html_e( 'Back', 'imei-phone-ledger' ); ?></a>
	</header>
	<hr class="wp-header-end">

	<?php Imei_Ledger_Notices::render(); ?>

	<?php if ( array() !== $imei_ledger_errors ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Please fix the highlighted fields.', 'imei-phone-ledger' ); ?></p></div>
	<?php endif; ?>

	<section class="ipl-device">
		<div>
			<p class="ipl-device__name"><?php echo esc_html( Imei_Ledger_View::phone_name( $imei_ledger_record ) ); ?></p>
			<p class="ipl-imei ipl-device__imei"><?php echo esc_html( Imei_Ledger_View::imei( $imei_ledger_record['imei1'] ) ); ?></p>
		</div>
		<dl class="ipl-device__facts">
			<div>
				<dt><?php esc_html_e( 'Bought for', 'imei-phone-ledger' ); ?></dt>
				<dd class="ipl-amount"><?php echo esc_html( Imei_Ledger_View::money( $imei_ledger_record['purchase_price'] ) ); ?></dd>
			</div>
			<div>
				<dt><?php esc_html_e( 'Bought on', 'imei-phone-ledger' ); ?></dt>
				<dd><?php echo esc_html( Imei_Ledger_View::date( $imei_ledger_record['purchase_date'] ) ); ?></dd>
			</div>
		</dl>
	</section>

	<form class="ipl-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<input type="hidden" name="action" value="imei_ledger_save_sale">
		<input type="hidden" name="id" value="<?php echo esc_attr( (string) $imei_ledger_id ); ?>">
		<?php wp_nonce_field( 'imei_ledger_save_sale' ); ?>

		<section class="ipl-card">
			<h2 class="ipl-card__title"><span class="ipl-step">1</span><?php esc_html_e( 'Sale', 'imei-phone-ledger' ); ?></h2>
			<div class="ipl-grid">
				<?php
				$imei_ledger_field(
					'sale_date',
					__( 'Sale date', 'imei-phone-ledger' ),
					array(
						'type'     => 'date',
						'required' => true,
						'attrs'    => array(
							'min' => (string) $imei_ledger_record['purchase_date'],
							'max' => $vars['today'],
						),
					)
				);
				?>
				<div>
					<?php
					$imei_ledger_field(
						'sale_price',
						/* translators: %s: currency symbol. */
						sprintf( __( 'Sale price (%s)', 'imei-phone-ledger' ), Imei_Ledger_View::currency() ),
						array(
							'required' => true,
							'attrs'    => array(
								'inputmode'           => 'decimal',
								'autocomplete'        => 'off',
								'class'               => 'ipl-mono',
								'data-profit-preview' => (string) $imei_ledger_record['purchase_price'],
							),
						)
					);
					?>
					<p class="ipl-profit-preview" id="ipl-profit-preview" aria-live="polite"></p>
				</div>
			</div>
		</section>

		<section class="ipl-card">
			<h2 class="ipl-card__title"><span class="ipl-step">2</span><?php esc_html_e( 'Buyer', 'imei-phone-ledger' ); ?></h2>
			<div class="ipl-grid">
				<?php
				$imei_ledger_field(
					'buyer_name',
					__( 'Name', 'imei-phone-ledger' ),
					array(
						'required' => true,
						'attrs'    => array(
							'maxlength'    => '150',
							'autocomplete' => 'off',
						),
					)
				);
				$imei_ledger_field(
					'buyer_phone',
					__( 'Mobile number', 'imei-phone-ledger' ),
					array(
						'type'     => 'tel',
						'required' => true,
						'attrs'    => array(
							'autocomplete' => 'off',
							'placeholder'  => '01XXXXXXXXX',
						),
					)
				);
				$imei_ledger_field(
					'buyer_nid',
					__( 'NID number', 'imei-phone-ledger' ),
					array(
						'hint'  => __( '10, 13 or 17 digits.', 'imei-phone-ledger' ),
						'attrs' => array(
							'inputmode'    => 'numeric',
							'autocomplete' => 'off',
						),
					)
				);
				$imei_ledger_field(
					'buyer_address',
					__( 'Address', 'imei-phone-ledger' ),
					array(
						'type' => 'textarea',
						'wide' => true,
					)
				);
				?>
			</div>
		</section>

		<div class="ipl-actions">
			<button type="submit" class="button button-primary button-hero">
				<?php echo $imei_ledger_resale ? esc_html__( 'Update sale', 'imei-phone-ledger' ) : esc_html__( 'Save sale', 'imei-phone-ledger' ); ?>
			</button>
		</div>
	</form>
</div>
