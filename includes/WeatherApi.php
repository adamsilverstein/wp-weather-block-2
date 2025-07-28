<?php
/**
 * Weather API handler class.
 *
 * @package WeatherBlock
 */

namespace WeatherBlock;

/**
 * Class WeatherApi
 *
 * Handles all interactions with the OpenWeatherMap API.
 *
 * @since 0.1.0
 */
class WeatherApi {

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	private const API_BASE_URL = 'https://api.openweathermap.org/data/2.5/weather';

	/**
	 * Cache expiration time in seconds (15 minutes).
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = 15 * MINUTE_IN_SECONDS;

	/**
	 * API key for OpenWeatherMap.
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $api_key The OpenWeatherMap API key.
	 */
	public function __construct( string $api_key ) {
		$this->api_key = $api_key;
	}

	/**
	 * Get weather data for a location.
	 *
	 * @since 0.1.0
	 *
	 * @param string $location The location to get weather for.
	 * @param string $units    The units to use (metric or imperial).
	 * @return array|WP_Error Weather data array or WP_Error on failure.
	 */
	public function get_weather_data( string $location, string $units = 'metric' ) {
		// Validate inputs.
		if ( empty( $location ) ) {
			return new \WP_Error(
				'invalid_location',
				__( 'Location cannot be empty.', 'weather-block' ),
				array( 'status' => 400 )
			);
		}

		if ( ! in_array( $units, array( 'metric', 'imperial' ), true ) ) {
			return new \WP_Error(
				'invalid_units',
				__( 'Units must be either "metric" or "imperial".', 'weather-block' ),
				array( 'status' => 400 )
			);
		}

		// Check if API key is configured.
		if ( empty( $this->api_key ) || 'your_openweathermap_api_key_here' === $this->api_key ) {
			return new \WP_Error(
				'missing_api_key',
				__( 'Weather API key is not configured.', 'weather-block' ),
				array( 'status' => 500 )
			);
		}

		// Try to get cached data first.
		$cache_key = $this->get_cache_key( $location, $units );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Fetch fresh data from API.
		$api_response = $this->fetch_from_api( $location, $units );

		if ( is_wp_error( $api_response ) ) {
			return $api_response;
		}

		// Process and cache the data.
		$weather_data = $this->process_api_response( $api_response, $units );

		if ( is_wp_error( $weather_data ) ) {
			return $weather_data;
		}

		// Cache the processed data.
		set_transient( $cache_key, $weather_data, self::CACHE_EXPIRATION );

		return $weather_data;
	}

	/**
	 * Fetch data from the OpenWeatherMap API.
	 *
	 * @since 0.1.0
	 *
	 * @param string $location The location to fetch weather for.
	 * @param string $units    The units to use.
	 * @return array|WP_Error Raw API response or WP_Error on failure.
	 */
	private function fetch_from_api( string $location, string $units ) {
		$api_url = add_query_arg(
			array(
				'q'     => $location,
				'appid' => $this->api_key,
				'units' => $units,
			),
			self::API_BASE_URL
		);

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'WordPress Weather Block Plugin/' . WEATHER_BLOCK_VERSION,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Weather Block API Error: ' . $response->get_error_message() );
			return new \WP_Error(
				'api_request_failed',
				__( 'Could not fetch weather data. Please try again later.', 'weather-block' ),
				array( 'status' => 500 )
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( empty( $data ) ) {
			error_log( 'Weather Block API Error: Empty response body' );
			return new \WP_Error(
				'empty_response',
				__( 'Received empty response from weather API.', 'weather-block' ),
				array( 'status' => 500 )
			);
		}

		if ( isset( $data['cod'] ) && 200 !== (int) $data['cod'] ) {
			$error_message = isset( $data['message'] ) ? $data['message'] : __( 'Unknown API error', 'weather-block' );
			error_log( 'Weather Block API Error: ' . $error_message );

			return new \WP_Error(
				'api_error',
				__( 'Could not fetch weather data. Please check the location and try again.', 'weather-block' ),
				array( 'status' => 400 )
			);
		}

		return $data;
	}

	/**
	 * Process the raw API response into a standardized format.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $api_data Raw API response data.
	 * @param string $units    The units used for the request.
	 * @return array|WP_Error Processed weather data or WP_Error on failure.
	 */
	private function process_api_response( array $api_data, string $units ) {
		// Validate required fields.
		$required_fields = array( 'name', 'sys', 'main', 'weather' );
		foreach ( $required_fields as $field ) {
			if ( ! isset( $api_data[ $field ] ) ) {
				error_log( "Weather Block API Error: Missing required field '{$field}'" );
				return new \WP_Error(
					'invalid_api_response',
					__( 'Invalid response from weather API.', 'weather-block' ),
					array( 'status' => 500 )
				);
			}
		}

		// Validate nested required fields.
		if ( ! isset( $api_data['sys']['country'] ) ) {
			error_log( 'Weather Block API Error: Missing country in sys data' );
			return new \WP_Error(
				'invalid_api_response',
				__( 'Invalid response from weather API.', 'weather-block' ),
				array( 'status' => 500 )
			);
		}

		if ( ! isset( $api_data['main']['temp'], $api_data['main']['humidity'] ) ) {
			error_log( 'Weather Block API Error: Missing temperature or humidity in main data' );
			return new \WP_Error(
				'invalid_api_response',
				__( 'Invalid response from weather API.', 'weather-block' ),
				array( 'status' => 500 )
			);
		}

		if ( ! isset( $api_data['weather'][0]['description'], $api_data['weather'][0]['icon'] ) ) {
			error_log( 'Weather Block API Error: Missing weather description or icon' );
			return new \WP_Error(
				'invalid_api_response',
				__( 'Invalid response from weather API.', 'weather-block' ),
				array( 'status' => 500 )
			);
		}

		// Process and sanitize the data.
		return array(
			'location'    => sanitize_text_field( $api_data['name'] ),
			'country'     => sanitize_text_field( $api_data['sys']['country'] ),
			'temperature' => (float) $api_data['main']['temp'],
			'description' => sanitize_text_field( $api_data['weather'][0]['description'] ),
			'icon'        => sanitize_text_field( $api_data['weather'][0]['icon'] ),
			'humidity'    => (int) $api_data['main']['humidity'],
			'units'       => $units,
			'timestamp'   => time(),
		);
	}

	/**
	 * Generate a cache key for the given location and units.
	 *
	 * @since 0.1.0
	 *
	 * @param string $location The location.
	 * @param string $units    The units.
	 * @return string The cache key.
	 */
	private function get_cache_key( string $location, string $units ): string {
		return 'weather_block_' . md5( strtolower( trim( $location ) ) . $units );
	}

	/**
	 * Clear cached weather data for a specific location and units.
	 *
	 * @since 0.1.0
	 *
	 * @param string $location The location.
	 * @param string $units    The units.
	 * @return bool True on success, false on failure.
	 */
	public function clear_cache( string $location, string $units = 'metric' ): bool {
		$cache_key = $this->get_cache_key( $location, $units );
		return delete_transient( $cache_key );
	}

	/**
	 * Clear all cached weather data.
	 *
	 * @since 0.1.0
	 *
	 * @return int Number of cache entries cleared.
	 */
	public function clear_all_cache(): int {
		global $wpdb;

		$result = $wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_weather_block_%'
			OR option_name LIKE '_transient_timeout_weather_block_%'"
		);

		return (int) $result;
	}

	/**
	 * Get weather icon URL from OpenWeatherMap.
	 *
	 * @since 0.1.0
	 *
	 * @param string $icon_code The icon code from the API.
	 * @param string $size      The icon size (2x, 4x).
	 * @return string The icon URL.
	 */
	public static function get_icon_url( string $icon_code, string $size = '2x' ): string {
		$icon_code = sanitize_text_field( $icon_code );
		$size = in_array( $size, array( '2x', '4x' ), true ) ? $size : '2x';

		return "https://openweathermap.org/img/wn/{$icon_code}@{$size}.png";
	}
}
