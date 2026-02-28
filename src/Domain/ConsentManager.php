<?php

namespace OptivacConsent\Domain;

use OptivacConsent\API\ConsentApi;
use OptivacConsent\Domain\ValueObject\ConsentStatus;
use OptivacConsent\Http\ApiException;

class ConsentManager
{
    private ConsentApi $api;

    public function __construct(ConsentApi $api)
    {
        $this->api = $api;
    }

    public function getStatus(string $email): ConsentStatus
    {
        try {
            $response = $this->api->getAllStatus($email);
            return new ConsentStatus($response['body'] ?? []);
        } catch (ApiException $e) {
            return new ConsentStatus([]);
        }
    }

    public function validate(
        string $email,
        bool $newsletter,
        bool $offers,
        string $policyVersion
    ): bool {
        try {
            $response = $this->api->validate([
                'accountId'          => 0,
                'newsletter_consent' => $newsletter,
                'offers_consent'     => $offers,
                'policyVersion'      => $policyVersion,
                'source'             => 'WORDPRESS',
            ]);

            return in_array($response['status'], [200, 201], true);
        } catch (ApiException $e) {
            return false;
        }
    }
}