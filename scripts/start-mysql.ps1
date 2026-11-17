$ErrorActionPreference = 'Stop'
$project = Split-Path -Parent $PSScriptRoot
$runtime = Join-Path $project '.runtime'
$archive = Join-Path $runtime 'mysql-8.4.13-winx64.zip'
$install = Join-Path $runtime 'mysql-8.4.13-winx64'
$data = Join-Path $runtime 'mysql-data'
$config = Join-Path $runtime 'my.ini'
$server = Join-Path $install 'bin\mysqld.exe'
if (!(Test-Path $server)) {
    if (!(Test-Path $archive)) { throw "MySQL archive tidak ditemukan: $archive" }
    $checksum = (Get-FileHash -LiteralPath $archive -Algorithm MD5).Hash.ToLowerInvariant()
    if ($checksum -ne '7b94489d5524dea9b27097068bea25a8') { throw 'Checksum arsip MySQL tidak sesuai; ekstraksi dibatalkan.' }
    Expand-Archive -LiteralPath $archive -DestinationPath $runtime -Force
}
if (!(Test-Path $data)) {
    New-Item -ItemType Directory -Path $data | Out-Null
    $installConfig = $install.Replace('\', '/')
    $dataConfig = $data.Replace('\', '/')
    $runtimeConfig = $runtime.Replace('\', '/')
    $ini = "[mysqld]`nbasedir=$installConfig`ndatadir=$dataConfig`nport=3307`nbind-address=127.0.0.1`nmysqlx=0`ncharacter-set-server=utf8mb4`ncollation-server=utf8mb4_0900_ai_ci`nlog-error=$runtimeConfig/mysql-error.log`npid-file=$runtimeConfig/mysql.pid`n"
    [IO.File]::WriteAllText($config, $ini, [Text.UTF8Encoding]::new($false))
    & $server "--defaults-file=$config" --initialize-insecure
    if ($LASTEXITCODE -ne 0) { throw 'Inisialisasi data MySQL gagal. Lihat .runtime\mysql-error.log.' }
}
$alreadyRunning = Get-NetTCPConnection -LocalPort 3307 -State Listen -ErrorAction SilentlyContinue
if (!$alreadyRunning) {
    Start-Process -FilePath $server -ArgumentList "--defaults-file=$config" -WorkingDirectory $install -WindowStyle Hidden | Out-Null
    $client = Join-Path $install 'bin\mysqladmin.exe'
    for ($i=0; $i -lt 40; $i++) {
        Start-Sleep -Milliseconds 500
        & $client --host=127.0.0.1 --port=3307 --user=root --silent ping 2>$null
        if ($LASTEXITCODE -eq 0) { Write-Output 'MySQL aktif di 127.0.0.1:3307'; exit 0 }
    }
    throw 'MySQL belum siap. Periksa .runtime\mysql-error.log.'
}
Write-Output 'MySQL sudah aktif di 127.0.0.1:3307'
