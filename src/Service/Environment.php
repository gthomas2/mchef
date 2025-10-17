<?php

namespace App\Service;

class Environment extends AbstractService {

    final public static function instance(): Environment {
        return self::setup_singleton();
    }

    /**
     * Get an environment variable value
     * 
     * @param string $name Environment variable name
     * @param string|null $default Default value if not found
     * @return string|null
     */
    public function get(string $name, ?string $default = null): ?string {
        $value = getenv($name);
        return $value !== false ? $value : $default;
    }

    /**
     * Check if an environment variable is set (and not empty)
     * 
     * @param string $name Environment variable name
     * @return bool
     */
    public function has(string $name): bool {
        $value = getenv($name);
        return $value !== false && $value !== '';
    }

    /**
     * Get multiple environment variables as an associative array
     * 
     * @param array $names Array of environment variable names
     * @return array
     */
    public function getMultiple(array $names): array {
        $result = [];
        foreach ($names as $name) {
            $result[$name] = $this->get($name);
        }
        return $result;
    }
}
