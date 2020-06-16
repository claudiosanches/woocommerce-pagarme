const createCreditCardOrder = () => {
  cy.addProductToCart()
  cy.goToCheckoutPage()
  cy.fillCheckoutForm()
  cy.selectCreditCard()
  cy.fillCreditCardForm()
  cy.placeOrder()
}

context('Credit card', () => {
  before(() => {
    cy.configureCreditCard({ checkout: false })
  })

  describe('Basic purchase workflow', () => {
    before(() => {
      createCreditCardOrder()
    })

    it('should be at order received page', () => {
      cy.url({ timeout: 60000 }).should(
        'include',
        '/finalizar-compra/order-received/'
      )
      cy.contains('Pedido recebido')
    })

    it('should contains success message', () => {
      cy.contains('Pagamento realizado utilizando cartão de crédito')
    })
  })

  describe('Refund', () => {
    describe('when create "pending" a credit card order', () => {
      before('create a credit card order', () => {
        createCreditCardOrder()
      })

      it('should be at order received page', () => {
        cy.url({ timeout: 60000 }).should(
          'include',
          '/finalizar-compra/order-received/'
        )
        cy.contains('Pedido recebido')
      })

      it('should contains success message', () => {
        cy.contains('Pagamento realizado utilizando cartão de crédito')
      })

      describe('and refund the "pending" credit card order', () => {
        before(() => {
          cy.on('window:alert', (msg) => {
            expect(msg).to.be.equal('Um erro ocorreu ao tentar criar o reembolso utilizando a API do método de pagamento.')
          })

          cy.get('.woocommerce-order-overview__order strong')
            .then(($order) => $order.text())
            .then((id) => {
              cy.log(`OrderID: ${id}`)
              cy.refundOrder(id)
            })
        })

        it('should fail to refund', () => {
          cy.get('#select2-order_status-container')
            .contains('Aguardando')
        })
      })
    })

    describe('Partial', () => {
      before('create a credit card order', () => {
        createCreditCardOrder()
      })

      it('should be at order received page', () => {
        cy.url({ timeout: 60000 }).should(
          'include',
          '/finalizar-compra/order-received/'
        )
        cy.contains('Pedido recebido')
      })

      it('should contains success message', () => {
        cy.contains('Pagamento realizado utilizando cartão de crédito')
      })

      describe('and refund partially the credit card order', () => {
        before(() => {
          cy.get('.woocommerce-order-overview__order strong')
            .then(($order) => $order.text())
            .then(orderId => {
              const opts = {
                metadata: { order_number: orderId }
              }

              return cy.task('pagarmejs:transaction', opts)
                .then(transaction => cy.task('pagarmejs:postback', transaction.id))
                .then(postbacks => cy.updateOrderViaPostback(postbacks[0]))
                .then(() => cy.refundOrder(orderId, 1.00))
            })
        })

        it('should do partial refund', () => {
          cy.get('#select2-order_status-container')
            .contains('Processando')

          cy.contains('Reembolso #')
          cy.contains('por pagarme')

          cy.contains('-R$45.00')
        })
      })
    })

    describe('Total', () => {
      before('create a credit card order', () => {
        createCreditCardOrder()
      })

      it('should be at order received page', () => {
        cy.url({ timeout: 60000 }).should(
          'include',
          '/finalizar-compra/order-received/'
        )
        cy.contains('Pedido recebido')
      })

      it('should contains success message', () => {
        cy.contains('Pagamento realizado utilizando cartão de crédito')
      })

      describe('and refund the credit card order', () => {
        let orderId
        let orderTotal

        before(() => {
          cy.get('.woocommerce-order-overview__order strong')
            .then(($order) => $order.text())
            .then(id => {
              orderId = id
              const opts = {
                metadata: { order_number: orderId }
              }

              return cy.task('pagarmejs:transaction', opts)
                .then(transaction => cy.task('pagarmejs:postback', transaction.id))
                .then(postbacks => cy.updateOrderViaPostback(postbacks[0]))
              })
              .then(() =>
                cy.get('.woocommerce-order-overview__total strong span')
                  .then($total => {
                    cy.log('ORDER TOTAL:', $total.text())
                    orderTotal = $total.text().replace(/R\$/g, '')

                    return
                  })
              )
              .then(() => cy.refundOrder(orderId, orderTotal))
        })

        it('should do total refund', () => {
          cy.get('#select2-order_status-container')
            .contains('Reembolsado')

          cy.contains('Reembolso #')
          cy.contains('por pagarme')

          cy.contains('-R$45.00')
        })
      })
    })
  })
})
