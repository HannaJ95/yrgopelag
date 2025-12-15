<?php 

require __DIR__ . '/app/autoload.php';

$rooms = getAllRooms($database);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOST HOTEL</title>
    <link rel="stylesheet" href="/assets/styles/app.css">
</head>
<body>

    <section class="card_container">
        <?php foreach ($rooms as $room) : ?>

            <div class="card">
                <h1 class="card_h1"><?= $room['name'] ?></h1>
                <h2 class="card_h2"><?= $room['category'] ?></h2>
                <div class="card_img_holder">
                    <img class="card_img">
                </div>
                <p><?= $room['price'] ?></p>
            </div>
            <div class="card_calendar"></div>
            <?php endforeach ?>
    </section>
    
</body>
</html>