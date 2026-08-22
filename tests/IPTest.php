<?php
/**
 * CIDR matching and bogon detection.
 *
 * in_range() decides whether an address is a bogon, and a bogon is never
 * looked up. Getting the mask wrong in either direction is expensive: too wide
 * and real customer IPs are silently skipped, too narrow and private ranges
 * are sent to the API on every request.
 *
 * @package ArrayPress\IPInfo
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Tests;

use ArrayPress\IPInfo\Utils\IP;
use PHPUnit\Framework\TestCase;

/**
 * Class IPTest
 */
final class IPTest extends TestCase {

	/**
	 * A /24 is the common case and the one whose mask needs no partial byte.
	 */
	public function test_a_byte_aligned_range_matches_its_own_block(): void {
		$this->assertTrue( IP::in_range( '192.168.1.1', '192.168.1.0/24' ) );
		$this->assertTrue( IP::in_range( '192.168.1.255', '192.168.1.0/24' ) );
		$this->assertFalse( IP::in_range( '192.168.2.1', '192.168.1.0/24' ) );
	}

	/**
	 * The prefix lengths that need a partial mask byte -- the ones that go
	 * through chr(). Each boundary is checked from both sides, because a mask
	 * that is one bit too wide or too narrow still matches most addresses and
	 * only shows up at the edge.
	 */
	public function test_every_partial_byte_prefix_masks_correctly(): void {
		$cases = [
			// prefix, inside, outside
			[ '10.0.0.0/9', '10.127.255.255', '10.128.0.0' ],
			[ '10.0.0.0/10', '10.63.255.255', '10.64.0.0' ],
			[ '10.0.0.0/11', '10.31.255.255', '10.32.0.0' ],
			[ '10.0.0.0/12', '10.15.255.255', '10.16.0.0' ],
			[ '10.0.0.0/13', '10.7.255.255', '10.8.0.0' ],
			[ '10.0.0.0/14', '10.3.255.255', '10.4.0.0' ],
			[ '10.0.0.0/15', '10.1.255.255', '10.2.0.0' ],
		];

		foreach ( $cases as [ $range, $inside, $outside ] ) {
			$this->assertTrue( IP::in_range( $inside, $range ), "$inside should be inside $range" );
			$this->assertFalse( IP::in_range( $outside, $range ), "$outside should be outside $range" );
		}
	}

	/**
	 * A /32 is a single address, and a /0 is everything. Both are the ends of
	 * the range where an off-by-one in the mask shows up first.
	 */
	public function test_the_extremes_of_the_prefix_range(): void {
		$this->assertTrue( IP::in_range( '8.8.8.8', '8.8.8.8/32' ) );
		$this->assertFalse( IP::in_range( '8.8.8.9', '8.8.8.8/32' ) );

		$this->assertTrue( IP::in_range( '8.8.8.8', '0.0.0.0/0' ) );
		$this->assertTrue( IP::in_range( '192.168.1.1', '0.0.0.0/0' ) );
	}

	public function test_an_invalid_address_is_never_in_range(): void {
		$this->assertFalse( IP::in_range( 'not-an-ip', '192.168.1.0/24' ) );
		$this->assertFalse( IP::in_range( '', '192.168.1.0/24' ) );
	}

	/**
	 * The private and loopback ranges are the ones that reach this most often
	 * -- a site behind a misconfigured proxy sends them constantly, and each
	 * one that slips through is a wasted API call on a customer's request.
	 */
	public function test_the_ranges_a_lookup_must_refuse(): void {
		foreach ( [ '127.0.0.1', '10.0.0.1', '192.168.1.1', '172.16.0.1', '0.0.0.0' ] as $ip ) {
			$this->assertFalse( IP::is_valid_for_lookup( $ip ), "$ip is a bogon and must not be looked up" );
		}
	}

	public function test_a_routable_address_is_looked_up(): void {
		foreach ( [ '8.8.8.8', '1.1.1.1' ] as $ip ) {
			$this->assertTrue( IP::is_valid_for_lookup( $ip ), "$ip is routable" );
		}
	}
}
