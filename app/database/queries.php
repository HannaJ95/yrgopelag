<?php

declare(strict_types=1);

function getAllRooms(PDO $database): array
{
    $statement = $database->prepare('SELECT * FROM rooms');
    $statement->execute();
    return $statement->fetchAll();
}

function getActiveFeatures(PDO $database): array
{
    $statement = $database->prepare('SELECT * FROM features WHERE active = 1');
    $statement->execute();
    return $statement->fetchAll();
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

function getActivePackageOffer(PDO $database) : array {

    $stmt = $database->prepare("
        SELECT 
            p.id as package_id,
            p.name as package_name,
            p.room_id,
            p.price as package_price,
            p.active,
            p.number_of_nights as nights,
            r.name as room_name,
            r.category as room_category,
            GROUP_CONCAT(f.name) as feature_names,
            COUNT(f.name) as count_activities
        FROM packages p
        JOIN rooms r ON p.room_id = r.id
        LEFT JOIN packages_features pf ON p.id = pf.package_id
        LEFT JOIN features f ON pf.feature_id = f.id
        WHERE p.active = 1
        GROUP BY p.id
    ");

    $stmt->execute();
    return $stmt->fetchAll();
}

function getHotelSettingsTable(PDO $database): array
{
    $stmt = $database->prepare("SELECT * FROM hotel_settings LIMIT 1");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}