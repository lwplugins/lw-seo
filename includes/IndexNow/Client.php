<?php
/**
 * IndexNow HTTP client.
 *
 * @package LightweightPlugins\SEO
 */

declare(strict_types=1);

namespace LightweightPlugins\SEO\IndexNow;

/**
 * Posts URL lists to the IndexNow endpoint and logs every submission.
 */
final class Client {

	/**
	 * Default endpoint.
	 */
	public const ENDPOINT = 'https://api.indexnow.org/indexnow';

	/**
	 * Maximum URLs per request (protocol limit).
	 */
	public const CHUNK = 10000;

	/**
	 * Submit URLs.
	 *
	 * @param array<int, string> $urls   URLs.
	 * @param string             $source 'auto' (queue) or 'manual'.
	 * @return array{ok: bool, code: int, message: string}
	 */
	public static function submit( array $urls, string $source = 'auto' ): array {
		$urls = array_values( array_unique( array_filter( array_map( 'strval', $urls ) ) ) );

		if ( array() === $urls ) {
			return [
				'ok'      => true,
				'code'    => 0,
				'message' => 'Nothing to submit.',
			];
		}

		$result = [
			'ok'      => true,
			'code'    => 0,
			'message' => '',
		];

		foreach ( array_chunk( $urls, self::CHUNK ) as $chunk ) {
			$result = self::post( $chunk );

			if ( ! $result['ok'] ) {
				break;
			}
		}

		Log::add(
			[
				'time'    => time(),
				'count'   => count( $urls ),
				'code'    => $result['code'],
				'ok'      => $result['ok'],
				'message' => $result['message'],
				'source'  => 'manual' === $source ? 'manual' : 'auto',
			]
		);

		return $result;
	}

	/**
	 * One request.
	 *
	 * @param array<int, string> $chunk URLs.
	 * @return array{ok: bool, code: int, message: string}
	 */
	private static function post( array $chunk ): array {
		/**
		 * Filter the IndexNow endpoint.
		 *
		 * @since 1.9.0
		 *
		 * @param string $endpoint Endpoint URL.
		 */
		$endpoint = (string) apply_filters( 'lw_seo_indexnow_endpoint', self::ENDPOINT );

		$response = wp_remote_post(
			$endpoint,
			[
				'timeout' => 10,
				'headers' => [ 'Content-Type' => 'application/json; charset=utf-8' ],
				'body'    => (string) wp_json_encode(
					[
						'host'        => (string) wp_parse_url( home_url(), PHP_URL_HOST ),
						'key'         => Key::get(),
						'keyLocation' => Key::location(),
						'urlList'     => $chunk,
					]
				),
			]
		);

		if ( is_wp_error( $response ) ) {
			return [
				'ok'      => false,
				'code'    => 0,
				'message' => $response->get_error_message(),
			];
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		return [
			'ok'      => 200 === $code || 202 === $code,
			'code'    => $code,
			'message' => self::message( $code ),
		];
	}

	/**
	 * Human-readable status.
	 *
	 * @param int $code HTTP status.
	 * @return string
	 */
	private static function message( int $code ): string {
		$messages = [
			200 => 'OK',
			202 => 'Accepted',
			400 => 'Invalid request',
			403 => 'Key not valid',
			422 => "URLs don't belong to this host or key mismatch",
			429 => 'Too many requests',
		];

		return $messages[ $code ] ?? 'Unexpected response (HTTP ' . $code . ')';
	}
}
