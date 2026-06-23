import { LuChevronRight } from 'react-icons/lu';

function InfoCard( { title, desc, icon, buttonText, link } ) {
	return (
		<div className="fcf7-info-card">
			<div className="fcf7-card-icon">{ icon }</div>
			<h4 className="fcf7-card-title">{ title }</h4>
			<p className="fcf7-card-desc">{ desc }</p>
			<a className="fcf7-card-link" href={ link } target="_blank" rel="noopener noreferrer">
				{ buttonText }
				<span><LuChevronRight /></span>
			</a>
		</div>
	);
}

export default InfoCard;
