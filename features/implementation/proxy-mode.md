# Feature Implementation: Proxy Mode

## Overview
Proxy mode enables multiple Moodle instances to run on unique ports, with an nginx proxy container routing requests based on hostname. This allows for easy access to multiple instances via different hostnames, all managed by Moodle Chef.

## Implementation Details
- **RegistryInstance Model**: Added `proxyModePort` property to track allocated proxy ports for each instance.
- **Port Allocation**: Ports are allocated and recorded in the registry before Docker templates are rendered, ensuring correct port mapping for each instance.
- **ProxyService**: Created to manage the nginx proxy container, generate configuration files, and handle container restarts when configuration changes.
- **DockerData Model**: Extended with `useProxy`, `proxyModePort`, and `hostPort` properties to support proxy mode logic and correct port mapping in templates.
- **Templates**: Updated `main.compose.yml.twig` and related templates to use `hostPort` for port mapping, supporting both proxy and non-proxy modes.
- **Main Service**: Refactored to register instances before rendering Docker templates, always passing the correct port to templates and ensuring the proxy container is started and configured.
- **Testing**: Added PHPUnit tests for proxy mode logic, CLI/manual tests for container startup, and curl-based tests for HTTP routing.

## Code Examples
```php
// See src/Service/Main.php for port allocation and proxy logic
// See src/Service/ProxyService.php for proxy config generation and container management
```

## Testing
- Automated PHPUnit tests for model/service logic.
- Manual CLI and docker tests for container and proxy behavior.
- curl tests for HTTP routing through the proxy.

## Configuration
- Global proxy mode config in main config file
- Registry tracks allocated ports and instance hostnames
- Proxy config and container managed automatically

## Usage
- Enable proxy mode in global config
- Run `mchef up <recipe>` for each instance
- Access instances via their configured hostnames

## Future Considerations
- Dynamic removal of proxy config for deleted instances
- Improved error handling for port conflicts
- Support for SSL termination in proxy

## References
- See `features/requirements/proxy-mode.md` for requirements and testing details.
- See code comments in `ProxyService` and `Main` for implementation specifics.
