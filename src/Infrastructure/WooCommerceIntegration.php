<?php

namespace OptivacConsent\Infrastructure;

use OptivacConsent\Domain\ConsentManager;
use OptivacConsent\Support\Constants;

class WooCommerceIntegration
{
    private ConsentManager $manager;
    private AuditLogger    $auditLogger;

    public function __construct(ConsentManager $manager, AuditLogger $auditLogger)
    {
        $this->manager     = $manager;
        $this->auditLogger = $auditLogger;
    }

    public function register(): void
    {
        add_action('woocommerce_created_customer', [$this, 'handleRegistration'], 10, 3);
        add_action('wp_login', [$this, 'flushOnLogin'], 10, 2);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'handleConsent']);
    }

    
    public function handleRegistration(int $customerId, array $newCustomerData, bool $passwordGenerated): void
    {
        $email   = sanitize_email($newCustomerData['user_email'] ?? '');
        $consent = !empty($_POST['consentement_marketing']);

        if (!$email) {
            return;
        }

        $this->manager->validate($email, $consent, $consent, 'v1');
        $this->auditLogger->log($email, $consent, $consent, Constants::SOURCE_WORDPRESS);

        update_user_meta($customerId, '_optivac_newsletter', $consent ? '1' : '0');
        update_user_meta($customerId, '_optivac_offers', $consent ? '1' : '0');
        update_user_meta($customerId, '_optivac_last_consent_at', current_time('mysql'));
    }

    public function flushOnLogin(string $userLogin, \WP_User $user): void
    {
        if (!empty($user->user_email)) {
            $this->manager->flushPending($user->user_email);
        }
    }

    
    public function handleConsent(int $orderId): void
    {
        $email = sanitize_email($_POST['billing_email'] ?? '');

        if (!$email) {
            return;
        }

        $consent = !empty($_POST['ws_opt_in']);

        $this->manager->validate($email, $consent, $consent, 'v1');
        $this->manager->flushPending($email);

        update_post_meta($orderId, '_optivac_newsletter', $consent ? '1' : '0');
        update_post_meta($orderId, '_optivac_offers', $consent ? '1' : '0');
        update_post_meta($orderId, '_optivac_consent_source', Constants::SOURCE_WORDPRESS);
        update_post_meta($orderId, '_optivac_consent_timestamp', current_time('mysql'));

        $this->auditLogger->log($email, $consent, $consent, Constants::SOURCE_WORDPRESS);

        if (is_user_logged_in()) {
            $userId = get_current_user_id();
            update_user_meta($userId, '_optivac_newsletter', $consent ? '1' : '0');
            update_user_meta($userId, '_optivac_offers', $consent ? '1' : '0');
            update_user_meta($userId, '_optivac_last_consent_at', current_time('mysql'));
        }
    }
}