import checkoutData from './fixtures/data'

context('Credit card', () => {
  describe('Basic purchase workflow', () => {
    before(() => {
      cy.configureCreditCard({ checkout: false })

      cy.addProductToCart()
      cy.goToCheckoutPage()
      cy.fillCheckoutForm()
      cy.selectCreditCard()
      cy.fillCreditCardForm()
      cy.placeOrder()
    })

    it('should be at order received page', () => {
      cy.url({ timeout: 60000 }).should('include', '/finalizar-compra/order-received/')
      cy.contains('Pedido recebido')
    })

    it('should countains success message', () => {
      cy.contains('Pagamento realizado utilizando cartão de crédito')
    })
  })
})
