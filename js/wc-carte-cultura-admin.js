/**
 * WC Carte Cultura - Admin js
 *
 * @author ilGhera
 * @package wc-carte-cultura/js
 *
 * @version 0.9.0
 */

/**
 * Ajax - Elimina il certificato caricato precedentemente
 */
var wccc_delete_certificate = function() {
	jQuery(function($){
		$('.wccc-delete-certificate').on('click', function(){
			var sure = confirm('Sei sicuro di voler eliminare il certificato?');
			if(sure) {
				var cert = $('.cert-loaded').text();
				var data = {
					'action': 'wccc-delete-certificate',
					'wccc-delete': true,
                    'delete-nonce': wcccData.delCertNonce,
					'cert': cert
				}			
				$.post(ajaxurl, data, function(response){
					location.reload();
				})
			}
		})	
	})
}
wccc_delete_certificate();


/**
 * Aggiunge un nuovo abbinamento bene/ categoria per il controllo in pagina di checkout
 */
var wccc_add_cat = function() {
	jQuery(function($){
		$('.add-cat-hover.wccc').on('click', function(){
			var number = $('.setup-cat').length + 1;

			/*Beni già impostati da escludere*/
			var beni_values = [];
			$('.wccc-field.beni').each(function(){
				beni_values.push($(this).val());
			})

			var data = {
				'action': 'wccc-add-cat',
				'number': number,
				'exclude-beni': beni_values.toString(),
                'add-cat-nonce': wcccData.addCatNonce,
			}
			$.post(ajaxurl, data, function(response){
				$(response).appendTo('.categories-container');
				$('.wccc-tot-cats').val(number);
			})				
		})
	})
}
wccc_add_cat();


/**
 * Rimuove un abbinamento bene/ categoria
 */
var wccc_remove_cat = function() {
	jQuery(function($){
		$(document).on('click', '.remove-cat-hover', function(response){
			var cat = $(this).closest('li');
			$(cat).remove();
			var number = $('.setup-cat').length;
			$('.wccc-tot-cats').val(number);
		})
	})
}
wccc_remove_cat();


/**
 * Funzionalità Sandbox
 */
var wccc_sandbox = function() {
	jQuery(function($){

        var data, sandbox;
        var nonce = $('#wccc-sandbox-nonce').attr('value');
        
        $(document).ready(function() {

            if ( 'wccc-certificate' == $('.nav-tab.nav-tab-active').data('link') ) {

                if ( $('.wccc-sandbox-field .tzCheckBox').hasClass( 'checked' ) ) {
                    $('#wccc-certificate').hide();
                    $('#wccc-sandbox-option').show();

                } else {
                    $('#wccc-certificate').show();
                    $('#wccc-sandbox-option').show();
                }

            }

        })

        $(document).on( 'click', '.wccc-sandbox-field .tzCheckBox', function() {

            if ( $(this).hasClass( 'checked' ) ) {
                $('#wccc-certificate').hide();
                sandbox = 1;
            } else {
                $('#wccc-certificate').show('slow');
                sandbox = 0;
            }

            data = {
                'action': 'wccc-sandbox',
                'sandbox': sandbox,
                'nonce': nonce
            }

            $.post(ajaxurl, data);

        })

    })
}
wccc_sandbox();


/**
 * Menu di navigazione della pagina opzioni
 */
var wccc_menu_navigation = function() {
	jQuery(function($){
		var contents = $('.wccc-admin');
		var url = window.location.href.split("#")[0];
		var hash = window.location.href.split("#")[1];

		if(hash) {
	        contents.hide();		    
            
            if( 'wccc-certificate' == hash ) {
                wccc_sandbox();
            } else {
                $('#' + hash).fadeIn(200);		
            }

	        $('h2#wccc-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $('h2#wccc-admin-menu a').each(function(){
	        	if($(this).data('link') == hash) {
	        		$(this).addClass('nav-tab-active');
	        	}
	        })
	        
	        $('html, body').animate({
	        	scrollTop: 0
	        }, 'slow');
		}

		$("h2#wccc-admin-menu a").click(function () {
	        var $this = $(this);
	        
	        contents.hide();
	        $("#" + $this.data("link")).fadeIn(200);

            if( 'wccc-certificate' == $this.data("link") ) {
                $('#wccc-sandbox-option').fadeIn(200);
            
                wccc_sandbox();
            
            }
	        
            $('h2#wccc-admin-menu a.nav-tab-active').removeClass("nav-tab-active");
	        $this.addClass('nav-tab-active');

	        window.location = url + '#' + $this.data('link');

	        $('html, body').scrollTop(0);

	    })

	})
}
wccc_menu_navigation();

/**
 * Mostra i dettagli della mail all'utente
 * nel caso la funzione ordini in sospeso sia stata attivata
 *
 * @return void
 */
var wccc_email_details = function() {
    jQuery(function($){
        $(document).ready(function() {

            var on_hold       = $('.wccc-orders-on-hold');
            var email_details = $('.wccc-email-details');

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
wccc_email_details();

/**
 * Attivazione opzione coupon con esclusione spese di spedizione
 *
 * @return void
 */
var wccc_exclude_shipping = function() {

    jQuery(function($){
        $(document).ready(function() {

            var excludeShipping = $('.wccc-exclude-shipping');
            var coupon          = $('.wccc-coupon');

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
wccc_exclude_shipping();
