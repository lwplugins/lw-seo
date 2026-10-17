<?php
/**
 * Parent/child ordering for the HTML sitemap.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\HtmlSitemap;

/**
 * Puts a flat list of pages or terms into tree order and gives each its depth.
 */
final class Tree {

	/**
	 * Arrange rows by parent.
	 *
	 * A row whose parent is not in the list (capped away, excluded) becomes
	 * a root. Rows keep their incoming order among siblings.
	 *
	 * @param array<int, array{id: int, parent: int, title: string, url: string}> $rows         Rows.
	 * @param bool                                                                $hierarchical Whether to nest at all.
	 * @return array<int, array{title: string, url: string, depth: int}>
	 */
	public static function arrange( array $rows, bool $hierarchical ): array {
		if ( ! $hierarchical ) {
			return array_map(
				static fn( array $row ): array => [
					'title' => $row['title'],
					'url'   => $row['url'],
					'depth' => 0,
				],
				$rows
			);
		}

		$ids      = array_column( $rows, 'id' );
		$children = [];
		foreach ( $rows as $row ) {
			$parent                = in_array( $row['parent'], $ids, true ) && $row['parent'] !== $row['id'] ? $row['parent'] : 0;
			$children[ $parent ][] = $row;
		}

		$out = [];
		self::walk( $children, 0, 0, $out, [] );

		return $out;
	}

	/**
	 * Depth-first walk.
	 *
	 * @param array<int, array<int, array{id: int, parent: int, title: string, url: string}>> $children Rows by parent.
	 * @param int                                                                             $parent   Parent ID.
	 * @param int                                                                             $depth    Current depth.
	 * @param array<int, array{title: string, url: string, depth: int}>                       $out      Result.
	 * @param array<int, bool>                                                                $seen     IDs on the path (cycle guard).
	 * @return void
	 */
	private static function walk( array $children, int $parent, int $depth, array &$out, array $seen ): void {
		foreach ( $children[ $parent ] ?? [] as $row ) {
			if ( isset( $seen[ $row['id'] ] ) ) {
				continue;
			}

			$out[] = [
				'title' => $row['title'],
				'url'   => $row['url'],
				'depth' => $depth,
			];

			self::walk( $children, $row['id'], $depth + 1, $out, $seen + [ $row['id'] => true ] );
		}
	}
}
