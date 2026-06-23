import { __, _n, sprintf } from '@wordpress/i18n';
import { BY_TYPE, cf7Name, duplicateNames } from '../shared';
import Popover from './Popover';

export function formIssues( fields ) {
	const list = Array.isArray( fields ) ? fields : [];
	const out = [];

	duplicateNames( list ).forEach( ( name ) => {
		out.push( {
			id: `dup-name:${ name }`,
			title: sprintf(
				/* translators: %s: the duplicated field name. */
				__( 'Duplicate field name "%s"', 'compactform' ),
				name
			),
			detail: __( 'These fields post under one key, so only one of them collects an answer.', 'compactform' ),
			fields: list.filter( ( f ) => cf7Name( f.name ) === name ),
		} );
	} );

	return out;
}

export default function Issues( { fields, actions } ) {
	const issues = formIssues( fields );
	const n = issues.length;

	return (
		<Popover
			className="fcf7b-issues"
			triggerClassName={ `fcf7b-ghost${ n ? ' has-issues' : '' }` }
			popClassName="fcf7b-issues-pop"
			title={ n
				? sprintf(
					/* translators: %d: number of issues found in the form. */
					_n( '%d issue in this form', '%d issues in this form', n, 'compactform' ),
					n
				)
				: __( 'No issues found', 'compactform' ) }
			trigger={ (
				<>
					<i className={ n ? 'ri-error-warning-fill' : 'ri-shield-check-line' } />
					{ n ? <span className="fcf7b-issues-count">{ n }</span> : null }
				</>
			) }
		>
			{ ( { close } ) => (
				<>
					<div className="fcf7b-issues-head">{ n ? `${ n } issue${ 1 === n ? '' : 's' }` : 'No issues' }</div>

					{ ! n && (
						<div className="fcf7b-issues-ok">Every field has a name of its own.</div>
					) }

					{ issues.map( ( issue ) => (
						<div key={ issue.id } className="fcf7b-issues-item">
							<div className="fcf7b-issues-title">
								<i className="ri-error-warning-fill" aria-hidden="true" />
								<span>{ issue.title }</span>
							</div>
							<p className="fcf7b-issues-detail">{ issue.detail }</p>
							{ issue.fields.map( ( f ) => (
								<button
									key={ f.id }
									type="button"
									className="fcf7b-issues-field"
									onClick={ () => { actions.select( f.id ); close(); } }
								>
									<i className={ ( BY_TYPE[ f.type ] || {} ).icon || 'ri-square-line' } aria-hidden="true" />
									<span className="fcf7b-issues-field-label">{ f.label || ( BY_TYPE[ f.type ] || {} ).title || f.type }</span>
									<span className="fcf7b-issues-field-name">{ cf7Name( f.name ) }</span>
								</button>
							) ) }
						</div>
					) ) }
				</>
			) }
		</Popover>
	);
}
