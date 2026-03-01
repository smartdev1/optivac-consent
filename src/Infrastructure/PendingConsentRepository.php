<?php

namespace OptivacConsent\Infrastructure;

class PendingConsentRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'optivac_consent_pending';
    }

    public function save(
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion
    ): void {
        global $wpdb;

        $wpdb->replace($this->table, [
            'email'          => $email,
            'newsletter'     => $newsletter ? 1 : 0,
            'offers'         => $offers ? 1 : 0,
            'policy_version' => $policyVersion,
            'created_at'     => current_time('mysql'),
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE email = %s",
                $email
            ),
            ARRAY_A
        ) ?: null;
    }

    public function delete(string $email): void
    {
        global $wpdb;

        $wpdb->delete($this->table, ['email' => $email]);
    }

    public function count(): int
    {
        global $wpdb;

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }
}