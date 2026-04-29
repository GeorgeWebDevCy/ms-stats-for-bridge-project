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
	function numFromCell( val ) {
		return parseFloat( String( val ).replace( /<[^>]*>/g, ' ' ).replace( /[%,]/g, '' ).trim() ) || 0;
	}

	function updateFooter( api ) {
		try {
		var $tfoot = $( api.table().node() ).find( 'tfoot tr' );
		if ( ! $tfoot.length ) { return; }

		/* Sum columns */
		$tfoot.find( '[data-sum-col]' ).each( function () {
			var col   = parseInt( $( this ).data( 'sum-col' ), 10 );
			var total = api.column( col, { search: 'applied' } ).data().reduce( function ( a, b ) {
				return a + numFromCell( b );
			}, 0 );
			$( this ).text( total.toLocaleString() );
		} );

		/* Average columns */
		$tfoot.find( '[data-avg-col]' ).each( function () {
			var col  = parseInt( $( this ).data( 'avg-col' ), 10 );
			var data = api.column( col, { search: 'applied' } ).data();
			var sum  = data.reduce( function ( a, b ) { return a + numFromCell( b ); }, 0 );
			var avg  = data.length ? ( sum / data.length ).toFixed( 1 ) : '0';
			$( this ).text( avg + '%' );
		} );

		/* Rate columns: numerator col / denominator col * 100 */
		$tfoot.find( '[data-rate-cols]' ).each( function () {
			var parts = String( $( this ).data( 'rate-cols' ) ).split( '/' );
			var num   = api.column( parseInt( parts[0], 10 ), { search: 'applied' } ).data()
				.reduce( function ( a, b ) { return a + numFromCell( b ); }, 0 );
			var den   = api.column( parseInt( parts[1], 10 ), { search: 'applied' } ).data()
				.reduce( function ( a, b ) { return a + numFromCell( b ); }, 0 );
			$( this ).text( den > 0 ? ( num / den * 100 ).toFixed( 1 ) + '%' : '0%' );
		} );
		} catch ( e ) { /* footer update failed silently */ }
	}

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
			},
			footerCallback: function () {
				updateFooter( this.api() );
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

	function fmtYMD( ymd ) {
		if ( ! ymd ) { return ''; }
		var p = ymd.split( '-' );
		return p[2] + '/' + p[1] + '/' + p[0];
	}

	function dateRangeLabel() {
		var params = new URLSearchParams( window.location.search );
		var from   = params.get( 'date_from' ) || '';
		var to     = params.get( 'date_to' )   || '';
		if ( from && to )   { return 'Period: ' + fmtYMD( from ) + ' – ' + fmtYMD( to ); }
		if ( from )         { return 'From: ' + fmtYMD( from ); }
		if ( to )           { return 'To: ' + fmtYMD( to ); }
		return 'All records';
	}

	async function buildPDF( tableId, title ) {
		var rgb       = hexToRgb( primary );
		var doc       = new window.jspdf.jsPDF( 'l', 'mm', 'a4' );
		var pageW     = doc.internal.pageSize.getWidth();
		var pageH     = doc.internal.pageSize.getHeight();
		var headerH   = 34;
		var dateLabel = dateRangeLabel();

		/* embed Unicode font so diacritics and Greek render correctly */
		await loadFont( doc );

		var uFont = cfg.fontUrl ? 'DejaVuSans' : 'helvetica';

		/* coloured header bar */
		doc.setFillColor( rgb[0], rgb[1], rgb[2] );
		doc.rect( 0, 0, pageW, headerH, 'F' );

		/* logo — base64 data URI pre-encoded server-side, no CORS */
		if ( cfg.logoData ) {
			var lW = Number( cfg.logoW );   // explicit cast; string "0" is truthy but Number("0") = 0
			var lH = Number( cfg.logoH );
			var lWmm, lHmm;

			if ( lW > 0 && lH > 0 ) {
				lWmm = 40;                          // 40mm ≈ 150px at 96dpi — proper header logo size
				lHmm = ( lH / lW ) * lWmm;
				if ( lHmm > headerH - 4 ) {         // clamp height inside the header bar
					lHmm = headerH - 4;
					lWmm = ( lW / lH ) * lHmm;
				}
			} else {
				lWmm = 40; lHmm = headerH - 6;     // fallback when dimensions unknown
			}

			doc.addImage( cfg.logoData, cfg.logoFmt || 'PNG', pageW - lWmm - 10, ( headerH - lHmm ) / 2, lWmm, lHmm );
		}

		/* header text */
		doc.setTextColor( 255, 255, 255 );
		doc.setFontSize( 8 );
		doc.setFont( uFont, 'normal' );
		doc.text( cfg.siteName || '', 10, 9 );

		doc.setFontSize( 14 );
		doc.setFont( uFont, 'bold' );
		doc.text( title, 10, 18 );

		doc.setFontSize( 9 );
		doc.setFont( uFont, 'normal' );
		doc.text( dateLabel, 10, 26 );

		doc.setFontSize( 8 );
		doc.text( new Date().toLocaleDateString( 'en-GB' ), 10, 33 );

		/* table */
		doc.autoTable( {
			html:          '#' + tableId,
			startY:        headerH + 4,
			theme:         'grid',
			headStyles:    { fillColor: [ 243, 244, 246 ], textColor: [ 17, 24, 39 ], fontStyle: 'bold', fontSize: 8.5, font: uFont, lineWidth: { bottom: 0.5 }, lineColor: rgb },
			bodyStyles:    { fontSize: 8, textColor: [ 40, 40, 40 ], font: uFont },
			alternateRowStyles: { fillColor: [ 246, 247, 249 ] },
			tableLineColor:  [ 210, 212, 216 ],
			tableLineWidth:  0.15,
			margin:          { left: 10, right: 10 },
			didParseCell: function ( data ) {
				/* Replace bar-chart cells with clean text from data-pdf-val attribute */
				var raw = data.cell.raw;
				if ( raw && raw.getAttribute ) {
					var pdfVal = raw.getAttribute( 'data-pdf-val' );
					if ( pdfVal !== null ) {
						data.cell.text = [ pdfVal ];
					}
				}
			},
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
