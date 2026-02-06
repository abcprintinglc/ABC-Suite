<?php

class ABC_Price_Matrix {
    public static function table_name(): string {
        global $wpdb;
        return $wpdb->prefix . 'abc_price_matrix';
    }

    public static function create_table(): void {
        global $wpdb;
        $table = self::table_name();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            template_id BIGINT UNSIGNED NOT NULL,
            vendor VARCHAR(100) NOT NULL,
            qty_min INT NOT NULL,
            qty_max INT NULL,
            options_hash CHAR(32) NOT NULL,
            options_json LONGTEXT NOT NULL,
            turnaround VARCHAR(50) NOT NULL DEFAULT '',
            cost DECIMAL(10,2) NOT NULL DEFAULT 0,
            last_verified DATE NULL,
            source_note TEXT NULL,
            PRIMARY KEY (id),
            KEY template_vendor_hash_qty (template_id, vendor, options_hash, qty_min, qty_max),
            KEY template_vendor (template_id, vendor)
        ) {$charset};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function parse_options_json(string $options_json): array {
        $decoded = json_decode($options_json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    public static function normalize_options($value) {
        if (is_array($value)) {
            $is_assoc = array_keys($value) !== range(0, count($value) - 1);
            if ($is_assoc) {
                ksort($value);
            } else {
                sort($value);
            }
            foreach ($value as $key => $item) {
                $value[$key] = self::normalize_options($item);
            }
        }
        return $value;
    }

    private static function normalize_turnaround(array $options, string $turnaround = ''): array {
        if (isset($options['Turnaround'])) {
            if ($turnaround === '') {
                $turnaround = (string) $options['Turnaround'];
            }
            unset($options['Turnaround']);
        }
        if (isset($options['turnaround'])) {
            if ($turnaround === '') {
                $turnaround = (string) $options['turnaround'];
            }
            unset($options['turnaround']);
        }
        return [$options, $turnaround];
    }

    public static function compute_options_hash(array $options, string $turnaround = ''): string {
        [$options, $turnaround] = self::normalize_turnaround($options, $turnaround);
        $data = $options;
        if ($turnaround !== '') {
            $data['turnaround'] = $turnaround;
        }
        $normalized = self::normalize_options($data);
        return md5(wp_json_encode($normalized));
    }

    public static function lookup(int $template_id, string $vendor, int $qty, array $options, string $turnaround = ''): ?array {
        global $wpdb;
        $table = self::table_name();
        $options_hash = self::compute_options_hash($options, $turnaround);

        $exact = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE template_id = %d
                   AND vendor = %s
                   AND options_hash = %s
                   AND qty_min = %d
                   AND (qty_max = %d OR qty_max IS NULL)
                 ORDER BY qty_max ASC
                 LIMIT 1",
                $template_id,
                $vendor,
                $options_hash,
                $qty,
                $qty
            ),
            ARRAY_A
        );

        if ($exact) {
            return $exact;
        }

        $bracket = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table}
                 WHERE template_id = %d
                   AND vendor = %s
                   AND options_hash = %s
                   AND qty_min <= %d
                   AND (qty_max >= %d OR qty_max IS NULL)
                 ORDER BY qty_min DESC, qty_max ASC
                 LIMIT 1",
                $template_id,
                $vendor,
                $options_hash,
                $qty,
                $qty
            ),
            ARRAY_A
        );

        if ($bracket) {
            return $bracket;
        }

        return null;
    }

    public static function upsert(array $data): int {
        global $wpdb;
        $table = self::table_name();

        $options = is_array($data['options']) ? $data['options'] : [];
        $turnaround = (string) ($data['turnaround'] ?? '');
        $turnaround = self::normalize_turnaround($options, $turnaround)[1];
        $options_hash = self::compute_options_hash($options, $turnaround);
        $options_json = wp_json_encode($options);

        $payload = [
            'template_id' => (int) $data['template_id'],
            'vendor' => (string) $data['vendor'],
            'qty_min' => (int) $data['qty_min'],
            'qty_max' => $data['qty_max'] !== '' ? (int) $data['qty_max'] : null,
            'options_hash' => $options_hash,
            'options_json' => $options_json,
            'turnaround' => $turnaround,
            'cost' => (float) $data['cost'],
            'last_verified' => $data['last_verified'] !== '' ? $data['last_verified'] : null,
            'source_note' => $data['source_note'] !== '' ? (string) $data['source_note'] : null,
        ];

        if (!empty($data['id'])) {
            $wpdb->update($table, $payload, ['id' => (int) $data['id']]);
            return (int) $data['id'];
        }

        $wpdb->insert($table, $payload);
        return (int) $wpdb->insert_id;
    }
}
