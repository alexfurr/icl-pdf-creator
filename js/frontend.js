

var ICLPDF = {
    
    init: function () {
        this.add_listeners();
    },
    
    add_listeners: function () {
        jQuery('#make_pdf').on( 'click', function ( e ) {
            ICLPDF.make_pdf();
        });
    },
    
    make_pdf: function () {
        var onSuccess = function ( response, status ) {
            console.log( 'onSuccess' );
            jQuery("#PDF_downloadFeedback").empty().append( response );
            jQuery('#pdf_request_spinner').empty();
        };
        var onError = function ( jqXHR, status, error ) {
            console.log( 'onError' );
            jQuery('#PDF_downloadFeedback').empty().append('Sorry, there was a problem.');
            jQuery('#pdf_request_spinner').empty();
        };
        var info = {
            site:   ICLPDF_SITE_ID,
            user:   ICLPDF_USER_ID,
            include_user_notes:   jQuery('#pdf_include_notes').is( ":checked" ),
        };
        
        jQuery('#pdf_request_spinner').empty().append('<div class="waitingDiv"></div>');
        ICLPDF.request( info, 'iclpdf_make_pdf', onSuccess, onError );
    },
    
    request: function ( info, action, onSuccess, onError ) {
        var data = { 
			'action':	action, 
			'info':		info
		};
		jQuery.ajax({
			type: 		"POST",
			data: 		data,
			url: 		iclpdf_ajax.ajaxurl,
			success: function( response, status ) {
				onSuccess( response, status );
                console.log( response );
			},
			error: function ( jqXHR, status, error ) {
				onError( jqXHR, status, error );
                console.log( error );
			}
		});
	}
};



jQuery( document ).ready( function () {

    ICLPDF.init();

});


