
<?php require __DIR__ . '/../app/autoload.php'; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
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
        <?php if (isset($_SESSION['admin']['error'])) {?>
            <p><?= $_SESSION['admin']['error'] ?></p>
            
        <?php } ?>

        <!-- input fields for email and password -->
        <form action="../app/admin-users/login.php" method="post">
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
    

    <?php if (isset($_SESSION['user'])) : ?>
        <p>Welcome, <?php echo $_SESSION['user']['name']; ?>!</p>
    <?php endif; ?>


</body>
</html>


