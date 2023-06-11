<?php
include("../../db/conn.php");
include("../../misc/safe.php");
session_start();
error_reporting(0);
if (!isset($_SESSION['user'])) {
    header('Location: ../');
    exit();
} else {
    $stmt = $pdo->prepare("SELECT `status` FROM account WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    if ($stmt->fetchAll()[0]['status'] == 'not_verified') {
        header("Location: ../../mail/verify.php");
        exit();
    }
}
$sucess = false;
if (isset($_POST['name']) && isset($_POST['email']) && isset($_POST['password'])) {
    $givenUser = safevar($_POST['name']);
    $givenEmail = safevar($_POST['email']);
    $givenPass = safevar($_POST['password']);
    if (isset($_POST['new'])) {
        $givenNewPass = safevar($_POST['new']);
    }

    $stmt = $pdo->prepare("SELECT `pass`, email, user FROM account WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    $rows = $stmt->fetchAll();
    $dbHash = $rows[0]['pass'];
    $dbEmail = $rows[0]['email'];
    $dbUser = $rows[0]['user'];
    if (password_verify($givenPass, $dbHash)) {

        if ($givenEmail != $dbEmail) {
            $stmt = $pdo->prepare("SELECT email FROM account WHERE email=?");
            $stmt->execute([$givenEmail]);
            $existingEmail = $stmt->fetchAll()[0]['email'];
            if ($existingEmail) {
                echo "ERROR: Email already exists";
                exit();
            } else {
                $stmt = $pdo->prepare("UPDATE account SET email = ? , `status` = 'not_verified' WHERE user=?");
                $stmt->execute([$givenEmail, $_SESSION['user']]);
            }
            $sucess = true;
        }
        if (isset($newpass)) {
            $stmt = $pdo->prepare("UPDATE account SET pass = ? WHERE user=?");
            $stmt->execute([password_hash($givenNewPass, PASSWORD_DEFAULT), $_SESSION['user']]);
            $sucess = true;
        }
        if ($givenUser != $_SESSION['user']) {
            $stmt = $pdo->prepare("SELECT user FROM account WHERE user=?");
            $stmt->execute([$givenUser]);
            $row = $stmt->fetchAll();
            $existingUser = $row[0]['user'];
            if ($existingUser) {
                echo "ERROR: Username already exists";
                exit();
            } else {
                $stmt = $pdo->prepare("UPDATE account SET user = ? WHERE user=?");
                $stmt->execute([$givenUser, $_SESSION['user']]);
                $_SESSION['user'] = $givenUser;
            }
            $sucess = true;
        }
    }

    $stmt = $pdo->prepare("SELECT api FROM account WHERE user=?");
    $stmt->execute([$givenUser]);

    $api = $stmt->fetchAll()[0]['api'];
    $user = $givenUser;
    $email = $givenEmail;
} else {
    $stmt = $pdo->prepare("SELECT user, email, api FROM account WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    $row = $stmt->fetchAll()[0];
    $user = $row['user'];
    $email = $row['email'];
    $api = $row['api'];
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-CB8848C3QW"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());

        gtag('config', 'G-CB8848C3QW');
    </script>
    <link rel="stylesheet" href="https:
        integrity=" sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin=" anonymous" referrerpolicy="no-referrer" />
    <meta charset="UTF-8">
    <title>Account Settings</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #EEEEEE;
            margin: 0;
        }

        .sidebar {
            width: 200px;
            background-color: #333;
            color: #fff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 60px;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar li {
            padding: 10px;
        }

        .sidebar a {
            color: #fff;
            text-decoration: none;
        }

        .sidebar a:hover {
            opacity: 0.8;
        }

        .dashboard {
            margin-left: 200px;
            padding: 20px;
        }

        .dashboard h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
        }

        .settings-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .settings-form label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .settings-form input[type="text"],
        .settings-form input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .settings-form button {
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .menu-button {
            color: #333;
            font-size: 24px;
            cursor: pointer;
            position: fixed;
            left: 20px;
            top: 20px;
            z-index: 999;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h3 style="position: absolute; top: 5px; left: 5px;">SimplePolls</h3>
        <ul>
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="poll.php"><i class="fas fa-plus"></i> New Poll</a></li>
            <li><a href=""><i class="fas fa-cog"></i> Account Settings</a></li>
            <li><a href="#"><i class="fas fa-trash-alt"></i> Delete Account</a></li>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a></li>
        </ul>
    </div>

    <div class="dashboard">
        <h2>Account Settings</h2>
        <center>
            <p style="color:green">
                <?php if ($sucess) {
                    echo "Account changes saved sucessfully";
                } ?>
            </p>
        </center>
        <div class="settings-form">
            <form id="settings" action="#" method="POST">
                <label for="name">Username:</label>
                <input type="text" id="name" name="name" placeholder="Enter your username" value="<?php echo $user; ?>">

                <label for="email">Email:</label>
                <input type="text" id="email" name="email" placeholder="Enter your email" value="<?php echo $email; ?>">

                <label for="password">New Password:</label>
                <input type="password" id="password" name="new"
                    placeholder="Enter your new password, Leave empty to not change">

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" placeholder="Enter your current password">

                <label for="api_key">API:</label>
                <div style="display: flex;">
                    <input type="text" id="api_key" name="api_key" value="<?php echo $api; ?>" disabled>
                    <button type="button" onclick="window.location.href='api.php'" style="margin-left: 10px;">Change</button>
                </div>

                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelector('.menu-button').addEventListener('click', function () {
            document.querySelector('.sidebar').classList.toggle('active');
        });
    </script>
</body>

</html>