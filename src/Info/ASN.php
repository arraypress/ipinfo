<?php
/**
 * ASN Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
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
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize ASN info
	 *
	 * @param array $data Raw ASN data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the ASN number
	 *
	 * @return string|null
	 */
	public function get_asn(): ?string {
		return $this->data['asn'] ?? null;
	}

	/**
	 * Get the ASN name
	 *
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->data['name'] ?? null;
	}

	/**
	 * Get the ASN domain
	 *
	 * @return string|null
	 */
	public function get_domain(): ?string {
		return $this->data['domain'] ?? null;
	}

	/**
	 * Get the ASN route
	 *
	 * @return string|null
	 */
	public function get_route(): ?string {
		return $this->data['route'] ?? null;
	}

	/**
	 * Get the ASN type
	 *
	 * @return string|null
	 */
	public function get_type(): ?string {
		return $this->data['type'] ?? null;
	}

}