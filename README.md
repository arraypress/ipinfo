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

// Single IP lookup
$info = $client->get_ip_info( '8.8.8.8' );

// Get specific field only
$country = $client->get_field( '8.8.8.8', 'country' );  // Returns: "US"
$city = $client->get_field( '8.8.8.8', 'city' );       // Returns: "Mountain View"

// Get multiple specific fields
$fields = $client->get_fields( '8.8.8.8', ['country', 'city', 'org'] );
// Returns: ['country' => 'US', 'city' => 'Mountain View', 'org' => 'Google LLC']

// Batch processing multiple IPs
$ips = [ '8.8.8.8', '1.1.1.1' ];
$batch_results = $client->get_batch_info( $ips );
foreach ( $batch_results as $ip => $info ) {
    echo "$ip is located in " . $info->get_city();
}
```

## Available Methods

### Client Methods

```php
// Initialize client with options
$client = new Client(
    'your-token-here',    // API token
    true,                 // Enable caching (optional, default: true)
    3600                  // Cache duration in seconds (optional, default: 3600)
);

// Get complete information for an IP
$info = $client->get_ip_info( '8.8.8.8' );

// Get specific field
$field = $client->get_field( '8.8.8.8', 'country' );

// Get multiple fields
$fields = $client->get_fields( '8.8.8.8', ['country', 'city'] );

// Process multiple IPs (max 1000)
$results = $client->get_batch_info(
    [ '8.8.8.8', '1.1.1.1' ], // IP addresses
    1000,                     // Batch size (optional, default: 1000)
    false,                    // Filter response (optional, default: false)
    5                         // Timeout in seconds (optional, default: 5)
);

// Cache management
$client->clear_cache( '8.8.8.8' );  // Clear specific IP
$client->clear_cache();             // Clear all cached data
```

### Response Methods

#### Basic Information

```php
// Get IP address
$ip = $info->get_ip();
// Returns: "8.8.8.8"

// Get hostname (if available)
$hostname = $info->get_hostname();
// Returns: "dns.google"

// Check if IP is anycast
$is_anycast = $info->is_anycast();
// Returns: true/false

// Get city name
$city = $info->get_city();
// Returns: "Mountain View"

// Get region/state
$region = $info->get_region();
// Returns: "California"

// Get country information
$country_code = $info->get_country();      // Returns: "US"
$country_name = $info->get_country_name(); // Returns: "United States"

// Get coordinates
$coords = $info->get_coordinates();
// Returns: ['latitude' => 37.4056, 'longitude' => -122.0775]

// Individual coordinate access
$lat = $info->get_latitude();   // Returns: 37.4056
$long = $info->get_longitude(); // Returns: -122.0775

// Get postal code
$postal = $info->get_postal();
// Returns: "94043"

// Get timezone
$timezone = $info->get_timezone();
// Returns: "America/Los_Angeles"

// Get plan level
$plan = $info->get_plan();
// Returns: "Free", "Basic", "Business", or "Premium"

// Check feature availability
$has_asn = $info->has_feature( 'asn' );        // Basic plan and above
$has_privacy = $info->has_feature( 'privacy' ); // Business plan and above
$has_domains = $info->has_feature( 'domains' ); // Premium plan only

// Extended country information
$flag = $info->get_country_flag();
if ($flag) {
    $emoji = $flag->get_emoji();     // Returns: "🇺🇸"
    $unicode = $flag->get_unicode();  // Returns: "U+1F1FA U+1F1F8"
}

$currency = $info->get_country_currency();
if ($currency) {
    $code = $currency->get_code();    // Returns: "USD"
    $symbol = $currency->get_symbol(); // Returns: "$"
}

$continent = $info->get_continent();
if ($continent) {
    $name = $continent->get_name();   // Returns: "North America"
    $code = $continent->get_code();   // Returns: "NA"
}

$is_eu = $info->is_eu(); // Returns: true/false
```

### ASN Information (Basic Plan and Above)

```php
if ($asn = $info->get_asn()) {
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
    $total = $domains->get_total();     // Returns: total number of domains
    $page = $domains->get_page();       // Returns: current page number
    $list = $domains->get_domains();    // Returns: array of domain names
}
```

## Response Format Examples

### Raw Data Access

```php
// Get full raw data array
$raw_data = $info->get_all();

// Magic property access
$raw_data = $info->all;
```

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
    ],
    'abuse' => [
        'address' => 'US, CA, Mountain View, 1600 Amphitheatre Parkway, 94043',
        'country' => 'US',
        'email' => 'network-abuse@google.com',
        'name' => 'Network Abuse',
        'network' => '8.8.8.0/24',
        'phone' => '+1-650-253-0000'
    ]
]
```

### Premium Plan Additional Data

```php
[
    // ... business plan data ...
    'domains' => [
        'total' => 2535948,
        'domains' => [
            'pub.dev',
            'virustotal.com',
            'blooket.com',
            'go.dev',
            'rytr.me'
        ]
    ]
]
```

### Batch Processing Response

```php
$batch_results = $client->get_batch_info(['8.8.8.8', '1.1.1.1']);
// Returns:
[
    '8.8.8.8' => Response Object,
    '1.1.1.1' => Response Object
]
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

## Contributions

Contributions to this library are highly appreciated. Raise issues on GitHub or submit pull requests for bug
fixes or new features. Share feedback and suggestions for improvements.

## License: GPLv2 or later

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public
License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.