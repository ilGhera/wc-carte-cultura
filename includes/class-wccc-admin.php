<?php
/**
 * Pagina opzioni e gestione certificati
 *
 * @author ilGhera
 * @package wc-carta-del-merito/includes
 *
 * @since 0.9.0
 */

/**
 * WCCDM_Admin class
 *
 * @since 0.9.0
 */
class WCCDM_Admin {

	/**
	 * The sandbox option
	 *
	 * @var bool
	 */
	private $sandbox;

	/**
	 * The constructor
	 *
	 * @return void
	 */
	public function __construct() {

		$this->sandbox = get_option( 'wccdm-sandbox' );

		add_action( 'admin_init', array( $this, 'wccdm_save_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_options_page' ) );
		add_action( 'wp_ajax_wccdm-delete-certificate', array( $this, 'delete_certificate_callback' ), 1 );
		add_action( 'wp_ajax_wccdm-add-cat', array( $this, 'add_cat_callback' ) );
		add_action( 'wp_ajax_wccdm-sandbox', array( $this, 'sandbox_callback' ) );
	}


	/**
	 * Registra la pagina opzioni del plugin
	 *
	 * @return void
	 */
	public function register_options_page() {

		add_submenu_page( 'woocommerce', __( 'WooCommerce Carta del Merito - Impostazioni', 'wccdm' ), __( 'WC Carta del Merito', 'wccdm' ), 'manage_options', 'wccdm-settings', array( $this, 'wccdm_settings' ) );

	}


	/**
	 * Verifica la presenza di un file per estenzione
	 *
	 * @param string $ext l,estensione del file da cercare.
	 *
	 * @return string l'url file
	 */
	public static function get_the_file( $ext ) {

		$files = array();

		foreach ( glob( WCCDM_PRIVATE . '*' . $ext ) as $file ) {
			$files[] = $file;
		}

		$output = empty( $files ) ? false : $files[0];

		return $output;

	}


	/**
	 * Cancella il certificato
	 *
	 * @return void
	 */
	public function delete_certificate_callback() {

		if ( isset( $_POST['wccdm-delete'], $_POST['delete-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['delete-nonce'] ) ), 'wccdm-del-cert-nonce' ) ) {

			$cert = isset( $_POST['cert'] ) ? sanitize_text_field( wp_unslash( $_POST['cert'] ) ) : '';

			if ( $cert ) {

				unlink( WCCDM_PRIVATE . $cert );

			}
		}

		exit;

	}


	/**
	 * Restituisce il nome esatto del bene Carta del Merito partendo dallo slug
	 *
	 * @param  array  $beni      l'elenco dei beni di Carta del Merito.
	 * @param  string $bene_slug lo slug del bene.
	 *
	 * @return string
	 */
	public function get_bene_label( $beni, $bene_slug ) {

		foreach ( $beni as $bene ) {

			if ( sanitize_title( $bene ) === $bene_slug ) {

				return $bene;

			}
		}

	}


	/**
	 * Categoria per la verifica in fase di checkout
	 *
	 * @param  int   $n             il numero dell'elemento aggiunto.
	 * @param  array $data          bene e categoria come chiave e velore.
	 * @param  array $exclude_beni  buoni già abbinati a categorie WC (al momento non utilizzato).
	 *
	 * @return mixed
	 */
	public function setup_cat( $n, $data = null, $exclude_beni = null ) {

		echo '<li class="setup-cat cat-' . esc_attr( $n ) . '">';

			/*L'elenco dei beni dei vari ambiti previsti dalla piattaforma*/
			$beni_index = array(
                'biglietti per rappresentazioni teatrali e cinematografiche e spettacoli dal vivo',
                'libri',
                'abbonamenti a quotidiani e periodici anche in formato digitale',
                'musica registrata',
                'prodotti dell’editoria audiovisiva',
                'titoli di accesso a musei, mostre ed eventi culturali, monumenti, gallerie, aree archeologiche, parchi naturali',
                'corsi di musica',
                'corsi di teatro',
                'corsi di danza',
                'corsi di lingua straniera',
			);

			$beni       = array_map( 'sanitize_title', $beni_index );
			$terms      = get_terms( 'product_cat' );
			$bene_value = is_array( $data ) ? key( $data ) : '';
			$term_value = $bene_value ? $data[ $bene_value ] : '';

			echo '<select name="wccdm-beni-' . esc_attr( $n ) . '" class="wccdm-field beni">';
				echo '<option value="">Bene Carta del Merito</option>';

			foreach ( $beni as $bene ) {

				echo '<option value="' . esc_attr( $bene ) . '"' . ( $bene === $bene_value ? ' selected="selected"' : '' ) . '>' . esc_html( $this->get_bene_label( $beni_index, $bene ) ) . '</option>';

			}
			echo '</select>';

			echo '<select name="wccdm-categories-' . esc_attr( $n ) . '" class="wccdm-field categories">';
				echo '<option value="">Categoria WooCommerce</option>';

			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->term_id ) . '"' . ( intval( $term_value ) === $term->term_id ? ' selected="selected"' : '' ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';

			if ( 1 === intval( $n ) ) {

				echo '<div class="add-cat-container">';
					echo '<img class="add-cat" src="' . esc_url( WCCDM_URI . 'images/add-cat.png' ) . '">';
					echo '<img class="add-cat-hover wccdm" src="' . esc_url( WCCDM_URI . 'images/add-cat-hover.png' ) . '">';
				echo '</div>';

			} else {

				echo '<div class="remove-cat-container">';
					echo '<img class="remove-cat" src="' . esc_url( WCCDM_URI . 'images/remove-cat.png' ) . '">';
					echo '<img class="remove-cat-hover" src="' . esc_url( WCCDM_URI . 'images/remove-cat-hover.png' ) . '">';
				echo '</div>';

			}

			echo '</li>';
	}


	/**
	 * Aggiunge una nuova categoria per la verifica in fase di checkout
	 *
	 * @return void
	 */
	public function add_cat_callback() {

		if ( isset( $_POST['add-cat-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['add-cat-nonce'] ) ), 'wccdm-add-cat-nonce' ) ) {

			$number       = isset( $_POST['number'] ) ? sanitize_text_field( wp_unslash( $_POST['number'] ) ) : '';
			$exclude_beni = isset( $_POST['exclude-beni'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude-beni'] ) ) : '';

			if ( $number ) {

				$this->setup_cat( $number, null, $exclude_beni );

			}
		}

		exit;
	}


	/**
	 * Pulsante call to action Premium
	 *
	 * @param bool $no_margin aggiunge la classe CSS con true.
	 *
	 * @return string
	 */
	public function get_go_premium( $no_margin = false ) {

		$output      = '<span class="label label-warning premium' . ( $no_margin ? ' no-margin' : null ) . '">';
			$output .= '<a href="https://www.ilghera.com/product/woocommerce-carta-del-merito-premium" target="_blank">Premium</a>';
		$output     .= '</span>';

		return $output;

	}


	/**
	 * Attivazione certificato
	 *
	 * @return string
	 */
	public function wccdm_cert_activation() {

		$soap_client = new WCCDM_Soap_Client( '11aa22bb', '' );

		try {

			$operation = $soap_client->check( 1 );
			return 'ok';

		} catch ( Exception $e ) {

			$notice = isset( $e->detail->FaultVoucher->exceptionMessage ) ? $e->detail->FaultVoucher->exceptionMessage : $e->faultstring;
			error_log( 'Error wccdm_cert_activation: ' . print_r( $e, true ) );

			return $notice;

		}
	}


	/**
	 * Funzionalita Sandbox
	 *
	 * @return void
	 */
	public function sandbox_callback() {

		if ( isset( $_POST['sandbox'], $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wccdm-sandbox' ) ) {

			$this->sandbox = sanitize_text_field( wp_unslash( $_POST['sandbox'] ) );

			update_option( 'wccdm-sandbox', $this->sandbox );
			update_option( 'wccdm-cert-activation', $this->sandbox );

		}

		exit();

	}


	/**
	 * Pagina opzioni plugin
	 *
	 * @return void
	 */
	public function wccdm_settings() {

		/*Recupero le opzioni salvate nel db*/
		$passphrase = base64_decode( get_option( 'wccdm-password' ) );
		$categories = get_option( 'wccdm-categories' );
		$tot_cats   = $categories ? count( $categories ) : 0;
		$wccdm_image = get_option( 'wccdm-image' );

		echo '<div class="wrap">';
			echo '<div class="wrap-left">';
				echo '<h1>WooCommerce Carta del Merito - ' . esc_html( __( 'Impostazioni', 'wccdm' ) ) . '</h1>';

				/*Tabs*/
				echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"></div>';
				echo '<h2 id="wccdm-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
					echo '<a href="#" data-link="wccdm-certificate" class="nav-tab nav-tab-active" onclick="return false;">' . esc_html( __( 'Certificato', 'wccdm' ) ) . '</a>';
					echo '<a href="#" data-link="wccdm-options" class="nav-tab" onclick="return false;">' . esc_html__( 'Opzioni', 'wccdm' ) . '</a>';
				echo '</h2>';

				/*Certificate*/
				echo '<div id="wccdm-certificate" class="wccdm-admin" style="display: block;">';

					/*Carica certificato .pem*/
					echo '<h3>' . esc_html__( 'Carica il tuo certificato', 'wccdm' ) . '</h3>';
					echo '<p class="description">' . esc_html__( 'Se sei già in posseso di un certificato non devi fare altro che caricarlo con relativa password, nient\'altro.', 'wccdm' ) . '</p>';

					echo '<form name="wccdm-upload-certificate" class="wccdm-upload-certificate one-of" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table wccdm-table">';

							/*Carica certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Carica certificato', 'wccdm' ) . '</th>';
								echo '<td>';
		if ( $file = self::get_the_file( '.pem' ) ) {

			$activation = $this->wccdm_cert_activation();

			if ( 'ok' === $activation ) {

				echo '<span class="cert-loaded">' . esc_html( basename( $file ) ) . '</span>';
				echo '<a class="button delete wccdm-delete-certificate">' . esc_html__( 'Elimina', 'wccdm' ) . '</a>';
				echo '<p class="description">' . esc_html__( 'File caricato e attivato correttamente.', 'wccdm' ) . '</p>';

				update_option( 'wccdm-cert-activation', 1 );

			} else {

				echo '<span class="cert-loaded error">' . esc_html( basename( $file ) ) . '</span>';
				echo '<a class="button delete wccdm-delete-certificate">' . esc_html__( 'Elimina', 'wccdm' ) . '</a>';

				/* Translators: the error message */
				echo '<p class="description">' . sprintf( esc_html__( 'L\'attivazione del certificato ha restituito il seguente errore: %s', 'wccdm' ), esc_html( $activation ) ) . '</p>';

				delete_option( 'wccdm-cert-activation' );

			}
		} else {

			echo '<input type="file" accept=".pem" name="wccdm-certificate" class="wccdm-certificate">';
			echo '<p class="description">' . esc_html__( 'Carica il certificato (.pem) necessario alla connessione con Carta del Merito', 'wccdm' ) . '</p>';

		}

								echo '</td>';
							echo '</tr>';

							/*Password utilizzata per la creazione del certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Password', 'wccdm' ) . '</th>';
								echo '<td>';
									echo '<input type="password" name="wccdm-password" placeholder="**********" value="' . esc_attr( $passphrase ) . '" required>';
									echo '<p class="description">' . esc_html__( 'La password utilizzata per la generazione del certificato', 'wccdm' ) . '</p>';

									wp_nonce_field( 'wccdm-upload-certificate', 'wccdm-certificate-nonce' );

									echo '<input type="hidden" name="wccdm-certificate-hidden" value="1">';
									echo '<input type="submit" class="button-primary wccdm-button" value="' . esc_html__( 'Salva certificato', 'wccdm' ) . '">';
								echo '</td>';
							echo '</tr>';

						echo '</table>';
					echo '</form>';

		/*Se il certificato non è presente vengono mostrati gli strumentui per generarlo*/
		if ( ! self::get_the_file( '.pem' ) ) {

			/*Genera richiesta certificato .der*/
			echo '<h3>' . esc_html( __( 'Richiedi un certificato', 'wccdm' ) ) . wp_kses_post( $this->get_go_premium() ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Con questo strumento puoi generare un file .der necessario per richiedere il tuo certificato su Carta del Merito.', 'wccdm' ) . '</p>';

			echo '<form id="generate-certificate-request" method="post" class="one-of" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccdm-table">';
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Stato', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="countryName" placeholder="IT" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Provincia', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="stateOrProvinceName" placeholder="Es. Milano" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Località', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="localityName" placeholder="Es. Legnano" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome azienda', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationName" placeholder="Es. Taldeitali srl" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Reparto azienda', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationalUnitName" placeholder="Es. Vendite" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="commonName" placeholder="Es. Franco Bianchi" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Email', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="email" name="emailAddress" placeholder="Es. franco.bianchi@taldeitali.it" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Password', 'wccdm' ) . '</th>';
						echo '<td>';
							echo '<input type="password" name="wccdm-password" placeholder="**********" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row"></th>';
						echo '<td>';
						echo '<input type="hidden" name="wccdm-generate-der-hidden" value="1">';
						echo '<input type="submit" name="generate-der" class="button-primary wccdm-button generate-der" value="' . esc_attr__( 'Scarica file .der', 'wccdm' ) . '" disabled>';
						echo '</td>';
					echo '</tr>';

				echo '</table>';
			echo '</form>';

			/*Genera certificato .pem*/
			echo '<h3>' . esc_html( __( 'Crea il tuo certificato', 'wccdm' ) ) . wp_kses_post( $this->get_go_premium() ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Con questo ultimo passaggio, potrai iniziare a ricevere pagamenti attraverso buoni Carta del Merito.', 'wccdm' ) . '</p>';

			echo '<form name="wccdm-generate-certificate" class="wccdm-generate-certificate one-of" method="post" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccdm-table">';

					/*Carica certificato*/
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Genera certificato', 'wccdm' ) . '</th>';
						echo '<td>';

							echo '<input type="file" accept=".cer" name="wccdm-cert" class="wccdm-cert" disabled>';
							echo '<p class="description">' . esc_html__( 'Carica il file .cer ottenuto da Carta del Merito per procedere', 'wccdm' ) . '</p>';

							echo '<input type="hidden" name="wccdm-gen-certificate-hidden" value="1">';
							echo '<input type="submit" class="button-primary wccdm-button" value="' . esc_html__( 'Genera certificato', 'wccdm' ) . '" disabled>';

						echo '</td>';
					echo '</tr>';

				echo '</table>';
			echo '</form>';

		}

				echo '</div>';

				/*Modalità Sandbox*/
				echo '<div id="wccdm-sandbox-option" class="wccdm-admin" style="display: block;">';
					echo '<h3>' . esc_html__( 'Modalità Sandbox', 'wccdm' ) . '</h3>';
				echo '<p class="description">';
					/* Translators: the email address */
					printf( wp_kses_post( __( 'Attiva questa funzionalità per testare buoni Carta del Merito in un ambiente di prova.<br>Richiedi i buoni test scrivendo a <a href="%s">docenti@sogei.it</a>', 'wccdm' ) ), 'mailto:docenti@sogei.it' );
				echo '</p>';

					echo '<form name="wccdm-sandbox" class="wccdm-sandbox" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table wccdm-table">';

							/*Carica certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Sandbox', 'wccdm' ) . '</th>';
								echo '<td class="wccdm-sandbox-field">';
									echo '<input type="checkbox" name="wccdm-sandbox" class="wccdm-sandbox"' . ( $this->sandbox ? ' checked="checked"' : null ) . '>';
									echo '<p class="description">' . esc_html__( 'Attiva modalità Sandbox', 'wccdm' ) . '</p>';

									wp_nonce_field( 'wccdm-sandbox', 'wccdm-sandbox-nonce' );

									echo '<input type="hidden" name="wccdm-sandbox-hidden" value="1">';

								echo '</td>';
							echo '</tr>';

						echo '</table>';
					echo '</form>';
				echo '</div>';

				/*Options*/
				echo '<div id="wccdm-options" class="wccdm-admin">';

					echo '<form name="wccdm-options" class="wccdm-form wccdm-options" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table">';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Categorie', 'wccdm' ) . '</th>';
								echo '<td>';

									echo '<ul  class="categories-container">';

		if ( $categories ) {

			for ( $i = 1; $i <= $tot_cats; $i++ ) {

				$this->setup_cat( $i, $categories[ $i - 1 ] );

			}
		} else {

			$this->setup_cat( 1 );

		}

									echo '</ul>';
									echo '<input type="hidden" name="wccdm-tot-cats" class="wccdm-tot-cats" value="' . ( is_array( $categories ) ? esc_attr( count( $categories ) ) : 1 ) . '">';
									echo '<p class="description">' . esc_html__( 'Seleziona le categorie di prodotti corrispondenti ai beni acquistabili.', 'wccdm' ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Utilizzo immagine', 'wccdm' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccdm-image" value="1"' . ( 1 === intval( $wccdm_image ) ? ' checked="checked"' : '' ) . '>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il logo <i>Carta del Merito</i> nella pagine di checkout.', 'wccdm' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Controllo prodotti', 'wccdm' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccdm-items-check" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il metodo di pagamento solo se il/ i prodotti a carrello sono acquistabili con buoni <i>Carta del Merito</i>.<br>Più prodotti dovranno prevedere l\'uso di buoni dello stesso ambito di utilizzo.', 'wccdm' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccdm-orders-on-hold">';
								echo '<th scope="row">' . esc_html__( 'Ordini in sospeso', 'wccdm' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccdm-orders-on-hold" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'I buoni Carta del Merito verranno validati con il completamento manuale degli ordini.', 'wccdm' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '<tr class="wccdm-exclude-shipping">';
								echo '<th scope="row">' . esc_html__( 'Spese di spedizione', 'wccdm' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccdm-exclude-shipping" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Escludi le spese di spedizione dal pagamento con Carta del Merito.', 'wccdm' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccdm-coupon">';
								echo '<th scope="row">' . esc_html__( 'Conversione in coupon', 'wccdm' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccdm-coupon" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Nel caso in cui il buono <i>Carta del Merito</i> inserito sia inferiore al totale a carrello, viene convertito in <i>Codice promozionale</i> ed applicato all\'ordine.', 'wccdm' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccdm-email-order-received wccdm-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine ricevuto', 'wccdm' ) . '</th>';
								echo '<td>';
									$default_order_received_message = __( 'L\'ordine verrà completato manualmente nei prossimi giorni e, contestualmente, verrà validato il buono Carta del Merito inserito. Riceverai una notifica email di conferma, grazie!', 'wccdm' );
									echo '<textarea cols="6" rows="6" class="regular-text" name="wccdm-email-order-received" placeholder="' . esc_html( $default_order_received_message ) . '" disabled></textarea>';
									echo '<p class="description">';
										echo wp_kses_post( __( 'Messaggio della mail inviata all\'utente al ricevimento dell\'ordine.', 'wccdm' ) );
									echo '</p>';
									echo '<div class="wccdm-divider"></div>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccdm-email-subject wccdm-email-details">';
								echo '<th scope="row">' . esc_html__( 'Oggetto email', 'wccdm' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccdm-email-subject" placeholder="' . esc_attr__( 'Ordine fallito', 'wccdm' ) . '" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Oggetto della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccdm' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccdm-email-heading wccdm-email-details">';
								echo '<th scope="row">' . esc_html__( 'Intestazione email', 'wccdm' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccdm-email-heading" placeholder="' . esc_attr__( 'Ordine fallito', 'wccdm' ) . '" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Intestazione della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccdm' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccdm-email-order-failed wccdm-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine fallito', 'wccdm' ) . '</th>';
								echo '<td>';
										$default_order_failed_message = __( 'La validazone del buono Carta del Merito ha restituito un errore e non è stato possibile completare l\'ordine, effettua il pagamento a <a href="[checkout-url]">questo indirizzo</a>.' );
										echo '<textarea cols="6" rows="6" class="regular-text" name="wccdm-email-order-failed" placeholder="' . esc_html( $default_order_failed_message ) . '" disabled></textarea>';
										echo '<p class="description">';
											echo '<span class="shortcodes">';
												echo '<code>[checkout-url]</code>';
											echo '</span>';
											echo wp_kses_post( __( 'Messaggio della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wccdm' ) );
										echo '</p>';
								echo '</td>';
							echo '</tr>';

						echo '</table>';

						wp_nonce_field( 'wccdm-save-settings', 'wccdm-settings-nonce' );

						echo '<input type="hidden" name="wccdm-settings-hidden" value="1">';
						echo '<input type="submit" class="button-primary" value="' . esc_html__( 'Salva impostazioni', 'wccdm' ) . '">';
					echo '</form>';
				echo '</div>';

			echo '</div>';

			echo '<div class="wrap-right">';
				echo '<iframe width="300" height="1300" scrolling="no" src="https://www.ilghera.com/images/wccdm-iframe.html"></iframe>';
			echo '</div>';
			echo '<div class="clear"></div>';

		echo '</div>';

	}


	/**
	 * Mostra un mesaggio d'errore nel caso in cui il certificato non isa valido
	 *
	 * @return void
	 */
	public function not_valid_certificate() {

		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'ATTENZIONE! Il file caricato non sembra essere un certificato valido.', 'wccdm' ); ?></p>
		</div>
		<?php

	}


	/**
	 * Salvataggio delle impostazioni dell'utente
	 *
	 * @return void
	 */
	public function wccdm_save_settings() {

		if ( isset( $_POST['wccdm-certificate-hidden'], $_POST['wccdm-certificate-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccdm-certificate-nonce'] ) ), 'wccdm-upload-certificate' ) ) {

			/*Carica certificato*/
			if ( isset( $_FILES['wccdm-certificate'] ) ) {

				$info = isset( $_FILES['wccdm-certificate']['name'] ) ? pathinfo( sanitize_text_field( wp_unslash( $_FILES['wccdm-certificate']['name'] ) ) ) : null;
				$name = isset( $info['basename'] ) ? sanitize_file_name( $info['basename'] ) : null;

				if ( $info ) {

					if ( 'pem' === $info['extension'] ) {

						if ( isset( $_FILES['wccdm-certificate']['tmp_name'] ) ) {

							$tmp_name = sanitize_text_field( wp_unslash( $_FILES['wccdm-certificate']['tmp_name'] ) );
							move_uploaded_file( $tmp_name, WCCDM_PRIVATE . $name );

						}
					} else {

						add_action( 'admin_notices', array( $this, 'not_valid_certificate' ) );

					}
				}
			}

			/*Password*/
			$wccdm_password = isset( $_POST['wccdm-password'] ) ? sanitize_text_field( wp_unslash( $_POST['wccdm-password'] ) ) : '';

			/*Salvo passw nel db*/
			if ( $wccdm_password ) {

				update_option( 'wccdm-password', base64_encode( $wccdm_password ) );

			}
		}

		if ( isset( $_POST['wccdm-settings-hidden'], $_POST['wccdm-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccdm-settings-nonce'] ) ), 'wccdm-save-settings' ) ) {

			/*Impostazioni categorie per il controllo in fase di checkout*/
			if ( isset( $_POST['wccdm-tot-cats'] ) ) {

				$tot = sanitize_text_field( wp_unslash( $_POST['wccdm-tot-cats'] ) );
				$tot = $tot ? $tot : 1;

				$wccdm_categories = array();

				for ( $i = 1; $i <= $tot; $i++ ) {

					$bene = isset( $_POST[ 'wccdm-beni-' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'wccdm-beni-' . $i ] ) ) : '';
					$cat  = isset( $_POST[ 'wccdm-categories-' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'wccdm-categories-' . $i ] ) ) : '';

					if ( $bene && $cat ) {

						$wccdm_categories[] = array( $bene => $cat );

					}
				}

				update_option( 'wccdm-categories', $wccdm_categories );
			}

			/*Immagine in pagina di checkout*/
			$wccdm_image = isset( $_POST['wccdm-image'] ) ? sanitize_text_field( wp_unslash( $_POST['wccdm-image'] ) ) : '';
			update_option( 'wccdm-image', $wccdm_image );

		}
	}

}
new WCCDM_Admin();

