<?php


// GET NUMBER OF HOTEL STARS FROM CENTRALBANKEN
$response = $client->request('GET', 'islands');
$islands = json_decode($response->getBody()->getContents(), true);

$key = array_search(213, array_column($islands, 'id'));
if ($key !== false) {
    $stars = $islands[$key]['stars'];
}

?>


<header class="main_header">
    <img class="header_logo" src="/assets/images/LOST_ISLAND_HOTEL1.png" alt="">
    <div class="header_stars">

        <?php for ($i=0; $i < $stars; $i++) : ?> 
            <img class="header_star" src="/assets/images/star1.png" alt="star">
        <?php endfor; ?>
    
    </div>
</header>