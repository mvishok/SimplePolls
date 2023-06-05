<?php

session_start();

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


if (isset($_POST['user']) && isset($_POST['pass'])) {
    $user = safevar($_POST['user']);
    $pass = safevar($_POST['pass']);
}

if (empty($user)) {
    header("Location: index.php?code=1L");
    exit();
} else if (empty($pass)) {
    header("Location: index.php?code=2L");
    exit();
} else {
    $stmt = $pdo->prepare("SELECT `pass`, `status` FROM account WHERE user=?");
    $stmt->execute([$user]);
    $rows = $stmt->fetchAll();
    $hash = $rows[0]['pass'];
    $status = $rows[0]['status'];
    if (password_verify($pass, $hash)){
        echo "Logged in successfully!";
        $_SESSION['user'] = $user;
        if ($status == 'not_verified'){
            header("Location: ../mail/verify.php");
            exit();        
        } else {
        header('Location: index.php?code=0L');
        }
    } else {
        header("Location: index.php?code=3L");
        exit();
    }
}
?>