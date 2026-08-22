<?php
/**
 * The IPinfo client.
 *
 * These lookups run on a checkout with a 15-second timeout, so what matters as
 * much as the answer is that a dead endpoint is asked once rather than once
 * per visitor.
 *
 * @package ArrayPress\IPInfo
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Tests;

use ArrayPress\IPInfo\Client;
use ArrayPress\IPInfo\Response;
use PHPUnit\Framework\TestCase;
use WP_Error;

/**
 * Class ClientTest
 */
final class ClientTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();

		ii_reset();
	}

	protected function tearDown(): void {
		ii_reset();

		parent::tearDown();
	}

	public function test_a_lookup_returns_a_response(): void {
		ii_will_return( ii_ok( [ 'ip' => '8.8.8.8', 'country' => 'US' ] ) );

		$this->assertInstanceOf( Response::class, ( new Client( 'key' ) )->get_ip_info( '8.8.8.8' ) );
	}

	/**
	 * A bogon address cannot be looked up and must not cost a request. Private
	 * ranges reach this often -- a site behind a proxy that forwards the wrong
	 * header sends them constantly.
	 */
	public function test_an_invalid_or_bogon_ip_is_refused_without_a_request(): void {
		$client = new Client( 'key' );

		$result = $client->get_ip_info( 'not-an-ip' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_ip', $result->get_error_code() );

		$this->assertInstanceOf( WP_Error::class, $client->get_ip_info( '127.0.0.1' ) );
		$this->assertSame( 0, ii_request_count(), 'nothing should reach the network' );
	}

	public function test_a_repeat_lookup_is_served_from_cache(): void {
		ii_will_return( ii_ok( [ 'ip' => '8.8.8.8' ] ) );

		$client = new Client( 'key' );
		$client->get_ip_info( '8.8.8.8' );
		$client->get_ip_info( '8.8.8.8' );

		$this->assertSame( 1, ii_request_count() );
	}

	/** ---------------------------------------------------------------------
	 * Failure handling
	 * -------------------------------------------------------------------- */

	/**
	 * The regression this file exists for. Without a negative cache an outage
	 * costs every visitor the full 15-second timeout, one after another.
	 */
	public function test_a_failed_lookup_is_not_immediately_retried(): void {
		ii_will_return( new WP_Error( 'http_request_failed', 'Connection timed out' ) );

		$client = new Client( 'key' );
		$client->get_ip_info( '8.8.8.8' );
		$second = $client->get_ip_info( '8.8.8.8' );

		$this->assertSame( 1, ii_request_count(), 'the dead endpoint must not be hit twice' );
		$this->assertInstanceOf( WP_Error::class, $second );
		$this->assertSame( 'ipinfo_recent_failure', $second->get_error_code() );
	}

	/**
	 * IPinfo answers 429 once the monthly allowance is gone. Retrying that per
	 * request burns the next month's as fast as it is granted.
	 */
	public function test_an_http_error_is_not_immediately_retried(): void {
		ii_will_return( [ 'code' => 429, 'body' => '{"error":"quota exceeded"}' ] );

		$client = new Client( 'key' );
		$client->get_ip_info( '8.8.8.8' );
		$client->get_ip_info( '8.8.8.8' );

		$this->assertSame( 1, ii_request_count() );
	}

	/**
	 * One bad lookup must not blind the caller to every other address.
	 */
	public function test_a_failure_is_remembered_per_address(): void {
		ii_will_return( new WP_Error( 'http_request_failed', 'Connection timed out' ) );
		$client = new Client( 'key' );
		$client->get_ip_info( '8.8.8.8' );

		ii_will_return( ii_ok( [ 'ip' => '1.1.1.1' ] ) );

		$this->assertInstanceOf( Response::class, $client->get_ip_info( '1.1.1.1' ) );
	}

	/**
	 * A single-field lookup is a different request from the full record, so a
	 * failure on one must not suppress the other.
	 */
	public function test_a_field_lookup_is_cached_separately_from_the_record(): void {
		ii_will_return( new WP_Error( 'http_request_failed', 'Connection timed out' ) );
		$client = new Client( 'key' );
		$client->get_ip_info( '8.8.8.8' );

		ii_will_return( [ 'code' => 200, 'body' => '"US"' ] );

		$this->assertSame( 'US', $client->get_field( '8.8.8.8', 'country' ) );
	}

	public function test_a_field_lookup_also_remembers_its_failures(): void {
		ii_will_return( new WP_Error( 'http_request_failed', 'Connection timed out' ) );

		$client = new Client( 'key' );
		$client->get_field( '8.8.8.8', 'country' );
		$client->get_field( '8.8.8.8', 'country' );

		$this->assertSame( 1, ii_request_count() );
	}

	public function test_failure_caching_can_be_switched_off(): void {
		ii_will_return( new WP_Error( 'http_request_failed', 'Connection timed out' ) );

		$client = ( new Client( 'key' ) )->set_failure_ttl( 0 );
		$client->get_ip_info( '8.8.8.8' );
		$client->get_ip_info( '8.8.8.8' );

		$this->assertSame( 2, ii_request_count() );
	}

	/**
	 * With caching off there is nowhere to record a failure, so every lookup
	 * has to go out. The guard must not silently suppress them.
	 */
	public function test_failures_are_not_suppressed_when_caching_is_off(): void {
		ii_will_return( new WP_Error( 'http_request_failed', 'Connection timed out' ) );

		$client = new Client( 'key', false );
		$client->get_ip_info( '8.8.8.8' );
		$client->get_ip_info( '8.8.8.8' );

		$this->assertSame( 2, ii_request_count() );
	}

	public function test_the_failure_window_is_configurable(): void {
		$client = new Client( 'key' );

		$this->assertSame( 60, $client->get_failure_ttl(), 'a sensible default' );
		$this->assertSame( 30, $client->set_failure_ttl( 30 )->get_failure_ttl() );
		$this->assertSame( 0, $client->set_failure_ttl( -5 )->get_failure_ttl(), 'never negative' );
	}
}
