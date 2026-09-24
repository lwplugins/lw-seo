/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { SwitchRow, TextRow } from '../components/Fields';
import Section from '../components/Section';
import Vars from '../components/Vars';

const on = __( 'noindex', 'lw-seo' );
const off = __( 'Indexed', 'lw-seo' );

export default function ContentTab( { store } ) {
	const vars = (
		<Vars
			names={ [
				'title',
				'sitename',
				'sep',
				'category',
				'author',
				'date',
				'term_title',
			] }
		/>
	);

	return (
		<>
			<Section
				title={ __( 'Posts and pages', 'lw-seo' ) }
				description={ vars }
			>
				<TextRow
					title={ __( 'Post Title Template', 'lw-seo' ) }
					store={ store }
					name="title_post"
					mono
				/>
				<SwitchRow
					title={ __( 'Posts', 'lw-seo' ) }
					help={ __( 'Set posts to noindex by default.', 'lw-seo' ) }
					store={ store }
					name="noindex_post"
					onText={ on }
					offText={ off }
				/>
				<TextRow
					title={ __( 'Page Title Template', 'lw-seo' ) }
					store={ store }
					name="title_page"
					mono
				/>
				<SwitchRow
					title={ __( 'Pages', 'lw-seo' ) }
					help={ __( 'Set pages to noindex by default.', 'lw-seo' ) }
					store={ store }
					name="noindex_page"
					onText={ on }
					offText={ off }
				/>
			</Section>
			<Section title={ __( 'Taxonomies', 'lw-seo' ) }>
				<TextRow
					title={ __( 'Category Title Template', 'lw-seo' ) }
					store={ store }
					name="title_category"
					mono
				/>
				<SwitchRow
					title={ __( 'Categories', 'lw-seo' ) }
					help={ __( 'Set categories to noindex.', 'lw-seo' ) }
					store={ store }
					name="noindex_category"
					onText={ on }
					offText={ off }
				/>
				<TextRow
					title={ __( 'Tag Title Template', 'lw-seo' ) }
					store={ store }
					name="title_post_tag"
					mono
				/>
				<SwitchRow
					title={ __( 'Tags', 'lw-seo' ) }
					help={ __( 'Set tags to noindex.', 'lw-seo' ) }
					store={ store }
					name="noindex_post_tag"
					onText={ on }
					offText={ off }
				/>
			</Section>
			<Section title={ __( 'Archives and special pages', 'lw-seo' ) }>
				<TextRow
					title={ __( 'Author Archive Title', 'lw-seo' ) }
					store={ store }
					name="title_author"
					mono
				/>
				<SwitchRow
					title={ __( 'Author Archives', 'lw-seo' ) }
					help={ __( 'Set author archives to noindex.', 'lw-seo' ) }
					store={ store }
					name="noindex_author"
					onText={ on }
					offText={ off }
				/>
				<TextRow
					title={ __( 'Date Archive Title', 'lw-seo' ) }
					store={ store }
					name="title_date"
					mono
				/>
				<SwitchRow
					title={ __( 'Date Archives', 'lw-seo' ) }
					help={ __( 'Set date archives to noindex.', 'lw-seo' ) }
					store={ store }
					name="noindex_date"
					onText={ on }
					offText={ off }
				/>
				<TextRow
					title={ __( 'Search Results Title', 'lw-seo' ) }
					help={
						<Vars names={ [ 'searchphrase', 'sep', 'sitename' ] } />
					}
					store={ store }
					name="title_search"
					mono
				/>
				<TextRow
					title={ __( '404 Page Title', 'lw-seo' ) }
					store={ store }
					name="title_404"
					mono
				/>
			</Section>
		</>
	);
}
