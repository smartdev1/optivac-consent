<?php

namespace OptivacConsent\Http;

use OptivacConsent\Support\Constants;
use OptivacConsent\Infrastructure\Logger;

class HttpClient
{
    private string $baseUrl;
    private string $apiKey;
    private Logger $logger;
    private int    $timeout = 10;

    public function __construct(Logger $logger)
    {
        $this->logger  = $logger;
        $this->baseUrl = rtrim(
            get_option(Constants::OPTION_API_URL, 'https://ws-test-optivac.makeessens.fr'),
            '/'
        );
        $this->apiKey = defined('OPTIVAC_API_KEY')
            ? OPTIVAC_API_KEY
            : get_option(Constants::OPTION_API_KEY, '');
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->request('GET', $this->buildUrl($endpoint, $query));
    }

    public function post(string $endpoint, array $body = []): array
    {
        return $this->request('POST', $this->buildUrl($endpoint), [
            'body' => wp_json_encode($body),
        ]);
    }

    private function request(string $method, string $url, array $args = []): array
    {
        if (empty($this->apiKey)) {
            throw new ApiException('Bearer token is not configured in Optivac Settings.', 0);
        }

        $headers = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];

        $response = wp_remote_request($url, array_merge([
            'method'  => $method,
            'timeout' => $this->timeout,
            'headers' => $headers,
        ], $args));

        if (is_wp_error($response)) {
            throw new ApiException($response->get_error_message());
        }

        $status  = wp_remote_retrieve_response_code($response);
        $body    = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);

        if ($status >= 400) {
            throw new ApiException(
                sprintf('API error (%d): %s', $status, $body),
                $status
            );
        }

        return [
            'status' => $status,
            'body'   => $decoded ?? [],
        ];
    }

    private function buildUrl(string $endpoint, array $query = []): string
    {
        $url = $this->baseUrl . $endpoint;

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }
}