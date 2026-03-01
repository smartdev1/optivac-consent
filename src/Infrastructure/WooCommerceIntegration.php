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
        add_action('wp_login', [$this, 'flushOnLogin'], 10, 2);

        add_action('user_register', [$this, 'flushOnRegister'], 10, 1);

        add_action(
            'woocommerce_after_checkout_billing_form',
            [$this, 'renderConsentFields']
        );

        add_action(
            'woocommerce_checkout_update_order_meta',
            [$this, 'handleConsent']
        );
    }

    public function flushOnLogin(string $userLogin, \WP_User $user): void
    {
        if (!empty($user->user_email)) {
            $this->manager->flushPending($user->user_email);
        }
    }

    public function flushOnRegister(int $userId): void
    {
        $user = get_userdata($userId);

        if ($user && !empty($user->user_email)) {
            $this->manager->flushPending($user->user_email);
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

        $newsletter = !empty($_POST['optivac_newsletter']);
        $offers     = !empty($_POST['optivac_offers']);

        $this->manager->validate($email, $newsletter, $offers, 'v1');

        $this->manager->flushPending($email);

        update_post_meta($orderId, '_optivac_newsletter', $newsletter ? '1' : '0');
        update_post_meta($orderId, '_optivac_offers', $offers ? '1' : '0');
        update_post_meta($orderId, '_optivac_consent_source', Constants::SOURCE_WORDPRESS);
        update_post_meta($orderId, '_optivac_consent_timestamp', current_time('mysql'));

        $this->auditLogger->log($email, $newsletter, $offers, Constants::SOURCE_WORDPRESS);

        if (is_user_logged_in()) {
            $userId = get_current_user_id();
            update_user_meta($userId, '_optivac_newsletter', $newsletter);
            update_user_meta($userId, '_optivac_offers', $offers);
            update_user_meta($userId, '_optivac_last_consent_at', current_time('mysql'));
        }
    }
}