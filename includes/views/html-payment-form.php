<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>

<fieldset id="<?php echo $this->id; ?>-payment-form">
	<p class="form-row form-row-wide">
		<label><input id="<?php echo esc_attr( $this->id ); ?>-payment-method-credit-cart" type="radio" name="<?php echo esc_attr( $this->id ); ?>_payment_method" value="credit-card" checked="checked" /> <?php _e( 'Credit Card', 'woocommerce-pagarme' ); ?></label>
		<label><input id="<?php echo esc_attr( $this->id ); ?>-payment-method-banking-ticket" type="radio" name="<?php echo esc_attr( $this->id ); ?>_payment_method" value="banking-ticket" /> <?php _e( 'Banking Ticket', 'woocommerce-pagarme' ); ?></label>
	</p>

	<div id="<?php echo esc_attr( $this->id ); ?>-credit-cart-form">
		<p class="form-row form-row-first">
			<label for="<?php echo esc_attr( $this->id ); ?>-card-holder-name"><?php _e( 'Card Holder Name', 'woocommerce-pagarme' ); ?> <small>(<?php _e( 'as recorded on the card', 'woocommerce-pagarme' ); ?>)</small> <span class="required">*</span></label>
			<input id="<?php echo esc_attr( $this->id ); ?>-card-holder-name" class="input-text" type="text" autocomplete="off" name="<?php echo esc_attr( $this->id ); ?>_card_holder_name" style="font-size: 1.5em; padding: 8px;" />
		</p>
		<p class="form-row form-row-last">
			<label for="<?php echo esc_attr( $this->id ); ?>-card-number"><?php _e( 'Card Number', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<input id="<?php echo esc_attr( $this->id ); ?>-card-number" class="input-text wc-credit-card-form-card-number" type="text" maxlength="20" autocomplete="off" placeholder="•••• •••• •••• ••••" name="<?php echo esc_attr( $this->id ); ?>_card_number" style="font-size: 1.5em; padding: 8px;" />
		</p>
		<div class="clear"></div>
		<p class="form-row form-row-first">
			<label for="<?php echo esc_attr( $this->id ); ?>-card-expiry"><?php _e( 'Expiry (MM/YY)', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<input id="<?php echo esc_attr( $this->id ); ?>-card-expiry" class="input-text wc-credit-card-form-card-expiry" type="text" autocomplete="off" placeholder="<?php _e( 'MM / YY', 'woocommerce-pagarme' ); ?>" name="<?php echo esc_attr( $this->id ); ?>_card_expiry" style="font-size: 1.5em; padding: 8px;" />
		</p>
		<p class="form-row form-row-last">
			<label for="<?php echo esc_attr( $this->id ); ?>-card-cvc"><?php _e( 'Card Code', 'woocommerce-pagarme' ); ?> <span class="required">*</span></label>
			<input id="<?php echo esc_attr( $this->id ); ?>-card-cvc" class="input-text wc-credit-card-form-card-cvc" type="text" autocomplete="off" placeholder="<?php _e( 'CVC', 'woocommerce-pagarme' ); ?>" name="<?php echo esc_attr( $this->id ); ?>_card_cvc" style="font-size: 1.5em; padding: 8px;" />
		</p>
	</div>
</fieldset>
