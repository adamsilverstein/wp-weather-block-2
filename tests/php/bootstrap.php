<?php
/**
 * PHPUnit bootstrap file for Weather Block tests.
 *
 * @package WeatherBlock
 */

// Define test environment constants.
define( 'WEATHER_BLOCK_TESTS', true );
define( 'ABSPATH', '/tmp/wordpress/' );
define( 'WP_DEBUG', true );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'WEATHER_BLOCK_VERSION', '0.1.0' );
define( 'WEATHER_BLOCK_API_KEY', 'test_api_key' );

// Load Composer autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Load the WeatherApi class.
require_once dirname( __DIR__, 2 ) . '/includes/WeatherApi.php';

// Load the Settings class.
require_once dirname( __DIR__, 2 ) . '/includes/Settings.php';

/**
 * Mock WordPress functions for testing.
 */

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock translation function.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		return $text;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * Mock get_transient function.
	 *
	 * @param string $transient Transient name.
	 * @return mixed
	 */
	function get_transient( string $transient ) {
		return false; // Always return false for testing.
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Mock set_transient function.
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Transient value.
	 * @param int    $expiration Expiration time.
	 * @return bool
	 */
	function set_transient( string $transient, $value, int $expiration ): bool {
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * Mock delete_transient function.
	 *
	 * @param string $transient Transient name.
	 * @return bool
	 */
	function delete_transient( string $transient ): bool {
		return true;
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Mock add_query_arg function.
	 *
	 * @param array  $args Query arguments.
	 * @param string $url  Base URL.
	 * @return string
	 */
	function add_query_arg( array $args, string $url ): string {
		return $url . '?' . http_build_query( $args );
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * Mock wp_remote_get function.
	 *
	 * @param string $url  URL to fetch.
	 * @param array  $args Request arguments.
	 * @return array|WP_Error
	 */
	function wp_remote_get( string $url, array $args = [] ) {
		// Return mock error for testing.
		return new WP_Error( 'http_request_failed', 'Mock error for testing' );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * Mock wp_remote_retrieve_body function.
	 *
	 * @param array $response HTTP response.
	 * @return string
	 */
	function wp_remote_retrieve_body( array $response ): string {
		return '';
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Mock is_wp_error function.
	 *
	 * @param mixed $thing Variable to check.
	 * @return bool
	 */
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Mock sanitize_text_field function.
	 *
	 * @param string $str String to sanitize.
	 * @return string
	 */
	function sanitize_text_field( string $str ): string {
		return trim( strip_tags( $str ) );
	}
}

if ( ! function_exists( 'error_log' ) ) {
	/**
	 * Mock error_log function.
	 *
	 * @param string $message Error message.
	 * @return bool
	 */
	function error_log( string $message ): bool {
		return true;
	}
}

/**
 * Mock WP_Error class for testing.
 */
if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Class WP_Error
	 */
	class WP_Error {
		/**
		 * Error code.
		 *
		 * @var string
		 */
		private string $code;

		/**
		 * Error message.
		 *
		 * @var string
		 */
		private string $message;

		/**
		 * Error data.
		 *
		 * @var array
		 */
		private array $data;

		/**
		 * Constructor.
		 *
		 * @param string $code    Error code.
		 * @param string $message Error message.
		 * @param array  $data    Error data.
		 */
		public function __construct( string $code, string $message, array $data = [] ) {
			$this->code = $code;
			$this->message = $message;
			$this->data = $data;
		}

		/**
		 * Get error code.
		 *
		 * @return string
		 */
		public function get_error_code(): string {
			return $this->code;
		}

		/**
		 * Get error message.
		 *
		 * @return string
		 */
		public function get_error_message(): string {
			return $this->message;
		}

		/**
		 * Get error data.
		 *
		 * @return array
		 */
		public function get_error_data(): array {
			return $this->data;
		}
	}
}
