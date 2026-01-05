<?php

declare(strict_types=1);

require __DIR__ . '/../../autoload.php';

if (!isset($_SESSION['user'])) {
    redirect($config['paths']['admin']['index']);
}

if (isset($_POST['feature_id'], $_POST['price'])) {
    $id = (int) $_POST['feature_id'];
    $price = (int) $_POST['price'];
    $table = 'features';

} elseif (isset($_POST['room_id'], $_POST['price'])) {
    $id = (int) $_POST['room_id'];
    $price = (int) $_POST['price'];
    $table = 'rooms';

} else {
    redirect($config['paths']['admin']['index']);
}

$stmt = $database->prepare("UPDATE $table SET price = :price WHERE id = :id");
$stmt->execute([
    ':price' => $price,
    ':id' => $id
]);


redirect($config['paths']['admin']['index']);
