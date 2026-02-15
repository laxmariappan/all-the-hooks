/**
 * Results View Component using DataViews
 */
import { useState, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DataViews } from '@wordpress/dataviews';
import { Button } from '@wordpress/components';
import { download } from '@wordpress/icons';

export default function ResultsView( { results } ) {
	const [ view, setView ] = useState( {
		type: 'table',
		perPage: 20,
		page: 1,
		sort: {
			field: 'name',
			direction: 'asc',
		},
		search: '',
		filters: [],
		hiddenFields: [],
		layout: {},
	} );

	// Prepare data for DataViews
	const data = useMemo( () => {
		return results.hooks.map( ( hook, index ) => ( {
			id: index,
			...hook,
			listeners_count: hook.listeners?.length || 0,
		} ) );
	}, [ results.hooks ] );

	// Define fields for DataViews
	const fields = [
		{
			id: 'name',
			label: __( 'Hook Name', 'all-the-hooks' ),
			enableHiding: false,
			enableSorting: true,
			render: ( { item } ) => <strong>{ item.name }</strong>,
		},
		{
			id: 'type',
			label: __( 'Type', 'all-the-hooks' ),
			enableHiding: false,
			enableSorting: true,
			elements: [
				{ value: 'action', label: __( 'Action', 'all-the-hooks' ) },
				{ value: 'filter', label: __( 'Filter', 'all-the-hooks' ) },
			],
			render: ( { item } ) => (
				<span className={ `ath-badge ath-badge-${ item.type }` }>{ item.type }</span>
			),
			filterBy: {
				operators: [ 'is', 'isNot' ],
			},
		},
		{
			id: 'is_core',
			label: __( 'Source', 'all-the-hooks' ),
			enableSorting: true,
			elements: [
				{ value: 'yes', label: __( 'Core', 'all-the-hooks' ) },
				{ value: 'no', label: __( 'Custom', 'all-the-hooks' ) },
			],
			render: ( { item } ) => (
				<span className={ `ath-badge ath-badge-${ item.is_core === 'yes' ? 'core' : 'custom' }` }>
					{ item.is_core === 'yes' ? __( 'Core', 'all-the-hooks' ) : __( 'Custom', 'all-the-hooks' ) }
				</span>
			),
			filterBy: {
				operators: [ 'is', 'isNot' ],
			},
		},
		{
			id: 'file',
			label: __( 'File', 'all-the-hooks' ),
			enableSorting: true,
			render: ( { item } ) => (
				<code className="ath-file-path">
					{ item.file }:{ item.line_number }
				</code>
			),
		},
		{
			id: 'listeners_count',
			label: __( 'Listeners', 'all-the-hooks' ),
			enableSorting: true,
			render: ( { item } ) => (
				<span className={ `ath-badge ${ item.listeners_count > 0 ? 'ath-badge-success' : '' }` }>
					{ item.listeners_count }
				</span>
			),
		},
	];

	// Define actions
	const actions = [
		{
			id: 'view-details',
			label: __( 'View Details', 'all-the-hooks' ),
			isPrimary: true,
			callback: ( items ) => {
				// Show details modal or expand row
				console.log( 'View details for:', items );
			},
		},
	];

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

			<DataViews
				data={ data }
				fields={ fields }
				view={ view }
				onChangeView={ setView }
				actions={ actions }
				paginationInfo={ {
					totalItems: data.length,
					totalPages: Math.ceil( data.length / view.perPage ),
				} }
			/>
		</div>
	);
}
