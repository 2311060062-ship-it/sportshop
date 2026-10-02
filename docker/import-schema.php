<?php

$host = getenv('DB_HOST') ?: '';
$port = (int) (getenv('DB_PORT') ?: 3306);
$name = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: '');
$user = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: '');
$pass = getenv('DB_PASSWORD');
if ($pass === false || $pass === '') {
    $pass = getenv('DB_PASS') ?: '';
}
$ca = getenv('MYSQL_ATTR_SSL_CA') ?: '';

if ($host === '' || $name === '' || $user === '') {
    fwrite(STDERR, "Thiếu DB_HOST, DB_DATABASE hoặc DB_USERNAME.\n");
    exit(1);
}

$mysqli = mysqli_init();
if ($ca !== '') {
    mysqli_ssl_set($mysqli, null, null, $ca, null, null);
}
$flags = $ca !== '' ? MYSQLI_CLIENT_SSL : 0;
if (!mysqli_real_connect($mysqli, $host, $user, $pass, $name, $port, null, $flags)) {
    fwrite(STDERR, 'Không kết nối được MySQL: ' . mysqli_connect_error() . "\n");
    exit(1);
}
mysqli_set_charset($mysqli, 'utf8mb4');

$exists = mysqli_query($mysqli, "SHOW TABLES LIKE 'user'");
if ($exists && mysqli_fetch_row($exists)) {
    echo "Database already has tables.\n";
    exit(0);
}

$sql = file_get_contents('/var/www/sports_database.sql');
if (!is_string($sql) || $sql === '') {
    fwrite(STDERR, "Không đọc được sports_database.sql.\n");
    exit(1);
}
$sql = preg_replace('/CREATE DATABASE[\s\S]*?;/i', '', $sql) ?? $sql;
$sql = preg_replace('/^\s*USE\s+`?sports`?\s*;/mi', '', $sql) ?? $sql;

if (!mysqli_multi_query($mysqli, $sql)) {
    fwrite(STDERR, mysqli_error($mysqli) . "\n");
    exit(1);
}
do {
    if ($result = mysqli_store_result($mysqli)) {
        mysqli_free_result($result);
    }
    if (mysqli_errno($mysqli)) {
        fwrite(STDERR, mysqli_error($mysqli) . "\n");
        exit(1);
    }
} while (mysqli_more_results($mysqli) && mysqli_next_result($mysqli));

echo "Schema imported.\n";
