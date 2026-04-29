/* global jQuery, msStatsConfig, window */
(function ( $ ) {
	'use strict';

	var cfg     = window.msStatsConfig || {};
	var primary = cfg.primaryColor || '#385bce';

	/* ── Datepicker ────────────────────────────────────────────────── */
	function initDatepicker() {
		$( '.ms-stats-datepicker' ).datepicker( {
			dateFormat:  'dd/mm/yy',
			changeMonth: true,
			changeYear:  true,
			onSelect: function ( dateText, inst ) {
				var altField = $( this ).data( 'alt-field' );
				var d        = new Date( inst.selectedYear, inst.selectedMonth, inst.selectedDay );
				var ymd      = d.getFullYear() + '-' +
					String( d.getMonth() + 1 ).padStart( 2, '0' ) + '-' +
					String( d.getDate() ).padStart( 2, '0' );
				$( altField ).val( ymd );
			}
		} );
	}

	/* ── DataTables ─────────────────────────────────────────────────── */
	function initDataTables() {
		if ( typeof $.fn.DataTable === 'undefined' ) { return; }
		$.fn.dataTable.ext.errMode = 'none';
		$( '.ms-stats-table' ).DataTable( {
			pageLength: 25,
			responsive: true,
			language: {
				search:            '',
				searchPlaceholder: 'Search…',
				lengthMenu:        'Show _MENU_',
				info:              '_START_–_END_ of _TOTAL_',
				paginate: { first: '«', last: '»', next: '›', previous: '‹' }
			}
		} );
	}

	/* ── PDF helpers ────────────────────────────────────────────────── */
	function hexToRgb( hex ) {
		var clean  = hex.replace( '#', '' );
		var chunks = clean.match( /.{2}/g );
		return chunks
			? [ parseInt( chunks[0], 16 ), parseInt( chunks[1], 16 ), parseInt( chunks[2], 16 ) ]
			: [ 56, 91, 206 ];
	}

	async function loadFont( doc ) {
		if ( ! cfg.fontUrl ) { return; }
		try {
			var resp   = await fetch( cfg.fontUrl );
			var buffer = await resp.arrayBuffer();
			var bytes  = new Uint8Array( buffer );
			var binary = '';
			for ( var i = 0; i < bytes.length; i++ ) {
				binary += String.fromCharCode( bytes[ i ] );
			}
			var b64 = btoa( binary );
			doc.addFileToVFS( 'DejaVuSans.ttf', b64 );
			doc.addFont( 'DejaVuSans.ttf', 'DejaVuSans', 'normal' );
			doc.addFont( 'DejaVuSans.ttf', 'DejaVuSans', 'bold' );
			doc.setFont( 'DejaVuSans' );
		} catch ( e ) { /* font load failed, fall back to Helvetica */ }
	}

	function loadImg( url ) {
		return new Promise( function ( resolve, reject ) {
			var img         = new Image();
			img.crossOrigin = 'anonymous';
			img.onload      = function () { resolve( img ); };
			img.onerror     = reject;
			img.src         = url;
		} );
	}

	async function buildPDF( tableId, title ) {
		var rgb   = hexToRgb( primary );
		var doc   = new window.jspdf.jsPDF( 'l', 'mm', 'a4' );
		var pageW = doc.internal.pageSize.getWidth();
		var pageH = doc.internal.pageSize.getHeight();

		/* embed Unicode font so diacritics and Greek render correctly */
		await loadFont( doc );

		/* coloured header bar */
		doc.setFillColor( rgb[0], rgb[1], rgb[2] );
		doc.rect( 0, 0, pageW, 26, 'F' );

		/* logo — best-effort, skip on CORS failure */
		if ( cfg.logo ) {
			try {
				var img  = await loadImg( cfg.logo );
				var cv   = document.createElement( 'canvas' );
				cv.width = img.width; cv.height = img.height;
				cv.getContext( '2d' ).drawImage( img, 0, 0 );
				var logoH = 16;
				var logoW = ( img.width / img.height ) * logoH;
				doc.addImage( cv.toDataURL( 'image/png' ), 'PNG', pageW - logoW - 10, 5, logoW, logoH );
			} catch ( e ) { /* logo unavailable, continue */ }
		}

		/* header text */
		doc.setTextColor( 255, 255, 255 );
		doc.setFontSize( 8 );  doc.setFont( undefined, 'normal' );
		doc.text( cfg.siteName || 'Bridge Project', 10, 9 );
		doc.setFontSize( 14 ); doc.setFont( undefined, 'bold' );
		doc.text( title, 10, 20 );
		doc.setFontSize( 8 );  doc.setFont( undefined, 'normal' );
		doc.text( new Date().toLocaleDateString( 'en-GB' ), pageW - 10, 20, { align: 'right' } );

		var uniFont = cfg.fontUrl ? 'DejaVuSans' : 'helvetica';

		/* table */
		doc.autoTable( {
			html:          '#' + tableId,
			startY:        32,
			theme:         'grid',
			headStyles:    { fillColor: rgb, textColor: [ 255, 255, 255 ], fontStyle: 'normal', fontSize: 8.5, font: uniFont },
			bodyStyles:    { fontSize: 8, textColor: [ 40, 40, 40 ], font: uniFont },
			alternateRowStyles: { fillColor: [ 246, 247, 249 ] },
			tableLineColor:  [ 210, 212, 216 ],
			tableLineWidth:  0.15,
			margin:          { left: 10, right: 10 },
			didDrawPage: function ( data ) {
				doc.setFontSize( 7.5 ); doc.setTextColor( 150 );
				doc.text( 'Page ' + data.pageNumber, pageW / 2, pageH - 5, { align: 'center' } );
				doc.text( cfg.pluginTitle || 'MS Stats', 10, pageH - 5 );
				doc.setFillColor( rgb[0], rgb[1], rgb[2] );
				doc.rect( 0, pageH - 2, pageW, 2, 'F' );
			}
		} );

		doc.save( title.replace( /[^a-z0-9]/gi, '-' ).toLowerCase() + '-' + new Date().toISOString().slice( 0, 10 ) + '.pdf' );
	}

	/* ── Boot ───────────────────────────────────────────────────────── */
	$( function () {
		initDatepicker();
		initDataTables();

		$( document ).on( 'click', '.ms-stats-export-pdf', function ( e ) {
			e.preventDefault();
			buildPDF( $( this ).data( 'table' ), $( this ).data( 'title' ) );
		} );
	} );

}( jQuery ) );
