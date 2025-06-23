/**
 * Template for displaying a List of Site Guides in the Gutenberg Sidebar.
 */

import { Fragment } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SiteGuidesList = ( props ) => {
	const { setOpen, setSiteGuideId } = props;
	const guides = pixcareSiteGuides.guides;
	const guidesNum = Object.keys( guides ).length;

	return (<Fragment>
			{guidesNum === 0 && <span>{__( 'No guides available right now.' )}</span>}
			{guidesNum > 0 && Object.keys( guides ).map( ( guideId, index ) => (
				<div key={guides[guideId]._uid} className="pixcare-guide-list-item">
					{index === 0 && guidesNum === 1 ? '' : <span>{index + 1 + '. '}</span>}
					<Button className="guide-btn" key={guides[guideId]._uid} onClick={() => {
						setOpen( true );
						setSiteGuideId( guideId );
					}}>
						{guides[guideId].label}
					</Button>
				</div>) )}
		</Fragment>);
};

export default SiteGuidesList;
