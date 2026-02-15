/**
 * Scan Form Component using DataForm
 */
import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DataForm } from '@wordpress/dataviews';
import { Button, Notice, Spinner } from '@wordpress/components';
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
				path: '/wp/v2/plugins',
				method: 'GET',
			} ).catch( () => {
				// Fallback to custom endpoint if WP REST API doesn't have plugins endpoint
				return apiFetch( {
					path: '/all-the-hooks/v1/plugins',
					method: 'GET',
				} );
			} );

			const pluginOptions = response.map( ( plugin ) => ( {
				label: plugin.name,
				value: plugin.plugin.split( '/' )[ 0 ],
			} ) );
			setPlugins( pluginOptions );
		} catch ( err ) {
			console.error( 'Failed to fetch plugins:', err );
		}
	};

	const fetchThemes = async () => {
		try {
			const response = await apiFetch( {
				path: '/all-the-hooks/v1/themes',
				method: 'GET',
			} );

			const themeOptions = response.themes.map( ( theme ) => ( {
				label: theme.name,
				value: theme.slug,
			} ) );
			setThemes( themeOptions );
		} catch ( err ) {
			console.error( 'Failed to fetch themes:', err );
		}
	};

	// Define form fields for DataForm
	const fields = [
		{
			id: 'source_type',
			label: __( 'Source Type', 'all-the-hooks' ),
			type: 'radio',
			elements: [
				{ label: __( 'Plugin', 'all-the-hooks' ), value: 'plugin' },
				{ label: __( 'Theme', 'all-the-hooks' ), value: 'theme' },
			],
		},
		{
			id: 'source_slug',
			label:
				formData.source_type === 'plugin'
					? __( 'Select Plugin', 'all-the-hooks' )
					: __( 'Select Theme', 'all-the-hooks' ),
			type: 'select',
			elements: formData.source_type === 'plugin' ? plugins : themes,
			description:
				formData.source_type === 'plugin'
					? __( 'Choose the plugin you want to scan for hooks.', 'all-the-hooks' )
					: __( 'Choose the theme you want to scan for hooks.', 'all-the-hooks' ),
		},
		{
			id: 'hook_type',
			label: __( 'Hook Type', 'all-the-hooks' ),
			type: 'select',
			elements: [
				{ label: __( 'All (Actions & Filters)', 'all-the-hooks' ), value: 'all' },
				{ label: __( 'Actions Only', 'all-the-hooks' ), value: 'action' },
				{ label: __( 'Filters Only', 'all-the-hooks' ), value: 'filter' },
			],
		},
		{
			id: 'include_docblocks',
			label: __( 'Include DocBlocks', 'all-the-hooks' ),
			type: 'checkbox',
			description: __( 'Extract and include PHPDoc comments for each hook.', 'all-the-hooks' ),
		},
		{
			id: 'format',
			label: __( 'Output Format', 'all-the-hooks' ),
			type: 'select',
			elements: [
				{ label: 'JSON', value: 'json' },
				{ label: 'Markdown', value: 'markdown' },
				{ label: 'HTML', value: 'html' },
			],
		},
	];

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

			const response = await apiFetch( {
				path: '/wp/v2/admin/ajax',
				method: 'POST',
				data: {
					action: 'ath_scan_source',
					nonce: athAdminData.nonce,
					...formData,
				},
			} ).catch( async () => {
				// Fallback to direct AJAX call
				const formDataObj = new FormData();
				formDataObj.append( 'action', 'ath_scan_source' );
				formDataObj.append( 'nonce', athAdminData.nonce );
				Object.keys( formData ).forEach( ( key ) => {
					formDataObj.append( key, formData[ key ] );
				} );

				const ajaxResponse = await fetch( athAdminData.ajaxUrl, {
					method: 'POST',
					body: formDataObj,
				} );
				return ajaxResponse.json();
			} );

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
		<div className="ath-scan-form">
			{ error && (
				<Notice status="error" isDismissible onRemove={ () => setError( null ) }>
					{ error }
				</Notice>
			) }

			<form onSubmit={ handleSubmit }>
				<DataForm data={ formData } fields={ fields } onChange={ setFormData } form={ { type: 'regular' } } />

				<div className="ath-form-actions">
					<Button variant="primary" type="submit" disabled={ isScanning } size="default">
						{ isScanning ? (
							<>
								<Spinner />
								{ __( 'Scanning...', 'all-the-hooks' ) }
							</>
						) : (
							__( 'Scan for Hooks', 'all-the-hooks' )
						) }
					</Button>
				</div>

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
			</form>
		</div>
	);
}
