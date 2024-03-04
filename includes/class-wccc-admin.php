<?php
/**
 * Pagina opzioni e gestione certificati
 *
 * @author ilGhera
 * @package wc-carte-cultura/includes
 *
 * @since 0.9.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCCC_Admin class
 *
 * @since 0.9.0
 */
class WCCC_Admin {

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

		$this->sandbox = get_option( 'wccc-sandbox' );

        /* Actions */
		add_action( 'admin_init', array( $this, 'wccc_save_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_options_page' ) );
		add_action( 'wp_ajax_wccc-delete-certificate', array( $this, 'delete_certificate_callback' ), 1 );
		add_action( 'wp_ajax_wccc-add-cat', array( $this, 'add_cat_callback' ) );
		add_action( 'wp_ajax_wccc-sandbox', array( $this, 'sandbox_callback' ) );

        /* Filters */
        add_filter( 'wp_check_filetype_and_ext', array( $this, 'wccc_allow_pem' ), 10, 4 );

	}


	/**
	 * Registra la pagina opzioni del plugin
	 *
	 * @return void
	 */
	public function register_options_page() {

		add_submenu_page( 'woocommerce', __( 'WooCommerce Carte Cultura - Impostazioni', 'wc-carte-cultura' ), __( 'WC Carte Cultura', 'wc-carte-cultura' ), 'manage_options', 'wccc-settings', array( $this, 'wccc_settings' ) );

	}


	/**
	 * Verifica la presenza di un file per estenzione
	 *
	 * @param string $ext l'estensione del file da cercare.
	 *
	 * @return string l'url file
	 */
	public static function get_the_file( $ext ) {

		$files = array();

		foreach ( glob( WCCC_PRIVATE . '*' . $ext ) as $file ) {
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

		if ( isset( $_POST['wccc-delete'], $_POST['delete-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['delete-nonce'] ) ), 'wccc-del-cert-nonce' ) ) {

			$cert = isset( $_POST['cert'] ) ? sanitize_text_field( wp_unslash( $_POST['cert'] ) ) : '';

			if ( $cert ) {

				unlink( WCCC_PRIVATE . $cert );

			}
		}

		exit;

	}


	/**
	 * Restituisce il nome esatto del bene Carte Cultura partendo dallo slug
	 *
	 * @param  array  $beni      l'elenco dei beni di Carte Cultura.
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

            $old = array(
                'Biglietti per rappresentazioni teatrali e cinematografiche e spettacoli dal vivo',
                'Libri',
                'Abbonamenti a quotidiani e periodici anche in formato digitale',
                'Musica registrata',
                'Prodotti dell’editoria audiovisiva',
                'Titoli di accesso a musei, mostre ed eventi culturali, monumenti, gallerie, aree archeologiche, parchi naturali',
                'Corsi di musica',
                'Corsi di teatro',
                'Corsi di danza',
                'Corsi di lingua straniera',
            );

			/*L'elenco dei beni dei vari ambiti previsti dalla piattaforma*/
			$beni_index = array(
                'Formazione',
                'Quotidiani e periodici',
                'Musei, monumenti e parchi naturali ed aree archeologiche',
                'Cinema',
                'Musica registrata',
                'Teatro e danza',
                'Libri',
                'Prodotti dell\'editoria audiovisiva',
                'Eventi culturali',
                'Concerti',
			);

			$beni       = array_map( 'sanitize_title', $beni_index );
			$terms      = get_terms( 'product_cat' );
			$bene_value = is_array( $data ) ? key( $data ) : '';
			$term_value = $bene_value ? $data[ $bene_value ] : '';

			echo '<select name="wccc-beni-' . esc_attr( $n ) . '" class="wccc-field beni">';
				echo '<option value="">Bene Carte Cultura</option>';

			foreach ( $beni as $bene ) {

				echo '<option value="' . esc_attr( $bene ) . '"' . ( $bene === $bene_value ? ' selected="selected"' : '' ) . '>' . esc_html( $this->get_bene_label( $beni_index, $bene ) ) . '</option>';

			}
			echo '</select>';

			echo '<select name="wccc-categories-' . esc_attr( $n ) . '" class="wccc-field categories">';
				echo '<option value="">Categoria WooCommerce</option>';

			foreach ( $terms as $term ) {
				echo '<option value="' . esc_attr( $term->term_id ) . '"' . ( intval( $term_value ) === $term->term_id ? ' selected="selected"' : '' ) . '>' . esc_html( $term->name ) . '</option>';
			}
			echo '</select>';

			if ( 1 === intval( $n ) ) {

				echo '<div class="add-cat-container">';
					echo '<img class="add-cat" src="' . esc_url( WCCC_URI . 'images/add-cat.png' ) . '">';
					echo '<img class="add-cat-hover wccc" src="' . esc_url( WCCC_URI . 'images/add-cat-hover.png' ) . '">';
				echo '</div>';

			} else {

				echo '<div class="remove-cat-container">';
					echo '<img class="remove-cat" src="' . esc_url( WCCC_URI . 'images/remove-cat.png' ) . '">';
					echo '<img class="remove-cat-hover" src="' . esc_url( WCCC_URI . 'images/remove-cat-hover.png' ) . '">';
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

		if ( isset( $_POST['add-cat-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['add-cat-nonce'] ) ), 'wccc-add-cat-nonce' ) ) {

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
			$output .= '<a href="https://www.ilghera.com/product/woocommerce-carte-cultura-premium" target="_blank">Premium</a>';
		$output     .= '</span>';

		return $output;

	}


	/**
	 * Attivazione certificato
	 *
	 * @return string
	 */
	public function wccc_cert_activation() {

		$soap_client = new WCCC_Soap_Client( '11aa22bb', '' );

		try {

			$operation = $soap_client->check( 1 );
			return 'ok';

		} catch ( Exception $e ) {

			$notice = isset( $e->detail->FaultVoucher->exceptionMessage ) ? $e->detail->FaultVoucher->exceptionMessage : $e->faultstring;
			error_log( 'Error wccc_cert_activation: ' . print_r( $e, true ) );

			return $notice;

		}
	}


	/**
	 * Funzionalita Sandbox
	 *
	 * @return void
	 */
	public function sandbox_callback() {

		if ( isset( $_POST['sandbox'], $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wccc-sandbox' ) ) {

			$this->sandbox = sanitize_text_field( wp_unslash( $_POST['sandbox'] ) );

			update_option( 'wccc-sandbox', $this->sandbox );
			update_option( 'wccc-cert-activation', $this->sandbox );

		}

		exit();

	}


	/**
	 * Pagina opzioni plugin
	 *
	 * @return void
	 */
	public function wccc_settings() {

		/*Recupero le opzioni salvate nel db*/
		$passphrase = base64_decode( get_option( 'wccc-password' ) );
		$categories = get_option( 'wccc-categories' );
		$tot_cats   = $categories ? count( $categories ) : 0;
		$wccc_image = get_option( 'wccc-image' );

		echo '<div class="wrap">';
			echo '<div class="wrap-left">';
				echo '<h1>WooCommerce Carte Cultura - ' . esc_html__( 'Impostazioni', 'wc-carte-cultura' ) . '</h1>';

				/*Tabs*/
				echo '<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"></div>';
				echo '<h2 id="wccc-admin-menu" class="nav-tab-wrapper woo-nav-tab-wrapper">';
					echo '<a href="#" data-link="wccc-certificate" class="nav-tab nav-tab-active" onclick="return false;">' . esc_html__( 'Certificato', 'wc-carte-cultura' ) . '</a>';
					echo '<a href="#" data-link="wccc-options" class="nav-tab" onclick="return false;">' . esc_html__( 'Opzioni', 'wc-carte-cultura' ) . '</a>';
				echo '</h2>';

				/*Certificate*/
				echo '<div id="wccc-certificate" class="wccc-admin" style="display: block;">';

					/*Carica certificato .pem*/
					echo '<h3>' . esc_html__( 'Carica il tuo certificato', 'wc-carte-cultura' ) . '</h3>';
					echo '<p class="description">' . esc_html__( 'Se sei già in posseso di un certificato non devi fare altro che caricarlo con relativa password.', 'wc-carte-cultura' ) . '</p>';

					echo '<form name="wccc-upload-certificate" class="wccc-upload-certificate one-of" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table wccc-table">';

							/*Carica certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Carica certificato', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
		if ( $file = self::get_the_file( '.pem' ) ) {

			$activation = $this->wccc_cert_activation();

			if ( 'ok' === $activation ) {

				echo '<span class="cert-loaded">' . esc_html( basename( $file ) ) . '</span>';
				echo '<a class="button delete wccc-delete-certificate">' . esc_html__( 'Elimina', 'wc-carte-cultura' ) . '</a>';
				echo '<p class="description">' . esc_html__( 'File caricato e attivato correttamente.', 'wc-carte-cultura' ) . '</p>';

				update_option( 'wccc-cert-activation', 1 );

			} else {

				echo '<span class="cert-loaded error">' . esc_html( basename( $file ) ) . '</span>';
				echo '<a class="button delete wccc-delete-certificate">' . esc_html__( 'Elimina', 'wc-carte-cultura' ) . '</a>';

				/* Translators: the error message */
				echo '<p class="description">' . sprintf( esc_html__( 'L\'attivazione del certificato ha restituito il seguente errore: %s', 'wc-carte-cultura' ), esc_html( $activation ) ) . '</p>';

				delete_option( 'wccc-cert-activation' );

			}
		} else {

			/* echo '<input type="file" accept=".pem" name="wccc-certificate" class="wccc-certificate">'; */
			echo '<input type="file" name="wccc-certificate" class="wccc-certificate">';
			echo '<p class="description">' . esc_html__( 'Carica il certificato (.pem) necessario alla connessione con Carta della Cultura Giovani e Carta del Merito', 'wc-carte-cultura' ) . '</p>';

		}

								echo '</td>';
							echo '</tr>';

							/*Password utilizzata per la creazione del certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Password', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
									echo '<input type="password" name="wccc-password" placeholder="**********" value="' . esc_attr( $passphrase ) . '" required>';
									echo '<p class="description">' . esc_html__( 'La password utilizzata per la generazione del certificato', 'wc-carte-cultura' ) . '</p>';

									wp_nonce_field( 'wccc-upload-certificate', 'wccc-certificate-nonce' );

									echo '<input type="hidden" name="wccc-certificate-hidden" value="1">';
									echo '<input type="submit" class="button-primary wccc-button" value="' . esc_html__( 'Salva certificato', 'wc-carte-cultura' ) . '">';
								echo '</td>';
							echo '</tr>';

						echo '</table>';
					echo '</form>';

		/*Se il certificato non è presente vengono mostrati gli strumentui per generarlo*/
		if ( ! self::get_the_file( '.pem' ) ) {

			/*Genera richiesta certificato .der*/
			echo '<h3>' . esc_html__( 'Richiedi un certificato', 'wc-carte-cultura' ) . wp_kses_post( $this->get_go_premium() ) . '</h3>';
			echo '<p class="description">' . wp_kses_post( __( "Con questo strumento puoi generare un file .der necessario per richiedere il tuo certificato sul sito <a target='_blank' href='https://cartegiovani.cultura.gov.it/giovanimeritoEsercente/#/login'>Carte Cultura</a>.", 'wc-carte-cultura' ) ) . '</p>';

			echo '<form id="generate-certificate-request" method="post" class="one-of" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccc-table">';
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Stato', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="countryName" placeholder="IT" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Provincia', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="stateOrProvinceName" placeholder="Es. Milano" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Località', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="localityName" placeholder="Es. Legnano" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome azienda', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationName" placeholder="Es. Taldeitali srl" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Reparto azienda', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="organizationalUnitName" placeholder="Es. Vendite" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Nome', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="text" name="commonName" placeholder="Es. Franco Bianchi" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Email', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="email" name="emailAddress" placeholder="Es. franco.bianchi@taldeitali.it" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row">' . esc_html__( 'Password', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';
							echo '<input type="password" name="wccc-password" placeholder="**********" disabled>';
						echo '</td>';
					echo '</tr>';

					echo '<th scope="row"></th>';
						echo '<td>';
						echo '<input type="hidden" name="wccc-generate-der-hidden" value="1">';
						echo '<input type="submit" name="generate-der" class="button-primary wccc-button generate-der" value="' . esc_attr__( 'Scarica file .der', 'wc-carte-cultura' ) . '" disabled>';
						echo '</td>';
					echo '</tr>';

				echo '</table>';
			echo '</form>';

			/*Genera certificato .pem*/
			echo '<h3>' . esc_html__( 'Crea il tuo certificato', 'wc-carte-cultura' ) . wp_kses_post( $this->get_go_premium() ) . '</h3>';
			echo '<p class="description">' . esc_html__( 'Con questo ultimo passaggio, potrai iniziare a ricevere pagamenti attraverso buoni Carta della Cultura Giovani e Carta del Merito.', 'wc-carte-cultura' ) . '</p>';

			echo '<form name="wccc-generate-certificate" class="wccc-generate-certificate one-of" method="post" enctype="multipart/form-data" action="">';
				echo '<table class="form-table wccc-table">';

					/*Carica certificato*/
					echo '<tr>';
						echo '<th scope="row">' . esc_html__( 'Genera certificato', 'wc-carte-cultura' ) . '</th>';
						echo '<td>';

							echo '<input type="file" accept=".cer" name="wccc-cert" class="wccc-cert" disabled>';
							echo '<p class="description">' . wp_kses_post( __( "Carica il file .cer ottenuto dal sito <a target='_blank' href='https://cartegiovani.cultura.gov.it/giovanimeritoEsercente/#/login'>Carte Cultura</a>", 'wc-carte-cultura' ) ) . '</p>';

							echo '<input type="hidden" name="wccc-gen-certificate-hidden" value="1">';
							echo '<input type="submit" class="button-primary wccc-button" value="' . esc_html__( 'Genera certificato', 'wc-carte-cultura' ) . '" disabled>';

						echo '</td>';
					echo '</tr>';

				echo '</table>';
			echo '</form>';

		}

				echo '</div>';

				/*Modalità Sandbox*/
				echo '<div id="wccc-sandbox-option" class="wccc-admin" style="display: block;">';
					echo '<h3>' . esc_html__( 'Modalità Sandbox', 'wc-carte-cultura' ) . '</h3>';
				echo '<p class="description">';
					/* Translators: the email address */
					echo wp_kses_post( __( "Attiva questa funzionalità per testare buoni Carta della Cultura Giovani e Carta del Merito in un ambiente di prova.<br>Richiedi i buoni test scrivendo a <a href=\"mailto:numeroverde@cultura.gov.it\">numeroverde@cultura.gov.it</a>", 'wc-carte-cultura' ) );;
				echo '</p>';

					echo '<form name="wccc-sandbox" class="wccc-sandbox" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table wccc-table">';

							/*Carica certificato*/
							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Sandbox', 'wc-carte-cultura' ) . '</th>';
								echo '<td class="wccc-sandbox-field">';
									echo '<input type="checkbox" name="wccc-sandbox" class="wccc-sandbox"' . ( $this->sandbox ? ' checked="checked"' : null ) . '>';
									echo '<p class="description">' . esc_html__( 'Attiva modalità Sandbox', 'wc-carte-cultura' ) . '</p>';

									wp_nonce_field( 'wccc-sandbox', 'wccc-sandbox-nonce' );

									echo '<input type="hidden" name="wccc-sandbox-hidden" value="1">';

								echo '</td>';
							echo '</tr>';

						echo '</table>';
					echo '</form>';
				echo '</div>';

				/*Options*/
				echo '<div id="wccc-options" class="wccc-admin">';

					echo '<form name="wccc-options" class="wccc-form wccc-options" method="post" enctype="multipart/form-data" action="">';
						echo '<table class="form-table">';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Categorie', 'wc-carte-cultura' ) . '</th>';
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
									echo '<input type="hidden" name="wccc-tot-cats" class="wccc-tot-cats" value="' . ( is_array( $categories ) ? esc_attr( count( $categories ) ) : 1 ) . '">';
									echo '<p class="description">' . esc_html__( 'Seleziona le categorie di prodotti corrispondenti ai beni acquistabili.', 'wc-carte-cultura' ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Utilizzo immagine', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccc-image" value="1"' . ( 1 === intval( $wccc_image ) ? ' checked="checked"' : '' ) . '>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il logo <i>Carte Cultura</i> nella pagine di checkout.', 'wc-carte-cultura' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr>';
								echo '<th scope="row">' . esc_html__( 'Controllo prodotti', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccc-items-check" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Mostra il metodo di pagamento solo se il/ i prodotti a carrello sono acquistabili con buoni <i>Carta della Cultura Giovani</i> e <i>Carta del Merito</i>.<br>Più prodotti dovranno prevedere l\'uso di buoni dello stesso ambito di utilizzo.', 'wc-carte-cultura' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccc-orders-on-hold">';
								echo '<th scope="row">' . esc_html__( 'Ordini in sospeso', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccc-orders-on-hold" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'I buoni Carta della Cultura Giovani e Carta del merito verranno validati con il completamento manuale degli ordini.', 'wc-carte-cultura' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '<tr class="wccc-exclude-shipping">';
								echo '<th scope="row">' . esc_html__( 'Spese di spedizione', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
										echo '<input type="checkbox" name="wccc-exclude-shipping" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Escludi le spese di spedizione dal pagamento con le Carte Cultura.', 'wc-carte-cultura' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccc-coupon">';
								echo '<th scope="row">' . esc_html__( 'Conversione in coupon', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
									echo '<input type="checkbox" name="wccc-coupon" value="1" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Nel caso in cui il buono <i>Carta della Cultura Giovani</i> o <i>Carta del Merito</i> inserito sia inferiore al totale a carrello, viene convertito in <i>Codice promozionale</i> ed applicato all\'ordine.', 'wc-carte-cultura' ) ) . '</p>';

									echo wp_kses_post( $this->get_go_premium( true ) );
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccc-email-order-received wccc-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine ricevuto', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
									$default_order_received_message = __( 'L\'ordine verrà completato manualmente nei prossimi giorni e, contestualmente, verrà validato il buono Carte Cultura inserito. Riceverai una notifica email di conferma, grazie!', 'wc-carte-cultura' );
									echo '<textarea cols="6" rows="6" class="regular-text" name="wccc-email-order-received" placeholder="' . esc_html( $default_order_received_message ) . '" disabled></textarea>';
									echo '<p class="description">';
										echo wp_kses_post( __( 'Messaggio della mail inviata all\'utente al ricevimento dell\'ordine.', 'wc-carte-cultura' ) );
									echo '</p>';
									echo '<div class="wccc-divider"></div>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccc-email-subject wccc-email-details">';
								echo '<th scope="row">' . esc_html__( 'Oggetto email', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccc-email-subject" placeholder="' . esc_attr__( 'Ordine fallito', 'wc-carte-cultura' ) . '" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Oggetto della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wc-carte-cultura' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccc-email-heading wccc-email-details">';
								echo '<th scope="row">' . esc_html__( 'Intestazione email', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
										echo '<input type="text" class="regular-text" name="wccc-email-heading" placeholder="' . esc_attr__( 'Ordine fallito', 'wc-carte-cultura' ) . '" disabled>';
									echo '<p class="description">' . wp_kses_post( __( 'Intestazione della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wc-carte-cultura' ) ) . '</p>';
								echo '</td>';
							echo '</tr>';

							echo '<tr class="wccc-email-order-failed wccc-email-details">';
								echo '<th scope="row">' . esc_html__( 'Ordine fallito', 'wc-carte-cultura' ) . '</th>';
								echo '<td>';
										$default_order_failed_message = __( 'La validazone del buono Carte Cultura ha restituito un errore e non è stato possibile completare l\'ordine, effettua il pagamento a <a href="[checkout-url]">questo indirizzo</a>.' );
										echo '<textarea cols="6" rows="6" class="regular-text" name="wccc-email-order-failed" placeholder="' . esc_html( $default_order_failed_message ) . '" disabled></textarea>';
										echo '<p class="description">';
											echo '<span class="shortcodes">';
												echo '<code>[checkout-url]</code>';
											echo '</span>';
											echo wp_kses_post( __( 'Messaggio della mail inviata all\'utente nel caso in cui la validazione del buono non sia andata a buon fine.', 'wc-carte-cultura' ) );
										echo '</p>';
								echo '</td>';
							echo '</tr>';

						echo '</table>';

						wp_nonce_field( 'wccc-save-settings', 'wccc-settings-nonce' );

						echo '<input type="hidden" name="wccc-settings-hidden" value="1">';
						echo '<input type="submit" class="button-primary" value="' . esc_html__( 'Salva impostazioni', 'wc-carte-cultura' ) . '">';
					echo '</form>';
				echo '</div>';

			echo '</div>';

			echo '<div class="wrap-right">';
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
			<p><?php esc_html_e( 'ATTENZIONE! Il file caricato non sembra essere un certificato valido.', 'wc-carte-cultura' ); ?></p>
		</div>
		<?php

	}


    /**
     * Plugin upload directory
     *
     * @param array $dir the default upload directory.
     *
     * @return array
     */
    public function wccc_upload_dir( $dir ) {

        $output = array(
            'path'   => $dir['basedir'] . '/wccc-private',
            'url'    => $dir['baseurl'] . '/wccc-private',
            'subdir' => '/wccc-private',
        ) + $dir;

        return $output;

    }


    /**
     * Allow upload pem files 
     *
     * @param array $file      Full path to the file. 
     * @param array $filename  The name of the file (may differ from $file due to $file being in a tmp directory). 
     * @param array $mimes     Array of mime types keyed by their file extension regex, or null if none were provided. 
     * @param array $real_mime The actual mime type or false if the type cannot be determined. 
     *
     * @return array
     */
    function wccc_allow_pem( $file, $filename, $mimes, $real_mime ) {

        if ( false !== strpos( $mimes, '.pem' ) ) {

            $file['ext']  = 'pem';
            $file['type'] = 'application/x-x509-ca-cert';

        }

        return $file;

    }


	/**
	 * Salvataggio delle impostazioni dell'utente
	 *
	 * @return void
	 */
	public function wccc_save_settings() {

		if ( isset( $_POST['wccc-certificate-hidden'], $_POST['wccc-certificate-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccc-certificate-nonce'] ) ), 'wccc-upload-certificate' ) ) {

            /* Temporary filter start */
            add_filter( 'upload_dir', array( $this, 'wccc_upload_dir' ) );

			/*Carica certificato*/
			if ( isset( $_FILES['wccc-certificate'] ) ) {

				$info = isset( $_FILES['wccc-certificate']['name'] ) ? pathinfo( sanitize_text_field( wp_unslash( $_FILES['wccc-certificate']['name'] ) ) ) : null;
				$name = isset( $info['basename'] ) ? sanitize_file_name( $info['basename'] ) : null;

				if ( $info ) {

					if ( 'pem' === $info['extension'] ) {

						if ( isset( $_FILES['wccc-certificate']['tmp_name'] ) ) {

							$tmp_name = sanitize_text_field( wp_unslash( $_FILES['wccc-certificate']['tmp_name'] ) );

                            if ( ! function_exists( 'wp_handle_upload' ) ) {
                                require_once( ABSPATH . 'wp-admin/includes/file.php' );
                            }

                            $move_file = wp_handle_upload( $_FILES['wccc-certificate'], array( 'test_form' => false, 'test_type' => true, 'mimes' => array( 'pem' => 'application/x-x509-ca-cert') ) );

						}
					} else {

						add_action( 'admin_notices', array( $this, 'not_valid_certificate' ) );

					}
				}
			}

			/*Password*/
			$wccc_password = isset( $_POST['wccc-password'] ) ? sanitize_text_field( wp_unslash( $_POST['wccc-password'] ) ) : '';

			/*Salvo passw nel db*/
			if ( $wccc_password ) {

				update_option( 'wccc-password', base64_encode( $wccc_password ) );

			}
		}

		if ( isset( $_POST['wccc-settings-hidden'], $_POST['wccc-settings-nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wccc-settings-nonce'] ) ), 'wccc-save-settings' ) ) {

			/*Impostazioni categorie per il controllo in fase di checkout*/
			if ( isset( $_POST['wccc-tot-cats'] ) ) {

				$tot = sanitize_text_field( wp_unslash( $_POST['wccc-tot-cats'] ) );
				$tot = $tot ? $tot : 1;

				$wccc_categories = array();

				for ( $i = 1; $i <= $tot; $i++ ) {

					$bene = isset( $_POST[ 'wccc-beni-' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'wccc-beni-' . $i ] ) ) : '';
					$cat  = isset( $_POST[ 'wccc-categories-' . $i ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'wccc-categories-' . $i ] ) ) : '';

					if ( $bene && $cat ) {

						$wccc_categories[] = array( $bene => $cat );

					}
				}

				update_option( 'wccc-categories', $wccc_categories );
			}

			/*Immagine in pagina di checkout*/
			$wccc_image = isset( $_POST['wccc-image'] ) ? sanitize_text_field( wp_unslash( $_POST['wccc-image'] ) ) : '';
			update_option( 'wccc-image', $wccc_image );

		}
	}

}
new WCCC_Admin();

