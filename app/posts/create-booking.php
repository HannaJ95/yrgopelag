<?php

declare(strict_types=1);
require __DIR__ . '/../autoload.php';

// use GuzzleHttp\Client;
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


function checkPackagePrice(PDO $database, int $room_id, array $feature_ids, int $nights): ?int
{
    if (empty($feature_ids)) {
        return null;
    }

    // check if package exist with chosen options from guest
    $statement = $database->prepare("
        SELECT p.id, p.price, p.number_of_nights
        FROM packages p
        WHERE p.room_id = ?
        AND p.active = 1
        AND p.number_of_nights = ?
    ");
    $statement->execute([$room_id, $nights]);
    $package = $statement->fetch();

    if (!$package) {
        return null;
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
        return $package['price'];
    }

    return null;
}


//*** CREATE BOOKING ***//

//*** CHECK if booking is not set ***//
if (empty($_POST['name']) || empty($_POST['api_key']) || empty($_POST['arrival']) || empty($_POST['departure'])) {
    $_SESSION['error'] = "You need to fill in all fields";
    redirect('/index.php');
    exit;
}

//*** COLLECT POST INPUT-DATA ***//
$name = ucwords(trim(htmlspecialchars($_POST['name'])));
$room_id = (int)htmlspecialchars($_POST['room_id']);
$arrival = htmlspecialchars($_POST['arrival']);
$departure = htmlspecialchars($_POST['departure']);
$guest_api_key = htmlspecialchars($_POST['api_key']);


//*** CHECK IF DEPARTURE IS BEFORE ARRIVAL ***//
if ($departure <= $arrival) {
    $_SESSION['error'] = "Departure must be after arrival";
    redirect('/index.php');
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

//get total price for booked room
$nights = (new DateTime($arrival))->diff(new DateTime($departure))->days;
$statement = $database->prepare('SELECT price from rooms where id = :room_id');
$statement->execute(['room_id' => $room_id]);
$room = $statement->fetch(PDO::FETCH_ASSOC);
$room_cost = $room['price'] * $nights;

//check if features is booked and get totalprice
$features_id = [];
if (isset($_POST['features'])) {
    $features_id = array_map('intval', $_POST['features']);
}

$features_cost = 0;
if (!empty($features_id)) {

    $placeholders = str_repeat('?,', count($features_id) - 1) . '?';

    $statement = $database->prepare("SELECT SUM(price) as total FROM features WHERE id IN ($placeholders)");
    $statement->execute($features_id);
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    $features_cost = $result['total'] ?? 0;
}


// *** KOLLA OM DET FINNS ETT PAKET ***//
$package_price = checkPackagePrice($database, $room_id, $features_id, $nights);

if ($package_price !== null) {
    $total_cost = $package_price;

} else {
    $total_cost = $room_cost + $features_cost;
}


//*** IF GUEST BOOKED MULTIPLE TIMES - GIVE A DISCOUNT ON TOTAL PRICE ***//
$discount_multiplier = 0.9;

$statement = $database->prepare('SELECT COUNT(*) as count 
        FROM bookings b
        JOIN guests g ON b.guest_id = g.id
        WHERE g.name = :name');
$statement->execute(['name' => $name]);

$result = $statement->fetch();
$number_of_bookings = $result['count'];

if (isset($result['count']) && $result['count'] != 0) {
    $total_cost = round($total_cost * $discount_multiplier);
}

//*** 3. CREATE TRANSFERCODE FOR GUEST (WITHDRAW) ***/

try {

    $response = $client->request('POST', 'withdraw', [
        'json' => [
            'user' => $name,
            'api_key' => $guest_api_key,
            'amount' => $total_cost
        ]
    ]);

    $response = $response->getBody()->getContents();
    $response = json_decode($response, true);

    $status = $response['status'];


} catch (RequestException $e) {

    if ($e->hasResponse()) {

        $error = json_decode($e->getResponse()->getBody()->getContents(), true);
        $_SESSION['error'] = $error['error'] ?? "The transfercode is not valid";

    } else {
        $_SESSION['error'] = "The transfercode is not valid";
    }

    header('Location: /index.php');
    exit;
}


if ($status != 'success') {
    $_SESSION['error'] = "The transfercode is not valid";
    header('Location: /index.php');
    exit;
}

$transfercode = $response['transferCode'];



//*** 4. CHECK IF TRANSFERCODE IS VALID - IF TRUE CONTINUE WITH BOOKING (TRANSFERCODE) ***//
$success = null;
try {

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

    if ($e->hasResponse()) {

        $error = json_decode($e->getResponse()->getBody()->getContents(), true);
        $_SESSION['error'] = $error['error'] ?? "The transfercode is not valid";

    } else {
        $_SESSION['error'] = "The transfercode is not valid";    
    }

    header('Location: /index.php');
    exit;
}


if ($success != 'success') {
    $_SESSION['error'] = "The transfercode is not valid";
    header('Location: /index.php');
    exit;
}


//*** 5. SEND RECEIPT TO CENTRALBANK (RECEIPT) ***//

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
    $_SESSION['error'] = $receipt_response['error'] ?? "Receipt was not accepted by Central Bank";
    header('Location: /index.php');
    exit;
}



//**** 6. INSER BOOKING-DATA INTO BOOKINGS TABLE ***//

$query_insert_booking = 'INSERT INTO bookings (guest_id, room_id, arrival_date, departure_date, total_cost, transfer_code) VALUES (:guest_id, :room_id, :arrival_date, :departure_date, :total_cost, :transfer_code)';

$guest = getGuest($database, $name);

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


//*** 7. INSERT FEATURES-DATA INTO BOOKINGS_FEATURES TABLE IF FEATURES BOOKED ***//

if (!empty($features_id)) {

    $statement = $database->prepare('INSERT INTO bookings_features (booking_id, feature_id) VALUES (:booking_id, :feature_id)');
    foreach ($features_id as $feature_id) {
        $statement->execute([
            'booking_id' => $booking_id,
            'feature_id' => (int)$feature_id
        ]);
    }
}


//*** 8. DEPOSIT-REQUEST - GET MONEY FROM GUEST (DEPOSIT) ***/

try {

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
        $_SESSION['error'] = $error['error'] ?? "Could not process deposit";

    } else {
        $_SESSION['error'] = "Could not connect to Central Bank";    
    }

    header('Location: /index.php');
    exit;
}

if (!str_contains($deposit_response['status'], "success")) {
    $_SESSION['error'] = $deposit_response['error'] ?? "Deposit was not accepted by Central Bank";
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
