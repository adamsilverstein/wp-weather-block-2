/**
 * Weather Block Admin Settings JavaScript
 * @param {Object} $ - jQuery object
 */

/* global weatherBlockAdmin, jQuery */

( function ( $ ) {
	'use strict';

	/**
	 * Settings page functionality
	 */
	const WeatherBlockSettings = {
		/**
		 * Initialize the settings page
		 */
		init() {
			this.bindEvents();
		},

		/**
		 * Bind event handlers
		 */
		bindEvents() {
			// Toggle API key visibility
			$( '#weather-block-toggle-visibility' ).on(
				'click',
				this.toggleApiKeyVisibility
			);

			// Test API key
			$( '#weather-block-test-api' ).on( 'click', this.testApiKey );

			// Auto-test API key when form is submitted
			$( 'form' ).on( 'submit', this.onFormSubmit );
		},

		/**
		 * Toggle API key field visibility
		 * @param {Event} e - The click event
		 */
		toggleApiKeyVisibility( e ) {
			e.preventDefault();

			const $button = $( this );
			const $input = $( '#weather_block_api_key' );
			const $icon = $button.find( '.dashicons' );
			const isPassword = $input.attr( 'type' ) === 'password';

			if ( isPassword ) {
				$input.attr( 'type', 'text' );
				$icon
					.removeClass( 'dashicons-visibility' )
					.addClass( 'dashicons-hidden' );
				$button.attr( 'title', weatherBlockAdmin.strings.hideKey );
			} else {
				$input.attr( 'type', 'password' );
				$icon
					.removeClass( 'dashicons-hidden' )
					.addClass( 'dashicons-visibility' );
				$button.attr( 'title', weatherBlockAdmin.strings.showKey );
			}
		},

		/**
		 * Test the API key
		 * @param {Event} e - The click event
		 */
		testApiKey( e ) {
			e.preventDefault();

			const $button = $( this );

			// Disable button and show loading state
			$button.prop( 'disabled', true ).addClass( 'testing' );
			$button.text( weatherBlockAdmin.strings.testing );

			// Make AJAX request
			$.ajax( {
				url: weatherBlockAdmin.ajaxUrl,
				type: 'POST',
				data: {
					action: 'weather_block_test_api_key',
					nonce: weatherBlockAdmin.nonce,
				},
				success( response ) {
					if ( response.success ) {
						WeatherBlockSettings.showStatus(
							'success',
							response.data.message,
							response.data.data
						);
					} else {
						WeatherBlockSettings.showStatus(
							'error',
							response.data.message
						);
					}
				},
				error() {
					WeatherBlockSettings.showStatus(
						'error',
						weatherBlockAdmin.strings.testError
					);
				},
				complete() {
					// Re-enable button and remove loading state
					$button.prop( 'disabled', false ).removeClass( 'testing' );
					$button.text(
						$button.data( 'original-text' ) ||
							weatherBlockAdmin.strings.testApi ||
							'Test API Key'
					);
				},
			} );
		},

		/**
		 * Show status message
		 * @param {string} type    - The status type (success, warning, error)
		 * @param {string} message - The status message
		 * @param {Object} data    - Optional additional data
		 */
		showStatus( type, message, data ) {
			const $status = $( '#weather-block-api-status' );
			let iconClass = 'dashicons-warning';
			let statusClass = 'weather-block-status--error';

			switch ( type ) {
				case 'success':
					iconClass = 'dashicons-yes-alt';
					statusClass = 'weather-block-status--success';
					break;
				case 'warning':
					iconClass = 'dashicons-warning';
					statusClass = 'weather-block-status--warning';
					break;
				case 'error':
				default:
					iconClass = 'dashicons-warning';
					statusClass = 'weather-block-status--error';
					break;
			}

			let html = `
				<p class="weather-block-status ${ statusClass }">
					<span class="dashicons ${ iconClass }"></span>
					${ message }
				</p>
			`;

			// Add additional data if available
			if ( data && type === 'success' ) {
				html += `
					<div class="weather-block-test-result" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px; font-size: 12px;">
						<strong>Test Result:</strong><br>
						Location: ${ data.location }<br>
						Temperature: ${ Math.round( data.temperature ) }°${
							data.units === 'metric' ? 'C' : 'F'
						}
					</div>
				`;
			}

			$status.html( html );
		},

		/**
		 * Handle form submission
		 */
		onFormSubmit() {
			// Store original button text for later restoration
			const $testButton = $( '#weather-block-test-api' );
			if ( ! $testButton.data( 'original-text' ) ) {
				$testButton.data( 'original-text', $testButton.text() );
			}
		},
	};

	/**
	 * Initialize when document is ready
	 */
	$( document ).ready( function () {
		WeatherBlockSettings.init();
	} );
} )( jQuery );
