import checkoutData from '../fixtures/data'

Cypress.Commands.add('addProductToCart', () => {
  cy.log('Adding product to cart...')

  cy.visit('/product/hoodie-with-zipper/')
  cy.contains('Comprar').click()

  cy.log('Product added successfully.')
})

Cypress.Commands.add('goToCheckoutPage', () => {
  cy.log('Going to checkout page...')

  cy.visit('/finalizar-compra/')

  cy.log('In checkout page.')
})

Cypress.Commands.add('fillCheckoutForm', () => {
  cy.log('Filling customer data...')

  cy.get('#billing_first_name')
    .clear()
    .type(checkoutData.customer.name)

  cy.get('#billing_last_name')
    .clear()
    .type(checkoutData.customer.lastname)

  cy.get('#billing_persontype')
    .select('Pessoa Física', {
      force: true
    })

  cy.get('#billing_cpf')
    .clear()
    .type(checkoutData.customer.documents[0].number)

  cy.get('#billing_state')
    .select(checkoutData.address.state.toUpperCase(), {
      force: true
    })

  cy.get('#billing_postcode')
    .clear()
    .type(checkoutData.address.zipcode)

  cy.get('#billing_address_1')
    .clear()
    .type(checkoutData.address.street)

  cy.get('#billing_number')
    .clear()
    .type(checkoutData.address.street_number)

  cy.get('#billing_neighborhood')
    .clear()
    .type(checkoutData.address.neighborhood)

  cy.get('#billing_city')
    .clear()
    .type(checkoutData.address.city)

  cy.get('#billing_country')
    .select(checkoutData.address.country.toUpperCase(), {
      force: true
    })

  cy.get('#billing_phone')
    .clear()
    .type(checkoutData.customer.phone_numbers[0])

  cy.get('#billing_cellphone')
    .clear()
    .type(checkoutData.customer.phone_numbers[1])

  cy.get('#billing_email')
    .clear()
    .type(checkoutData.customer.email)

  cy.log('Form filled successfully.')
})

Cypress.Commands.add('pagarmeCheckoutCreditCardForm', (iframeSelector, elSelector) => {
  return cy
    .get(`iframe${iframeSelector || ''}`, { timeout: 60000 })
    .then($iframe => {
      return cy.wrap($iframe.contents().find('#pagarme-modal-box-step-credit-card-information'))
    })
})

Cypress.Commands.add('selectCreditCard', () => {
  cy.log('Select payment method credt card')

  cy.get('#payment_method_pagarme-credit-card')
    .next()
    .contains('Cartão de crédito')
    .click()
})

Cypress.Commands.add('selectBankingTicket', () => {
  cy.log('Select payment method banking ticket')

  cy.get('#payment_method_pagarme-banking-ticket')
    .next()
    .contains('Boleto bancário')
    .click()
})

Cypress.Commands.add('fillPagarMeCheckoutCreditCardForm', (cardData) => {
  cy.wait(2000)
  Object.assign(checkoutData, cardData)

  cy.pagarmeCheckoutCreditCardForm().as('pagarmeModal')

  cy.get('@pagarmeModal')
    .find('#pagarme-modal-box-credit-card-number')
    .type(checkoutData.card_number)

  cy.get('@pagarmeModal')
    .find('#pagarme-modal-box-credit-card-name')
    .type(checkoutData.card_holder_name)

  cy.get('@pagarmeModal')
    .find('#pagarme-modal-box-credit-card-expiration')
    .type(checkoutData.card_expiration_date)

  cy.get('@pagarmeModal')
    .find('#pagarme-modal-box-credit-card-cvv')
    .type(checkoutData.card_cvv)

  cy.get('@pagarmeModal')
    .find('#pagarme-modal-box-installments')
    .select(checkoutData.card_installments.toString(), { force: true })

  cy.get('@pagarmeModal')
    .find('button.pagarme-modal-box-next-step')
    .click()
})

Cypress.Commands.add('placeOrder', () => {
  cy.get('#place_order')
    .contains('Finalizar compra')
    .click()
})

Cypress.Commands.add('loginAsAdmin', () => {
  cy.visit('/wp-login.php')

  cy.get('#user_login')
    .clear()
    .type('pagarme')
  cy.get('#user_pass').type('wordpress')

  cy.get('#wp-submit').click()
})

/**
 * @param {Object} options
 * @param {boolean} options.enabled
 * @param {string} options.title
 * @param {string} options.description
 * @param {string} options.api_key
 * @param {string} options.encryption_key
 * @param {boolean} options.checkout
 * @param {boolean} options.register_refused_order
 * @param {number} options.max_installments
 * @param {number} options.min_installments_value
 * @param {float} options.interest_rate
 * @param {number} options.free_installments
 */
Cypress.Commands.add('configureCreditCard', (options) => {
  cy.visit('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_pagarme_credit_card_gateway')
  cy.log('Update settings Pagar.me')

  if (!!options.enabled) {
    cy.get('#woocommerce_pagarme-credit-card_enabled')
      .check()
  }

  if (options.enabled === false) {
    cy.get('#woocommerce_pagarme-credit-card_enabled')
      .uncheck()
  }

  if (!!options.title) {
    cy.get('#woocommerce_pagarme-credit-card_title')
      .clear()
      .type(options.title)
  }

  if (!!options.description) {
    cy.get('#woocommerce_pagarme-credit-card_description')
      .clear()
      .type(options.description)
  }

  if (!!options.api_key) {
    cy.get('#woocommerce_pagarme-credit-card_api_key')
      .clear()
      .type(options.api_key)
  }

  if (!!options.encryption_key) {
    cy.get('#woocommerce_pagarme-credit-card_encryption_key')
      .clear()
      .type(options.encryption_key)
  }

  if (!!options.checkout) {
    cy.get('#woocommerce_pagarme-credit-card_checkout')
      .check()
  }

  if (options.checkout === false) {
    cy.get('#woocommerce_pagarme-credit-card_checkout')
      .uncheck()
  }

  if (!!options.register_refused_order) {
    cy.get('#woocommerce_pagarme-credit-card_register_refused_order')
      .check()
  }

  if (options.register_refused_order === false) {
    cy.get('#woocommerce_pagarme-credit-card_register_refused_order')
      .uncheck()
  }

  if (!!options.max_installments) {
    cy.get('#woocommerce_pagarme-credit-card_max_installment')
      .select(number.toString)
  }

  if (!!options.min_installments_value) {
    cy.get('#woocommerce_pagarme-credit-card_smallest_installment')
      .clear()
      .type(options.min_installments_value.toString())
  }

  if (!!options.interest_rate) {
    cy.get('#woocommerce_pagarme-credit-card_interest_rate')
      .clear()
      .type(options.interest_rate.toString())
  }

  if (!!options.free_installments) {
    cy.get(woocommerce_pagarme-credit-card_free_installments)
      .select(options.free_installments.toString())
  }

  cy.get('.woocommerce-save-button')
    .contains('Salvar alterações')
    .click()
})

Cypress.Commands.add('fillAvoidingException', (element, value) => {
  cy.on('uncaught:exception', () => {
      return false
    })
    .get(element)
    .type(value)
})

Cypress.Commands.add('fillCreditCardForm', () => {
  cy.log('Filling credit card data...')

  cy.fillAvoidingException(
    '#pagarme-card-holder-name',
    checkoutData.card_holder_name
  )

  cy.get('#pagarme-card-number')
    .type(checkoutData.card_number)

  cy.get('#pagarme-card-expiry')
    .type(checkoutData.card_expiration_date)

  cy.get('#pagarme-card-cvc')
	  .type(checkoutData.card_cvv)

	cy.get('#pagarme-installments')
	  .select(checkoutData.card_installments.toString())
})

Cypress.Commands.add('getPayloadData', (payload) => {
  const items = {}
  payload.split('&').forEach(element => {
    const [prop = '', value = ''] = element.split('=')
    items[decodeURIComponent(prop)] = decodeURIComponent(value)
  })

  return items
})

Cypress.Commands.add('updateOrderViaPostback', (postback) =>
  cy.request({
    method: 'POST',
    url: 'wc-api/WC_Pagarme_Credit_Card_Gateway/',
    headers: JSON.parse(postback.headers),
    body: postback.payload
  })
)

Cypress.Commands.add('refundOrder', (id, amount = 0) => {
  cy.log('Refunding order')
  cy.visit(`/wp-admin/post.php?post=${id}&action=edit`)

  cy.log('Do refund')
  cy.log('Select refund option')
  cy.get('.refund-items')
    .contains('Reembolso')
    .click()

  cy.log('Type refund amount')
  cy.get('#refund_amount')
    .clear()
    .type(amount)


  cy.on('window:confirm', () => true)

  cy.get('.do-api-refund')
    .contains('Pagar.me - Cartão de crédito')
    .click()

  cy.wait(7000)
})

Cypress.Commands.add('goToPluginsPage', () => {
  cy.log('Going to plugins page...')

  cy.visit('/wp-admin/plugins.php')

  cy.log('In plugins page.')
})

Cypress.Commands.add('deactivateBrazilianMarketPlugin', () => {
  cy.log('Deactivating the Brazilian Market plugin...')

  cy.get('#deactivate-woocommerce-extra-checkout-fields-for-brazil')
    .click()
})

Cypress.Commands.add('activateBrazilianMarketPlugin', () => {
  cy.log('Activating the Brazilian Market plugin...')

  cy.get('#activate-woocommerce-extra-checkout-fields-for-brazil')
    .click()
})

Cypress.Commands.add('deactivateWoocommercePlugin', () => {
  cy.log('Deactivating the WooCommerce plugin...')

  cy.get('#deactivate-woocommerce')
    .click()
})

Cypress.Commands.add('activateWoocommercePlugin', () => {
  cy.log('Activating the WooCommerce plugin...')

  cy.get('#activate-woocommerce')
    .click()
})

Cypress.Commands.add('deactivateWoocommercePagarmePlugin', () => {
  cy.log('Deactivating the WooCommerce Pagarme plugin...')

  cy.get('#deactivate-woocommerce-pagarme')
    .click()
})

Cypress.Commands.add('activateWoocommercePagarmePlugin', () => {
  cy.log('Activating the WooCommerce Pagarme plugin...')

  cy.get('#activate-woocommerce-pagarme')
    .click()
})
