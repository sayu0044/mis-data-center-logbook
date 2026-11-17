<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$pdo = new PDO('mysql:host=127.0.0.1;port=3307;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE IF NOT EXISTS mis_logbook CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$username = 'mis_logbook_app';
$password = bin2hex(random_bytes(24));
$quotedUser = $pdo->quote($username);
$quotedPassword = $pdo->quote($password);
$pdo->exec("CREATE USER IF NOT EXISTS $quotedUser@'127.0.0.1' IDENTIFIED BY $quotedPassword");
$pdo->exec("ALTER USER $quotedUser@'127.0.0.1' IDENTIFIED BY $quotedPassword");
$pdo->exec("GRANT SELECT, INSERT, UPDATE, DELETE ON mis_logbook.* TO $quotedUser@'127.0.0.1'");

$envPath = dirname(__DIR__) . '/.env';
$lines = is_file($envPath) ? file($envPath, FILE_IGNORE_NEW_LINES) : [];
$values = ['APP_URL'=>'http://127.0.0.1:8000','APP_ENV'=>'development','DB_HOST'=>'127.0.0.1','DB_PORT'=>'3307','DB_DATABASE'=>'mis_logbook','DB_USERNAME'=>$username,'DB_PASSWORD'=>$password,'SESSION_SECURE_COOKIE'=>'false'];
$written = []; $writtenLine = [];
foreach ($lines as $line) {
    if (preg_match('/^([A-Z_]+)=/', $line, $match) && array_key_exists($match[1], $values)) {
        $key = $match[1]; $written[$key] = true; $line = $key . '=' . $values[$key];
    }
    $writtenLine[] = $line;
}
foreach ($values as $key=>$value) if (!isset($written[$key])) $writtenLine[] = $key . '=' . $value;
file_put_contents($envPath, implode(PHP_EOL, $writtenLine) . PHP_EOL, LOCK_EX);
fwrite(STDOUT, "MySQL database and restricted application user are configured. .env now uses MySQL on 127.0.0.1:3307.\n");
