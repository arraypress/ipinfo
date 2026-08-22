<?php
/**
 * Failure Cache
 *
 * Remembers that a lookup failed, so an outage costs one visitor a timeout
 * rather than all of them.
 *
 * Without this, an API that is down or out of quota is re-asked on every
 * single request, and each one waits out the full HTTP timeout before giving
 * up. On a checkout that is the difference between a slow page and an
 * abandoned basket. A quota error is worse still: retrying it per request
 * burns the next allowance as fast as it is granted.
 *
 * @package     ArrayPress\IPInfo
 * @copyright   Copyright (c) 2026, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Traits;

trait FailureCache {

	/**
	 * How long a failed lookup is remembered, in seconds.
	 *
	 * Short on purpose: long enough that an outage does not cost every visitor
	 * a full timeout, short enough that a recovered API is picked up quickly.
	 * Set to 0 to disable.
	 *
	 * @var int
	 */
	private int $failure_ttl = 60;

	/**
	 * Set how long a failed lookup is remembered.
	 *
	 * @param int $seconds Seconds, or 0 to retry every time.
	 *
	 * @return self
	 */
	public function set_failure_ttl( int $seconds ): self {
		$this->failure_ttl = max( 0, $seconds );

		return $this;
	}

	/**
	 * How long a failed lookup is remembered.
	 *
	 * @return int
	 */
	public function get_failure_ttl(): int {
		return $this->failure_ttl;
	}

	/**
	 * The transient key recording a failure for this lookup.
	 *
	 * Derived from the same cache key as the success, so the two never collide
	 * and a failure is remembered per lookup rather than globally -- one bad
	 * address must not blind the caller to every other one.
	 *
	 * @param string $cache_key The cache key the successful response would use.
	 *
	 * @return string
	 */
	private function get_failure_key( string $cache_key ): string {
		return $cache_key . '_f';
	}

	/**
	 * Whether this lookup failed recently.
	 *
	 * @param string $cache_key The cache key the successful response would use.
	 *
	 * @return bool
	 */
	private function recently_failed( string $cache_key ): bool {
		if ( ! $this->is_cache_enabled() || $this->failure_ttl <= 0 ) {
			return false;
		}

		return (bool) get_transient( $this->get_failure_key( $cache_key ) );
	}

	/**
	 * Remember that this lookup failed.
	 *
	 * @param string $cache_key The cache key the successful response would use.
	 *
	 * @return void
	 */
	private function cache_failure( string $cache_key ): void {
		if ( ! $this->is_cache_enabled() || $this->failure_ttl <= 0 ) {
			return;
		}

		set_transient( $this->get_failure_key( $cache_key ), 1, $this->failure_ttl );
	}

	/**
	 * The error returned instead of re-asking a failing endpoint.
	 *
	 * @return \WP_Error
	 */
	private function recent_failure_error(): \WP_Error {
		return new \WP_Error(
			'ipinfo_recent_failure',
			__( 'IPinfo lookup failed recently; not retrying yet.', 'arraypress' )
		);
	}
}
