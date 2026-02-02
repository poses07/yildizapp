<?php
$config = require __DIR__ . '/config.php';

$dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=' . $config['db']['charset'];
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
  PDO::ATTR_PERSISTENT => true,
  PDO::ATTR_TIMEOUT => 5,
];

function db(): PDO {
  global $dsn, $config, $options;
  static $pdo;
  if (!$pdo) {
    $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], $options);
  }
  return $pdo;
}
