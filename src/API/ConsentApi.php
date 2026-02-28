<?php

namespace OptivacConsent\API;

use OptivacConsent\Http\HttpClient;

class ConsentApi
{
    private HttpClient $client;

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    public function getAllStatus(string $email): array
    {
        return $this->client->get(
            '/optivac-ws/api/consents/status/all',
            ['email' => $email]
        );
    }

    public function validate(array $payload): array
    {
        return $this->client->post(
            '/optivac-ws/api/consents/validate',
            $payload
        );
    }
}