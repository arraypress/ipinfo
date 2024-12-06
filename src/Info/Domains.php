<?php
/**
 * Domains Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class DomainsInfo
 *
 * Represents domains information from the API.
 * Available in Premium plan only.
 */
class Domains {

	/**
	 * Raw domains data
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize domains info
	 *
	 * @param array $data Raw domains data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the total number of domains
	 *
	 * @return int
	 */
	public function get_total(): int {
		return $this->data['total'] ?? 0;
	}

	/**
	 * Get the list of domains
	 *
	 * @return array
	 */
	public function get_domains(): array {
		return $this->data['domains'] ?? [];
	}

	/**
	 * Get the current page number
	 *
	 * @return int
	 */
	public function get_page(): int {
		return $this->data['page'] ?? 0;
	}

}