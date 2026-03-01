<?php
/**
 * Plugin Name: Optivac Consent
 * Description: Gestion des consentements newsletter et offres.
 * Version: 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

define('OPTIVAC_CONSENT_PATH', plugin_dir_path(__FILE__));
define('OPTIVAC_CONSENT_URL', plugin_dir_url(__FILE__));
define('OPTIVAC_CONSENT_VERSION', '1.1.0');

require_once OPTIVAC_CONSENT_PATH . 'src/Core/Autoloader.php';

OptivacConsent\Core\Autoloader::register();

register_activation_hook(__FILE__, function () {
    OptivacConsent\Infrastructure\Installer::install();
});

register_deactivation_hook(__FILE__, function () {
    OptivacConsent\Infrastructure\Installer::deactivate();
});

add_action('plugins_loaded', function () {
    (new OptivacConsent\Core\Plugin())->boot();
});
