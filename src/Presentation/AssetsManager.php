<?php

namespace OptivacConsent\Presentation;

use OptivacConsent\Support\Constants;

class AssetsManager
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_enqueue_style(
            'optivac-consent',
            OPTIVAC_CONSENT_URL . 'assets/css/consent.css',
            [],
            OPTIVAC_CONSENT_VERSION
        );

        wp_enqueue_script(
            'optivac-consent',
            OPTIVAC_CONSENT_URL . 'assets/js/consent.js',
            ['jquery'],
            OPTIVAC_CONSENT_VERSION,
            true
        );

        wp_localize_script('optivac-consent', 'optivacConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce(Constants::NONCE_ACTION),
        ]);
    }
}
