<?php
/**
 * Add / edit purchase screen.
 *
 * @package ImeiPhoneLedger
 *
 * @var array $vars {
 *     @type array|null $record Record being edited, or null.
 *     @type array      $values Field values.
 *     @type array      $errors Field => message.
 *     @type array|null $state  Form state (duplicate level and matches).
 *     @type string     $today  Y-m-d.
 * }
 */

defined( 'ABSPATH' ) || exit;

$imei_ledger_record = $vars['record'];
$imei_ledger_values = $vars['values'];
$imei_ledger_errors = $vars['errors'];
$imei_ledger_is_new = null === $imei_ledger_record;
$imei_ledger_id     = $imei_ledger_is_new ? 0 : (int) $imei_ledger_record['id'];

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

$imei_ledger_brands = array( 'Samsung', 'Apple', 'Xiaomi', 'Redmi', 'Realme', 'Oppo', 'Vivo', 'OnePlus', 'Tecno', 'Infinix', 'Itel', 'Nokia', 'Motorola', 'Google', 'Huawei', 'Honor', 'Symphony', 'Walton' );
?>
<div class="wrap ipl">
	<header class="ipl-header">
		<div>
			<p class="ipl-eyebrow"><?php esc_html_e( 'IMEI Ledger', 'imei-phone-ledger' ); ?></p>
			<h1 class="ipl-title">
				<?php echo $imei_ledger_is_new ? esc_html__( 'Add purchase', 'imei-phone-ledger' ) : esc_html__( 'Edit purchase', 'imei-phone-ledger' ); ?>
			</h1>
		</div>
		<a class="button" href="<?php echo esc_url( $imei_ledger_is_new ? Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST ) : Imei_Ledger_View::record_url( 'view', $imei_ledger_id ) ); ?>">
			<?php esc_html_e( 'Back', 'imei-phone-ledger' ); ?>
		</a>
	</header>
	<hr class="wp-header-end">

	<?php Imei_Ledger_Notices::render(); ?>

	<?php if ( array() !== $imei_ledger_errors ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Please fix the highlighted fields.', 'imei-phone-ledger' ); ?></p></div>
	<?php endif; ?>

	<form class="ipl-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<input type="hidden" name="action" value="imei_ledger_save_purchase">
		<input type="hidden" name="id" value="<?php echo esc_attr( (string) $imei_ledger_id ); ?>">
		<?php wp_nonce_field( 'imei_ledger_save_purchase' ); ?>

		<?php
		Imei_Ledger_View::render(
			'partials/duplicate-notice',
			array(
				'level'   => $vars['state']['duplicate'] ?? Imei_Ledger_Duplicates::NONE,
				'matches' => $vars['state']['matches'] ?? array(),
			)
		);
		?>

		<section class="ipl-card">
			<h2 class="ipl-card__title"><span class="ipl-step">1</span><?php esc_html_e( 'Phone', 'imei-phone-ledger' ); ?></h2>
			<div class="ipl-grid">
				<div class="ipl-imei-field">
					<?php
					$imei_ledger_field(
						'imei1',
						__( 'IMEI 1', 'imei-phone-ledger' ),
						array(
							'required' => true,
							'hint'     => __( 'Dial *#06# on the phone to see its IMEI.', 'imei-phone-ledger' ),
							'attrs'    => array(
								'inputmode'          => 'numeric',
								'autocomplete'       => 'off',
								'maxlength'          => '24',
								'class'              => 'ipl-mono',
								'data-imei-check'    => '1',
								'data-exclude-id'    => (string) $imei_ledger_id,
								'data-status-target' => 'ipl-imei1-status',
							),
						)
					);
					?>
					<div class="ipl-imei-status" id="ipl-imei1-status" aria-live="polite"></div>
				</div>
				<div class="ipl-imei-field">
					<?php
					$imei_ledger_field(
						'imei2',
						__( 'IMEI 2', 'imei-phone-ledger' ),
						array(
							'hint'  => __( 'Only for dual-SIM phones.', 'imei-phone-ledger' ),
							'attrs' => array(
								'inputmode'          => 'numeric',
								'autocomplete'       => 'off',
								'maxlength'          => '24',
								'class'              => 'ipl-mono',
								'data-imei-check'    => '1',
								'data-exclude-id'    => (string) $imei_ledger_id,
								'data-status-target' => 'ipl-imei2-status',
							),
						)
					);
					?>
					<div class="ipl-imei-status" id="ipl-imei2-status" aria-live="polite"></div>
				</div>
				<?php
				$imei_ledger_field(
					'brand',
					__( 'Brand', 'imei-phone-ledger' ),
					array(
						'required' => true,
						'attrs'    => array(
							'list'      => 'ipl-brands',
							'maxlength' => '100',
						),
					)
				);
				$imei_ledger_field(
					'model',
					__( 'Model', 'imei-phone-ledger' ),
					array(
						'required' => true,
						'attrs'    => array(
							'maxlength'   => '100',
							'placeholder' => __( 'e.g. Galaxy A52', 'imei-phone-ledger' ),
						),
					)
				);
				$imei_ledger_field(
					'storage',
					__( 'RAM / Storage', 'imei-phone-ledger' ),
					array(
						'attrs' => array(
							'maxlength'   => '50',
							'placeholder' => __( 'e.g. 8/128GB', 'imei-phone-ledger' ),
						),
					)
				);
				$imei_ledger_field( 'color', __( 'Color', 'imei-phone-ledger' ), array( 'attrs' => array( 'maxlength' => '50' ) ) );
				$imei_ledger_field(
					'device_condition',
					__( 'Condition', 'imei-phone-ledger' ),
					array(
						'type'     => 'select',
						'required' => true,
						'options'  => Imei_Ledger_Validator::condition_labels(),
					)
				);
				?>
			</div>
			<datalist id="ipl-brands">
				<?php foreach ( $imei_ledger_brands as $imei_ledger_brand ) : ?>
					<option value="<?php echo esc_attr( $imei_ledger_brand ); ?>"></option>
				<?php endforeach; ?>
			</datalist>
		</section>

		<section class="ipl-card">
			<h2 class="ipl-card__title"><span class="ipl-step">2</span><?php esc_html_e( 'Purchase', 'imei-phone-ledger' ); ?></h2>
			<div class="ipl-grid">
				<?php
				$imei_ledger_field(
					'purchase_date',
					__( 'Purchase date', 'imei-phone-ledger' ),
					array(
						'type'     => 'date',
						'required' => true,
						'attrs'    => array( 'max' => $vars['today'] ),
					)
				);
				$imei_ledger_field(
					'purchase_price',
					/* translators: %s: currency symbol. */
					sprintf( __( 'Purchase price (%s)', 'imei-phone-ledger' ), Imei_Ledger_View::currency() ),
					array(
						'required' => true,
						'attrs'    => array(
							'inputmode'    => 'decimal',
							'autocomplete' => 'off',
							'class'        => 'ipl-mono',
						),
					)
				);
				?>
			</div>
		</section>

		<section class="ipl-card">
			<h2 class="ipl-card__title"><span class="ipl-step">3</span><?php esc_html_e( 'Seller', 'imei-phone-ledger' ); ?></h2>
			<p class="ipl-card__lead"><?php esc_html_e( 'Who sold this phone to the shop. Keep a copy of their NID if you can.', 'imei-phone-ledger' ); ?></p>
			<div class="ipl-grid">
				<?php
				$imei_ledger_field(
					'seller_name',
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
					'seller_phone',
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
					'seller_nid',
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
					'seller_address',
					__( 'Address', 'imei-phone-ledger' ),
					array(
						'type' => 'textarea',
						'wide' => true,
					)
				);
				?>
			</div>
		</section>

		<section class="ipl-card">
			<h2 class="ipl-card__title"><span class="ipl-step">4</span><?php esc_html_e( 'Notes', 'imei-phone-ledger' ); ?></h2>
			<div class="ipl-grid">
				<?php
				$imei_ledger_field(
					'notes',
					__( 'Notes', 'imei-phone-ledger' ),
					array(
						'type'  => 'textarea',
						'wide'  => true,
						'attrs' => array( 'placeholder' => __( 'Box, charger, scratches, battery health…', 'imei-phone-ledger' ) ),
					)
				);
				?>
			</div>
		</section>

		<div class="ipl-actions">
			<button type="submit" class="button button-primary button-hero">
				<?php echo $imei_ledger_is_new ? esc_html__( 'Save purchase', 'imei-phone-ledger' ) : esc_html__( 'Update purchase', 'imei-phone-ledger' ); ?>
			</button>
		</div>
	</form>
</div>
