<?php
/**
 * Shared contract for per-vendor SEO data migrators.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Migration;

/**
 * Contract implemented by each vendor migrator (RankMath, Yoast).
 */
interface MigratorInterface {

	/**
	 * Detect available source data without modifying anything.
	 *
	 * @return array<string, mixed>
	 */
	public function detect(): array;

	/**
	 * Run the migration.
	 *
	 * @return array<string, mixed>
	 */
	public function run(): array;
}
