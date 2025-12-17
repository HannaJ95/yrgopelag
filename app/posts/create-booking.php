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
    $feature = null;
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

        
        //TODO: räkna ut det faktiska priset////////////////////
        $total_cost = 10;
        
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
        
        //TODO: SPARA FEATURES I BOOKING MHA bookings table och features
        //check if features is booked, if so save it
        if (isset($_POST['feature'])) {
            
            $feature = $_POST['feature'];
        }

    } else {
        //TODO: make code here to let the user know the dates are already booked/////////////////
    }
}

//Gå tillbaka till
header('Location: /index.php');
exit;


// echo $feature[0]; blir pool i utskrift om man tryckt i den..