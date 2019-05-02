context('Postback', () => {
  describe('Update purchase with postback', () => {
    let postback;

    it('Get postbacks', () => {
      cy.task('pagarmejs:postback')
        .then(postbacks => {
          expect(postbacks).to.not.be.empty
          postback = postbacks[0]
        })
    })

    it('Postback is valid', () => {
      expect(postback).to.have
        .property('request_url', 'http://woopagarme/wc-api/WC_Pagarme_Credit_Card_Gateway/')
    })
  })
})