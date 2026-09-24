/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	cloudDownload,
	cog,
	commentAuthorAvatar,
	mapMarker,
	notFound,
	page,
	postList,
	redo,
	share,
	shipping,
	tool,
} from '@wordpress/icons';

/**
 * Tab registry. `save` = the tab edits lw_seo_options (top bar Save shown).
 * `woo` = only when WooCommerce is active.
 */
export const TABS = [
	{
		id: 'general',
		label: __( 'General', 'lw-seo' ),
		title: __( 'General Settings', 'lw-seo' ),
		icon: cog,
		save: true,
	},
	{
		id: 'content',
		label: __( 'Content', 'lw-seo' ),
		title: __( 'Content Types', 'lw-seo' ),
		icon: postList,
		save: true,
	},
	{
		id: 'social',
		label: __( 'Social', 'lw-seo' ),
		title: __( 'Social Media', 'lw-seo' ),
		icon: share,
		save: true,
	},
	{
		id: 'sitemap',
		label: __( 'Sitemap', 'lw-seo' ),
		title: __( 'XML Sitemap', 'lw-seo' ),
		icon: page,
		save: true,
	},
	{
		id: 'ai',
		label: __( 'AI / LLM', 'lw-seo' ),
		title: __( 'AI / LLM Settings', 'lw-seo' ),
		icon: commentAuthorAvatar,
		save: true,
	},
	{
		id: 'woocommerce',
		label: __( 'WooCommerce', 'lw-seo' ),
		title: __( 'WooCommerce SEO', 'lw-seo' ),
		icon: shipping,
		save: true,
		woo: true,
	},
	{
		id: 'local',
		label: __( 'Local SEO', 'lw-seo' ),
		title: __( 'Local SEO', 'lw-seo' ),
		icon: mapMarker,
		save: true,
	},
	{
		id: 'redirects',
		label: __( 'Redirects', 'lw-seo' ),
		title: __( 'Redirect Manager', 'lw-seo' ),
		icon: redo,
		save: true,
	},
	{
		id: '404',
		label: '404',
		title: __( '404 Error Handling', 'lw-seo' ),
		icon: notFound,
		save: true,
	},
	{
		id: 'advanced',
		label: __( 'Advanced', 'lw-seo' ),
		title: __( 'Advanced Settings', 'lw-seo' ),
		icon: tool,
		save: true,
	},
	{
		id: 'migration',
		label: __( 'Import', 'lw-seo' ),
		title: __( 'Import SEO Data', 'lw-seo' ),
		icon: cloudDownload,
		save: false,
	},
];
