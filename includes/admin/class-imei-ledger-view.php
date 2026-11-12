<?php
/**
 * Template rendering, URLs and small display helpers for admin screens.
 *
 * @package CodereyesImeiLedger
 */

defined( 'ABSPATH' ) || exit;

/**
 * View helpers. Every helper escapes its own output.
 */
final class Imei_Ledger_View {

	public const PAGE_LIST    = 'imei-ledger';
	public const PAGE_ADD     = 'imei-ledger-add';
	public const PAGE_REPORTS = 'imei-ledger-reports';

	/**
	 * Public NEIR page where an IMEI can be checked against BTRC records.
	 */
	public const NEIR_URL = 'https://neir.btrc.gov.bd/';

	/**
	 * Include a template from views/ with $vars in scope.
	 *
	 * @param string $template File name without extension.
	 * @param array  $vars     Variables for the template.
	 */
	public static function render( string $template, array $vars = array() ): void {
		$file = IMEI_LEDGER_DIR . 'views/' . $template . '.php';

		if ( 1 !== preg_match( '/^[a-z0-9\/-]+$/', $template ) || ! is_readable( $file ) ) {
			return;
		}

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $vars is read by the included template.
		( static function ( string $imei_ledger_file, array $vars ): void {
			include $imei_ledger_file;
		} )( $file, $vars );
	}

	/**
	 * Admin page URL.
	 *
	 * @param string $page Page slug.
	 * @param array  $args Extra query args.
	 * @return string Unescaped URL.
	 */
	public static function page_url( string $page, array $args = array() ): string {
		return add_query_arg( array_merge( array( 'page' => $page ), $args ), admin_url( 'admin.php' ) );
	}

	/**
	 * URL of one record's screen.
	 *
	 * @param string $action view | edit | sell.
	 * @param int    $id     Record ID.
	 * @return string Unescaped URL.
	 */
	public static function record_url( string $action, int $id ): string {
		return self::page_url(
			self::PAGE_LIST,
			array(
				'action' => $action,
				'id'     => $id,
			)
		);
	}

	/**
	 * Nonce-protected admin-post URL for a one-click action on a record.
	 *
	 * @param string $action cancel_sale | delete.
	 * @param int    $id     Record ID.
	 * @return string Unescaped URL.
	 */
	public static function record_action_url( string $action, int $id ): string {
		$url = add_query_arg(
			array(
				'action' => 'imei_ledger_' . $action,
				'id'     => $id,
			),
			admin_url( 'admin-post.php' )
		);

		return wp_nonce_url( $url, 'imei_ledger_' . $action . '_' . $id );
	}

	/**
	 * Currency symbol, filterable.
	 *
	 * @return string
	 */
	public static function currency(): string {
		return (string) apply_filters( 'imei_ledger_currency_symbol', '৳' );
	}

	/**
	 * Formatted amount.
	 *
	 * @param string|null $amount Amount.
	 * @return string Unescaped.
	 */
	public static function money( ?string $amount ): string {
		return Imei_Ledger_Money::format( $amount, self::currency() );
	}

	/**
	 * Date in the site's date format.
	 *
	 * @param string|null $date Y-m-d.
	 * @return string Unescaped.
	 */
	public static function date( ?string $date ): string {
		if ( null === $date || ! Imei_Ledger_Validator::is_date( $date ) ) {
			return '—';
		}

		return (string) mysql2date( get_option( 'date_format' ), $date );
	}

	/**
	 * IMEI split into 2-6-6-1 groups for easier reading.
	 *
	 * @param string $imei IMEI.
	 * @return string Unescaped.
	 */
	public static function imei( string $imei ): string {
		if ( Imei_Ledger_Imei::LENGTH !== strlen( $imei ) ) {
			return $imei;
		}

		return substr( $imei, 0, 2 ) . ' ' . substr( $imei, 2, 6 ) . ' ' . substr( $imei, 8, 6 ) . ' ' . substr( $imei, 14 );
	}

	/**
	 * "Samsung Galaxy A52 · 8/128GB · Black".
	 *
	 * @param array $record Record.
	 * @return string Unescaped.
	 */
	public static function phone_name( array $record ): string {
		$name    = trim( $record['brand'] . ' ' . $record['model'] );
		$details = array_filter( array( $record['storage'], $record['color'] ) );

		return implode( ' · ', array_merge( array( $name ), $details ) );
	}

	/**
	 * Status pill.
	 *
	 * @param string $status Status.
	 */
	public static function status_badge( string $status ): void {
		$labels = Imei_Ledger_Status::labels();

		printf(
			'<span class="ipl-badge ipl-badge--%1$s">%2$s</span>',
			esc_attr( $status ),
			esc_html( $labels[ $status ] ?? $status )
		);
	}

	/**
	 * Profit with a positive/negative class.
	 *
	 * @param array $record Record.
	 */
	public static function profit( array $record ): void {
		$profit = Imei_Ledger_Status::SOLD === $record['status']
			? Imei_Ledger_Money::profit( (string) $record['purchase_price'], $record['sale_price'] )
			: null;

		if ( null === $profit ) {
			echo '<span class="ipl-muted">—</span>';
			return;
		}

		$class = 0 > Imei_Ledger_Money::to_cents( $profit ) ? 'ipl-loss' : 'ipl-gain';

		printf( '<span class="%1$s">%2$s</span>', esc_attr( $class ), esc_html( self::money( $profit ) ) );
	}

	/**
	 * A labelled form field with its error message.
	 *
	 * Keys: name, label, value, type (text|date|tel|textarea|select),
	 * options (value => label, for select), required, error, hint, attrs
	 * (extra input attributes) and wide (span both grid columns).
	 *
	 * @param array $field Field definition.
	 */
	public static function field( array $field ): void {
		$field = array_merge(
			array(
				'type'     => 'text',
				'value'    => '',
				'required' => false,
				'error'    => '',
				'hint'     => '',
				'options'  => array(),
				'attrs'    => array(),
				'wide'     => false,
			),
			$field
		);

		$id       = 'ipl-' . str_replace( '_', '-', $field['name'] );
		$describe = array_filter(
			array(
				'' !== $field['hint'] ? $id . '-hint' : '',
				'' !== $field['error'] ? $id . '-error' : '',
			)
		);
		$classes  = array_filter(
			array(
				'ipl-field',
				$field['wide'] ? 'ipl-field--wide' : '',
				'' !== $field['error'] ? 'ipl-field--error' : '',
			)
		);
		$attrs    = array_merge(
			$field['attrs'],
			array(
				'id'               => $id,
				'name'             => $field['name'],
				'aria-describedby' => implode( ' ', $describe ),
				'aria-invalid'     => '' !== $field['error'] ? 'true' : '',
				'required'         => $field['required'] ? 'required' : '',
			)
		);

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';
		printf(
			'<label for="%1$s">%2$s%3$s</label>',
			esc_attr( $id ),
			esc_html( $field['label'] ),
			$field['required'] ? ' <span class="ipl-required" aria-hidden="true">*</span>' : ''
		);

		self::control( $field, $attrs );

		if ( '' !== $field['hint'] ) {
			printf( '<p class="ipl-hint" id="%1$s-hint">%2$s</p>', esc_attr( $id ), esc_html( $field['hint'] ) );
		}

		if ( '' !== $field['error'] ) {
			printf( '<p class="ipl-error" id="%1$s-error">%2$s</p>', esc_attr( $id ), esc_html( $field['error'] ) );
		}

		echo '</div>';
	}

	/**
	 * The input, textarea or select for a field.
	 *
	 * @param array $field Field definition.
	 * @param array $attrs Attributes.
	 */
	private static function control( array $field, array $attrs ): void {
		if ( 'textarea' === $field['type'] ) {
			printf( '<textarea rows="3" %1$s>%2$s</textarea>', self::attributes( $attrs ), esc_textarea( $field['value'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes.
			return;
		}

		if ( 'select' === $field['type'] ) {
			echo '<select ' . self::attributes( $attrs ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes.
			foreach ( $field['options'] as $value => $label ) {
				printf(
					'<option value="%1$s"%2$s>%3$s</option>',
					esc_attr( (string) $value ),
					selected( (string) $value, $field['value'], false ),
					esc_html( $label )
				);
			}
			echo '</select>';
			return;
		}

		$attrs = array_merge(
			array(
				'type'  => $field['type'],
				'value' => $field['value'],
			),
			$attrs
		);

		echo '<input ' . self::attributes( $attrs ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- attributes() escapes.
	}

	/**
	 * Escaped HTML attribute string; empty values are dropped.
	 *
	 * @param array<string,string> $attrs Attributes.
	 * @return string
	 */
	private static function attributes( array $attrs ): string {
		$parts = array();

		foreach ( $attrs as $name => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}

			$parts[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}

		return implode( ' ', $parts );
	}
}
