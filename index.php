<?php 

require __DIR__ . '/app/autoload.php';

$rooms = getAllRooms($database);
$features = getActiveFeatures($database);
$offers = getActivePackageOffer($database);


// Group feature after activity
$grouped_features = [];
foreach ($features as $feature) {
    $activity = $feature['activities'];
    if (!isset($grouped_features[$activity])) {
        $grouped_features[$activity] = [];
    }
    $grouped_features[$activity][] = $feature;
}

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

    <?php require __DIR__ . '/views/header.php'; ?>

<body>


    <!--**** ROOM SECTION ****-->
    <section class="card_container">
        <?php foreach ($rooms as $room) : ?>

            <div class="card">
                <h1 class="card_h1"><?= $room['name']?></h1>
                <h2 class="card_h2"><?= $room['category'] ?></h2>
                <div class="card_img-holder">
                    <img class="card_img" src="<?= $config['assets']['images']['rooms'] . $room['image'] ?>" alt="<?= htmlspecialchars($room['name']) ?>">
                </div>
                <p><?= $room['price'] ?> / night</p>
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

    <section class="flex_section">

        <!--**** BOOKING SECTION ****-->
        <?php require __DIR__ . '/views/booking-form.php'; ?>

        <!-- OFFER -->
        <section class="offer_section">
            <h2>OFFERS</h2>
            <?php foreach ($offers as $offer) : ?>
                <article class="offer">
                    <h3><?= $offer['package_name'] ?></h3>
                    <p>Book 
                        <?= $offer['nights'] > 1 ? ($offer['nights'] . ' nights') : 'one night' ?>
                        at <?= $offer["room_name"] ?>
                        with <?= $offer['count_activities'] > 1 ? ($offer['count_activities'] . ' activities: ') : 'one activity, ' ?>
                        <?= formatFeaturesList($offer["feature_names"]) . ', ' ?>
                        for <?= $offer['package_price'] ?> credits!
                    </p>
                </article>
            <?php endforeach ?>
        </section>
                
        

    </section>

</body>
</html>