context('Boleto', () => {
  describe('when create a purchase with boleto as payment method', () => {
    before(() => {
      cy.addProductToCart()
      cy.goToCheckoutPage()
      cy.fillCheckoutForm()
      cy.selectBankingTicket()
      cy.placeOrder()
    })

    it('should be at order received page', () => {
      cy.url({ timeout: 60000 })
        .should('include', '/finalizar-compra/order-received/')

      cy.contains('Pedido recebido')
    })

    it('should countains boleto url', () => {
      cy.contains('Pagar boleto bancário')
        .and('have.attr', 'href', 'https://pagar.me') 
    })
  })
})
