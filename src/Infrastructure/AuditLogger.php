<?php

namespace OptivacConsent\Infrastructure;

class AuditLogger
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'optivac_consent_logs';
    }

    public function log(
        string $email,
        bool $newsletter,
        bool $offers,
        string $source
    ): void {
        global $wpdb;

        $wpdb->insert($this->table, [
            'email'      => $email,
            'newsletter' => $newsletter ? 1 : 0,
            'offers'     => $offers ? 1 : 0,
            'source'     => $source,
            'ip_address' => $this->resolveIp(),
            'created_at' => current_time('mysql'),
        ]);
    }

    private function resolveIp(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return sanitize_text_field(explode(',', $_SERVER[$key])[0]);
            }
        }

        return '';
    }
}
