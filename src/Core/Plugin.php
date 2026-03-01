<?php

namespace OptivacConsent\Core;

use OptivacConsent\Ajax\ConsentController;
use OptivacConsent\Admin\AdminMenu;
use OptivacConsent\Admin\SettingsPage;
use OptivacConsent\Presentation\AssetsManager;
use OptivacConsent\Infrastructure\WooCommerceIntegration;

class Plugin
{
    private Container $container;

    public function boot(): void
    {
        $this->container = new Container();
        $this->maybeRunInstaller();
        $this->registerHooks();
    }

    private function maybeRunInstaller(): void
    {
        $installed = get_option('optivac_consent_db_version', '');

        if ($installed !== '1.0') {
            \OptivacConsent\Infrastructure\Installer::install();
        }
    }

    private function registerHooks(): void
    {
        $this->container
            ->get(ConsentController::class)
            ->register();

        if (is_admin()) {
            $this->container
                ->get(AdminMenu::class)
                ->register();

            $this->container
                ->get(SettingsPage::class)
                ->register();
        }

        $this->container
            ->get(AssetsManager::class)
            ->register();

        if (class_exists('WooCommerce')) {
            $this->container
                ->get(WooCommerceIntegration::class)
                ->register();
        }
    }
}