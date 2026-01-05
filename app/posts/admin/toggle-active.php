<?php

declare(strict_types=1);

require __DIR__ . '/../../autoload.php';

// Check if logged in admin
if (!isset($_SESSION['user'])) {
    redirect($config['paths']['admin']['login']);
}

//TODO: IF EMPTY - kolla det också??!! htmlspec..??

if (isset($_POST['feature_id'])) {
    $id = (int) $_POST['feature_id'];
    $table = 'features';
} elseif (isset($_POST['package_id'])) {
    $id = (int) $_POST['package_id'];
    $table = 'packages';
} else {
    redirect($config['paths']['admin']['index']);
}

// Toggle active status
$stmt = $database->prepare("UPDATE $table SET active = NOT active WHERE id = ?");
$stmt->execute([$id]);


redirect($config['paths']['admin']['index']);
