<?php

declare(strict_types=1);

function getAllRooms(PDO $database): array
{
    $statement = $database->prepare('SELECT * FROM rooms');
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}