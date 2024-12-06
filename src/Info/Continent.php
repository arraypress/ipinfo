<?php
/**
 * Continent Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class Continent
 *
 * Represents continent information.
 */
class Continent {

	/**
	 * Raw continent data
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize continent info
	 *
	 * @param array $data Raw continent data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the continent code
	 *
	 * @return string|null
	 */
	public function get_code(): ?string {
		return $this->data['code'] ?? null;
	}

	/**
	 * Get the continent name
	 *
	 * @return string|null
	 */
	public function get_name(): ?string {
		return $this->data['name'] ?? null;
	}

}