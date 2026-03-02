<?php

namespace OptivacConsent\Infrastructure;

use OptivacConsent\Domain\ConsentManager;
use OptivacConsent\Support\Constants;
use OptivacConsent\Support\Helpers;

class WooCommerceIntegration
{
    private ConsentManager $manager;
    private AuditLogger    $auditLogger;
    private Logger         $logger;

    public function __construct(ConsentManager $manager, AuditLogger $auditLogger, Logger $logger)
    {
        $this->manager     = $manager;
        $this->auditLogger = $auditLogger;
        $this->logger      = $logger;
    }

    public function register(): void
    {
        add_action('wp_login',      [$this, 'flushOnLogin'],    10, 2);
        add_action('user_register', [$this, 'flushOnRegister'], 10, 1);

        add_action('woocommerce_created_customer',             [$this, 'handleRegistration'],    10, 3);
        add_action('woocommerce_after_checkout_billing_form',  [$this, 'renderConsentFields']);
        add_action('woocommerce_checkout_update_order_meta',   [$this, 'handleConsent']);
    }

    public function flushOnLogin(string $userLogin, \WP_User $user): void
    {
        if (!empty($user->user_email)) {
            $this->manager->flushPending($user->user_email);
        }
    }

    public function flushOnRegister(int $userId): void
    {
        // Évite le double traitement si handleRegistration a déjà envoyé à l'API
        if (did_action('woocommerce_created_customer')) {
            return;
        }

        $user = get_userdata($userId);

        if ($user && !empty($user->user_email)) {
            $this->manager->flushPending($user->user_email);
        }
    }

    public function handleRegistration(int $userId, array $newCustomerData, bool $passwordGenerated): void
    {
        $user = get_userdata($userId);

        if (!$user || empty($user->user_email)) {
            $this->logger->warning('handleRegistration: no user found', ['userId' => $userId]);
            return;
        }

        $email      = $user->user_email;
        $newsletter = !empty($_POST['consentement_marketing']);
        $offers     = !empty($_POST['consentement_marketing']);
        $firstName  = sanitize_text_field($_POST['billing_first_name'] ?? $_POST['first_name'] ?? '');
        $lastName   = sanitize_text_field($_POST['billing_last_name']  ?? $_POST['last_name']  ?? '');

        $this->logger->info('handleRegistration: processing consent', [
            'email'      => $email,
            'newsletter' => $newsletter,
            'offers'     => $offers,
        ]);

        $success = $this->manager->validate($email, $newsletter, $offers, 'v1', $firstName, $lastName);

        if ($success) {
            $this->auditLogger->log($email, $newsletter, $offers, Constants::SOURCE_WORDPRESS);
            update_user_meta($userId, '_optivac_newsletter',      $newsletter);
            update_user_meta($userId, '_optivac_offers',          $offers);
            update_user_meta($userId, '_optivac_last_consent_at', current_time('mysql'));
        } else {
            $this->logger->warning('handleRegistration: consent failed or stored pending', [
                'email' => $email,
            ]);
        }
    }

    public function renderConsentFields(): void
    {
        ?>
        <div class="optivac-consent-checkout" style="margin:16px 0;">
            <h3 style="margin-bottom:8px;">Préférences de communication</h3>
            <p>
                <label>
                    <input type="checkbox" name="optivac_newsletter" value="1" />
                    Je souhaite recevoir la newsletter
                </label>
            </p>
            <p>
                <label>
                    <input type="checkbox" name="optivac_offers" value="1" />
                    Je souhaite recevoir les offres promotionnelles
                </label>
            </p>
        </div>
        <?php
    }

    public function handleConsent(int $orderId): void
    {
        $email = sanitize_email($_POST['billing_email'] ?? '');

        if (!$email) {
            return;
        }

        $newsletter             = !empty($_POST['optivac_newsletter']);
        $offers                 = !empty($_POST['optivac_offers']);
        [$firstName, $lastName] = Helpers::resolveNameFromPost();

        $this->manager->validate($email, $newsletter, $offers, 'v1', $firstName, $lastName);
        $this->manager->flushPending($email);

        update_post_meta($orderId, '_optivac_newsletter',        $newsletter ? '1' : '0');
        update_post_meta($orderId, '_optivac_offers',            $offers ? '1' : '0');
        update_post_meta($orderId, '_optivac_consent_source',    Constants::SOURCE_WORDPRESS);
        update_post_meta($orderId, '_optivac_consent_timestamp', current_time('mysql'));

        $this->auditLogger->log($email, $newsletter, $offers, Constants::SOURCE_WORDPRESS);

        if (is_user_logged_in()) {
            $userId = get_current_user_id();
            update_user_meta($userId, '_optivac_newsletter',      $newsletter);
            update_user_meta($userId, '_optivac_offers',          $offers);
            update_user_meta($userId, '_optivac_last_consent_at', current_time('mysql'));
        }
    }
}