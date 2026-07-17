<?php
/**
 * Yoast knowledge-graph identity migrator.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Yoast;

/**
 * Migrates Yoast's knowledge-graph identity (company/person, name, logo) from
 * wpseo_titles into LW SEO knowledge_* options. Never overwrites filled slots.
 */
final class KnowledgeMigrator {

	/**
	 * Migrate knowledge-graph identity fields.
	 *
	 * @param array         $titles  Yoast wpseo_titles.
	 * @param array         $lw      LW options (by reference).
	 * @param array<string> $details Details log (by reference).
	 * @return int Number of migrated options.
	 */
	public function migrate( array $titles, array &$lw, array &$details ): int {
		$count = 0;

		if ( isset( $titles['company_or_person'] ) && ! $this->is_filled( $lw, 'knowledge_type' ) ) {
			$lw['knowledge_type'] = ( 'company' === $titles['company_or_person'] ) ? 'organization' : 'person';
			$details[]            = 'company_or_person -> knowledge_type';
			++$count;
		}

		$name = $titles['company_name'] ?? ( $titles['person_name'] ?? '' );
		if ( '' !== $name && ! $this->is_filled( $lw, 'knowledge_name' ) ) {
			$lw['knowledge_name'] = (string) $name;
			$details[]            = 'company_name/person_name -> knowledge_name';
			++$count;
		}

		if ( ! empty( $titles['company_logo'] ) && ! $this->is_filled( $lw, 'knowledge_logo' ) ) {
			$lw['knowledge_logo'] = (string) $titles['company_logo'];
			$details[]            = 'company_logo -> knowledge_logo';
			++$count;
		}

		return $count;
	}

	/**
	 * Whether an LW option slot is already filled (never overwrite).
	 *
	 * @param array  $lw  LW options.
	 * @param string $key LW option key.
	 * @return bool
	 */
	private function is_filled( array $lw, string $key ): bool {
		return isset( $lw[ $key ] ) && '' !== $lw[ $key ];
	}
}
