<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<fieldset id="<?php echo $this->id; ?>-payment-form">
	<?php if ( 'all' == $this->methods ) : ?>
		<?php if ( $is_subscription ) : ?>
			<p class="form-row form-row-wide"><?php _e( 'Choose the payment method that will be used in the subscriotion collection', 'woocommerce-pagarme' ); ?>:</p>
		<?php endif; ?>
		<p class="form-row form-row-wide">
			<label><input id="pagarme-payment-method-credit-cart" type="radio" name="pagarme_payment_method" value="credit-card" checked="checked" /> <?php _e( 'Credit Card', 'woocommerce-pagarme' ); ?></label>
			<label><input id="pagarme-payment-method-banking-ticket" type="radio" name="pagarme_payment_method" value="banking-ticket" /> <?php _e( 'Banking Ticket', 'woocommerce-pagarme' ); ?></label>
		</p>
	<?php else : ?>
		<input id="pagarme-payment-method-credit-cart" type="hidden" name="pagarme_payment_method" value="credit-card" />
	<?php endif; ?>

	<div id="pagarme-credit-cart-form">
		<p class="form-row form-row-first">
			<label for="pagarme-card-number"><?php _e( 'Card Number', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<input id="pagarme-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="&bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull;" style="font-size: 1.5em; padding: 8px;" />
		</p>
		<p class="form-row form-row-last">
			<label for="pagarme-card-holder-name"><?php _e( 'Card Holder Name', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<input id="pagarme-card-holder-name" class="input-text" type="text" autocomplete="off" style="font-size: 1.5em; padding: 8px;" />
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first">
			<label for="pagarme-card-expiry"><?php _e( 'Expiry (MM/YY)', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<input id="pagarme-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="<?php _e( 'MM / YY', 'woocommerce-pagarme' ); ?>" style="font-size: 1.5em; padding: 8px;" />
		</p>
		<p class="form-row form-row-last">
			<label for="pagarme-card-cvc"><?php _e( 'Card Code', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<input id="pagarme-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="<?php _e( 'CVC', 'woocommerce-pagarme' ); ?>" style="font-size: 1.5em; padding: 8px;" />
		</p>
		<div class="clear"></div>
		<?php if ( 1 < $this->max_installment && ! $is_subscription ) : ?>
			<p class="form-row form-row-wide">
				<label for="pagarme-card-installments"><?php _e( 'Installments', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
				<select name="pagarme_installments" id="pagarme-installments" style="font-size: 1.5em; padding: 8px; width: 100%;">
					<?php
						$smallest_installment = $this->api->get_smallest_installment();

						foreach ( $installments as $number => $installment ) :
							if ( $smallest_installment > $installment['installment_amount'] ) {
								break;
							}

							$interest           = ( ( $cart_total * 100 ) < $installment['amount'] ) ? sprintf( __( '(total of %s)', 'woocommerce-pagarme' ), strip_tags( wc_price( $installment['amount'] / 100 ) ) ) : __( '(interest-free)', 'woocommerce-pagarme' );
							$installment_amount = strip_tags( wc_price( $installment['installment_amount'] / 100 ) );
							$installment_number = absint( $installment['installment'] );
						?>
						<option value="<?php echo $installment_number; ?>"><?php echo sprintf( __( '%dx of %s %s', 'woocommerce-pagarme' ), $installment_number, $installment_amount, $interest ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>
		<?php endif; ?>
	</div>
</fieldset>
