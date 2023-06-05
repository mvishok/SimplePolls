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

if ($_GET['action'] == 'acc') {
    $pdo->prepare("DELETE FROM account WHERE user=?")->execute([$_SESSION['user']]);
    $pdo->prepare("UPDATE poll SET `owner`='Deleted Account' WHERE `owner`=?")->execute([$_SESSION['user']]);
    session_destroy();
    header("Location: logout.php");
    exit();
} else if ($_GET['action'] == 'poll') {
    if (isset($_GET['pid'])){
        $pid = safevar($_GET['pid']);
        $pdo->prepare("DELETE FROM poll WHERE id=?")->execute([$pid]);
        header("Location: index.php");
        exit();
    } else {
        echo "Error";
    }
}
?>