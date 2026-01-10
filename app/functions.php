<?php

declare(strict_types=1);

function redirect(string $path)
{
    header("Location: $path");
    exit;
}


function getIslandStars(int $island_id = 213): int
{
    global $client;

    try {
        $response = $client->request('GET', 'islands');
        $islands = json_decode($response->getBody()->getContents(), true);

        $key = array_search($island_id, array_column($islands, 'id'));
        if ($key !== false && isset($islands[$key]['stars'])) {
            return (int)$islands[$key]['stars'];
        }
    } catch (\Throwable $e) {
        
    }

    return 0;
}
