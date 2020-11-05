context('Woocommerce Checkout Page', () => {
  describe('when go to the woocommerce checkout page', () => {
    before(() => {
      cy.addProductToCart()
      cy.goToCheckoutPage()
    })
  
    describe('billing_neighborhood field', () => {
      it('should be required', () => {
        cy.get('#billing_neighborhood_field')
          .should('have.class', 'validate-required')
      })
    })
  })
})
