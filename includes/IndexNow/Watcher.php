<?php
/**
 * Queues IndexNow URLs when content changes.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

use LightweightPlugins\SEO\Content\Eligibility;
use LightweightPlugins\SEO\Options;
use LightweightPlugins\SEO\Sitemap\ExcludedPosts;

/**
 * Never sends anything itself: URLs go to the queue.
 */
final class Watcher {

	/**
	 * Term links captured before deletion, by term ID.
	 *
	 * @var array<int, string>
	 */
	private array $deleted_terms = [];

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( ! Options::get( 'indexnow_enabled' ) ) {
			return;
		}

		add_action( 'transition_post_status', [ $this, 'on_transition' ], 10, 3 );
		add_action( 'edited_term', [ $this, 'on_term_edited' ], 10, 3 );
		add_action( 'pre_delete_term', [ $this, 'before_term_delete' ], 10, 2 );
		add_action( 'delete_term', [ $this, 'on_term_deleted' ] );
	}

	/**
	 * Post status change.
	 *
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post.
	 * @return void
	 */
	public function on_transition( string $new_status, string $old_status, \WP_Post $post ): void {
		if ( 'publish' !== $new_status && 'publish' !== $old_status ) {
			return;
		}

		if ( $this->is_ignored_request( (int) $post->ID ) || ! is_post_type_viewable( $post->post_type ) ) {
			return;
		}

		if ( 'publish' === $new_status ) {
			if ( ! Eligibility::is_post_eligible( $post ) || in_array( (int) $post->ID, ExcludedPosts::ids( (string) $post->post_type ), true ) ) {
				return;
			}
		} elseif ( ! Eligibility::is_type_indexable( (string) $post->post_type ) || Options::get_post_meta( (int) $post->ID, 'noindex' ) ) {
			// Removal: the status no longer publish, so only the noindex checks apply.
			return;
		}

		$url = get_permalink( $post );

		if ( $url ) {
			// Trashing may already have appended the __trashed suffix to the slug.
			Queue::add( [ str_replace( '__trashed', '', $url ) ] );
		}
	}

	/**
	 * Term updated.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function on_term_edited( int $term_id, int $tt_id, string $taxonomy ): void {
		$url = $this->eligible_term_url( $term_id, $taxonomy );

		if ( null !== $url ) {
			Queue::add( [ $url ] );
		}
	}

	/**
	 * Remember the term link while the term still exists.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function before_term_delete( int $term_id, string $taxonomy ): void {
		$url = $this->eligible_term_url( $term_id, $taxonomy );

		if ( null !== $url ) {
			$this->deleted_terms[ $term_id ] = $url;
		}
	}

	/**
	 * Term deleted.
	 *
	 * @param int $term_id Term ID.
	 * @return void
	 */
	public function on_term_deleted( int $term_id ): void {
		if ( isset( $this->deleted_terms[ $term_id ] ) ) {
			Queue::add( [ $this->deleted_terms[ $term_id ] ] );
			unset( $this->deleted_terms[ $term_id ] );
		}
	}

	/**
	 * Archive URL of an eligible term.
	 *
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy.
	 * @return string|null
	 */
	private function eligible_term_url( int $term_id, string $taxonomy ): ?string {
		if ( $this->is_ignored_request( 0 ) ) {
			return null;
		}

		$term = get_term( $term_id, $taxonomy );

		if ( ! $term instanceof \WP_Term || ! Eligibility::is_term_eligible( $term ) ) {
			return null;
		}

		$url = get_term_link( $term );

		return is_string( $url ) && '' !== $url ? $url : null;
	}

	/**
	 * Revisions, autosaves and imports never notify.
	 *
	 * @param int $post_id Post ID (0 for terms).
	 * @return bool
	 */
	private function is_ignored_request( int $post_id ): bool {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) ) {
			return true;
		}

		return $post_id > 0 && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) );
	}
}
