<?php

namespace OptivacConsent\Infrastructure;

use OptivacConsent\Domain\ConsentManager;

class WooCommerceIntegration
{
    private ConsentManager $manager;

    public function __construct(ConsentManager $manager)
    {
        $this->manager = $manager;
    }

    public function register(): void
    {
        add_action(
            'woocommerce_after_checkout_billing_form',
            [$this, 'renderConsentFields']
        );

        add_action(
            'woocommerce_checkout_update_order_meta',
            [$this, 'handleConsent']
        );
    }

    public function renderConsentFields(): void
    {
        echo '<div class="optivac-consent-checkout">
            <label>
                <input type="checkbox" name="optivac_newsletter" />
                Je souhaite recevoir la newsletter
            </label>
            <label>
                <input type="checkbox" name="optivac_offers" />
                Je souhaite recevoir les offres promotionnelles
            </label>
        </div>';
    }

    public function handleConsent(int $orderId): void
    {
        $email = sanitize_email($_POST['billing_email'] ?? '');

        if (!$email) {
            return;
        }

        $newsletter = isset($_POST['optivac_newsletter']);
        $offers     = isset($_POST['optivac_offers']);

        // 1️⃣ Appel API
        $this->manager->validate(
            $email,
            $newsletter,
            $offers,
            'v1'
        );

        // 2️⃣ Trace locale dans la commande
        update_post_meta($orderId, '_optivac_newsletter', $newsletter ? '1' : '0');
        update_post_meta($orderId, '_optivac_offers', $offers ? '1' : '0');
        update_post_meta($orderId, '_optivac_consent_source', 'WORDPRESS');
        update_post_meta($orderId, '_optivac_consent_timestamp', current_time('mysql'));

        // 3️⃣ Si utilisateur connecté → user meta
        if (is_user_logged_in()) {
            $userId = get_current_user_id();

            update_user_meta($userId, '_optivac_newsletter', $newsletter);
            update_user_meta($userId, '_optivac_offers', $offers);
            update_user_meta($userId, '_optivac_last_consent_at', current_time('mysql'));
        }
    }
}