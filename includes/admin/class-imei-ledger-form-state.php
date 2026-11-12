<?php
/**
 * Carries form input and errors across the post/redirect/get round trip.
 *
 * @package CodereyesImeiLedger
 */

defined( 'ABSPATH' ) || exit;

/**
 * One-shot, per-user form state stored in a short-lived transient.
 */
final class Imei_Ledger_Form_State {

	/**
	 * Long enough to survive the redirect, short enough not to keep personal data around.
	 */
	private const TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Store state for the current user.
	 *
	 * Keys: form (purchase|sale), id (0 for a new purchase), input (values
	 * to refill the form with), errors (field => message), and optionally
	 * duplicate (an Imei_Ledger_Duplicates level) and matches (records).
	 *
	 * @param array $state Form state.
	 */
	public static function save( array $state ): void {
		set_transient( self::key(), $state, self::TTL );
	}

	/**
	 * Read and clear the state for a form, if it belongs to that form.
	 *
	 * @param string $form Form name.
	 * @param int    $id   Record ID.
	 * @return array|null
	 */
	public static function take( string $form, int $id ): ?array {
		$state = get_transient( self::key() );

		if ( ! is_array( $state ) || ( $state['form'] ?? '' ) !== $form || (int) ( $state['id'] ?? -1 ) !== $id ) {
			return null;
		}

		delete_transient( self::key() );

		return $state;
	}

	/**
	 * Transient key for the current user.
	 *
	 * @return string
	 */
	private static function key(): string {
		return 'imei_ledger_form_' . get_current_user_id();
	}
}
