<?php
/**
 * Class WCCC
 *
 * @author ilGhera
 * @package wc-carte-cultura/includes
 *
 * @since 0.9.0
 */

/**
 * WCCC class
 *
 * @since 0.9.0
 */
class WCCC {

	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		/* Filters */
		add_filter( 'woocommerce_payment_gateways', array( $this, 'wccc_add_gateway_class' ) );

	}


	/**
	 * Restituisce i dati della sessione WC corrente
	 *
	 * @return array
	 */
	public function get_session_data() {

		$session = WC()->session;

		if ( $session ) {

			return $session->get_session_data();

		}

	}


	/**
	 * Se presente un certificato, aggiunge il nuovo gateway a quelli disponibili in WooCommerce
	 *
	 * @param array $methods gateways disponibili.
	 *
	 * @return array
	 */
	public function wccc_add_gateway_class( $methods ) {

		$sandbox = get_option( 'wccc-sandbox' );

		if ( $sandbox || ( WCCC_Admin::get_the_file( '.pem' ) && get_option( 'wccc-cert-activation' ) ) ) {

			$methods[] = 'WCCC_Gateway';

		}

		return $methods;

	}

}

new WCCC();

