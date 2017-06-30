( function ( window, document, $, undefined ) {

	var EDDUH = {

		init: function () {
			EDDUH.trackHistory();
		},

		trackHistory: function () {
			$.ajax({
				type: "POST",
				url: edduh.ajaxUrl,
				data: {
					action: 'edduh_track_history',
					page_url: edduh.currentUrl,
					referrer: document.referrer
				},
				success: function ( response ) {
					// if ( window.console && window.console.log ) {
					// 	console.log( response );
					// }
				}

			}).fail( function ( response ) {
				// if ( window.console && window.console.log ) {
				// 	console.log( response );
				// }
			});
		}
	};

	EDDUH.init();

})( window, document, jQuery );
