<?php

namespace OptivacConsent\Infrastructure;

use OptivacConsent\Support\Constants;

class Cache
{
    public function get(string $email): ?array
    {
        $cached = get_transient($this->key($email));

        return $cached !== false ? $cached : null;
    }

    public function set(string $email, array $data): void
    {
        set_transient($this->key($email), $data, Constants::CACHE_TTL);
    }

    public function delete(string $email): void
    {
        delete_transient($this->key($email));
    }

    private function key(string $email): string
    {
        return Constants::CACHE_PREFIX . md5(strtolower(trim($email)));
    }
}
