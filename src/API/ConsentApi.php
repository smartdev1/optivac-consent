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

    public function getStatus(string $email, string $type): array
    {
        return $this->client->get(
            '/optivac-ws/api/consents/status',
            ['email' => $email, 'type' => $type]
        );
    }

    public function getStatusByType(string $email, string $type): array
    {
        return $this->client->get(
            '/optivac-ws/api/consents/status/' . $type,
            ['email' => $email]
        );
    }

    public function checkBrevo(string $email, string $type): array
    {
        return $this->client->get(
            '/optivac-ws/api/external/brevo/check',
            ['email' => $email, 'type' => $type]
        );
    }

    public function checkWordPress(string $email, string $type): array
    {
        return $this->client->get(
            '/optivac-ws/api/external/wordpress/check',
            ['email' => $email, 'type' => $type]
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
