<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'src/Core/Autoloader.php';

define('OPTIVAC_CONSENT_PATH', plugin_dir_path(__FILE__));

OptivacConsent\Core\Autoloader::register();
OptivacConsent\Infrastructure\Installer::uninstall();
