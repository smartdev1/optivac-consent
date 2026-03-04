<?php

namespace OptivacConsent\Domain;

use OptivacConsent\API\ConsentApi;
use OptivacConsent\Domain\ValueObject\ConsentStatus;
use OptivacConsent\Http\ApiException;
use OptivacConsent\Infrastructure\Cache;
use OptivacConsent\Infrastructure\Logger;
use OptivacConsent\Infrastructure\PendingConsentRepository;
use OptivacConsent\Support\Constants;

class ConsentManager
{
    private ConsentApi               $api;
    private Cache                    $cache;
    private Logger                   $logger;
    private PendingConsentRepository $pending;

    public function __construct(
        ConsentApi $api,
        Cache $cache,
        Logger $logger,
        PendingConsentRepository $pending
    ) {
        $this->api     = $api;
        $this->cache   = $cache;
        $this->logger  = $logger;
        $this->pending = $pending;
    }

    public function getStatus(string $email): ConsentStatus
    {
        $cached = $this->cache->get($email);

        if ($cached !== null) {
            return new ConsentStatus($cached);
        }

        try {
            $response = $this->api->getAllStatus($email);
            $data     = $response['body'] ?? [];

            $this->cache->set($email, $data);

            return new ConsentStatus($data);
        } catch (ApiException $e) {
            $this->logger->error('getStatus failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'code'  => $e->getStatusCode(),
            ]);

            return new ConsentStatus([]);
        }
    }

    public function validate(
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion,
        string $firstname = '',
        string $lastname  = ''
    ): bool {
        try {
            return $this->sendToApi(
                $email,
                $newsletter,
                $offers,
                $policyVersion,
                Constants::SOURCE_WORDPRESS,
                $firstname,
                $lastname
            );
        } catch (ApiException $e) {
            $this->logger->error('validate failed - storing pending', [
                'email' => $email,
                'error' => $e->getMessage(),
                'code'  => $e->getStatusCode(),
            ]);
            $this->storePending($email, $newsletter, $offers, $policyVersion);

            return true;
        }
    }

    /**
     * Révoque un ou plusieurs consentements.
     * Appelé depuis BrevoWebhookController quand Brevo notifie une désinscription.
     *
     * $newsletter / $offers : false = révoquer, null = conserver l'état actuel
     */
    public function revoke(string $email, ?bool $newsletter, ?bool $offers): bool
    {
        try {
            $current = $this->getStatus($email);

            $newsletterValue = $newsletter ?? $current->newsletterGranted();
            $offersValue     = $offers     ?? $current->offersGranted();

            $this->logger->info('revoke initiated', [
                'email'      => $email,
                'newsletter' => $newsletterValue,
                'offers'     => $offersValue,
            ]);

            return $this->sendToApi(
                $email,
                $newsletterValue,
                $offersValue,
                'v1',
                Constants::SOURCE_BREVO
            );
        } catch (ApiException $e) {
            $this->logger->error('revoke failed', [
                'email' => $email,
                'error' => $e->getMessage(),
                'code'  => $e->getStatusCode(),
            ]);

            return false;
        }
    }

    public function flushPending(string $email): void
    {
        $consent = $this->pending->findByEmail($email);

        if (!$consent) {
            return;
        }

        try {
            $success = $this->sendToApi(
                $email,
                (bool) $consent['newsletter'],
                (bool) $consent['offers'],
                $consent['policy_version'],
                Constants::SOURCE_WORDPRESS
            );

            if ($success) {
                $this->pending->delete($email);
                $this->logger->info('flushPending: consent sent and cleared', ['email' => $email]);
            }
        } catch (ApiException $e) {
            $this->logger->warning('flushPending: API still unavailable', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function sendToApi(
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion,
        string $source,
        string $firstname = '',
        string $lastname  = ''
    ): bool {
        $payload = [
            'email'              => $email,
            'newsletter_consent' => $newsletter,
            'offers_consent'     => $offers,
            'policyVersion'      => $policyVersion,
            'source'             => $source,
        ];

        if (!empty($firstname)) {
            $payload['firstname'] = $firstname;
        }

        if (!empty($lastname)) {
            $payload['lastname'] = $lastname;
        }

        $this->logger->info('sendToApi payload', $payload);

        $response = $this->api->validate($payload);

        $this->logger->info('sendToApi response', [
            'status' => $response['status'],
            'body'   => $response['body'],
        ]);

        $success = in_array($response['status'], [200, 201], true);

        if ($success) {
            $this->cache->delete($email);
        }

        return $success;
    }

    private function storePending(
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion
    ): void {
        $this->pending->save($email, $newsletter, $offers, $policyVersion);

        $this->logger->info('consent stored locally (pending)', [
            'email'      => $email,
            'newsletter' => $newsletter,
            'offers'     => $offers,
        ]);
    }
}