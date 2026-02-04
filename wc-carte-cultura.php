<?php
/**
 * Plugin name: WC Carte Cultura
 * Plugin URI: https://www.ilghera.com/product/woocommerce-carte-cultura-premium/
 * Description: Abilita in WooCommerce il pagamento con Carte Cultura prevista dallo stato Italiano.
 * Author: ilGhera
 *
 * @package wc-carte-cultura
 * Version: 1.1.0
 * Stable tag: 1.0.0
 * Author URI: https://ilghera.com
 * Requires at least: 4.0
 * Tested up to: 6.8
 * WC tested up to: 9
 * Text Domain: wc-carte-cultura
 * Domain Path: /languages
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 */

defined( 'ABSPATH' ) || exit;

/**
 * Attivazione
 */
function wccc_activation() {

	/*Is WooCommerce activated?*/
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	/*Definizione costanti*/
	define( 'WCCC_DIR', plugin_dir_path( __FILE__ ) );
	define( 'WCCC_URI', plugin_dir_url( __FILE__ ) );
	define( 'WCCC_INCLUDES', WCCC_DIR . 'includes/' );
	define( 'WCCC_INCLUDES_URI', WCCC_URI . 'includes/' );
	define( 'WCCC_VERSION', '1.1.0' );

	/*Main directory di upload*/
	$wp_upload_dir = wp_upload_dir();

	/*Creo se necessario la cartella wccc-private*/
	if ( wp_mkdir_p( trailingslashit( $wp_upload_dir['basedir'] . '/wccc-private/files/backups' ) ) ) {
		define( 'WCCC_PRIVATE', $wp_upload_dir['basedir'] . '/wccc-private/' );
		define( 'WCCC_PRIVATE_URI', $wp_upload_dir['baseurl'] . '/wccc-private/' );
	}

	/*Requires*/
	require WCCC_INCLUDES . 'class-wccc-gateway.php';
	require WCCC_INCLUDES . 'class-wccc-soap-client.php';
	require WCCC_INCLUDES . 'class-wccc-admin.php';
	require WCCC_INCLUDES . 'class-wccc.php';

	/**
	 * Script e folgi di stile front-end
	 *
	 * @return void
	 */
	function wccc_load_scripts() {
		wp_enqueue_style( 'wccc-style', WCCC_URI . 'css/wc-carte-cultura.css', array(), WCCC_VERSION );
	}

	/**
	 * Script e folgi di stile back-end
	 *
	 * @return void
	 */
	function wccc_load_admin_scripts() {

		$admin_page = get_current_screen();

		if ( isset( $admin_page->base ) && 'woocommerce_page_wccc-settings' === $admin_page->base ) {

			wp_enqueue_style( 'wccc-admin-style', WCCC_URI . 'css/wc-carte-cultura-admin.css', array(), WCCC_VERSION );
			wp_enqueue_script( 'wccc-admin-scripts', WCCC_URI . 'js/wc-carte-cultura-admin.js', array(), WCCC_VERSION, false );

			/* Nonce per l'eliminazione del certificato */
			$delete_nonce  = wp_create_nonce( 'wccc-del-cert-nonce' );
			$add_cat_nonce = wp_create_nonce( 'wccc-add-cat-nonce' );

			wp_localize_script(
				'wccc-admin-scripts',
				'wcccData',
				array(
					'delCertNonce' => $delete_nonce,
					'addCatNonce'  => $add_cat_nonce,
				)
			);

			/*tzCheckBox*/
			wp_enqueue_style( 'tzcheckbox-style', WCCC_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.css', array(), WCCC_VERSION );
			wp_enqueue_script( 'tzcheckbox', WCCC_URI . 'js/tzCheckbox/jquery.tzCheckbox/jquery.tzCheckbox.js', array( 'jquery' ), WCCC_VERSION, false );
			wp_enqueue_script( 'tzcheckbox-script', WCCC_URI . 'js/tzCheckbox/js/script.js', array( 'jquery' ), WCCC_VERSION, false );

		}

	}

	/*Script e folgi di stile*/
	add_action( 'wp_enqueue_scripts', 'wccc_load_scripts' );
	add_action( 'admin_enqueue_scripts', 'wccc_load_admin_scripts' );
}
add_action( 'plugins_loaded', 'wccc_activation', 100 );

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

