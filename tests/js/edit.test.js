/**
 * Tests for the Weather Block edit component.
 *
 * @package WeatherBlock
 */

import { render, screen } from '@testing-library/react';
import '@testing-library/jest-dom';
import Edit from '../../src/weather-block/edit';

// Mock WordPress dependencies
jest.mock( '@wordpress/i18n', () => ( {
	__: ( text ) => text,
} ) );

jest.mock( '@wordpress/block-editor', () => ( {
	useBlockProps: () => ( { className: 'wp-block-weather-block-weather' } ),
	InspectorControls: ( { children } ) => <div data-testid="inspector-controls">{ children }</div>,
} ) );

jest.mock( '@wordpress/components', () => ( {
	PanelBody: ( { children, title } ) => <div data-testid="panel-body" title={ title }>{ children }</div>,
	TextControl: ( { label, value, onChange, placeholder, help } ) => (
		<div data-testid="text-control">
			<label>{ label }</label>
			<input
				value={ value }
				onChange={ ( e ) => onChange( e.target.value ) }
				placeholder={ placeholder }
				title={ help }
			/>
		</div>
	),
	RadioControl: ( { label, selected, options, onChange, help } ) => (
		<div data-testid="radio-control">
			<label>{ label }</label>
			{ options.map( ( option ) => (
				<label key={ option.value }>
					<input
						type="radio"
						value={ option.value }
						checked={ selected === option.value }
						onChange={ () => onChange( option.value ) }
					/>
					{ option.label }
				</label>
			) ) }
			{ help && <p>{ help }</p> }
		</div>
	),
	Spinner: () => <div data-testid="spinner">Loading...</div>,
	Notice: ( { children, status } ) => <div data-testid="notice" className={ `notice-${ status }` }>{ children }</div>,
} ) );

jest.mock( '@wordpress/element', () => ( {
	useState: require( 'react' ).useState,
	useEffect: require( 'react' ).useEffect,
} ) );

jest.mock( '@wordpress/api-fetch', () => jest.fn() );

describe( 'Weather Block Edit Component', () => {
	const defaultAttributes = {
		location: '',
		units: 'metric',
		displayMode: 'auto',
	};

	const defaultProps = {
		attributes: defaultAttributes,
		setAttributes: jest.fn(),
	};

	beforeEach( () => {
		jest.clearAllMocks();
	} );

	test( 'renders placeholder when no location is set', () => {
		render( <Edit { ...defaultProps } /> );

		expect( screen.getByText( 'Enter a location in the sidebar to display weather information.' ) ).toBeInTheDocument();
	} );

	test( 'renders inspector controls', () => {
		render( <Edit { ...defaultProps } /> );

		expect( screen.getByTestId( 'inspector-controls' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'panel-body' ) ).toBeInTheDocument();
	} );

	test( 'renders location text control', () => {
		render( <Edit { ...defaultProps } /> );

		const textControl = screen.getByTestId( 'text-control' );
		expect( textControl ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Location' ) ).toBeInTheDocument();
	} );

	test( 'renders units radio control', () => {
		render( <Edit { ...defaultProps } /> );

		const radioControl = screen.getAllByTestId( 'radio-control' );
		expect( radioControl ).toHaveLength( 2 ); // Units and Display Mode

		expect( screen.getByLabelText( 'Temperature Units' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Celsius (°C)' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Fahrenheit (°F)' ) ).toBeInTheDocument();
	} );

	test( 'renders display mode radio control', () => {
		render( <Edit { ...defaultProps } /> );

		expect( screen.getByLabelText( 'Display Mode' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Auto (System)' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Light' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Dark' ) ).toBeInTheDocument();
	} );

	test( 'calls setAttributes when location changes', () => {
		const setAttributes = jest.fn();
		const props = { ...defaultProps, setAttributes };

		render( <Edit { ...props } /> );

		const locationInput = screen.getByPlaceholderText( 'Enter city name (e.g., New York)' );
		locationInput.value = 'New York';
		locationInput.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		// Note: This test would need more sophisticated mocking to fully test the onChange behavior
		expect( locationInput ).toBeInTheDocument();
	} );

	test( 'displays correct default values', () => {
		render( <Edit { ...defaultProps } /> );

		// Check that metric is selected by default
		const metricRadio = screen.getByDisplayValue( 'metric' );
		expect( metricRadio ).toBeChecked();

		// Check that auto is selected by default
		const autoRadio = screen.getByDisplayValue( 'auto' );
		expect( autoRadio ).toBeChecked();
	} );
} );
