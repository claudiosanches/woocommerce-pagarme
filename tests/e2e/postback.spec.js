context('Postback last transaction', () => {
  describe('when you update a purchase with postback', () => {
    let postback
    let orderId

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
      cy.url({ timeout: 60000 })
        .should('include', '/finalizar-compra/order-received/')
      cy.contains('Pedido recebido')
    })

    it('should contain at least one postback', () => {
      cy.log('Wait process transaction on Pagar.me')
      cy.wait(5000)
      
      cy.task('pagarmejs:lastPostback')
        .then(postbacks => {
          expect(postbacks).to.not.be.empty
          postback = postbacks[0]

          cy.getPayloadData(postback.payload)
            .then((payload) => {
              orderId = payload['transaction[metadata][order_number]']
            })
        })
    })

    it('Postback URL should equals test domain', () => {
      expect(postback).to.have
        .property('request_url', 'http://woopagarme/wc-api/WC_Pagarme_Credit_Card_Gateway/')
    })

    it('should validate the current status of the order', () => {
      cy.visit(`minha-conta/view-order/${orderId}/`)
        .contains('atualmente está Aguardando.')
    })

    it('should update order transaction via postback', () => {
      cy.updateOrderViaPostback(postback)
        .then((response) => {
          expect(response.status).to.eq(200)
        })
    })

    it('should validate the new status of the order', () => {
      const status = {
        paid: 'Processando',
        refused: 'Malsucedido'
      }

      cy.getPayloadData(postback.payload)
        .then((payload) => {
          cy.visit(`minha-conta/view-order/${orderId}/`)
          cy.contains('atualmente está ' + status[payload['current_status']])
        })
    })
  })
})
