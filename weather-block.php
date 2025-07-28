<?php
/**
 * Plugin Name:       Weather Block
 * Description:       A WordPress block plugin that displays current weather conditions for user-specified locations.
 * Version:           0.1.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       weather-block
 * Domain Path:       /languages
 *
 * @package WeatherBlock
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'WEATHER_BLOCK_VERSION', '0.1.0' );
define( 'WEATHER_BLOCK_PLUGIN_FILE', __FILE__ );
define( 'WEATHER_BLOCK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEATHER_BLOCK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// For testing purposes, define the API key here.
// In a real plugin, this would be in wp-config.php or a settings page.
if ( ! defined( 'WEATHER_BLOCK_API_KEY' ) ) {
	define( 'WEATHER_BLOCK_API_KEY', 'your_openweathermap_api_key_here' );
}

// Autoload classes.
if ( file_exists( WEATHER_BLOCK_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
	require_once WEATHER_BLOCK_PLUGIN_DIR . 'vendor/autoload.php';
}

// Load plugin classes.
require_once WEATHER_BLOCK_PLUGIN_DIR . 'includes/WeatherApi.php';
require_once WEATHER_BLOCK_PLUGIN_DIR . 'includes/Settings.php';

/**
 * Initialize the Weather Block plugin.
 *
 * @since 0.1.0
 */
function weather_block_init(): void {
	// Load text domain for internationalization.
	load_plugin_textdomain(
		'weather-block',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);

	// Register the block.
	register_block_type(
		WEATHER_BLOCK_PLUGIN_DIR . 'build/weather-block',
		array(
			'render_callback' => 'weather_block_render_callback',
		)
	);

	// Register REST API endpoints for weather data.
	weather_block_register_rest_routes();

	// Initialize settings page.
	if ( is_admin() ) {
		$settings = new WeatherBlock\Settings();
		$settings->init();
	}
}
add_action( 'init', 'weather_block_init' );

/**
 * Register REST API routes for weather data.
 *
 * @since 0.1.0
 */
function weather_block_register_rest_routes(): void {
	register_rest_route(
		'weather-block/v1',
		'/weather/(?P<location>[a-zA-Z0-9-]+)',
		array(
			'methods'             => 'GET',
			'callback'            => 'weather_block_get_weather_data',
			'permission_callback' => 'weather_block_permissions_check',
			'args'                => array(
				'location' => array(
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'weather_block_validate_location',
				),
				'units'    => array(
					'default'           => 'metric',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => 'weather_block_validate_units',
				),
			),
		)
	);
}

/**
 * Check permissions for weather API access.
 *
 * @since 0.1.0
 *
 * @param WP_REST_Request $request The REST request object.
 * @return bool True if user has permission, false otherwise.
 */
function weather_block_permissions_check( WP_REST_Request $request ): bool {
	// Allow access for users who can edit posts (editors and above).
	return current_user_can( 'edit_posts' );
}

/**
 * Validate location parameter.
 *
 * @since 0.1.0
 *
 * @param string $param The location parameter.
 * @return bool True if valid, false otherwise.
 */
function weather_block_validate_location( string $param ): bool {
	return ! empty( $param ) && strlen( $param ) <= 100;
}

/**
 * Validate units parameter.
 *
 * @since 0.1.0
 *
 * @param string $param The units parameter.
 * @return bool True if valid, false otherwise.
 */
function weather_block_validate_units( string $param ): bool {
	return in_array( $param, array( 'metric', 'imperial' ), true );
}

/**
 * Get weather data from API.
 *
 * @since 0.1.0
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response|WP_Error The weather data or error.
 */
function weather_block_get_weather_data( WP_REST_Request $request ) {
	$location = $request->get_param( 'location' );
	$units    = $request->get_param( 'units' );

	// Verify nonce for security.
	if ( ! wp_verify_nonce( $request->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
		return new WP_Error(
			'invalid_nonce',
			__( 'Invalid security token.', 'weather-block' ),
			array( 'status' => 403 )
		);
	}

	// Create weather API instance.
	$api_key     = WeatherBlock\Settings::get_effective_api_key();
	$weather_api = new WeatherBlock\WeatherApi( $api_key );

	// Get weather data.
	$weather_data = $weather_api->get_weather_data( $location, $units );

	if ( is_wp_error( $weather_data ) ) {
		return $weather_data;
	}

	return rest_ensure_response( $weather_data );
}

/**
 * Render callback for the weather block.
 *
 * @since 0.1.0
 *
 * @param array $attributes Block attributes.
 * @return string The rendered block HTML.
 */
function weather_block_render_callback( array $attributes ): string {
	// If no location is set, return empty string.
	if ( empty( $attributes['location'] ) ) {
		return '';
	}

	// Get weather data.
	$location     = sanitize_text_field( $attributes['location'] );
	$units        = isset( $attributes['units'] ) ? sanitize_text_field( $attributes['units'] ) : 'metric';
	$display_mode = isset( $attributes['displayMode'] ) ? sanitize_text_field( $attributes['displayMode'] ) : 'auto';

	// Create weather API instance and get data.
	$api_key      = WeatherBlock\Settings::get_effective_api_key();
	$weather_api  = new WeatherBlock\WeatherApi( $api_key );
	$weather_data = $weather_api->get_weather_data( $location, $units );

	if ( is_wp_error( $weather_data ) ) {
		return '<div class="weather-block weather-block--error">' .
			esc_html( $weather_data->get_error_message() ) .
			'</div>';
	}

	if ( empty( $weather_data ) ) {
		return '<div class="weather-block weather-block--error">' .
			esc_html__( 'Weather data is loading...', 'weather-block' ) .
			'</div>';
	}

	// Build the HTML output.
	$temperature_unit = 'metric' === $units ? '°C' : '°F';
	$icon_url         = 'https://openweathermap.org/img/wn/' . esc_attr( $weather_data['icon'] ) . '@2x.png';

	$html = sprintf(
		'<div class="weather-block weather-block--theme-%s" data-location="%s">
			<div class="weather-block__header">
				<h3 class="weather-block__location">%s, %s</h3>
			</div>
			<div class="weather-block__content">
				<div class="weather-block__temperature">
					<img src="%s" alt="%s" class="weather-block__icon" />
					<span class="weather-block__temp">%s%s</span>
				</div>
				<div class="weather-block__details">
					<p class="weather-block__description">%s</p>
					<p class="weather-block__humidity">%s: %d%%</p>
				</div>
			</div>
		</div>',
		esc_attr( $display_mode ),
		esc_attr( $location ),
		esc_html( $weather_data['location'] ),
		esc_html( $weather_data['country'] ),
		esc_url( $icon_url ),
		esc_attr( $weather_data['description'] ),
		esc_html( number_format( $weather_data['temperature'], 1 ) ),
		esc_html( $temperature_unit ),
		esc_html( ucfirst( $weather_data['description'] ) ),
		esc_html__( 'Humidity', 'weather-block' ),
		(int) $weather_data['humidity']
	);

	return $html;
}

/**
 * Enqueue block assets for the frontend.
 *
 * @since 0.1.0
 */
function weather_block_enqueue_assets(): void {
	// Enqueue frontend styles.
	wp_enqueue_style(
		'weather-block-style',
		WEATHER_BLOCK_PLUGIN_URL . 'build/weather-block/style-index.css',
		array(),
		WEATHER_BLOCK_VERSION
	);

	// Enqueue frontend script if it exists.
	$script_path = WEATHER_BLOCK_PLUGIN_DIR . 'build/weather-block/view.js';
	if ( file_exists( $script_path ) ) {
		wp_enqueue_script(
			'weather-block-view',
			WEATHER_BLOCK_PLUGIN_URL . 'build/weather-block/view.js',
			array(),
			WEATHER_BLOCK_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'weather_block_enqueue_assets' );

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 */
function weather_block_activate(): void {
	// Flush rewrite rules to ensure REST API endpoints are available.
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'weather_block_activate' );

/**
 * Plugin deactivation hook.
 *
 * @since 0.1.0
 */
function weather_block_deactivate(): void {
	// Clean up transients.
	global $wpdb;
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_weather_block_%'" );
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_weather_block_%'" );

	// Flush rewrite rules.
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'weather_block_deactivate' );
