<?php
/**
 * Tests for purchase and sale input validation.
 *
 * @package ImeiPhoneLedger
 */

use PHPUnit\Framework\TestCase;

final class ValidatorTest extends TestCase {

	const TODAY = '2026-11-07';

	private function valid_purchase( array $overrides = array() ) {
		return array_merge(
			array(
				'imei1'            => '490154203237518',
				'imei2'            => '',
				'brand'            => 'Samsung',
				'model'            => 'Galaxy A52',
				'color'            => 'Black',
				'storage'          => '8/128GB',
				'device_condition' => 'good',
				'purchase_date'    => '2026-11-01',
				'purchase_price'   => '15,000',
				'seller_name'      => 'Rahim Uddin',
				'seller_phone'     => '01711-223344',
				'seller_nid'       => '1234567890',
				'seller_address'   => 'Mirpur, Dhaka',
				'notes'            => 'Box missing',
			),
			$overrides
		);
	}

	private function valid_sale( array $overrides = array() ) {
		return array_merge(
			array(
				'sale_date'     => '2026-11-05',
				'sale_price'    => '17500',
				'buyer_name'    => 'Karim Mia',
				'buyer_phone'   => '+8801811223344',
				'buyer_nid'     => '',
				'buyer_address' => '',
			),
			$overrides
		);
	}

	public function test_valid_purchase_returns_clean_data() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase(), self::TODAY );

		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( '490154203237518', $result['data']['imei1'] );
		$this->assertSame( '15000.00', $result['data']['purchase_price'] );
		$this->assertSame( '01711223344', $result['data']['seller_phone'] );
		$this->assertSame( 'good', $result['data']['device_condition'] );
	}

	public function test_input_array_is_not_mutated() {
		$input = $this->valid_purchase();
		$copy  = $input;

		Imei_Ledger_Validator::validate_purchase( $input, self::TODAY );

		$this->assertSame( $copy, $input );
	}

	public function test_unknown_fields_are_dropped() {
		$result = Imei_Ledger_Validator::validate_purchase(
			$this->valid_purchase( array( 'status' => 'sold', 'id' => 99 ) ),
			self::TODAY
		);

		$this->assertArrayNotHasKey( 'status', $result['data'] );
		$this->assertArrayNotHasKey( 'id', $result['data'] );
	}

	public function test_invalid_imei_is_reported() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'imei1' => '490154203237519' ) ), self::TODAY );

		$this->assertArrayHasKey( 'imei1', $result['errors'] );
	}

	public function test_second_imei_must_differ_from_first() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'imei2' => '490154203237518' ) ), self::TODAY );

		$this->assertArrayHasKey( 'imei2', $result['errors'] );
	}

	public function test_valid_second_imei_is_accepted() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'imei2' => '356938035643809' ) ), self::TODAY );

		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( '356938035643809', $result['data']['imei2'] );
	}

	public function test_required_fields_are_reported() {
		$result = Imei_Ledger_Validator::validate_purchase(
			$this->valid_purchase(
				array(
					'brand'          => '',
					'model'          => ' ',
					'seller_name'    => '',
					'seller_phone'   => '',
					'purchase_price' => '',
					'purchase_date'  => '',
				)
			),
			self::TODAY
		);

		foreach ( array( 'brand', 'model', 'seller_name', 'seller_phone', 'purchase_price', 'purchase_date' ) as $field ) {
			$this->assertArrayHasKey( $field, $result['errors'], $field );
		}
	}

	public function test_future_purchase_date_is_rejected() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'purchase_date' => '2026-11-08' ) ), self::TODAY );

		$this->assertArrayHasKey( 'purchase_date', $result['errors'] );
	}

	public function test_impossible_date_is_rejected() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'purchase_date' => '2026-02-30' ) ), self::TODAY );

		$this->assertArrayHasKey( 'purchase_date', $result['errors'] );
	}

	public function test_unknown_condition_is_rejected() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'device_condition' => 'broken' ) ), self::TODAY );

		$this->assertArrayHasKey( 'device_condition', $result['errors'] );
	}

	public function test_nid_must_have_bangladesh_length() {
		$bad  = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'seller_nid' => '12345' ) ), self::TODAY );
		$good = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'seller_nid' => '১২৩৪৫৬৭৮৯০১২৩' ) ), self::TODAY );

		$this->assertArrayHasKey( 'seller_nid', $bad['errors'] );
		$this->assertSame( '1234567890123', $good['data']['seller_nid'] );
	}

	public function test_phone_with_too_few_digits_is_rejected() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'seller_phone' => '123' ) ), self::TODAY );

		$this->assertArrayHasKey( 'seller_phone', $result['errors'] );
	}

	public function test_text_fields_are_sanitized() {
		$result = Imei_Ledger_Validator::validate_purchase(
			$this->valid_purchase( array( 'seller_name' => '<script>x</script>Rahim' ) ),
			self::TODAY
		);

		$this->assertSame( 'xRahim', $result['data']['seller_name'] );
	}

	public function test_long_textareas_are_capped() {
		$result = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase( array( 'notes' => str_repeat( 'a', 5000 ) ) ), self::TODAY );

		$this->assertSame( Imei_Ledger_Validator::TEXTAREA_LIMIT, mb_strlen( $result['data']['notes'] ) );
	}

	public function test_form_fields_lists_every_purchase_and_sale_field() {
		$purchase = Imei_Ledger_Validator::validate_purchase( $this->valid_purchase(), self::TODAY );
		$sale     = Imei_Ledger_Validator::validate_sale( $this->valid_sale(), '2026-11-01', self::TODAY );

		$this->assertSame( array_keys( $purchase['data'] ), Imei_Ledger_Validator::PURCHASE_FIELDS );
		$this->assertSame( array_keys( $sale['data'] ), Imei_Ledger_Validator::SALE_FIELDS );
	}

	public function test_valid_sale_returns_clean_data() {
		$result = Imei_Ledger_Validator::validate_sale( $this->valid_sale(), '2026-11-01', self::TODAY );

		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( '17500.00', $result['data']['sale_price'] );
		$this->assertSame( '+8801811223344', $result['data']['buyer_phone'] );
	}

	public function test_sale_before_purchase_is_rejected() {
		$result = Imei_Ledger_Validator::validate_sale( $this->valid_sale( array( 'sale_date' => '2026-10-31' ) ), '2026-11-01', self::TODAY );

		$this->assertArrayHasKey( 'sale_date', $result['errors'] );
	}

	public function test_sale_requires_buyer_and_price() {
		$result = Imei_Ledger_Validator::validate_sale(
			$this->valid_sale( array( 'buyer_name' => '', 'buyer_phone' => '', 'sale_price' => 'x' ) ),
			'2026-11-01',
			self::TODAY
		);

		$this->assertArrayHasKey( 'buyer_name', $result['errors'] );
		$this->assertArrayHasKey( 'buyer_phone', $result['errors'] );
		$this->assertArrayHasKey( 'sale_price', $result['errors'] );
	}
}
