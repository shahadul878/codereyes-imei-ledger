<?php
/**
 * Tests for CSV report rows.
 *
 * @package CodereyesImeiLedger
 */

use PHPUnit\Framework\TestCase;

final class CsvTest extends TestCase {

	private function record( array $overrides = array() ) {
		return array_merge(
			array(
				'id'               => '5',
				'imei1'            => '490154203237518',
				'imei2'            => '',
				'brand'            => 'Samsung',
				'model'            => 'Galaxy A52',
				'color'            => 'Black',
				'storage'          => '8/128GB',
				'device_condition' => 'good',
				'status'           => 'sold',
				'purchase_date'    => '2026-11-01',
				'purchase_price'   => '15000.00',
				'seller_name'      => 'Rahim',
				'seller_phone'     => '01711223344',
				'seller_nid'       => '',
				'sale_date'        => '2026-11-05',
				'sale_price'       => '17500.00',
				'buyer_name'       => 'Karim',
				'buyer_phone'      => '01811223344',
				'buyer_nid'        => '',
				'notes'            => '',
			),
			$overrides
		);
	}

	public function test_row_has_one_cell_per_header() {
		$this->assertCount( count( Imei_Ledger_Csv::headers() ), Imei_Ledger_Csv::row( $this->record() ) );
	}

	public function test_imei_is_kept_as_text_for_spreadsheets() {
		$row = Imei_Ledger_Csv::row( $this->record() );

		$this->assertSame( "\t490154203237518", $row[1] );
	}

	public function test_profit_is_computed_for_sold_records() {
		$headers = Imei_Ledger_Csv::headers();
		$row     = Imei_Ledger_Csv::row( $this->record() );

		$this->assertSame( '2500.00', $row[ array_search( 'Profit', $headers, true ) ] );
	}

	public function test_profit_is_blank_for_stock() {
		$headers = Imei_Ledger_Csv::headers();
		$row     = Imei_Ledger_Csv::row( $this->record( array( 'status' => 'in_stock', 'sale_price' => null, 'sale_date' => null ) ) );

		$this->assertSame( '', $row[ array_search( 'Profit', $headers, true ) ] );
	}

	public function test_formula_injection_is_neutralised() {
		$this->assertSame( "'=HYPERLINK(\"x\")", Imei_Ledger_Csv::escape_cell( '=HYPERLINK("x")' ) );
		$this->assertSame( "'+1", Imei_Ledger_Csv::escape_cell( '+1' ) );
		$this->assertSame( "'@SUM(A1)", Imei_Ledger_Csv::escape_cell( '@SUM(A1)' ) );
		$this->assertSame( 'Rahim', Imei_Ledger_Csv::escape_cell( 'Rahim' ) );
	}

	public function test_text_fields_in_row_are_escaped() {
		$row = Imei_Ledger_Csv::row( $this->record( array( 'seller_name' => '=cmd' ) ) );

		$this->assertContains( "'=cmd", $row );
	}
}
