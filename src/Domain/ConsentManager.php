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
        string $policyVersion
    ): bool {
        $accountId = $this->resolveAccountId($email);

        // Pas d'accountId connu → stockage local en attente
        if ($accountId === 0) {
            $this->storePending($email, $newsletter, $offers, $policyVersion);
            return true; 
        }

        return $this->sendToApi($accountId, $email, $newsletter, $offers, $policyVersion);
    }

    /**
     * Rejoue les consentements en attente pour un email
     * dont on vient de résoudre l'accountId (ex: après une commande WooCommerce)
     */
    public function flushPending(string $email): void
    {
        $consent = $this->pending->findByEmail($email);

        if (!$consent) {
            return;
        }

        $accountId = $this->resolveAccountId($email);

        if ($accountId === 0) {
            $this->logger->warning('flushPending: accountId still unresolved', ['email' => $email]);
            return;
        }

        $success = $this->sendToApi(
            $accountId,
            $email,
            (bool) $consent['newsletter'],
            (bool) $consent['offers'],
            $consent['policy_version']
        );

        if ($success) {
            $this->pending->delete($email);
            $this->logger->info('flushPending: consent sent and cleared', ['email' => $email]);
        }
    }

    private function sendToApi(
        int $accountId,
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion
    ): bool {
        $payload = [
            'accountId'          => $accountId,
            'newsletter_consent' => $newsletter,
            'offers_consent'     => $offers,
            'policyVersion'      => $policyVersion,
        ];

        $this->logger->info('validate payload', $payload);

        try {
            $response = $this->api->validate($payload);

            $this->logger->info('validate response', [
                'status' => $response['status'],
                'body'   => $response['body'],
            ]);

            $success = in_array($response['status'], [200, 201], true);

            if ($success) {
                $this->cache->delete($email);
            }

            return $success;
        } catch (ApiException $e) {
            $this->logger->error('validate failed', [
                'email'   => $email,
                'payload' => $payload,
                'error'   => $e->getMessage(),
                'code'    => $e->getStatusCode(),
            ]);

            return false;
        }
    }

    private function storePending(
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion
    ): void {
        $this->pending->save($email, $newsletter, $offers, $policyVersion);

        $this->logger->info('consent stored locally (no accountId)', [
            'email'      => $email,
            'newsletter' => $newsletter,
            'offers'     => $offers,
        ]);
    }

    private function resolveAccountId(string $email): int
    {
        $user = get_user_by('email', $email);

        return $user ? $user->ID : 0;
    }
}