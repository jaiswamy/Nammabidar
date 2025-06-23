/**
 * Template for displaying a Site Guide
 */

import { Guide } from '@wordpress/components';

const SiteGuide = ( props ) => {
	const guides = pixcareSiteGuides.guides;
	const { siteGuideId } = props;
	let containerClasses = "pixcare-site-guide";
	if ( typeof guides[siteGuideId] !== 'undefined' && guides[siteGuideId].pages.length === 1 ) {
		containerClasses += ' single-page';
	}

	return (
		typeof guides[siteGuideId] !== 'undefined' &&
		<>
			{!!guides[siteGuideId].custom_css && <style>{guides[siteGuideId].custom_css}</style>}
			<Guide {...props}
				   className={ containerClasses + ' ' + guides[siteGuideId]?.container_classes.join(' ') }
				   pages={guides[siteGuideId].pages.map( ( page, index ) => (
					   {
						   image: page.image ? <div className="site-guide-image"><img src={page.image} alt={ "Site guide #"+index+"page image"} /></div> : '',
						   content: (
							   <div
								   className="site-guide-content"
								   dangerouslySetInnerHTML={{ __html: page.content }}/>
						   )
					   }
				   ) )
				   }
				   contentLabel={guides[siteGuideId].label}
				   finishButtonText={guides[siteGuideId].finish_button_label}
			/>
		</>
	);
};

export default SiteGuide;
