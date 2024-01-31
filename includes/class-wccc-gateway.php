<?php
/**
 * Estende la classe WC_Payment_Gateway di WooCommerce aggiungendo il nuovo gateway Carte Cultura
 *
 * @author ilGhera
 * @package wc-carte-cultura/includes
 *
 * @since 0.9.0
 */

/**
 * WCCC_Gateway class
 *
 * @since 0.9.0
 */
class WCCC_Gateway extends WC_Payment_Gateway {

	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->plugin_id          = 'woocommerce_carte_cultura';
		$this->id                 = 'carte-cultura';
		$this->has_fields         = true;
		$this->method_title       = __( 'Buono Carte Cultura', 'wccc' );
		$this->method_description = __( 'Consente ai diciottenni di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.', 'wccc' );

		if ( get_option( 'wccc-image' ) ) {

			$this->icon = WCCC_URI . 'images/carte-cultura.png';

		}

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		/* Actions */
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'display_code' ), 10, 1 );
		add_action( 'woocommerce_email_after_order_table', array( $this, 'display_code' ), 10, 1 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_code' ), 10, 1 );

	}


	/**
	 * Campi relativi al sistema di pagamento, modificabili nel back-end
	 */
	public function init_form_fields() {

		$this->form_fields = apply_filters(
			'wc_offline_form_fields',
			array(
				'enabled'     => array(
					'title'   => __( 'Enable/Disable', 'woocommerce' ),
					'type'    => 'checkbox',
					'label'   => __( 'Abilita pagamento con buono Carte Cultura', 'wccc' ),
					'default' => 'yes',
				),
				'title'       => array(
					'title'       => __( 'Title', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'wccc' ),
					'default'     => __( 'Carte Cultura', 'wccc' ),
					'desc_tip'    => true,
				),
				'description' => array(
					'title'   => __( 'Messaggio utente', 'woocommerce' ),
					'type'    => 'textarea',
					'default' => 'Consente ai diciottenni di utilizzare il buono a loro riservato per l\'acquisto di materiale didattico.',
				),
			)
		);

	}


	/**
	 * Campo per l'inserimento del buono nella pagina di checkout
	 */
	public function payment_fields() {
		?>
		<p>
			<?php echo wp_kses_post( $this->description ); ?>
			<label for="wc-codice-carte-cultura">
				<?php esc_html_e( 'Inserisci qui il tuo codice', 'wccc' ); ?>
				<span class="required">*</span>
			</label>
			<input type="text" class="wc-codice-carte-cultura" id="wc-codice-carte-cultura" name="wc-codice-carte-cultura" />
		</p>
		<?php
	}


	/**
	 * Restituisce la cateogia prodotto corrispondente al bene acquistabile con il buono
	 *
	 * @param string $purchasable bene acquistabile.
	 * @param array  $categories  gli abbinamenti di categoria salvati nel db.
	 *
	 * @return int l'id di categoria acquistabile
	 */
	public static function get_purchasable_cats( $purchasable, $categories = null ) {

		$wccc_categories = is_array( $categories ) ? $categories : get_option( 'wccc-categories' );

		if ( $wccc_categories ) {

			$purchasable      = str_replace( '(', '', $purchasable );
			$purchasable      = str_replace( ')', '', $purchasable );
			$bene             = strtolower( str_replace( ' ', '-', $purchasable ) );
			$output           = array();
			$count_categories = count( $wccc_categories );

			for ( $i = 0; $i < $count_categories; $i++ ) {

				if ( array_key_exists( $bene, $wccc_categories[ $i ] ) ) {

					$output[] = $wccc_categories[ $i ][ $bene ];

				}
			}

			return $output;

		}

	}


	/**
	 * Tutti i prodotti dell'ordine devono essere della tipologia (cat) consentita dal buono Carte Cultura.
	 *
	 * @param  object $order the WC order.
	 * @param  string $bene  il bene acquistabile con il buono.
	 *
	 * @return bool
	 */
	public static function is_purchasable( $order, $bene ) {

		$wccc_categories = get_option( 'wccc-categories' );
		$cats            = self::get_purchasable_cats( $bene, $wccc_categories );
		$items           = $order->get_items();
		$output          = true;

		if ( is_array( $cats ) && ! empty( $wccc_categories ) ) {

			foreach ( $items as $item ) {
				$terms = get_the_terms( $item['product_id'], 'product_cat' );
				$ids   = array();

				if ( is_array( $terms ) ) {

					foreach ( $terms as $term ) {

						$ids[] = $term->term_id;

					}
				}

				$results = array_intersect( $ids, $cats );

				if ( ! is_array( $results ) || empty( $results ) ) {

					$output = false;
					continue;

				}
			}
		}

		return $output;

	}


	/**
	 * Add the shortcode to get the specific checkout URL.
	 *
	 * @param array $args the shortcode vars.
	 *
	 * @return mixed the URL
	 */
	public function get_checkout_payment_url( $args ) {

		$order_id = isset( $args['order-id'] ) ? $args['order-id'] : null;

		if ( $order_id ) {

			$order = wc_get_order( $order_id );

			return $order->get_checkout_payment_url();

		}

	}


	/**
	 * Mostra il buono Carte Cultura nella thankyou page, nelle mail e nella pagina dell'ordine.
	 *
	 * @param  object $order the WC order.
	 *
	 * @return void
	 */
	public function display_code( $order ) {

		$data         = $order->get_data();
		$wccc_code = null;

		if ( 'carte-cultura' === $data['payment_method'] ) {

			echo '<p><strong>' . esc_html__( 'Buono Carte Cultura', 'wccc' ) . ': </strong>' . esc_html( $order->get_meta( 'wc-codice-carte-cultura' ) ) . '</p>';

		}

	}


	/**
	 * Processa il buono Carte Cultura inserito
	 *
	 * @param int    $order_id     l'id dell'ordine.
	 * @param string $wccc_code il buono Carte Cultura.
	 * @param float  $import       il totale dell'ordine.
	 *
	 * @return mixed string in caso di errore, 1 in alternativa
	 */
	public static function process_code( $order_id, $wccc_code, $import ) {

		global $woocommerce;

		$output      = 1;
		$order       = wc_get_order( $order_id );
		$soap_client = new WCCC_Soap_Client( $wccc_code, $import );

		try {

			/*Prima verifica del buono*/
			$response      = $soap_client->check();
			$bene          = $response->checkResp->ambito; // Il bene acquistabile con il buono inserito.
			$importo_buono = floatval( $response->checkResp->importo ); // L'importo del buono inserito.

			/*Verifica se i prodotti dell'ordine sono compatibili con i beni acquistabili con il buono*/
			$purchasable = self::is_purchasable( $order, $bene );

			if ( ! $purchasable ) {

				$output = __( 'Uno o più prodotti nel carrello non sono acquistabili con il buono inserito.', 'wccc' );

			} else {

				$type = null;

				if ( $importo_buono === $import ) {

					$type = 'check';

				} else {

					$type = 'confirm';

				}

				if ( $type ) {

					try {

						/*Operazione differente in base al rapporto tra valore del buono e totale dell'ordine*/
						if ( 'check' === $type ) {

							$operation = $soap_client->check( 2 );

						} else {

							$operation = $soap_client->confirm();

						}

						/*Aggiungo il buono Carte Cultura all'ordine*/
						$order->update_meta_data( 'wc-codice-carte-cultura', $wccc_code );

						/* Ordine completato */
						$order->payment_complete();

						/*Svuota carrello*/
						$woocommerce->cart->empty_cart();

					} catch ( Exception $e ) {

						$output = $e->detail->FaultVoucher->exceptionMessage;

					}
				}
			}
		} catch ( Exception $e ) {

			$output = $e->detail->FaultVoucher->exceptionMessage;

		}

		return $output;

	}


	/**
	 * Gestisce il processo di pagamento, verificando la validità del buono inserito dall'utente
	 *
	 * @param  int $order_id l'id dell'ordine.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order  = wc_get_order( $order_id );
		$import = floatval( $order->get_total() );
		$notice = null;
		$output = array(
			'result'   => 'failure',
			'redirect' => '',
		);

		$data         = $this->get_post_data();
		$wccc_code = $data['wc-codice-carte-cultura']; // Il buono inserito dall'utente.

		if ( $wccc_code ) {

			$notice = self::process_code( $order_id, $wccc_code, $import );

			if ( 1 === intval( $notice ) ) {

				$output = array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ),
				);

			} else {

				/* Translators: Notifica all'utente nella pagina di checkout */
				wc_add_notice( sprintf( __( 'Buono Carte Cultura - %s', 'wccc' ), $notice ), 'error' );

			}
		}

		return $output;

	}

}

