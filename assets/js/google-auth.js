(function( settings, $ ){
	$( function() {
		var $container = $( '.google-api-auth' ),
			$authStep1 = $( '#auth-step-1' ),
			$authStep2 = $( '#auth-step-2' ),
			$authStep3 = $( '#auth-step-3' ),
			$notificationContainer = $( '#google-api-auth-notification-container' ),
			notificationsTemplate = wp.template( 'required-wp-top-content-notifications' ),
			$customAPICredentialsWrapper = $authStep1.find( '.custom-api-credentials' );

		// Toogle client ID/secret fields.
		$( '[name="api-credentials-type"]' ).on( 'change', function() {
			$customAPICredentialsWrapper.toggleClass( 'hidden', 'custom' !== this.value );
		});

		// Save auth type/data.
		$( '#save-auth-data' ).on( 'click', function() {
			var data = {}, request;

			$container.addClass( 'is-saving' );
			$notificationContainer.empty();

			_.each( $authStep1.serializeArray(), function( pair ) {
				data[ pair.name ] = pair.value;
			});

			request = wp.ajax.post( 'required-save-auth-data', data );

			request.done( function( response ) {
				$authStep1.addClass( 'hidden' );
				$authStep2.find( '#get-auth-code' ).attr( 'href', response.authUrl );
				$authStep2.removeClass( 'hidden' );
			});
			request.fail( function( response ) {
				var notifications = [];
				_.each( response, function( notification ) {
					notification.type = 'error';
					notifications.push( notification );
				});
				$notificationContainer.append( $.trim(
					notificationsTemplate( { notifications: notifications, altNotice: true } )
				) );
			});
			request.always( function() {
				$container.removeClass( 'is-saving' );
			});
		});

		$( '#return-to-step-1' ).on( 'click', function() {
			$authStep2.addClass( 'hidden' );
			$authStep1.removeClass( 'hidden' );
		});

		$( '#return-to-step-2' ).on( 'click', function() {
			$authStep3.addClass( 'hidden' );
			$authStep2.removeClass( 'hidden' );
		});

		// Open new window for auth code request.
		$( '#get-auth-code' ).on( 'click', function( event ) {
			event.preventDefault();

			var modalWidth = 500, modalHeight = 450,
				modalLeft, modalTop, windowFeatures;

			modalLeft = screen.width / 2 - modalWidth / 2;
			modalTop = screen.height / 2 - modalHeight / 2;
			windowFeatures = 'location=yes,height=' + modalHeight + ',width=' + modalWidth + ',scrollbars=yes,status=yes,left=' + modalLeft + ',top=' + modalTop;

			window.open( $( this ).attr( 'href' ), 'GoogleAuthCodeRequest', windowFeatures );
		});

		// Authorize with auth code.
		$( '#authorize' ).on( 'click', function() {
			var data = {}, request;

			$container.addClass( 'is-saving' );
			$notificationContainer.empty();

			_.each( $authStep2.serializeArray(), function( pair ) {
				data[ pair.name ] = pair.value;
			});

			request = wp.ajax.post( 'required-google-authorize', data );

			request.done( function( response ) {
				$authStep2.addClass( 'hidden' );
				$authStep3.find( '.google-analytics-profiles select' ).replaceWith( response );
				$authStep3.removeClass( 'hidden' );
			});
			request.fail( function( response ) {
				var notifications = [];
				_.each( response, function( notification ) {
					notification.type = 'error';
					notifications.push( notification );
				});
				$notificationContainer.append( $.trim(
					notificationsTemplate( { notifications: notifications, altNotice: true } )
				) );
			});
			request.always( function() {
				$container.removeClass( 'is-saving' );
			});
		});
	});
})( window._requiredGoogleAuthSettings, jQuery );
