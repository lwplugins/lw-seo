/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FormSkeleton from './components/FormSkeleton';
import LoadError from './components/LoadError';
import Notices from './components/Notices';
import useSettingsStore from './data/useSettingsStore';
import Footer from './shell/Footer';
import SideNav from './shell/SideNav';
import TopBar from './shell/TopBar';
import { TABS } from './shell/tabs';
import useSaveShortcut from './shell/useSaveShortcut';
import useTab from './shell/useTab';
import useUnsavedWarning from './shell/useUnsavedWarning';
import AdvancedTab from './tabs/AdvancedTab';
import AiTab from './tabs/AiTab';
import ContentTab from './tabs/ContentTab';
import GeneralTab from './tabs/GeneralTab';
import LocalTab from './tabs/LocalTab';
import MigrationTab from './tabs/MigrationTab';
import NotFoundTab from './tabs/NotFoundTab';
import RedirectsTab from './tabs/redirects/RedirectsTab';
import SitemapTab from './tabs/SitemapTab';
import SocialTab from './tabs/SocialTab';
import WooTab from './tabs/WooTab';

const VIEWS = {
	general: GeneralTab,
	content: ContentTab,
	social: SocialTab,
	sitemap: SitemapTab,
	ai: AiTab,
	woocommerce: WooTab,
	local: LocalTab,
	redirects: RedirectsTab,
	404: NotFoundTab,
	advanced: AdvancedTab,
	migration: MigrationTab,
};

/**
 * Shell + one options store shared by every settings tab (partial saves).
 */
export default function App() {
	const store = useSettingsStore();
	const woo = store.data?.meta.woo_active ?? true;
	const tabs = TABS.filter( ( tab ) => ! tab.woo || woo );
	const tab = useTab(
		tabs.map( ( t ) => t.id ),
		'general'
	);
	const current = tabs.find( ( t ) => t.id === tab ) || tabs[ 0 ];
	const View = VIEWS[ current.id ];

	useUnsavedWarning( store.hasEdits );
	useSaveShortcut( store.save, store.hasEdits && ! store.isSaving );

	let content;
	if ( store.error ) {
		content = (
			<LoadError message={ store.error } onRetry={ store.reload } />
		);
	} else if ( store.isLoading ) {
		content = <FormSkeleton />;
	} else {
		content = (
			<>
				{ store.data.meta.conflict_plugin && (
					<Notice status="warning" isDismissible={ false }>
						{ __(
							'Another SEO plugin (Yoast SEO, Rank Math or All in One SEO) is active. LW SEO skips its meta tags to avoid duplicates. Deactivate the other plugin to use LW SEO fully.',
							'lw-seo'
						) }
					</Notice>
				) }
				<View store={ store } />
			</>
		);
	}

	return (
		<>
			<div className="lw-admin-shell">
				<SideNav tabs={ tabs } current={ current.id } />
				<div className="lw-admin-main">
					<TopBar
						title={ current.title }
						store={ current.save ? store : null }
					/>
					<main className="lw-admin-scroll">
						<div className="lw-admin-content">{ content }</div>
					</main>
					<Footer />
				</div>
			</div>
			<Notices />
		</>
	);
}
