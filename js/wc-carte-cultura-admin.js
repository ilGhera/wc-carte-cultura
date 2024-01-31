/**
 * WC Carta del Merito - Admin js
 *
 * @author ilGhera
 * @package wc-carta-del-merito/js
 *
 * @version 0.9.0
 */

/**
 * Ajax - Elimina il certificato caricato precedentemente
 */
var wccdm_delete_certificate = function() {
	jQuery(function($){
		$('.wccdm-delete-certificate').on('click', function(){
			var sure = confirm('Sei sicuro di voler eliminare il certificato?');
			if(sure) {
				var cert = $('.cert-loaded').text();
				var data = {
					'action': 'wccdm-delete-certificate',
					'wccdm-delete': true,
                    'delete-nonce': wccdmData.delCertNonce,
					'cert': cert
				}			
				$.post(ajaxurl, data, function(response){
					location.reload();
				})
			}
		})	
	})
}
wccdm_delete_certificate();


/**
 * Aggiunge un nuovo abbinamento bene/ categoria per il controllo in pagina di checkout
 */
var wccdm_add_cat = function() {
	jQuery(function($){
		$('.add-cat-hover.wccdm').on('click', function(){
			var number = $('.setup-cat').length + 1;

			/*Beni già impostati da escludere*/
			var beni_values = [];
			$('.wccdm-field.beni').each(function(){
				beni_values.push($(this).val());
			})

			var data = {
				'action': 'wccdm-add-cat',
				'number': number,
				'exclude-beni': beni_values.toString(),
                'add-cat-nonce': wccdmData.addCatNonce,
			}
			$.post(ajaxurl, data, function(response){
				$(response).appendTo('.categories-container');
				$('.wccdm-tot-cats').val(number);
			})				
		})
	})
}
wccdm_add_cat();


/**
 * Rimuove un abbinamento bene/ categoria
 */
var wccdm_remove_cat = function() {
	jQuery(function($){
		$(document).on('click', '.remove-cat-hover', function(response){
			var cat = $(this).closest('li');
			$(cat).remove();
			var number = $('.setup-cat').length;
			$('.wccdm-tot-cats').val(number);
		})
	})
}
wccdm_remove_cat();


/**
 * Funzionalità Sandbox
 */
var wccdm_sandbox = function() {
	jQuery(function($){

        var data, sandbox;
        var nonce = $('#wccdm-sandbox-nonce').attr('value');
        
        $(document).ready(function() {

            if ( 'wccdm-certificate' == $('.nav-tab.nav-tab-active').data('link') ) {

                if ( $('.wccdm-sandbox-field .tzCheckBox').hasClass( 'checked' ) ) {
                    $('#wccdm-certificate').hide();
                    $('#wccdm-sandbox-option').show();

                } else {
                    $('#wccdm-certificate').show();
                    $('#wccdm-sandbox-option').show();
                }

            }

        })

        $(document).on( 'click', '.wccdm-sandbox-field .tzCheckBox', function() {

            if ( $(this).hasClass( 'checked' ) ) {
                $('#wccdm-certificate').hide();
                sandbox = 1;
            } else {
                $('#wccdm-certificate').show('slow');
                sandbox = 0;
            }

            data = {
                'action': 'wccdm-sandbox',
                'sandbox': sandbox,
                'nonce': nonce
            }

            $.post(ajaxurl, data);

        })

    })
}
wccdm_sandbox();


/**
 * Menu di navigazione della pagina opzioni
 */
var wccdm_menu_navigation = function() {
	jQuery(function($){
		var contents = $('.wccdm-admin');
		var url = window.location.href.split("#")[0];
		var hash = window.location.href.split("#")[1];

		if(hash) {
	        contents.hide();		    
            
            if( 'wccdm-certificate' == hash ) {
                wccdm_sandbox();
            } else {
                $('#' + hash).fadeIn(200);		
            }

	        $('h2#wccdm-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $('h2#wccdm-admin-menu a').each(function(){
	        	if($(this).data('link') == hash) {
	        		$(this).addClass('nav-tab-active');
	        	}
	        })
	        
	        $('html, body').animate({
	        	scrollTop: 0
	        }, 'slow');
		}

		$("h2#wccdm-admin-menu a").click(function () {
	        var $this = $(this);
	        
	        contents.hide();
	        $("#" + $this.data("link")).fadeIn(200);

            if( 'wccdm-certificate' == $this.data("link") ) {
                $('#wccdm-sandbox-option').fadeIn(200);
            
                wccdm_sandbox();
            
            }
	        
            $('h2#wccdm-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $this.addClass('nav-tab-active');

	        window.location = url + '#' + $this.data('link');

	        $('html, body').scrollTop(0);

	    })

	})
}
wccdm_menu_navigation();

/**
 * Mostra i dettagli della mail all'utente
 * nel caso la funzione ordini in sospeso sia stata attivata
 *
 * @return void
 */
var wccdm_email_details = function() {
    jQuery(function($){
        $(document).ready(function() {

            var on_hold       = $('.wccdm-orders-on-hold');
            var email_details = $('.wccdm-email-details');

            if ( $('.tzCheckBox', on_hold).hasClass( 'checked' ) ) {
                $(email_details).show();
            }

            $('.tzCheckBox', on_hold).on( 'click', function() {

                if ( $(this).hasClass( 'checked' ) ) {
                    $(email_details).show('slow');
                } else {
                    $(email_details).hide();
                }

            })
            
        })
    })
}
wccdm_email_details();

/**
 * Attivazione opzione coupon con esclusione spese di spedizione
 *
 * @return void
 */
var wccdm_exclude_shipping = function() {

    jQuery(function($){
        $(document).ready(function() {

            var excludeShipping = $('.wccdm-exclude-shipping');
            var coupon          = $('.wccdm-coupon');

            $('.tzCheckBox', excludeShipping).on( 'click', function() {

                if ( $(this).hasClass( 'checked' ) && ! $('.tzCheckBox', coupon).hasClass( 'checked' ) ) {
                    $('.tzCheckBox', coupon).trigger('click');
                }

            })

            // Non disattivare opzione coupon con esclusione spese di spedizione attive
            $('.tzCheckBox', coupon).on( 'click', function() {

                if ( ! $(this).hasClass( 'checked' ) && $('.tzCheckBox', excludeShipping).hasClass( 'checked' ) ) {
                    alert( 'L\'esclusione delle spese di spedizione prevedere l\'utilizzo di questa funzionalità.' );
                    $('.tzCheckBox', coupon).trigger('click');
                }

            })
        })
    })

}
wccdm_exclude_shipping();
