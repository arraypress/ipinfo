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
	 * Maximum number of IPs per batch request
	 *
	 * @var int
	 */
	private const BATCH_MAX_SIZE = 1000;

	/**
	 * Default timeout for batch requests in seconds
	 *
	 * @var int
	 */
	private const BATCH_TIMEOUT = 5;

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
	 * Get information for multiple IP addresses in a batch
	 *
	 * @param array $ips        Array of IP addresses to look up
	 * @param int   $batch_size Optional batch size (max 1000)
	 * @param bool  $filter     Whether to filter the response (default: false)
	 * @param int   $timeout    Request timeout in seconds
	 *
	 * @return array|WP_Error Array of Response objects keyed by IP or WP_Error on failure
	 */
	public function get_batch_info( array $ips, int $batch_size = self::BATCH_MAX_SIZE, bool $filter = false, int $timeout = self::BATCH_TIMEOUT ) {
		$results = [];

		// No IPs to process
		if ( empty( $ips ) ) {
			return $results;
		}

		// Validate batch size
		$batch_size = min( max( 1, $batch_size ), self::BATCH_MAX_SIZE );

		// Check cache for existing results
		if ( $this->enable_cache ) {
			foreach ( $ips as $key => $ip ) {
				$cached_data = get_transient( $this->get_cache_key( $ip ) );
				if ( false !== $cached_data ) {
					$results[ $ip ] = new Response( $cached_data );
					unset( $ips[ $key ] ); // Remove from IPs to process
				}
			}
		}

		// All results were cached
		if ( empty( $ips ) ) {
			return $results;
		}

		// Process remaining IPs in batches
		$batches = array_chunk( array_values( $ips ), $batch_size );
		foreach ( $batches as $batch ) {
			$batch_results = $this->process_batch( $batch, $filter, $timeout );

			if ( is_wp_error( $batch_results ) ) {
				return $batch_results;
			}

			foreach ( $batch_results as $ip => $data ) {
				if ( $this->enable_cache ) {
					set_transient( $this->get_cache_key( $ip ), $data, $this->cache_expiration );
				}
				$results[ $ip ] = new Response( $data );
			}
		}

		return $results;
	}

	/**
	 * Process a batch of IP addresses
	 *
	 * @param array $ips     Array of IP addresses
	 * @param bool  $filter  Whether to filter the response
	 * @param int   $timeout Request timeout in seconds
	 *
	 * @return array|WP_Error Array of results or WP_Error on failure
	 */
	private function process_batch( array $ips, bool $filter, int $timeout ): array {
		$url = self::API_BASE . 'batch' . ( $filter ? '?filter=1' : '' );

		$args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->token,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			],
			'body'    => json_encode( $ips ),
			'timeout' => $timeout,
			'method'  => 'POST'
		];

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'batch_error',
				sprintf(
					__( 'Batch request failed: %s', 'arraypress' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return new WP_Error(
				'batch_error',
				sprintf(
					__( 'Batch request returned error code: %d', 'arraypress' ),
					$status_code
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_error',
				__( 'Failed to parse IPInfo batch response', 'arraypress' )
			);
		}

		return $data;
	}

	/**
	 * Get a specific field for an IP address
	 *
	 * @param string $ip    IP address to look up
	 * @param string $field Field to retrieve (e.g., 'country', 'city', 'loc', etc.)
	 *
	 * @return string|WP_Error The field value or WP_Error on failure
	 */
	public function get_field( string $ip, string $field ) {
		if ( ! $this->is_valid_ip( $ip ) ) {
			return new WP_Error(
				'invalid_ip',
				sprintf( __( 'Invalid IP address: %s', 'arraypress' ), $ip )
			);
		}

		// Check if field is cached
		$cache_key = $this->get_cache_key( $ip . '/' . $field );
		if ( $this->enable_cache ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return $cached_data;
			}
		}

		// Make API request
		$url  = self::API_BASE . $ip . '/' . $field;
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

		$body  = wp_remote_retrieve_body( $response );
		$value = trim( $body, "\" \t\n\r\0\x0B" ); // Remove quotes and whitespace

		// Cache the result
		if ( $this->enable_cache ) {
			set_transient( $cache_key, $value, $this->cache_expiration );
		}

		return $value;
	}

	/**
	 * Get multiple fields for an IP address
	 *
	 * @param string $ip     IP address to look up
	 * @param array  $fields Array of fields to retrieve
	 *
	 * @return array|WP_Error Array of field values or WP_Error on failure
	 */
	public function get_fields( string $ip, array $fields ) {
		if ( ! $this->is_valid_ip( $ip ) ) {
			return new WP_Error(
				'invalid_ip',
				sprintf( __( 'Invalid IP address: %s', 'arraypress' ), $ip )
			);
		}

		$results = [];
		foreach ( $fields as $field ) {
			$value = $this->get_field( $ip, $field );
			if ( is_wp_error( $value ) ) {
				return $value;
			}
			$results[ $field ] = $value;
		}

		return $results;
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