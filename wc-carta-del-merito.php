<?php
/**
 * Plugin name: WooCommerce Carta del Merito 
 * Plugin URI: https://www.ilghera.com/product/wc-carta-del-merito/
 * Description: Abilita in WooCommerce il pagamento con Carta del Merito prevista dallo stato Italiano.
 * Author: ilGhera
 *
 * @package wc-carta-del-merito
 * Version: 0.9.0
 * Author URI: https://ilghera.com
 * Requires at least: 4.0
 * Tested up to: 6.4
 * WC tested up to: 8
 * Text Domain: wccdm
 * Domain Path: /languages
 */

/**
 * Attivazione
 */
function wccdm_activation() {

	/*Is WooCommerce activated?*/
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	/*Definizione costanti*/
	define( 'WCCDM_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WCCDM_URI', plugin_dir_url( __FILE__ ) );
	define( 'WCCDM_INCLUDES', WCCDM_DIR . 'includes/' );
	define( 'WCCDM_INCLUDES_URI', WCCDM_URI . 'includes/' );
	define( 'WCCDM_VERSION', '0.9.0' );

	/*Main directory di upload*/
	$wp_upload_dir = wp_upload_dir();

	/*Creo se necessario la cartella wccdm-private*/
	if ( wp_mkdir_p( trailingslashit( $wp_upload_dir['basedir'] . '/wccdm-private/files/backups' ) ) ) {
		define( 'WCCDM_PRIVATE', $wp_upload_dir['basedir'] . '/wccdm-private/' );
		define( 'WCCDM_PRIVATE_URI', $wp_upload_dir['baseurl'] . '/wccdm-private/' );
	}

	/*Requires*/
	require WCCDM_INCLUDES . 'class-wccdm-gateway.php';
	require WCCDM_INCLUDES . 'class-wccdm-soap-client.php';
	require WCCDM_INCLUDES . 'class-wccdm-admin.php';
	require WCCDM_INCLUDES . 'class-wccdm.php';

	/**
	 * Script e folgi di stile front-end
	 *
	 * @return void
	 */
	function wccdm_load_scripts() {
		wp_enqueue_style( 'wccdm-style', WCCDM_URI . 'css/wc-carta-del-merito.css', array(), WCCDM_VERSION );
	}

	/**
	 * Script e folgi di stile back-end
	 *
	 * @return void
	 */
	function wccdm_load_admin_scripts() {

		$admin_page = get_current_screen();

		if ( isset( $admin_page->base ) && 'woocommerce_page_wccdm-settings' === $admin_page->base ) {

			wp_enqueue_style( 'wccdm-admin-style', WCCDM_URI . 'css/wc-carta-del-merito-admin.css', array(), WCCDM_VERSION );
			wp_enqueue_script( 'wccdm-admin-scripts', WCCDM_URI . 'js/wc-carta-del-merito-admin.js', array(), WCCDM_VERSION, false );

			/* Nonce per l'eliminazione del certificato */
			$delete_nonce  = wp_create_nonce( 'wccdm-del-cert-nonce' );
			$add_cat_nonce = wp_create_nonce( 'wccdm-add-cat-nonce' );

			wp_localize_script(
				'wccdm-admin-scripts',
				'wccdmData',
				array(
					'delCertNonce' => $delete_nonce,
					'addCatNonce'  => $add_cat_nonce,
				)
			);

			/*tzCheckBox*/
			wp_enqueue_style( 'tzcheckbox-style', WCCDM_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css', array(), WCCDM_VERSION );
			wp_enqueue_script( 'tzcheckbox', WCCDM_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ), WCCDM_VERSION, false );
			wp_enqueue_script( 'tzcheckbox-script', WCCDM_URI . 'js/tzCheckbox/js/script.js', array( 'jquery' ), WCCDM_VERSION, false );

		}

	}

	/*Script e folgi di stile*/
	add_action( 'wp_enqueue_scripts', 'wccdm_load_scripts' );
	add_action( 'admin_enqueue_scripts', 'wccdm_load_admin_scripts' );
}
add_action( 'plugins_loaded', 'wccdm_activation', 100 );

/**
 * HPOS compatibility
 */
add_action(
	'before_woocommerce_init',
	function() {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

