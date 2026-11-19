<?php
declare(strict_types=1);

function employee_db(): PDO
{
    if (envv('EMPLOYEE_DB_USERNAME') === '') {
        return db();
    }

    static $connection;
    if (!$connection) {
        $connection = new PDO(
            'mysql:host=' . envv('EMPLOYEE_DB_HOST', envv('DB_HOST', '127.0.0.1'))
                . ';port=' . envv('EMPLOYEE_DB_PORT', envv('DB_PORT', '3306'))
                . ';dbname=' . envv('EMPLOYEE_DB_DATABASE', 'esa') . ';charset=utf8mb4',
            envv('EMPLOYEE_DB_USERNAME'),
            envv('EMPLOYEE_DB_PASSWORD'),
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_TIMEOUT => 5, PDO::ATTR_PERSISTENT => true]
        );
        $connection->exec("SET time_zone = '+07:00'");
    }
    return $connection;
}

function employee_source(): string
{
    $sourceName = envv('EMPLOYEE_LOOKUP_SOURCE', 'esa.v_user_esa');
    if (!preg_match('/^[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)?$/', $sourceName)) {
        throw new RuntimeException('Employee lookup source is not configured.');
    }
    return implode('.', array_map(static fn(string $part): string => '`' . $part . '`', explode('.', $sourceName)));
}

function mis_staff_exists(string $name): bool
{
    $statement = employee_db()->prepare('SELECT 1 FROM ' . employee_source() . " WHERE nama = :name AND id_depar = 17 AND main_status = 'p' LIMIT 1");
    $statement->execute([':name' => $name]);
    return (bool)$statement->fetchColumn();
}

function employee_api(string $path): never
{
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: private, no-store');
    header('X-Content-Type-Options: nosniff');

    $respond = static function (int $status, array $body): never {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    };

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
        header('Allow: GET');
        $respond(405, ['message' => 'Metode permintaan tidak didukung.']);
    }

    $field = is_string($_GET['field'] ?? null) ? $_GET['field'] : '';
    $query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    if ($path === '/api/employees' && (!in_array($field, ['nik', 'name'], true)
        || mb_strlen($query) < 2 || mb_strlen($query) > 100)) {
        $respond(400, ['message' => 'Pencarian tidak valid.']);
    }

    try {
        $source = employee_source();
        if ($path === '/api/mis-staff') {
            $statement = employee_db()->prepare("SELECT DISTINCT nama, id_depar FROM $source WHERE id_depar = :department AND main_status = 'p' AND nama <> '' ORDER BY nama ASC");
            $statement->bindValue(':department', 17, PDO::PARAM_INT);
            $statement->execute();
        } else {
            $escaped = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($query));
            $pattern = $escaped . '%';
            $column = $field === 'nik' ? 'id_card' : 'nama';
            $hasCard = $field === 'nik' ? " AND id_card <> ''" : '';
            $statement = employee_db()->prepare("SELECT DISTINCT id_card, nama FROM $source WHERE LOWER($column) LIKE :pattern ESCAPE '!' AND main_status = 'p' AND nama <> ''$hasCard ORDER BY $column ASC");
            $statement->bindValue(':pattern', $pattern, PDO::PARAM_STR);
            $statement->execute();
        }

        $employees = $path === '/api/mis-staff'
            ? array_map(static fn(array $row): array => ['nama' => (string)($row['nama'] ?? ''), 'id_depar' => (int)($row['id_depar'] ?? 0)], $statement->fetchAll())
            : array_map(static fn(array $row): array => ['id_card' => (string)($row['id_card'] ?? ''), 'nama' => (string)($row['nama'] ?? '')], $statement->fetchAll());
        $respond(200, ['items' => $employees]);
    } catch (Throwable $error) {
        error_log('Employee lookup failed: ' . $error->getMessage());
        $respond(503, ['message' => 'Data karyawan belum dapat dimuat. Hubungi pengelola sistem.']);
    }
}
