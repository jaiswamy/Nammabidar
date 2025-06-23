<?php
/**
 * Custom partial template with the place order button and terms and services checkbox. You won't find this file in WooCommerce.
 * It's contents are taken from woocommerce/checkout/payment.php
 */
?>
<div class="form-row place-order">

    <noscript>
		<?php
		/* translators: $1 and $2 opening and closing emphasis tags respectively */
		printf( esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ), '<em>', '</em>' );
		?>
        <br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
    </noscript>

	<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

	<?php echo apply_filters( 'woocommerce_order_button_html', '<input type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '" />' ); ?>

	<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

	<?php wp_nonce_field( 'woocommerce-process_checkout', 'woocommerce-process-checkout-nonce' ); ?>

    <?php if ( apply_filters( 'woocommerce_checkout_show_terms', true ) && function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) ) {
	    do_action( 'woocommerce_checkout_before_terms_and_conditions' );

	    /**
	     * Terms and conditions hook used to inject content.
	     *
	     * @since 3.4.0.
	     * @hooked wc_checkout_privacy_policy_text() Shows custom privacy policy text. Priority 20.
	     * @hooked wc_terms_and_conditions_page_content() Shows t&c page content. Priority 30.
	     */
	    do_action( 'woocommerce_checkout_terms_and_conditions' );
	    ?>

	    <?php if ( wc_terms_and_conditions_checkbox_enabled() ) : ?>
            <p class="form-row terms validate-required">
                <label for="terms" class="checkbox"><?php wc_terms_and_conditions_checkbox_text(); ?></label>
                <input type="checkbox" class="input-checkbox"
                       name="terms" <?php checked( apply_filters( 'woocommerce_terms_is_checked_default', isset( $_POST['terms'] ) ), true ); ?>
                       id="terms"/>
                <input type="hidden" name="terms-field" value="1"/>
            </p>
	    <?php endif; ?>
	    <?php

	    do_action( 'woocommerce_checkout_after_terms_and_conditions' );
    } ?>

</div>