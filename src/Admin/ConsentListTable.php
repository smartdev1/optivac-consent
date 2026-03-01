<?php

namespace OptivacConsent\Admin;

use OptivacConsent\Http\HttpClient;
use OptivacConsent\Http\ApiException;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ConsentListTable extends \WP_List_Table
{
    private string $searchEmail  = '';
    private int    $perPage      = 20;
    private string $table;
    private string $pendingTable;

    public function __construct()
    {
        global $wpdb;
        $this->table        = $wpdb->prefix . 'optivac_consent_logs';
        $this->pendingTable = $wpdb->prefix . 'optivac_consent_pending';

        parent::__construct([
            'singular' => 'consent',
            'plural'   => 'consents',
            'ajax'     => false,
        ]);
    }

    public function get_columns(): array
    {
        return [
            'email'      => 'Email',
            'type'       => 'Type',
            'status'     => 'Statut',
            'source'     => 'Source',
            'granted_at' => 'Date d\'accord',
            'pending'    => 'En attente',
        ];
    }

    public function get_sortable_columns(): array
    {
        return [
            'email'      => ['email', false],
            'granted_at' => ['granted_at', true],
        ];
    }

    public function prepare_items(): void
    {
        $this->searchEmail = sanitize_email($_REQUEST['s'] ?? '');

        $columns = $this->get_columns();
        $this->_column_headers = [$columns, [], $this->get_sortable_columns()];

        $currentPage = $this->get_pagenum();
        $offset      = ($currentPage - 1) * $this->perPage;

        $data  = $this->fetchData($offset);
        $total = $this->countTotal();

        $this->items = $data;

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page'    => $this->perPage,
            'total_pages' => ceil($total / $this->perPage),
        ]);
    }

    private function fetchData(int $offset): array
    {
        $apiRows = $this->searchEmail ? $this->fetchFromApi($this->searchEmail) : [];
        $localRows = $this->fetchFromLocal($offset);
        $pendingRows = $this->fetchPending();
        $merged = [];
        $seen   = [];

        foreach (array_merge($apiRows, $localRows, $pendingRows) as $row) {
            $key = $row['email'] . '|' . $row['type'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $merged[]   = $row;
            }
        }

        return $merged;
    }

    private function fetchFromApi(string $email): array
    {
        try {
            $client   = new HttpClient();
            $response = $client->get(
                '/optivac-ws/api/consents/status/all',
                ['email' => $email]
            );

            $body = $response['body'] ?? [];
            $rows = [];

            foreach (['newsletter' => 'NEWSLETTER', 'offers' => 'OFFERS'] as $key => $label) {
                if (!isset($body[$key])) {
                    continue;
                }

                $item    = $body[$key];
                $granted = (bool) ($item['granted'] ?? false);

                $rows[] = [
                    'email'      => $email,
                    'type'       => $label,
                    'status'     => $granted ? 'GRANTED' : 'REVOKED',
                    'source'     => $item['source'] ?? '—',
                    'granted_at' => $this->formatDate($item['grantedAt'] ?? ''),
                    'pending'    => false,
                    '_origin'    => 'api',
                ];
            }

            return $rows;
        } catch (ApiException $e) {
            add_action('admin_notices', function () use ($e) {
                echo '<div class="notice notice-warning is-dismissible"><p>'
                    . '<strong>Optivac API :</strong> '
                    . esc_html($e->getMessage())
                    . '</p></div>';
            });

            return [];
        }
    }

    private function fetchFromLocal(int $offset): array
    {
        global $wpdb;

        $where  = '';
        $params = [];

        if ($this->searchEmail) {
            $where    = 'WHERE email = %s';
            $params[] = $this->searchEmail;
        }

        $orderby = sanitize_sql_orderby($_GET['orderby'] ?? 'created_at') ?: 'created_at';
        $order   = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT * FROM {$this->table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

        $params[] = $this->perPage;
        $params[] = $offset;

        $results = $wpdb->get_results(
            $wpdb->prepare($sql, ...$params),
            ARRAY_A
        );

        $rows = [];

        foreach ($results as $row) {
            foreach (['newsletter' => 'NEWSLETTER', 'offers' => 'OFFERS'] as $field => $label) {
                $rows[] = [
                    'email'      => $row['email'],
                    'type'       => $label,
                    'status'     => $row[$field] ? 'GRANTED' : 'REVOKED',
                    'source'     => $row['source'] ?? 'WORDPRESS',
                    'granted_at' => $this->formatDate($row['created_at'] ?? ''),
                    'pending'    => false,
                    '_origin'    => 'local',
                ];
            }
        }

        return $rows;
    }

    private function fetchPending(): array
    {
        global $wpdb;

        $where  = '';
        $params = [];

        if ($this->searchEmail) {
            $where    = 'WHERE email = %s';
            $params[] = $this->searchEmail;
        }

        $sql = "SELECT * FROM {$this->pendingTable} {$where} ORDER BY created_at DESC LIMIT 100";

        $results = $params
            ? $wpdb->get_results($wpdb->prepare($sql, ...$params), ARRAY_A)
            : $wpdb->get_results($sql, ARRAY_A);

        $rows = [];

        foreach ($results as $row) {
            foreach (['newsletter' => 'NEWSLETTER', 'offers' => 'OFFERS'] as $field => $label) {
                $rows[] = [
                    'email'      => $row['email'],
                    'type'       => $label,
                    'status'     => $row[$field] ? 'GRANTED' : 'REVOKED',
                    'source'     => 'WORDPRESS',
                    'granted_at' => $this->formatDate($row['created_at'] ?? ''),
                    'pending'    => true,
                    '_origin'    => 'pending',
                ];
            }
        }

        return $rows;
    }

    private function countTotal(): int
    {
        global $wpdb;

        if ($this->searchEmail) {
            return (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table} WHERE email = %s",
                    $this->searchEmail
                )
            ) * 2;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}") * 2;
    }

    public function column_status(array $item): string
    {
        $granted = $item['status'] === 'GRANTED';

        return sprintf(
            '<span style="display:inline-block;padding:2px 10px;border-radius:12px;font-size:12px;font-weight:600;background:%s;color:#fff;">%s</span>',
            $granted ? '#28a745' : '#dc3545',
            esc_html($item['status'])
        );
    }

    public function column_type(array $item): string
    {
        $icons = [
            'NEWSLETTER' => '',
            'OFFERS'     => '',
        ];

        $icon = $icons[$item['type']] ?? '';

        return $icon . ' ' . esc_html($item['type']);
    }

    public function column_source(array $item): string
    {
        $colors = [
            'WORDPRESS' => '#0073aa',
            'BREVO'     => '#0acf97',
            'MOBILE'    => '#fd7e14',
        ];

        $color = $colors[$item['source']] ?? '#888';

        return sprintf(
            '<span style="color:%s;font-weight:600;">%s</span>',
            $color,
            esc_html($item['source'])
        );
    }

    public function column_pending(array $item): string
    {
        if (!$item['pending']) {
            return '<span style="color:#28a745;">✔ Envoyé</span>';
        }

        return '<span style="color:#fd7e14;font-weight:600;"> En attente</span>';
    }

    public function column_email(array $item): string
    {
        return sprintf(
            '<strong>%s</strong>',
            esc_html($item['email'])
        );
    }

    public function column_default($item, $column_name): string
    {
        return esc_html($item[$column_name] ?? '—');
    }

    public function search_box($text, $input_id): void
    {
        ?>
        <p class="search-box">
            <label for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($text); ?></label>
            <input type="search"
                   id="<?php echo esc_attr($input_id); ?>"
                   name="s"
                   value="<?php echo esc_attr($this->searchEmail); ?>"
                   placeholder="Rechercher par email…" />
            <?php submit_button($text, 'button', false, false); ?>
        </p>
        <?php
    }

    private function formatDate(string $date): string
    {
        if (!$date) {
            return '—';
        }

        try {
            $dt = new \DateTime($date);
            return $dt->format('d/m/Y H:i');
        } catch (\Exception $e) {
            return $date;
        }
    }
}