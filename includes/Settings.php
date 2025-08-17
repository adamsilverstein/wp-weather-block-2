<?php
/**
 * Settings page handler class.
 *
 * @package WeatherBlock
 */

namespace WeatherBlock;

/**
 * Class Settings
 *
 * Handles the WordPress admin settings page for Weather Block configuration.
 *
 * @since 0.1.0
 */
class Settings {

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private const PAGE_SLUG = 'weather-block-settings';

	/**
	 * Option name for storing the API key.
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'weather_block_api_key';

	/**
	 * Settings group name.
	 *
	 * @var string
	 */
	private const SETTINGS_GROUP = 'weather_block_settings';

	/**
	 * Default API key placeholder.
	 *
	 * @var string
	 */
	private const DEFAULT_API_KEY_PLACEHOLDER = 'your_openweathermap_api_key_here';

	/**
	 * Initialize the settings page.
	 *
	 * @since 0.1.0
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_ajax_weather_block_test_api_key', array( $this, 'ajax_test_api_key' ) );
	}

	/**
	 * Add the settings page to the WordPress admin menu.
	 *
	 * @since 0.1.0
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'Weather Block Settings', 'weather-block' ),
			__( 'Weather Block', 'weather-block' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings with WordPress.
	 *
	 * @since 0.1.0
	 */
	public function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_api_key' ),
				'default'           => '',
			)
		);

		add_settings_section(
			'weather_block_api_section',
			__( 'OpenWeatherMap API Configuration', 'weather-block' ),
			array( $this, 'render_api_section_description' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'weather_block_api_key',
			__( 'API Key', 'weather-block' ),
			array( $this, 'render_api_key_field' ),
			self::PAGE_SLUG,
			'weather_block_api_section'
		);
	}

	/**
	 * Sanitize the API key input.
	 *
	 * @since 0.1.0
	 *
	 * @param string $api_key The API key to sanitize.
	 * @return string The sanitized API key.
	 */
	public function sanitize_api_key( string $api_key ): string {
		$api_key = sanitize_text_field( trim( $api_key ) );

		// Validate API key format (OpenWeatherMap keys are 32 character alphanumeric strings).
		if ( ! empty( $api_key ) && ! preg_match( '/^[a-zA-Z0-9]{32}$/', $api_key ) ) {
			add_settings_error(
				self::OPTION_NAME,
				'invalid_api_key_format',
				__( 'Invalid API key format. OpenWeatherMap API keys should be 32 alphanumeric characters.', 'weather-block' ),
				'error'
			);
			return get_option( self::OPTION_NAME, '' );
		}

		return $api_key;
	}

	/**
	 * Render the settings page.
	 *
	 * @since 0.1.0
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'weather-block' ) );
		}

		$api_key       = $this->get_api_key();
		$is_configured = ! empty( $api_key );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<div class="weather-block-settings">
				<div class="weather-block-settings__main">
					<form method="post" action="options.php">
						<?php
						settings_fields( self::SETTINGS_GROUP );
						do_settings_sections( self::PAGE_SLUG );
						submit_button();
						?>
					</form>
				</div>

				<div class="weather-block-settings__sidebar">
					<div class="postbox">
						<h3 class="hndle"><?php esc_html_e( 'API Status', 'weather-block' ); ?></h3>
						<div class="inside">
							<div id="weather-block-api-status">
								<?php if ( $is_configured ) : ?>
									<p class="weather-block-status weather-block-status--unknown">
										<span class="dashicons dashicons-clock"></span>
										<?php esc_html_e( 'API key configured. Click "Test API Key" to verify.', 'weather-block' ); ?>
									</p>
								<?php else : ?>
									<p class="weather-block-status weather-block-status--error">
										<span class="dashicons dashicons-warning"></span>
										<?php esc_html_e( 'No API key configured.', 'weather-block' ); ?>
									</p>
								<?php endif; ?>
							</div>

							<?php if ( $is_configured ) : ?>
								<button type="button" id="weather-block-test-api" class="button button-secondary">
									<?php esc_html_e( 'Test API Key', 'weather-block' ); ?>
								</button>
							<?php endif; ?>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle"><?php esc_html_e( 'Getting Started', 'weather-block' ); ?></h3>
						<div class="inside">
							<ol>
								<li>
									<a href="https://openweathermap.org/api" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'Sign up for OpenWeatherMap API', 'weather-block' ); ?>
										<span class="dashicons dashicons-external"></span>
									</a>
								</li>
								<li><?php esc_html_e( 'Get your free API key', 'weather-block' ); ?></li>
								<li><?php esc_html_e( 'Enter the API key in the form', 'weather-block' ); ?></li>
								<li><?php esc_html_e( 'Test the API key to ensure it works', 'weather-block' ); ?></li>
								<li><?php esc_html_e( 'Start using the Weather Block!', 'weather-block' ); ?></li>
							</ol>
						</div>
					</div>

					<div class="postbox">
						<h3 class="hndle"><?php esc_html_e( 'Documentation', 'weather-block' ); ?></h3>
						<div class="inside">
							<ul>
								<li>
									<a href="https://openweathermap.org/api/one-call-3" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'OpenWeatherMap API Documentation', 'weather-block' ); ?>
										<span class="dashicons dashicons-external"></span>
									</a>
								</li>
								<li>
									<a href="https://openweathermap.org/faq" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'OpenWeatherMap FAQ', 'weather-block' ); ?>
										<span class="dashicons dashicons-external"></span>
									</a>
								</li>
								<li>
									<a href="https://openweathermap.org/price" target="_blank" rel="noopener noreferrer">
										<?php esc_html_e( 'API Pricing & Limits', 'weather-block' ); ?>
										<span class="dashicons dashicons-external"></span>
									</a>
								</li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the API section description.
	 *
	 * @since 0.1.0
	 */
	public function render_api_section_description(): void {
		?>
		<p>
			<?php
			printf(
				/* translators: %s: OpenWeatherMap website URL */
				esc_html__( 'To use the Weather Block, you need an API key from %s. The free tier includes 1,000 API calls per day, which is sufficient for most websites.', 'weather-block' ),
				'<a href="https://openweathermap.org/" target="_blank" rel="noopener noreferrer">OpenWeatherMap</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the API key input field.
	 *
	 * @since 0.1.0
	 */
	public function render_api_key_field(): void {
		$api_key = $this->get_api_key();
		?>
		<input
			type="password"
			id="weather_block_api_key"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>"
			value="<?php echo esc_attr( $api_key ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Enter your OpenWeatherMap API key', 'weather-block' ); ?>"
		/>
		<button type="button" id="weather-block-toggle-visibility" class="button button-secondary">
			<span class="dashicons dashicons-visibility"></span>
			<span class="screen-reader-text"><?php esc_html_e( 'Toggle API key visibility', 'weather-block' ); ?></span>
		</button>
		<p class="description">
			<?php
			printf(
				/* translators: %s: OpenWeatherMap API keys page URL */
				esc_html__( 'Your OpenWeatherMap API key. You can find this in your %s after signing up.', 'weather-block' ),
				'<a href="https://home.openweathermap.org/api_keys" target="_blank" rel="noopener noreferrer">' . esc_html__( 'OpenWeatherMap account dashboard', 'weather-block' ) . '</a>'
			);
			?>
		</p>
		<?php
	}

	/**
	 * Enqueue admin assets for the settings page.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'weather-block-admin',
			WEATHER_BLOCK_PLUGIN_URL . 'admin/css/settings.css',
			array(),
			WEATHER_BLOCK_VERSION
		);

		wp_enqueue_script(
			'weather-block-admin',
			WEATHER_BLOCK_PLUGIN_URL . 'admin/js/settings.js',
			array( 'jquery' ),
			WEATHER_BLOCK_VERSION,
			true
		);

		wp_localize_script(
			'weather-block-admin',
			'weatherBlockAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'weather_block_test_api' ),
				'strings' => array(
					'testing'     => __( 'Testing API key...', 'weather-block' ),
					'testSuccess' => __( 'API key is valid and working!', 'weather-block' ),
					'testError'   => __( 'API key test failed. Please check your key and try again.', 'weather-block' ),
					'showKey'     => __( 'Show API key', 'weather-block' ),
					'hideKey'     => __( 'Hide API key', 'weather-block' ),
				),
			)
		);
	}

	/**
	 * AJAX handler for testing the API key.
	 *
	 * @since 0.1.0
	 */
	public function ajax_test_api_key(): void {
		check_ajax_referer( 'weather_block_test_api', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( -1, 403 );
		}

		$api_key = $this->get_api_key();

		if ( empty( $api_key ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No API key configured.', 'weather-block' ),
				)
			);
		}

		// Test the API key with a simple request.
		$weather_api = new WeatherApi( $api_key );
		$test_result = $weather_api->get_weather_data( 'London', 'metric' );

		if ( is_wp_error( $test_result ) ) {
			wp_send_json_error(
				array(
					'message' => $test_result->get_error_message(),
				)
			);
		}

		wp_send_json_success(
			array(
				'message' => __( 'API key is valid and working!', 'weather-block' ),
				'data'    => array(
					'location'    => $test_result['location'],
					'temperature' => $test_result['temperature'],
					'units'       => $test_result['units'],
				),
			)
		);
	}

	/**
	 * Get the stored API key.
	 *
	 * @since 0.1.0
	 *
	 * @return string The API key or empty string if not set.
	 */
	public function get_api_key(): string {
		return get_option( self::OPTION_NAME, '' );
	}

	/**
	 * Get the API key for use in the plugin.
	 * Falls back to constant if option is not set.
	 *
	 * @since 0.1.0
	 *
	 * @return string The API key to use.
	 */
	public static function get_effective_api_key(): string {
		$option_key = get_option( self::OPTION_NAME, '' );

		if ( ! empty( $option_key ) ) {
			return $option_key;
		}

		// Fallback to constant for backward compatibility.
		if ( defined( 'WEATHER_BLOCK_API_KEY' ) ) {
			return WEATHER_BLOCK_API_KEY;
		}

		return '';
	}

	/**
	 * Check if the API key is configured.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if API key is configured, false otherwise.
	 */
	public static function is_api_key_configured(): bool {
		$api_key = self::get_effective_api_key();
		return ! empty( $api_key ) && self::DEFAULT_API_KEY_PLACEHOLDER !== $api_key;
	}
}
