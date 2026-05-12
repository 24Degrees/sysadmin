( function () {
	'use strict';

	const grid = document.getElementById( 'sysadmin-drag-grid' );
	if ( ! grid || typeof window.jQuery === 'undefined' ) {
		return;
	}

	const storageKey = 'sysadminToolboxCardOrder';
	const $grid = window.jQuery( grid );
	const storedOrder = window.localStorage.getItem( storageKey );

	if ( storedOrder ) {
		try {
			const ids = JSON.parse( storedOrder );
			ids.forEach( function ( id ) {
				const card = grid.querySelector( '[data-card-id="' + id + '"]' );
				if ( card ) {
					grid.appendChild( card );
				}
			} );
		} catch ( e ) {
			window.localStorage.removeItem( storageKey );
		}
	}

	$grid.sortable( {
		handle: '.sysadmin-drag-handle',
		items: '.sysadmin-card',
		placeholder: 'sysadmin-card sysadmin-card-placeholder',
		update: function () {
			const currentOrder = Array.from( grid.querySelectorAll( '.sysadmin-card' ) )
				.map( function ( el ) {
					return el.getAttribute( 'data-card-id' );
				} )
				.filter( Boolean );

			window.localStorage.setItem( storageKey, JSON.stringify( currentOrder ) );
		}
	} );

	const preview = document.querySelector( '.sysadmin-preview[data-password-column]' );
	if ( ! preview ) {
		return;
	}

	const columnInputs = preview.querySelectorAll( 'input[name="sysadmin_export_columns[]"]' );
	const capitalizeInput = preview.querySelector( 'input[name="sysadmin_capitalize_word"]' );
	const suffix = preview.getAttribute( 'data-suffix' ) || '';
	const position = preview.getAttribute( 'data-position' ) || 'suffix';
	const passwordColumn = parseInt( preview.getAttribute( 'data-password-column' ), 10 );

	const capitalizeWord = function ( word ) {
		if ( ! word ) {
			return '';
		}

		const chars = Array.from( word );
		chars[0] = chars[0].toLocaleUpperCase();
		return chars.join( '' );
	};

	const rebuildPassword = function ( baseWord, shouldCapitalize ) {
		let normalizedWord = baseWord || '';
		if ( shouldCapitalize ) {
			normalizedWord = capitalizeWord( normalizedWord );
		}

		return position === 'prefix' ? suffix + normalizedWord : normalizedWord + suffix;
	};

	const updateColumnsPreview = function () {
		const selected = new Set();
		columnInputs.forEach( function ( input ) {
			if ( input.checked ) {
				selected.add( input.value );
			}
		} );

		preview.querySelectorAll( '[data-source-col]' ).forEach( function ( cell ) {
			const sourceColumn = cell.getAttribute( 'data-source-col' );
			cell.style.display = selected.has( sourceColumn ) ? '' : 'none';
		} );
	};

	const updatePasswordPreview = function () {
		if ( Number.isNaN( passwordColumn ) || ! capitalizeInput ) {
			return;
		}

		const shouldCapitalize = capitalizeInput.checked;
		const selector = '.sysadmin-password-cell[data-source-col="' + passwordColumn + '"]';
		preview.querySelectorAll( selector ).forEach( function ( cell ) {
			const baseWord = cell.getAttribute( 'data-base-word' ) || '';
			cell.textContent = rebuildPassword( baseWord, shouldCapitalize );
		} );
	};

	columnInputs.forEach( function ( input ) {
		input.addEventListener( 'change', updateColumnsPreview );
	} );

	if ( capitalizeInput ) {
		capitalizeInput.addEventListener( 'change', updatePasswordPreview );
	}

	updateColumnsPreview();
	updatePasswordPreview();
}() );
