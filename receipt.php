<?php
require __DIR__ . '/app/autoload.php';

if (!isset($_SESSION['receipt'])) {
    redirect($config['paths']['index']);
}

$receipt_response = $_SESSION['receipt'];


unset($_SESSION['receipt']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Receipt - LOST ISLAND HOTEL</title>
    <link rel="stylesheet" href="<?= $config['assets']['css']; ?>">
</head>
<body>
    <section class="receipt_container">
        <h1>Booking Confirmation</h1>
        
        <div class="receipt_details">
            <p><?php var_dump($receipt_response) ?></p>

        </div>

        <a href="<?= $config['paths']['index']; ?>" class="btn">Back to Home</a>

    </section>
</body>
</html>