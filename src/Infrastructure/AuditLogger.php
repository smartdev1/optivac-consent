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
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'created_at' => current_time('mysql'),
        ]);
    }
}
