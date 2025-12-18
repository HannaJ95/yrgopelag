<?php
declare(strict_types=1);
require __DIR__ . '/../autoload.php';


//FUNCTIONS
function getGuest(PDO $database, $name) : array
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


//IF SET SAVE BOOKING IN DATABASE
if (isset($_POST['name'],$_POST['room_id'], $_POST['arrival'], $_POST['departure'])) {
    
    //collect POST input-data
    $name = trim(htmlspecialchars($_POST['name']));
    $room_id = (int)htmlspecialchars($_POST['room_id']);
    $arrival = htmlspecialchars($_POST['arrival']);
    $departure = htmlspecialchars($_POST['departure']);
    $transfercode = htmlspecialchars($_POST['transfercode']);
    
    
    //check if not booked dates are avalable
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

    //if result is zero, dates are avalable
    if (isset($result['count']) && $result['count'] === 0) {

        //get GuestID
        $guest = getGuest($database, $name);

        //get price for bookede room
        $nights = (strtotime($departure) - strtotime($arrival)) / (60 * 60 * 24);
        $statement = $database->prepare('SELECT price from rooms where id = :room_id');
        $statement->execute(['room_id' => $room_id]);
        $room = $statement->fetch(PDO::FETCH_ASSOC);
        $room_cost = $room['price'] * $nights;

        //check if features is booked and get totalprice
        $features_id = [];
        if (isset($_POST['features'])) {
            
            $features_id = $_POST['features'];
        }

        $features_cost = 0;
        if(!empty($features_id)) {

            $placeholders = str_repeat('?,', count($features_id) - 1) . '?';
            
            $statement = $database->prepare("SELECT SUM(price) as total FROM features WHERE id IN ($placeholders)");
            $statement->execute($features_id);
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            $features_cost = $result['total'] ?? 0;
        }

        $total_cost = $room_cost + $features_cost;

        
        //insert into bookings
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


        //insert into booking_features
        if (!empty($features_id)){
            
            $statement = $database->prepare('INSERT INTO bookings_features (booking_id, feature_id) VALUES (:booking_id, :feature_id)');
            foreach ($features_id as $feature_id) {
                $statement->execute([
                    'booking_id' => $booking_id,
                    'feature_id' => (int)$feature_id
                ]);
            }
        }

        
    } else {
        //TODO: make code here to let the user know the dates are already booked/////////////////
    }
}

//Gå tillbaka till
header('Location: /index.php');
exit;