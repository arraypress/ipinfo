<?php
/**
 * Privacy Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class PrivacyInfo
 *
 * Represents privacy information from the API.
 * Available in Business plan and above.
 */
class Privacy {

	/**
	 * Raw privacy data
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize privacy info
	 *
	 * @param array $data Raw privacy data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Check if the IP is a VPN
	 *
	 * @return bool
	 */
	public function is_vpn(): bool {
		return $this->data['vpn'] ?? false;
	}

	/**
	 * Check if the IP is a proxy
	 *
	 * @return bool
	 */
	public function is_proxy(): bool {
		return $this->data['proxy'] ?? false;
	}

	/**
	 * Check if the IP is a Tor exit node
	 *
	 * @return bool
	 */
	public function is_tor(): bool {
		return $this->data['tor'] ?? false;
	}

	/**
	 * Check if the IP is a relay
	 *
	 * @return bool
	 */
	public function is_relay(): bool {
		return $this->data['relay'] ?? false;
	}

	/**
	 * Check if the IP is a hosting provider
	 *
	 * @return bool
	 */
	public function is_hosting(): bool {
		return $this->data['hosting'] ?? false;
	}

	/**
	 * Get the privacy service name
	 *
	 * @return string|null
	 */
	public function get_service(): ?string {
		return $this->data['service'] ?? null;
	}

}