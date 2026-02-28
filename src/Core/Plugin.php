<?php

namespace OptivacConsent\Core;

use OptivacConsent\Ajax\ConsentController;
use OptivacConsent\Admin\AdminMenu;
use OptivacConsent\Presentation\AssetsManager;
use OptivacConsent\WooCommerce\WooCommerceIntegration;

class Plugin
{
    private Container $container;

    public function boot(): void
    {
        $this->container = new Container();
        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        // AJAX
        $this->container
            ->get(ConsentController::class)
            ->register();

        // ADMIN
        if (is_admin()) {
            $this->container
                ->get(AdminMenu::class)
                ->register();
        }

        // FRONT ASSETS
        $this->container
            ->get(AssetsManager::class)
            ->register();

        // WOOCOMMERCE
        if (class_exists('WooCommerce')) {
            $this->container
                ->get(WooCommerceIntegration::class)
                ->register();
        }
    }
}