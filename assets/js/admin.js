/**
 * Codereyes IMEI Ledger admin behaviour:
 * - live IMEI check on the purchase form
 * - profit preview on the sale form
 * - confirmation before delete / cancel sale
 */
( function () {
	'use strict';

	const config = window.imeiLedger;
	if ( ! config ) {
		return;
	}

	const IMEI_LENGTH = 15;
	const CHECK_DELAY_MS = 350;
	const BENGALI_DIGITS = '০১২৩৪৫৬৭৮৯';
	const i18n = config.i18n;

	function toAsciiDigits( value ) {
		return value.replace( /[০-৯]/g, ( digit ) => String( BENGALI_DIGITS.indexOf( digit ) ) );
	}

	function normalizeImei( value ) {
		return toAsciiDigits( value ).replace( /[\s\-/.]+/g, '' );
	}

	function el( tag, className, text ) {
		const node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	/* ---------- Live IMEI check ---------- */

	function renderStatus( target, state, message, matches ) {
		target.replaceChildren();
		target.dataset.state = state;

		if ( ! message ) {
			return;
		}

		target.appendChild( el( 'p', 'ipl-imei-status__message', message ) );

		( matches || [] ).forEach( ( match ) => {
			const row = el( 'p', 'ipl-imei-status__match' );
			const link = el( 'a', '', i18n.viewRecord + ' #' + match.id );
			link.href = match.url;
			link.target = '_blank';
			link.rel = 'noopener';
			row.append( link, document.createTextNode( ' · ' + match.phone + ' · ' + match.statusLabel + ' · ' + match.purchaseDate ) );
			target.appendChild( row );
		} );
	}

	async function checkImei( input, target ) {
		const imei = normalizeImei( input.value );

		if ( '' === imei ) {
			renderStatus( target, '', '' );
			return;
		}

		if ( ! /^\d+$/.test( imei ) || imei.length !== IMEI_LENGTH ) {
			const counter = /^\d+$/.test( imei ) ? ' (' + imei.length + '/' + IMEI_LENGTH + ')' : '';
			renderStatus( target, 'pending', i18n.invalid + counter );
			return;
		}

		renderStatus( target, 'pending', i18n.checking );

		const body = new URLSearchParams( {
			action: 'imei_ledger_check_imei',
			_ajax_nonce: config.nonce,
			imei,
			exclude_id: input.dataset.excludeId || '0',
		} );

		try {
			const response = await fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body } );
			const json = await response.json();

			if ( normalizeImei( input.value ) !== imei ) {
				return; // The user kept typing; a newer check will run.
			}

			if ( ! json.success ) {
				throw new Error( 'Request failed' );
			}

			const data = json.data;

			if ( ! data.valid ) {
				renderStatus( target, 'invalid', i18n.invalid );
			} else if ( 'in_stock' === data.level ) {
				renderStatus( target, 'block', i18n.inStock, data.matches );
			} else if ( 'sold_before' === data.level ) {
				renderStatus( target, 'warn', i18n.soldBefore, data.matches );
			} else {
				renderStatus( target, 'clear', i18n.clear );
			}
		} catch ( error ) {
			renderStatus( target, 'pending', i18n.failed );
		}
	}

	document.querySelectorAll( '[data-imei-check]' ).forEach( ( input ) => {
		const target = document.getElementById( input.dataset.statusTarget );
		let timer = null;

		if ( ! target ) {
			return;
		}

		input.addEventListener( 'input', () => {
			window.clearTimeout( timer );
			timer = window.setTimeout( () => checkImei( input, target ), CHECK_DELAY_MS );
		} );

		if ( '' !== input.value ) {
			checkImei( input, target );
		}
	} );

	/* ---------- Profit preview ---------- */

	// Mirrors Imei_Ledger_Money::parse(): currency only as prefix/suffix,
	// commas only as thousands (or lakh) separators.
	function toCents( raw ) {
		const clean = toAsciiDigits( raw.trim() ).replace( /^(?:৳|\$|tk\.?|bdt)\s*|\s*(?:৳|tk\.?|bdt)$/gi, '' );
		const match = clean.match( /^(\d+|\d{1,3}(?:,\d{2,3})*,\d{3})(?:\.(\d{1,2}))?$/ );

		if ( ! match ) {
			return null;
		}

		return parseInt( match[ 1 ].replace( /,/g, '' ), 10 ) * 100 + parseInt( ( match[ 2 ] || '' ).padEnd( 2, '0' ), 10 );
	}

	function formatCents( cents ) {
		const absolute = Math.abs( cents );
		const units = Math.floor( absolute / 100 ).toLocaleString( 'en-US' );
		const fraction = String( absolute % 100 ).padStart( 2, '0' );

		return config.currency + units + '.' + fraction;
	}

	const priceInput = document.querySelector( '[data-profit-preview]' );
	const preview = document.getElementById( 'ipl-profit-preview' );

	if ( priceInput && preview ) {
		const purchaseCents = toCents( priceInput.dataset.profitPreview );

		const update = () => {
			const saleCents = toCents( priceInput.value );

			if ( null === saleCents || null === purchaseCents ) {
				preview.textContent = '';
				preview.dataset.state = '';
				return;
			}

			const diff = saleCents - purchaseCents;
			preview.textContent = ( diff < 0 ? i18n.loss : i18n.profit ) + ': ' + formatCents( diff );
			preview.dataset.state = diff < 0 ? 'loss' : 'gain';
		};

		priceInput.addEventListener( 'input', update );
		update();
	}

	/* ---------- Confirmations ---------- */

	const confirmations = {
		'.ipl-confirm-delete': i18n.confirmDelete,
		'.ipl-confirm-cancel': i18n.confirmCancel,
	};

	document.addEventListener( 'click', ( event ) => {
		Object.keys( confirmations ).forEach( ( selector ) => {
			const link = event.target.closest( selector );

			if ( link && ! window.confirm( confirmations[ selector ] ) ) {
				event.preventDefault();
			}
		} );
	} );
}() );
