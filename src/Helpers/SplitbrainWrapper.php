<?php

namespace App\Helpers;

/**
 * Wrapper to suppress specific splitbrain/php-cli deprecation warnings
 * while preserving other error reporting.
 */
class SplitbrainWrapper {
    
    private static $previousErrorHandler = null;
    private static $suppressedWarnings = [
        'splitbrain\phpcli\Options::__construct(): Implicitly marking parameter $colors as nullable is deprecated',
        'splitbrain\phpcli\Exception::__construct(): Implicitly marking parameter $previous as nullable is deprecated'
    ];
    
    /**
     * Execute a callable while suppressing splitbrain deprecation warnings
     */
    public static function suppressDeprecationWarnings(callable $callback) {
        self::startSuppression();
        try {
            return $callback();
        } finally {
            self::stopSuppression();
        }
    }
    
    /**
     * Start suppressing splitbrain deprecation warnings
     */
    public static function startSuppression(): void {
        self::$previousErrorHandler = set_error_handler([self::class, 'errorHandler']);
    }
    
    /**
     * Stop suppressing and restore previous error handler
     */
    public static function stopSuppression(): void {
        if (self::$previousErrorHandler !== null) {
            set_error_handler(self::$previousErrorHandler);
            self::$previousErrorHandler = null;
        } else {
            restore_error_handler();
        }
    }
    
    /**
     * Custom error handler that filters out splitbrain deprecation warnings
     */
    public static function errorHandler(int $severity, string $message, string $file = '', int $line = 0): bool {
        // Check if this is a deprecation warning we want to suppress
        if ($severity === E_USER_DEPRECATED || $severity === E_DEPRECATED) {
            foreach (self::$suppressedWarnings as $suppressedPattern) {
                if (strpos($message, $suppressedPattern) !== false) {
                    // Suppress this specific warning
                    return true;
                }
            }
        }
        
        // Let other errors/warnings through to the previous handler
        if (self::$previousErrorHandler) {
            return call_user_func(self::$previousErrorHandler, $severity, $message, $file, $line);
        }
        
        // Use default error handling for non-suppressed warnings
        return false;
    }
}