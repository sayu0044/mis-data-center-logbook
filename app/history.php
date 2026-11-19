<?php
declare(strict_types=1);

function history_date(string $value): string
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date) return $value;
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $months = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    return $days[(int)$date->format('w')] . ', ' . $date->format('j') . ' ' . $months[(int)$date->format('n')] . ' ' . $date->format('Y');
}

function history_dialog(array $visit, bool $public = false, ?array $flash = null, string $returnQuery = ''): string
{
    $id = (int)$visit['id'];
    $date = e(history_date((string)$visit['visit_date']));
    $name = e($visit['visitor_name']);
    $visitorNik = e(($visit['visitor_nik'] ?? '') ?: '—');
    $company = e($visit['company'] ?: '—');
    $person = e($visit['person_to_meet']);
    $purpose = e($visit['purpose']);
    $timeIn = e(substr($visit['check_in_time'], 0, 5));
    $timeOut = e($visit['check_out_time'] ? substr($visit['check_out_time'], 0, 5) : '—');
    $status = $visit['check_out_time'] ? 'Lengkap' : 'Belum lengkap';
    $dialogFlash = $flash && (int)($flash['id'] ?? 0) === $id ? $flash : null;
    $openEdit = $dialogFlash && ($dialogFlash['type'] ?? '') === 'error';
    $edit = [
        'visit_date' => (string)$visit['visit_date'],
        'visitor_name' => (string)$visit['visitor_name'],
        'visitor_nik' => (string)($visit['visitor_nik'] ?? ''),
        'company' => (string)($visit['company'] ?? ''),
        'person_to_meet' => (string)$visit['person_to_meet'],
        'purpose' => (string)$visit['purpose'],
        'check_in_time' => substr((string)$visit['check_in_time'], 0, 5),
        'check_out_time' => $visit['check_out_time'] ? substr((string)$visit['check_out_time'], 0, 5) : '',
    ];
    if ($openEdit && is_array($dialogFlash['values'] ?? null)) $edit = array_merge($edit, $dialogFlash['values']);
    $message = $dialogFlash ? '<div class="dialog-message ' . (($dialogFlash['type'] ?? '') === 'success' ? 'success' : 'error') . '" role="status">' . e($dialogFlash['message'] ?? '') . '</div>' : '';
    return '<dialog class="visit-dialog" id="visit-dialog-' . $id . '" aria-labelledby="visit-dialog-title-' . $id . '"' . ($openEdit ? ' data-open-edit' : '') . '>'
        . '<div class="dialog-head"><h2 id="visit-dialog-title-' . $id . '">Detail Kunjungan Tamu</h2><button type="button" class="dialog-close" data-dialog-close aria-label="Tutup detail">×</button></div>'
        . '<div class="dialog-content">' . $message . '<div class="dialog-visit-view"><div class="dialog-meta"><span>ID &amp; TANGGAL KUNJUNGAN</span><strong data-detail="visit_date_label">#' . $id . ' · ' . $date . '</strong></div>'
        . '<div class="dialog-times"><div><small>JAM MASUK</small><strong data-detail="check_in_time">' . $timeIn . '</strong></div><div><small>JAM KELUAR</small><strong data-detail="check_out_time">' . $timeOut . '</strong></div></div>'
        . '<dl class="dialog-details"><div><dt>NAMA TAMU</dt><dd data-detail="visitor_name">' . $name . '</dd></div><div><dt>NIK</dt><dd data-detail="visitor_nik">' . $visitorNik . '</dd></div><div><dt>DEPARTEMEN</dt><dd data-detail="company_display">' . $company . '</dd></div><div><dt>YANG DITEMUI</dt><dd data-detail="person_to_meet">' . $person . '</dd></div><div><dt>STATUS KUNJUNGAN</dt><dd data-detail="visit_status">' . $status . '</dd></div><div><dt>MAKSUD &amp; KEPERLUAN</dt><dd class="dialog-purpose" data-detail="purpose">' . $purpose . '</dd></div></dl></div>'
        . '<form class="dialog-visit-form" method="post" action="/history" hidden><input type="hidden" name="_csrf" value="' . e(csrf()) . '"><input type="hidden" name="action" value="update-visit"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="return_query" value="' . e($returnQuery) . '"><div class="dialog-form-id"><span>ID KUNJUNGAN</span><strong>#' . $id . '</strong></div><div class="dialog-form-grid"><label><span>Tanggal Kunjungan *</span><input type="date" name="visit_date" value="' . e($edit['visit_date']) . '" required></label><div class="dialog-autocomplete autocomplete-field" data-autocomplete="name"><label for="edit-visitor-name-' . $id . '"><span>Nama Tamu *</span></label><input id="edit-visitor-name-' . $id . '" name="visitor_name" maxlength="150" value="' . e($edit['visitor_name']) . '" required autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="edit-name-suggestions-' . $id . '"><div class="suggestion-panel" id="edit-name-suggestions-' . $id . '" role="listbox" hidden></div><small class="lookup-status" role="status" aria-live="polite"></small></div><div class="dialog-autocomplete autocomplete-field" data-autocomplete="nik"><label for="edit-visitor-nik-' . $id . '"><span>NIK <small>(opsional)</small></span></label><input id="edit-visitor-nik-' . $id . '" name="visitor_nik" maxlength="64" value="' . e($edit['visitor_nik']) . '" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="edit-nik-suggestions-' . $id . '"><div class="suggestion-panel" id="edit-nik-suggestions-' . $id . '" role="listbox" hidden></div><small class="lookup-status" role="status" aria-live="polite"></small></div><label><span>DEPARTEMEN</span><input name="company" maxlength="150" value="' . e($edit['company']) . '"></label><label><span>Yang Ditemui *</span><input name="person_to_meet" maxlength="150" value="' . e($edit['person_to_meet']) . '" required></label><label><span>Jam Masuk *</span><input type="time" name="check_in_time" value="' . e($edit['check_in_time']) . '" required></label><label><span>Jam Keluar <small>(opsional)</small></span><input type="time" name="check_out_time" value="' . e($edit['check_out_time']) . '"></label><label class="dialog-form-purpose"><span>Maksud &amp; Keperluan *</span><textarea name="purpose" maxlength="3000" rows="3" required>' . e($edit['purpose']) . '</textarea></label></div><p class="dialog-status-note">Status akan menjadi <strong>Lengkap</strong> jika Jam Keluar diisi, atau <strong>Belum lengkap</strong> jika dikosongkan.</p><div class="dialog-form-actions"><button class="button secondary" type="button" data-cancel-visit>Batal</button><button class="button primary" type="submit">Simpan</button></div></form></div>'
        . '<div class="dialog-footer"><button class="button secondary" type="button" data-edit-visit>Edit Kunjungan</button><button class="button dialog-done" type="button" data-dialog-close>Tutup</button></div>'
        . '</dialog>';
}

function history_body(array $rows, array $stats, array $allStats, int $total, int $pageNumber, string $search, string $from, string $to, string $status, bool $admin = true, ?array $flash = null): string
{
    $filter = array_filter(['q' => $search, 'from' => $from, 'to' => $to, 'status' => $status], static fn($value) => $value !== '');
    $baseUrl = $admin ? '/admin/' : '/history';
    $queryUrl = static fn(array $values): string => $baseUrl . '?' . e(http_build_query($values));
    $currentUrl = $queryUrl(array_merge(['page' => 'visits'], $filter));
    $xlsxUrl = $queryUrl(array_merge(['action' => 'export-xlsx'], $filter));
    $statusUrl = static function (string $next) use ($filter, $queryUrl): string {
        $params = $filter;
        if ($next === '') unset($params['status']);
        else $params['status'] = $next;
        return $queryUrl(array_merge(['page' => 'visits'], $params));
    };
    $previousUrl = $pageNumber > 1 ? $queryUrl(array_merge(['page' => 'visits'], $filter, ['p' => $pageNumber - 1])) : null;
    $nextUrl = $pageNumber * 25 < $total ? $queryUrl(array_merge(['page' => 'visits'], $filter, ['p' => $pageNumber + 1])) : null;
    $returnQuery = http_build_query(array_merge($filter, $pageNumber > 1 ? ['p' => $pageNumber] : []));
    ob_start();
    ?>
<?php if ($flash): ?><div class="history-flash <?= ($flash['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status"><?= e($flash['message'] ?? '') ?></div><?php endif ?>
<section class="history-hero panel">
    <div class="history-hero-main">
        <div class="history-title-line"><span class="history-title-icon"><?= icon('user') ?></span><h1>Riwayat &amp; Presensi Tamu Kantor</h1><span class="history-count"><?= (int)$allStats['total'] ?> data tersimpan</span></div>
        <p>Daftar tamu lengkap beserta tanggal, jam masuk, dan jam keluar kunjungan.</p>
        <div class="history-summary"><span>Live Database MIS</span><span>Hari ini <b><?= (int)$stats['total'] ?></b></span><span>Belum lengkap <b><?= (int)$allStats['active'] ?></b></span><span>Lengkap hari ini <b><?= (int)$stats['done'] ?></b></span></div>
    </div>
    <div class="history-hero-actions">
        <a class="button history-refresh" href="<?= $currentUrl ?>"><?= icon('refresh') ?> Sinkronkan Data</a>
        <a class="button export-button excel-button" href="<?= $xlsxUrl ?>"><?= icon('note') ?> Ekspor Excel</a>
    </div>
</section>
<section class="history-controls" aria-label="Cari dan filter riwayat">
    <form class="history-search" method="get" action="<?= e($baseUrl) ?>">
        <input type="hidden" name="page" value="visits">
        <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif ?>
        <?php if ($from !== ''): ?><input type="hidden" name="from" value="<?= e($from) ?>"><?php endif ?>
        <?php if ($to !== ''): ?><input type="hidden" name="to" value="<?= e($to) ?>"><?php endif ?>
        <?= icon('search') ?><input name="q" type="search" value="<?= e($search) ?>" placeholder="Cari nama, NIK, departemen, staf, atau keperluan..." aria-label="Cari riwayat tamu"><button type="submit" aria-label="Cari">Cari</button>
    </form>
    <nav class="history-status" aria-label="Filter status">
        <a class="<?= $status === '' ? 'selected' : '' ?>" href="<?= $statusUrl('') ?>">Semua <span>(<?= (int)$allStats['total'] ?>)</span></a>
        <a class="<?= $status === 'active' ? 'selected' : '' ?>" href="<?= $statusUrl('active') ?>">Belum lengkap <span>(<?= (int)$allStats['active'] ?>)</span></a>
        <a class="<?= $status === 'done' ? 'selected' : '' ?>" href="<?= $statusUrl('done') ?>">Lengkap <span>(<?= (int)$allStats['done'] ?>)</span></a>
    </nav>
</section>
<form class="history-dates" method="get" action="<?= e($baseUrl) ?>" aria-label="Filter tanggal">
    <input type="hidden" name="page" value="visits">
    <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= e($search) ?>"><?php endif ?>
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif ?>
    <label>Dari tanggal <input type="date" name="from" value="<?= e($from) ?>"></label>
    <label>Sampai tanggal <input type="date" name="to" value="<?= e($to) ?>"></label>
    <button class="button secondary" type="submit">Terapkan tanggal</button>
    <?php if ($search !== '' || $from !== '' || $to !== '' || $status !== ''): ?><a href="<?= e($baseUrl) ?>" class="history-clear">Reset filter</a><?php endif ?>
</form>
<section class="history-table-section">
    <div class="history-mobile-hint">Geser tabel ke samping untuk melihat seluruh kolom.</div>
    <div class="history-table-wrap">
        <table class="history-table">
            <thead><tr><th>WAKTU &amp; JAM PRESENSI</th><th>NAMA TAMU</th><th>NIK</th><th>DEPARTEMEN</th><th>BERTEMU (MIS)</th><th>KEPERLUAN</th><th>STATUS KUNJUNGAN</th><th>AKSI</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="history-empty"><strong>Belum ada kunjungan yang sesuai.</strong><span>Periksa kata kunci atau rentang tanggal, lalu coba lagi.</span></td></tr>
            <?php endif ?>
            <?php foreach ($rows as $index => $visit): ?>
                <?php $number = ($pageNumber - 1) * 25 + $index + 1; $id = (int)$visit['id']; $detailHref = $admin ? '/admin/?page=detail&amp;id=' . $id : '#visit-dialog-' . $id; ?>
                <tr data-visit-row="<?= $id ?>">
                    <td class="history-time"><div class="history-date"><?= icon('clock') ?><span data-field="visit_date_label"><?= e(history_date((string)$visit['visit_date'])) ?></span><small class="history-index">#<?= $number ?></small></div><div class="history-time-badges"><span class="time-in" data-field="check_in_time"><?= e(substr($visit['check_in_time'], 0, 5)) ?></span><span class="history-arrow">→</span><span class="<?= $visit['check_out_time'] ? 'time-out' : 'time-empty' ?>" data-field="check_out_time"><?= e($visit['check_out_time'] ? substr($visit['check_out_time'], 0, 5) : '—') ?></span></div></td>
                    <td class="history-name" data-field="visitor_name"><?= e($visit['visitor_name']) ?></td>
                    <td class="history-nik" data-field="visitor_nik"><?= e(($visit['visitor_nik'] ?? '') ?: '—') ?></td>
                    <td class="history-company"><?= icon('building') ?> <span data-field="company_display"><?= e($visit['company'] ?: '—') ?></span></td>
                    <td class="history-person" data-field="person_to_meet"><?= e($visit['person_to_meet']) ?></td>
                    <td class="history-purpose" data-field="purpose" title="<?= e($visit['purpose']) ?>"><?= e($visit['purpose']) ?></td>
                    <td class="history-status-cell"><span class="pill <?= $visit['check_out_time'] ? 'done' : 'active' ?>" data-field="status_label"><?= $visit['check_out_time'] ? '✓ Lengkap (' . e(substr($visit['check_out_time'], 0, 5)) . ')' : 'Belum lengkap' ?></span></td>
                    <td class="history-action"><a href="<?= $detailHref ?>" data-dialog-open="visit-dialog-<?= $id ?>" aria-label="Lihat detail kunjungan <?= e($visit['visitor_name']) ?>">›</a></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
    <?php foreach ($rows as $visit) echo history_dialog($visit, !$admin, $flash, $returnQuery); ?>
    <div class="history-pagination"><span>Menampilkan <?= $total ? (($pageNumber - 1) * 25 + 1) . '–' . min($pageNumber * 25, $total) : '0' ?> dari <?= $total ?> kunjungan</span><div><?php if ($previousUrl): ?><a href="<?= $previousUrl ?>">← Sebelumnya</a><?php endif ?><span>Halaman <?= $pageNumber ?></span><?php if ($nextUrl): ?><a href="<?= $nextUrl ?>">Berikutnya →</a><?php endif ?></div></div>
</section>
<?php
    return (string)ob_get_clean();
}
