<?php

namespace Optivac\Consent\Admin;

class AdminMenu
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            'Optivac Consents',
            'Optivac',
            'manage_options',
            'optivac-consents',
            [$this, 'render'],
            'dashicons-email',
            26
        );
    }

    public function render(): void
    {
        require_once OPTIVAC_CONSENT_PATH . 'src/Admin/ConsentAdminPage.php';
        (new ConsentAdminPage())->render();
    }
}