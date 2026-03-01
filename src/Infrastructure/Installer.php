<?php

namespace OptivacConsent\Infrastructure;

class Installer
{
    public static function install(): void
    {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}optivac_consent_logs (
            id          BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email       VARCHAR(255)        NOT NULL,
            newsletter  TINYINT(1)          NOT NULL DEFAULT 0,
            offers      TINYINT(1)          NOT NULL DEFAULT 0,
            source      VARCHAR(50)         NOT NULL DEFAULT '',
            ip_address  VARCHAR(45)         NOT NULL DEFAULT '',
            created_at  DATETIME            NOT NULL,
            PRIMARY KEY (id),
            KEY email (email)
        ) {$charset};");

        dbDelta("CREATE TABLE IF NOT EXISTS {$wpdb->prefix}optivac_consent_pending (
            id             BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email          VARCHAR(255)        NOT NULL,
            newsletter     TINYINT(1)          NOT NULL DEFAULT 0,
            offers         TINYINT(1)          NOT NULL DEFAULT 0,
            policy_version VARCHAR(50)         NOT NULL DEFAULT 'v1',
            created_at     DATETIME            NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email)
        ) {$charset};");

        update_option('optivac_consent_db_version', '1.0');
    }

    public static function deactivate(): void
    {
        // On deactivation we keep the data intentionally
    }

    public static function uninstall(): void
    {
        global $wpdb;

        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}optivac_consent_logs");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}optivac_consent_pending");

        delete_option('optivac_api_url');
        delete_option('optivac_api_key');
        delete_option('optivac_auth_type');
        delete_option('optivac_api_username');
        delete_option('optivac_api_password');
        delete_option('optivac_consent_db_version');
    }
}