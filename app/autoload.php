<?php

declare(strict_types=1);

// Load .env file
require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Start the session engines.
session_start();

// Set the default timezone to Coordinated Universal Time.
date_default_timezone_set('UTC');

// Set the default character encoding to UTF-8.
mb_internal_encoding('UTF-8');

// Include the helper functions.
require __DIR__ . '/functions.php';

// Fetch the global configuration array.
$config = require __DIR__ . '/config.php';

// Setup the database connection.
$database = new PDO($config['database_path']);

// Include database queries
require __DIR__ . '/database/queries.php';
