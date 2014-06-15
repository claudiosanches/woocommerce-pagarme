(function ( $ ) {
	'use strict';

	$( function () {

		function switchInstallmentsOptions( target ) {
			var elements = $( '#mainform h4:eq(0), #mainform table:eq(1)' );

			if ( 'ticket' === target ) {
				elements.hide();
			} else {
				elements.show();
			}
		}

		switchInstallmentsOptions( $( '#woocommerce_pagarme_methods' ).val() );

		$( '#woocommerce_pagarme_methods' ).on( 'change', function () {
			switchInstallmentsOptions( $( this ).val() );
		});
	});

}( jQuery ));
