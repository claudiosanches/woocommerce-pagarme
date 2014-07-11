/* global wc_pagarme_params, PagarMe */
(function ( $ ) {
	'use strict';

	$( function () {

		/**
		 * Hide and display the credit card form.
		 *
		 * @param  {string} method
		 * @return {void}
		 */
		function wcPagarMeFormSwitch( method ) {
			var creditCardForm = $( '#pagarme-credit-cart-form' );

			if ( 'credit-card' === method ) {
				creditCardForm.slideDown( 200 );
			} else {
				creditCardForm.slideUp( 200 );
			}
		}

		/**
		 * Controls the credit card display.
		 *
		 * @return {void}
		 */
		function wcPagarMeFormDisplay() {
			var method = $( '#pagarme-payment-form input[name=pagarme_payment_method]' ).val();
			wcPagarMeFormSwitch( method );
		}

		wcPagarMeFormDisplay();

		/**
		 * Display or hide the credit card for when change the payment method.
		 */
		$( 'body' ).on( 'click', 'li.payment_method_pagarme input[name=pagarme_payment_method]', function () {
			wcPagarMeFormSwitch( $( this ).val() );
		});

		/**
		 * Display or hide the credit card for when change the payment gateway.
		 */
		$( 'body' ).on( 'updated_checkout', function () {
			wcPagarMeFormDisplay();
		});

		/**
		 * Process the credit card data when submit the checkout form.
		 */
		$( 'body' ).on( 'click', '#place_order', function () {
			if ( ! $( '#payment_method_pagarme' ).is( ':checked' ) ) {
				return true;
			}

			if ( 'radio' === $( 'body li.payment_method_pagarme input[name=pagarme_payment_method]' ).attr( 'type' ) ) {
				if ( 'credit-card' !== $( 'body li.payment_method_pagarme input[name=pagarme_payment_method]:checked' ).val() ) {
					return true;
				}
			} else {
				if ( 'credit-card' !== $( 'body li.payment_method_pagarme input[name=pagarme_payment_method]' ).val() ) {
					return true;
				}
			}

			PagarMe.encryption_key = wc_pagarme_params.encryption_key;

			var form           = $( 'form.checkout, form#order_review' ),
				creditCard     = new PagarMe.creditCard(),
				creditCardForm = $( '#pagarme-credit-cart-form', form ),
				errors         = null,
				errorHtml      = '';

			// Lock the checkout form.
			form.addClass( 'processing' );

			// Set the Credit card data.
			creditCard.cardHolderName      = $( '#pagarme-card-holder-name', form ).val();
			creditCard.cardExpirationMonth = $( '#pagarme-card-expiry', form ).val().replace( /[^\d]/g, '' ).substr( 0, 2 );
			creditCard.cardExpirationYear  = $( '#pagarme-card-expiry', form ).val().replace( /[^\d]/g, '' ).substr( 2 );
			creditCard.cardNumber          = $( '#pagarme-card-number', form ).val().replace( /[^\d]/g, '' );
			creditCard.cardCVV             = $( '#pagarme-card-cvc', form ).val();

			// Get the errors.
			errors = creditCard.fieldErrors();

			// Display the errors in credit card form.
			if ( ! $.isEmptyObject( errors ) ) {
				$( '.woocommerce-error', creditCardForm ).remove();

				errorHtml += '<ul>';
				$.each( errors, function ( key, value ) {
					errorHtml += '<li>' + value + '</li>';
				});
				errorHtml += '</ul>';

				creditCardForm.prepend( '<div class="woocommerce-error">' + errorHtml + '</div>' );
			} else {
				form.removeClass( 'processing' );
				$( '.woocommerce-error', creditCardForm ).remove();

				// Generate the hash.
				creditCard.generateHash( function ( cardHash ) {
					// Remove any old hash input.
					$( 'input[name=pagarme_card_hash]', form ).remove();

					// Add the hash input.
					form.append( $( '<input name="pagarme_card_hash" type="hidden" />' ).val( cardHash ) );

					// Submit the form.
					form.submit();
				});
			}

			return false;
		});
	});

}( jQuery ));
