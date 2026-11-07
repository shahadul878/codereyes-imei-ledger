<?php
/**
 * The phone list.
 *
 * @package ImeiPhoneLedger
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Searchable, filterable list of ledger records.
 */
final class Imei_Ledger_List_Table extends WP_List_Table {

	/**
	 * Repository.
	 *
	 * @var Imei_Ledger_Repository
	 */
	private $repository;

	/**
	 * Current filters.
	 *
	 * @var array
	 */
	private $filters;

	/**
	 * Constructor.
	 *
	 * @param Imei_Ledger_Repository $repository Repository.
	 */
	public function __construct( Imei_Ledger_Repository $repository ) {
		parent::__construct(
			array(
				'singular' => 'phone',
				'plural'   => 'phones',
				'ajax'     => false,
			)
		);

		$this->repository = $repository;
		$this->filters    = Imei_Ledger_Actions::filters_from_request( wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only filters, sanitised in filters_from_request().
	}

	/**
	 * Current filters (for the export link and filter form).
	 *
	 * @return array
	 */
	public function filters(): array {
		return $this->filters;
	}

	/**
	 * Columns.
	 *
	 * @return array<string,string>
	 */
	public function get_columns() {
		return array(
			'imei'     => __( 'IMEI', 'imei-phone-ledger' ),
			'phone'    => __( 'Phone', 'imei-phone-ledger' ),
			'status'   => __( 'Status', 'imei-phone-ledger' ),
			'purchase' => __( 'Bought', 'imei-phone-ledger' ),
			'sale'     => __( 'Sold', 'imei-phone-ledger' ),
			'profit'   => __( 'Profit', 'imei-phone-ledger' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @return array
	 */
	protected function get_sortable_columns() {
		return array(
			'phone'    => array( 'model', false ),
			'purchase' => array( 'purchase_date', true ),
			'sale'     => array( 'sale_date', true ),
		);
	}

	/**
	 * Load the current page of records.
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'imei_ledger_per_page', 20 );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only sorting, whitelisted in Imei_Ledger_Query::order_by().
		$orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'id';
		$order   = isset( $_GET['order'] ) ? sanitize_key( wp_unslash( $_GET['order'] ) ) : 'desc';
		// phpcs:enable

		$result = $this->repository->search( $this->filters, $per_page, $this->get_pagenum(), $orderby, $order );

		$this->items           = $result['rows'];
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns(), 'imei' );

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $result['total'] / $per_page ),
			)
		);
	}

	/**
	 * All / In stock / Sold links.
	 *
	 * @return array<string,string>
	 */
	protected function get_views() {
		$current = $this->filters['status'];
		$views   = array( '' => __( 'All', 'imei-phone-ledger' ) ) + Imei_Ledger_Status::labels();
		$links   = array();

		foreach ( $views as $status => $label ) {
			$url = Imei_Ledger_View::page_url( Imei_Ledger_View::PAGE_LIST, '' === $status ? array() : array( 'status' => $status ) );

			$links[ '' === $status ? 'all' : $status ] = sprintf(
				'<a href="%1$s"%2$s>%3$s</a>',
				esc_url( $url ),
				$current === $status ? ' class="current" aria-current="page"' : '',
				esc_html( $label )
			);
		}

		return $links;
	}

	/**
	 * Message when nothing matches.
	 */
	public function no_items() {
		if ( '' !== $this->filters['search'] ) {
			esc_html_e( 'No phone matches that search. Check the IMEI, or add it as a new purchase.', 'imei-phone-ledger' );
			return;
		}

		esc_html_e( 'No phones yet. Add your first purchase to start the ledger.', 'imei-phone-ledger' );
	}

	/**
	 * IMEI column with row actions.
	 *
	 * @param array $item Record.
	 * @return string
	 */
	protected function column_imei( $item ) {
		$id   = (int) $item['id'];
		$html = sprintf(
			'<a class="ipl-imei row-title" href="%1$s">%2$s</a>',
			esc_url( Imei_Ledger_View::record_url( 'view', $id ) ),
			esc_html( Imei_Ledger_View::imei( $item['imei1'] ) )
		);

		if ( '' !== $item['imei2'] ) {
			$html .= sprintf( '<span class="ipl-imei ipl-muted">%s</span>', esc_html( Imei_Ledger_View::imei( $item['imei2'] ) ) );
		}

		return $html . $this->row_actions( $this->actions_for( $item ) );
	}

	/**
	 * Row actions for a record.
	 *
	 * @param array $item Record.
	 * @return array<string,string>
	 */
	private function actions_for( array $item ): array {
		$id      = (int) $item['id'];
		$is_sold = Imei_Ledger_Status::SOLD === $item['status'];
		$actions = array(
			'view' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( Imei_Ledger_View::record_url( 'view', $id ) ), esc_html__( 'View', 'imei-phone-ledger' ) ),
			'edit' => sprintf( '<a href="%1$s">%2$s</a>', esc_url( Imei_Ledger_View::record_url( 'edit', $id ) ), esc_html__( 'Edit', 'imei-phone-ledger' ) ),
		);

		if ( $is_sold ) {
			$actions['cancel'] = sprintf(
				'<a class="ipl-confirm-cancel" href="%1$s">%2$s</a>',
				esc_url( Imei_Ledger_View::record_action_url( 'cancel_sale', $id ) ),
				esc_html__( 'Cancel sale', 'imei-phone-ledger' )
			);
		} else {
			$actions['sell'] = sprintf( '<a href="%1$s"><strong>%2$s</strong></a>', esc_url( Imei_Ledger_View::record_url( 'sell', $id ) ), esc_html__( 'Sell', 'imei-phone-ledger' ) );
		}

		$actions['delete'] = sprintf(
			'<a class="ipl-confirm-delete" href="%1$s">%2$s</a>',
			esc_url( Imei_Ledger_View::record_action_url( 'delete', $id ) ),
			esc_html__( 'Delete', 'imei-phone-ledger' )
		);

		return $actions;
	}

	/**
	 * Phone column.
	 *
	 * @param array $item Record.
	 * @return string
	 */
	protected function column_phone( $item ) {
		$labels = Imei_Ledger_Validator::condition_labels();

		return sprintf(
			'<strong>%1$s</strong><span class="ipl-muted">%2$s</span>',
			esc_html( trim( $item['brand'] . ' ' . $item['model'] ) ),
			esc_html( implode( ' · ', array_filter( array( $item['storage'], $item['color'], $labels[ $item['device_condition'] ] ?? '' ) ) ) )
		);
	}

	/**
	 * Status column.
	 *
	 * @param array $item Record.
	 * @return string
	 */
	protected function column_status( $item ) {
		ob_start();
		Imei_Ledger_View::status_badge( $item['status'] );

		return (string) ob_get_clean();
	}

	/**
	 * Purchase column.
	 *
	 * @param array $item Record.
	 * @return string
	 */
	protected function column_purchase( $item ) {
		return $this->party_cell( $item['purchase_price'], $item['purchase_date'], $item['seller_name'] );
	}

	/**
	 * Sale column.
	 *
	 * @param array $item Record.
	 * @return string
	 */
	protected function column_sale( $item ) {
		if ( Imei_Ledger_Status::SOLD !== $item['status'] ) {
			return sprintf(
				'<a class="button button-small" href="%1$s">%2$s</a>',
				esc_url( Imei_Ledger_View::record_url( 'sell', (int) $item['id'] ) ),
				esc_html__( 'Sell', 'imei-phone-ledger' )
			);
		}

		return $this->party_cell( $item['sale_price'], $item['sale_date'], $item['buyer_name'] );
	}

	/**
	 * Profit column.
	 *
	 * @param array $item Record.
	 * @return string
	 */
	protected function column_profit( $item ) {
		ob_start();
		Imei_Ledger_View::profit( $item );

		return (string) ob_get_clean();
	}

	/**
	 * Price over date and person.
	 *
	 * @param string|null $price  Price.
	 * @param string|null $date   Date.
	 * @param string      $person Seller or buyer.
	 * @return string
	 */
	private function party_cell( ?string $price, ?string $date, string $person ): string {
		return sprintf(
			'<strong class="ipl-amount">%1$s</strong><span class="ipl-muted">%2$s · %3$s</span>',
			esc_html( Imei_Ledger_View::money( $price ) ),
			esc_html( Imei_Ledger_View::date( $date ) ),
			esc_html( $person )
		);
	}
}
