/* global wcPagarmeParams, PagarMeCheckout */
(function( $ ) {
	'use strict';

	$( function() {

		/**
		 * Check if object exists.
		 *
		 * @param {Object} obj
		 * @return {Bool}
		 */
		function isset( obj ) {
			return 0 < obj.length;
		}

		/**
		 * Get only numbers.
		 *
		 * @param {String} str
		 * @return {String}
		 */
		function getNumbers( str ) {
			return str.replace( /[^\d]/g, '' );
		}

		/**
		 * Get customer fields from checkout form.
		 *
		 * @param {Object} form [description]
		 * @return {Object}
		 */
		function getCustomerFields( form ) {
			var phone,
				data = {};

			data.customerName  = $.trim( $( '#billing_first_name', form ).val() + ' ' + $( '#billing_last_name', form ).val() );
			data.customerEmail = $( '#billing_email', form ).val();

			// Address fields.
			if ( isset( $( '#billing_address_1' ) ) ) {
				data.customerAddressStreet        = $( '#billing_address_1' ).val();
				data.customerAddressComplementary = $( '#billing_address_2' ).val();
				data.customerAddressZipcode       = getNumbers( $( '#billing_postcode' ).val() );

				if ( isset( $( '#billing_number' ) ) ) {
					data.customerAddressStreetNumber = $( '#billing_number' ).val();
				}

				if ( isset( $( '#billing_neighborhood' ) ) ) {
					data.customerAddressNeighborhood = $( '#billing_neighborhood' ).val();
				}
			}

			// Phone fields.
			if ( isset( $( '#billing_phone' ) ) ) {
				phone = getNumbers( $( '#billing_phone' ).val() );

				data.customerPhoneDdd    = phone.substr( 0, 2 );
				data.customerPhoneNumber = phone.substr( 2 );
			}

			if ( isset( $( '#billing_persontype' ) ) ) {
				if ( '1' === $( '#billing_persontype' ).val() ) {
					data.customerDocumentNumber = getNumbers( $( '#billing_cpf' ).val() );
				} else {
					data.customerName           = $( '#billing_company' ).val();
					data.customerDocumentNumber = getNumbers( $( '#billing_cnpj' ).val() );
				}
			} else if ( isset( $( '#billing_cpf' ) ) ) {
				data.customerDocumentNumber = getNumbers( $( '#billing_cpf' ).val() );
			} else if ( isset( $( '#billing_cnpj' ) ) ) {
				data.customerName           = $( '#billing_company' ).val();
				data.customerDocumentNumber = getNumbers( $( '#billing_cnpj' ).val() );
			}

			return data;
		}

		/**
		 * Process the credit card data when submit the checkout form.
		 */
		$( 'body' ).on( 'click', '#place_order', function() {
			if ( ! $( '#payment_method_pagarme-credit-card' ).is( ':checked' ) ) {
				return true;
			}

			var checkout, customer, params,
				form        = $( 'form.checkout, form#order_review' ),
				inline_data = $( '#pagarme-checkout-params', form );

			// Create checkout.
			checkout = new PagarMeCheckout.Checkout({
				encryption_key: wcPagarmeParams.encryptionKey,
				success: function( data ) {
					// Remove any old token input.
					$( 'input[name=pagarme_checkout_token]', form ).remove();

					// Add the token input.
					form.append( $( '<input name="pagarme_checkout_token" type="hidden" />' ).val( data.token ) );

					// Submit the form.
					form.submit();
				}
			});

			if ( wcPagarmeParams.checkoutPayPage ) {
				customer = wcPagarmeParams.customerFields;
			} else {
				customer = getCustomerFields( form );
			}

			// Set params.
			params = $.extend({}, {
				paymentMethods:   'credit_card',
				customerData:     false,
				amount:           inline_data.data( 'total' ),
				createToken:      true,
				interestRate:     wcPagarmeParams.interestRate,
				maxInstallments:  inline_data.data( 'max_installment' ),
				freeInstallments: wcPagarmeParams.freeInstallments,
				postbackUrl:      wcPagarmeParams.postbackUrl,
				uiColor:          wcPagarmeParams.uiColor
			}, customer );

			// Open modal.
			checkout.open( params );

			return false;
		});
	});

}( jQuery ));
