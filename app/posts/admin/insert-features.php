<?php

declare(strict_types=1);

require __DIR__ . '/../../autoload.php';

use GuzzleHttp\Exception\RequestException;

if (!isset($_SESSION['user'])) {
    $_SESSION['admin']['error'] = "You must be logged in to access this page";
    redirect($config['paths']['admin']['login']);
}

if (!isset($_POST['name'], $_POST['activities'], $_POST['tier'], $_POST['price'])) {
    $_SESSION['admin']['error'] = "All fields are required";
    redirect($config['paths']['admin']['index']);
}

// form data
$name = htmlspecialchars(trim($_POST['name']));
$activity = htmlspecialchars(trim($_POST['activities']));
$tier = htmlspecialchars(trim($_POST['tier']));
$price = (int) $_POST['price'];

// check if empty
if (empty($name) || empty($activity) || empty($tier) || $price < 0) {
    $_SESSION['admin']['error'] = "All fields are required";
    redirect($config['paths']['admin']['index']);
}

// get neede info from hotel_settings
$settings = $database->query('SELECT * FROM hotel_settings LIMIT 1')->fetch();

// get features from features table
$stmt = $database->query('SELECT activities, tier, name FROM features');
$current_features = $stmt->fetchAll();

// make features array
$features_for_api = [];
foreach ($current_features as $f) {
    if (!isset($features_for_api[$f['activities']])) {
        $features_for_api[$f['activities']] = [];
    }
    $features_for_api[$f['activities']][$f['tier']] = $f['name'];
}


if (!isset($features_for_api[$activity])) {
    $features_for_api[$activity] = [];
}
$features_for_api[$activity][$tier] = $name;


// MAKE POST REQUEST TO CENTRALBANK FOR WANTED FEATURE
try {
    $response = $client->request('POST', 'islands', [
        'json' => [
            'user' => $config['user'],
            'api_key' => $_ENV['API_KEY'],
            'islandName' => $settings['island_name'],
            'hotelName' => $settings['hotel_name'],
            'url' => $settings['url'],
            'stars' => $settings['stars'],
            'features' => $features_for_api
        ]
    ]);

    $result = json_decode($response->getBody()->getContents(), true);
} catch (RequestException $e) {

    if ($e->hasResponse()) {
        $error = json_decode($e->getResponse()->getBody()->getContents(), true);
    }
    
    $_SESSION['admin']['error'] = $error['error'] ?? "Failed to connect to Centralbanken API";
    redirect($config['paths']['admin']['index']);
}

if ($result["charged"] === 0) {
    $_SESSION['admin']['error'] = "Failed to charge feature";
    redirect($config['paths']['admin']['index']);
}


// INSERT INTO DATABASE
$stmt = $database->prepare('
    INSERT INTO features (name, activities, tier, price) 
    VALUES (:name, :activities, :tier, :price)
');

$stmt->execute([
    'name' => $name,
    'activities' => $activity,
    'tier' => $tier,
    'price' => $price
]);


$_SESSION['admin']['success'] = "Successfully added feature";

redirect($config['paths']['admin']['index']);
