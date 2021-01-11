context('Wordpress admin notices', () => {
  describe('when go to the wordpress plugins page', () => {
    describe('and deactivate the woocommerce plugin', () => {
      before(() => {
        cy.goToPluginsPage()
        cy.deactivateWoocommercePlugin()
      })
  
      it('should contain error message', () => {
        cy.get('.error a.button')
          .contains('WooCommerce')
          .should('have.attr', 'href')
          .and('match', /action\=activate\&plugin\=woocommerce/)
      })

      describe('and when reactivate the woocommerce plugin', () => {
        before(() => {
          cy.goToPluginsPage()
          cy.activateWoocommercePlugin()
        })

        it('should not contain the message anymore', () => {
          cy.get('.error a[href*="action=activate&plugin=woocommerce"]')
          .should('not.exist')
        })
      })
    })

    describe('and deactivate the woocommerce-pagarme plugin', () => {
      before(() => {
        cy.goToPluginsPage()
        cy.deactivateWoocommercePagarmePlugin()
      })

      describe('and reactivate the woocommerce-pagarme plugin', () => {
        before(() => {
          cy.goToPluginsPage()
          cy.activateWoocommercePagarmePlugin()
        })
        
        it('should contain a message with the Pagar.me documentation link button', () => {
          cy.get('.updated a.button-primary')              
            .should('have.attr', 'href')
            .and('match', /docs.pagar.me/)
        })
      })

      describe('and when click to close the message', () => {
        before(() => {
          cy.goToPluginsPage()
          cy.get('.is-dismissible a[href*="woocommerce-pagarme-hide-notice=documentation_link"]')
            .click()
        })

        it('should not contain the message anymore', () => {
          cy.get('.updated a[href*="docs.pagar.me"]')
          .should('not.exist')
        })
      })
    })

    describe('and deactivate the brazilian market plugin', () => {
      before(() => {
        cy.goToPluginsPage()
        cy.deactivateBrazilianMarketPlugin()
      })
  
      describe('and deactivate the woocommerce-pagarme plugin', () => {
        before(() => {
          cy.goToPluginsPage()
          cy.deactivateWoocommercePagarmePlugin()
        })
  
        describe('and reactivate the woocommerce-pagarme plugin', () => {
          before(() => {
            cy.goToPluginsPage()
            cy.activateWoocommercePagarmePlugin()
          })
  
          it('should contain a message recommending the Brazilian Market plugin activation', () => {
            cy.get('.updated a.button-primary')              
              .should('have.attr', 'href')
              .and('match', /action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/)
          })
        })

        describe('and when click to close the message', () => {
          before(() => {
            cy.goToPluginsPage()
            cy.get('.is-dismissible a[href*="woocommerce-pagarme-hide-notice=missing_brazilian_market"]')
              .click()
          })
  
          it('should not contain the message anymore', () => {
            cy.get('.updated a[href*="action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil"]')
            .should('not.exist')
          })
        })
      })

      describe('and deactivate the woocommerce-pagarme plugin', () => {
        before(() => {
          cy.goToPluginsPage()
          cy.deactivateWoocommercePagarmePlugin()
        })
  
        describe('and reactivate the woocommerce-pagarme plugin', () => {
          before(() => {
            cy.goToPluginsPage()
            cy.activateWoocommercePagarmePlugin()
          })
  
          it('should contain again a message recommending the Brazilian Market plugin activation', () => {
            cy.get('.updated a.button-primary')              
              .should('have.attr', 'href')
              .and('match', /action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil/)
          })
        })

        describe('and when reactivate the brazilian market plugin', () => {
          before(() => {
            cy.goToPluginsPage()
            cy.activateBrazilianMarketPlugin()
          })
  
          it('should not contain the message anymore', () => {
            cy.get('.updated a[href*="action=activate&plugin=woocommerce-extra-checkout-fields-for-brazil"]')
            .should('not.exist')
          })
        })

        after(() => {
          cy.goToPluginsPage()
          cy.get('.is-dismissible a[href*="woocommerce-pagarme-hide-notice=documentation_link"]')
            .click()
        })
      })
    })
  })
})
  