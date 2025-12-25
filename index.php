<?php 

require __DIR__ . '/app/autoload.php';

$rooms = getAllRooms($database);
$features = getActiveFeatures($database);

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

    <!--**** ROOM SECTION ****-->
    <section class="card_container">
        <?php foreach ($rooms as $room) : ?>

            <div class="card">
                <h1 class="card_h1"><?= $room['name']?></h1>
                <h2 class="card_h2"><?= $room['category'] ?></h2>
                <div class="card_img-holder">
                    <img class="card_img">
                </div>
                <p><?= $room['price'] ?></p>
                <div class="card_calendar">


                    <section class="calendar">
                        <?php
                        $booked = getBookedDaysForRoom($database, $room['id']);
                        for ($i = 1; $i <= 31; $i++) :
                            if (in_array($i, $booked)){
                                ?><div class="day booked"><?= $i; ?></div><?php
                            } else if (($i % 7) === 0 || ($i % 7) === 6) {
                                ?><div class="day weekend"><?= $i; ?></div><?php
                            }
                            else {
                                ?><div class="day"><?= $i; ?></div><?php
                            }     
                        endfor;?>
                    </section>
                </div>
                
                
            </div>
        <?php endforeach ?>
    </section>

    <!--**** BOOKING SECTION ****-->
    <section class="booking_container">
        <form class="booking" action="app/posts/create-booking.php" method="post">

            <!-- ask for guestname -->
            <label for="name">Your name (guest_id)</label>
            <input type="text" id="name" name="name"/>
            
            <!-- ask for transfer-code-->
            <label for="transfercode">Transfer-code:</label>
            <input type="text" id="transfercode" name="transfercode"/>
            
            <!-- print features-list -->
            <label for="feature">Features</label>
                <?php foreach ($features as $feature) : ?>
                    <div>
                        <input type="checkbox" id="feature-<?= $feature['id'] ?>" name="features[]" value="<?= $feature['id'] ?>">
                        <label for="feature-<?= $feature['id'] ?>"><?= $feature['name'] ?></label>
                    </div>
                <?php endforeach ?>

            <!-- room dropdown -->
            <label for="room_id">Pick room</label>
            <select name="room_id">
                <?php foreach ($rooms as $room) : ?>
                    <option value="<?= $room['id'] ?>"><?= $room['category'] ?></option>
                <?php endforeach ?>
            </select>
            
            <!-- Arrival/Departure calendar -->
            <label for="arrival">Arrival</label>
            <input type="date" name="arrival" class="arrival" id="arrival" min="2026-01-01" max="2026-01-31">
            
            <label for="departure">Departure</label>
            <input type="date" name="departure" class="departure" id="departure" min="2026-01-01" max="2026-01-31">


            <button type="submit" class="btn">BOOK</button>
        </form>
    </section>
    
</body>
</html>