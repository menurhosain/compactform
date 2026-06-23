import { __ } from '@wordpress/i18n';
import InfoCard from '../components/InfoCard';
import { LuHeadset, LuStar, LuBookOpenText } from 'react-icons/lu';

const Welcome = () => {
	return (
		<>
			<div className="fcf7-banner">
				<div className="fcf7-banner-text">
					<h2>{ __( 'Welcome to CompactForm', 'compactform' ) }</h2>
					<p>
						{ __(
							'Advanced fields and stylings for Contact Form 7, built by RSTheme. Enable what you need — each extension loads its assets only when a form actually uses it.',
							'compactform'
						) }
					</p>
				</div>
				<div className="fcf7-banner-stat">
					<span className="fcf7-banner-stat-num">
						{ FCF7Local?.activeCount ?? 0 }
						<span>/{ FCF7Local?.totalCount ?? 0 }</span>
					</span>
					<span className="fcf7-banner-stat-label">{ __( 'Active', 'compactform' ) }</span>
				</div>
			</div>

			<div className="fcf7-welcome-grid">
				<InfoCard
					icon={ <LuHeadset /> }
					title={ __( 'Need any help?', 'compactform' ) }
					desc={ __(
						'Run into an issue or need a hand? Our team is here to help on the support portal.',
						'compactform'
					) }
					buttonText={ __( 'Create a ticket', 'compactform' ) }
					link={ FCF7Local?.links?.support }
				/>
				<InfoCard
					icon={ <LuStar /> }
					title={ __( 'Enjoying the plugin?', 'compactform' ) }
					desc={ __(
						'A quick review really helps and keeps development going.',
						'compactform'
					) }
					buttonText={ __( 'Leave a review', 'compactform' ) }
					link={ FCF7Local?.links?.review }
				/>
				<InfoCard
					icon={ <LuBookOpenText /> }
					title={ __( 'Knowledge base', 'compactform' ) }
					desc={ __(
						'Read the docs to learn how to set up each extension step by step.',
						'compactform'
					) }
					buttonText={ __( 'Read documentation', 'compactform' ) }
					link={ FCF7Local?.links?.docs }
				/>
			</div>
		</>
	);
};

export default Welcome;
