<?php 
session_start();
include("../db/conn.php");
if (isset($_SESSION['user'])){
    $stmt = $pdo->prepare("SELECT `status` FROM account WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    if ($stmt->fetchAll()[0]['status'] == 'verified'){
        header("Location: ../account/dashboard");
        exit();
    }
} else {
    header("Location: ../account");
    exit();
}

if (isset($_POST['1']) && isset($_POST['2']) && isset($_POST['3']) && isset($_POST['4'])){

    #Get entered otp
    $inOTP = $_POST['1'].$_POST['2'].$_POST['3'].$_POST['4'];

    #Get existing OTP
    $stmt = $pdo->prepare("SELECT `otp` FROM verify WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    $OTP = $stmt->fetchAll()[0]['otp'];

    #Check if they match
    if ($inOTP == $OTP){
        try {
            $stmt = $pdo->prepare("UPDATE account SET status = 'verified' WHERE user=?");
            $stmt->execute([$_SESSION['user']]);
        } catch (Exception $e) {
            echo "Error occured";
            exit();
        }
        $stmt = $pdo->prepare("DELETE FROM verify WHERE user=?");
        $stmt->execute([$_SESSION['user']]);

        header("Location: ../account/dashboard");
        exit();
    } else {
        $incorrect = true;
    }

} else {

    #Generate OTP
    $OTP = mt_rand(1111, 9999);

    #Delete existing OTP (if exist)
    $stmt = $pdo->prepare("DELETE FROM verify WHERE user=?");
    $stmt->execute([$_SESSION['user']]);

    #INSERT OTP into db
    try {
        $stmt = $pdo->prepare("INSERT INTO verify VALUES (?, ?)");
        $stmt->execute([$_SESSION['user'], $OTP]);
    } catch (Exception $e) {
        echo "Error occured";
        exit();
    }

    #Get user's mail id
    $stmt = $pdo->prepare("SELECT `email` FROM account WHERE user=?");
    $stmt->execute([$_SESSION['user']]);
    $email = $stmt->fetchAll()[0]['email'];

    #Finalize body of verification mail
    $body = file_get_contents('templates/verify.html');
    $body = str_replace("{{user}}", $_SESSION['user'], $body);
    $body = str_replace("{{otp}}", $OTP, $body);

    #Set subject and headers
    $subject = "Verify your SimplePolls account";
    $headers = 'MIME-Version: 1.0'."\r\n";
    $headers .="Content-Type: text/html\r\n";
    $headers .= "From: no-reply@vishok.poll.tech\r\n". 
    'Reply-To: ' . $email. "\r\n" . 
    'X-Mailer: PHP/' . phpversion();

    #Send it
    mail($email,$subject,$body,$headers);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
   <title>OTP Verification</title>
    <link rel="stylesheet" href="style.css" />
    <!-- Boxicons CSS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/boxicons/2.1.0/dist/boxicons.js" integrity="sha512-Dm5UxqUSgNd93XG7eseoOrScyM1BVs65GrwmavP0D0DujOA8mjiBfyj71wmI2VQZKnnZQsSWWsxDKNiQIqk8sQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="script.js" defer></script>
  </head>
  <body>
    <div class="container">
      <header>
        <i class="bx bxs-check-shield"></i>
      </header>
      <h4>Enter OTP Code Sent To Your Mail ID</h4>
      <?php if (isset($incorrect)){?> <p style="color:red;">ERROR: Incorrect OTP</p> <?php } ?>
      <form action="#" method="POST">
        <div class="input-field">
          <input name='1' type="number" />
          <input name='2' type="number" disabled />
          <input name='3' type="number" disabled />
          <input name='4' type="number" disabled />
        </div>
        <button>Verify OTP</button>
      </form>
    </div>
  </body>
</html>