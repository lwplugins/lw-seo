/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import {
	SkeletonBlock,
	SkeletonRegion,
	SkeletonRows,
	SkeletonSection,
	SkeletonText,
} from '.';

/**
 * First paint of the whole screen, before the settings arrive: the same
 * sidebar + top bar + content frame as the Dashboard.
 */
export default function DashboardSkeleton() {
	return (
		<SkeletonRegion
			label={ __( 'Loading settings…', 'lw-seo' ) }
			className="lw-admin-shell"
		>
			<aside className="lw-admin-sidebar">
				<div className="lw-admin-sidebar__head">
					<SkeletonBlock width={ 24 } height={ 24 } />
					<SkeletonText width={ 110 } size="lg" />
				</div>
				<span className="lw-skel-stack lw-skel-nav">
					{ Array.from( { length: 7 }, ( _, index ) => (
						<SkeletonText
							key={ index }
							width={ `${ 55 + ( ( index * 13 ) % 35 ) }%` }
						/>
					) ) }
				</span>
			</aside>
			<div className="lw-admin-main">
				<div className="lw-admin-topbar">
					<SkeletonText width={ 200 } size="lg" />
					<SkeletonBlock width={ 96 } height={ 40 } />
				</div>
				<div className="lw-admin-scroll">
					<div className="lw-admin-content">
						<SkeletonSection description={ false }>
							<SkeletonBlock height={ 44 } />
							<SkeletonRows count={ 2 } />
						</SkeletonSection>
						<SkeletonSection description={ false }>
							<SkeletonRows count={ 3 } />
						</SkeletonSection>
					</div>
				</div>
			</div>
		</SkeletonRegion>
	);
}
