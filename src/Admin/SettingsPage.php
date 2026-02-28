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
    }

    public function render(): void
    {
        ?>
        <div class="wrap">
            <h1>Optivac Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('optivac_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Base URL</th>
                        <td>
                            <input type="text" name="optivac_api_url"
                                value="<?php echo esc_attr(get_option('optivac_api_url')); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <td>
                            <input type="password" name="optivac_api_key"
                                value="<?php echo esc_attr(get_option('optivac_api_key')); ?>"
                                class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}