# IPInfo Library for WordPress

A WordPress library for IPInfo.io API integration with smart caching and plan-aware responses.

## Installation

Install via Composer:

```bash
composer require arraypress/ipinfo
```

## Requirements

- PHP 7.4 or later
- WordPress 6.2.2 or later
- IPInfo.io API key

## Basic Usage

```php
use ArrayPress\IPInfo\Client;

// Initialize with your API token
$client = new Client( 'your-token-here' );

// Get IP information
$info = $client->get_ip_info( '8.8.8.8' );

// Access basic data
$city = $info->get_city();
$country = $info->get_country();
$coords = $info->get_coordinates();
```

## Available Methods

### Basic Information

```php
// Get IP address
$ip = $info->get_ip();
// Returns: "8.8.8.8"

// Get city name
$city = $info->get_city();
// Returns: "Mountain View"

// Get region/state
$region = $info->get_region();
// Returns: "California"

// Get country code
$country = $info->get_country();
// Returns: "US"

// Get coordinates
$coords = $info->get_coordinates();
// Returns: ['latitude' => 37.4056, 'longitude' => -122.0775]

// Get postal code
$postal = $info->get_postal();
// Returns: "94043"

// Get timezone
$timezone = $info->get_timezone();
// Returns: "America/Los_Angeles"
```

### ASN Information (Basic Plan and Above)

```php
if ( $asn = $info->get_asn() ) {
    $asn_number = $asn->get_asn();      // Returns: "AS15169"
    $asn_name = $asn->get_name();       // Returns: "Google LLC"
    $asn_domain = $asn->get_domain();   // Returns: "google.com"
    $asn_route = $asn->get_route();     // Returns: "8.8.8.0/24"
    $asn_type = $asn->get_type();       // Returns: "hosting"
}
```

### Privacy Detection (Business/Premium Plans)

```php
if ($privacy = $info->get_privacy()) {
    $is_vpn = $privacy->is_vpn();         // Returns: false
    $is_proxy = $privacy->is_proxy();      // Returns: false
    $is_tor = $privacy->is_tor();          // Returns: false
    $is_relay = $privacy->is_relay();      // Returns: false
    $is_hosting = $privacy->is_hosting();  // Returns: true
    $service = $privacy->get_service();    // Returns: null or service name
}
```

### Company Information (Business/Premium Plans)

```php
if ($company = $info->get_company()) {
    $name = $company->get_name();      // Returns: "Google LLC"
    $domain = $company->get_domain();  // Returns: "google.com"
    $type = $company->get_type();      // Returns: "hosting"
}
```

### Abuse Contact Information (Business/Premium Plans)

```php
if ($abuse = $info->get_abuse()) {
    $email = $abuse->get_email();     // Returns: "network-abuse@google.com"
    $name = $abuse->get_name();       // Returns: "Google LLC"
    $phone = $abuse->get_phone();     // Returns: "+1-650-253-0000"
    $address = $abuse->get_address(); // Returns: "1600 Amphitheatre Parkway..."
    $country = $abuse->get_country(); // Returns: "US"
    $network = $abuse->get_network(); // Returns: "8.8.8.0/24"
}
```

### Domains Information (Premium Plan Only)

```php
if ($domains = $info->get_domains()) {
    $total = $domains->get_total();     // Returns: 2
    $page = $domains->get_page();       // Returns: 0
    $list = $domains->get_domains();    // Returns: ['example.com', 'example.org']
}
```

## Configuration

```php
// Initialize with custom cache settings
$client = new Client(
    'your-token-here',    // IPInfo API token
    true,                 // Enable caching
    3600                  // Cache expiration in seconds
);

// Clear cache for specific IP
$client->clear_cache('8.8.8.8');

// Clear all cached data
$client->clear_cache();
```

## Error Handling

The library uses WordPress's `WP_Error` for error handling:

```php
$info = $client->get_ip_info('invalid-ip');

if (is_wp_error($info)) {
    echo $info->get_error_message();
    // Output: "Invalid IP address: invalid-ip"
}
```

Common error cases:

- Invalid IP address
- Invalid API token
- API request failure
- Rate limit exceeded
- Invalid response format

## Response Format Examples

### Basic Plan Response

```php
[
    'ip' => '8.8.8.8',
    'city' => 'Mountain View',
    'region' => 'California',
    'country' => 'US',
    'loc' => '37.4056,-122.0775',
    'org' => 'AS15169 Google LLC',
    'postal' => '94043',
    'timezone' => 'America/Los_Angeles'
]
```

### Business Plan Additional Data

```php
[
    // ... basic plan data ...
    'asn' => [
        'asn' => 'AS15169',
        'name' => 'Google LLC',
        'domain' => 'google.com',
        'route' => '8.8.8.0/24',
        'type' => 'hosting'
    ],
    'company' => [
        'name' => 'Google LLC',
        'domain' => 'google.com',
        'type' => 'hosting'
    ],
    'privacy' => [
        'vpn' => false,
        'proxy' => false,
        'tor' => false,
        'relay' => false,
        'hosting' => true,
        'service' => null
    ]
]
```

## Contributions

Contributions to this library are highly appreciated. Raise issues on GitHub or submit pull requests for bug
fixes or new features. Share feedback and suggestions for improvements.

## License: GPLv2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.