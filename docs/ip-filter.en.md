# IP Filter

GeolocatorBundle allows you to configure allowed IP addresses (whitelist) and blocked IP addresses (blacklist) to precisely control access to your application.

## Configuration

The IP filter is configured in your Symfony configuration file:

```yaml
# config/packages/geolocator.yaml
geolocator:
  ip_filters:
    enabled: true                   # Enable or disable the IP filter
    allow_list_exclusive: false     # If true, only IPs in allow_list are allowed
    allow_list:                     # List of allowed IPs
      - '127.0.0.1'                 # Exact IP
      - '192.168.1.0/24'            # CIDR range
      - '10.0.0.*'                  # Wildcard
    block_list:                     # List of blocked IPs
      - '1.2.3.4'
      - '5.6.7.0/24'
```

## Supported Formats

The IP filter supports different formats:

1. **Exact IP**: `127.0.0.1`
2. **CIDR Range**: `192.168.1.0/24` (all IPs in the 192.168.1.0 network with a 24-bit mask)
3. **Wildcard**: `10.0.0.*` (all IPs starting with 10.0.0)

## Operating Modes

### Standard Mode

By default (`allow_list_exclusive: false`), the filter works as follows:

1. If the IP is in the `allow_list`, it is **always allowed**
2. If the IP is in the `block_list`, it is **always blocked**
3. If the IP is not in any list, it is **allowed by default**

### Exclusive Whitelist Mode

When `allow_list_exclusive: true`, the behavior changes to:

1. If the IP is in the `allow_list`, it is **allowed**
2. If the IP is not in the `allow_list`, it is **blocked**
3. The `block_list` is ignored in this mode

This mode is useful for restricting access to only a defined set of IP addresses.

## Programmatic Usage

You can also use the IP filter in your code:

```php
// Check if an IP is allowed
$isAllowed = $geolocatorService->isIpAllowed('192.168.1.10');

// Get the service directly
$ipFilter = $container->get('geolocator.ip_filter');

// Check if an IP is in the allow list
$isInAllowList = $ipFilter->isInAllowList('192.168.1.10');

// Check if an IP is in the block list
$isInBlockList = $ipFilter->isInBlockList('1.2.3.4');
```

## Processing Order

In the request processing cycle, the IP filter is checked **before** other filters (country, VPN, etc.), allowing you to create exceptions for certain IP addresses even if they come from normally blocked countries.

## Use Cases

1. **Testing Environment**: Only allow IPs from your company
2. **Private API**: Restrict access to IPs of known partners
3. **Abuse Prevention**: Block specific IPs that have shown suspicious behavior
4. **Exceptions**: Allow certain IPs even if they come from countries or VPNs that are normally blocked

## Logging

All IP filter actions are recorded in the `geolocator.log` with the following information:
- Checked IP
- Result (allowed/blocked)
- Matching rule

This allows you to easily debug the IP filter configuration.
