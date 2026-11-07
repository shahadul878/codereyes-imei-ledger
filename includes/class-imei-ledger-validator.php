<?php
/**
 * Validates and sanitises purchase and sale input.
 *
 * Pure: takes raw (already unslashed) request arrays and returns a new
 * array( 'data' => clean values, 'errors' => field => message ). Only known
 * fields are copied, so the caller can pass $_POST straight in.
 *
 * @package ImeiPhoneLedger
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * Input rules for ledger records.
 */
final class Imei_Ledger_Validator {

	public const CONDITIONS = array( 'new', 'like_new', 'good', 'fair', 'poor' );

	/**
	 * Purchase fields, in form order.
	 */
	public const PURCHASE_FIELDS = array(
		'imei1',
		'imei2',
		'brand',
		'model',
		'color',
		'storage',
		'device_condition',
		'purchase_date',
		'purchase_price',
		'seller_name',
		'seller_phone',
		'seller_nid',
		'seller_address',
		'notes',
	);

	/**
	 * Sale fields, in form order.
	 */
	public const SALE_FIELDS = array( 'sale_date', 'sale_price', 'buyer_name', 'buyer_phone', 'buyer_nid', 'buyer_address' );

	/**
	 * Longest address or note kept, in characters.
	 */
	public const TEXTAREA_LIMIT = 2000;

	private const NID_LENGTHS = array( 10, 13, 17 );

	private const PHONE_PATTERN = '/^\+?\d{6,15}$/';

	private const DATE_FORMAT = 'Y-m-d';

	/**
	 * Short text fields and their column lengths.
	 */
	private const TEXT_LIMITS = array(
		'brand'       => 100,
		'model'       => 100,
		'color'       => 50,
		'storage'     => 50,
		'seller_name' => 150,
		'buyer_name'  => 150,
	);

	/**
	 * Human labels for device conditions.
	 *
	 * @return array<string,string>
	 */
	public static function condition_labels(): array {
		return array(
			'new'      => __( 'New', 'imei-phone-ledger' ),
			'like_new' => __( 'Like new', 'imei-phone-ledger' ),
			'good'     => __( 'Good', 'imei-phone-ledger' ),
			'fair'     => __( 'Fair', 'imei-phone-ledger' ),
			'poor'     => __( 'Poor', 'imei-phone-ledger' ),
		);
	}

	/**
	 * Validate the purchase side of a record.
	 *
	 * @param array  $input Raw input.
	 * @param string $today Today as Y-m-d (site timezone).
	 * @return array{data: array<string,string>, errors: array<string,string>}
	 */
	public static function validate_purchase( array $input, string $today ): array {
		$checks = array(
			'imei1'            => self::imei( $input, 'imei1', true ),
			'imei2'            => self::imei( $input, 'imei2', false ),
			'brand'            => self::text( $input, 'brand', true ),
			'model'            => self::text( $input, 'model', true ),
			'color'            => self::text( $input, 'color', false ),
			'storage'          => self::text( $input, 'storage', false ),
			'device_condition' => self::condition( $input ),
			'purchase_date'    => self::date( $input, 'purchase_date', $today, null ),
			'purchase_price'   => self::money( $input, 'purchase_price' ),
			'seller_name'      => self::text( $input, 'seller_name', true ),
			'seller_phone'     => self::phone( $input, 'seller_phone' ),
			'seller_nid'       => self::nid( $input, 'seller_nid' ),
			'seller_address'   => self::textarea( $input, 'seller_address' ),
			'notes'            => self::textarea( $input, 'notes' ),
		);

		$result = self::collect( $checks );

		$has_both_imeis = '' !== $result['data']['imei2'] && ! isset( $result['errors']['imei2'] );
		if ( $has_both_imeis && $result['data']['imei1'] === $result['data']['imei2'] ) {
			$result['errors']['imei2'] = __( 'IMEI 2 must be different from IMEI 1.', 'imei-phone-ledger' );
		}

		return $result;
	}

	/**
	 * Validate the sale side of a record.
	 *
	 * @param array  $input         Raw input.
	 * @param string $purchase_date The record's purchase date (Y-m-d).
	 * @param string $today         Today as Y-m-d (site timezone).
	 * @return array{data: array<string,string>, errors: array<string,string>}
	 */
	public static function validate_sale( array $input, string $purchase_date, string $today ): array {
		return self::collect(
			array(
				'sale_date'     => self::date( $input, 'sale_date', $today, $purchase_date ),
				'sale_price'    => self::money( $input, 'sale_price' ),
				'buyer_name'    => self::text( $input, 'buyer_name', true ),
				'buyer_phone'   => self::phone( $input, 'buyer_phone' ),
				'buyer_nid'     => self::nid( $input, 'buyer_nid' ),
				'buyer_address' => self::textarea( $input, 'buyer_address' ),
			)
		);
	}

	/**
	 * Split per-field results into data and errors.
	 *
	 * @param array<string,array{0:string,1:?string}> $checks Field => array( value, error ).
	 * @return array{data: array<string,string>, errors: array<string,string>}
	 */
	private static function collect( array $checks ): array {
		$data   = array_map(
			static function ( array $check ): string {
				return $check[0];
			},
			$checks
		);
		$errors = array_filter(
			array_map(
				static function ( array $check ): ?string {
					return $check[1];
				},
				$checks
			)
		);

		return array(
			'data'   => $data,
			'errors' => $errors,
		);
	}

	/**
	 * Read a scalar input value as a string.
	 *
	 * @param array  $input Raw input.
	 * @param string $key   Field.
	 * @return string
	 */
	private static function raw( array $input, string $key ): string {
		$value = $input[ $key ] ?? '';

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * IMEI field.
	 *
	 * @param array  $input    Raw input.
	 * @param string $key      Field.
	 * @param bool   $required Whether it must be present.
	 * @return array{0:string,1:?string}
	 */
	private static function imei( array $input, string $key, bool $required ): array {
		$value = Imei_Ledger_Imei::normalize( self::raw( $input, $key ) );

		if ( '' === $value ) {
			return array( '', $required ? __( 'IMEI is required.', 'imei-phone-ledger' ) : null );
		}

		if ( ! Imei_Ledger_Imei::is_valid( $value ) ) {
			return array( $value, __( 'This is not a valid IMEI. It must be 15 digits and pass the check-digit test. Dial *#06# on the phone to see it.', 'imei-phone-ledger' ) );
		}

		return array( $value, null );
	}

	/**
	 * Single-line text field.
	 *
	 * @param array  $input    Raw input.
	 * @param string $key      Field.
	 * @param bool   $required Whether it must be present.
	 * @return array{0:string,1:?string}
	 */
	private static function text( array $input, string $key, bool $required ): array {
		$value = sanitize_text_field( self::raw( $input, $key ) );
		$value = mb_substr( $value, 0, self::TEXT_LIMITS[ $key ] ?? 191 );

		if ( $required && '' === $value ) {
			return array( '', __( 'This field is required.', 'imei-phone-ledger' ) );
		}

		return array( $value, null );
	}

	/**
	 * Multi-line text field (never required).
	 *
	 * @param array  $input Raw input.
	 * @param string $key   Field.
	 * @return array{0:string,1:?string}
	 */
	private static function textarea( array $input, string $key ): array {
		return array( mb_substr( sanitize_textarea_field( self::raw( $input, $key ) ), 0, self::TEXTAREA_LIMIT ), null );
	}

	/**
	 * Device condition from the whitelist.
	 *
	 * @param array $input Raw input.
	 * @return array{0:string,1:?string}
	 */
	private static function condition( array $input ): array {
		$value = self::raw( $input, 'device_condition' );

		if ( ! in_array( $value, self::CONDITIONS, true ) ) {
			return array( '', __( 'Choose the phone condition.', 'imei-phone-ledger' ) );
		}

		return array( $value, null );
	}

	/**
	 * Date field: real calendar date, not in the future, not before $min.
	 *
	 * @param array       $input Raw input.
	 * @param string      $key   Field.
	 * @param string      $today Today (Y-m-d).
	 * @param string|null $min   Earliest allowed date (Y-m-d).
	 * @return array{0:string,1:?string}
	 */
	private static function date( array $input, string $key, string $today, ?string $min ): array {
		$value = trim( Imei_Ledger_Digits::to_ascii( self::raw( $input, $key ) ) );

		if ( '' === $value ) {
			return array( '', __( 'Date is required.', 'imei-phone-ledger' ) );
		}

		if ( ! self::is_date( $value ) ) {
			return array( '', __( 'Enter a real date.', 'imei-phone-ledger' ) );
		}

		if ( $value > $today ) {
			return array( $value, __( 'The date cannot be in the future.', 'imei-phone-ledger' ) );
		}

		if ( null !== $min && $value < $min ) {
			return array( $value, __( 'The sale date cannot be before the purchase date.', 'imei-phone-ledger' ) );
		}

		return array( $value, null );
	}

	/**
	 * Whether a string is a real Y-m-d date (rejects 2026-02-30).
	 *
	 * @param string $value Candidate.
	 * @return bool
	 */
	public static function is_date( string $value ): bool {
		$date = DateTimeImmutable::createFromFormat( '!' . self::DATE_FORMAT, $value );

		return false !== $date && $date->format( self::DATE_FORMAT ) === $value;
	}

	/**
	 * Required, non-negative amount.
	 *
	 * @param array  $input Raw input.
	 * @param string $key   Field.
	 * @return array{0:string,1:?string}
	 */
	private static function money( array $input, string $key ): array {
		$raw = trim( self::raw( $input, $key ) );

		if ( '' === $raw ) {
			return array( '', __( 'Price is required.', 'imei-phone-ledger' ) );
		}

		$value = Imei_Ledger_Money::parse( $raw );

		if ( null === $value ) {
			return array( '', __( 'Enter a price such as 15000 or 15000.50.', 'imei-phone-ledger' ) );
		}

		return array( $value, null );
	}

	/**
	 * Required phone number: optional leading +, 6–15 digits.
	 *
	 * @param array  $input Raw input.
	 * @param string $key   Field.
	 * @return array{0:string,1:?string}
	 */
	private static function phone( array $input, string $key ): array {
		$value = Imei_Ledger_Digits::to_ascii( self::raw( $input, $key ) );
		$value = (string) preg_replace( '/[\s\-().]+/', '', $value );

		if ( '' === $value ) {
			return array( '', __( 'Phone number is required.', 'imei-phone-ledger' ) );
		}

		if ( 1 !== preg_match( self::PHONE_PATTERN, $value ) ) {
			return array( '', __( 'Enter a phone number with 6 to 15 digits.', 'imei-phone-ledger' ) );
		}

		return array( $value, null );
	}

	/**
	 * Optional Bangladesh NID: 10, 13 or 17 digits.
	 *
	 * @param array  $input Raw input.
	 * @param string $key   Field.
	 * @return array{0:string,1:?string}
	 */
	private static function nid( array $input, string $key ): array {
		$raw   = trim( self::raw( $input, $key ) );
		$value = Imei_Ledger_Digits::only( $raw );

		if ( '' === $raw ) {
			return array( '', null );
		}

		if ( ! in_array( strlen( $value ), self::NID_LENGTHS, true ) ) {
			return array( '', __( 'An NID number has 10, 13 or 17 digits.', 'imei-phone-ledger' ) );
		}

		return array( $value, null );
	}
}
