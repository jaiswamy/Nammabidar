import { Fragment, render, useEffect, useState } from '@wordpress/element';
import './admin.scss';
import Guide from './guide';

/**
 * Determine whether to show a site guide in the rest of the WordPress Admin.
 */
const SiteGuideInWPDashboard = () => {

	const isBlockEditor = document.body.className.indexOf( 'block-editor-page' ) > -1;

	if ( isBlockEditor || typeof pixcareSiteGuides === 'undefined' || typeof pixcareSiteGuides.guides === 'undefined' ) {
		return null;
	}

	const guides = pixcareSiteGuides.guides;
	if ( ! Object.keys(guides).length ) {
		return null;
	}

	const [ isOpen, setOpen ] = useState( false );
	const [ siteGuideId, setSiteGuideId ] = useState();

	// We need to determine if there is a site guide to autoshow.
	const shownSiteGuidesIds = JSON.parse( localStorage.getItem('pixcare_siteGuidesShownIds' ) ) || [];

	useEffect(() => {
		// Determine if we should auto-open a guide.
		let autoOpenSiteGuideId = false;
		const guidesIds = Object.keys( guides );
		for ( let i = 0; i < guidesIds.length; i++ ) {
			const guideId = guidesIds[i];

			if ( guides[guideId].auto_open && ! shownSiteGuidesIds.includes(guideId) ) {
				autoOpenSiteGuideId = guideId;
				break;
			}
		}
		if ( false !== autoOpenSiteGuideId ) {
			setOpen(true);
			setSiteGuideId( autoOpenSiteGuideId );
		}
	}, [guides] );

	const siteGuideLinks = document.querySelectorAll('.pixcare-site-guide-link');
	siteGuideLinks.forEach( function( link ) {
		link.addEventListener( 'click', (e) => {
			setOpen(true);
			setSiteGuideId( e.currentTarget.getAttribute('data-siteguideid') );
		})
	});

	return (
		<Fragment>
			{isOpen && <Guide
				onFinish={() => {
					setOpen( false );

					if ( -1 === shownSiteGuidesIds.indexOf( siteGuideId ) ) {
						shownSiteGuidesIds.push( siteGuideId );
						localStorage.setItem( 'pixcare_siteGuidesShownIds', JSON.stringify( shownSiteGuidesIds ) );
					}
				}}
				siteGuideId={siteGuideId}
			/>}
		</Fragment>
	);
};

document.addEventListener( 'DOMContentLoaded', () => {
	const popup = document.createElement( 'div' );
	const body = document.body.appendChild( popup );
	if ( body ) {
		render(
			<SiteGuideInWPDashboard/>,
			body
		);
	}
} );
