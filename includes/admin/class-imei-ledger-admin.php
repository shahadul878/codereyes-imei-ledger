<?php
/**
 * Admin menu, assets and screen routing.
 *
 * @package ImeiPhoneLedger
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the IMEI Ledger menu and renders its screens.
 */
final class Imei_Ledger_Admin {

	/**
	 * Repository.
	 *
	 * @var Imei_Ledger_Repository
	 */
	private $repository;

	/**
	 * Screen hook suffixes of our pages.
	 *
	 * @var string[]
	 */
	private $hooks = array();

	/**
	 * Constructor.
	 *
	 * @param Imei_Ledger_Repository $repository Repository.
	 */
	public function __construct( Imei_Ledger_Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'set_screen_option_imei_ledger_per_page', array( $this, 'save_screen_option' ), 10, 3 );
	}

	/**
	 * Add the menu and its pages.
	 */
	public function add_menu(): void {
		$capability = Imei_Ledger_Installer::CAPABILITY;

		$list = add_menu_page(
			__( 'IMEI Ledger', 'imei-phone-ledger' ),
			__( 'IMEI Ledger', 'imei-phone-ledger' ),
			$capability,
			Imei_Ledger_View::PAGE_LIST,
			array( $this, 'render_main' ),
			'dashicons-smartphone',
			56
		);

		$this->hooks[] = $list;
		$this->hooks[] = add_submenu_page( Imei_Ledger_View::PAGE_LIST, __( 'All Phones', 'imei-phone-ledger' ), __( 'All Phones', 'imei-phone-ledger' ), $capability, Imei_Ledger_View::PAGE_LIST, array( $this, 'render_main' ) );
		$this->hooks[] = add_submenu_page( Imei_Ledger_View::PAGE_LIST, __( 'Add Purchase', 'imei-phone-ledger' ), __( 'Add Purchase', 'imei-phone-ledger' ), $capability, Imei_Ledger_View::PAGE_ADD, array( $this, 'render_add' ) );
		$this->hooks[] = add_submenu_page( Imei_Ledger_View::PAGE_LIST, __( 'Reports', 'imei-phone-ledger' ), __( 'Reports', 'imei-phone-ledger' ), $capability, Imei_Ledger_View::PAGE_REPORTS, array( $this, 'render_reports' ) );

		add_action( 'load-' . $list, array( $this, 'add_screen_options' ) );
	}

	/**
	 * "Phones per page" screen option on the list.
	 */
	public function add_screen_options(): void {
		add_screen_option(
			'per_page',
			array(
				'default' => 20,
				'option'  => 'imei_ledger_per_page',
			)
		);
	}

	/**
	 * Persist the per-page screen option.
	 *
	 * @param mixed  $status Value to save, or false.
	 * @param string $option Option name.
	 * @param mixed  $value  Submitted value.
	 * @return mixed
	 */
	public function save_screen_option( $status, $option, $value ) {
		return min( 200, max( 1, absint( $value ) ) );
	}

	/**
	 * Load CSS and JS on our screens only.
	 *
	 * @param string $hook Current screen hook.
	 */
	public function enqueue_assets( $hook ): void {
		if ( ! in_array( $hook, $this->hooks, true ) ) {
			return;
		}

		wp_enqueue_style( 'imei-ledger-admin', IMEI_LEDGER_URL . 'assets/css/admin.css', array(), IMEI_LEDGER_VERSION );
		wp_enqueue_script( 'imei-ledger-admin', IMEI_LEDGER_URL . 'assets/js/admin.js', array(), IMEI_LEDGER_VERSION, true );
		wp_localize_script(
			'imei-ledger-admin',
			'imeiLedger',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( Imei_Ledger_Ajax::NONCE ),
				'currency' => Imei_Ledger_View::currency(),
				'i18n'     => array(
					'checking'      => __( 'Checking…', 'imei-phone-ledger' ),
					'invalid'       => __( 'Not a valid IMEI yet. Dial *#06# on the phone to see it.', 'imei-phone-ledger' ),
					'clear'         => __( 'New IMEI. No earlier records.', 'imei-phone-ledger' ),
					'soldBefore'    => __( 'This phone was bought and sold before. Check the earlier record before buying it again.', 'imei-phone-ledger' ),
					'inStock'       => __( 'This phone is already in stock. You cannot add it twice.', 'imei-phone-ledger' ),
					'viewRecord'    => __( 'View record', 'imei-phone-ledger' ),
					'failed'        => __( 'Could not check the IMEI right now. It will be checked again when you save.', 'imei-phone-ledger' ),
					'profit'        => __( 'Profit', 'imei-phone-ledger' ),
					'loss'          => __( 'Loss', 'imei-phone-ledger' ),
					'confirmDelete' => __( 'Delete this record permanently? This cannot be undone.', 'imei-phone-ledger' ),
					'confirmCancel' => __( 'Cancel this sale and put the phone back in stock?', 'imei-phone-ledger' ),
				),
			)
		);
	}

	/**
	 * Main page: list, or a single record's view/edit/sell screen.
	 */
	public function render_main(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Routing only; every change goes through a nonce-checked handler.
		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		// phpcs:enable

		if ( in_array( $action, array( 'view', 'edit', 'sell' ), true ) ) {
			$this->render_record_screen( $action, $id );
			return;
		}

		$this->render_list();
	}

	/**
	 * Add Purchase page.
	 */
	public function render_add(): void {
		$this->render_purchase_form( null );
	}

	/**
	 * Reports page.
	 */
	public function render_reports(): void {
		require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-reports.php';

		( new Imei_Ledger_Reports( $this->repository ) )->render();
	}

	/**
	 * The phone list with summary cards.
	 */
	private function render_list(): void {
		require_once IMEI_LEDGER_DIR . 'includes/admin/class-imei-ledger-list-table.php';

		$table = new Imei_Ledger_List_Table( $this->repository );
		$table->prepare_items();

		$today = current_time( 'Y-m-d' );

		Imei_Ledger_View::render(
			'page-list',
			array(
				'table'   => $table,
				'summary' => $this->repository->summary( current_time( 'Y-m-01' ), $today ),
				'filters' => $table->filters(),
			)
		);
	}

	/**
	 * View, edit or sell screen for one record.
	 *
	 * @param string $action view | edit | sell.
	 * @param int    $id     Record ID.
	 */
	private function render_record_screen( string $action, int $id ): void {
		$record = $this->repository->find( $id );

		if ( null === $record ) {
			Imei_Ledger_View::render( 'page-not-found' );
			return;
		}

		if ( 'edit' === $action ) {
			$this->render_purchase_form( $record );
			return;
		}

		if ( 'sell' === $action ) {
			$this->render_sale_form( $record );
			return;
		}

		Imei_Ledger_View::render(
			'page-view',
			array(
				'record'  => $record,
				'history' => $this->repository->find_by_imeis( Imei_Ledger_Duplicates::imeis_to_check( $record ), (int) $record['id'] ),
			)
		);
	}

	/**
	 * Purchase form for a new record (null) or an existing one.
	 *
	 * @param array|null $record Record being edited.
	 */
	private function render_purchase_form( ?array $record ): void {
		$id    = null === $record ? 0 : (int) $record['id'];
		$state = Imei_Ledger_Form_State::take( 'purchase', $id );

		Imei_Ledger_View::render(
			'page-purchase-form',
			array(
				'record' => $record,
				'values' => $state['input'] ?? $record ?? array(
					'purchase_date'    => current_time( 'Y-m-d' ),
					'device_condition' => 'good',
				),
				'errors' => $state['errors'] ?? array(),
				'state'  => $state,
				'today'  => current_time( 'Y-m-d' ),
			)
		);
	}

	/**
	 * Sale form for a record.
	 *
	 * @param array $record Record.
	 */
	private function render_sale_form( array $record ): void {
		$state    = Imei_Ledger_Form_State::take( 'sale', (int) $record['id'] );
		$defaults = Imei_Ledger_Status::SOLD === $record['status']
			? $record
			: array( 'sale_date' => current_time( 'Y-m-d' ) );

		Imei_Ledger_View::render(
			'page-sale-form',
			array(
				'record' => $record,
				'values' => $state['input'] ?? $defaults,
				'errors' => $state['errors'] ?? array(),
				'today'  => current_time( 'Y-m-d' ),
			)
		);
	}
}
