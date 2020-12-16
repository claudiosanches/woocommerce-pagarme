context('WordPress Required Plugins', () => {
  describe('when go to the wordpress plugins page', () => {
    describe('and deactivate the brazilian market plugin', () => {
      before(() => {
        cy.goToPluginsPage()
        cy.deactivateBrazilianMarketPlugin()
      })
  
      it('should contains error message', () => {
        cy.get('.error a.button')
          .contains('Brazilian Market')
          .should('have.attr', 'href')
          .and('match', /plugins.php\?action\=activate\&plugin\=woocommerce-extra-checkout-fields-for-brazil/)
      })

      after(() => {
        cy.goToPluginsPage()
        cy.activateBrazilianMarketPlugin()
      })
    })

    describe('and deactivate the woocommerce plugin', () => {
      before(() => {
        cy.goToPluginsPage()
        cy.deactivateWoocommercePlugin()
      })
  
      it('should contains error message', () => {
        cy.get('.error a.button')
          .contains('WooCommerce')
          .should('have.attr', 'href')
          .and('match', /plugins.php\?action\=activate\&plugin\=woocommerce/)
      })

      after(() => {
        cy.goToPluginsPage()
        cy.activateWoocommercePlugin()
      })
    })
  })
})
  