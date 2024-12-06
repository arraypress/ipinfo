<?php
/**
 * IPInfo.io API Client
 *
 * A comprehensive utility class for interacting with the IPInfo.io API service.
 * Supports all plan levels (Free, Basic, Business, Premium) with WordPress transient caching.
 *
 * Supported Plans & Features:
 * - Free: Basic geolocation data
 * - Basic: Adds ASN data
 * - Business: Adds privacy detection, abuse contact, company info
 * - Premium: Adds hosted domains data
 *
 * Example usage:
 * ```php
 * // Initialize the client
 * $client = new Client( 'your-token-here' );
 *
 * // Get IP information
 * $info = $client->get_ip_info( '8.8.8.8' );
 *
 * // Access data through response object
 * $country = $info->get_country();
 * $city = $info->get_city();
 * $coords = $info->get_coordinates();
 *
 * // Access plan-specific data (returns null if not in your plan)
 * $privacy = $info->get_privacy();
 * if ( $privacy && $privacy->is_vpn() ) {
 *     // Handle VPN connection
 * }
 * ```
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo;

use WP_Error;

/**
 * Class Response
 *
 * A comprehensive utility class for interacting with the IPInfo.io API service.
 */
class Client {

	/**
	 * API token for IPInfo.io
	 *
	 * @var string
	 */
	private string $token;

	/**
	 * Base URL for the IPInfo API
	 *
	 * @var string
	 */
	private const API_BASE = 'https://ipinfo.io/';

	/**
	 * Whether to enable response caching
	 *
	 * @var bool
	 */
	private bool $enable_cache;

	/**
	 * Cache expiration time in seconds
	 *
	 * @var int
	 */
	private int $cache_expiration;

	/**
	 * Initialize the IPInfo client
	 *
	 * @param string $token            API token for IPInfo.io
	 * @param bool   $enable_cache     Whether to enable caching (default: true)
	 * @param int    $cache_expiration Cache expiration in seconds (default: 1 hour)
	 */
	public function __construct( string $token, bool $enable_cache = true, int $cache_expiration = 3600 ) {
		$this->token            = $token;
		$this->enable_cache     = $enable_cache;
		$this->cache_expiration = $cache_expiration;
	}

	/**
	 * Get complete information for an IP address
	 *
	 * Returns a response object containing all available data for the IP address based on your plan level.
	 * The response object provides methods to access specific data points, returning null for unavailable data.
	 *
	 * @param string $ip IP address to look up
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function get_ip_info( string $ip ) {
		if ( ! $this->is_valid_ip( $ip ) ) {
			return new WP_Error(
				'invalid_ip',
				sprintf( __( 'Invalid IP address: %s', 'arraypress' ), $ip )
			);
		}

		$cache_key = $this->get_cache_key( $ip );

		// Check cache if enabled
		if ( $this->enable_cache ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		// Make API request
		$response = $this->make_request( $ip );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$data = $this->parse_response( $response );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		// Cache the raw data if enabled
		if ( $this->enable_cache ) {
			set_transient( $cache_key, $data, $this->cache_expiration );
		}

		return new Response( $data );
	}

	/**
	 * Make request to IPInfo API
	 *
	 * @param string $ip IP address to look up
	 *
	 * @return array|WP_Error Response array or WP_Error on failure
	 * @access private
	 */
	private function make_request( string $ip ) {
		$url  = self::API_BASE . $ip;
		$args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->token,
				'Accept'        => 'application/json',
			],
			'timeout' => 15,
		];

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'IPInfo API request failed: %s', 'arraypress' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return new WP_Error(
				'api_error',
				sprintf(
					__( 'IPInfo API returned error code: %d', 'arraypress' ),
					$status_code
				)
			);
		}

		return $response;
	}

	/**
	 * Parse the API response
	 *
	 * @param array $response API response to parse
	 *
	 * @return array|WP_Error Parsed data or WP_Error on failure
	 * @access private
	 */
	private function parse_response( array $response ) {
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse IPInfo API response', 'arraypress' )
			);
		}

		if ( isset( $data['error'] ) ) {
			return new WP_Error(
				'api_error',
				$data['error']['message'] ?? __( 'Unknown API error', 'arraypress' )
			);
		}

		return $data;
	}

	/**
	 * Generate cache key for an IP address
	 *
	 * @param string $ip IP address
	 *
	 * @return string Cache key
	 * @access private
	 */
	private function get_cache_key( string $ip ): string {
		return 'ipinfo_' . md5( $ip . $this->token );
	}

	/**
	 * Validate an IP address
	 *
	 * @param string $ip IP address to validate
	 *
	 * @return bool Whether the IP is valid
	 * @access private
	 */
	private function is_valid_ip( string $ip ): bool {
		return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
	}

	/**
	 * Clear cached data for an IP address
	 *
	 * @param string|null $ip Optional specific IP to clear cache for
	 *
	 * @return bool True on success, false on failure
	 */
	public function clear_cache( ?string $ip = null ): bool {
		if ( $ip !== null ) {
			return delete_transient( $this->get_cache_key( $ip ) );
		}

		global $wpdb;
		$pattern = $wpdb->esc_like( '_transient_ipinfo_' ) . '%';

		return $wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
					$pattern
				)
			) !== false;
	}

}