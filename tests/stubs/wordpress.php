<?php
/**
 * Minimal WordPress stand-ins for pure-logic unit tests.
 *
 * Only the functions the classes under test call are stubbed.
 *
 * @package ImeiPhoneLedger
 */

// phpcs:disable

function __( $text, $domain = 'default' ) {
	return $text;
}

function sanitize_text_field( $str ) {
	$str = strip_tags( (string) $str );
	$str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
	return trim( $str );
}

function sanitize_textarea_field( $str ) {
	return trim( strip_tags( (string) $str ) );
}
