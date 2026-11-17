<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$source = new PDO('mysql:host=127.0.0.1;port=3306;dbname=mis_logbook;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$target = new PDO('mysql:host=127.0.0.1;port=3307;dbname=mis_logbook;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$tables = ['visits'];
foreach ($tables as $table) if ((int)$target->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() !== 0) throw new RuntimeException("Tabel MySQL `$table` sudah berisi data; migrasi dibatalkan agar tidak menimpa data.");
$target->beginTransaction();
try {
    foreach ($tables as $table) {
        $columns = array_column($source->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');
        $names = implode(',', array_map(static fn(string $name): string => '`' . str_replace('`', '``', $name) . '`', $columns));
        $insert = $target->prepare("INSERT INTO `$table` ($names) VALUES (" . implode(',', array_fill(0, count($columns), '?')) . ')');
        foreach ($source->query("SELECT * FROM `$table`") as $row) $insert->execute(array_values($row));
        fwrite(STDOUT, "$table: " . $source->query("SELECT COUNT(*) FROM `$table`")->fetchColumn() . " row(s) copied.\n");
    }
    $target->commit();
} catch (Throwable $error) {
    $target->rollBack();
    throw $error;
}
