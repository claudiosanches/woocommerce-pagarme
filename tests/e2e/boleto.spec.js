context('Boleto', () => {
  describe('Basic purchase workflow', () => {
    before(() => {
      cy.addToCart()
      cy.goToCheckoutPage()
      cy.fillCheckoutForm()
    })

    it('Order received', () => {
      cy
        .get('#payment_method_pagarme-banking-ticket')
        .next()
        .contains('Boleto bancário')
        .click()

      cy.get('form.woocommerce-checkout').submit()
    })

    it('should be at order received page', () => {
      cy.url({ timeout: 60000 }).should('include', '/finalizar-compra/order-received/')
      cy.contains('Pedido recebido')
    })

    it('should countains boleto url', () => {
      cy
        .contains('Pagar boleto bancário')
        .and('have.attr', 'href', 'https://pagar.me' )
    })
  })
})
