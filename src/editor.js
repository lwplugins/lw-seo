/**
 * WordPress dependencies
 */
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import SeoPanel from './editor/SeoPanel';
import './editor/editor.scss';

// WordPress 6.6+ exports the panel from @wordpress/editor; older sites only
// have it on wp.editPost.
const Panel =
	PluginDocumentSettingPanel ||
	window.wp?.editPost?.PluginDocumentSettingPanel;

registerPlugin( 'lw-seo', {
	render: () => (
		<Panel
			name="lw-seo"
			title={ __( 'LW SEO', 'lw-seo' ) }
			className="lw-seo-document-panel"
		>
			<SeoPanel />
		</Panel>
	),
} );
