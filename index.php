<?php 

require __DIR__ . '/app/autoload.php';

$rooms = getAllRooms($database);
$features = getActiveFeatures($database);
$offers = getActivePackageOffer($database);

//Get list 
function formatFeaturesList($featureNames): string
{

    if (empty($featureNames)) {
        return 'no features';
    }
    
    $features = is_array($featureNames) ? $featureNames : explode(',', $featureNames);
    
    $count = count($features);
    
    if ($count === 1) {
        return $features[0];
    }
    
    if ($count === 2) {
        return $features[0] . ' and ' . $features[1];
    }
    
    // 3+
    $last = array_pop($features);
    return implode(', ', $features) . ' and ' . $last;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOST HOTEL</title>
    <link rel="stylesheet" href="<?= $config['assets']['css']; ?>">
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

                    <div class="calendar_days">
                        <?php foreach ($config['calendar_days'] as $weekdays) : ?>
                            <div class="weekday"><?= $weekdays ?></div>
                        <?php endforeach ?>
                    </div>

                    <section class="calendar">
                        <?php
                        $booked = getBookedDaysForRoom($database, $room['id']);
                        for ($i = 1; $i <= 34; $i++) :
                            if ($i < 4) {
                                ?><div class="empty"></div><?php
                            }else if (in_array($i-3, $booked)){
                                ?><div class="day booked"><?= $i-3; ?></div><?php
                            } else if (($i % 7) === 0 || ($i % 7) === 6) {
                                ?><div class="day weekend"><?= $i-3; ?></div><?php
                            }
                            else {
                                ?><div class="day"><?= $i-3; ?></div><?php
                            }     
                        endfor;?>
                    </section>
                </div>
                
                
            </div>
        <?php endforeach ?>
    </section>


    <!-- OFFER -->
    <section class="offer">
        <?php foreach ($offers as $offer) : ?>
            <h2>OFFER</h2>
            <h3><?= $offer['package_name'] ?></h3>
            <p>Book 
                <?= $offer['nights'] > 1 ? ($offer['nights'] . ' nights') : 'one night' ?>
                at <?= $offer["room_name"] ?>
                with <?= $offer['count_activities'] > 1 ? ($offer['count_activities'] . ' activities: ') : 'one activity: ' ?>
                <?= formatFeaturesList($offer["feature_names"]) . ', ' ?>
                for <?= $offer['package_price'] ?> credits!
            </p>
        <?php endforeach ?>
            
    </section>

    <!--**** BOOKING SECTION ****-->
    <section class="booking_container">


        <!-- Display error/success messages INSIDE booking section -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="message error">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="message success">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>


        <form class="booking" action="<?= $config['paths']['posts']['create_booking']; ?>" method="post">

            <!-- ask for guestname -->
            <label for="name">Your name:</label>
            <input type="text" id="name" name="name" autocomplete="name"/>

            <!-- ask for guest api-key-->
            <label for="api_key">Api-key:</label>
            <input type="text" id="api_key" name="api_key" autocomplete="name"/>
            
            <!-- print features-list -->
            <p class="features-heading"><strong>Features</strong></p>
                <?php foreach ($features as $feature) : ?>
                    <div>
                        <input type="checkbox" 
                               id="feature-<?= $feature['id'] ?>" 
                               name="features[]" 
                               value="<?= $feature['id'] ?>"
                               data-price="<?= $feature['price'] ?>"
                               class="feature-checkbox">
                        <label for="feature-<?= $feature['id'] ?>">
                            <?= $feature['name'] ?> (<?= $feature['price'] ?> credits)
                        </label>
                    </div>
                <?php endforeach ?>

            <!-- room dropdown -->
            <label for="room-select">Pick room</label>
            <select name="room_id" id="room-select">
                <?php foreach ($rooms as $room) : ?>
                    <option value="<?= $room['id'] ?>" data-price="<?= $room['price'] ?>">
                        <?= $room['name'] ?> - <?= $room['category'] ?> (<?= $room['price'] ?> credits/night)
                    </option>
                <?php endforeach ?>
            </select>
            
            <!-- Arrival/Departure calendar -->
            <label for="arrival">Arrival</label>
            <input 
                type="date" 
                name="arrival" 
                class="arrival" 
                id="arrival" 
                value="2026-01-01"
                min="2026-01-01" 
                max="2026-01-31"
            />
            
            <label for="departure">Departure</label>
            <input 
                type="date" 
                name="departure" 
                class="departure" 
                id="departure" 
                min="2026-01-01" 
                max="2026-01-31"
            />

            <!-- display updated price from choices -->
            <p><strong>Total Price: <span id="total-price">0</span> Dharma beers</strong></p>

            <button type="submit" class="btn">BOOK</button>
        </form>
    </section>
    
    <script>
        let currentDiscount = 0;

        async function checkDiscount() {
            const name = document.getElementById('name').value.trim();
            
            if (!name) {
                currentDiscount = 0;
                updatePrice();
                return;
            }
            
            try {
                const response = await fetch(`app/posts/check-discount.php?name=${encodeURIComponent(name)}`);
                const data = await response.json();
                currentDiscount = data.discount_multiplier;
                updatePrice();
                
            } catch (error) {
                console.error('Error checking discount:', error);
            }
        }

        async function updatePrice() {
            let total = 0;

            const roomSelect = document.getElementById('room-select');
            const roomPrice = parseInt(roomSelect.options[roomSelect.selectedIndex].dataset.price) || 0;
            const roomId = roomSelect.value;

            const arrival = document.getElementById('arrival').value;
            const departure = document.getElementById('departure').value;

            let nights = 0;
            if (arrival && departure) {
                const arrivalDate = new Date(arrival);
                const departureDate = new Date(departure);
                nights = Math.max(0, (departureDate - arrivalDate) / (1000 * 60 * 60 * 24));
            }

            const selectedFeatures = Array.from(document.querySelectorAll('.feature-checkbox:checked'))
                .map(cb => cb.value);

            //check if package price exist
            let packagePrice = null;
            if (selectedFeatures.length > 0 && nights > 0) {
                try {
                    const response = await fetch(`app/posts/check-package.php?room_id=${roomId}&features=${selectedFeatures.join(',')}&nights=${nights}`);
                    const text = await response.text();
                    console.log('Raw response:', text);
                    const data = JSON.parse(text);
                    if (data.package) {
                        packagePrice = data.package_price;
                    }
                } catch (error) {
                    console.error('Error checking package:', error);
                }
            }

            if (packagePrice !== null) {
                total = Math.round(packagePrice);

            } else {
                total = Math.round(roomPrice * nights);

                document.querySelectorAll('.feature-checkbox:checked').forEach(checkbox => {
                    total += parseInt(checkbox.dataset.price) || 0;
                });
            }

            if (currentDiscount > 0) {
                total = Math.round(total * currentDiscount);
            }

            document.getElementById('total-price').textContent = total;
        }

        // name input field
        document.getElementById('name').addEventListener('blur', checkDiscount);

        // eventlisteners
        document.getElementById('room-select').addEventListener('change', updatePrice);
        document.getElementById('arrival').addEventListener('change', updatePrice);
        document.getElementById('departure').addEventListener('change', updatePrice);
        document.querySelectorAll('.feature-checkbox').forEach(cb => {
            cb.addEventListener('change', updatePrice);
        });

        updatePrice();

    </script>
</body>
</html>