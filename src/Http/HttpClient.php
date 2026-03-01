<?php

namespace OptivacConsent\Http;

use OptivacConsent\Support\Constants;

class HttpClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $authType;
    private string $username;
    private string $password;
    private int    $timeout = 10;

    public function __construct()
    {
        $this->baseUrl  = rtrim(get_option(Constants::OPTION_API_URL, 'https://ws-test-optivac.makeessens.fr'), '/');
        $this->apiKey   = get_option(Constants::OPTION_API_KEY, '');
        $this->authType = get_option('optivac_auth_type', 'bearer');
        $this->username = get_option('optivac_api_username', '');
        $this->password = get_option('optivac_api_password', '');
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
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        $authHeader = $this->buildAuthHeader();
        if ($authHeader) {
            $headers['Authorization'] = $authHeader;
        }

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

    private function buildAuthHeader(): string
    {
        switch ($this->authType) {
            case 'bearer':
                return $this->apiKey ? 'Bearer ' . $this->apiKey : '';

            case 'basic':
                if ($this->username && $this->password) {
                    return 'Basic ' . base64_encode($this->username . ':' . $this->password);
                }
                return '';

            case 'apikey':
                return $this->apiKey ? 'X-API-Key ' . $this->apiKey : '';

            default:
                return '';
        }
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