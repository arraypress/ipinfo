<?php
/**
 * Country Flag Information
 *
 * @package     ArrayPress/IPInfo
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @since       1.0.0
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo\Info;

/**
 * Class CountryFlag
 *
 * Represents country flag information including emoji and unicode.
 */
class CountryFlag {

	/**
	 * Raw flag data
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize country flag info
	 *
	 * @param array $data Raw country flag data from the API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the flag emoji
	 *
	 * @return string|null
	 */
	public function get_emoji(): ?string {
		return $this->data['emoji'] ?? null;
	}

	/**
	 * Get the flag unicode
	 *
	 * @return string|null
	 */
	public function get_unicode(): ?string {
		return $this->data['unicode'] ?? null;
	}

}