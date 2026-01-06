<?php

require __DIR__ . '/../app/autoload.php';

use GuzzleHttp\Exception\RequestException;

//get islandFeatures
try {
    $response = $client->request('POST', 'islandFeatures', [
        'json' => [
            'user' => $config['user'],
            'api_key' => $_ENV['API_KEY']
        ]
    ]);

    $response = $response->getBody()->getContents();
    $islandFeatures = json_decode($response, true);
} catch (RequestException $e) {
    if ($e->hasResponse()) {
        $error = json_decode($e->getResponse()->getBody()->getContents(), true);
        $_SESSION['admin']['error'] = $error['error'] ?? "Failed to load island features: " . $e->getMessage();
    } else {
        $_SESSION['admin']['error'] = "Failed to connect to Central Bank: " . $e->getMessage();
    }
    $islandFeatures = ['features' => []];
}


//get accountInfo - credits
try {
    $response = $client->request('POST', 'accountInfo', [
        'json' => [
            'user' => $config['user'],
            'api_key' => $_ENV['API_KEY']
        ]
    ]);

    $response = $response->getBody()->getContents();
    $accountInfo = json_decode($response, true);
} catch (RequestException $e) {
    if ($e->hasResponse()) {
        $error = json_decode($e->getResponse()->getBody()->getContents(), true);
        $_SESSION['admin']['error'] = $error['error'] ?? "Failed to load account info: " . $e->getMessage();
    } else {
        $_SESSION['admin']['error'] = "Failed to connect to Central Bank: " . $e->getMessage();
    }
    $accountInfo = ['credit' => 0];
}

// get features table
$stmt = $database->query('SELECT * FROM features');
$features = $stmt->fetchAll();

// get rooms table
$stmt = $database->query('SELECT * FROM rooms');
$rooms = $stmt->fetchAll();

// get package table with booked features
$stmt = $database->query('SELECT * FROM packages');
$packages = $stmt->fetchAll();

function getPackageFeatures(PDO $database, $package_id): array
{

    $stmt = $database->prepare("
        SELECT 
            f.id,
            f.name,
            f.activities,
            f.tier,
            f.price
        FROM packages_features pf
        JOIN features f ON pf.feature_id = f.id
        WHERE pf.package_id = :package_id
    ");

    $stmt->execute([':package_id' => $package_id]);
    $features = $stmt->fetchAll();

    return $features;
}


//get bookings table with booked features and guests
$stmt = $database->query("
    SELECT 
        b.*,
        g.name as guest_name,
        r.name as room_name,
        r.category as room_category,
        GROUP_CONCAT(f.name) as features
    FROM bookings b
    JOIN guests g ON b.guest_id = g.id
    JOIN rooms r ON b.room_id = r.id
    LEFT JOIN bookings_features bf ON b.id = bf.booking_id
    LEFT JOIN features f ON bf.feature_id = f.id
    GROUP BY b.id
    ORDER BY b.created_at DESC
");

$bookings = $stmt->fetchAll();

// get features as array
foreach ($bookings as &$booking) {
    $booking['features'] = $booking['features']
        ? explode(',', $booking['features'])
        : [];
}



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <link rel="stylesheet" href="<?= $config['assets']['css']; ?>">
</head>

<body>

    <!--*** LOG OUT OPTION VISIBLE IF SIGNED IN ***-->
    <nav class="admin-nav">
        <?php if (isset($_SESSION['user'])) { ?>
            <a class="nav-link" href="../app/admin-users/logout.php">Logout</a>
        <?php } ?>
    </nav>

    <header>
        <h1 class="admin-header">
            <?= $config['hotel_name'] ?>
        </h1>
        <h2 class="admin-admin">Admin page</h2>
    </header>


    <!--*** LOG IN ***-->
    <?php if (!isset($_SESSION['user'])) { ?>
        <h3 class="admin_text-signin">Sign in to admin-page:</h3>

        <!-- display error if something wrong when tried to log in-->
        <?php if (isset($_SESSION['admin']['error'])) { ?>
            <p><?= $_SESSION['admin']['error'] ?></p>

        <?php } ?>

        <!-- input fields for email and password -->
        <form action="<?= $config['paths']['posts']['admin']['login']; ?>" method="post">
            <div>
                <label for="email" class="admin-visually-hidden">Email</label>
                <input class="form-control" type="email" name="email" id="email" placeholder="E-mail" required>
            </div>

            <div>
                <label for="password" class="admin-visually-hidden">Password</label>
                <input class="form-control" type="password" name="password" id="password" placeholder="Password" required>
            </div>

            <button type="submit" class="btn btn-primary">Login</button>
        </form>
    <?php } ?>


    <!-- WHEN LOGGED IN -->
    <?php if (isset($_SESSION['user'])) : ?>
        <p>Welcome, <?php echo $_SESSION['user']['name']; ?>!</p>

        <!-- ADMIN NOTIFICATIONS -->
        <?php if (isset($_SESSION['admin']['error'])): ?>
            <div class="admin-notification error" id="notification">
                <span><?= htmlspecialchars($_SESSION['admin']['error']) ?></span>
                <button class="close-btn" onclick="closeNotification()">&times;</button>
            </div>
            <?php unset($_SESSION['admin']['error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['admin']['success'])): ?>
            <div class="admin-notification success" id="notification">
                <span><?= htmlspecialchars($_SESSION['admin']['success']) ?></span>
                <button class="close-btn" onclick="closeNotification()">&times;</button>
            </div>
            <?php unset($_SESSION['admin']['success']); ?>
        <?php endif; ?>

        <!-- DISPLAY ACOUNT CREDITS -->
        <p>Credits: <?= $accountInfo['credit'] ?></p>


        <!-- BOUGHT FEATURES CENTRALBANKEN -->
        <h2> BOUGHT FEATURES CENTRALBANKEN:</h2>
        <table>
            <thead>
                <th>Feature</th>
                <th>Activity</th>
                <th>Tier</th>
            </thead>
            <tbody>
                <?php foreach ($islandFeatures['features'] as $islandFeature) : ?>
                    <tr>
                        <td><?= htmlspecialchars($islandFeature['feature']) ?></td>
                        <td><?= htmlspecialchars($islandFeature['activity']) ?></td>
                        <td><?= htmlspecialchars($islandFeature['tier']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>


        <!-- DISPLAY DATABASE FEATURES TABLE WITH TOOGLE, CHANGE PRICE AND INSERT FUNCTION -->
        <h2>DATABASE FEATURES TABLE:</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Activity</th>
                    <th>Tier</th>
                    <th>Price</th>
                    <th>Update price</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($features as $feature): ?>
                    <tr>
                        <td><?= $feature['id'] ?></td>
                        <td><?= htmlspecialchars($feature['name']) ?></td>
                        <td><?= htmlspecialchars($feature['activities']) ?></td>
                        <td><?= htmlspecialchars($feature['tier']) ?></td>
                        <td><?= $feature['price'] ?></td>
                        <td>
                            <form method="POST" action="<?= $config['paths']['posts']['admin']['update_price']; ?>" style="display:inline;">
                                <input type="hidden" name="feature_id" value="<?= $feature['id'] ?>">
                                <input type="text" name="price">
                                <button type="submit">OK</button>
                            </form>

                        </td>
                        <td><?= $feature['active'] ? '✅ Active' : '❌ Inactive' ?></td>
                        <td>
                            <form method="POST" action="<?= $config['paths']['posts']['admin']['toggle_active']; ?>" style="display:inline;">
                                <input type="hidden" name="feature_id" value="<?= $feature['id'] ?>">
                                <button type="submit">Toggle</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <!--INSERT FEATURES FORM (BUY FROM CENTRALBANKEN)-->
                <td><?= $feature['id'] + 1 ?></td>
                <form method="POST" action="<?= $config['paths']['posts']['admin']['insert_features']; ?>">
                    <td><input type="text" name="name"></td>
                    <td><input type="text" name="activities"></td>
                    <td><input type="text" name="tier"></td>
                    <td><input type="number" name="price"></td>
                    <td></td>
                    <td>✅ Active</td>
                    <td><button type="submit">INSERT</button></td>
                </form>

            </tbody>
        </table>


        <!-- DISPLAY DATABASE ROOMS TABLE WITH CHANGE PRICE FUNCTION -->
        <h2>DATABASE ROOMS TABLE:</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Update price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= $room['id'] ?></td>
                        <td><?= htmlspecialchars($room['name']) ?></td>
                        <td><?= htmlspecialchars($room['category']) ?></td>
                        <td><?= htmlspecialchars($room['price']) ?></td>
                        <td>
                            <form method="POST" action="<?= $config['paths']['posts']['admin']['update_price']; ?>" style="display:inline;">
                                <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                                <input type="text" name="price">
                                <button type="submit">OK</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>



        <!-- DISPLAY DATABASE PACKAGES TABLE -->
        <h2>DATABASE PACKAGES TABLE:</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>room_id</th>
                    <th>Price</th>
                    <th>Number of nights</th>
                    <th>Features</th>
                    <th>Active</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($packages as $package): ?>
                    <tr>
                        <td><?= $package['id'] ?></td>
                        <td><?= htmlspecialchars($package['name']) ?></td>
                        <td><?= htmlspecialchars($package['room_id']) ?></td>
                        <td><?= $package['price'] ?></td>
                        <td><?= htmlspecialchars($package['number_of_nights']) ?></td>
                        <td>
                            <?php
                            $features = getPackageFeatures($database, $package['id']);
                            foreach ($features as $f) : ?>
                                <ul>
                                    <li><?= htmlspecialchars($f['name']) ?></li>
                                </ul>

                            <?php endforeach ?>
                        </td>
                        <td><?= $package['active'] ? '✅ Active' : '❌ Inactive' ?></td>
                        <td>
                            <form method="POST" action="<?= $config['paths']['posts']['admin']['toggle_active']; ?>" style="display:inline;">
                                <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                                <button type="submit">Toggle</button>
                            </form>
                        </td>

                    </tr>
                <?php endforeach; ?>
                <!-- INSERT PACKAGE ROW -->
                <tr>
                    <td><?= $package['id'] + 1 ?></td>
                    <form method="POST" action="<?= $config['paths']['posts']['admin']['insert_package']; ?>">
                        <td><input type="text" name="name"></td>
                        <td><input type="text" name="room_id"></td>
                        <td><input type="text" name="price"></td>
                        <td><input type="text" name="number_of_nights"></td>
                        <td>
                            <?php foreach ($features as $feature) : ?>
                                <ul>
                                    <li>
                                        <input type="checkbox"
                                            id="feature-<?= $feature['id'] ?>"
                                            name="features[]"
                                            value="<?= $feature['id'] ?>"
                                            data-price="<?= $feature['price'] ?>"
                                            class="feature-checkbox">
                                        <label for="feature-<?= $feature['id'] ?>">
                                            <?= $feature['name'] ?>
                                        </label>
                                    </li>
                                </ul>
                            <?php endforeach ?>

                        </td>
                        <td>✅ Active</td>
                        <td><button type="submit">INSERT</button></td>
                    </form>
                </tr>
            </tbody>
        </table>


        <!-- DISPLAY DATABASE BOOKINGS TABLE AND FEATURES BOOKED -->
        <h2>DATABASE BOOKINGS TABLE WITH FEATURES BOOKED:</h2>
        <table>
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Guest Name</th>
                    <th>Room</th>
                    <th>Category</th>
                    <th>Arrival</th>
                    <th>Departure</th>
                    <th>Total Cost</th>
                    <th>Features</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td><?= $booking['id'] ?></td>
                        <td><?= htmlspecialchars($booking['guest_name']) ?></td>
                        <td><?= htmlspecialchars($booking['room_name']) ?></td>
                        <td><?= htmlspecialchars($booking['room_category']) ?></td>
                        <td><?= $booking['arrival_date'] ?></td>
                        <td><?= $booking['departure_date'] ?></td>
                        <td><?= $booking['total_cost'] ?></td>
                        <td>
                            <?php if (!empty($booking['features'])): ?>
                                <ul>
                                    <?php foreach ($booking['features'] as $feature): ?>
                                        <li><?= htmlspecialchars($feature) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= $booking['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <script>
        function closeNotification() {
            const notification = document.getElementById('notification');
            if (notification) {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        }

        // Auto-hide notification after 3 seconds
        window.addEventListener('DOMContentLoaded', () => {
            const notification = document.getElementById('notification');
            if (notification) {
                setTimeout(() => {
                    closeNotification();
                }, 3000);
            }
        });
    </script>
    
</body>
</html>