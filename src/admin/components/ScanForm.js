/**
 * Scan Form Component using WordPress Components
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Notice,
	Spinner,
	Card,
	CardBody,
	SelectControl,
	RadioControl,
	CheckboxControl,
	__experimentalVStack as VStack,
	__experimentalHStack as HStack,
} from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

export default function ScanForm( { onScanStart, onScanComplete, onScanError, isScanning } ) {
	const [ formData, setFormData ] = useState( {
		source_type: 'plugin',
		source_slug: '',
		hook_type: 'all',
		include_docblocks: false,
		format: 'json',
	} );

	const [ plugins, setPlugins ] = useState( [] );
	const [ themes, setThemes ] = useState( [] );
	const [ error, setError ] = useState( null );
	const [ progress, setProgress ] = useState( 0 );

	// Fetch available plugins and themes
	useEffect( () => {
		fetchPlugins();
		fetchThemes();
	}, [] );

	const fetchPlugins = async () => {
		try {
			const response = await apiFetch( {
				path: '/all-the-hooks/v1/plugins',
				method: 'GET',
			} );

			const pluginOptions = [
				{ label: __( '-- Select a Plugin --', 'all-the-hooks' ), value: '' },
				...response.map( ( plugin ) => ( {
					label: plugin.name,
					value: plugin.slug,
				} ) ),
			];
			setPlugins( pluginOptions );
		} catch ( err ) {
			console.error( 'Failed to fetch plugins:', err );
			setError( __( 'Failed to load plugins list.', 'all-the-hooks' ) );
		}
	};

	const fetchThemes = async () => {
		try {
			const response = await apiFetch( {
				path: '/all-the-hooks/v1/themes',
				method: 'GET',
			} );

			const themeOptions = [
				{ label: __( '-- Select a Theme --', 'all-the-hooks' ), value: '' },
				...response.themes.map( ( theme ) => ( {
					label: theme.name,
					value: theme.slug,
				} ) ),
			];
			setThemes( themeOptions );
		} catch ( err ) {
			console.error( 'Failed to fetch themes:', err );
			setError( __( 'Failed to load themes list.', 'all-the-hooks' ) );
		}
	};

	const updateFormData = ( key, value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	};

	const handleSubmit = async ( e ) => {
		e.preventDefault();
		setError( null );

		if ( ! formData.source_slug ) {
			setError( __( 'Please select a plugin or theme to scan.', 'all-the-hooks' ) );
			return;
		}

		onScanStart();
		setProgress( 10 );

		try {
			// Simulate progress
			const progressInterval = setInterval( () => {
				setProgress( ( prev ) => {
					if ( prev < 80 ) return prev + 10;
					return prev;
				} );
			}, 300 );

			// Make AJAX call
			const formDataObj = new FormData();
			formDataObj.append( 'action', 'ath_scan_source' );
			formDataObj.append( 'nonce', athAdminData.nonce );
			formDataObj.append( 'source_type', formData.source_type );
			formDataObj.append( 'source_slug', formData.source_slug );
			formDataObj.append( 'hook_type', formData.hook_type );
			formDataObj.append( 'include_docblocks', formData.include_docblocks ? '1' : '0' );
			formDataObj.append( 'format', formData.format );

			const ajaxResponse = await fetch( athAdminData.ajaxUrl, {
				method: 'POST',
				body: formDataObj,
			} );

			const response = await ajaxResponse.json();

			clearInterval( progressInterval );
			setProgress( 100 );

			if ( response.success ) {
				onScanComplete( response.data );
			} else {
				setError( response.data?.message || __( 'An error occurred during scanning.', 'all-the-hooks' ) );
				onScanError();
			}
		} catch ( err ) {
			setError( err.message || __( 'Failed to scan. Please try again.', 'all-the-hooks' ) );
			onScanError();
		}
	};

	return (
		<Card>
			<CardBody>
				<h2>{ __( 'Scan Configuration', 'all-the-hooks' ) }</h2>

				{ error && (
					<Notice status="error" isDismissible onRemove={ () => setError( null ) }>
						{ error }
					</Notice>
				) }

				<form onSubmit={ handleSubmit }>
					<VStack spacing={ 4 }>
						<RadioControl
							label={ __( 'Source Type', 'all-the-hooks' ) }
							selected={ formData.source_type }
							options={ [
								{ label: __( 'Plugin', 'all-the-hooks' ), value: 'plugin' },
								{ label: __( 'Theme', 'all-the-hooks' ), value: 'theme' },
							] }
							onChange={ ( value ) => {
								updateFormData( 'source_type', value );
								updateFormData( 'source_slug', '' ); // Reset selection when switching type
							} }
						/>

						{ formData.source_type === 'plugin' ? (
							<SelectControl
								label={ __( 'Select Plugin', 'all-the-hooks' ) }
								value={ formData.source_slug }
								options={ plugins }
								onChange={ ( value ) => updateFormData( 'source_slug', value ) }
								help={ __( 'Choose the plugin you want to scan for hooks.', 'all-the-hooks' ) }
							/>
						) : (
							<SelectControl
								label={ __( 'Select Theme', 'all-the-hooks' ) }
								value={ formData.source_slug }
								options={ themes }
								onChange={ ( value ) => updateFormData( 'source_slug', value ) }
								help={ __( 'Choose the theme you want to scan for hooks.', 'all-the-hooks' ) }
							/>
						) }

						<SelectControl
							label={ __( 'Hook Type', 'all-the-hooks' ) }
							value={ formData.hook_type }
							options={ [
								{ label: __( 'All (Actions & Filters)', 'all-the-hooks' ), value: 'all' },
								{ label: __( 'Actions Only', 'all-the-hooks' ), value: 'action' },
								{ label: __( 'Filters Only', 'all-the-hooks' ), value: 'filter' },
							] }
							onChange={ ( value ) => updateFormData( 'hook_type', value ) }
						/>

						<CheckboxControl
							label={ __( 'Include DocBlocks', 'all-the-hooks' ) }
							checked={ formData.include_docblocks }
							onChange={ ( value ) => updateFormData( 'include_docblocks', value ) }
							help={ __( 'Extract and include PHPDoc comments for each hook.', 'all-the-hooks' ) }
						/>

						<SelectControl
							label={ __( 'Output Format', 'all-the-hooks' ) }
							value={ formData.format }
							options={ [
								{ label: 'JSON', value: 'json' },
								{ label: 'Markdown', value: 'markdown' },
								{ label: 'HTML', value: 'html' },
							] }
							onChange={ ( value ) => updateFormData( 'format', value ) }
						/>

						<HStack justify="flex-start">
							<Button variant="primary" type="submit" disabled={ isScanning } isBusy={ isScanning }>
								{ isScanning
									? __( 'Scanning...', 'all-the-hooks' )
									: __( 'Scan for Hooks', 'all-the-hooks' ) }
							</Button>
						</HStack>
					</VStack>
				</form>

				{ isScanning && (
					<div className="ath-progress">
						<div className="ath-progress-bar">
							<div className="ath-progress-fill" style={ { width: `${ progress }%` } } />
						</div>
						<p className="ath-progress-text">
							{ progress < 30 && __( 'Starting scan...', 'all-the-hooks' ) }
							{ progress >= 30 && progress < 60 && __( 'Analyzing files...', 'all-the-hooks' ) }
							{ progress >= 60 && progress < 90 && __( 'Processing hooks...', 'all-the-hooks' ) }
							{ progress >= 90 && __( 'Finalizing results...', 'all-the-hooks' ) }
						</p>
					</div>
				) }
			</CardBody>
		</Card>
	);
}
