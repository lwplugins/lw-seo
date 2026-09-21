<?php
/**
 * AI crawler settings block.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\Admin\Settings;

use LightweightPlugins\SEO\Crawlers\Registry;
use LightweightPlugins\SEO\Options;

/**
 * Renders purpose toggles and per-crawler checkboxes grouped by purpose.
 */
final class CrawlerSection {

	use FieldRendererTrait;

	/**
	 * Render the block.
	 *
	 * @return void
	 */
	public function render(): void {
		?>
		<h3><?php esc_html_e( 'AI Crawler Access', 'lw-seo' ); ?></h3>
		<p class="description" style="margin-bottom: 15px;">
			<?php esc_html_e( 'Blocked crawlers get a "Disallow: /" rule in robots.txt. Checked = Blocked.', 'lw-seo' ); ?>
		</p>
		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Block by purpose', 'lw-seo' ); ?></th>
				<td>
					<?php foreach ( self::purposes() as $purpose => $labels ) : ?>
						<?php
						$this->render_checkbox_field(
							[
								'name'  => 'block_purpose_' . $purpose,
								'label' => $labels['toggle'],
							]
						);
						?>
						<br />
					<?php endforeach; ?>
					<p class="description"><?php esc_html_e( 'Covers every crawler with that purpose, including ones added in future updates.', 'lw-seo' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
		$this->render_groups();
		?>
		<div class="lw-seo-info-box" style="margin-top: 20px;">
			<span class="dashicons dashicons-info"></span>
			<p><?php esc_html_e( 'robots.txt is a request, not an enforcement. OpenAI, Perplexity, Meta and Amazon state that their user-triggered fetchers may ignore it.', 'lw-seo' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Crawler cards grouped by primary purpose.
	 *
	 * @return void
	 */
	private function render_groups(): void {
		$crawlers = Registry::builtin();

		foreach ( self::purposes() as $purpose => $labels ) {
			$group = array_filter( $crawlers, static fn( array $crawler ): bool => $purpose === $crawler['purposes'][0] );
			if ( [] === $group ) {
				continue;
			}
			printf( '<h4>%s</h4><div class="lw-seo-crawler-grid">', esc_html( $labels['heading'] ) );
			foreach ( $group as $key => $crawler ) {
				$this->render_card( (string) $key, $crawler );
			}
			echo '</div>';
		}
	}

	/**
	 * One crawler card.
	 *
	 * @param string                                              $key     Registry key.
	 * @param array{name: string, company: string, agent: string} $crawler Crawler.
	 * @return void
	 */
	private function render_card( string $key, array $crawler ): void {
		$is_blocked = (bool) Options::get( 'block_' . $key );
		?>
		<div class="lw-seo-crawler-card <?php echo $is_blocked ? 'blocked' : ''; ?>">
			<input
				type="checkbox"
				id="block_<?php echo esc_attr( $key ); ?>"
				name="<?php echo esc_attr( Options::OPTION_NAME ); ?>[block_<?php echo esc_attr( $key ); ?>]"
				value="1"
				<?php checked( $is_blocked, true ); ?>
			/>
			<div class="lw-seo-crawler-info">
				<div class="lw-seo-crawler-name"><?php echo esc_html( $crawler['name'] ); ?></div>
				<div class="lw-seo-crawler-company"><?php echo esc_html( $crawler['company'] ); ?></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Purpose labels.
	 *
	 * @return array<string, array{toggle: string, heading: string}>
	 */
	private static function purposes(): array {
		return [
			Registry::TRAINING => [
				'toggle'  => __( 'Block all AI training crawlers', 'lw-seo' ),
				'heading' => __( 'Training crawlers', 'lw-seo' ),
			],
			Registry::SEARCH   => [
				'toggle'  => __( 'Block all AI search crawlers', 'lw-seo' ),
				'heading' => __( 'AI search crawlers', 'lw-seo' ),
			],
			Registry::USER     => [
				'toggle'  => __( 'Block all user-triggered AI fetchers', 'lw-seo' ),
				'heading' => __( 'User-triggered fetchers', 'lw-seo' ),
			],
		];
	}
}
