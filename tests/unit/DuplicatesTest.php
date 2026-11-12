<?php
/**
 * Tests for duplicate IMEI classification.
 *
 * @package CodereyesImeiLedger
 */

use PHPUnit\Framework\TestCase;

final class DuplicatesTest extends TestCase {

	public function test_no_matches_is_clear() {
		$this->assertSame( Imei_Ledger_Duplicates::NONE, Imei_Ledger_Duplicates::classify( array() ) );
	}

	public function test_previously_sold_phone_is_a_warning() {
		$matches = array(
			array( 'id' => 3, 'status' => 'sold' ),
		);

		$this->assertSame( Imei_Ledger_Duplicates::SOLD_BEFORE, Imei_Ledger_Duplicates::classify( $matches ) );
	}

	public function test_phone_already_in_stock_blocks() {
		$matches = array(
			array( 'id' => 3, 'status' => 'sold' ),
			array( 'id' => 7, 'status' => 'in_stock' ),
		);

		$this->assertSame( Imei_Ledger_Duplicates::IN_STOCK, Imei_Ledger_Duplicates::classify( $matches ) );
	}

	public function test_imeis_to_check_skips_empty_second_imei() {
		$this->assertSame(
			array( '490154203237518' ),
			Imei_Ledger_Duplicates::imeis_to_check( array( 'imei1' => '490154203237518', 'imei2' => '' ) )
		);
	}

	public function test_changed_imeis_only_returns_new_values() {
		$this->assertSame(
			array( '356938035643809' ),
			Imei_Ledger_Duplicates::changed_imeis(
				array( 'imei1' => '490154203237518', 'imei2' => '356938035643809' ),
				array( 'imei1' => '490154203237518', 'imei2' => '' )
			)
		);
	}

	public function test_changed_imeis_is_empty_when_nothing_changed() {
		$record = array( 'imei1' => '490154203237518', 'imei2' => '' );

		$this->assertSame( array(), Imei_Ledger_Duplicates::changed_imeis( $record, $record ) );
	}

	public function test_imeis_to_check_includes_both() {
		$this->assertSame(
			array( '490154203237518', '356938035643809' ),
			Imei_Ledger_Duplicates::imeis_to_check( array( 'imei1' => '490154203237518', 'imei2' => '356938035643809' ) )
		);
	}
}
