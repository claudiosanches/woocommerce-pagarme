/* global wcPagarmeParams, PagarMeCheckout */
(function( $ ) {
	'use strict';

	$( function() {

		var formSubmit = false;

		$( 'form.checkout' ).on( 'click', '#place_order', function() {
			return openCheckout();
		} );

		$( 'form.checkout' ).on( 'checkout_place_order_pagarme-credit-card', {
			preservePagarmeCheckoutSubmitValue: true
		}, isCheckoutInvalid );

		$( 'form#order_review' ).submit( function() {
			return openCheckout();
		} );

		/**
		 * Validate person type fields.
		 * Used only when the person type select field is available.
		 *
		 * @return {Bool}
		 */
		function validatePersontype() {
			var requiredError = false;

			if ( '1' === $( '#billing_persontype' ).val() ) {
				$( '#billing_cpf, #billing_rg' ).each( function() {
					if ( '' === $( this ).val() ) {
						requiredError = true;
					}
				});
			} else if ( '2' === $( '#billing_persontype' ).val() ) {
				$( '#billing_cnpj, #billing_company, #billing_ie' ).each( function() {
					if ( '' === $( this ).val() ) {
						requiredError = true;
					}
				});
			}

			return requiredError;
		}

		/**
		 * Check if checkout is valid.
		 *
		 * @param {Object} evt
		 *
		 * @return {Bool}
		 */
		function isCheckoutInvalid( evt ) {
			var requiredInputs = null;

			// If this submit is a result of the request callback firing,
			// let submit proceed by returning true immediately.
			if ( formSubmit ) {
				if ( 'undefined' !== typeof evt && 'undefined' !== typeof evt.data ) {
					if ( 'undefined' !== typeof evt.data.preservePagarmeCheckoutSubmitValue && ! evt.data.preservePagarmeCheckoutSubmitValue ) {
						formSubmit = false;
					}
				}
				return true;
			}

			if ( ! $( '#payment_method_pagarme-credit-card' ).is( ':checked' ) ) {
				return true;
			}

			if ( 0 < $( 'input[name=pagarme_checkout_token]' ).length ) {
				return true;
			}

			if ( 1 === $( 'input#terms' ).size() && 0 === $( 'input#terms:checked' ).size() ) {
				return true;
			}

			if ( $( '#createaccount' ).is( ':checked' ) && $( '#account_password' ).length && '' === $( '#account_password' ).val() ) {
				return true;
			}

			// Check to see if we need to validate shipping address.
			if ( $( '#ship-to-different-address-checkbox' ).is( ':checked' ) ) {
				requiredInputs = $( '.woocommerce-billing-fields .validate-required, .woocommerce-shipping-fields .validate-required' );
			} else {
				requiredInputs = $( '.woocommerce-billing-fields .validate-required' );
			}

			if ( requiredInputs.size() ) {
				var requiredError = false;

				// Check if person type select field is available.
				if ( 0 < $( '#billing_persontype' ).length ) {
					requiredInputs.each( function() {
						if ( '' === $( this ).find( 'input.input-text, select' ).not( $( '#account_password, #account_username, #billing_cpf, #billing_rg, #billing_cnpj, #billing_company, #billing_ie' ) ).val() ) {
							requiredError = true;
						}
					});

					if ( ! requiredError ) {
						requiredError = validatePersontype();
					}
				} else {
					requiredInputs.each( function() {
						if ( '' === $( this ).find( 'input.input-text, select' ).not( $( '#account_password, #account_username' ) ).val() ) {
							requiredError = true;
						}
					});
				}

				if ( requiredError ) {
					return true;
				}
			}

			return false;
		}

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
		 * Get transaction data
		 *
		 * @param {Object} form [description]
		 * @return {Object}
		 */
		function getTransactionData( form ) {
			var data = {};

			data.customer = {
				external_id: $( '#billing_email', form ).val(),
				name: $.trim( $( '#billing_first_name', form ).val() + ' ' + $( '#billing_last_name', form ).val() ),
				email: $( '#billing_email', form ).val(),
				type: getDocumentType() === 'cpf' ? 'individual' : 'corporation',
				documents: getDocuments(),
				phone_numbers: getPhoneNumbers(),
				country: $( '#billing_country' ).val().toLowerCase(),
			}

			if ( getBillingData() ) {
				data.billing = getBillingData();
			}

			if ( getShippingData() ) {
				data.shipping = getShippingData();
			}

			return data;
		}

		/**
		 * Get phone numbers from checkout form.
		 *
		 * @return {Array}
		 */
		function getPhoneNumbers() {
			var phone_numbers = [];
			if ( isset( $( '#billing_phone' ) ) ) {
				var phone = '+55' + getNumbers( $( '#billing_phone' ).val() );
				phone_numbers.push(phone);
			}

			if ( isset( $( '#billing_cellphone' ) ) ) {
				var cellphone = '+55' + getNumbers( $( '#billing_cellphone' ).val() );
				phone_numbers.push(cellphone);
			}
			return phone_numbers;
		}

		/**
		 * Get document type.
		 *
		 * @return {String}
		 */
		function getDocumentType() {
			if ( isset ( $( '#billing_persontype' ) ) ) {
				if ( '1' === $( '#billing_persontype' ).val() ) {
					return 'cpf';
				} else {
					return 'cnpj';
				}
			}

			if ( $( '#billing_cpf' ) ) {
				return 'cpf';
			}
			if ( $( '#billing_cnpj' ) ) {
				return 'cnpj';
			}
		}

		/**
		 * Get documents data.
		 *
		 * @return {Array}
		 */
		function getDocuments() {
			var document = {};
			var type = getDocumentType();
			var field = '#billing_cpf';
			document.type = type; 
			if ( 'cnpj' === type ) {
				field = '#billing_cnpj'
			}
			document.number = getNumbers( $( field ).val() );

			return [document];
		}

		/**
		 * Get billing data from checkout form.
		 *
		 * @return {Object}
		 */
		function getBillingData() {
			if ( isset( $( '#billing_address_1' ) ) ) {
				var billing = {};
				billing.address = {
					street: $( '#billing_address_1' ).val(),
					street_number: $( '#billing_number' ).val(),
					neighborhood: $( '#billing_neighborhood' ).val(),
					city: $( '#billing_city' ).val(),
					state: $( '#billing_state').val(),
					zipcode: getNumbers( $( '#billing_postcode' ).val() ),
					country: $( '#billing_country' ).val().toLowerCase()
				}

				billing.name = $.trim( $( '#billing_first_name' ).val() + ' ' + $( '#billing_last_name' ).val() );

				return billing;
			}
			return false;
		}

		/**
		 * Get shipping data from checkout form.
		 *
		 * @return {Object}
		 */
		function getShippingData() {
			if ( isset( $( '#shipping_address_1' ) ) ) {
				var shipping = {};
				shipping.address = {
					street: $( '#shipping_address_1' ).val(),
					street_number: $( '#shipping_number' ).val(),
					neighborhood: $( '#shipping_neighborhood' ).val(),
					city: $( '#shipping_city' ).val(),
					state: $( '#shipping_state').val(),
					zipcode: getNumbers( $( '#shipping_postcode' ).val() ),
					country: $( '#shipping_country' ).val().toLowerCase()
				}

				if( isset( $( '#shipping_address_2' ) ) ) {
					shipping.address.complementary = $( '#shipping_address_2' ).val();
				}

				shipping.name = $.trim( $( '#shipping_first_name' ).val() + ' ' + $( '#shipping_last_name' ).val() );

				return shipping;
			}

			return false;
		}

		/**
		 * Open Checkout modal.
		 */
		function openCheckout() {
			// Check if checkout is invalid and allow to be submitted and validated.
			if ( isCheckoutInvalid() ) {
				return true;
			}

			var checkout, transaction, params,
				form        = $( 'form.checkout, form#order_review' ),
				inline_data = $( '#pagarme-checkout-params', form );

			// Create checkout.
			checkout = new PagarMeCheckout.Checkout({
				encryption_key: wcPagarmeParams.encryptionKey,
				success: function( data ) {
					formSubmit = true;

					// Remove any old token input.
					$( 'input[name=pagarme_checkout_token]', form ).remove();

					// Add the token input.
					form.append( $( '<input name="pagarme_checkout_token" type="hidden" />' ).val( data.token ) );

					// Submit the form.
					form.submit();
				}
			});

			if ( wcPagarmeParams.checkoutPayPage ) {
				transaction = wcPagarmeParams.transactionFields;
			} else {
				transaction = getTransactionData( form );
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
				uiColor:          wcPagarmeParams.uiColor,
			}, transaction );

			if( params.shipping && ! params.shipping.fee) {
				params.shipping.fee = inline_data.data( 'fee' );
			}

			if ( ! params.items ) {
				params.items = inline_data.data( 'items' );
			}
			// Open modal.
			checkout.open( params );

			return false;
		}
	});

}( jQuery ));
