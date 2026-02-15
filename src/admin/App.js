/**
 * Main Admin App Component
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import ScanForm from './components/ScanForm';
import ResultsView from './components/ResultsView';
import './style.css';

export default function AdminApp() {
	const [ results, setResults ] = useState( null );
	const [ isScanning, setIsScanning ] = useState( false );

	const handleScanComplete = ( data ) => {
		setResults( data );
		setIsScanning( false );
	};

	const handleScanStart = () => {
		setIsScanning( true );
		setResults( null );
	};

	const handleScanError = () => {
		setIsScanning( false );
	};

	return (
		<div className="ath-admin-app">
			<div className="ath-admin-header">
				<h1>{ __( 'All The Hooks', 'all-the-hooks' ) }</h1>
				<p className="description">
					{ __( 'Discover and document hooks in WordPress plugins and themes', 'all-the-hooks' ) }
				</p>
			</div>

			<ScanForm
				onScanStart={ handleScanStart }
				onScanComplete={ handleScanComplete }
				onScanError={ handleScanError }
				isScanning={ isScanning }
			/>

			{ results && <ResultsView results={ results } /> }
		</div>
	);
}
