<?php

declare(strict_types=1);
require __DIR__ . '/../autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;




//FUNCTIONS
function getGuest(PDO $database, $name): array
{
    $check_name = $database->prepare("SELECT name FROM guests WHERE name= :name");
    $check_name->bindParam(':name', $name, PDO::PARAM_STR);
    $check_name->execute();
    $result = $check_name->fetch();

    //If guest don´t exist, insert guest
    if (!$result) {

        $statement = $database->prepare('INSERT INTO guests (name) VALUES (:name)');
        $statement->bindParam(':name', $name, PDO::PARAM_STR);
        $statement->execute();
    }

    //Get guest id
    $statement = $database->prepare("SELECT id FROM guests WHERE name = :name");
    $statement->bindParam(':name', $name, PDO::PARAM_STR);
    $statement->execute();
    $guest = $statement->fetch(PDO::FETCH_ASSOC);

    return $guest;
}


//*** SAVE BOOKING IN DATABASE ***//

//*** CHECK if booking is not set ***//
if (!isset($_POST['name'], $_POST['room_id'], $_POST['arrival'], $_POST['departure'])) {
    $_SESSION['error'] = "You need to enter your name, room, arrival and departure";
    header('Location: /index.php');
    exit;
}

//*** COLLECT POST INPUT-DATA ***//
$name = trim(htmlspecialchars($_POST['name']));
$room_id = (int)htmlspecialchars($_POST['room_id']);
$arrival = htmlspecialchars($_POST['arrival']);
$departure = htmlspecialchars($_POST['departure']);
$transfercode = htmlspecialchars($_POST['transfercode']);


//*** CHECK IF DEPARTURE IS BEFORE ARRIVAL ***//
if ($departure <= $arrival) {
    $_SESSION['error'] = "Departure must be after arrival";
    header('Location: /index.php');
    exit;
}


//*** 1. CHECK IF NOT BOOKED - IF ROOM AVALABLE ***//

//query to se if dates are avalable, count when condition is true
$query_check_dates = ("
    SELECT COUNT(*) as count 
    FROM bookings 
    WHERE room_id = :room_id
    AND (
        (:arrival >= arrival_date AND :arrival < departure_date) OR
        (:departure > arrival_date AND :departure <= departure_date) OR
        (:arrival <= arrival_date AND :departure >= departure_date)
    )
");

$statement = $database->prepare($query_check_dates);
$statement->execute([
    'room_id' => $room_id,
    'arrival' => $arrival,
    'departure' => $departure
]);

$result = $statement->fetch();

//check if result is not 0 - then it is not available
if (isset($result['count']) && $result['count'] != 0) {
    $_SESSION['error'] = "The room is not available";
    header('Location: /index.php');
    exit;
}

//*** 2. GET TOTAL PRICE FOR BOOKING ***//
$guest = getGuest($database, $name);

//get total price for booked room
$nights = (strtotime($departure) - strtotime($arrival)) / (60 * 60 * 24);
$statement = $database->prepare('SELECT price from rooms where id = :room_id');
$statement->execute(['room_id' => $room_id]);
$room = $statement->fetch(PDO::FETCH_ASSOC);
$room_cost = $room['price'] * $nights;
$user = $config['user'];
$api_key = $_ENV['API_KEY'];

//check if features is booked and get totalprice
$features_id = [];
if (isset($_POST['features'])) {

    $features_id = $_POST['features'];
}

$features_cost = 0;
if (!empty($features_id)) {

    $placeholders = str_repeat('?,', count($features_id) - 1) . '?';

    $statement = $database->prepare("SELECT SUM(price) as total FROM features WHERE id IN ($placeholders)");
    $statement->execute($features_id);
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    $features_cost = $result['total'] ?? 0;
}

$total_cost = $room_cost + $features_cost;



//*** 3. CHECK IF TRANSFERCODE IS VALID - IF TRUE CONTINUE WITH BOOKING ***//
$success = null;
try {
    $client = new Client(['base_uri' => $config['centralbank_api']]);

    $response = $client->request('POST', 'transferCode', [
        'json' => [
            'transferCode' => $transfercode,
            'totalCost' => $total_cost
        ]
    ]);

    $response = $response->getBody()->getContents();
    $response = json_decode($response, true);

    $success = $response['status'];
} catch (RequestException $e) {
    $_SESSION['error'] = "The transfercode is not valid";
    header('Location: /index.php');
    exit;
}

if ($success != 'success') {
    $_SESSION['error'] = "The room is not available";
    header('Location: /index.php');
    exit;
}


//*** 4. SEND RECEIPT TO CENTRALBANK ***//

//get the featureslist with names and tiers for receipt
$features_for_receipt = [];

if (!empty($features_id)) {
    $placeholders = str_repeat('?,', count($features_id) - 1) . '?';

    $statement = $database->prepare(" SELECT activities as activity, tier FROM features WHERE id IN ($placeholders)");

    $statement->execute($features_id);
    $features_for_receipt = $statement->fetchAll(PDO::FETCH_ASSOC);
}

//try to send receipt to centralbanken
try {
    $client = new Client(['base_uri' => $config['centralbank_api']]);

    $response = $client->request('POST', 'receipt', [
        'json' => [
            "user" => $config['user'],
            "api_key" => $_ENV['API_KEY'],
            "guest_name" => $name,
            "arrival_date" => $arrival,
            "departure_date" => $departure,
            "features_used" => $features_for_receipt,
            "star_rating" => 1
        ]
    ]);

    $receipt_response = json_decode($response->getBody()->getContents(), true);

} catch (RequestException $e) {

    if ($e->hasResponse()) {

        $error = json_decode($e->getResponse()->getBody()->getContents(), true);
        $_SESSION['error'] = $error['error'] ?? "Could not send receipt to Central Bank";

    } else {
        $_SESSION['error'] = "Could not connect to Central Bank";    
    }

    header('Location: /index.php');
    exit;
}

if (!str_contains($receipt_response['status'], "success")) {
    $_SESSION['error'] = "Receipt was not accepted by Central Bank";
    header('Location: /index.php');
    exit;
}



//**** 5. INSER BOOKING-DATA INTO BOOKINGS TABLE ***//

$query_insert_booking = 'INSERT INTO bookings (guest_id, room_id, arrival_date, departure_date, total_cost, transfer_code) VALUES (:guest_id, :room_id, :arrival_date, :departure_date, :total_cost, :transfer_code)';

$statement = $database->prepare($query_insert_booking);
$statement->execute([
    'guest_id' => $guest['id'],
    'room_id' => $room_id,
    'arrival_date' => $arrival,
    'departure_date' => $departure,
    'total_cost' => $total_cost,
    'transfer_code' => $transfercode
]);

$booking_id = $database->lastInsertId();


//*** 6. INSERT FEATURES-DATA INTO BOOKINGS_FEATURES TABLE IF FEATURES BOOKED ***//

if (!empty($features_id)) {

    $statement = $database->prepare('INSERT INTO bookings_features (booking_id, feature_id) VALUES (:booking_id, :feature_id)');
    foreach ($features_id as $feature_id) {
        $statement->execute([
            'booking_id' => $booking_id,
            'feature_id' => (int)$feature_id
        ]);
    }
}


//*** 7. DEPOSIT-REQUEST - GET MONEY FROM GUEST ***/

try {
    $client = new Client(['base_uri' => $config['centralbank_api']]);

    $response = $client->request('POST', 'deposit', [
        'json' => [
            "user" => $config['user'],
            "transferCode" => $transfercode
        ]
    ]);

    $deposit_response = json_decode($response->getBody()->getContents(), true);

} catch (RequestException $e) {
    if ($e->hasResponse()) {

        $error = json_decode($e->getResponse()->getBody()->getContents(), true);
        $_SESSION['error'] = $error['error'] ?? "Could not be deposit";

    } else {
        $_SESSION['error'] = "Could not connect to Central Bank";    
    }

    header('Location: /index.php');
    exit;
}

if (!str_contains($deposit_response['status'], "success")) {
    $_SESSION['error'] = "Deposit was not accepted by Central Bank";
    header('Location: /index.php');
    exit;


}


//TODO: SKRIV UT "KVITTOT TILL GÄSTEN, ändra header()"
//Save receipt for guest
$_SESSION['receipt'] = [
    'booking_id' => $booking_id,
    'guest_name' => $name,
    'room_id' => $room_id,
    'arrival' => $arrival,
    'departure' => $departure,
    'total_cost' => $total_cost,
    'features' => $features_for_receipt,
    'receipt_response' => $receipt_response
];

//när bokningen är lyckad, skriv ut kvitto
$_SESSION['success'] = "Booking successful!";
header('Location: /receipt.php');
exit;
