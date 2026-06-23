import { __, _n, sprintf } from '@wordpress/i18n';
import GlobalSettings from './GlobalSettings';
import CanvasBackground from './CanvasBackground';
import TemplatesModal from './TemplatesModal';

export default function Toolbar( {
	view,
	setView,
	fieldsCount,
	renderLoading,
	device,
	setDevice,
	navOpen,
	setNavOpen,
	schema,
	setSchema,
	breakpoints,
	onBreakpointsChange,
	fieldGap,
	onFieldGapChange,
	containerColumnGap,
	onContainerColumnGapChange,
	containerRowGap,
	onContainerRowGapChange,
	containerPadding,
	onContainerPaddingChange,
	webhook,
	onWebhookChange,
	spamProtection,
	onSpamProtectionChange,
	fields,
	canUndo,
	canRedo,
	undo,
	redo,
	exportForm,
	onImportFile,
	importInput,
	importSchema,
	canvasBg,
	setCanvasBg,
	theme,
	setTheme,
	fullscreen,
	setFullscreen,
	saveState,
	dirty,
	saveError,
	saveProgress,
	quickSave,
} ) {
	const saveLabel = saveState === 'saving'
		? __( 'Saving…', 'compactform' )
		: saveState === 'saved'
			? __( 'Saved ✓', 'compactform' )
			: saveState === 'error'
				? __( 'Retry', 'compactform' )
				: __( 'Save', 'compactform' );
	const branding = window.FCF7Builder || {};
	const logoUrl = ( theme === 'dark' ? branding.logoLightUrl : branding.logoUrl ) || '';
	const saveCls = `fcf7b-save${ saveState === 'saved' ? ' fcf7b-save--saved' : '' }${ saveState === 'error' ? ' fcf7b-save--error' : '' }`;

	return (
			<div className="fcf7b-toolbar">
				<div className="fcf7b-brand">
					{ logoUrl
						? <img src={ logoUrl } alt="CompactForm" className="fcf7b-brand-logo" />
						: <>{ '⬤' } CompactForm</> }
				</div>
				<div className="fcf7b-tabs">
					<button type="button" className={ view === 'preview' ? 'active' : '' } onClick={ () => setView( 'preview' ) }>{ __( 'Builder', 'compactform' ) }</button>
					<button type="button" className={ view === 'code' ? 'active' : '' } onClick={ () => setView( 'code' ) }>{ __( 'Generated CF7', 'compactform' ) }</button>
				</div>
				<div className="fcf7b-tb-right">
					<span className="fcf7b-count">
						{ sprintf(
							/* translators: %d: number of fields on the canvas. */
							_n( '%d field', '%d fields', fieldsCount, 'compactform' ),
							fieldsCount
						) }
						{ renderLoading ? ' · ' + __( 'rendering…', 'compactform' ) : '' }
					</span>

					<div className="fcf7b-devices">
						{ [
							[ 'desktop', 'ri-computer-line', __( 'Desktop', 'compactform' ) ],
							[ 'tablet', 'ri-tablet-line', __( 'Tablet', 'compactform' ) ],
							[ 'mobile', 'ri-smartphone-line', __( 'Mobile', 'compactform' ) ],
						].map( ( [ d, icon, title ] ) => (
							<button
								key={ d }
								type="button"
								title={ title }
								className={ device === d ? 'active' : '' }
								onClick={ () => setDevice( d ) }
							><i className={ icon } /></button>
						) ) }
					</div>

					<div className="fcf7b-tb-group">
						<TemplatesModal onImport={ importSchema } />

						<button
							type="button"
							className={ `fcf7b-ghost${ navOpen ? ' is-set' : '' }` }
							title={ __( 'Navigator (form structure)', 'compactform' ) }
							disabled={ view !== 'preview' }
							onClick={ () => setNavOpen( ! navOpen ) }
						><i className="ri-stack-line" /></button>

						<GlobalSettings
							schema={ schema }
							setSchema={ setSchema }
							breakpoints={ breakpoints }
							onBreakpointsChange={ onBreakpointsChange }
							fieldGap={ fieldGap }
							onFieldGapChange={ onFieldGapChange }
							containerColumnGap={ containerColumnGap }
							onContainerColumnGapChange={ onContainerColumnGapChange }
							containerRowGap={ containerRowGap }
							onContainerRowGapChange={ onContainerRowGapChange }
							containerPadding={ containerPadding }
							onContainerPaddingChange={ onContainerPaddingChange }
							webhook={ webhook }
							onWebhookChange={ onWebhookChange }
							spamProtection={ spamProtection }
							onSpamProtectionChange={ onSpamProtectionChange }
							fields={ fields }
						/>
					</div>

					<div className="fcf7b-tb-group">
						<button type="button" className="fcf7b-ghost" title={ __( 'Undo (Ctrl+Z)', 'compactform' ) } disabled={ ! canUndo } onClick={ undo }>{ <i class="ri-arrow-go-back-line"></i> }</button>
						<button type="button" className="fcf7b-ghost" title={ __( 'Redo (Ctrl+Y)', 'compactform' ) } disabled={ ! canRedo } onClick={ redo }>{ <i class="ri-arrow-go-forward-line"></i> }</button>
						<span className="fcf7b-tb-div" aria-hidden="true" />
						<button type="button" className="fcf7b-ghost" title={ __( 'Export form (JSON)', 'compactform' ) } onClick={ exportForm }>{ <i class="ri-download-2-line"></i> }</button>
						<button type="button" className="fcf7b-ghost" title={ __( 'Import form (JSON)', 'compactform' ) } onClick={ () => importInput.current && importInput.current.click() }>{ <i class="ri-upload-2-line"></i> }</button>
						<input ref={ importInput } type="file" accept="application/json,.json" style={ { display: 'none' } } onChange={ onImportFile } />
						<span className="fcf7b-tb-div" aria-hidden="true" />
						<CanvasBackground value={ canvasBg } onChange={ setCanvasBg } disabled={ view !== 'preview' } />
						<button type="button" className="fcf7b-ghost" title={ theme === 'light' ? __( 'Dark theme', 'compactform' ) : __( 'Light theme', 'compactform' ) } onClick={ () => setTheme( theme === 'light' ? 'dark' : 'light' ) }>{ theme === 'light' ? <i class="ri-moon-line"></i> : <i class="ri-sun-line"></i> }</button>
						<button type="button" className="fcf7b-ghost" title={ fullscreen ? __( 'Exit fullscreen', 'compactform' ) : __( 'Fullscreen', 'compactform' ) } onClick={ () => setFullscreen( ! fullscreen ) }>{ fullscreen ? <i class="ri-fullscreen-exit-line"></i> : <i class="ri-fullscreen-line"></i> }</button>
					</div>
					<button type="button" className={ saveCls }
						disabled={ saveState === 'saving' || ( ! dirty && saveState !== 'error' ) }
						title={ saveState === 'error' ? saveError : ( dirty ? __( 'Save form layout', 'compactform' ) : __( 'No unsaved changes', 'compactform' ) ) }
						aria-busy={ saveState === 'saving' }
						onClick={ quickSave }>{ saveLabel }</button>
				</div>
				<div className={ `fcf7b-progress${ saveState === 'idle' ? '' : ' is-active' }${ saveState === 'error' ? ' fcf7b-progress--error' : '' }` }
					role="progressbar" aria-valuemin={ 0 } aria-valuemax={ 100 } aria-valuenow={ saveProgress }>
					<span className="fcf7b-progress-fill" style={ { width: `${ saveProgress }%` } } />
				</div>
			</div>
	);
}
