<?php
/**
 * Tests for money parsing and formatting.
 *
 * @package CodereyesImeiLedger
 */

use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase {

	public function test_parse_plain_number() {
		$this->assertSame( '12500.00', Imei_Ledger_Money::parse( '12500' ) );
	}

	public function test_parse_with_commas_and_decimals() {
		$this->assertSame( '12500.50', Imei_Ledger_Money::parse( '12,500.5' ) );
	}

	public function test_parse_bengali_digits_and_currency_symbol() {
		$this->assertSame( '15000.00', Imei_Ledger_Money::parse( '৳ ১৫,০০০' ) );
	}

	public function test_parse_rejects_negative_and_garbage() {
		$this->assertNull( Imei_Ledger_Money::parse( '-100' ) );
		$this->assertNull( Imei_Ledger_Money::parse( 'abc' ) );
		$this->assertNull( Imei_Ledger_Money::parse( '' ) );
		$this->assertNull( Imei_Ledger_Money::parse( '1.2.3' ) );
		$this->assertNull( Imei_Ledger_Money::parse( '10.555' ) );
	}

	public function test_parse_accepts_lakh_grouping() {
		$this->assertSame( '150000.00', Imei_Ledger_Money::parse( '1,50,000' ) );
		$this->assertSame( '1500000.00', Imei_Ledger_Money::parse( '1,500,000' ) );
	}

	public function test_parse_accepts_currency_as_prefix_or_suffix() {
		$this->assertSame( '500.00', Imei_Ledger_Money::parse( 'Tk 500' ) );
		$this->assertSame( '500.00', Imei_Ledger_Money::parse( '500 BDT' ) );
	}

	public function test_parse_rejects_ambiguous_commas() {
		$this->assertNull( Imei_Ledger_Money::parse( '15000,50' ) );
		$this->assertNull( Imei_Ledger_Money::parse( '1,5' ) );
		$this->assertNull( Imei_Ledger_Money::parse( '15,00,0' ) );
	}

	public function test_parse_rejects_currency_inside_number() {
		$this->assertNull( Imei_Ledger_Money::parse( '1tk5' ) );
	}

	public function test_parse_rejects_values_above_column_limit() {
		$this->assertNull( Imei_Ledger_Money::parse( '10000000000' ) );
	}

	public function test_to_cents_and_back() {
		$this->assertSame( 1250050, Imei_Ledger_Money::to_cents( '12500.50' ) );
		$this->assertSame( '12500.50', Imei_Ledger_Money::from_cents( 1250050 ) );
		$this->assertSame( '-300.25', Imei_Ledger_Money::from_cents( -30025 ) );
	}

	public function test_profit_for_sold_record() {
		$this->assertSame( '2500.00', Imei_Ledger_Money::profit( '10000.00', '12500.00' ) );
		$this->assertSame( '-500.00', Imei_Ledger_Money::profit( '10000.00', '9500.00' ) );
	}

	public function test_profit_is_null_when_unsold() {
		$this->assertNull( Imei_Ledger_Money::profit( '10000.00', null ) );
	}

	public function test_format_groups_thousands() {
		$this->assertSame( '৳12,500.50', Imei_Ledger_Money::format( '12500.50', '৳' ) );
		$this->assertSame( '-৳500.00', Imei_Ledger_Money::format( '-500', '৳' ) );
		$this->assertSame( '—', Imei_Ledger_Money::format( null, '৳' ) );
	}
}
