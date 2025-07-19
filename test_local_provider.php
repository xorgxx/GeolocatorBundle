<?php

require_once __DIR__ . '/vendor/autoload.php';

use GeolocatorBundle\Provider\LocalProvider;

// Create the LocalProvider
$localProvider = new LocalProvider();

// Test with a local IP
$localIp = '127.0.0.1';
echo "Testing local IP: $localIp\n";
$localGeoLocation = $localProvider->getGeoLocation($localIp);
echo "Country code: " . $localGeoLocation->getCountryCode() . "\n";
echo "Country name: " . $localGeoLocation->getCountryName() . "\n";
echo "Is local: " . ($localGeoLocation->getData()['is_local'] ? 'Yes' : 'No') . "\n";
echo "\n";

// Test with a private IP
$privateIp = '192.168.1.1';
echo "Testing private IP: $privateIp\n";
$privateGeoLocation = $localProvider->getGeoLocation($privateIp);
echo "Country code: " . $privateGeoLocation->getCountryCode() . "\n";
echo "Country name: " . $privateGeoLocation->getCountryName() . "\n";
echo "Is local: " . ($privateGeoLocation->getData()['is_local'] ? 'Yes' : 'No') . "\n";
echo "\n";

// Test with a public IP
$publicIp = '8.8.8.8';
echo "Testing public IP: $publicIp\n";
$publicGeoLocation = $localProvider->getGeoLocation($publicIp);
echo "Country code: " . $publicGeoLocation->getCountryCode() . "\n";
echo "Country name: " . $publicGeoLocation->getCountryName() . "\n";
echo "Is local: " . ($publicGeoLocation->getData()['is_local'] ? 'Yes' : 'No') . "\n";