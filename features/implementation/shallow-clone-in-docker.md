# Shallow Clone in Docker - Implementation

## Status - Completed

## Github issue number - #34

## Description

Implemented shallow cloning of plugins directly in the Docker container, ensuring plugins are always available in the Docker image regardless of volume mount settings.

## Implementation Details

### 1. Recipe Model Changes (`src/Model/Recipe.php`)

- Added new `mountPlugins` property (bool, default false)
- Marked `cloneRepoPlugins` as deprecated with PHPDoc annotation
- Maintained backward compatibility

```php
/**
 * @var bool - if true, clone repository plugins to cwd/plugins
 * @deprecated Use mountPlugins instead
 */
public bool $cloneRepoPlugins = false,

/**
 * @var bool - if true, use volume mounts for plugins (allows local development)
 */
public bool $mountPlugins = false,
```

### 2. Plugin Service Updates (`src/Service/Plugins.php`)

- Added deprecation warning when `cloneRepoPlugins` is used
- Implemented backward compatibility by setting `mountPlugins = true` when `cloneRepoPlugins = true`
- Enhanced `getPluginsInfoFromRecipe()` to handle both volume mount and non-volume mount scenarios
- Made `extractRepoInfoFromPlugin()` method public for use by Main service

**Key Logic:**
- When `mountPlugins = true`: Clone plugins locally and create volume mounts (existing behavior)
- When `mountPlugins = false`: Still analyze plugin metadata but create Plugin objects without volumes for Docker shallow cloning

### 3. DockerData Model Enhancement (`src/Model/DockerData.php`)

- Added `pluginsForDocker` property to store plugin information needed for Dockerfile template

```php
/**
 * @var array|null - plugin information for dockerfile cloning
 */
public ?array $pluginsForDocker = null;
```

### 4. Main Service Updates (`src/Service/Main.php`)

- Enhanced Docker data preparation to include plugin information
- Added logic to populate `pluginsForDocker` with repository URLs, branches, and Moodle paths
- Fixed port handling by using existing `getHostPort()` method instead of storing in DockerData

**Plugin Data Structure:**
```php
$pluginsForDocker[] = [
    'repo' => $recipePlugin->repo,
    'branch' => $recipePlugin->branch,
    'path' => $pluginInfo->path
];
```

### 5. Dockerfile Template Updates (`templates/docker/main.dockerfile.twig`)

- Added shallow clone commands for plugins after Moodle installation
- Uses `--depth 1` for efficient shallow clones
- Integrates seamlessly with existing Moodle installation process

```twig
# Clone plugins using shallow clones
{% if pluginsForDocker %}
{% for plugin in pluginsForDocker %}
RUN git clone {{ plugin.repo }} --branch {{ plugin.branch }} --depth 1 $MOODLE_PATH{{ plugin.path }}
{% endfor %}
{% endif %}
```

## Backward Compatibility

- Existing recipes using `cloneRepoPlugins: true` will continue to work
- Deprecation warning guides users to migrate to `mountPlugins`
- All existing functionality preserved

## Usage Examples

### New Recommended Approach

```json
{
  "mountPlugins": true,  // For local development with volume mounts
  "plugins": [
    "https://github.com/user/plugin1.git~main",
    {
      "repo": "https://github.com/user/plugin2.git",
      "branch": "develop"
    }
  ]
}
```

### Volume Mounts Disabled (Docker-only plugins)

```json
{
  "mountPlugins": false,  // Plugins only in Docker, no local volumes
  "plugins": [
    "https://github.com/user/plugin1.git~main"
  ]
}
```

## Benefits

1. **Always Available**: Plugins are always present in Docker image regardless of volume mount settings
2. **Efficient**: Uses shallow clones (`--depth 1`) for minimal download time and disk usage
3. **Flexible**: Supports both local development (with volumes) and Docker-only deployment
4. **Backward Compatible**: Existing recipes continue to work without changes
5. **Clear Migration Path**: Deprecation warnings guide users to new property names

## Testing Recommendations

- Test with `mountPlugins: true` to ensure volume mounting still works
- Test with `mountPlugins: false` to verify Docker-only plugin installation
- Test deprecated `cloneRepoPlugins: true` to ensure warning appears and functionality works
- Verify plugins are correctly installed in Docker containers
- Test with various plugin types and repository structures

## Related Files Modified

- `src/Model/Recipe.php` - Added new property, deprecated old one
- `src/Service/Plugins.php` - Enhanced plugin processing logic
- `src/Model/DockerData.php` - Added plugin data storage
- `src/Service/Main.php` - Enhanced Docker data preparation
- `templates/docker/main.dockerfile.twig` - Added shallow clone commands
