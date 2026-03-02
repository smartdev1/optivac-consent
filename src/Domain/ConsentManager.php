<?php

namespace OptivacConsent\Domain;

use OptivacConsent\API\ConsentApi;
use OptivacConsent\Domain\ValueObject\ConsentStatus;
use OptivacConsent\Http\ApiException;
use OptivacConsent\Infrastructure\Cache;
use OptivacConsent\Infrastructure\Logger;
use OptivacConsent\Infrastructure\PendingConsentRepository;

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
            $this->logger->debug('getStatus: cache hit', ['email' => $email]);
            return new ConsentStatus($cached);
        }

        $this->logger->debug('getStatus: cache miss, calling API', ['email' => $email]);

        try {
            $response = $this->api->getAllStatus($email);
            $data     = $response['body'] ?? [];

            $this->cache->set($email, $data);
            $this->logger->info('getStatus: API response cached', ['email' => $email]);

            return new ConsentStatus($data);
        } catch (ApiException $e) {
            $this->logger->error('getStatus: API call failed', [
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
        $this->logger->info('validate: start', [
            'email'      => $email,
            'newsletter' => $newsletter,
            'offers'     => $offers,
            'policy'     => $policyVersion,
        ]);

        try {
            return $this->sendToApi($email, $newsletter, $offers, $policyVersion);
        } catch (ApiException $e) {
            $this->logger->error('validate: API failed, storing pending', [
                'email' => $email,
                'error' => $e->getMessage(),
                'code'  => $e->getStatusCode(),
            ]);

            $this->storePending($email, $newsletter, $offers, $policyVersion);

            return true;
        }
    }

    public function flushPending(string $email): void
    {
        $consent = $this->pending->findByEmail($email);

        if (!$consent) {
            $this->logger->debug('flushPending: nothing to flush', ['email' => $email]);
            return;
        }

        $this->logger->info('flushPending: found pending consent, retrying API', ['email' => $email]);

        try {
            $success = $this->sendToApi(
                $email,
                (bool) $consent['newsletter'],
                (bool) $consent['offers'],
                $consent['policy_version']
            );

            if ($success) {
                $this->pending->delete($email);
                $this->logger->info('flushPending: consent sent and cleared', ['email' => $email]);
            } else {
                $this->logger->warning('flushPending: API returned non-success status', ['email' => $email]);
            }
        } catch (ApiException $e) {
            $this->logger->warning('flushPending: API still unavailable', [
                'email' => $email,
                'error' => $e->getMessage(),
                'code'  => $e->getStatusCode(),
            ]);
        }
    }

    private function sendToApi(
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion
    ): bool {
        $payload = [
            'email'              => $email,
            'newsletter_consent' => $newsletter,
            'offers_consent'     => $offers,
            'policyVersion'      => $policyVersion,
        ];

        $this->logger->debug('sendToApi: payload', $payload);

        $response = $this->api->validate($payload);

        $success = in_array($response['status'], [200, 201], true);

        $this->logger->info('sendToApi: response', [
            'status'  => $response['status'],
            'success' => $success,
            'email'   => $email,
        ]);

        if ($success) {
            $this->cache->delete($email);
            $this->logger->debug('sendToApi: cache invalidated', ['email' => $email]);
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

        $this->logger->info('storePending: consent saved locally', [
            'email'      => $email,
            'newsletter' => $newsletter,
            'offers'     => $offers,
        ]);
    }
}