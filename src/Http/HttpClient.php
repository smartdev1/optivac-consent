<?php

namespace OptivacConsent\Http;

class HttpClient
{
    private string $baseUrl;
    private int $timeout = 10;

    public function __construct()
    {
        $this->baseUrl = 'https://ws-test-optivac.makeessens.fr';
    }

    public function get(string $endpoint, array $query = []): array
    {
        $url = $this->buildUrl($endpoint, $query);
        return $this->request('GET', $url);
    }

    public function post(string $endpoint, array $body = []): array
    {
        return $this->request(
            'POST',
            $this->buildUrl($endpoint),
            [
                'body' => wp_json_encode($body),
            ]
        );
    }

    private function request(string $method, string $url, array $args = []): array
    {
        $response = wp_remote_request($url, array_merge([
            'method'  => $method,
            'timeout' => $this->timeout,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
        ], $args));

        if (is_wp_error($response)) {
            throw new ApiException($response->get_error_message());
        }

        $status = wp_remote_retrieve_response_code($response);
        $body   = wp_remote_retrieve_body($response);

        $decoded = json_decode($body, true);

        if ($status >= 400) {
            throw new ApiException(
                sprintf('API error (%d)', $status)
            );
        }

        return [
            'status' => $status,
            'body'   => $decoded ?? [],
        ];
    }

    private function buildUrl(string $endpoint, array $query = []): string
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}