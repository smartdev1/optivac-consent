<?php

namespace OptivacConsent\Admin;

class ConsentAdminPage
{
    public function render(): void
    {
        global $wpdb;

        $logsTable    = $wpdb->prefix . 'optivac_consent_logs';
        $pendingTable = $wpdb->prefix . 'optivac_consent_pending';

        $totalLogs    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$logsTable}");
        $totalPending = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$pendingTable}");
        $totalGranted = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$logsTable} WHERE newsletter = 1 OR offers = 1");

        $table = new ConsentListTable();
        $table->prepare_items();

        ?>
        <div class="wrap">
            <h1>Optivac — Journal des consentements</h1>

            <div style="display:flex;gap:16px;margin:16px 0;">
                <?php foreach ([
                    ['label' => 'Total enregistrés', 'value' => $totalLogs,    'color' => '#0073aa'],
                    ['label' => 'Accordés',           'value' => $totalGranted, 'color' => '#28a745'],
                    ['label' => 'En attente',         'value' => $totalPending, 'color' => '#fd7e14'],
                ] as $stat): ?>
                <div style="background:#fff;border:1px solid #ddd;border-left:4px solid <?php echo $stat['color']; ?>;border-radius:4px;padding:12px 20px;min-width:160px;">
                    <div style="font-size:28px;font-weight:700;color:<?php echo $stat['color']; ?>;">
                        <?php echo esc_html($stat['value']); ?>
                    </div>
                    <div style="color:#666;font-size:13px;margin-top:4px;">
                        <?php echo esc_html($stat['label']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="get">
                <input type="hidden" name="page" value="optivac-consents" />
                <?php
                $table->search_box('Rechercher', 'consent-search');
                $table->display();
                ?>
            </form>
        </div>
        <?php
    }
}