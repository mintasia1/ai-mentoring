<?php
/**
 * Logger Class with Log Rotation
 * CUHK Law E-Mentoring Platform
 */

class Logger {
    private static string $logDir = __DIR__ . '/../log/';
    private static string $logFile = 'php_error.log';
    private static int $maxFileSize = 5242880; // 5MB in bytes
    
    // Log level hierarchy (lower number = higher priority)
    private static array $logLevels = [
        'ERROR' => 1,
        'WARNING' => 2,
        'INFO' => 3,
        'DEBUG' => 4
    ];
    
    /**
     * Check if a log level should be logged based on configured LOG_LEVEL
     */
    private static function shouldLog(string $level): bool {
        $configuredLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'WARNING';
        
        // If configured level is not valid, default to WARNING
        if (!isset(self::$logLevels[$configuredLevel])) {
            $configuredLevel = 'WARNING';
        }
        
        // Log if the message level is equal to or higher priority than configured level
        return self::$logLevels[$level] <= self::$logLevels[$configuredLevel];
    }
    
    /**
     * Log an error message
     */
    public static function error(string $message, array $context = []): void {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log a warning message
     */
    public static function warning(string $message, array $context = []): void {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log an info message
     */
    public static function info(string $message, array $context = []): void {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log a debug message
     */
    public static function debug(string $message, array $context = []): void {
        self::log('DEBUG', $message, $context);
    }
    
    /**
     * Main logging function with rotation
     */
    private static function log(string $level, string $message, array $context = []): void {
        try {
            // Check if this log level should be logged
            if (!self::shouldLog($level)) {
                return;
            }
            
            // Ensure log directory exists
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
            
            $logFilePath = self::$logDir . self::$logFile;
            
            // Check if rotation is needed
            if (file_exists($logFilePath) && filesize($logFilePath) >= self::$maxFileSize) {
                self::rotateLog($logFilePath);
            }
            
            // Format log entry
            $timestamp = date('Y-m-d H:i:s');
            $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
            $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
            
            // Write to log file
            file_put_contents($logFilePath, $logEntry, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // Fallback to error_log if our logging fails
            error_log("Logger failed: " . $e->getMessage());
        }
    }
    
    /**
     * Rotate log file when it reaches max size
     */
    private static function rotateLog(string $logFilePath): void {
        $date = date('Y-m-d_His');
        $rotatedFileName = self::$logDir . 'php_error_' . $date . '.log';
        
        // Rename current log file
        rename($logFilePath, $rotatedFileName);
    }
    
    /**
     * Log exception
     */
    public static function logException(Throwable $e, string $context = ''): void {
        $message = "Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine();
        if ($context) {
            $message = $context . " - " . $message;
        }
        self::error($message, [
            'trace' => $e->getTraceAsString()
        ]);
    }
}
