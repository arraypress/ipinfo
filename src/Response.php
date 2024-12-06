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
 */

declare( strict_types=1 );

namespace ArrayPress\IPInfo;

use ArrayPress\IPInfo\Info\{Abuse, ASN, Company, Continent, CountryCurrency, CountryFlag, Domains, Privacy};

/**
 * Class Response
 *
 * Main response object for IPInfo API data. Handles data based on plan level.
 */
class Response {

	/**
	 * Raw response data from the API
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Initialize the response object
	 *
	 * @param array $data Raw response data from IPInfo API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get the IP address
	 *
	 * @return string|null
	 */
	public function get_ip(): ?string {
		return $this->data['ip'] ?? null;
	}

	/**
	 * Get the hostname
	 *
	 * @return string|null
	 */
	public function get_hostname(): ?string {
		return $this->data['hostname'] ?? null;
	}

	/**
	 * Check if the IP is anycast
	 *
	 * @return bool
	 */
	public function is_anycast(): bool {
		return (bool) ( $this->data['anycast'] ?? false );
	}

	/**
	 * Get the city name
	 *
	 * @return string|null
	 */
	public function get_city(): ?string {
		return $this->data['city'] ?? null;
	}

	/**
	 * Get the region name
	 *
	 * @return string|null
	 */
	public function get_region(): ?string {
		return $this->data['region'] ?? null;
	}

	/**
	 * Get the country code
	 *
	 * @return string|null
	 */
	public function get_country(): ?string {
		return $this->data['country'] ?? null;
	}

	/**
	 * Get the country name
	 *
	 * @return string|null
	 */
	public function get_country_name(): ?string {
		return $this->data['country_name'] ?? null;
	}

	/**
	 * Get the country flag information
	 *
	 * @return CountryFlag|null
	 */
	public function get_country_flag(): ?CountryFlag {
		return isset( $this->data['country_flag'] ) ? new CountryFlag( $this->data['country_flag'] ) : null;
	}

	/**
	 * Get the country flag URL
	 *
	 * @return string|null
	 */
	public function get_country_flag_url(): ?string {
		return $this->data['country_flag_url'] ?? null;
	}

	/**
	 * Get the country currency information
	 *
	 * @return CountryCurrency|null
	 */
	public function get_country_currency(): ?CountryCurrency {
		return isset( $this->data['country_currency'] ) ? new CountryCurrency( $this->data['country_currency'] ) : null;
	}

	/**
	 * Check if the country is in the European Union
	 *
	 * @return bool
	 */
	public function is_eu(): bool {
		return (bool) ( $this->data['is_eu'] ?? false );
	}

	/**
	 * Get the continent information
	 *
	 * @return Continent|null
	 */
	public function get_continent(): ?Continent {
		return isset( $this->data['continent'] ) ? new Continent( $this->data['continent'] ) : null;
	}

	/**
	 * Get the coordinates
	 *
	 * @return array|null Array with 'latitude' and 'longitude' or null if not available
	 */
	public function get_coordinates(): ?array {
		if ( isset( $this->data['latitude'], $this->data['longitude'] ) ) {
			return [
				'latitude'  => (float) $this->data['latitude'],
				'longitude' => (float) $this->data['longitude']
			];
		}

		if ( isset( $this->data['loc'] ) ) {
			list( $latitude, $longitude ) = array_pad( explode( ',', $this->data['loc'] ), 2, null );

			return [
				'latitude'  => (float) $latitude,
				'longitude' => (float) $longitude
			];
		}

		return null;
	}

	/**
	 * Get the latitude
	 *
	 * @return float|null
	 */
	public function get_latitude(): ?float {
		return isset( $this->data['latitude'] ) ? (float) $this->data['latitude'] : null;
	}

	/**
	 * Get the longitude
	 *
	 * @return float|null
	 */
	public function get_longitude(): ?float {
		return isset( $this->data['longitude'] ) ? (float) $this->data['longitude'] : null;
	}

	/**
	 * Get the organization information
	 *
	 * @return string|null
	 */
	public function get_org(): ?string {
		return $this->data['org'] ?? null;
	}

	/**
	 * Get the postal code
	 *
	 * @return string|null
	 */
	public function get_postal(): ?string {
		return $this->data['postal'] ?? null;
	}

	/**
	 * Get the timezone
	 *
	 * @return string|null
	 */
	public function get_timezone(): ?string {
		return $this->data['timezone'] ?? null;
	}

	/**
	 * Get ASN information (Basic plan and above)
	 *
	 * @return ASN|null
	 */
	public function get_asn(): ?ASN {
		return isset( $this->data['asn'] ) ? new ASN( $this->data['asn'] ) : null;
	}

	/**
	 * Get company information (Business plan and above)
	 *
	 * @return Company|null
	 */
	public function get_company(): ?Company {
		return isset( $this->data['company'] ) ? new Company( $this->data['company'] ) : null;
	}

	/**
	 * Get privacy information (Business plan and above)
	 *
	 * @return Privacy|null
	 */
	public function get_privacy(): ?Privacy {
		return isset( $this->data['privacy'] ) ? new Privacy( $this->data['privacy'] ) : null;
	}

	/**
	 * Get abuse contact information (Business plan and above)
	 *
	 * @return Abuse|null
	 */
	public function get_abuse(): ?Abuse {
		return isset( $this->data['abuse'] ) ? new Abuse( $this->data['abuse'] ) : null;
	}

	/**
	 * Get domains information (Premium plan only)
	 *
	 * @return Domains|null
	 */
	public function get_domains(): ?Domains {
		return isset( $this->data['domains'] ) ? new Domains( $this->data['domains'] ) : null;
	}

	/**
	 * Get raw data array
	 *
	 * @return array
	 */
	public function get_all(): array {
		return $this->data;
	}

	/**
	 * Magic getter for accessing the raw data array
	 *
	 * @return array
	 */
	public function __get( $name ) {
		if ( $name === 'all' ) {
			return $this->get_all();
		}

		return null;
	}

}