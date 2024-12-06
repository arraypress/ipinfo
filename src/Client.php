<?php
/**
 * IPInfo.io Client Class
 *
 * @package     ArrayPress/Utils
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo;

use WP_Error;

/**
 * Class Client
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
	 * Make a GET request to the IPInfo API
	 *
	 * @param string $endpoint API endpoint
	 * @param array  $args     Additional request arguments
	 *
	 * @return array|WP_Error Response array or WP_Error on failure
	 */
	private function make_get_request( string $endpoint, array $args = [] ) {
		$default_args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->token,
				'Accept'        => 'application/json',
			],
			'timeout' => 15,
		];

		$args     = wp_parse_args( $args, $default_args );
		$response = wp_remote_get( self::API_BASE . $endpoint, $args );

		return $this->handle_response( $response );
	}

	/**
	 * Make a POST request to the IPInfo API
	 *
	 * @param string $endpoint API endpoint
	 * @param array  $body     Request body
	 * @param array  $args     Additional request arguments
	 *
	 * @return array|WP_Error Response array or WP_Error on failure
	 */
	private function make_post_request( string $endpoint, array $body, array $args = [] ) {
		$default_args = [
			'headers' => [
				'Authorization' => 'Bearer ' . $this->token,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
			],
			'body'    => json_encode( $body ),
			'method'  => 'POST',
		];

		$args     = wp_parse_args( $args, $default_args );
		$response = wp_remote_post( self::API_BASE . $endpoint, $args );

		return $this->handle_response( $response );
	}

	/**
	 * Handle API response
	 *
	 * @param array|WP_Error $response API response
	 *
	 * @return array|WP_Error Processed response or WP_Error
	 */
	private function handle_response( $response ) {
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
	 * Get complete information for an IP address
	 *
	 * @param string $ip IP address to look up
	 *
	 * @return Response|WP_Error Response object or WP_Error on failure
	 */
	public function get_ip_info( string $ip ) {
		if ( ! IP::is_valid_for_lookup( $ip ) ) {
			return new WP_Error(
				'invalid_ip',
				sprintf( __( 'Invalid or bogon IP address: %s', 'arraypress' ), $ip )
			);
		}

		$cache_key = $this->get_cache_key( $ip );

		if ( $this->enable_cache ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return new Response( $cached_data );
			}
		}

		$response = $this->make_get_request( $ip );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		if ( $this->enable_cache ) {
			set_transient( $cache_key, $response, $this->cache_expiration );
		}

		return new Response( $response );
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
		$valid_ips = array_filter( $ips, [ IP::class, 'is_valid_for_lookup' ] );

		if ( empty( $valid_ips ) ) {
			return new WP_Error(
				'invalid_ips',
				__( 'No valid IPs provided for lookup', 'arraypress' )
			);
		}

		$results       = $this->get_cached_batch_results( $valid_ips );
		$remaining_ips = array_diff( $valid_ips, array_keys( $results ) );

		if ( empty( $remaining_ips ) ) {
			return $results;
		}

		$batch_size = min( max( 1, $batch_size ), self::BATCH_MAX_SIZE );
		$batches    = array_chunk( array_values( $remaining_ips ), $batch_size );

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
	 * Get cached results for a batch of IPs
	 *
	 * @param array $ips Array of IP addresses
	 *
	 * @return array Array of cached results
	 */
	private function get_cached_batch_results( array $ips ): array {
		$results = [];
		if ( $this->enable_cache ) {
			foreach ( $ips as $ip ) {
				$cached_data = get_transient( $this->get_cache_key( $ip ) );
				if ( false !== $cached_data ) {
					$results[ $ip ] = new Response( $cached_data );
				}
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
	private function process_batch( array $ips, bool $filter, int $timeout ) {
		$endpoint = 'batch' . ( $filter ? '?filter=1' : '' );

		return $this->make_post_request( $endpoint, $ips, [ 'timeout' => $timeout ] );
	}

	/**
	 * Get a specific field for an IP address
	 *
	 * @param string $ip    IP address to look up
	 * @param string $field Field to retrieve
	 *
	 * @return string|WP_Error The field value or WP_Error on failure
	 */
	public function get_field( string $ip, string $field ) {
		if ( ! IP::is_valid_for_lookup( $ip ) ) {
			return new WP_Error(
				'invalid_ip',
				sprintf( __( 'Invalid IP address: %s', 'arraypress' ), $ip )
			);
		}

		$cache_key = $this->get_cache_key( $ip . '/' . $field );

		if ( $this->enable_cache ) {
			$cached_data = get_transient( $cache_key );
			if ( false !== $cached_data ) {
				return $cached_data;
			}
		}

		$response = $this->make_get_request( $ip . '/' . $field );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		// For single field requests, the response should be treated as a string
		$value = is_array( $response ) ? wp_remote_retrieve_body( $response ) : $response;
		$value = trim( $value, "\" \t\n\r\0\x0B" );

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
		if ( ! IP::is_valid_for_lookup( $ip ) ) {
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
	 * Generate cache key for an IP address
	 *
	 * @param string $ip IP address
	 *
	 * @return string Cache key
	 */
	private function get_cache_key( string $ip ): string {
		return 'ipinfo_' . md5( $ip . $this->token );
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
				) ) !== false;
	}

}