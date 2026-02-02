<?php
$config = require __DIR__ . '/config.php';

$serverDsn = 'mysql:host=' . $config['db']['host'] . ';charset=' . $config['db']['charset'];
$pdoServer = new PDO($serverDsn, $config['db']['user'], $config['db']['pass'], [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$pdoServer->exec('CREATE DATABASE IF NOT EXISTS `' . $config['db']['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

$dsn = 'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['name'] . ';charset=' . $config['db']['charset'];
$pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$pdo->exec("CREATE TABLE IF NOT EXISTS companies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  db_host VARCHAR(255) NULL,
  db_name VARCHAR(255) NULL,
  db_user VARCHAR(255) NULL,
  db_pass VARCHAR(255) NULL,
  db_charset VARCHAR(32) NOT NULL DEFAULT 'utf8mb4',
  reservations_table VARCHAR(255) NULL,
  pk_column VARCHAR(255) NULL,
  phone_column VARCHAR(255) NULL,
  name_column VARCHAR(255) NULL,
  status_column VARCHAR(255) NULL,
  new_value VARCHAR(255) NULL,
  approved_value VARCHAR(255) NULL,
  message_new VARCHAR(255) NULL,
  message_approved VARCHAR(255) NULL,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  customer_name VARCHAR(255) NOT NULL,
  customer_phone VARCHAR(32) NOT NULL,
  status VARCHAR(32) NOT NULL DEFAULT 'new',
  notified_new TINYINT(1) NOT NULL DEFAULT 0,
  notified_approved TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_at TIMESTAMP NULL,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS sent_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  company_id INT NOT NULL,
  remote_id VARCHAR(128) NOT NULL,
  phone VARCHAR(32) NULL,
  stage ENUM('new','approved','canceled') NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_company_remote_stage (company_id, remote_id, stage),
  UNIQUE KEY uniq_company_phone_stage (company_id, phone, stage),
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo 'ok';
