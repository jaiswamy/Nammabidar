/**
 * The Main Js file. Written with ESNext standard and JSX support – build step required.
 * This one gets loaded in the WordPress Block Editor
 * It adds an additional tab in the WordPress Block Editor's sidebar.
 */

import {
	PluginSidebar as PluginSidebarEditPost
} from '@wordpress/edit-post';
import { PluginSidebar as PluginSidebarEditSite } from '@wordpress/edit-site';

/**
 * Internal dependencies
 */
import './style.scss';
import GuideList from './templates/guide-list';

import { PanelBody } from '@wordpress/components';
import { registerPlugin } from '@wordpress/plugins';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { help as helpIcon } from '@wordpress/icons';
import Guide from './templates/guide';

/**
 * Determine whether to show the guides in the Block Editor's sidebar.
 */
function showGuideList () {
	return !!Object.keys( pixcareSiteGuides.guides ).length;
}

/**
 * Register the Site Guides plugin in the Block Editor's sidebar
 * @since wp 5.4
 * @see https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-document-setting-panel/
 */

const SiteGuidesPluginRegisterSidebar = () => {
	if ( typeof pixcareSiteGuides === 'undefined' || typeof pixcareSiteGuides.guides === 'undefined' ) {
		return null;
	}

	const [ isOpen, setOpen ] = useState( false );
	const [ siteGuideId, setSiteGuideId ] = useState();

	const guides = pixcareSiteGuides.guides;
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

			// Make sure that the site guides sidebar is open
			wp.data.dispatch("core/edit-post").openGeneralSidebar( 'pixcare-site-guides-plugin-panel/pixcare-site-guides-edit-post' );
			wp.data.dispatch("core/edit-site").openGeneralSidebar( 'pixcare-site-guides-plugin-panel/pixcare-site-guides-edit-site' );
		}
	}, [guides] );

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
			{showGuideList() &&
				<PluginSidebarEditPost
					name="pixcare-site-guides-edit-post"
					title="Help Guides"
					className="pixcare-site-guides"
					icon={helpIcon}
				>
					<PanelBody>
						<p className="description">A list of quick guides for this context.</p>
						<GuideList
							setOpen={setOpen}
							setSiteGuideId={setSiteGuideId}
						/>
					</PanelBody>
				</PluginSidebarEditPost>}

			{showGuideList() &&
				<PluginSidebarEditSite
					name="pixcare-site-guides-edit-site"
					title="Help Guides"
					className="pixcare-site-guides"
					icon={helpIcon}
				>
					<PanelBody>
						<p className="description">A list of quick guides for this context.</p>
						<GuideList
							setOpen={setOpen}
							setSiteGuideId={setSiteGuideId}
						/>
					</PanelBody>
				</PluginSidebarEditSite>}
		</Fragment>
	);
};

registerPlugin( 'pixcare-site-guides-plugin-panel', {
	render: function () {
		return (
				<SiteGuidesPluginRegisterSidebar/>
		);
	},
	icon: 'dashicons-editor-help'
} );
