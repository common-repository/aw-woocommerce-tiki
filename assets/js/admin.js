/* 
name: main admin jquery
version: 1.0.0
package: WooCommerce TIKI Shipping
*/
jQuery(function($) {
	
	// agenwebsite_tiki_admin_params is required to continue, ensure the object exists
	if ( typeof agenwebsite_tiki_admin_params === 'undefined' ) {
		return false;
	}
	
	jQuery(window).load(function($) {
	
		var aw_status_version = jQuery('#aw_status_version');
		var aw_status_version_help = jQuery('#aw_status_version_help');
	
		jQuery.ajax({
			url: agenwebsite_tiki_admin_params.ajax_url,
			type: "POST",
			dataType:"json",
			data:{ action: 'woocommerce_tiki_check_version', _wpnonce: agenwebsite_tiki_admin_params.tiki_admin_wpnonce },
			success: function( data ){
				if( data != 0 ){
					var mark = jQuery( aw_status_version ).find('mark')
					var status;
					if( data.result == 1 ){
						status = data.version;
					}else if( data.result == 0 ){
						jQuery( mark ).removeClass( 'yes' ).addClass( 'no' );
						jQuery( mark ).before( data.version );
						jQuery( aw_status_version_help ).find('a').attr('href', data.update_url);
						jQuery( aw_status_version_help ).show();
						status = ' - ' + data.latest_version + ' ' + agenwebsite_tiki_admin_params.i18n_is_available;
					}
					jQuery( mark ).text( status );
				}
			}
		});
        
        /**
         * Load status
         */
        jQuery.post( agenwebsite_tiki_admin_params.ajax_url, { action: 'woocommerce_tiki_check_status', license_code: agenwebsite_tiki_admin_params.license, _wpnonce: agenwebsite_tiki_admin_params.tiki_admin_wpnonce }, function(response){
            jQuery('#wc_tiki_status tbody').html(response.message);
        });
        
	});

	jQuery(document).ready(function($) {
		/*
		 * Add reset button
		 */
        if( agenwebsite_tiki_admin_params.tab !== 'tools' && agenwebsite_tiki_admin_params.tab !== 'shortcodes' ){
            $('#last_tab').before('<input name="reset_default" type="submit" value="Kembali ke Settingan Awal" class="button button_secondary" id="reset_default">');
        }else{
            $('input[type="submit"]').hide();
        }
        
		/*
		 * Event click reset default
		 */
		$("#reset_default").click(function(){
			var e = confirm( agenwebsite_tiki_admin_params.i18n_reset_default );
			return e?void 0:!1
		});

        jQuery('.premium-version').click(function(){
            alert("Pengaturan ini tidak berfungsi untuk free version. Jika Anda membutuhkan fungsi ini, anda harus mengupgrade ke full version.");
            return false;
        });

	});

});