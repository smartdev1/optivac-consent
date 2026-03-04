<?php

namespace OptivacConsent\API;

use OptivacConsent\Domain\ConsentManager;

class BrevoWebhookController
{
    private ConsentManager $manager;

    public function __construct(ConsentManager $manager)
    {
        $this->manager = $manager;
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'registerRoutes']);
    }

    public function registerRoutes(): void
    {
        register_rest_route('optivac/v1', '/brevo/unsubscribe', [
            'methods'             => 'POST',
            'callback'            => [$this, 'handleUnsubscribe'],
            'permission_callback' => [$this, 'verifyToken'],
        ]);
    }

    public function verifyToken(\WP_REST_Request $request): bool
    {
        $secret = get_option('optivac_brevo_webhook_secret', '');

        if (empty($secret)) {
            return true;
        }

        $token = sanitize_text_field($request->get_param('token') ?? '');

        return hash_equals($secret, $token);
    }

    public function handleUnsubscribe(\WP_REST_Request $request): \WP_REST_Response
    {
        $payload = $request->get_json_params();

        $email = sanitize_email($payload['email'] ?? '');
        $type  = strtoupper(sanitize_text_field($payload['type'] ?? 'NEWSLETTER'));

        if (!is_email($email)) {
            return new \WP_REST_Response(['message' => 'Invalid email'], 400);
        }

        if (!in_array($type, ['NEWSLETTER', 'OFFERS'], true)) {
            $type = 'NEWSLETTER';
        }

        $newsletter = ($type === 'NEWSLETTER') ? false : null;
        $offers     = ($type === 'OFFERS')     ? false : null;

        $revoked = $this->manager->revoke($email, $newsletter, $offers);

        if (!$revoked) {
            return new \WP_REST_Response(['message' => 'Revocation failed'], 500);
        }

        return new \WP_REST_Response([
            'message' => 'Consent revoked',
            'email'   => $email,
            'type'    => $type,
        ], 200);
    }
}