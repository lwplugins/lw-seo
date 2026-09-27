<?php
/**
 * Records template variables that have no LW SEO equivalent.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration\Support;

/**
 * The template converters remove source variables LW SEO cannot render, so a
 * title never shows a raw "#tag" or an empty gap silently. Each removal is
 * recorded here and reported as a migration warning.
 */
final class VariableLog {

	/**
	 * Removed variable (as written in the source) => occurrences.
	 *
	 * @var array<string, int>
	 */
	private array $removed = [];

	/**
	 * Record one removed variable.
	 *
	 * @param string $variable Variable as written in the source, e.g. "%%wc_price%%".
	 * @return void
	 */
	public function removed( string $variable ): void {
		$this->removed[ $variable ] = ( $this->removed[ $variable ] ?? 0 ) + 1;
	}

	/**
	 * Removed variables with their counts.
	 *
	 * @return array<string, int>
	 */
	public function all(): array {
		return $this->removed;
	}

	/**
	 * The migration warning for the removed variables, null when none.
	 *
	 * @param string $plugin Source plugin name.
	 * @return array{code: string, severity: string, message: string}|null
	 */
	public function warning( string $plugin ): ?array {
		if ( empty( $this->removed ) ) {
			return null;
		}

		$list = [];
		foreach ( $this->removed as $variable => $count ) {
			$list[] = $variable . ' (' . $count . ')';
		}

		return [
			'code'     => 'variables',
			'severity' => 'info',
			'message'  => sprintf(
				/* translators: 1: source SEO plugin name, 2: comma-separated list of template variables with occurrence counts. */
				__( 'These %1$s template variables have no LW SEO equivalent and were left out of the imported titles and descriptions: %2$s', 'lw-seo' ),
				$plugin,
				implode( ', ', $list )
			),
		];
	}
}
