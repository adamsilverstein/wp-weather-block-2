<?php
/**
 * Tests for WeatherApi class.
 *
 * @package WeatherBlock
 */

namespace WeatherBlock\Tests;

use PHPUnit\Framework\TestCase;
use WeatherBlock\WeatherApi;

/**
 * Class WeatherApiTest
 *
 * @since 0.1.0
 */
class WeatherApiTest extends TestCase {

	/**
	 * Test WeatherApi constructor.
	 *
	 * @since 0.1.0
	 */
	public function test_constructor(): void {
		$api = new WeatherApi( 'test_api_key' );
		$this->assertInstanceOf( WeatherApi::class, $api );
	}

	/**
	 * Test get_weather_data with empty location.
	 *
	 * @since 0.1.0
	 */
	public function test_get_weather_data_empty_location(): void {
		$api = new WeatherApi( 'test_api_key' );
		$result = $api->get_weather_data( '' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_location', $result->get_error_code() );
	}

	/**
	 * Test get_weather_data with invalid units.
	 *
	 * @since 0.1.0
	 */
	public function test_get_weather_data_invalid_units(): void {
		$api = new WeatherApi( 'test_api_key' );
		$result = $api->get_weather_data( 'New York', 'invalid' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'invalid_units', $result->get_error_code() );
	}

	/**
	 * Test get_weather_data with missing API key.
	 *
	 * @since 0.1.0
	 */
	public function test_get_weather_data_missing_api_key(): void {
		$api = new WeatherApi( '' );
		$result = $api->get_weather_data( 'New York' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'missing_api_key', $result->get_error_code() );
	}

	/**
	 * Test get_weather_data with default API key.
	 *
	 * @since 0.1.0
	 */
	public function test_get_weather_data_default_api_key(): void {
		$api = new WeatherApi( 'your_openweathermap_api_key_here' );
		$result = $api->get_weather_data( 'New York' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertEquals( 'missing_api_key', $result->get_error_code() );
	}

	/**
	 * Test get_icon_url static method.
	 *
	 * @since 0.1.0
	 */
	public function test_get_icon_url(): void {
		$url = WeatherApi::get_icon_url( '01d' );
		$this->assertEquals( 'https://openweathermap.org/img/wn/01d@2x.png', $url );

		$url_4x = WeatherApi::get_icon_url( '01d', '4x' );
		$this->assertEquals( 'https://openweathermap.org/img/wn/01d@4x.png', $url_4x );

		// Test invalid size defaults to 2x
		$url_invalid = WeatherApi::get_icon_url( '01d', 'invalid' );
		$this->assertEquals( 'https://openweathermap.org/img/wn/01d@2x.png', $url_invalid );
	}
}
