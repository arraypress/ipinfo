<?php
/**
 * IPInfo.io API Response Class
 *
 * Contains all response-related classes for handling IPInfo.io API data.
 * Each class represents a specific data structure returned by the API.
 *
 * @package     ArrayPress/IPInfo
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
	 * @return array|null
	 */
	public function __get( $name ) {
		if ( $name === 'all' ) {
			return $this->get_all();
		}

		return null;
	}

	/** Plan & Feature Detection **********************************************/

	/**
	 * Determine the IPInfo plan based on available data
	 *
	 * @return string Returns 'Free', 'Basic', 'Business', or 'Premium'
	 */
	public function get_plan(): string {
		if ( isset( $this->data['domains'] ) ) {
			return 'Premium';
		}

		if ( isset( $this->data['privacy'] ) || isset( $this->data['abuse'] ) || isset( $this->data['company'] ) ) {
			return 'Business';
		}

		if ( isset( $this->data['asn'] ) ) {
			return 'Basic';
		}

		return 'Free';
	}

	/**
	 * Check if the current plan has a specific feature
	 *
	 * @param string $feature Feature to check. One of: 'asn', 'privacy', 'abuse', 'company', 'domains'
	 *
	 * @return bool True if the feature is available in the current plan
	 */
	public function has_feature( string $feature ): bool {
		$plan = $this->get_plan();

		switch ( $feature ) {
			case 'asn':
				return in_array( $plan, [ 'Basic', 'Business', 'Premium' ] );
			case 'privacy':
			case 'abuse':
			case 'company':
				return in_array( $plan, [ 'Business', 'Premium' ] );
			case 'domains':
				return $plan === 'Premium';
			default:
				return false;
		}
	}

	/** Basic Information *****************************************************/

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

	/** Location Information **************************************************/

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

	/** Extended Location Information *****************************************/

	/**
	 * Get the country name
	 *
	 * @return string|null
	 */
	public function get_country_name(): ?string {
		$country_code = $this->get_country();

		return $country_code ? Locations::get_country_name( $country_code ) : null;
	}

	/**
	 * Get the country flag information
	 *
	 * @return CountryFlag|null
	 */
	public function get_country_flag(): ?CountryFlag {
		$country_code = $this->get_country();
		$flag_data    = $country_code ? Locations::get_flag( $country_code ) : null;

		return $flag_data ? new CountryFlag( $flag_data ) : null;
	}

	/**
	 * Get the country currency information
	 *
	 * @return CountryCurrency|null
	 */
	public function get_country_currency(): ?CountryCurrency {
		$country_code  = $this->get_country();
		$currency_data = $country_code ? Locations::get_currency( $country_code ) : null;

		return $currency_data ? new CountryCurrency( $currency_data ) : null;
	}

	/**
	 * Get the continent information
	 *
	 * @return Continent|null
	 */
	public function get_continent(): ?Continent {
		$country_code   = $this->get_country();
		$continent_data = $country_code ? Locations::get_continent( $country_code ) : null;

		return $continent_data ? new Continent( $continent_data ) : null;
	}

	/**
	 * Check if the country is in the European Union
	 *
	 * @return bool
	 */
	public function is_eu(): bool {
		$country_code = $this->get_country();

		return $country_code && Locations::is_eu( $country_code );
	}

	/** Geographical Information **********************************************/

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

	/** Organization Information **********************************************/

	/**
	 * Get the organization information
	 *
	 * @return string|null
	 */
	public function get_org(): ?string {
		return $this->data['org'] ?? null;
	}

	/** Enhanced Information (Basic Plan+) ************************************/

	/**
	 * Get ASN information (Basic plan and above)
	 *
	 * @return ASN|null
	 */
	public function get_asn(): ?ASN {
		return isset( $this->data['asn'] ) ? new ASN( $this->data['asn'] ) : null;
	}

	/** Business Information (Business Plan+) *********************************/

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

	/** Premium Information (Business Plan) ***********************************/

	/**
	 * Get domains information (Premium plan only)
	 *
	 * @return Domains|null
	 */
	public function get_domains(): ?Domains {
		return isset( $this->data['domains'] ) ? new Domains( $this->data['domains'] ) : null;
	}

}