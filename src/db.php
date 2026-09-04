<?php

$dsn = 'mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME') . ';charset=utf8mb4';

$pdo = new PDO($dsn, getenv('DB_USER'), getenv('DB_PASSWORD'), [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
]);

$pdo->exec("set time_zone = '+00:00'");
