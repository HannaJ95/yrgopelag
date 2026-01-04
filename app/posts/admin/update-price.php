<?php

declare(strict_types=1);

require __DIR__ . '/../../autoload.php';

if (!isset($_SESSION['user'])) {
    redirect('/admin/index.php');
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
    redirect('/admin/index.php');
    exit;
}

$stmt = $database->prepare("UPDATE $table SET price = :price WHERE id = :id");
$stmt->execute([
    ':price' => $price,
    ':id' => $id
]);


redirect('/admin/index.php');
exit;
