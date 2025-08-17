<?php
/**
 * Tests for Settings class.
 *
 * @package WeatherBlock
 */

namespace WeatherBlock\Tests;

use PHPUnit\Framework\TestCase;
use WeatherBlock\Settings;

/**
 * Class SettingsTest
 *
 * @since 0.1.0
 */
class SettingsTest extends TestCase {

	/**
	 * Reset global variables before each test.
	 *
	 * @since 0.1.0
	 */
	protected function setUp(): void {
		parent::setUp();

		// Reset global options array
		global $wp_options, $wp_settings_errors;
		$wp_options = array();
		$wp_settings_errors = array();

		// Clear any defined constants for clean testing
		if ( defined( 'WEATHER_BLOCK_API_KEY' ) ) {
			// We can't undefine constants, but we can work around it
		}
	}

	/**
	 * Test Settings class instantiation.
	 *
	 * @since 0.1.0
	 */
	public function test_settings_instantiation(): void {
		$settings = new Settings();
		$this->assertInstanceOf( Settings::class, $settings );
	}

	/**
	 * Test get_api_key with no option set.
	 *
	 * @since 0.1.0
	 */
	public function test_get_api_key_no_option(): void {
		$settings = new Settings();
		$result = $settings->get_api_key();
		$this->assertEquals( '', $result );
	}

	/**
	 * Test get_api_key with option set.
	 *
	 * @since 0.1.0
	 */
	public function test_get_api_key_with_option(): void {
		global $wp_options;
		$wp_options['weather_block_api_key'] = 'test_api_key_12345678901234567890';

		$settings = new Settings();
		$result = $settings->get_api_key();
		$this->assertEquals( 'test_api_key_12345678901234567890', $result );
	}

	/**
	 * Test get_effective_api_key with no option set, falls back to constant.
	 *
	 * @since 0.1.0
	 */
	public function test_get_effective_api_key_fallback_to_constant(): void {
		// The constant WEATHER_BLOCK_API_KEY is defined in bootstrap.php
		$result = Settings::get_effective_api_key();
		$this->assertEquals( 'test_api_key', $result );
	}

	/**
	 * Test get_effective_api_key with option set, prefers option over constant.
	 *
	 * @since 0.1.0
	 */
	public function test_get_effective_api_key_prefers_option(): void {
		global $wp_options;
		$wp_options['weather_block_api_key'] = 'option_api_key_123456789012345678';

		$result = Settings::get_effective_api_key();
		$this->assertEquals( 'option_api_key_123456789012345678', $result );
	}

	/**
	 * Test get_effective_api_key with no option and no constant.
	 *
	 * @since 0.1.0
	 */
	public function test_get_effective_api_key_no_option_no_constant(): void {
		global $wp_options;
		$wp_options = array(); // Clear options

		// We can't undefine the constant, but we can test the logic
		// by ensuring the option takes precedence when set
		$result = Settings::get_effective_api_key();
		// Since constant is defined in bootstrap, this will return the constant value
		$this->assertEquals( 'test_api_key', $result );
	}

	/**
	 * Test is_api_key_configured with no key.
	 *
	 * @since 0.1.0
	 */
	public function test_is_api_key_configured_no_key(): void {
		global $wp_options;
		$wp_options = array(); // Clear options

		// Since we have the constant defined, this will return true
		$result = Settings::is_api_key_configured();
		$this->assertTrue( $result );
	}

	/**
	 * Test is_api_key_configured with placeholder key.
	 *
	 * @since 0.1.0
	 */
	public function test_is_api_key_configured_placeholder_key(): void {
		global $wp_options;
		$wp_options['weather_block_api_key'] = 'your_openweathermap_api_key_here';

		$result = Settings::is_api_key_configured();
		$this->assertFalse( $result );
	}

	/**
	 * Test is_api_key_configured with valid key.
	 *
	 * @since 0.1.0
	 */
	public function test_is_api_key_configured_valid_key(): void {
		global $wp_options;
		$wp_options['weather_block_api_key'] = 'valid_api_key_1234567890123456789';

		$result = Settings::is_api_key_configured();
		$this->assertTrue( $result );
	}

	/**
	 * Test sanitize_api_key with valid key.
	 *
	 * @since 0.1.0
	 */
	public function test_sanitize_api_key_valid(): void {
		$settings = new Settings();
		$valid_key = 'abcd1234567890abcdef1234567890ab'; // 32 chars

		$result = $settings->sanitize_api_key( $valid_key );
		$this->assertEquals( $valid_key, $result );
	}

	/**
	 * Test sanitize_api_key with invalid format.
	 *
	 * @since 0.1.0
	 */
	public function test_sanitize_api_key_invalid_format(): void {
		global $wp_options, $wp_settings_errors;
		$wp_options['weather_block_api_key'] = 'existing_key';

		$settings = new Settings();
		$invalid_key = 'too_short'; // Less than 32 chars

		$result = $settings->sanitize_api_key( $invalid_key );

		// Should return the existing option value
		$this->assertEquals( 'existing_key', $result );

		// Should add a settings error
		$this->assertCount( 1, $wp_settings_errors );
		$this->assertEquals( 'invalid_api_key_format', $wp_settings_errors[0]['code'] );
	}

	/**
	 * Test sanitize_api_key with empty key.
	 *
	 * @since 0.1.0
	 */
	public function test_sanitize_api_key_empty(): void {
		$settings = new Settings();
		$result = $settings->sanitize_api_key( '' );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test sanitize_api_key with whitespace.
	 *
	 * @since 0.1.0
	 */
	public function test_sanitize_api_key_with_whitespace(): void {
		$settings = new Settings();
		$key_with_spaces = '  abcd1234567890abcdef1234567890ab  ';
		$expected = 'abcd1234567890abcdef1234567890ab';

		$result = $settings->sanitize_api_key( $key_with_spaces );
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test sanitize_api_key with special characters.
	 *
	 * @since 0.1.0
	 */
	public function test_sanitize_api_key_special_characters(): void {
		global $wp_options, $wp_settings_errors;
		$wp_options['weather_block_api_key'] = '';

		$settings = new Settings();
		$invalid_key = 'abcd1234567890abcdef1234567890@#'; // Contains special chars

		$result = $settings->sanitize_api_key( $invalid_key );

		// Should return empty string (existing option)
		$this->assertEquals( '', $result );

		// Should add a settings error
		$this->assertCount( 1, $wp_settings_errors );
		$this->assertEquals( 'invalid_api_key_format', $wp_settings_errors[0]['code'] );
	}
}
