<?php
/**
 * Handle the plugin's behavior when Backstage is present.
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( function_exists( 'backstage_is_customizer_user' ) ) {
	function pixcare_disable_support_ticket_submission_for_backstage_user( $pixcare_config ) {
		// We will only do this in the Customizer and when the Backstage user is logged in.
		if ( ! is_customize_preview() || ! backstage_is_customizer_user() ) {
			return $pixcare_config;
		}

		if ( empty( $pixcare_config['knowledgeBase'] ) ) {
			$pixcare_config['knowledgeBase'] = [];
		}

		if ( empty( $pixcare_config['knowledgeBase']['openTicket'] ) ) {
			$pixcare_config['knowledgeBase']['openTicket'] = [];
		}

		// Put in the marker.
		$pixcare_config['knowledgeBase']['openTicket']['disableTicketSubmission'] = true;

		// Change the text next to the submit button.
		if ( ! empty( $pixcare_config['knowledgeBase']['openTicket']['blocks']['sticky']['fields']['noSuccess']['value'] ) ) {
			$pixcare_config['knowledgeBase']['openTicket']['blocks']['sticky']['fields']['noSuccess']['value'] = esc_html__( 'Ticket submission is disabled in this demo.', 'pixelgrade_care' );
		}

		return $pixcare_config;
	}
	add_filter( 'pixcare_config', 'pixcare_disable_support_ticket_submission_for_backstage_user', 10, 1 );
}
