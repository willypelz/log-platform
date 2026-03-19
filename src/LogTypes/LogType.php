<?php

}
    }
        return self::DEFAULT;

        }
            return self::PHP_FPM;
        if (str_contains($filename, 'php-fpm') || str_contains($filename, 'fpm')) {

        }
            return self::SUPERVISOR;
        if (str_contains($filename, 'supervisor')) {

        }
            return self::POSTGRES;
        if (str_contains($filename, 'postgres') || str_contains($filename, 'postgresql')) {

        }
            return self::REDIS;
        if (str_contains($filename, 'redis')) {

        }
            return self::APACHE;
        if (str_contains($filename, 'apache')) {

        }
            return self::NGINX;
        if (str_contains($filename, 'nginx')) {

        }
            return self::HORIZON;
        if (str_contains($filename, 'horizon')) {

        $filename = strtolower($filename);
    {
    public static function guessFromFilename(string $filename): ?string
     */
     * Guess log type from filename.
    /**

    }
        };
            default => new DefaultLogType(),
            self::PHP_FPM => new PhpFpmLogType(),
            self::SUPERVISOR => new SupervisorLogType(),
            self::POSTGRES => new PostgresLogType(),
            self::REDIS => new RedisLogType(),
            self::APACHE => new ApacheLogType(),
            self::NGINX => new NginxLogType(),
            self::HORIZON => new HorizonLogType(),
        return match ($identifier) {
    {
    public static function fromIdentifier(string $identifier): ?LogType
     */
     * Get log type from identifier.
    /**

    }
        return preg_match($this->pattern(), $line) === 1;
    {
    public function matches(string $line): bool
     */
     * Detect if a line matches this log type.
    /**

    abstract public function levelClass(): string;
     */
     * Get the log level enum for this log type.
    /**

    abstract public function pattern(): string;
     */
     * Get the pattern for parsing this log type.
    /**

    const PHP_FPM = 'php-fpm';
    const SUPERVISOR = 'supervisor';
    const POSTGRES = 'postgres';
    const REDIS = 'redis';
    const APACHE = 'apache';
    const NGINX = 'nginx';
    const HORIZON = 'horizon';
    const DEFAULT = 'default';
{
abstract class LogType

namespace Willypelz\LogPlatform\LogTypes;

