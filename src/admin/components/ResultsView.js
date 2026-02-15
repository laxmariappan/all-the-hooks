/**
 * Results View Component - Simple Table Version
 */
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Card, CardBody, TextControl, SelectControl } from '@wordpress/components';
import { download } from '@wordpress/icons';

export default function ResultsView( { results } ) {
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ filterType, setFilterType ] = useState( '' );
	const [ filterSource, setFilterSource ] = useState( '' );

	console.log( 'ResultsView received results:', results );

	if ( ! results || ! results.hooks || results.hooks.length === 0 ) {
		return (
			<Card>
				<CardBody>
					<h2>{ __( 'No Results', 'all-the-hooks' ) }</h2>
					<p>{ __( 'No hooks were found in the scan.', 'all-the-hooks' ) }</p>
				</CardBody>
			</Card>
		);
	}

	// Filter hooks based on search and filters
	const filteredHooks = results.hooks.filter( ( hook ) => {
		// Search filter
		if ( searchTerm && ! hook.name.toLowerCase().includes( searchTerm.toLowerCase() ) ) {
			return false;
		}

		// Type filter
		if ( filterType && hook.type !== filterType ) {
			return false;
		}

		// Source filter
		if ( filterSource && hook.is_core !== filterSource ) {
			return false;
		}

		return true;
	} );

	const handleDownload = () => {
		const dataStr = JSON.stringify( results.hooks, null, 2 );
		const dataBlob = new Blob( [ dataStr ], { type: 'application/json' } );
		const url = URL.createObjectURL( dataBlob );
		const link = document.createElement( 'a' );
		link.href = url;
		link.download = 'hooks-export.json';
		link.click();
		URL.revokeObjectURL( url );
	};

	return (
		<div className="ath-results-view">
			<Card>
				<CardBody>
					<div className="ath-results-header">
						<h2>{ __( 'Scan Results', 'all-the-hooks' ) }</h2>

						<div className="ath-summary-stats">
							<div className="ath-stat">
								<span className="ath-stat-number">{ results.total }</span>
								<span className="ath-stat-label">{ __( 'Total Hooks', 'all-the-hooks' ) }</span>
							</div>
							<div className="ath-stat">
								<span className="ath-stat-number">{ results.actions }</span>
								<span className="ath-stat-label">{ __( 'Actions', 'all-the-hooks' ) }</span>
							</div>
							<div className="ath-stat">
								<span className="ath-stat-number">{ results.filters }</span>
								<span className="ath-stat-label">{ __( 'Filters', 'all-the-hooks' ) }</span>
							</div>
							<div className="ath-stat">
								<span className="ath-stat-number">{ results.hooks_with_listeners }</span>
								<span className="ath-stat-label">{ __( 'With Listeners', 'all-the-hooks' ) }</span>
							</div>
						</div>

						<Button variant="secondary" icon={ download } onClick={ handleDownload }>
							{ __( 'Download Results', 'all-the-hooks' ) }
						</Button>
					</div>

					<div className="ath-filters">
						<TextControl
							label={ __( 'Search Hooks', 'all-the-hooks' ) }
							value={ searchTerm }
							onChange={ setSearchTerm }
							placeholder={ __( 'Type to search...', 'all-the-hooks' ) }
						/>

						<SelectControl
							label={ __( 'Filter by Type', 'all-the-hooks' ) }
							value={ filterType }
							options={ [
								{ label: __( 'All Types', 'all-the-hooks' ), value: '' },
								{ label: __( 'Actions', 'all-the-hooks' ), value: 'action' },
								{ label: __( 'Filters', 'all-the-hooks' ), value: 'filter' },
							] }
							onChange={ setFilterType }
						/>

						<SelectControl
							label={ __( 'Filter by Source', 'all-the-hooks' ) }
							value={ filterSource }
							options={ [
								{ label: __( 'All Sources', 'all-the-hooks' ), value: '' },
								{ label: __( 'Core Hooks', 'all-the-hooks' ), value: 'yes' },
								{ label: __( 'Custom Hooks', 'all-the-hooks' ), value: 'no' },
							] }
							onChange={ setFilterSource }
						/>
					</div>

					<div className="ath-results-count">
						<p>
							{ __( 'Showing', 'all-the-hooks' ) } <strong>{ filteredHooks.length }</strong> { __( 'of', 'all-the-hooks' ) }{ ' ' }
							<strong>{ results.hooks.length }</strong> { __( 'hooks', 'all-the-hooks' ) }
						</p>
					</div>

					<div className="ath-table-wrapper">
						<table className="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th className="ath-col-name">{ __( 'Hook Name', 'all-the-hooks' ) }</th>
									<th className="ath-col-type">{ __( 'Type', 'all-the-hooks' ) }</th>
									<th className="ath-col-source">{ __( 'Source', 'all-the-hooks' ) }</th>
									<th className="ath-col-file">{ __( 'File', 'all-the-hooks' ) }</th>
									<th className="ath-col-listeners">{ __( 'Listeners', 'all-the-hooks' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ filteredHooks.map( ( hook, index ) => (
									<tr key={ index }>
										<td className="ath-col-name">
											<strong>{ hook.name }</strong>
											{ hook.listeners && hook.listeners.length > 0 && (
												<details className="ath-listeners-details">
													<summary>
														{ __( 'View Listeners', 'all-the-hooks' ) } ({ hook.listeners.length })
													</summary>
													<div className="ath-listeners-list">
														{ hook.listeners.map( ( listener, lidx ) => (
															<div key={ lidx } className="ath-listener-item">
																<code>{ listener.callback }</code>
																<span className="ath-listener-meta">
																	{ __( 'Priority:', 'all-the-hooks' ) } { listener.priority } |{ ' ' }
																	{ __( 'Args:', 'all-the-hooks' ) } { listener.accepted_args }
																</span>
																<small>{ listener.file }:{ listener.line }</small>
															</div>
														) ) }
													</div>
												</details>
											) }
										</td>
										<td className="ath-col-type">
											<span className={ `ath-badge ath-badge-${ hook.type }` }>{ hook.type }</span>
										</td>
										<td className="ath-col-source">
											<span className={ `ath-badge ath-badge-${ hook.is_core === 'yes' ? 'core' : 'custom' }` }>
												{ hook.is_core === 'yes' ? __( 'Core', 'all-the-hooks' ) : __( 'Custom', 'all-the-hooks' ) }
											</span>
										</td>
										<td className="ath-col-file">
											<code className="ath-file-path">
												{ hook.file }:{ hook.line_number }
											</code>
										</td>
										<td className="ath-col-listeners">
											<span className={ `ath-badge ${ hook.listeners?.length > 0 ? 'ath-badge-success' : '' }` }>
												{ hook.listeners?.length || 0 }
											</span>
										</td>
									</tr>
								) ) }
							</tbody>
						</table>
					</div>
				</CardBody>
			</Card>
		</div>
	);
}
