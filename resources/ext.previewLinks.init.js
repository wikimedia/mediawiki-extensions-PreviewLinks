( () => {
	let hoverTimer, activeRequest, popup;

	function makePopup( e, data ) {
		if ( popup ) {
			popup.$element.remove();
		}
		const $cnt = $( '<div>' ).addClass( 'ext-preview-links-popup-body' );
		let portraitOriented = false;
		if ( data.thumbnail ) {
			if ( data.thumbnail.height < data.thumbnail.width ) {
				$cnt.append( $( '<div>' ).css( {
					width: '320px',
					height: data.thumbnail.height <= 180 ? data.thumbnail.height + 'px' : '180px'
				} ).append(
					$( '<img>' )
						.css( {
							width: '100%',
							height: '100%',
							objectFit: 'cover'
						} )
						.attr( 'src', data.thumbnail.source )
				) );
			} else {
				$cnt.append( $( '<div>' ).css( {
					width: data.thumbnail.width <= 203 ? data.thumbnail.width + 'px' : '203px',
					height: '250px'
				} ).append(
					$( '<img>' )
						.css( {
							width: '100%',
							height: '100%',
							objectFit: 'cover'
						} )
						.attr( 'src', data.thumbnail.source )
				) );
				portraitOriented = true;
				$cnt.addClass( 'ext-preview-portrait-oriented' );
			}
		}
		const $content = $( '<p>' ).css( { padding: '10px' } ).html( data.extract_html );
		$cnt.append( $( '<div>' ).addClass( 'ext-preview-links-popup-content' ).append( $content ) );
		const boundingRect = e.target.getBoundingClientRect();

		const topPosition = boundingRect.top + e.target.offsetHeight + 5;
		const isAtBottom = ( window.innerHeight - topPosition ) < 250;
		let position = 'below';
		if ( isAtBottom ) {
			position = 'above';
		}

		const $overlay = $( '<div>' ).addClass( 'oo-ui-defaultOverlay' );
		const id = 'preview-links-' + data.pageid;
		popup = new OO.ui.PopupWidget( {
			$content: $( '<div>' ).html( $cnt ),
			padded: false,
			align: 'center',
			width: portraitOriented ? 450 : 320,
			position: position,
			classes: [ 'ext-preview-links-page-popup' ],
			id: id
		} );
		$( e.target ).attr( 'aria-describedby', id );

		$overlay.append( popup.$element );
		$( '#mw-teleport-target' ).append( $overlay ); // eslint-disable-line no-jquery/no-global-selector
		popup.toggle( true );
		popup.$element.removeClass( 'oo-ui-element-hidden' );
		popup.$element.attr( 'role', 'tooltip' );

		if ( isAtBottom ) {
			popup.$element.css( {
				position: 'fixed',
				bottom: window.innerHeight - boundingRect.top - 5,
				left: boundingRect.left + boundingRect.width / 2
			} );
		} else {
			popup.$element.css( {
				position: 'fixed',
				top: topPosition,
				left: boundingRect.left + boundingRect.width / 2
			} );
		}

		const bodyContent = $( '#bodyContent' ); // eslint-disable-line no-jquery/no-global-selector, no-jquery/variable-pattern
		// Check if there is enough space to the right
		if ( e.clientX + popup.$element.width() >
			bodyContent.offset().left + bodyContent.width() ) {
			popup.$element.css( {
				left: bodyContent.offset().left + bodyContent.width() - popup.$element.width()
			} );
		}
	}

	$( document ).on( 'mouseenter focusin', '.mw-body-content a', ( e ) => {
		e.preventDefault();
		if ( $( '.ve-activated' ).length > 0 ) {
			return;
		}
		const role = $( e.target ).attr( 'role' );
		if ( role === 'button' ) {
			return;
		}
		const href = $( e.target ).attr( 'href' );
		if ( !href || href.includes( 'action' ) || href.includes( 'diff' ) ) { // eslint-disable-line es-x/no-array-prototype-includes
			return;
		}

		const classes = $( e.target ).attr( 'class' );
		if ( classes && classes.includes( 'external' ) ) { // eslint-disable-line es-x/no-array-prototype-includes
			return;
		}

		$( e.target ).attr( 'orig-title', $( e.target ).attr( 'title' ) );
		$( e.target ).attr( 'title', '' );

		hoverTimer = setTimeout( () => {
			let target = e.target;
			if ( e.target.nodeName !== 'A' ) {
				target = $( target ).parent()[ 0 ];
				if ( target.nodeName !== 'A' ) {
					target = $( target ).parent()[ 0 ];
					if ( target.nodeName !== 'A' ) {
						target = $( target ).parent()[ 0 ];
					}
				}
			}

			const title = $( target ).attr( 'orig-title' ),
				isNew = target.classList.contains( 'new' );

			if ( isNew || !title ) {
				return;
			}
			e.stopImmediatePropagation();

			activeRequest = $.ajax( {
				url: mw.util.wikiScript( 'rest' ) + '/previewlinks/preview',
				method: 'GET',
				data: {
					pagetitle: encodeURIComponent( title )
				},
				beforeSend: () => {
					if ( activeRequest ) {
						activeRequest.abort();
					}
				}
			} ).done( ( data ) => {
				if ( data.length === 0 ) {
					return;
				}
				makePopup( e, data );
			} );
		}, 500 );
	} ).on( 'mouseleave blur', '.mw-body-content a', ( e ) => {
		clearTimeout( hoverTimer );
		if ( popup ) {
			popup.$element.remove();
		}
		$( e.target ).attr( 'title', $( e.target ).attr( 'orig-title' ) );
	} );

} )();
