<?php
/**
 * Company Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class CompanyInfo
 *
 * Represents company information from the API.
 * Available in Business plan and above.
 *
 * @since 1.0.0
 */
class Company {

	/**
	 * Raw company data
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize company info
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Raw company data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the company name
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->data['name'] ?? null;
	}

	/**
	 * Get the company domain
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_domain(): ?string {
		return $this->data['domain'] ?? null;
	}

	/**
	 * Get the company type
	 *
	 * @since 1.0.0
	 *
	 * @return string|null
	 */
	public function get_type(): ?string {
		return $this->data['type'] ?? null;
	}
}