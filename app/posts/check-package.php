<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

$room_id = (int) $_GET['room_id'];
$features_string = $_GET['features'] ?? '';
$feature_ids = $features_string ? array_map('intval', explode(',', $features_string)) : [];
$nights = (int) ($_GET['nights'] ?? 0);

// check if package exist with chosen options from guest
$statement = $database->prepare("
    SELECT p.id, p.name, p.price, p.number_of_nights
    FROM packages p
    WHERE p.room_id = ?
    AND p.active = 1
    AND p.number_of_nights = ?
");
$statement->execute([$room_id, $nights]);
$package = $statement->fetch();

if (!$package) {
    echo json_encode(['package' => null]);
    exit;
}

// get wich features are included
$statement = $database->prepare("
    SELECT feature_id
    FROM packages_features
    WHERE package_id = ?
");
$statement->execute([$package['id']]);
$package_features = $statement->fetchAll(PDO::FETCH_COLUMN);

// check if chosen options matches
sort($feature_ids);
sort($package_features);

if ($feature_ids === $package_features) {
    echo json_encode([
        'package' => true,
        'package_name' => $package['name'],
        'package_price' => $package['price'],
        'package_nights' => $package['number_of_nights']
    ]);
} else {
    echo json_encode(['package' => null]);
}