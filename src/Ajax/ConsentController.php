<?php

namespace OptivacConsent\Ajax;

use OptivacConsent\Domain\ConsentManager;

class ConsentController
{
    private ConsentManager $manager;

    public function __construct(ConsentManager $manager)
    {
        $this->manager = $manager;
    }

    public function register(): void
    {
        add_action('wp_ajax_optivac_status', [$this, 'status']);
        add_action('wp_ajax_nopriv_optivac_status', [$this, 'status']);

        add_action('wp_ajax_optivac_validate', [$this, 'validate']);
        add_action('wp_ajax_nopriv_optivac_validate', [$this, 'validate']);
    }

    private function verifyNonce(): void
    {
        if (
            !isset($_POST['nonce']) ||
            !wp_verify_nonce($_POST['nonce'], 'optivac_consent_nonce')
        ) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }
    }

    public function status(): void
    {
        $this->verifyNonce();

        $email = sanitize_email($_POST['email'] ?? '');

        if (!$email) {
            wp_send_json_error(['message' => 'Invalid email']);
        }

        $status = $this->manager->getStatus($email);

        wp_send_json_success($status->toArray());
    }

    public function validate(): void
    {
        $this->verifyNonce();

        $email      = sanitize_email($_POST['email'] ?? '');
        $newsletter = filter_var($_POST['newsletter'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $offers     = filter_var($_POST['offers'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $policy     = sanitize_text_field($_POST['policyVersion'] ?? '');

        if (!$email || !$policy) {
            wp_send_json_error(['message' => 'Invalid data']);
        }

        $success = $this->manager->validate(
            $email,
            $newsletter,
            $offers,
            $policy
        );

        $success
            ? wp_send_json_success()
            : wp_send_json_error();
    }
}