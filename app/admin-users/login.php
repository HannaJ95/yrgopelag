<?php

declare(strict_types=1);

require __DIR__ . '/../autoload.php';

// Check if email and password exists in database
if (isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']);

    $statement = $database->prepare('SELECT * FROM admin_users WHERE email = :email');
    $statement->bindParam(':email', $email, PDO::PARAM_STR);
    $statement->execute();

    $user = $statement->fetch(PDO::FETCH_ASSOC);

    // if admin-user don't exist, svare error in $_SESSION
    if (!$user) {
        $_SESSION['admin']['error'] = "User does not exist please try again";
        redirect($config['paths']['admin']['index']);

    }

    //verify password and save user in $_SESSION
    if (password_verify($_POST['password'], $user['password'])) {

        unset($user['password']);

        $_SESSION['user'] = $user;

        unset($_SESSION['admin']['error']);
    }
}

redirect($config['paths']['admin']['index']);
