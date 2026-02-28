<?php

namespace Optivac\Consent\Admin;

if (!class_exists('\WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class ConsentListTable extends \WP_List_Table
{
    private array $data = [];

    public function __construct()
    {
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
            'newsletter' => 'Newsletter',
            'offers'     => 'Offers',
            'source'     => 'Source',
            'date'       => 'Date',
        ];
    }

    public function prepare_items(): void
    {
        $columns = $this->get_columns();
        $this->_column_headers = [$columns, [], []];

        $this->data = $this->fetchData();

        $this->items = $this->data;
    }

    private function fetchData(): array
    {
        // ⚠️ Remplacer par appel API réel
        return [
            [
                'email' => 'john@test.com',
                'newsletter' => 'GRANTED',
                'offers' => 'REVOKED',
                'source' => 'WORDPRESS',
                'date' => '2026-02-28 15:20'
            ],
            [
                'email' => 'anna@test.com',
                'newsletter' => 'GRANTED',
                'offers' => 'GRANTED',
                'source' => 'MOBILE',
                'date' => '2026-02-27 10:10'
            ],
        ];
    }

    public function column_default($item, $column_name)
    {
        return $item[$column_name] ?? '';
    }
}