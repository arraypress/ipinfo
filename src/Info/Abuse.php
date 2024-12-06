<?php
/**
 * Abuse Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class AbuseInfo
 *
 * Represents abuse contact information from the API.
 * Available in Business plan and above.
 */
class Abuse {

	/**
	 * Raw abuse contact data
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize abuse info
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Raw abuse contact data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the abuse contact address
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_address(): ?string {
		return $this->data['address'] ?? null;
	}

	/**
	 * Get the abuse contact country
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_country(): ?string {
		return $this->data['country'] ?? null;
	}

	/**
	 * Get the abuse contact email
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_email(): ?string {
		return $this->data['email'] ?? null;
	}

	/**
	 * Get the abuse contact name
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->data['name'] ?? null;
	}

	/**
	 * Get the abuse contact network
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_network(): ?string {
		return $this->data['network'] ?? null;
	}

	/**
	 * Get the abuse contact phone
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_phone(): ?string {
		return $this->data['phone'] ?? null;
	}

}