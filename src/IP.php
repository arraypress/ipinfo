<?php
/**
 * IP Utility Class for IPInfo
 *
 * @package     ArrayPress/Utils
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo;

/**
 * Class Utils
 *
 * IP address validation and checking utilities for IPInfo library.
 */
class IP {

	/**
	 * List of bogon CIDR ranges
	 *
	 * @var array
	 */
	private const BOGON_NETWORKS = [
		// IPv4 bogons
		"0.0.0.0/8",
		"10.0.0.0/8",
		"100.64.0.0/10",
		"127.0.0.0/8",
		"169.254.0.0/16",
		"172.16.0.0/12",
		"192.0.0.0/24",
		"192.0.2.0/24",
		"192.168.0.0/16",
		"198.18.0.0/15",
		"198.51.100.0/24",
		"203.0.113.0/24",
		"224.0.0.0/4",
		"240.0.0.0/4",
		"255.255.255.255/32",
		// IPv6 bogons
		"::/128",
		"::1/128",
		"::ffff:0:0/96",
		"::/96",
		"100::/64",
		"2001:10::/28",
		"2001:db8::/32",
		"fc00::/7",
		"fe80::/10",
		"fec0::/10",
		"ff00::/8",
		"2002::/24",
		"2002:a00::/24",
		"2002:7f00::/24",
		"2002:a9fe::/32",
		"2002:ac10::/28",
		"2002:c000::/40",
		"2002:c000:200::/40",
		"2002:c0a8::/32",
		"2002:c612::/31",
		"2002:c633:6400::/40",
		"2002:cb00:7100::/40",
		"2002:e000::/20",
		"2002:f000::/20",
		"2002:ffff:ffff::/48",
		"2001::/40",
		"2001:0:a00::/40",
		"2001:0:7f00::/40",
		"2001:0:a9fe::/48",
		"2001:0:ac10::/44",
		"2001:0:c000::/56",
		"2001:0:c000:200::/56",
		"2001:0:c0a8::/48",
		"2001:0:c612::/47",
		"2001:0:c633:6400::/56",
		"2001:0:cb00:7100::/56",
		"2001:0:e000::/36",
		"2001:0:f000::/36",
		"2001:0:ffff:ffff::/64"
	];

	/**
	 * Validate an IP address
	 *
	 * @param string $ip IP address to validate
	 *
	 * @return bool True if IP is valid
	 */
	public static function is_valid( string $ip ): bool {
		return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Check if an IP is valid for IPInfo lookup
	 *
	 * @param string $ip IP address to check
	 *
	 * @return bool True if IP is valid for lookup
	 */
	public static function is_valid_for_lookup( string $ip ): bool {
		return self::is_valid( $ip ) && ! self::is_bogon( $ip );
	}

	/**
	 * Check if an IP is a bogon address
	 *
	 * @param string $ip IP address to check
	 *
	 * @return bool True if IP is a bogon
	 */
	public static function is_bogon( string $ip ): bool {
		foreach ( self::BOGON_NETWORKS as $network ) {
			if ( self::in_range( $ip, $network ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an IP is in a CIDR range
	 *
	 * @param string $ip    IP address to check
	 * @param string $range CIDR range
	 *
	 * @return bool True if IP is in range
	 */
	public static function in_range( string $ip, string $range ): bool {
		if ( ! self::is_valid( $ip ) ) {
			return false;
		}

		list( $subnet, $bits ) = explode( '/', $range );

		// Convert subnet to binary format
		$ip     = inet_pton( $ip );
		$subnet = inet_pton( $subnet );

		if ( $ip === false || $subnet === false ) {
			return false;
		}

		$ip_bits = strlen( $ip ) * 8;
		$bits    = (int) $bits;

		// Create a mask based on the prefix length
		$mask           = str_repeat( "\xFF", $bits >> 3 );
		$remaining_bits = $bits & 7;
		if ( $remaining_bits ) {
			$mask .= chr( 0xFF << ( 8 - $remaining_bits ) );
		}
		$mask = str_pad( $mask, strlen( $ip ), "\x00" );

		return ( $ip & $mask ) === ( $subnet & $mask );
	}

	/**
	 * Get bogon networks list
	 *
	 * @return array Array of bogon CIDR ranges
	 */
	public static function get_bogon_networks(): array {
		return self::BOGON_NETWORKS;
	}

	/**
	 * Validate an IPv4 address
	 *
	 * @param string $ip The IP address to validate
	 *
	 * @return bool True if valid IPv4
	 */
	public static function is_ipv4( string $ip ): bool {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false;
	}

	/**
	 * Validate an IPv6 address
	 *
	 * @param string $ip The IP address to validate
	 *
	 * @return bool True if valid IPv6
	 */
	public static function is_ipv6( string $ip ): bool {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false;
	}
	
}