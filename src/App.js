/**
 * WordPress dependencies
 */
import { Notice } from '@wordpress/components';
import { useCallback, useMemo } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import FormSkeleton from './components/FormSkeleton';
import LoadError from './components/LoadError';
import Notices from './components/Notices';
import useSettingsStore from './data/useSettingsStore';
import QuickSearch from './quick-search';
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

// Settings tabs that render purely from the loaded options (no requests or
// effects). Redirects, 404 log and Import fetch data, so they are found by
// name only.
const SEARCHABLE = [
	'general',
	'content',
	'social',
	'sitemap',
	'ai',
	'woocommerce',
	'local',
	'advanced',
];

/**
 * Shell + one options store shared by every settings tab (partial saves).
 */
export default function App() {
	const store = useSettingsStore();
	const woo = store.data?.meta.woo_active ?? true;
	const tabs = useMemo(
		() => TABS.filter( ( tab ) => ! tab.woo || woo ),
		[ woo ]
	);
	const tab = useTab(
		tabs.map( ( t ) => t.id ),
		'general'
	);
	const current = tabs.find( ( t ) => t.id === tab ) || tabs[ 0 ];
	const View = VIEWS[ current.id ];

	const searchPages = useCallback(
		() =>
			store.data
				? tabs
						.filter( ( t ) => SEARCHABLE.includes( t.id ) )
						.map( ( t ) => {
							const Tab = VIEWS[ t.id ];
							return {
								tab: t,
								element: <Tab store={ store } />,
							};
						} )
				: null,
		// The index is read once per load; later edits are not re-indexed.
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[ store.data?.meta, tabs ]
	);

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
						{ sprintf(
							/* translators: %s: name of the other active SEO plugin, e.g. Yoast SEO. */
							__(
								'Another SEO plugin (%s) is active. LW SEO skips its meta tags to avoid duplicates. You can import its data on the Import tab, then deactivate it to use LW SEO fully.',
								'lw-seo'
							),
							store.data.meta.conflict_plugin
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
				<SideNav
					tabs={ tabs }
					current={ current.id }
					search={
						<QuickSearch
							tabs={ tabs }
							pages={ searchPages }
							storageKey="lw-seo-quick-search"
						/>
					}
				/>
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
