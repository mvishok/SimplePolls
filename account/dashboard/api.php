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
$apiKey = base64_encode(random_bytes(32));
$stmt = $pdo->prepare("UPDATE account SET api = ? WHERE user=?");
$stmt->execute([$apiKey, $_SESSION['user']]);
header("Location: settings.php");
exit();

?>