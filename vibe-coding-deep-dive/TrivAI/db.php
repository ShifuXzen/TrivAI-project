<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . "config.php";

function getDbConnection(): PDO
{
  static $pdo = null;
  if ($pdo instanceof PDO) {
    return $pdo;
  }

  $host = getenv("DB_HOST") ?: "127.0.0.1";
  $port = getenv("DB_PORT") ?: "3306";
  $name = getenv("DB_NAME") ?: "trivai_reports";
  $user = getenv("DB_USER") ?: "bit_academy";
  $pass = getenv("DB_PASS") ?: "bit_academy";
  $charset = getenv("DB_CHARSET") ?: "utf8mb4";

  if ($name === "" || $user === "") {
    throw new RuntimeException(
      "Database configuratie ontbreekt. Zet DB_HOST, DB_NAME, DB_USER, DB_PASS in .env."
    );
  }

  $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  $pdo = new PDO($dsn, $user, $pass, $options);
  return $pdo;
}
