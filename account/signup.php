<?php

include("../db/conn.php");
include("../misc/safe.php");

if (isset($_SESSION['user'])){
    $stmt = $pdo->prepare("SELECT `status` FROM account WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    if ($stmt->fetchAll()[0]['status'] == 'not_verified'){
        header("Location: ../mail/verify.php");
        exit();
    } else {
    header("Location: dashboard");
    exit();
    }

}

if (isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['email'])) {
    $user = safevar($_POST['user']);
    $pass = safevar($_POST['pass']);
    $email = safevar($_POST['email']);
}

if (empty($user)) {
    header("Location: index.php?code=1S");
    exit();
} else if (empty($pass)) {
    header("Location: index.php?code=2S");
    exit();
} else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?code=3S");
    exit();
} else if (!username($user)) {
    header("Location: index.php?code=4S");
    exit();
} else {
    $stmt = $pdo->prepare("SELECT user, email FROM account WHERE user=? or email=?");
    $stmt->execute([$user, $email]);
    $row = $stmt->fetchAll();
    $existingUser = $row[0]['user'];
    $existingEmail = $row[0]['email'];

    if ($existingUser or $existingEmail) {
        header("Location: index.php?code=5S");
        exit();
    } else {

        try {
            $stmt = $pdo->prepare("INSERT INTO account (user, pass, email) VALUES (?, ?, ?)");
            $stmt->execute([$user, password_hash($pass, PASSWORD_DEFAULT), $email]);
            header("Location: index.php?code=0S");
            exit();
        } catch (Exception $e) {
            header("Location: index.php?code=6S");
            exit();
        }
    }


}
?>