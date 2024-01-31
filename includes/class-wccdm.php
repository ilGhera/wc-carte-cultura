<?php
/**
 * Class WCCDM
 *
 * @author ilGhera
 * @package wc-carta-del-merito/includes
 *
 * @since 0.9.0
 */

/**
 * WCCDM class
 *
 * @since 0.9.0
 */
class WCCDM {

	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		/* Filters */
		add_filter( 'woocommerce_payment_gateways', array( $this, 'wccdm_add_gateway_class' ) );

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
	public function wccdm_add_gateway_class( $methods ) {

		$sandbox = get_option( 'wccdm-sandbox' );

		if ( $sandbox || ( WCCDM_Admin::get_the_file( '.pem' ) && get_option( 'wccdm-cert-activation' ) ) ) {

			$methods[] = 'WCCDM_Gateway';

		}

		return $methods;

	}

}

new WCCDM();

