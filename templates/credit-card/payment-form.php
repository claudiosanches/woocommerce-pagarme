<?php
/**
 * Credit Card - Checkout form.
 *
 * @author  Pagar.me
 * @package WooCommerce_Pagarme/Templates
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<fieldset id="pagarme-credit-cart-form">
	<p class="form-row form-row-first">
		<label for="pagarme-card-holder-name"><?php esc_html_e( 'Card Holder Name', 'woocommerce-pagarme' ); ?><span class="required">*</span></label>
		<input id="pagarme-card-holder-name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<p class="form-row form-row-last">
		<label for="pagarme-card-number"><?php esc_html_e( 'Card Number', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
		<input id="pagarme-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<div class="clear"></div>
	<p class="form-row form-row-first">
		<label for="pagarme-card-expiry"><?php esc_html_e( 'Expiry (MM/YY)', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
		<input id="pagarme-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'MM / YY', 'woocommerce-pagarme' ); ?>" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<p class="form-row form-row-last">
		<label for="pagarme-card-cvc"><?php esc_html_e( 'Card Code', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
		<input id="pagarme-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="<?php esc_html_e( 'CVC', 'woocommerce-pagarme' ); ?>" style="font-size: 1.5em; padding: 8px;" />
	</p>
	<div class="clear"></div>
	<?php if ( apply_filters( 'wc_pagarme_allow_credit_card_installments', 1 < $max_installment ) ) : ?>
		<p class="form-row form-row-wide">
			<label for="pagarme-card-installments"><?php esc_html_e( 'Installments', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<select name="pagarme_installments" id="pagarme-installments" style="font-size: 1.5em; padding: 8px; width: 100%;">
				<?php
				foreach ( $installments as $number => $installment ) :
					if ( $smallest_installment > $installment['installment_amount'] ) {
						break;
					}

					$interest           = ( ( $cart_total * 100 ) < $installment['amount'] ) ? sprintf( __( '(total of %s)', 'woocommerce-pagarme' ), strip_tags( wc_price( $installment['amount'] / 100 ) ) ) : __( '(interest-free)', 'woocommerce-pagarme' );
					$installment_amount = strip_tags( wc_price( $installment['installment_amount'] / 100 ) );
					$installment_number = absint( $installment['installment'] );
				?>
				<option value="<?php echo esc_attr( $installment_number ); ?>"><?php printf( esc_html__( '%1$dx of %2$s %2$s', 'woocommerce-pagarme' ), $installment_number, $installment_amount, $interest ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
	<?php endif; ?>
</fieldset>
