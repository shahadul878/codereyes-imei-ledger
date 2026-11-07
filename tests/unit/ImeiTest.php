<?php
/**
 * Tests for IMEI normalisation and Luhn validation.
 *
 * @package ImeiPhoneLedger
 */

use PHPUnit\Framework\TestCase;

final class ImeiTest extends TestCase {

	public function test_normalize_strips_spaces_dashes_and_slashes() {
		$this->assertSame( '490154203237518', Imei_Ledger_Imei::normalize( ' 49-015420 / 323751 8 ' ) );
	}

	public function test_normalize_converts_bengali_digits() {
		$this->assertSame( '490154203237518', Imei_Ledger_Imei::normalize( '৪৯০১৫৪২০৩২৩৭৫১৮' ) );
	}

	public function test_valid_imei_passes_luhn() {
		$this->assertTrue( Imei_Ledger_Imei::is_valid( '490154203237518' ) );
		$this->assertTrue( Imei_Ledger_Imei::is_valid( '356938035643809' ) );
	}

	public function test_wrong_check_digit_fails() {
		$this->assertFalse( Imei_Ledger_Imei::is_valid( '490154203237519' ) );
	}

	public function test_wrong_length_fails() {
		$this->assertFalse( Imei_Ledger_Imei::is_valid( '49015420323751' ) );
		$this->assertFalse( Imei_Ledger_Imei::is_valid( '4901542032375180' ) );
	}

	public function test_non_digits_fail() {
		$this->assertFalse( Imei_Ledger_Imei::is_valid( '49015420323751A' ) );
		$this->assertFalse( Imei_Ledger_Imei::is_valid( '' ) );
	}

	public function test_all_zero_imei_is_rejected() {
		$this->assertFalse( Imei_Ledger_Imei::is_valid( '000000000000000' ) );
	}
}
