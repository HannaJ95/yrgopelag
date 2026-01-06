<?php
require __DIR__ . '/../autoload.php';

header('Content-Type: application/json');

if (empty($_GET['name'])) {
    echo json_encode(['discount_multiplier' => 0, 'bookings_count' => 0]);
    exit;
}

$name = ucwords(trim(htmlspecialchars($_GET['name'])));

$statement = $database->prepare('SELECT COUNT(*) as count 
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    WHERE g.name = :name');
$statement->execute(['name' => $name]);

$result = $statement->fetch();
$has_bookings = isset($result['count']) && $result['count'] != 0;

echo json_encode([
    'discount_multiplier' => $has_bookings ? 0.9 : 0,
    'bookings_count' => $result['count']
]);