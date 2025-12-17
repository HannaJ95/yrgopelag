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
    //TODO: LÄGG TILL CHECK OM DEN ÄR ACTIVE ELLER EJ///////////////////////////////
    $statement = $database->prepare('SELECT * FROM features');
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}