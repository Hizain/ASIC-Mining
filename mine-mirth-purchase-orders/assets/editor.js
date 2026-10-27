/* Mine Mirth Purchase Orders: editor (product search, live totals, customer fill, save and PDF). */
( function () {
	'use strict';

	const form = document.getElementById( 'mmpo-form' );
	const cfgEl = document.getElementById( 'mmpo-config' );
	if ( ! form || ! cfgEl ) {
		return;
	}
	const cfg = JSON.parse( cfgEl.textContent );
	const t = cfg.i18n;
	const $ = ( sel, root ) => ( root || document ).querySelector( sel );
	const $$ = ( sel, root ) => Array.from( ( root || document ).querySelectorAll( sel ) );
	let dirty = false;
	let seq = Date.now();

	/* ---------------------------------------------------------- Money */

	const num = ( v ) => {
		const n = parseFloat( String( v || '' ).replace( /[^0-9.\-]/g, '' ) );
		return isFinite( n ) ? n : 0;
	};
	const currency = () => {
		const code = ( $( '[data-mmpo-currency]', form ) || {} ).value || 'USD';
		const c = cfg.currencies[ code ] || [ code + ' ', 2, false ];
		return { code, symbol: c[ 0 ], dec: c[ 1 ], after: c[ 2 ] };
	};
	const round = ( v, dec ) => {
		const f = Math.pow( 10, dec );
		return Math.round( ( v + ( v >= 0 ? 1e-9 : -1e-9 ) ) * f ) / f;
	};
	const money = ( v ) => {
		const c = currency();
		const neg = v < 0;
		const parts = Math.abs( v ).toFixed( c.dec ).split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
		const n = parts.join( '.' );
		return ( neg ? '-' : '' ) + ( c.after ? n + c.symbol : c.symbol + n );
	};

	/* ---------------------------------------------------------- Totals (same rules as mmpo_totals) */

	function recalc() {
		const c = currency();
		const r = ( v ) => round( v, c.dec );
		$$( '[data-mmpo-sym]', form ).forEach( ( el ) => {
			el.textContent = c.after ? '' : c.symbol.trim();
			el.closest( '.mmpo-money' ).dataset.after = c.after ? c.symbol.trim() : '';
		} );
		let subtotal = 0;
		$$( '[data-mmpo-item]', form ).forEach( ( row ) => {
			const amount = r( num( $( '[data-mmpo-qty]', row ).value ) * num( $( '[data-mmpo-price]', row ).value ) );
			subtotal += amount;
			$( '[data-mmpo-line]', row ).textContent = money( amount );
		} );
		const shipping = r( num( form.elements[ 'shipping[amount]' ].value ) );
		let fees = 0;
		$$( '[data-mmpo-charge="fees"]', form ).forEach( ( row ) => {
			const pct = $( 'select', row ).value === 'percent';
			const v = num( $( '[data-mmpo-calc]:not(select)', row ).value );
			const amount = pct ? r( subtotal * v / 100 ) : r( v );
			fees += amount;
			$( '[data-mmpo-unit]', row ).textContent = pct ? '%' : ( c.after ? '' : c.symbol.trim() );
			$( '[data-mmpo-charge-out]', row ).textContent = money( amount );
		} );
		const taxable = subtotal + ( form.elements.tax_all.checked ? shipping + fees : 0 );
		let taxes = 0;
		$$( '[data-mmpo-charge="taxes"]', form ).forEach( ( row ) => {
			const pct = $( 'select', row ).value === 'percent';
			const v = num( $( '[data-mmpo-calc]:not(select)', row ).value );
			const amount = pct ? r( taxable * v / 100 ) : r( v );
			taxes += amount;
			$( '[data-mmpo-unit]', row ).textContent = pct ? '%' : ( c.after ? '' : c.symbol.trim() );
			$( '[data-mmpo-charge-out]', row ).textContent = money( amount );
		} );
		const total = r( subtotal + shipping + fees + taxes );
		const paid = r( num( form.elements.paid.value ) );
		const out = { subtotal: r( subtotal ), shipping, fees: r( fees ), taxes: r( taxes ), total, balance: r( total - paid ) };
		Object.keys( out ).forEach( ( k ) => {
			$$( '[data-mmpo-out="' + k + '"]' ).forEach( ( el ) => {
				el.textContent = money( out[ k ] );
			} );
		} );
		const bal = $( '.mmpo-balance' );
		if ( bal ) {
			bal.classList.toggle( 'is-paid', paid > 0 && out.balance <= 0 );
		}
		return out;
	}

	/* ---------------------------------------------------------- Rows */

	function addRow( kind, focus ) {
		const tpl = document.getElementById( 'item' === kind ? 'mmpo-item-template' : 'mmpo-' + kind + '-template' );
		const holder = 'item' === kind ? $( '[data-mmpo-items]', form ) : $( '[data-mmpo-list="' + kind + '"]', form );
		const html = tpl.innerHTML.replace( /__i__/g, 'n' + ( seq++ ) );
		const wrap = document.createElement( 'div' );
		wrap.innerHTML = html.trim();
		const row = wrap.firstElementChild;
		holder.appendChild( row );
		if ( 'item' === kind ) {
			bindProduct( row );
		}
		recalc();
		if ( focus ) {
			const input = $( 'input:not([type=hidden])', row );
			if ( input ) {
				input.focus();
			}
		}
		dirty = true;
		return row;
	}

	form.addEventListener( 'click', ( e ) => {
		const add = e.target.closest( '[data-mmpo-add]' );
		if ( add ) {
			e.preventDefault();
			addRow( add.dataset.mmpoAdd, true );
			return;
		}
		const rm = e.target.closest( '[data-mmpo-remove]' );
		if ( rm ) {
			e.preventDefault();
			const row = rm.closest( '[data-mmpo-item], [data-mmpo-charge]' );
			if ( row && row.matches( '[data-mmpo-item]' ) && $$( '[data-mmpo-item]', form ).length === 1 ) {
				// Keep one empty product row.
				$$( 'input, textarea', row ).forEach( ( el ) => {
					el.value = el.matches( '[data-mmpo-qty]' ) ? '1' : ( el.matches( '[data-mmpo-pid]' ) ? '0' : '' );
				} );
				$( '[data-mmpo-hint]', row ).textContent = '';
			} else if ( row ) {
				row.remove();
			}
			recalc();
			dirty = true;
		}
	} );

	form.addEventListener( 'input', ( e ) => {
		dirty = true;
		if ( e.target.matches( '[data-mmpo-calc]' ) ) {
			recalc();
		}
		if ( e.target.matches( '[data-mmpo-desc]' ) ) {
			grow( e.target );
		}
	} );
	form.addEventListener( 'change', ( e ) => {
		dirty = true;
		if ( e.target.matches( '[data-mmpo-calc]' ) ) {
			recalc();
		}
	} );

	function grow( el ) {
		el.style.height = 'auto';
		el.style.height = Math.min( 220, el.scrollHeight + 2 ) + 'px';
	}
	$$( '[data-mmpo-desc]', form ).forEach( grow );

	/* ---------------------------------------------------------- Suggest lists */

	function debounce( fn, ms ) {
		let timer;
		return ( ...args ) => {
			clearTimeout( timer );
			timer = setTimeout( () => fn( ...args ), ms );
		};
	}

	function get( action, q ) {
		const url = cfg.ajax + '?action=' + action + '&nonce=' + encodeURIComponent( cfg.nonce ) + '&q=' + encodeURIComponent( q );
		return fetch( url, { credentials: 'same-origin' } ).then( ( r ) => r.json() ).then( ( r ) => ( r && r.success ? r.data : [] ) );
	}

	function suggester( input, list, opts ) {
		let items = [];
		let active = -1;
		let last = null;
		const close = () => {
			list.hidden = true;
			active = -1;
		};
		const paint = () => {
			$$( '.mmpo-suggest__item', list ).forEach( ( el, i ) => el.classList.toggle( 'is-active', i === active ) );
		};
		const render = ( data, q ) => {
			items = data;
			list.innerHTML = '';
			if ( ! data.length ) {
				list.innerHTML = '<div class="mmpo-suggest__empty">' + esc( opts.empty ) + '</div>';
				list.hidden = '' === q && ! opts.showEmpty;
				return;
			}
			data.forEach( ( item, i ) => {
				const el = document.createElement( 'button' );
				el.type = 'button';
				el.className = 'mmpo-suggest__item';
				el.innerHTML = opts.html( item );
				el.addEventListener( 'mousedown', ( ev ) => ev.preventDefault() );
				el.addEventListener( 'click', () => {
					opts.pick( item );
					close();
				} );
				el.addEventListener( 'mouseenter', () => {
					active = i;
					paint();
				} );
				list.appendChild( el );
			} );
			list.hidden = false;
		};
		const search = debounce( ( q ) => {
			if ( document.activeElement !== input || q.length < ( opts.min || 0 ) ) {
				close();
				return;
			}
			last = q;
			list.innerHTML = '<div class="mmpo-suggest__empty">' + esc( t.searching ) + '</div>';
			list.hidden = false;
			opts.fetch( q ).then( ( data ) => {
				if ( q === last && document.activeElement === input ) {
					render( data, q );
				}
			} ).catch( close );
		}, 220 );
		input.addEventListener( 'input', () => search( input.value.trim() ) );
		input.addEventListener( 'focus', () => {
			if ( opts.onFocus ) {
				search( input.value.trim() );
			}
		} );
		input.addEventListener( 'blur', () => setTimeout( close, 120 ) );
		input.addEventListener( 'keydown', ( e ) => {
			if ( 'Escape' === e.key ) {
				close();
				return;
			}
			if ( list.hidden || ! items.length ) {
				return;
			}
			if ( 'ArrowDown' === e.key || 'ArrowUp' === e.key ) {
				e.preventDefault();
				active = ( active + ( 'ArrowDown' === e.key ? 1 : -1 ) + items.length ) % items.length;
				paint();
			} else if ( 'Enter' === e.key && active >= 0 ) {
				e.preventDefault();
				opts.pick( items[ active ] );
				close();
			}
		} );
	}

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, ( c ) => ( { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ] ) );
	}

	/* ---------------------------------------------------------- Products */

	function bindProduct( row ) {
		const input = $( '[data-mmpo-product]', row );
		const list = $( '.mmpo-suggest', row );
		if ( ! input || ! list || ! cfg.woo ) {
			return;
		}
		input.addEventListener( 'input', () => {
			// Typing a different name turns it into a custom item.
			const pid = $( '[data-mmpo-pid]', row );
			if ( pid.value !== '0' && input.dataset.picked !== input.value ) {
				pid.value = '0';
				$( '[data-mmpo-hint]', row ).textContent = '';
			}
		} );
		suggester( input, list, {
			fetch: ( q ) => get( 'mmpo_products', q ),
			onFocus: true,
			showEmpty: true,
			empty: t.noProducts,
			html: ( p ) => '<span class="mmpo-suggest__img">' + ( p.img ? '<img src="' + esc( p.img ) + '" alt="">' : '' ) + '</span>' +
				'<span class="mmpo-suggest__text"><strong>' + esc( p.name ) + '</strong><small>' + esc( [ p.sku ? 'SKU ' + p.sku : '', p.specs ].filter( Boolean ).join( ' · ' ) ) + '</small></span>' +
				'<span class="mmpo-suggest__side"><b>' + ( null === p.price ? '' : esc( money( p.price ) ) ) + '</b><i class="' + ( p.in ? 'is-in' : 'is-out' ) + '">' + esc( p.stock ) + '</i></span>',
			pick: ( p ) => {
				input.value = p.name;
				input.dataset.picked = p.name;
				$( '[data-mmpo-pid]', row ).value = String( p.id );
				$( '[data-mmpo-sku]', row ).value = p.sku || '';
				const desc = $( '[data-mmpo-desc]', row );
				if ( ! desc.value.trim() ) {
					desc.value = p.specs || '';
					grow( desc );
				}
				const price = $( '[data-mmpo-price]', row );
				if ( null !== p.price ) {
					price.value = String( p.price );
				}
				$( '[data-mmpo-hint]', row ).textContent = null === p.price ? '' : t.catalog + ' ' + money( p.price ) + ' · ' + p.stock;
				recalc();
				dirty = true;
				const qty = $( '[data-mmpo-qty]', row );
				qty.focus();
				qty.select();
			},
		} );
	}
	$$( '[data-mmpo-item]', form ).forEach( bindProduct );

	/* ---------------------------------------------------------- Customer fill */

	const custInput = $( '[data-mmpo-customer]', form );
	const custList = $( '[data-mmpo-customer-list]', form );
	if ( custInput && custList ) {
		suggester( custInput, custList, {
			min: 2,
			fetch: ( q ) => get( 'mmpo_customers', q ),
			empty: t.noCustomers,
			html: ( c ) => {
				const s = c.ship;
				const where = [ s.address_1, s.city, s.state, s.postcode ].filter( Boolean ).join( ', ' );
				return '<span class="mmpo-suggest__text"><strong>' + esc( s.name || s.company || s.email ) + ( s.company && s.name ? ' · ' + esc( s.company ) : '' ) + '</strong><small>' + esc( where || s.email ) + '</small></span><span class="mmpo-suggest__side"><i>' + esc( c.source ) + '</i></span>';
			},
			pick: ( c ) => {
				Object.keys( c.ship ).forEach( ( k ) => {
					const el = form.elements[ 'ship[' + k + ']' ];
					if ( el ) {
						el.value = c.ship[ k ] || '';
					}
				} );
				states( c.ship.state || '' );
				custInput.value = '';
				dirty = true;
			},
		} );
	}

	/* ---------------------------------------------------------- States follow the country */

	function states( keep ) {
		const country = form.elements[ 'ship[country]' ];
		const wrap = $( '[data-mmpo-state-wrap]', form );
		if ( ! country || ! wrap ) {
			return;
		}
		const current = form.elements[ 'ship[state]' ];
		const value = undefined === keep ? current.value : keep;
		const list = cfg.states[ country.value ];
		let el;
		if ( list && Object.keys( list ).length ) {
			el = document.createElement( 'select' );
			el.innerHTML = '<option value=""></option>' + Object.keys( list ).map( ( code ) => '<option value="' + esc( code ) + '">' + esc( list[ code ] ) + '</option>' ).join( '' );
			// Match a typed name or a code.
			const match = Object.keys( list ).find( ( code ) => code === value || list[ code ].toLowerCase() === String( value ).toLowerCase() );
			el.value = match || '';
		} else {
			el = document.createElement( 'input' );
			el.value = value;
		}
		el.className = 'mmpo-input';
		el.name = 'ship[state]';
		el.setAttribute( 'data-mmpo-state', '' );
		wrap.innerHTML = '';
		wrap.appendChild( el );
	}
	const countrySel = form.elements[ 'ship[country]' ];
	if ( countrySel ) {
		countrySel.addEventListener( 'change', () => states( '' ) );
		states();
	}

	/* ---------------------------------------------------------- Status, document title, number */

	function pill() {
		const checked = $( '[data-mmpo-status]:checked', form );
		const holder = $( '[data-mmpo-pill]' );
		if ( ! checked || ! holder ) {
			return;
		}
		const st = cfg.statuses[ checked.value ] || { label: checked.value, color: '#64748b' };
		holder.innerHTML = '<span class="mmpo-pill" style="--c:' + esc( st.color ) + '"><i></i>' + esc( st.label ) + '</span>';
	}
	$$( '[data-mmpo-status]', form ).forEach( ( el ) => el.addEventListener( 'change', () => {
		pill();
		const totals = recalc();
		if ( 'paid' === el.value && num( form.elements.paid.value ) <= 0 && totals.total > 0 ) {
			form.elements.paid.value = totals.total.toFixed( currency().dec );
			recalc();
			toast( t.paidFull );
		}
		if ( 'partial' === el.value && num( form.elements.paid.value ) <= 0 ) {
			form.elements.paid.focus();
		}
	} ) );
	const paidFull = $( '[data-mmpo-paidfull]', form );
	if ( paidFull ) {
		paidFull.addEventListener( 'click', () => {
			form.elements.paid.value = recalc().total.toFixed( currency().dec );
			recalc();
			dirty = true;
		} );
	}
	const docSel = $( '[data-mmpo-docsel]', form );
	if ( docSel ) {
		docSel.addEventListener( 'change', () => {
			$( '[data-mmpo-doc]' ).textContent = docSel.options[ docSel.selectedIndex ].textContent;
			$( '[data-mmpo-duelabel]', form ).textContent = 'quote' === docSel.value ? t.valid : t.due;
		} );
	}
	const numIn = $( '[data-mmpo-numin]', form );
	if ( numIn ) {
		numIn.addEventListener( 'input', () => {
			$( '[data-mmpo-number]' ).textContent = numIn.value.trim() || numIn.placeholder;
		} );
	}

	/* ---------------------------------------------------------- Save */

	function toast( msg ) {
		const el = $( '[data-mmpo-state]' );
		if ( ! el ) {
			return;
		}
		el.textContent = msg;
		el.classList.add( 'is-on' );
		clearTimeout( toast.timer );
		toast.timer = setTimeout( () => el.classList.remove( 'is-on' ), 3200 );
	}

	function saveInBackground() {
		const data = new FormData( form );
		data.set( 'ajax', '1' );
		data.delete( 'then' );
		return fetch( cfg.post, { method: 'POST', body: data, credentials: 'same-origin' } )
			.then( ( r ) => r.json() )
			.then( ( r ) => {
				if ( ! r || ! r.success ) {
					throw new Error( ( r && r.data && r.data.message ) || t.failed );
				}
				const d = r.data;
				form.elements.id.value = String( d.id );
				if ( numIn && ! numIn.value.trim() ) {
					numIn.value = d.number;
				}
				$( '[data-mmpo-number]' ).textContent = d.number;
				const hist = $( '[data-mmpo-history]' );
				if ( hist ) {
					hist.innerHTML = d.history;
					hist.closest( 'details' ).hidden = false;
				}
				form.elements.status_note.value = '';
				const dup = $( '.mmpo-dup-js' );
				if ( dup ) {
					dup.hidden = ! d.dup;
					$( 'p', dup ).textContent = d.dup ? t.dup.replace( '%s', d.dup ) : '';
				}
				if ( window.history && window.history.replaceState ) {
					window.history.replaceState( null, '', d.edit );
				}
				dirty = false;
				toast( t.saved );
				return d;
			} );
	}

	$$( '[data-mmpo-open]' ).forEach( ( btn ) => btn.addEventListener( 'click', () => {
		const download = 'download' === btn.dataset.mmpoOpen;
		// Open the tab now (browsers only allow it right after a click), fill it after saving.
		const win = download ? null : window.open( '', '_blank' );
		if ( win ) {
			win.document.write( '<p style="font:15px system-ui;padding:24px;color:#506279">' + esc( t.saving ) + '</p>' );
		}
		btn.disabled = true;
		toast( t.saving );
		saveInBackground().then( ( d ) => {
			if ( download ) {
				window.location.href = d.download;
			} else if ( win ) {
				win.location.href = d.pdf;
			} else {
				toast( t.popup );
				window.location.href = d.pdf;
			}
		} ).catch( ( err ) => {
			if ( win ) {
				win.close();
			}
			toast( err.message || t.failed );
		} ).finally( () => {
			btn.disabled = false;
		} );
	} ) );

	form.addEventListener( 'submit', () => {
		dirty = false;
	} );
	document.addEventListener( 'keydown', ( e ) => {
		if ( ( e.ctrlKey || e.metaKey ) && 's' === e.key.toLowerCase() ) {
			e.preventDefault();
			if ( form.requestSubmit ) {
				form.requestSubmit();
			} else {
				form.submit();
			}
		}
	} );
	window.addEventListener( 'beforeunload', ( e ) => {
		if ( dirty ) {
			e.preventDefault();
			e.returnValue = t.leave;
		}
	} );

	recalc();
	pill();
}() );
