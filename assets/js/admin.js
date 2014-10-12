(function ( $ ) {
	'use strict';

	$( function () {
		$( '#woocommerce_pagarme_methods' ).on( 'change', function () {
			var current = $( this ).val(),
				elements = $( '#mainform h4:eq(0), #mainform table:eq(1)' );

			if ( 'ticket' === current ) {
				elements.hide();
			} else {
				elements.show();
			}
		}).change();
	});

}( jQuery ));
