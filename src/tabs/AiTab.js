/**
 * WordPress dependencies
 */
import {
	ExternalLink,
	FormToggle,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Callout from '../components/Callout';
import { AreaRow, SelectRow, SwitchRow, TextRow } from '../components/Fields';
import Section from '../components/Section';
import SettingRow from '../components/SettingRow';
import TypeToggles from '../components/TypeToggles';

const SIGNALS = [
	{ value: '', label: __( 'Not specified', 'lw-seo' ) },
	{ value: 'yes', label: __( 'Allow', 'lw-seo' ) },
	{ value: 'no', label: __( 'Disallow', 'lw-seo' ) },
];

const GROUPS = [
	[ 'training', __( 'Training crawlers', 'lw-seo' ) ],
	[ 'search', __( 'AI search crawlers', 'lw-seo' ) ],
	[ 'user', __( 'User-triggered fetchers', 'lw-seo' ) ],
];

function CrawlerCard( { crawler, store } ) {
	const key = `block_${ crawler.key }`;
	const blocked = !! store.data.options[ key ];
	const id = `lw-seo-crawler-${ crawler.key }`;
	return (
		<label
			className={ `lw-admin-crawler ${ blocked ? 'is-blocked' : '' }` }
			htmlFor={ id }
		>
			<FormToggle
				id={ id }
				checked={ blocked }
				onChange={ ( event ) => store.set( key, event.target.checked ) }
			/>
			<span className="lw-admin-stack">
				<strong>{ crawler.agent }</strong>
				<span className="lw-admin-hint">{ crawler.company }</span>
			</span>
			<span className="lw-admin-crawler__state">
				{ blocked
					? __( 'Blocked', 'lw-seo' )
					: __( 'Allowed', 'lw-seo' ) }
			</span>
		</label>
	);
}

export default function AiTab( { store } ) {
	const { options, meta } = store.data;

	return (
		<>
			<Section
				title={ __( 'Content Signals', 'lw-seo' ) }
				description={ __(
					'Tell AI systems how they may use your content. Sent as a Content-Signal HTTP header, a meta tag and a robots.txt line. "Not specified" neither grants nor restricts that use.',
					'lw-seo'
				) }
			>
				<SelectRow
					title={ __( 'Search', 'lw-seo' ) }
					store={ store }
					name="content_signals_search"
					options={ SIGNALS }
				/>
				<SelectRow
					title={ __( 'AI Input (RAG, grounding)', 'lw-seo' ) }
					store={ store }
					name="content_signals_ai_input"
					options={ SIGNALS }
				/>
				<SelectRow
					title={ __( 'AI Training', 'lw-seo' ) }
					store={ store }
					name="content_signals_ai_train"
					options={ SIGNALS }
				/>
			</Section>

			<Section
				title={ __( 'llms.txt File', 'lw-seo' ) }
				description={
					<>
						{ __( 'Your llms.txt:', 'lw-seo' ) }{ ' ' }
						<ExternalLink href={ meta.urls.llms }>
							{ meta.urls.llms }
						</ExternalLink>{ ' ' }
						·{ ' ' }
						<ExternalLink href="https://llmstxt.org/">
							{ __( 'Learn more', 'lw-seo' ) }
						</ExternalLink>
					</>
				}
			>
				<SwitchRow
					title={ __( 'llms.txt', 'lw-seo' ) }
					store={ store }
					name="llms_txt_enabled"
					onText={ __( 'Enable llms.txt for AI crawlers', 'lw-seo' ) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
				<TextRow
					title={ __( 'Summary', 'lw-seo' ) }
					help={ __(
						'One sentence shown as the summary line. Empty = the site tagline.',
						'lw-seo'
					) }
					store={ store }
					name="llms_txt_summary"
				/>
				<AreaRow
					title={ __( 'Introduction', 'lw-seo' ) }
					help={ __(
						'Optional Markdown text shown before the page lists. Do not use headings.',
						'lw-seo'
					) }
					store={ store }
					name="llms_txt_intro"
					rows={ 4 }
				/>
				<SettingRow
					title={ __( 'Content types', 'lw-seo' ) }
					help={ __(
						'Each type becomes its own section. Noindex content and content with AI Input set to "No" are left out.',
						'lw-seo'
					) }
				>
					<TypeToggles
						items={ meta.post_types }
						value={ options.llms_txt_post_types }
						fallback
						onChange={ ( map ) =>
							store.set( 'llms_txt_post_types', map )
						}
					/>
				</SettingRow>
				<SettingRow
					title={ __( 'Items per section', 'lw-seo' ) }
					help={ __(
						'Pages are listed in menu order, other types newest first.',
						'lw-seo'
					) }
				>
					<NumberControl
						__next40pxDefaultSize
						label={ __( 'Items per section', 'lw-seo' ) }
						hideLabelFromVision
						min={ 1 }
						max={ 500 }
						value={ options.llms_txt_max_items }
						onChange={ ( value ) =>
							store.set(
								'llms_txt_max_items',
								Math.min(
									500,
									Math.max( 1, parseInt( value, 10 ) || 1 )
								)
							)
						}
					/>
				</SettingRow>
				<SwitchRow
					title={ __( 'Markdown links', 'lw-seo' ) }
					store={ store }
					name="llms_txt_markdown_links"
					onText={ __(
						'Link to the Markdown version of each page (/md)',
						'lw-seo'
					) }
					offText={ __( 'Link to the HTML page', 'lw-seo' ) }
				/>
				<AreaRow
					title={ __( 'Extra links', 'lw-seo' ) }
					help={ __(
						'One per line: Title | https://url | optional description. Listed under "Optional".',
						'lw-seo'
					) }
					store={ store }
					name="llms_txt_optional_links"
					rows={ 4 }
					mono
				/>
				<SwitchRow
					title={ __( 'llms-full.txt', 'lw-seo' ) }
					help={ __(
						'Built as a logged-out visitor. Content-restriction plugins that only protect the main page query may not apply here; use the lw_seo_post_is_eligible filter to leave restricted posts out.',
						'lw-seo'
					) }
					store={ store }
					name="llms_full_txt_enabled"
					onText={ __(
						'Also serve /llms-full.txt with the full Markdown content (max 1 MB)',
						'lw-seo'
					) }
					offText={ __( 'Off', 'lw-seo' ) }
				/>
			</Section>

			<Section
				title={ __( 'AI Crawler Access', 'lw-seo' ) }
				description={ __(
					'Blocked crawlers get a "Disallow: /" rule in robots.txt.',
					'lw-seo'
				) }
			>
				<SwitchRow
					title={ __( 'All AI training crawlers', 'lw-seo' ) }
					help={ __(
						'Covers every crawler with that purpose, including ones added in future updates.',
						'lw-seo'
					) }
					store={ store }
					name="block_purpose_training"
					onText={ __( 'Blocked', 'lw-seo' ) }
					offText={ __( 'Allowed', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'All AI search crawlers', 'lw-seo' ) }
					store={ store }
					name="block_purpose_search"
					onText={ __( 'Blocked', 'lw-seo' ) }
					offText={ __( 'Allowed', 'lw-seo' ) }
				/>
				<SwitchRow
					title={ __( 'All user-triggered AI fetchers', 'lw-seo' ) }
					store={ store }
					name="block_purpose_user"
					onText={ __( 'Blocked', 'lw-seo' ) }
					offText={ __( 'Allowed', 'lw-seo' ) }
				/>
				{ GROUPS.map( ( [ purpose, label ] ) => (
					<div key={ purpose } className="lw-admin-stack">
						<span className="lw-admin-label">{ label }</span>
						<div className="lw-admin-crawlers">
							{ meta.crawlers
								.filter( ( c ) => c.purpose === purpose )
								.map( ( crawler ) => (
									<CrawlerCard
										key={ crawler.key }
										crawler={ crawler }
										store={ store }
									/>
								) ) }
						</div>
					</div>
				) ) }
				<Callout tone="warning">
					{ __(
						'robots.txt is a request, not an enforcement. OpenAI, Perplexity, Meta and Amazon state that their user-triggered fetchers may ignore it.',
						'lw-seo'
					) }
				</Callout>
			</Section>
		</>
	);
}
