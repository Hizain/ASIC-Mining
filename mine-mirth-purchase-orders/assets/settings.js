/* Mine Mirth Purchase Orders: settings (logo picker, fill from the website, statuses). */
( function () {
	'use strict';

	const $ = ( sel, root ) => ( root || document ).querySelector( sel );

	document.addEventListener( 'click', ( e ) => {
		const fill = e.target.closest( '[data-mmpo-fill]' );
		if ( fill ) {
			e.preventDefault();
			const field = fill.closest( '.mmpo-f' ).querySelector( 'input, textarea' );
			field.value = fill.dataset.mmpoFill;
			fill.remove();
			return;
		}
		const remove = e.target.closest( '[data-mmpo-remove-status]' );
		if ( remove ) {
			e.preventDefault();
			remove.closest( '.mmpo-statusrow' ).remove();
			return;
		}
		const add = e.target.closest( '[data-mmpo-add-status]' );
		if ( add ) {
			e.preventDefault();
			const i = parseInt( add.dataset.next, 10 ) || 0;
			add.dataset.next = String( i + 1 );
			const wrap = document.createElement( 'div' );
			wrap.innerHTML = $( '#mmpo-status-template' ).innerHTML.replace( /__i__/g, String( i ) ).trim();
			$( '[data-mmpo-statuses]' ).appendChild( wrap.firstElementChild );
			$( '[data-mmpo-statuses] .mmpo-statusrow:last-child input.mmpo-input' ).focus();
		}
	} );

	const box = $( '[data-mmpo-logo]' );
	if ( ! box || ! window.wp || ! wp.media ) {
		return;
	}
	const id = $( '[data-mmpo-logo-id]', box );
	const img = $( '[data-mmpo-logo-img]', box );
	const clear = $( '[data-mmpo-logo-clear]', box );
	let frame;
	$( '[data-mmpo-logo-pick]', box ).addEventListener( 'click', ( e ) => {
		e.preventDefault();
		if ( ! frame ) {
			frame = wp.media( { library: { type: [ 'image/png', 'image/jpeg' ] }, multiple: false } );
			frame.on( 'select', () => {
				const a = frame.state().get( 'selection' ).first().toJSON();
				id.value = String( a.id );
				const url = ( a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url );
				img.innerHTML = '<img src="' + url.replace( /"/g, '&quot;' ) + '" alt="">';
				clear.hidden = false;
				const radio = $( '[data-mmpo-logo-custom]' );
				if ( radio ) {
					radio.checked = true;
				}
			} );
		}
		frame.open();
	} );
	clear.addEventListener( 'click', ( e ) => {
		e.preventDefault();
		id.value = '0';
		img.innerHTML = '';
		clear.hidden = true;
	} );
}() );
