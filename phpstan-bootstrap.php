<?php
/**
 * PHPStan bootstrap file to define WordPress functions and constants.
 *
 * @package WeatherBlock
 */

// Define WordPress constants that PHPStan needs to know about.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/path/to/wordpress/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', true );
}

// Define common WordPress functions that PHPStan needs to know about.
if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Mock add_action function for PHPStan.
	 *
	 * @param string   $hook_name The name of the action to add the callback to.
	 * @param callable $callback  The callback to be run when the action is called.
	 * @param int      $priority  The priority of the callback.
	 * @param int      $accepted_args The number of arguments the callback accepts.
	 */
	function add_action( string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		// Mock implementation.
	}
}

if ( ! function_exists( 'register_block_type' ) ) {
	/**
	 * Mock register_block_type function for PHPStan.
	 *
	 * @param string $block_type Block type name or path to block.json.
	 * @param array  $args       Block type arguments.
	 * @return mixed
	 */
	function register_block_type( string $block_type, array $args = array() ) {
		// Mock implementation - parameters are intentionally unused.
		unset( $block_type, $args );
		return null;
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Mock translation function for PHPStan.
	 *
	 * @param string $text   Text to translate.
	 * @param string $domain Text domain.
	 * @return string
	 */
	function __( string $text, string $domain = 'default' ): string {
		// Domain parameter is intentionally unused in mock.
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * Mock esc_html function for PHPStan.
	 *
	 * @param string $text Text to escape.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	/**
	 * Mock wp_enqueue_script function for PHPStan.
	 *
	 * @param string $handle Script handle.
	 * @param string $src    Script source.
	 * @param array  $deps   Dependencies.
	 * @param string $ver    Version.
	 * @param bool   $in_footer Whether to enqueue in footer.
	 */
	function wp_enqueue_script( string $handle, string $src = '', array $deps = array(), string $ver = '', bool $in_footer = false ): void {
		// Mock implementation.
	}
}
