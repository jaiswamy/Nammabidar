<?php
/**
 * Checkout login form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-login.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.8.0
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() || 'no' === get_option( 'woocommerce_enable_checkout_login_reminder' ) ) {
	return;
}

?>
<div class="woocommerce-login-fields">

	<?php
	$info_message  = apply_filters( 'woocommerce_checkout_login_message', __( 'Returning customer?', 'woocommerce' ) );

	//if we are using Login with Ajax then we want to use the uber modal to login or register
	if( listable_using_lwa() ) {
		$login_url = listable_get_login_url();
		$classes = listable_get_login_link_class();

		$info_message = '<div class="woocommerce-info lwa">' . $info_message;
		$info_message .= ' <a href="' . $login_url . '" class="' . $classes . '">' . __( 'Click here to login', 'woocommerce' ) . '</a>';
		$info_message .= '</div>';

		echo $info_message;
	} else {
		$info_message .= ' <a href="#" class="showlogin">' . __( 'Click here to login', 'listable' ) . '</a>';
		wc_print_notice( $info_message, 'notice' );

		$defaults = array(
			'message'  => __( 'If you have shopped with us before, please enter your details in the boxes below. If you are a new customer please proceed to the Billing &amp; Shipping section.', 'listable' ),
			'redirect' => wc_get_page_permalink( 'checkout' ),
			'hidden'   => true
		);

		$args = wp_parse_args( $args, $defaults );

		wc_get_template( 'custom/form-login.php', $args );
	} ?>

</div>
