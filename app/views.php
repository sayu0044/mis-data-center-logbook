<?php
declare(strict_types=1);

function icon(string $name): string
{
    $paths = [
        'book' => '<path d="M4 5.5h7a3 3 0 0 1 3 3v11a3 3 0 0 0-3-2.5H4V5.5Zm16 0h-3a3 3 0 0 0-3 3v11a3 3 0 0 1 3-2.5h3V5.5Z"/><path d="m17 10 1.4 1.4L21 8.8"/>',
        'user' => '<circle cx="12" cy="8" r="3"/><path d="M5.5 20v-1.5a6.5 6.5 0 0 1 13 0V20"/>',
        'building' => '<path d="M4 20V7l8-3 8 3v13M2 20h20M8 9h1m5 0h1M8 13h1m5 0h1M10 20v-4h4v4"/>',
        'team' => '<circle cx="9" cy="8" r="2.5"/><path d="M3 19v-1a6 6 0 0 1 12 0v1M16 6a2.5 2.5 0 0 1 0 5m1 2a5 5 0 0 1 4 5v1"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'note' => '<path d="M6 3h9l3 3v15H6zM15 3v4h3M9 11h6M9 15h6"/>',
        'shield' => '<path d="M12 2 5 5v6c0 5 3 8 7 11 4-3 7-6 7-11V5l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
        'refresh' => '<path d="M20 7V3l-3 3a8 8 0 0 0-13 5M4 17v4l3-3a8 8 0 0 0 13-5"/><path d="M20 3v5h-5M4 21v-5h5"/>',
        'qr' => '<path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM20 14v3M20 20h-3M14 20h2"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
    ];
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['book']) . '</svg>';
}

function page(string $title, string $body, bool $admin = false, string $active = '', ?int $visitCount = null): void
{
    $home = $admin ? '/admin/' : '/';
    $isHistoryPage = !$admin && $title === 'Riwayat Tamu';
    ?><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Logbook kunjungan Data Center MIS.">
    <title><?= e($title) ?> · Logbook Tamu MIS</title>
    <link rel="stylesheet" href="/assets/tailwind.css">
    <script src="/assets/app.js" defer></script>
</head>
<body class="min-h-screen flex flex-col <?= $admin ? 'is-admin' : 'is-guest' ?>">
<?php if ($admin): ?>
    <div class="topbar"><div class="topbar-inner"><span><i class="status-dot"></i> Logbook Tamu MIS (Admin Panel)</span><a href="/">Buka Tampilan Tamu ↗</a></div></div>
<?php endif ?>
<header class="site-header"><div class="header-inner">
    <a class="brand" href="<?= e($home) ?>"><span class="brand-icon"><?= icon('book') ?></span><span class="brand-copy"><strong>Logbook Tamu MIS</strong><small>Meja Resepsionis &amp; Kunjungan Tamu MIS Developer</small></span></a>
    <?php if ($admin): ?>
    <div class="admin-header-actions"><nav class="main-nav" aria-label="Navigasi admin"><a href="/"><?= icon('book') ?> <span>Formulir Tamu</span></a><a class="<?= in_array($active, ['dashboard', 'visits'], true) ? 'selected' : '' ?>" href="/admin/"><?= icon('history') ?> <span>Riwayat Tamu</span><?php if ($visitCount !== null): ?><small class="nav-count"><?= $visitCount ?></small><?php endif ?></a></nav><button class="header-icon-button" type="button" data-reload aria-label="Muat ulang riwayat" title="Muat ulang"><?= icon('refresh') ?></button><a class="header-icon-button <?= $active === 'qr' ? 'selected' : '' ?>" href="/admin/?page=qr" aria-label="QR formulir tamu" title="QR formulir tamu"><?= icon('qr') ?></a></div>
    <?php else: ?>
    <div class="guest-header-actions"><nav class="guest-page-nav" aria-label="Navigasi buku tamu"><a class="guest-form-link <?= $isHistoryPage ? '' : 'selected' ?>" href="/"><?= icon('book') ?> <span>Formulir Tamu</span></a><a class="guest-history-link <?= $isHistoryPage ? 'selected' : '' ?>" href="/history"><?= icon('history') ?> <span>Riwayat Tamu</span></a></nav><span class="guest-official"><?= icon('shield') ?> Presensi Resmi Tamu</span></div>
    <?php endif ?>
</div></header>
<main id="main" class="page-shell <?= $admin ? 'admin-shell' : ($isHistoryPage ? 'history-shell' : 'guest-shell') ?>"><?= $body ?></main>
<footer class="site-footer"><span>Logbook Tamu MIS</span><i>•</i><?= $admin ? 'Sistem Logbook Administrasi Digital' : 'Buku Tamu Digital &amp; Presensi Kunjungan' ?></footer>
</body></html><?php
}

function guest_intro(): string
{
    $current = e(date('d-m-Y H:i:s'));
    return '<section class="intro"><div class="intro-badge"><i class="blue-dot"></i> Sistem Buku Tamu &amp; Presensi MIS</div><h1>Formulir Kunjungan Tamu</h1><p>Isi catatan kunjungan saat Anda akan meninggalkan Data Center MIS.</p><div class="clock-note">' . icon('clock') . ' <span>Waktu Sekarang: <strong id="live-clock">' . $current . '</strong></span></div></section>';
}

function fields(array $d = [], bool $guest = false): string
{
    $v = static fn(string $key, string $default = ''): string => e($d[$key] ?? $default);
    $time = e(substr((string)($d['check_in_time'] ?? ''), 0, 5));
    $out = e(substr((string)($d['check_out_time'] ?? date('H:i')), 0, 5));
    if (!$guest) {
        return '<label><span>Nama Lengkap <b>*</b></span><input name="visitor_name" maxlength="150" required autocomplete="name" placeholder="Contoh: Budi Santoso" value="' . $v('visitor_name') . '"></label>'
            . '<label><span> Departemen <small>(opsional)</small></span><input name="company" maxlength="150" autocomplete="organization" placeholder="Nama perusahaan" value="' . $v('company') . '"></label>'
            . '<label><span>Yang Ditemui <b>*</b></span><input name="person_to_meet" maxlength="150" required placeholder="Nama staf MIS" value="' . $v('person_to_meet') . '"></label>'
            . '<label><span>Tanggal Kunjungan <b>*</b></span><input type="date" name="visit_date" required value="' . $v('visit_date', date('Y-m-d')) . '"></label>'
            . '<div class="visit-time-grid"><label><span>Jam Masuk <b>*</b></span><input type="time" name="check_in_time" required value="' . $time . '"></label><label><span>Jam Keluar <b>*</b></span><input type="time" name="check_out_time" required value="' . $out . '"></label></div>'
            . '<label><span>Keperluan Kunjungan <b>*</b></span><textarea name="purpose" maxlength="3000" required rows="4" placeholder="Tuliskan maksud dan tujuan kunjungan secara ringkas...">' . $v('purpose') . '</textarea></label>';
    }
    return '<label class="field"><span>NAMA LENGKAP <b>*</b></span><span class="input-wrap">' . icon('user') . '<input name="visitor_name" maxlength="150" required autocomplete="name" placeholder="Contoh: Ir. Budi Santoso, M.Kom" value="' . $v('visitor_name') . '"></span></label>'
        . '<label class="field"><span> Departemen <small>(opsional)</small></span><span class="input-wrap">' . icon('building') . '<input name="company" maxlength="150" autocomplete="organization" placeholder="Ketik nama Departemen Anda..." value="' . $v('company') . '"></span></label>'
        . '<label class="field"><span>BERTEMU DENGAN <b>*</b></span><span class="input-wrap">' . icon('team') . '<input name="person_to_meet" maxlength="150" required placeholder="Ketik nama staf MIS yang ditemui..." value="' . $v('person_to_meet') . '"></span></label>'
        . '<label class="field"><span>TANGGAL KUNJUNGAN <b>*</b></span><input type="date" name="visit_date" required value="' . $v('visit_date', date('Y-m-d')) . '"></label>'
        . '<div class="visit-time-grid guest-time-grid"><label><span>' . icon('clock') . ' JAM MASUK KUNJUNGAN <b>*</b></span><input type="time" name="check_in_time" required value="' . $time . '"><small>Jam kedatangan (format 24 jam).</small></label><label><span>' . icon('clock') . ' JAM KELUAR KUNJUNGAN <b>*</b></span><input type="time" name="check_out_time" required value="' . $out . '"><small>Jam saat meninggalkan Data Center.</small></label></div>'
        . '<label class="field"><span>KEPERLUAN KUNJUNGAN <b>*</b></span><span class="input-wrap textarea-wrap">' . icon('note') . '<textarea name="purpose" maxlength="3000" required rows="4" placeholder="Tuliskan maksud dan tujuan kunjungan secara ringkas...">' . $v('purpose') . '</textarea></span></label>';
}

function guest_visit_form(): string
{
    return guest_intro()
        . '<section class="panel form-panel guest-card"><form method="post" action="/" class="form"><input type="hidden" name="_csrf" value="' . e(csrf()) . '">'
        . fields([], true)
        . '<button class="button primary wide guest-submit" type="submit">↗ &nbsp; Simpan Catatan Kunjungan</button></form>'
        . '<div class="form-note"><span class="status-dot"></span> Isi satu kali ketika Anda akan keluar dari Data Center.</div></section>';
}

function guest_success(): string
{
    return '<section class="panel narrow success"><div class="success-icon">✓</div><div class="eyebrow">LOGBOOK TAMU MIS</div><h1>Kunjungan tersimpan</h1><p class="muted">Terima kasih. Catatan kunjungan Anda sudah tersimpan.</p><a class="button primary wide" href="/">Kembali ke Formulir Tamu</a></section>';
}
