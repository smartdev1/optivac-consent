<?php

namespace OptivacConsent\Admin;

class SettingsPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addPage(): void
    {
        add_submenu_page(
            'optivac-consents',
            'Optivac Settings',
            'Settings',
            'manage_options',
            'optivac-settings',
            [$this, 'render']
        );
    }

    public function registerSettings(): void
    {
        register_setting('optivac_settings_group', 'optivac_api_url');
        register_setting('optivac_settings_group', 'optivac_api_key');
        register_setting('optivac_settings_group', 'optivac_auth_type');
        register_setting('optivac_settings_group', 'optivac_api_username');
        register_setting('optivac_settings_group', 'optivac_api_password');
        register_setting('optivac_settings_group', 'optivac_brevo_webhook_secret');
    }

    public function render(): void
    {
        $authType   = get_option('optivac_auth_type', 'bearer');
        $webhookUrl = rest_url('optivac/v1/brevo/unsubscribe');
        ?>
        <div class="wrap">
            <h1>Optivac — Configuration API</h1>
            <form method="post" action="options.php">
                <?php settings_fields('optivac_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">API Base URL</th>
                        <td>
                            <input type="text" name="optivac_api_url"
                                   value="<?php echo esc_attr(get_option('optivac_api_url', 'https://ws-test-optivac.makeessens.fr')); ?>"
                                   class="regular-text"
                                   placeholder="https://ws-test-optivac.makeessens.fr" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Type d'authentification</th>
                        <td>
                            <select name="optivac_auth_type" id="optivac_auth_type" onchange="optivacToggleAuth(this.value)">
                                <option value="bearer" <?php selected($authType, 'bearer'); ?>>Bearer Token</option>
                                <option value="basic"  <?php selected($authType, 'basic'); ?>>Basic Auth (username / password)</option>
                                <option value="apikey" <?php selected($authType, 'apikey'); ?>>X-API-Key</option>
                                <option value="none"   <?php selected($authType, 'none'); ?>>Aucune</option>
                            </select>
                        </td>
                    </tr>
                    <tr id="row_apikey" <?php echo in_array($authType, ['bearer', 'apikey']) ? '' : 'style="display:none"'; ?>>
                        <th scope="row">Token / API Key</th>
                        <td>
                            <input type="password" name="optivac_api_key"
                                   value="<?php echo esc_attr(get_option('optivac_api_key')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr id="row_username" <?php echo $authType === 'basic' ? '' : 'style="display:none"'; ?>>
                        <th scope="row">Username</th>
                        <td>
                            <input type="text" name="optivac_api_username"
                                   value="<?php echo esc_attr(get_option('optivac_api_username')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr id="row_password" <?php echo $authType === 'basic' ? '' : 'style="display:none"'; ?>>
                        <th scope="row">Password</th>
                        <td>
                            <input type="password" name="optivac_api_password"
                                   value="<?php echo esc_attr(get_option('optivac_api_password')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Brevo Webhook Secret</th>
                        <td>
                            <input type="password" name="optivac_brevo_webhook_secret"
                                   value="<?php echo esc_attr(get_option('optivac_brevo_webhook_secret')); ?>"
                                   class="regular-text" />
                            <p class="description">
                                Clé HMAC partagée avec Brevo pour sécuriser les webhooks entrants.<br>
                                URL à renseigner dans Brevo &rarr; Webhooks :
                                <code><?php echo esc_html($webhookUrl); ?></code>
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Enregistrer les paramètres'); ?>
            </form>
        </div>
        <script>
        function optivacToggleAuth(type) {
            document.getElementById('row_apikey').style.display   = ['bearer','apikey'].includes(type) ? '' : 'none';
            document.getElementById('row_username').style.display = type === 'basic' ? '' : 'none';
            document.getElementById('row_password').style.display = type === 'basic' ? '' : 'none';
        }
        </script>
        <?php
    }
}