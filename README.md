# IPInfo

Turn an IP address into a country, a city, a network and a privacy flag.

## What it does

IPInfo.io answers where an address is and what it belongs to. This calls it,
gives back a response object with named methods rather than a decoded array,
and caches the answer — the same visitor hitting five pages should be one
lookup, not five.

It also takes a list, so a report over a month of orders is one request rather
than a thousand.

## Features

* Get the country, region, city and coordinates for an address
* See the network it belongs to — the ASN, the company, the type of connection
* Tell whether it is a VPN, proxy, Tor exit or hosting provider
* Look up many addresses in one request, for a report or an import
* Pull a single field when that is all you want, without fetching the rest
* Cache answers for as long as suits, and clear one address on demand

## Installation

```bash
composer require arraypress/wp-ipinfo
```

## Quick start

Show an order's location in the admin, without a lookup on every page load:

```php
use ArrayPress\IPInfo\Client;

$client = new Client( $api_key );
$info   = $client->get_ip_info( $order->ip );

if ( ! is_wp_error( $info ) ) {
	printf(
		'%s, %s',
		esc_html( $info->get_city() ),
		esc_html( $info->get_country_name() )
	);
}
```

When one field is all you need, ask for one:

```php
$country = $client->get_field( $order->ip, 'country' );
```

## What it does not do

An IP tells you where the network is, not where the person is. A mobile
customer can appear in another city and a VPN in another country — useful as
one signal among several, misleading on its own.

## Requirements

* PHP 8.3 or later
* WordPress 7.1 or later
* An IPInfo.io API token

## License

GPL-2.0-or-later
