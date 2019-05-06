context('Postback last transaction', () => {
  describe('when you update a purchase with postback', () => {
    let postback

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
      cy.task('pagarmejs:postback')
        .then(postbacks => {
          expect(postbacks).to.not.be.empty
          postback = postbacks[0]
        })
    })

    it('Postback URL should equals test domain', () => {
      expect(postback).to.have
        .property('request_url', 'http://woopagarme/wc-api/WC_Pagarme_Credit_Card_Gateway/')
    })

    it('should update order transaction via postback', () => {
      cy.request({
          method: 'POST',
          url: 'wc-api/WC_Pagarme_Credit_Card_Gateway/',
          headers: JSON.parse(postback.headers),
          body: postback.payload
        })
        .then((response) => {
          expect(response.status).to.eq(200)
        })
    })
  })
})
