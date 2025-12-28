<?php

declare(strict_types=1);

function getAllRooms(PDO $database): array
{
    $statement = $database->prepare('SELECT * FROM rooms');
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function getActiveFeatures(PDO $database): array
{
    $statement = $database->prepare('SELECT * FROM features WHERE active = 1');
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}


function getBookedDaysForRoom(PDO $database, int $room_id): array
{
    $stmt = $database->prepare("
        SELECT arrival_date, departure_date 
        FROM bookings 
        WHERE room_id = ?
    ");
    
    $stmt->execute([$room_id]);
    $bookings = $stmt->fetchAll();
    
    $booked_days = [];
    
    foreach ($bookings as $booking) {
        $start = new DateTime($booking['arrival_date']);
        $end = new DateTime($booking['departure_date']);
        
        while ($start < $end) {
            $booked_days[] = (int) $start->format('j');
            $start->modify('+1 day');
        }
    }
    
    return $booked_days;
}