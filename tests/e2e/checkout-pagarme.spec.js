context('Checkout Pagarme', () => {
  describe('when create a purchase with checkout pagar.me as payment method', () => {
    before(() => {
      cy.configureCreditCard({ checkout: true, register_refused_order: false })
      cy.addProductToCart()
      cy.goToCheckoutPage()
      cy.fillCheckoutForm()
      cy.selectCreditCard()
      cy.placeOrder()

      cy.fillPagarMeCheckoutCreditCardForm()
    })

    it('should be at order received page', () => {
      cy.url({ timeout: 60000 }).should('include', '/finalizar-compra/order-received/')
      cy.contains('Pedido recebido')
    })

    it('should contains payment informations', () => {
      cy.contains('Pagamento realizado utilizando cartão de crédito Visa em 1x.')
    })
  })

  describe('when make a purchase with refused orders register enabled', () => {
    let orderId
    let postback

    before(() => {
      cy.configureCreditCard({ checkout: true, register_refused_order: true })
      cy.addProductToCart()
      cy.goToCheckoutPage()
      cy.fillCheckoutForm()
      cy.selectCreditCard()
      cy.placeOrder()

      cy.fillPagarMeCheckoutCreditCardForm({card_cvv: '666'})
    })

    it('should be at order received page', () => {
      cy.url({ timeout: 60000 })
        .should('include', '/finalizar-compra/order-received/')
      cy.contains('Pedido recebido')
    })

    it('should contains payment informations', () => {
      cy.contains('Pagamento realizado utilizando cartão de crédito Visa em 1x.')
    })

    it('should be registered at "my orders" page', () => {
      cy.get('.woocommerce-order-overview__order strong').then($order => {
        orderId = $order.text()
        cy.visit('/minha-conta/orders/')

        cy.get('tbody', { timeout: 60000 })
          .contains(`#${orderId}`)
      })
    })

    it('should validate the current status of the order', () => {
      cy.visit(`minha-conta/view-order/${orderId}/`)
      cy.contains(`Pedido #${orderId}`)
      cy.contains('atualmente está Aguardando.')
    })

    it('should contain at least one postback', () => {
      const opts = {
        metadata: { order_number: orderId }
      }

      cy.log('Wait process transaction in Pagar.me')
      cy.wait(5000)

      cy.task('pagarmejs:transaction', opts)
        .then((transaction) =>
          cy.task('pagarmejs:postback', transaction.id)
            .then((postbacks) => {
              expect(postbacks).to.not.be.empty
              postback = postbacks[0]
            })
        )
    })
  })
})
