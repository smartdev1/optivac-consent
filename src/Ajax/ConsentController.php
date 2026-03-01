<?php

namespace OptivacConsent\Ajax;

use OptivacConsent\Domain\ConsentManager;
use OptivacConsent\Infrastructure\EmailResolver;
use OptivacConsent\Infrastructure\PendingConsentRepository;
use OptivacConsent\Support\Constants;

class ConsentController
{
    private ConsentManager           $manager;
    private EmailResolver            $emailResolver;
    private PendingConsentRepository $pending;

    public function __construct(
        ConsentManager $manager,
        EmailResolver $emailResolver,
        PendingConsentRepository $pending
    ) {
        $this->manager       = $manager;
        $this->emailResolver = $emailResolver;
        $this->pending       = $pending;
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
            !wp_verify_nonce($_POST['nonce'], Constants::NONCE_ACTION)
        ) {
            wp_send_json_error(['message' => 'Invalid nonce'], 403);
        }
    }

    public function status(): void
    {
        $this->verifyNonce();

        $email = sanitize_email($_POST['email'] ?? '');

        if (!$email) {
            $email = $this->emailResolver->resolve();
        }

        if (!$email) {
            wp_send_json_error(['message' => 'Email required'], 400);
        }

        // Consentement déjà en attente → pas besoin d'afficher la modale
        if ($this->pending->findByEmail($email)) {
            wp_send_json_success([
                'newsletter'      => ['needsConsent' => false, 'granted' => false, 'source' => '', 'policyVersion' => '', 'modalConsentVisible' => false],
                'offers'          => ['needsConsent' => false, 'granted' => false, 'source' => '', 'policyVersion' => '', 'modalConsentVisible' => false],
                'needsAnyConsent' => false,
                'pending'         => true,
            ]);
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

        if (!$email) {
            $email = $this->emailResolver->resolve();
        }

        if (!$email || !$policy) {
            wp_send_json_error(['message' => 'Invalid data'], 400);
        }

        $success = $this->manager->validate($email, $newsletter, $offers, $policy);

        $success
            ? wp_send_json_success(['message' => 'Consent saved'])
            : wp_send_json_error(['message' => 'Could not save consent'], 500);
    }
}