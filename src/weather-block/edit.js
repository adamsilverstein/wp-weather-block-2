/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';

/**
 * WordPress components for the block editor.
 */
import {
	PanelBody,
	TextControl,
	RadioControl,
	Spinner,
	Notice,
} from '@wordpress/components';

/**
 * React hooks.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';

/**
 * WordPress data and API fetch utilities.
 */
import apiFetch from '@wordpress/api-fetch';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @param {Object}   props               Properties passed to the function.
 * @param {Object}   props.attributes    Available block attributes.
 * @param {Function} props.setAttributes Function that updates individual attributes.
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
	const { location, units, displayMode } = attributes;
	const [ weatherData, setWeatherData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	/**
	 * Fetch weather data from the REST API.
	 */
	const fetchWeatherData = useCallback( async () => {
		if ( ! location || location.trim() === '' ) {
			setWeatherData( null );
			setError( null );
			return;
		}

		setIsLoading( true );
		setError( null );

		try {
			const data = await apiFetch( {
				path: `/weather-block/v1/weather/${ encodeURIComponent(
					location
				) }?units=${ units }`,
				method: 'GET',
			} );

			setWeatherData( data );
		} catch ( err ) {
			setError(
				err.message ||
					__(
						'Could not fetch weather data. Please check the location and try again.',
						'weather-block'
					)
			);
			setWeatherData( null );
		} finally {
			setIsLoading( false );
		}
	}, [ location, units ] );

	/**
	 * Effect to fetch weather data when location or units change.
	 */
	useEffect( () => {
		const timeoutId = setTimeout( () => {
			fetchWeatherData();
		}, 500 ); // Debounce API calls

		return () => clearTimeout( timeoutId );
	}, [ location, units, fetchWeatherData ] );

	/**
	 * Handle location input change.
	 *
	 * @param {string} value New location value.
	 */
	const onLocationChange = ( value ) => {
		setAttributes( { location: value } );
	};

	/**
	 * Handle units change.
	 *
	 * @param {string} value New units value.
	 */
	const onUnitsChange = ( value ) => {
		setAttributes( { units: value } );
	};

	/**
	 * Handle display mode change.
	 *
	 * @param {string} value New display mode value.
	 */
	const onDisplayModeChange = ( value ) => {
		setAttributes( { displayMode: value } );
	};

	/**
	 * Render weather data display.
	 *
	 * @return {Element} Weather display element.
	 */
	const renderWeatherDisplay = () => {
		if ( ! location || location.trim() === '' ) {
			return (
				<div className="weather-block__placeholder">
					<p>
						{ __(
							'Enter a location in the sidebar to display weather information.',
							'weather-block'
						) }
					</p>
				</div>
			);
		}

		if ( isLoading ) {
			return (
				<div className="weather-block__loading">
					<Spinner />
					<p>{ __( 'Loading weather data…', 'weather-block' ) }</p>
				</div>
			);
		}

		if ( error ) {
			return (
				<div className="weather-block__error">
					<Notice status="error" isDismissible={ false }>
						{ error }
					</Notice>
				</div>
			);
		}

		if ( ! weatherData ) {
			return null;
		}

		const temperatureUnit = units === 'metric' ? '°C' : '°F';
		const iconUrl = `https://openweathermap.org/img/wn/${ weatherData.icon }@2x.png`;

		return (
			<div
				className={ `weather-block weather-block--theme-${ displayMode }` }
			>
				<div className="weather-block__header">
					<h3 className="weather-block__location">
						{ weatherData.location }, { weatherData.country }
					</h3>
				</div>
				<div className="weather-block__content">
					<div className="weather-block__temperature">
						<img
							src={ iconUrl }
							alt={ weatherData.description }
							className="weather-block__icon"
						/>
						<span className="weather-block__temp">
							{ Math.round( weatherData.temperature ) }
							{ temperatureUnit }
						</span>
					</div>
					<div className="weather-block__details">
						<p className="weather-block__description">
							{ weatherData.description
								.charAt( 0 )
								.toUpperCase() +
								weatherData.description.slice( 1 ) }
						</p>
						<p className="weather-block__humidity">
							{ __( 'Humidity', 'weather-block' ) }:{ ' ' }
							{ weatherData.humidity }%
						</p>
					</div>
				</div>
			</div>
		);
	};

	const blockProps = useBlockProps( {
		className: `weather-block-editor weather-block-editor--theme-${ displayMode }`,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Weather Settings', 'weather-block' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Location', 'weather-block' ) }
						value={ location }
						onChange={ onLocationChange }
						placeholder={ __(
							'Enter city name (e.g., New York)',
							'weather-block'
						) }
						help={ __(
							'Enter the name of the city for which you want to display weather information.',
							'weather-block'
						) }
					/>

					<RadioControl
						label={ __( 'Temperature Units', 'weather-block' ) }
						selected={ units }
						options={ [
							{
								label: __( 'Celsius (°C)', 'weather-block' ),
								value: 'metric',
							},
							{
								label: __( 'Fahrenheit (°F)', 'weather-block' ),
								value: 'imperial',
							},
						] }
						onChange={ onUnitsChange }
					/>

					<RadioControl
						label={ __( 'Display Mode', 'weather-block' ) }
						selected={ displayMode }
						options={ [
							{
								label: __( 'Auto (System)', 'weather-block' ),
								value: 'auto',
							},
							{
								label: __( 'Light', 'weather-block' ),
								value: 'light',
							},
							{
								label: __( 'Dark', 'weather-block' ),
								value: 'dark',
							},
						] }
						onChange={ onDisplayModeChange }
						help={ __(
							"Auto mode will respect the user's system theme preference.",
							'weather-block'
						) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>{ renderWeatherDisplay() }</div>
		</>
	);
}
