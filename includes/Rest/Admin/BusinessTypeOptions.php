<?php
/**
 * Business type choices for the settings API.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Rest\Admin;

/**
 * Shapes the grouped LocalBusiness types into select groups, keeping the
 * stored type selectable even when it is not in any group.
 */
final class BusinessTypeOptions {

	/**
	 * Build the select groups.
	 *
	 * @param array<string, array<string, string>> $grouped Group label => [ type => label ].
	 * @param array<string, string>                $all     Every known type => label.
	 * @param string                               $current Stored business type.
	 * @param string                               $other   Label of the fallback group.
	 * @return array<int, array{group: string, options: array<int, array{value: string, label: string}>}>
	 */
	public static function build( array $grouped, array $all, string $current, string $other ): array {
		$groups = [];
		$listed = false;

		foreach ( $grouped as $label => $types ) {
			$options = [];
			foreach ( $types as $value => $type_label ) {
				$options[] = [
					'value' => (string) $value,
					'label' => (string) $type_label,
				];
				$listed    = $listed || (string) $value === $current;
			}
			$groups[] = [
				'group'   => (string) $label,
				'options' => $options,
			];
		}

		if ( '' !== $current && ! $listed ) {
			$groups[] = [
				'group'   => $other,
				'options' => [
					[
						'value' => $current,
						'label' => $all[ $current ] ?? $current,
					],
				],
			];
		}

		return $groups;
	}
}
