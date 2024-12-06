<?php
/**
 * Country Currency Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class CountryCurrency
 *
 * Represents country currency information.
 */
class CountryCurrency {

	/**
	 * Raw currency data
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize country currency info
	 *
	 * @param array $data Raw country currency data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the currency code
	 *
	 * @return string|null
	 */
	public function get_code(): ?string {
		return $this->data['code'] ?? null;
	}

	/**
	 * Get the currency symbol
	 *
	 * @return string|null
	 */
	public function get_symbol(): ?string {
		return $this->data['symbol'] ?? null;
	}

}