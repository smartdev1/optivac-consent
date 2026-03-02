<?php

namespace OptivacConsent\Infrastructure;

class Logger
{
    private const PREFIX = '[OptivacConsent]';

    private const LEVELS = [
        'DEBUG'   => 0,
        'INFO'    => 1,
        'WARNING' => 2,
        'ERROR'   => 3,
    ];

    private string $minLevel;

    public function __construct()
    {
        // En production, passer à 'INFO' via constante dans wp-config.php
        // define('OPTIVAC_LOG_LEVEL', 'INFO');
        $this->minLevel = defined('OPTIVAC_LOG_LEVEL') ? OPTIVAC_LOG_LEVEL : 'DEBUG';
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $entry = sprintf(
            '%s [%s] [%s] %s%s',
            self::PREFIX,
            current_time('Y-m-d H:i:s'),
            $level,
            $message,
            empty($context) ? '' : ' | ' . $this->formatContext($context)
        );

        error_log($entry);
    }

    private function shouldLog(string $level): bool
    {
        $min     = self::LEVELS[$this->minLevel] ?? 0;
        $current = self::LEVELS[$level] ?? 0;

        return $current >= $min;
    }

    private function formatContext(array $context): string
    {
        // Masque les données sensibles avant écriture
        foreach (['apiKey', 'api_key', 'password', 'Authorization'] as $sensitive) {
            if (isset($context[$sensitive])) {
                $context[$sensitive] = '***';
            }
        }

        return wp_json_encode($context, JSON_UNESCAPED_UNICODE);
    }
}