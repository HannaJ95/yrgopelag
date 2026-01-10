<?php

declare(strict_types=1);

require __DIR__ . '/../../autoload.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['admin']['error'] = "You must be logged in to access this page";
    redirect($config['paths']['admin']['index']);
}


if (!isset($_POST['loyalty_discount']) || !isset($_POST['id'])) {
    $_SESSION['admin']['error'] = "No input provided";
    redirect($config['paths']['admin']['index']);
}

$loyalty_discount = (int) $_POST['loyalty_discount'];
$id = (int) $_POST['id'];

if ($loyalty_discount > 100 || $loyalty_discount < 0) {
    $_SESSION['admin']['error'] = "Invalid loyalty discount value";
    redirect($config['paths']['admin']['index']);
}


$loyalty_discount = $loyalty_discount / 100;

$stmt = $database->prepare("UPDATE hotel_settings SET loyalty_discount = :loyalty_discount WHERE id = :id");
$stmt->execute([
    'loyalty_discount' => $loyalty_discount,
    'id' => $id
]);

$_SESSION['admin']['success'] = "Successfully updated loyalty discount";

redirect($config['paths']['admin']['index']);