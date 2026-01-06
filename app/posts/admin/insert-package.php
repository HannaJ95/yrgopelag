<?php

declare(strict_types=1);

require __DIR__ . '/../../autoload.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['admin']['error'] = "You must be logged in to access this page";
    redirect($config['paths']['admin']['login']);
}

if (!isset($_POST['name'], $_POST['room_id'], $_POST['price'], $_POST['number_of_nights'], $_POST['features'])) {
    $_SESSION['admin']['error'] = "All fields are required";
    redirect($config['paths']['admin']['index']);
}

//TODO: IF EMPTY - kolla det också??!!

$name = htmlspecialchars($_POST['name']);
$room_id = (int)$_POST['room_id'];
$price = (int)$_POST['price'];
$number_of_nights = (int)$_POST['number_of_nights'];


$query = "INSERT INTO packages (name, room_id, price, number_of_nights)
VALUES (:name, :room_id, :price, :number_of_nights)";

$stmt = $database->prepare($query);
$stmt->execute([
    'name' => $name,
    'room_id' => $room_id,
    'price' => $price,
    'number_of_nights' => $number_of_nights
]);

$package_id = $database->lastInsertId();

$query = "INSERT INTO packages_features (package_id, feature_id)
VALUES (:package_id, :feature_id)";

$stmt = $database->prepare($query);
foreach ($_POST['features'] as $feature_id) {
    $stmt->execute([
        'package_id' => $package_id,
        'feature_id' => (int)$feature_id
    ]);
}

$_SESSION['admin']['success'] = "Successfully inserted package";
redirect($config['paths']['admin']['index']);