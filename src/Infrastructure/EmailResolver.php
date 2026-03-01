<?php

namespace OptivacConsent\Infrastructure;

class EmailResolver
{
    public function resolve(): string
    {
        if (is_user_logged_in()) {
            return wp_get_current_user()->user_email ?? '';
        }

        return $this->resolveFromSession();
    }

    public function resolveFromPost(string $field = 'email'): string
    {
        return sanitize_email($_POST[$field] ?? '');
    }

    private function resolveFromSession(): string
    {
        if (!function_exists('WC') || WC()->session === null) {
            return '';
        }

        $customerId = WC()->session->get_customer_id();

        if ($customerId) {
            $customer = new \WC_Customer($customerId);
            $email    = $customer->get_email();

            if ($email) {
                return sanitize_email($email);
            }
        }

        return sanitize_email(WC()->session->get('billing_email', ''));
    }
}
