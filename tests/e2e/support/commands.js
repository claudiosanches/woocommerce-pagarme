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

Cypress.Commands.add('fillPagarMeCheckoutCreditCardForm', (installments) => {
  cy.wait(2000)

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
    .select(installments.toString(), { force: true })

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
  cy.visit('/wp-admin')
  cy.wait(1000)

  cy.get('#user_login').type('pagarme')
  cy.get('#user_pass').type('wordpress')
  cy.get('#wp-submit').click()
})

Cypress.Commands.add('enableCheckoutPagarme', () => {
  cy.visit('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_pagarme_credit_card_gateway')

  cy.get('#woocommerce_pagarme-credit-card_checkout')
    .check()

  cy.get('.woocommerce-save-button')
    .contains('Salvar alterações')
    .click()
})

Cypress.Commands.add('disableCheckoutPagarme', () => {
  cy.visit('/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_pagarme_credit_card_gateway')

  cy.get('#woocommerce_pagarme-credit-card_checkout')
    .uncheck()

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
})
