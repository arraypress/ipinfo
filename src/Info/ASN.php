<?php
/**
 * IPInfo.io API Response Classes
 *
 * Contains all response-related classes for handling IPInfo.io API data.
 * Each class represents a specific data structure returned by the API.
 *
 * @package     ArrayPress/Utils
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class ASNInfo
 *
 * Represents ASN (Autonomous System Number) information from the API.
 * Available in Basic plan and above.
 */
class ASN {

	/**
	 * Raw ASN data
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize ASN info
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Raw ASN data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the ASN number
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_asn(): ?string {
		return $this->data['asn'] ?? null;
	}

	/**
	 * Get the ASN name
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->data['name'] ?? null;
	}

	/**
	 * Get the ASN domain
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_domain(): ?string {
		return $this->data['domain'] ?? null;
	}

	/**
	 * Get the ASN route
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_route(): ?string {
		return $this->data['route'] ?? null;
	}

	/**
	 * Get the ASN type
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_type(): ?string {
		return $this->data['type'] ?? null;
	}

}