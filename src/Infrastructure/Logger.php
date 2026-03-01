<?php

namespace OptivacConsent\Infrastructure;

class Logger
{
    private string $prefix = '[OptivacConsent]';

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    private function log(string $level, string $message, array $context): void
    {
        $entry = sprintf(
            '%s [%s] %s',
            $this->prefix,
            $level,
            $message
        );

        if (!empty($context)) {
            $entry .= ' | ' . wp_json_encode($context);
        }

        error_log($entry);
    }
}
