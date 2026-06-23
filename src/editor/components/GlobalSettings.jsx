import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { DATA } from '../shared';
import { hooks } from '../field-preview-registry';
import DeviceSettings from './DeviceSettings';
import Popover from './Popover';
import SpamProtectionTab from './SpamProtectionTab';
import WebhookTab from './WebhookTab';

const TABS = [
	{ id: 'breakpoints', label: __( 'General', 'compactform' ), icon: 'ri-layout-4-line' },
	{ id: 'webhook', label: __( 'Webhook', 'compactform' ), icon: 'ri-send-plane-line', show: !! DATA.integrations?.webhook?.active },
	{ id: 'spamProtection', label: __( 'Spam Protection', 'compactform' ), icon: 'ri-shield-keyhole-line', show: !! DATA.configuration?.spamProtection?.active },
].filter( ( t ) => t.show !== false );

export default function GlobalSettings( {
	schema,
	setSchema,
	breakpoints = {},
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
	fields = [],
} ) {
	const [ tab, setTab ] = useState( TABS[ 0 ].id );

	const extraTabs = hooks.applyFilters( 'fcf7b.globalSettingsTabs', [] );
	const allTabs = [ ...TABS, ...extraTabs ];
	const activeExtraTab = extraTabs.find( ( t ) => t.id === tab );

	return (
		<Popover
			className="fcf7b-gs"
			triggerClassName="fcf7b-ghost"
			popClassName="fcf7b-gs-pop"
			backdropClassName="fcf7b-gs-backdrop"
			title={ __( 'Global settings', 'compactform' ) }
			trigger={ <i className="ri-settings-3-line" /> }
		>
			{ ( { close } ) => (
				<>
					<div className="fcf7b-gs-head">
						Global Settings
						<button type="button" className="fcf7b-gs-close" title="Close" onClick={ close }>
							<i className="ri-close-line" />
						</button>
					</div>

					<div className="fcf7b-gs-main">
						<div className="fcf7b-gs-sidebar">
							{ allTabs.map( ( t ) => (
								<button
									key={ t.id }
									type="button"
									className={ `fcf7b-gs-navitem${ tab === t.id ? ' active' : '' }` }
									onClick={ () => setTab( t.id ) }
								>
									<i className={ t.icon } />
									{ t.label }
								</button>
							) ) }
						</div>

						<div className="fcf7b-gs-body">
							{ tab === 'breakpoints' && (
								<DeviceSettings
									breakpoints={ breakpoints }
									onChange={ onBreakpointsChange }
									fieldGap={ fieldGap }
									onFieldGapChange={ onFieldGapChange }
									containerColumnGap={ containerColumnGap }
									onContainerColumnGapChange={ onContainerColumnGapChange }
									containerRowGap={ containerRowGap }
									onContainerRowGapChange={ onContainerRowGapChange }
									containerPadding={ containerPadding }
									onContainerPaddingChange={ onContainerPaddingChange }
								/>
							) }
							{ tab === 'webhook' && (
								<WebhookTab value={ webhook } onChange={ onWebhookChange } fields={ fields } />
							) }
							{ tab === 'spamProtection' && (
								<SpamProtectionTab value={ spamProtection } onChange={ onSpamProtectionChange } />
							) }
							{ activeExtraTab && activeExtraTab.render( { schema, setSchema, fields } ) }
						</div>
					</div>
				</>
			) }
		</Popover>
	);
}
