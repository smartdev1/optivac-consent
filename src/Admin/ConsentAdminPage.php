<?php

namespace Optivac\Consent\Admin;

class ConsentAdminPage
{
    public function render(): void
    {
        require_once OPTIVAC_CONSENT_PATH . 'src/Admin/ConsentListTable.php';

        $table = new ConsentListTable();
        $table->prepare_items();

        echo '<div class="wrap">';
        echo '<h1>Optivac Consent List</h1>';
        echo '<form method="post">';
        $table->display();
        echo '</form>';
        echo '</div>';
    }
}