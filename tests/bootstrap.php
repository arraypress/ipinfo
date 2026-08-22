<?php
/**
 * Test bootstrap.
 *
 * The transport is the seam: a test says what the API returns, and the
 * assertions are about what the library does with it.
 *
 * @package ArrayPress\IPInfo
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

/**
 * What the next HTTP call returns, what has been asked, and what is cached.
 *
 * @var array
 */
$GLOBALS['ii'] = [
	'response'   => null,
	'requests'   => [],
	'transients' => [],
];

/**
 * Reset everything a test set up.
 */
function ii_reset(): void {
	$GLOBALS['ii'] = [
		'response'   => null,
		'requests'   => [],
		'transients' => [],
	];
}

/**
 * Queue the response the next request receives.
 *
 * @param mixed $response A WP_Error, or an array with 'code' and 'body'.
 */
function ii_will_return( $response ): void {
	$GLOBALS['ii']['response'] = $response;
}

/**
 * A successful IPinfo body.
 *
 * @param array $data Response fields.
 *
 * @return array
 */
function ii_ok( array $data = [] ): array {
	return [ 'code' => 200, 'body' => (string) json_encode( $data ) ];
}

/**
 * How many requests have been made.
 *
 * @return int
 */
function ii_request_count(): int {
	return count( $GLOBALS['ii']['requests'] );
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {

		public string $code;
		public string $message;
		public array $data;

		public function __construct( string $code = '', string $message = '', $data = [] ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = (array) $data;
		}

		public function get_error_code(): string {
			return $this->code;
		}

		public function get_error_message(): string {
			return $this->message;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ): bool {
		return $thing instanceof WP_Error;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = null ) {
		return $text;
	}
}

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = [] ) {
		return array_merge( $defaults, (array) $args );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( string $url, array $args = [] ) {
		$GLOBALS['ii']['requests'][] = $url;

		return $GLOBALS['ii']['response'] ?? [ 'code' => 200, 'body' => '{}' ];
	}
}

if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( string $url, array $args = [] ) {
		$GLOBALS['ii']['requests'][] = $url;

		return $GLOBALS['ii']['response'] ?? [ 'code' => 200, 'body' => '{}' ];
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $response ) {
		return is_array( $response ) ? ( $response['code'] ?? 200 ) : 0;
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $response ) {
		return is_array( $response ) ? ( $response['body'] ?? '' ) : '';
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( string $key ) {
		$entry = $GLOBALS['ii']['transients'][ $key ] ?? null;

		if ( $entry === null ) {
			return false;
		}

		if ( $entry['expires'] !== 0 && $entry['expires'] < time() ) {
			unset( $GLOBALS['ii']['transients'][ $key ] );

			return false;
		}

		return $entry['value'];
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( string $key, $value, int $expiration = 0 ): bool {
		$GLOBALS['ii']['transients'][ $key ] = [
			'value'   => $value,
			'expires' => $expiration > 0 ? time() + $expiration : 0,
		];

		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( string $key ): bool {
		unset( $GLOBALS['ii']['transients'][ $key ] );

		return true;
	}
}
