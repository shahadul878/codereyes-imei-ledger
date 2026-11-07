<?php
/**
 * Tests for the list/report query builder.
 *
 * @package ImeiPhoneLedger
 */

use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase {

	public function test_empty_args_match_everything() {
		$query = Imei_Ledger_Query::where( array() );

		$this->assertSame( '1=1', $query['sql'] );
		$this->assertSame( array(), $query['params'] );
	}

	public function test_status_filter_is_whitelisted() {
		$sold  = Imei_Ledger_Query::where( array( 'status' => 'sold' ) );
		$bogus = Imei_Ledger_Query::where( array( 'status' => "sold' OR 1=1" ) );

		$this->assertStringContainsString( 'status = %s', $sold['sql'] );
		$this->assertSame( array( 'sold' ), $sold['params'] );
		$this->assertSame( '1=1', $bogus['sql'] );
	}

	public function test_search_uses_placeholders_and_escapes_like() {
		$query = Imei_Ledger_Query::where( array( 'search' => 'Rahim_50%' ) );

		$this->assertStringContainsString( 'seller_name LIKE %s', $query['sql'] );
		$this->assertStringNotContainsString( 'Rahim', $query['sql'] );
		$this->assertContains( '%Rahim\\_50\\%%', $query['params'] );
	}

	public function test_search_normalizes_imei_digits() {
		$query = Imei_Ledger_Query::where( array( 'search' => '৪৯০১৫৪-২০৩' ) );

		$this->assertContains( '%490154203%', $query['params'] );
	}

	public function test_date_range_on_whitelisted_field() {
		$query = Imei_Ledger_Query::where(
			array(
				'date_field' => 'sale_date',
				'date_from'  => '2026-11-01',
				'date_to'    => '2026-11-30',
			)
		);

		$this->assertStringContainsString( 'sale_date >= %s', $query['sql'] );
		$this->assertStringContainsString( 'sale_date <= %s', $query['sql'] );
		$this->assertSame( array( '2026-11-01', '2026-11-30' ), $query['params'] );
	}

	public function test_empty_date_field_defaults_to_purchase_date() {
		$query = Imei_Ledger_Query::where(
			array(
				'date_field' => '',
				'date_from'  => '2026-11-01',
			)
		);

		$this->assertSame( 'purchase_date >= %s', $query['sql'] );
	}

	public function test_invalid_dates_and_fields_are_ignored() {
		$query = Imei_Ledger_Query::where(
			array(
				'date_field' => 'id; DROP TABLE x',
				'date_from'  => 'yesterday',
			)
		);

		$this->assertSame( '1=1', $query['sql'] );
	}

	public function test_order_by_is_whitelisted() {
		$this->assertSame( 'sale_price ASC, id ASC', Imei_Ledger_Query::order_by( 'sale_price', 'asc' ) );
		$this->assertSame( 'id DESC', Imei_Ledger_Query::order_by( 'password', 'sideways' ) );
	}

	public function test_order_by_adds_id_tiebreaker_for_stable_pages() {
		$this->assertSame( 'purchase_date DESC, id DESC', Imei_Ledger_Query::order_by( 'purchase_date', 'desc' ) );
	}
}
