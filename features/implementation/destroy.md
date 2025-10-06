# Destroy Command Implementation

## Overview
The destroy command provides a safe way to completely remove MChef instances, including all associated Docker containers, volumes, and registry entries.

## Implementation Details

### Command Structure
- **Command**: `mchef destroy <instance-name>`
- **Location**: [`src/Command/Destroy.php`](../../src/Command/Destroy.php)
- **Base Class**: `AbstractCommand`
- **Traits**: `SingletonTrait`, `ExecTrait`

### Key Features

#### 1. **Safety Validations**
- **Input validation**: Instance names must match strict pattern (`[a-zA-Z0-9_-]{1,64}`)
- **Shell escaping**: All container and volume names are properly escaped using `escapeshellarg()`
- Verifies instance exists in registry before proceeding
- Shows detailed list of what will be destroyed
- Requires typing exact word "yes" to confirm (not just "y")

#### 2. **Complete Cleanup**
- **Containers**: Stops and removes both `{instance}-moodle` and `{instance}-db` containers
- **Volumes**: Uses `docker inspect` to find and remove actual attached volumes
- **Registry**: Uses proper Configurator service abstraction for atomic registry updates
- **Active Instance**: Clears active instance if it was the destroyed one

#### 3. **Reliable Volume Discovery**
- Uses Docker service methods for volume inspection
- Leverages `docker inspect` to find volumes actually attached to containers
- Avoids pattern-matching that could accidentally destroy unrelated volumes

### Service Dependencies

#### Docker Service Enhancements
Added new methods to [`src/Service/Docker.php`](../../src/Service/Docker.php):

```php
/**
 * Get all volumes attached to a specific container.
 * Uses escapeshellarg() for safe shell execution.
 */
public function getContainerVolumes(string $containerName): array

/**
 * Get all volumes for multiple containers associated with an instance.
 */
public function getInstanceVolumes(string $instanceName): array

/**
 * Remove a volume by name with safe shell escaping.
 */
public function removeVolume(string $volumeName): bool

/**
 * Check if a volume exists with safe shell escaping.
 */
public function volumeExists(string $volumeName): bool
```

#### Configurator Service Enhancement
Added registry management method to [`src/Service/Configurator.php`](../../src/Service/Configurator.php):

```php
/**
 * Remove an instance from the registry by instance name.
 * Uses existing registry abstraction for consistent format and atomic writes.
 */
public function deregisterInstance(string $instanceName): bool
```

These methods use `docker inspect` to reliably identify volumes attached to containers, ensuring only instance-specific volumes are destroyed.

### Workflow

1. **Validation**: Verify instance exists in registry
2. **Discovery**: List containers and volumes that will be destroyed
3. **Confirmation**: Require user to type "yes" exactly
4. **Container Cleanup**: Stop and remove containers
5. **Volume Cleanup**: Remove attached volumes using Docker service
6. **Registry Cleanup**: Remove instance from registry and clear active instance if needed

### Error Handling
- Graceful handling of missing containers/volumes
- Continues cleanup even if some steps fail
- Clear error messages and warnings
- Safe fallback for non-existent resources

### Safety Features
- **Input validation**: Instance names restricted to alphanumeric, hyphens, underscores (1-64 chars)
- **Shell injection protection**: All parameters properly escaped with `escapeshellarg()`
- **Explicit confirmation**: Must type "yes" exactly (case-sensitive)
- **Preview mode**: Shows exactly what will be destroyed before confirmation
- **Registry validation**: Only destroys registered instances
- **Atomic operations**: Uses existing service abstractions for consistent data handling
- **Selective cleanup**: Only targets instance-specific resources

## Usage Examples

```bash
# Destroy a specific instance
mchef destroy my-project

# Example output:
# The following will be destroyed for instance 'my-project':
#   - Container: my-project-moodle
#   - Container: my-project-db
#   - Volume: mc-my-project_moodledata
#   - Volume: mc-my-project_pgdata
#   - Instance registration
# All associated containers / data will be destroyed. Type 'yes' to confirm: yes
```

## Integration Points

### Command Registration
The command is automatically registered via the `AbstractCommand` pattern and appears in the main MChef help system.

### Service Architecture
Properly integrated with the MChef service layer:
- Uses `Configurator` service for registry management
- Uses enhanced `Docker` service for container/volume operations
- Follows singleton pattern for consistent service access

## Testing Considerations

### Manual Testing
- Test with existing instances
- Test with non-existent instances
- Test cancellation (typing anything other than "yes")
- Test volume cleanup accuracy
- Test registry cleanup

### Edge Cases
- Containers that don't exist
- Volumes that are in use by other containers
- Corrupted registry entries
- Permission issues with Docker operations

## Related Features
- Works alongside existing `halt` command (stops containers)
- Complements `list` command (shows available instances)
- Integrates with `use` command (active instance management)
