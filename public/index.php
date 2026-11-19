<?php
declare(strict_types=1);

if (is_file(dirname(__DIR__) . '/vendor/autoload.php')) require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/app/bootstrap.php';

$path = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
require dirname(__DIR__) . '/app/employee_api.php';
if (in_array($path, ['/api/employees', '/api/mis-staff'], true)) {
    employee_api($path);
}
$isHistory = in_array($path, ['/history', '/history/', '/riwayat', '/riwayat/'], true);
$isAdmin = in_array($path, ['/admin', '/admin/', '/admin/index.php'], true);
if ($isAdmin) {
    http_response_code(404);
    page('Tidak ditemukan', '<section class="panel narrow"><h1>Halaman tidak ditemukan</h1><p class="muted">Aplikasi ini tidak memiliki area admin.</p><a class="button secondary" href="/">Kembali ke formulir</a></section>');
    exit;
}
if (!$isHistory && $path !== '/') {
    http_response_code(404);
    page('Tidak ditemukan', '<section class="panel"><h1>Halaman tidak ditemukan</h1><a href="/">Kembali</a></section>');
    exit;
}

session_name('mislogbook');
session_set_cookie_params(['httponly' => true, 'secure' => envv('SESSION_SECURE_COOKIE') === 'true', 'samesite' => 'Lax', 'path' => '/']);
session_start();
$p = $isHistory ? 'history' : (string)($_GET['page'] ?? 'home');
if (!$isHistory && !in_array($p, ['home', 'success'], true)) {
    if (in_array($p, ['checkin', 'checkout', 'checkout-done'], true) && $_SERVER['REQUEST_METHOD'] === 'GET') { header('Location: /', true, 302); exit; }
    http_response_code(404);
    page('Tidak ditemukan', '<section class="panel"><h1>Halaman tidak ditemukan</h1><a href="/">Kembali</a></section>');
    exit;
}

try {
    if ($p === 'home') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $hasNikColumn = db()->query("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'visits' AND column_name = 'visitor_nik' LIMIT 1")->fetchColumn();
            if (!$hasNikColumn) {
                http_response_code(503);
                page('Database belum siap', '<section class="panel narrow"><h1>Catatan kunjungan belum dapat disimpan</h1><p class="muted">Database logbook belum selesai diperbarui. Hubungi pengelola sistem, lalu coba lagi setelah pembaruan selesai.</p><a class="button secondary" href="javascript:history.back()">Kembali ke formulir</a></section>');
                exit;
            }
            $d = visit_data();
            if (!mis_staff_exists($d['person_to_meet'])) {
                throw new InvalidArgumentException('Pilih karyawan MIS yang ditemui dari daftar.');
            }
            $s = db()->prepare("INSERT INTO visits(visit_date,visitor_nik,visitor_name,company,person_to_meet,purpose,check_in_time,check_out_time,checkin_recorded_at,checkout_recorded_at,created_by) VALUES(?,?,?,?,?,?,?,?,NOW(),NOW(),'public')");
            $s->execute([$d['visit_date'], $d['visitor_nik'], $d['visitor_name'], $d['company'], $d['person_to_meet'], $d['purpose'], $d['check_in_time'], $d['check_out_time']]);
            $_SESSION['visit_saved'] = true;
            go('success');
        }
        page('Formulir Kunjungan', guest_visit_form());
        exit;
    }
    if ($p === 'success') {
        if (empty($_SESSION['visit_saved'])) go('home');
        unset($_SESSION['visit_saved']);
        page('Kunjungan Tersimpan', guest_success());
        exit;
    }

    $actionSource = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
    $historyAction = is_string($actionSource['action'] ?? null) ? $actionSource['action'] : '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($historyAction !== 'update-visit') throw new InvalidArgumentException('Aksi riwayat tidak valid.');
        $wantsJson = str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if ($wantsJson) {
            if (!hash_equals(csrf(), (string)($_POST['_csrf'] ?? ''))) {
                http_response_code(419);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'message' => 'Sesi formulir kedaluwarsa. Muat ulang halaman.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            verify_csrf();
        }
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $submitted = [];
        try {
            if (!$id) throw new InvalidArgumentException('ID kunjungan tidak valid.');
            foreach (['visit_date', 'visitor_name', 'visitor_nik', 'company', 'person_to_meet', 'purpose', 'check_in_time', 'check_out_time'] as $field) $submitted[$field] = trim((string)($_POST[$field] ?? ''));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $submitted['visit_date']);
            if (!$date || $date->format('Y-m-d') !== $submitted['visit_date']) throw new InvalidArgumentException('Tanggal kunjungan tidak valid.');
            if ($submitted['visitor_name'] === '' || $submitted['person_to_meet'] === '' || $submitted['purpose'] === '') throw new InvalidArgumentException('Nama tamu, orang yang ditemui, dan keperluan wajib diisi.');
            foreach (['visitor_name' => 150, 'visitor_nik' => 64, 'company' => 150, 'person_to_meet' => 150, 'purpose' => 3000] as $field => $max) {
                if (mb_strlen($submitted[$field]) > $max) throw new InvalidArgumentException('Isian terlalu panjang.');
            }
            $checkIn = $submitted['check_in_time'];
            $checkOut = $submitted['check_out_time'];
            $validTime = static fn(string $time): bool => (bool)preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time);
            if (!$validTime($checkIn)) throw new InvalidArgumentException('Jam masuk wajib menggunakan format HH:mm.');
            if ($checkOut !== '' && !$validTime($checkOut)) throw new InvalidArgumentException('Jam keluar harus kosong atau menggunakan format HH:mm.');
            if ($checkOut !== '' && $checkOut < $checkIn) throw new InvalidArgumentException('Jam keluar tidak boleh mendahului jam masuk pada tanggal yang sama.');
            $exists = db()->prepare('SELECT id FROM visits WHERE id=? AND deleted_at IS NULL');
            $exists->execute([$id]);
            if (!$exists->fetchColumn()) throw new InvalidArgumentException('Data kunjungan tidak ditemukan.');
            $save = db()->prepare('UPDATE visits SET visit_date=?,visitor_name=?,visitor_nik=?,company=?,person_to_meet=?,purpose=?,check_in_time=?,check_out_time=?,checkout_recorded_at=CASE WHEN ? IS NULL THEN NULL ELSE COALESCE(checkout_recorded_at,NOW()) END WHERE id=? AND deleted_at IS NULL');
            $outValue = $checkOut === '' ? null : $checkOut . ':00';
            $save->execute([$submitted['visit_date'], $submitted['visitor_name'], $submitted['visitor_nik'] === '' ? null : $submitted['visitor_nik'], $submitted['company'] === '' ? null : $submitted['company'], $submitted['person_to_meet'], $submitted['purpose'], $checkIn . ':00', $outValue, $outValue, $id]);
            if ($wantsJson) {
                $fresh = db()->prepare('SELECT * FROM visits WHERE id=? AND deleted_at IS NULL');
                $fresh->execute([$id]);
                $updated = $fresh->fetch();
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => true, 'message' => 'Data kunjungan berhasil diperbarui.', 'visit' => [
                    'id' => (int)$updated['id'],
                    'visit_date' => (string)$updated['visit_date'],
                    'visit_date_label' => history_date((string)$updated['visit_date']),
                    'visitor_name' => (string)$updated['visitor_name'],
                    'visitor_nik' => (string)($updated['visitor_nik'] ?? ''),
                    'visitor_nik_display' => (string)($updated['visitor_nik'] ?: '—'),
                    'company' => (string)($updated['company'] ?? ''),
                    'company_display' => (string)($updated['company'] ?: '—'),
                    'person_to_meet' => (string)$updated['person_to_meet'],
                    'purpose' => (string)$updated['purpose'],
                    'check_in_time' => substr((string)$updated['check_in_time'], 0, 5),
                    'check_out_time' => $updated['check_out_time'] ? substr((string)$updated['check_out_time'], 0, 5) : '',
                    'complete' => (bool)$updated['check_out_time'],
                    'status_label' => $updated['check_out_time'] ? '✓ Lengkap (' . substr((string)$updated['check_out_time'], 0, 5) . ')' : 'Belum lengkap',
                ]], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['history_flash'] = ['type' => 'success', 'id' => $id, 'message' => 'Data kunjungan berhasil diperbarui.'];
        } catch (InvalidArgumentException $error) {
            if ($wantsJson) {
                http_response_code(422);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['ok' => false, 'message' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $_SESSION['history_flash'] = ['type' => 'error', 'id' => (int)$id, 'message' => $error->getMessage(), 'values' => $submitted];
        }
        $returnParams = [];
        parse_str((string)($_POST['return_query'] ?? ''), $returnInput);
        if (is_string($returnInput['q'] ?? null) && trim($returnInput['q']) !== '') $returnParams['q'] = mb_substr(trim($returnInput['q']), 0, 150);
        foreach (['from', 'to'] as $dateKey) if (is_string($returnInput[$dateKey] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $returnInput[$dateKey])) $returnParams[$dateKey] = $returnInput[$dateKey];
        if (in_array($returnInput['status'] ?? '', ['active', 'done'], true)) $returnParams['status'] = $returnInput['status'];
        $returnPage = filter_var($returnInput['p'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 2]]);
        if ($returnPage) $returnParams['p'] = $returnPage;
        $returnParams['open'] = (int)$id;
        header('Location: /history?' . http_build_query($returnParams), true, 303);
        exit;
    }

    $where = ['deleted_at IS NULL']; $params = [];
    $q = is_string($_GET['q'] ?? null) ? mb_substr(trim($_GET['q']), 0, 150) : '';
    if ($q !== '') { $where[] = '(visitor_name LIKE ? OR visitor_nik LIKE ? OR company LIKE ? OR person_to_meet LIKE ? OR purpose LIKE ?)'; $like = '%' . $q . '%'; array_push($params, $like, $like, $like, $like, $like); }
    $validDate = static function (mixed $value): string { if (!is_string($value) || $value === '') return ''; $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value); return $date && $date->format('Y-m-d') === $value ? $value : ''; };
    $from = $validDate($_GET['from'] ?? ''); $to = $validDate($_GET['to'] ?? '');
    if ($from !== '') { $where[] = 'visit_date >= ?'; $params[] = $from; }
    if ($to !== '') { $where[] = 'visit_date <= ?'; $params[] = $to; }
    $status = is_string($_GET['status'] ?? null) ? $_GET['status'] : ''; if (!in_array($status, ['', 'active', 'done'], true)) $status = '';
    if ($status === 'active') $where[] = 'check_out_time IS NULL'; if ($status === 'done') $where[] = 'check_out_time IS NOT NULL';
    $filter = implode(' AND ', $where);
    if (in_array($historyAction, ['export-csv', 'export-xlsx'], true)) {
        $export = db()->prepare("SELECT id,visit_date,visitor_name,visitor_nik,company,person_to_meet,purpose,check_in_time,check_out_time FROM visits WHERE $filter ORDER BY visit_date DESC,id DESC");
        $export->execute($params);
        $exportRows = $export->fetchAll();
        $headers = ['ID Kunjungan', 'Tanggal Kunjungan', 'Nama Tamu', 'NIK', 'Departemen', 'Orang yang Ditemui', 'Keperluan', 'Jam Masuk', 'Jam Keluar', 'Status'];
        $rowValues = static fn(array $row): array => [
            (string)$row['id'], (string)$row['visit_date'], (string)$row['visitor_name'], (string)($row['visitor_nik'] ?? ''), (string)($row['company'] ?? ''),
            (string)$row['person_to_meet'], (string)$row['purpose'], substr((string)$row['check_in_time'], 0, 5),
            $row['check_out_time'] ? substr((string)$row['check_out_time'], 0, 5) : '', $row['check_out_time'] ? 'Selesai' : 'Belum lengkap',
        ];
        $filenameDate = date('Y-m-d');
        if ($historyAction === 'export-csv') {
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="riwayat_tamu_' . $filenameDate . '.csv"');
            header('Cache-Control: no-store');
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $headers);
            foreach ($exportRows as $row) {
                $values = array_map(static fn(string $value): string => preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value, $rowValues($row));
                fputcsv($output, $values);
            }
            fclose($output);
            exit;
        }
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) throw new RuntimeException('PhpSpreadsheet belum tersedia. Jalankan composer install.');
        $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle('Riwayat Tamu');
        foreach ($headers as $column => $heading) $sheet->setCellValue([$column + 1, 1], $heading);
        foreach ($exportRows as $rowIndex => $row) {
            foreach ($rowValues($row) as $column => $value) {
                $sheet->setCellValueExplicit([$column + 1, $rowIndex + 2], $value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        $sheet->getStyle('A1:J1')->getFont()->setBold(true);
        foreach (range('A', 'J') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        $sheet->freezePane('A2');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="riwayat_tamu_' . $filenameDate . '.xlsx"');
        header('Cache-Control: no-store');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
        exit;
    }
    $count = db()->prepare("SELECT COUNT(*) FROM visits WHERE $filter"); $count->execute($params); $total = (int)$count->fetchColumn();
    $pg = min(max(1, (int)($_GET['p'] ?? 1)), max(1, (int)ceil($total / 25)));
    $s = db()->prepare("SELECT * FROM visits WHERE $filter ORDER BY visit_date DESC,id DESC LIMIT 25 OFFSET " . (($pg - 1) * 25)); $s->execute($params); $rows = $s->fetchAll();
    $stats = db()->query("SELECT COALESCE(SUM(visit_date = CURDATE()),0) total,COALESCE(SUM(check_out_time IS NULL),0) active,COALESCE(SUM(visit_date = CURDATE() AND check_out_time IS NOT NULL),0) done FROM visits WHERE deleted_at IS NULL")->fetch();
    $allStats = db()->query("SELECT COUNT(*) total,COALESCE(SUM(check_out_time IS NULL),0) active,COALESCE(SUM(check_out_time IS NOT NULL),0) done FROM visits WHERE deleted_at IS NULL")->fetch();
    $historyFlash = is_array($_SESSION['history_flash'] ?? null) ? $_SESSION['history_flash'] : null;
    unset($_SESSION['history_flash']);
    page('Riwayat Tamu', history_body($rows, $stats, $allStats, $total, $pg, $q, $from, $to, $status, false, $historyFlash));
} catch (InvalidArgumentException $error) {
    http_response_code(400); page('Periksa kembali', '<section class="panel narrow"><div class="alert">' . e($error->getMessage()) . '</div><a class="button secondary" href="javascript:history.back()">Kembali</a></section>');
} catch (Throwable $error) {
    error_log($error->__toString()); http_response_code(500); page('Gangguan sementara', '<section class="panel narrow"><h1>Permintaan belum dapat diproses</h1><p class="muted">Silakan coba lagi.</p><a class="button secondary" href="/">Beranda</a></section>');
}
